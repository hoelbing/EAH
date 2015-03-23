<?php
/**
* PHPMyLib library with several helping functions
*
* @author i-fabrik GmbH
* @copyright 2002 by i-fabrik GmbH
* @version $Id: PUtil.php,v 1.45 2007/01/29 12:17:24 ralph Exp $
*
*/

// for backward compatibility: include PPage if older PHPMyLib version (only php4)
if ( !defined('PML_INCLUDED') ) {
    if ( !isset($PHPMyLib_path) ) $PHPMyLib_path = 'PHPMyLib/';
    if ( @is_readable($PHPMyLib_path.'PPage.php') ) {
        if ( !defined('PML_PATH') ) define('PML_PATH', $PHPMyLib_path);
        require_once($PHPMyLib_path.'PPage.php');
    } else {
        require_once('PHPMyLib/PPage.php');
        if ( !defined('PML_PATH') ) define('PML_PATH', 'PHPMyLib/');
    }
}


/**
* Die Klasse PUtil enthält einige Methoden, die bei der Bearbeitung von Webseiten häufig anfallen.
**/
class PUtil {

    /**
    * Diese Methode k&uuml;rzt div-Tags, so dass lediglich das align-Attribut erhalten bleibt
    * <b>Parameter:</b>
    * $html_source - HTML-Quelltext, welcher geparst werden soll
    * <b>Rückgabe:</b>
    * Der geparste Quelltext.
    **/
    function preg_replace_div($html) {
        $current = 0;
        while( $pos = strpos(strtolower($html), '<div', $current) ) {
            $end_div = strpos($html, '>', $pos);
            $div     = substr($html, $pos, (1+$end_div-$pos));
            $html    = str_replace($div, '{div}', $html);
            if ( preg_match("/( align=[^ >]*)/is", $div, $match) ) $div = "<div$match[1]>";
                else $div = '<div>';
            $current = $pos+strlen($div);
            $html    = preg_replace("/\{div\}/", $div, $html);
        }
        return $html;
    }
    // PUtil::preg_replace_div


    /**
    * Diese Methode zeigt Bilder an (jpeg, gif, png, swf)
    * <b>Parameter:</b>
    * $url - Pfad des auszugebenden Bildes (relativ zum Dateisystem)
    **/
    function showimg($url) {
        if ( is_readable($url) ) {
            $type = getimagesize($url);
            switch ($type[2]):
            case 1:
                header('Content-type: image/gif');
                readfile($url);
                break;
            case 2:
                header('Content-type: image/jpeg');
                readfile($url);
                break;
            case 3:
                header('Content-type: image/png');
                readfile($url);
                break;
            case 4:
                header('Content-type: image/swf');
                readfile($url);
                break;
            default:
                header('Content-type: text/plain');
                echo 'Bildformat unbekannt!';
                break;
            endswitch;
        } else {
            header('HTTP/1.0 404 Not Found');
            echo 'Bild nicht gefunden!';
        }
        exit();
    }
    // PUtil::showimg

    /**
    * Diese Methode konvertiert Internet- und eMail-Adressen in HTML Hyperlinks
    * <b>Parameter:</b>
    * $str - Zu parsender String
    * <b>Rückgabe:</b>
    * Der manipulierte String.
    **/
    function makeLinks($str) {
        $str = preg_replace("/([^\"])(http:\/\/[\\w\\-]+\.[\\w\\-]+[\\w\\-~\.\/]*)/","\\1<a href=\"\\2\" target=\"_blank\">\\2</a>",$str);
        $str = preg_replace("/([>\\s])(www\.[\\w\\-]+\.[\\w\\-]+[\\w~\.\/]*)/","\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>",$str);
        $str = preg_replace("/([>\\s])([\\w\\-~\.]+\\@[\\w\\-]+\.[\\w\\-\.]+)/","\\1<a href=\"mailto:\\2\">\\2</a>",$str);

        // falls Link am Text-Anfang:
        $str = preg_replace("/^(http:\/\/[\\w\\-]+\.[\\w\\-]+[\\w\\-~\.\/]*)/","<a href=\"\\1\" target=\"_blank\">\\1</a>",$str);
        $str = preg_replace("/^(www\.[\\w\\-]+\.[\\w\\-]+[\\w~\.\/]*)/","<a href=\"http://\\1\" target=\"_blank\">\\1</a>",$str);
        $str = preg_replace("/^([\\w\\-~\.]+\\@[\\w\\-]+\.[\\w\\-\.]+)/","<a href=\"mailto:\\1\">\\1</a>",$str);

        return $str;
    }
    // PUtil::makeLinks

    /**
    * Diese Methode dekodiert MIME-kodierte Wörter (bes. in Mail-Headern)
    * in 8-bit, wenn iso-8859-1 Kodierung vorliegt.
    * <b>Parameter:</b>
    * $str - der zu konvertierende String
    * <b>Rückgabe:</b>
    * Der konvertierte String.
    **/
    function decodeMIMEExt($str) {
        while ( ($pos=strpos($str,'=?iso-8859-1?',0)) !== FALSE ) {         // Durchlaufe den String und suche nach Delimitern der "MIME Part Three"
            if ( ($epos=strpos($str, '?=', $pos)) === FALSE ) return $str;  // Suche nach Ende des kodierten Abschnittes
            $code = substr($str, $pos+13, 1);                               // Extrahiere die Kodierungsart
            $tmp  = substr($str, $pos+15, $epos-$pos-15);                   // Extrahiere den kodierten Text

            if ( strtoupper($code)=='Q' ) {                                 // wir haben "Quoted printable"-Kodierung
                $tmp = str_replace('_', ' ', $tmp);
                // Ersetze alle kodierten Zeichen durch ihre 8-bit-Repräsentation
                $tmp = preg_replace("/=([a-fA-F0-9][a-fA-F0-9])/e","chr(hexdec(\"\\1\"))", $tmp);
            } elseif ( strtoupper($code)=='B' ) {                           // wir haben "Base64"-Kodierung
                $tmp = base64_decode($tmp);
            } else return $str;
            $str = substr_replace($str,$tmp,$pos,$epos-$pos+2);             // ersetzen des Teil-Strings durch den Bearbeiteten
        }
        return $str;
    }
    // PUtil::decodeMIMEExt


    /**
     * Diese Methode ersetzt Umlaute durch die entsprechenden
     * HTML-Zeichenfolgen
     * <b>Parameter:</b>
     * $str - der zu konvertierende String
     * <b>Rückgabe:</b>
     * Der konvertierte String.
     */
    function replace_uml($str, $uml_flag=1) {
        if ( $uml_flag==1 ) $str = str_replace ('&', '&amp;', $str);
        $str = str_replace ('Ä', '&Auml;', $str);
        $str = str_replace ('ä', '&auml;', $str);
        $str = str_replace ('Ü', '&Uuml;', $str);
        $str = str_replace ('ü', '&uuml;', $str);
        $str = str_replace ('Ö', '&Ouml;', $str);
        $str = str_replace ('ö', '&ouml;', $str);
        $str = str_replace ('ß', '&szlig;', $str);
        $str = str_replace ('€', '&euro;', $str);

        if ( $uml_flag==1 ) {
            $str = str_replace ('<', '&lt;', $str);
            $str = str_replace ('>', '&gt;', $str);
        }
        return $str ;
    }
    // PUtil::replace_uml


    /**
    * Diese Methode entfernt Umlaute (z.B. ä->ae, ß->ss)
    * <b>Parameter:</b>
    * $str - der zu konvertierende String
    * <b>Rückgabe:</b>
    * Der konvertierte String.
    **/
    function remove_uml($str) {
        $str = preg_replace ("/Ä([A-Z])/s", "AE\\1", $str);
        $str = preg_replace ("/([A-Z])Ä/s", "\\1AE", $str);
        $str = preg_replace ("/Ü([A-Z])/s", "UE\\1", $str);
        $str = preg_replace ("/([A-Z])Ü/s", "\\1UE", $str);
        $str = preg_replace ("/Ö([A-Z])/s", "OE\\1", $str);
        $str = preg_replace ("/([A-Z])Ö/s", "\\1OE", $str);
        $str = str_replace ('Ä', 'Ae', $str);
        $str = str_replace ('ä', 'ae', $str);
        $str = str_replace ('Ü', 'Ue', $str);
        $str = str_replace ('ü', 'ue', $str);
        $str = str_replace ('Ö', 'Oe', $str);
        $str = str_replace ('ö', 'oe', $str);
        $str = str_replace ('ß', 'ss', $str);
        return $str ;
    }
    // PUtil::remove_uml


    /**
    * Diese Methode erstellt eine Verbindung zu einer gewünschten Datenbank und  
    * führt dabei eine Benutzerauthentifizierung durch.
    * Geprüft wird dabei, ob der Benutzer auf der Datenbank Zugriffsrechte hat
    * <b>Parameter:</b>
    * $dbname - Name der Datenbank zu der eine Verbindung aufgebaut werden soll
    * $host - Server auf dem die Datenbank liegt (default: localhost)
    * $realm - Name des Sicherheitsbereichs(default: Passwortgeschützter Bereich)
    * <b>Rückgabe:</b>
    * $dbID - ID des Datenbankhandlers
    **/
    function authentify( $dbname, $host='localhost', $realm='Passwortgeschützter Bereich') {
        if ( isset($_SERVER) ) {
            if ( isset($_SERVER['PHP_AUTH_USER']) ) $IFAB_AUTH_USER = $_SERVER['PHP_AUTH_USER']; else $IFAB_AUTH_USER = '';
            if ( isset($_SERVER['PHP_AUTH_PW']) )   $IFAB_AUTH_PW   = $_SERVER['PHP_AUTH_PW'];   else $IFAB_AUTH_PW   = '';
        } else {
            global $HTTP_SERVER_VARS;
            if ( isset($HTTP_SERVER_VARS['PHP_AUTH_USER']) ) $IFAB_AUTH_USER = $HTTP_SERVER_VARS['PHP_AUTH_USER']; else $IFAB_AUTH_USER = '';
            if ( isset($HTTP_SERVER_VARS['PHP_AUTH_PW']) )   $IFAB_AUTH_PW   = $HTTP_SERVER_VARS['PHP_AUTH_PW'];   else $IFAB_AUTH_PW   = '';
        }

        $dbID = @mysql_connect($host, $IFAB_AUTH_USER, $IFAB_AUTH_PW);
        if ( !$dbID || !@mysql_select_db($dbname, $dbID) ) {
            Header('status: 401 Unauthorized');		// For Roxen cgi-wrapper
            Header('HTTP/1.0 401 Unauthorized');
            Header('WWW-authenticate: basic realm="'.$realm.'"');
            Header("KiSS: $IFAB_AUTH_USER");
            PUtil::showError();
            exit();
        }
        return $dbID;
    }
    // PUtil::authentify


    /**
    * Diese Methode stellt eine Datenbankverbindung her, wählt die gewünschte Datenbank und
    * führt eine Benutzerauthentifizierung durch.
    * Geprüft wird, ob Benutzer in einer speziellen Passworttabelle in der aktuellen Datenbank vorhanden ist.
    * <b>Parameter:</b>
    * $dbname - Name der Datenbank zu dr eine Verbindung aufgebaut werden soll
    * $dblogin - Login für die Datenbank
    * $Passwort für die Datenbank
    * $host - Server auf dem die Datenbank liegt (default: localhost)
    * $realm - Name des Sicherheitsbereichs (default: Passwortgeschützter Bereich)
    * $table - Tabelle mit erlaubten Benutzern (default: auth)
    * $userCol - Name der Spalte mit Benutzernamen in $table (default: user)
    * $passCol - Name der Spalte mit Benutzerpasswörtern in $table (default: passwd)
    * <b>Rückgabe:</b>
    * $dbID - ID des Datenbankhandlers
    **/
    function authentify2($dbname, $dblogin, $dbpass, $realm='Passwortgeschützter Bereich', $host='localhost', $table='auth', $userCol='user', $passCol='passwd') {

        if ( isset($_SERVER) ) {
            if ( isset($_SERVER['PHP_AUTH_USER']) ) $IFAB_AUTH_USER = $_SERVER['PHP_AUTH_USER']; else $IFAB_AUTH_USER = '';
            if ( isset($_SERVER['PHP_AUTH_PW']) )   $IFAB_AUTH_PW   = $_SERVER['PHP_AUTH_PW'];   else $IFAB_AUTH_PW   = '';
        } else {
            global $HTTP_SERVER_VARS;
            if ( isset($HTTP_SERVER_VARS['PHP_AUTH_USER']) ) $IFAB_AUTH_USER = $HTTP_SERVER_VARS['PHP_AUTH_USER']; else $IFAB_AUTH_USER	= '';
            if ( isset($HTTP_SERVER_VARS['PHP_AUTH_PW']) )   $IFAB_AUTH_PW   = $HTTP_SERVER_VARS['PHP_AUTH_PW'];   else $IFAB_AUTH_PW	= '';
        }
        $dbID  = @mysql_connect($host, $dblogin, $dbpass);
        $dbSel = @mysql_select_db($dbname, $dbID);
        $sql = "SELECT * FROM $table WHERE $userCol='$IFAB_AUTH_USER' AND $passCol=PASSWORD('$IFAB_AUTH_PW')";
        $sql = @mysql_query($sql, $dbID);
        if ( !$dbID || !$dbSel || @mysql_num_rows($sql) < 1 ) {
            header('status: 401 Unauthorized');		// For Roxen cgi-wrapper
            header('HTTP/1.0 401 Unauthorized');
            header("WWW-authenticate: basic realm=\"$realm\"");
            header("KiSS: $IFAB_AUTH_USER , $IFAB_AUTH_PW");
            PUtil::showError($imgpath);
            exit();
        }
        return ($dbID);
    }
    // PUtil::authentify2


    /**
    * Diese Methode führt eine Benutzerauthentifizierung bei bestehender Datenbankverbindung durch,
    * geprüft wird, ob Benutzer in einer eigenen Passworttabelle in der aktuellen Datenbank vorhanden ist.
    * <b>Parameter:</b>
    * $dbID - ID der aktuellen Datenbankverbindung
    * $realm - Name des Sicherheitsbereichs (default: Passwortgeschützter Bereich)
    * $table - Tabelle mit erlaubten Benutzern (default: auth)
    * $userCol - Name der Spalte mit Benutzernamen in $table (default: user)
    * $passCol - Name der Spalte mit Benutzerpasswörtern (PASSWORD od. SHA1 kodiert) in $table (default: passwd)
    **/
    function authentify3($dbID, $realm = 'Passwortgeschützter Bereich', $table='auth', $userCol='user', $passCol='passwd', $returnCol='*', $imgpath='') {

        if ( isset($_SERVER) ) {
            if ( isset($_SERVER['PHP_AUTH_USER']) ) $IFAB_AUTH_USER = $_SERVER['PHP_AUTH_USER']; else $IFAB_AUTH_USER = '';
            if ( isset($_SERVER['PHP_AUTH_PW']) )   $IFAB_AUTH_PW   = $_SERVER['PHP_AUTH_PW'];   else $IFAB_AUTH_PW   = '';
        } else {
            global $HTTP_SERVER_VARS;
            if ( isset($HTTP_SERVER_VARS['PHP_AUTH_USER']) ) $IFAB_AUTH_USER = $HTTP_SERVER_VARS['PHP_AUTH_USER']; else $IFAB_AUTH_USER	= '';
            if ( isset($HTTP_SERVER_VARS['PHP_AUTH_PW']) )   $IFAB_AUTH_PW   = $HTTP_SERVER_VARS['PHP_AUTH_PW'];   else $IFAB_AUTH_PW	= '';
        }

        $sql = "SELECT $returnCol 
                FROM $table 
                WHERE $userCol='$IFAB_AUTH_USER' AND ( 
                    $passCol=SHA1('$IFAB_AUTH_PW') OR $passCol=MD5('$IFAB_AUTH_PW') OR 
                    $passCol=PASSWORD('$IFAB_AUTH_PW') OR $passCol=OLD_PASSWORD('$IFAB_AUTH_PW') )";
        $sql = mysql_query($sql, $dbID);echo mysql_error();
        if ( mysql_num_rows($sql) < 1 ) {
            header('status: 401 Unauthorized');		// For Roxen cgi-wrapper
            header('HTTP/1.0 401 Unauthorized');
            header('WWW-authenticate: basic realm="'.$realm.'"');
            header('KiSS: '.$IFAB_AUTH_USER.' , '.$IFAB_AUTH_PW);
            PUtil::showError($imgpath);
            exit();
        }
        return mysql_fetch_row($sql);				// return the specified value

    }
    // PUtil::authentify3


    /**
    *	Diese Methode wandelt einen String in einen Quoted_printable-String (email-Kodierung im Header)
    *	<b>Parameter:</b>
    *		$str - String, der konvertiert werden soll
    *	<b>Rückgabe:</b>
    *		$str - konvertierter String
    **/
    function quoted_printable($str) {
        $phpcode = "((ord('\\1')<32)||(ord('\\1')>127)) ? \"=\".strtoupper(dechex(ord('\\1'))) :
            ((ord('\\1')==32) ? \"_\" : '\\1')";
        $str     = preg_replace("/(.)/e", $phpcode, $str);
        $str     = "=?iso-8859-1?Q?$str?=";
        return $str;
    }
    // PUtil::quoted_printable


    /**
    *	Funktion zum Anzeigen von Fehler 401 im Design der jeweiligen Webseite
    *	<b>Parameter:</b>
    *		keine
    *	<b>Rückgabe:</b>
    *		keine
    *	<b>zugehörige Dateien:</b>
    *		error.dwt - in Verzeichnis /templates aller Homedirectories auf dem Server
    *
    *	in error.dwt sollte folgendes vorhanden sein:
    *		{errNumber}	- wird durch Fehlernummer ersetzt
    *		{fehler}	- wird duch Fehlernamen ersetzt
    *		{text}		- wird durch Kurzbeschreibung des Fehlers ersetzt
    **/
    function showError( $path = '' ) {
        $dwt         = '';
        $dwt_content = '';
        if ( @is_readable($_SERVER['DOCUMENT_ROOT'].'/templates/error.dwt') ) $dwt = $_SERVER['DOCUMENT_ROOT'].'/templates/error.dwt';
            elseif ( @is_readable($_SERVER['DOCUMENT_ROOT'].'/Templates/error.dwt') ) $dwt = $_SERVER['DOCUMENT_ROOT'].'/Templates/error.dwt';
            elseif ( @is_readable('./Templates/error.dwt') )  $dwt = './Templates/error.dwt';
            elseif ( @is_readable('./templates/error.dwt') )  $dwt = './templates/error.dwt';
            elseif ( @is_readable('../Templates/error.dwt') ) $dwt = '../Templates/error.dwt';
            elseif ( @is_readable('../templates/error.dwt') ) $dwt = '../templates/error.dwt';
                else $dwt_content = '<b>HTTP-Fehler 401: Unauthorized</b><br>Zugriff wurde verweigert. (Die Anfrage erforderte eine Authentifizierung des Nutzers.)';

        $dwt = new PTemplate($dwt_content, $dwt);
        $dwt->removeDir();
        $dwt->addComponent('errNumber', 401);
        $dwt->addComponent('fehler', 'Unauthorized');
        $dwt->addComponent('text', 'Zugriff wurde verweigert. (Die Anfrage erforderte eine Authentifizierung des Nutzers.)');

        ob_start();
            echo $dwt->outputStr();
            // output headers (with content length from output buffering)
            header ('HTTP/1.0 401');
            header ('Last-Modified: '.@gmdate('D, d M Y H:i:s').' GMT');
            header ('Accept-Ranges: bytes');
            header ('Content-Length: '.@ob_get_length());
            header ('Content-Type: text/html');
        ob_end_flush();
        return true;
    }
    // PUtil::showError


    function wordwrap_html($text, $width=75, $break="\n") {
        $newtext = '';
        $stack   = 0;
        while ( strlen($text) > 0 ) {
            if ( ($length = strcspn( $text, " <\n\t")) <= ($width-$stack) ) {
            // leave strings untouched if shorter than '$width'
                if ( strpos($text, '<')!==0 ) {
                    $newtext .= substr($text, 0, ($pos=$length+strspn($text, " \n\t")));
                    $text     = substr($text, $pos);
                    $stack    = 0;
                } else $stack += $length;
            } else {
            // insert string '$break' after '$width' characters
                $newtext .= substr($text, 0, $width-$stack).$break;
                $text     = substr($text, $width-$stack);
                $stack    = 0;
            }
            if ( strpos($text, '<') === 0 ) {
            // leave html-tags untouched
                $newtext .= substr($text, 0, ($pos=1+strpos($text, '>')));
                $text     = substr($text, $pos);
            }
        }
        return $newtext;
    }
    // PUtil::wordwrap_html


    // action:      convert non-ASCII characters in a given string to ASCII-chars (needed for order column in mysql)
    // arguments:   string $string
    //	return:     string - converted string
    function sort_string($string) {
        $from = 'äáàåâæöóòôüúùûéèêýñíìîçßÄÁÀÅÂÆÖÓÒÔÜÚÙÛÉÈÊÝÑÍÌÎÇ';
        $to   = 'aaaaaaoooouuuueeeyniiicsAAAAAAOOOOUUUUEEEYNIIIC';
        return strtr($string, $from, $to);
    }
    // PUtil::sort_string


    // for compatibility
    function check_mail($email, $forbidden_domains = array()) {
        return PUtil::check_email($email, $forbidden_domains);
    }


    /**
    *	Funktion zum &Uuml;berpr&uuml;fen von E-Mail-Adressen
    *   ( falls der &uuml;bergene String eine g&uuml;ltige Adresse enth&auml;lt, wird diese extrahiert; 
    *     falls der 2.Parameter auf TRUE, darf die Absender-Adresse nicht von der Domain des aufgerufenen Scripts stammen )
    *	<b>Parameter:</b>
    *		String  - email
    *       array   - forbidden_domains ( default: array() )
    *	<b>Rückgabe:</b>
    *		boolean - TRUE / FALSE)
    **/
    function check_email($email, $forbidden_domains = array()) {
        if ( !@is_array($forbidden_domains) ) $forbidden_domains = array();

        if ( strpos($email, ',') ) $adressen = explode(',', $email);
            else $adressen = array($email);

        $return = FALSE; // Standard Rueckgabewert (wird auf TRUE gesetzt, wenn min. eine gueltige eMail-Adresse gefunden)
        foreach ( $adressen as $email ) {

            $email = trim($email, ' ');         // nur Leerzeichen am Anfang und Ende entfernen
            if ( empty($email) ) continue;      // leere Adressen ignorieren (falls andere gueltige Adressen in Liste)
            
            if ( FALSE !== strpos($email, "\n") )        return FALSE;  // Zeilenumbruch in allen Adressen verboten 
            if ( FALSE !== strpos($email, "\r") )        return FALSE;  // Zeilenumbruch in allen Adressen verboten
            if ( FALSE !== strpos($email, ':') )         return FALSE;  // ':' in allen Adressen verboten 

            // Syntaxcheck der Mail-Adresse 
            $reg_denyChars = "<>@\s:?\"';()\[\]{}\$§´`\\\\";            // verbotene Buchstaben in mail-Adressen
            $reg_mailAddr  = "([^$reg_denyChars]+@[^$reg_denyChars]+)"; // gueltiges Muster von mail-Adressen
            $reg_exp       = "/^(?(?=[^<:]*<)(?:[^<]+<$reg_mailAddr>)|$reg_mailAddr)$/i";
            if ( !preg_match($reg_exp, $email, $match) ) return FALSE;
                elseif ( !empty($match[2]) ) $email = $match[2];
                    else $email = $match[1];

            ////////////////////////////////// 
            // Domain checks                // 
            $domain = substr($email, strpos($email,'@')+1);
            if ( FALSE !== strpos($domain, '@') )        return FALSE;  // '@' in allen Domains verboten 
            if ( !strpos($domain, '.') )                 return FALSE;  // '.' in allen Domains zwingend erforderlich (TLD)
            if ( in_array($domain, $forbidden_domains) ) return FALSE;  // teste, ob verbotene Domain (bei allen Adressen)

            // teste DNS-Eintrag (nur ein Fehler, falls alle Adressen ungueltig)
            if ( checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A') ) $return = TRUE;

        } // foreach ($adressen)


        // wenn mindestens eine gueltige Adresse gefunden und kein kritischer Fehler => TRUE
        return $return;
    }
    // PUtil::check_email 



    /**
    *	alternative in_array() - Funktion: 
    *      falls Parameter strict == FALSE, werden Variablen immer als Strings verglichen 
    *      (Achtung:keine verschachtelten Arrays möglich)
    *	<b>Syntax:</b>
    *	   bool in_array ( mixed needle, array haystack [, bool strict] )
    **/
    function in_array($mixed_needle, $array_haystack, $bool_strict = FALSE) {
       
        foreach ($array_haystack as $value) {
            if ( $bool_strict ) {
                if ( $mixed_needle === $value ) {
                    return TRUE;
                    break;
                }
            } else {
                $mixed_needle = (string)$mixed_needle;
                $value        = (string)$value;
                if ( $mixed_needle === $value ) {
                    return TRUE;
                    break;
                }
            }
        }
        return FALSE;
    }
    // PUtil::in_array 


}
//class PUtil



class CFG {
    
    var $configVars = array();

    function __construct() {
        // test to make constructor really private (only if object is called from CFG::getInstance() the return value is 0)
        if ( $this->getInstance(TRUE) )
            trigger_error('CFG: new operator not allowed, use <strong>\'& CFG::getInstance()\'</strong>', E_USER_ERROR);
    }
    
    function CFG() {
        $this->__construct();
    }
    

    // singleton pattern
    function & getInstance($test = FALSE) {
        static $instance = array('singleton' => 0);

        // test to make constructor really private 
        $instance['singleton']++;
        if ( $test ) return $instance['singleton'];

        if ( !isset($instance['class']) ) {
            $instance['singleton'] = -1;
            $class                 = __CLASS__;
            $instance['class']     = & new $class();
        }
        if ( !is_object($instance['class']) ) {
            $instance['singleton'] = -1;
            $class                 = __CLASS__;
            $instance['class']     = & new $class();
            trigger_error('CFG::getInstance(): Object was destroyed! Script stopped for security reasons', E_USER_ERROR);
        }
        return $instance['class'];
    } // singleton::getInstance

    function set($key, $value = 'DO_NOT_SET_A_NEW_VALUE', $protected = FALSE) {
        $obj = & CFG::getInstance();
        
        if ( 'DO_NOT_SET_A_NEW_VALUE' !== $value ) {
        
            if ( !$obj->protect($key, FALSE) ) {
                $obj->configVars[$key] = $value;
                if ( $protected ) $obj->protect($key);
            } else trigger_error('CFG::set(): Cannot overwrite \''.$key.'\' - variable is read only', E_USER_WARNING);

        } else return $obj->get($key);
    } // CFG::set

    function add($key, $arg1, $arg2 = 'DO_NOT_USE_AN_INDEX') {
        $obj = & CFG::getInstance();
        if ( !$obj->protect($key, FALSE) ) {
            if ( !isset($obj->configVars[$key]) ) $obj->configVars[$key] = array();
            if ( is_array($obj->configVars[$key]) ) {
                if ( 'DO_NOT_USE_AN_INDEX' === $arg2 ) $obj->configVars[$key][] = $arg1;
                    else $obj->configVars[$key][$arg1] = $arg2;
            } else trigger_error('CFG::add(): Cannot add value to \''.$key.'\' - variable is not an array', E_USER_WARNING);
        } else trigger_error('CFG::add(): Cannot overwrite \''.$key.'\' - variable is read only', E_USER_WARNING);
    }

    function get($key) {
        $obj = & CFG::getInstance();
        if ( isset($obj->configVars[$key]) ) return $obj->configVars[$key];
            elseif ( preg_match("/^(.*?)(?:\[([^\[\]]+)\])(?:\[([^\[\]]+)\])?(?:\[([^\[\]]+)\])?(?:\[([^\[\]]+)\])?(?:\[([^\[\]]+)\])?$/is", $key, $match) ) {
                $tmp = $obj->configVars;
                $i   = 0;
                while ( (++$i < count($match)) && $isset = isset($tmp[$match[$i]]) ) $tmp = $tmp[$match[$i]];
                if ( empty($isset) ) trigger_error('CFG::get(): Array \''.$key.'\' is not defined', E_USER_WARNING);
                    else return $tmp;
            } else trigger_error('CFG::get(): Variable \''.$key.'\' is not defined', E_USER_WARNING);
    } // CFG::get

    function protect($key, $set = TRUE) {
        static $cfgProtect = array();
        $obj = & CFG::getInstance();
        if ( $set ) {
            if ( isset($obj->configVars[$key]) ) $cfgProtect[$key] = TRUE;
                else trigger_error('CFG::protect(): Cannot protect non existing variable \''.$key.'\'', E_USER_WARNING);
        } elseif ( !empty($cfgProtect[$key]) ) return TRUE;
            else return FALSE;
    }
} // class::CFG



// error handler 
function PML_error_handler($type, $msg, $file, $line, $context) {

    $errTypes = error_reporting();

    if ( !empty($errTypes) && ($type & $errTypes) ) {
        $errortype = array (
            E_ERROR           => 'Error',
            E_WARNING         => 'Warning',
            E_PARSE           => 'Parsing Error',
            E_NOTICE          => 'Notice',
            E_CORE_ERROR      => 'Core Error',
            E_CORE_WARNING    => 'Core Warning',
            E_COMPILE_ERROR   => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR      => 'Fatal Error',
            E_USER_WARNING    => 'User Warning',
            E_USER_NOTICE     => 'User Notice'
        );
        if ( defined('E_STRICT') ) $errortype[E_STRICT] = 'Runtime Notice';

        // get array with file names with alternate error handling 
        if ( ! $filesToHandle = @CFG::get('PML_ALTERNATE_ERROR_HANDLING') ) $filesToHandle = array();
        
        // set alternate error handling for PML scripts
        $filesToHandle[] = PML_PATH.'PUtil.php';
        $filesToHandle[] = PML_PATH.'PPage.php';
        $filesToHandle[] = PML_PATH.'PComponent.php';
        $filesToHandle[] = PML_PATH.'PObject.php';
        $filesToHandle[] = PML_PATH.'PPowerslave.php';
        $filesToHandle[] = PML_PATH.'PLocal.php';
        $filesToHandle[] = '/usr/local/etc/httpd/php-bin/include/common.mysql.php';
        $filesToHandle = array();
       
        
        // check if alternate error handling should be used 
        if ( ! PUtil::in_array($file, $filesToHandle) ) return FALSE;

        $cfg_log = array();
        $cfg_log[] = get_cfg_var('display_errors');
        $cfg_log[] = get_cfg_var('log_errors');
        $cfg_log[] = get_cfg_var('error_log');
        $cfg_log[] = get_cfg_var('log_errors_max_len');

 
        $msg = str_replace('mysql_', 'sql_', $msg);

        $backtrace = debug_backtrace();
        $i=1;
        while ( in_array($backtrace[$i]['file'], $filesToHandle) && isset($backtrace[(1+$i)]['file']) ) $i++;
        if ( !empty($cfg_log[0]) && strtolower($cfg_log[0]) != 'off' )
            echo '<br /><b>'.$errortype[$type].':</b> '.$msg.' in <b>'.$backtrace[$i]['file'].'</b> on line <b>'.$backtrace[$i]['line'].'</b><br />';
        if ( !empty($cfg_log[1]) && strtolower($cfg_log[1]) != 'off' )
            error_log($errortype[$type].': '.substr($msg,0,$cfg_log[3]).' in '.$backtrace[$i]['file'].' on line '.$backtrace[$i]['line']."\n");

        if ( in_array($type, array(E_ERROR, E_USER_ERROR)) ) exit();    // exit script on fatal error
    }
}

set_error_handler('PML_error_handler');


?>