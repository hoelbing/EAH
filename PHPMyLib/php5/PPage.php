<?php
/**
* PHPMyLib template library
*
* @author i-fabrik GmbH
* @copyright 2002 by i-fabrik GmbH
* @version $Id: PPage.php,v 1.13 2007/01/29 12:39:22 erik Exp $
*
*/

// for PHP versions < 4.3.0 define fucntion file_get_contents
if ( !function_exists('file_get_contents') ) {
    function file_get_contents($file='') {
        if ( @is_readable($file) && $tmp=@file($file) ) return @join('', $tmp); else return FALSE;
    }
    //file_get_contents
}
//


/**
* Die Klasse PTemplate verwaltet Dreamweaver-Templates und ist in der Lage Ersetzungen von
* Platzhaltern der Form {name} und editierbaren Bereichen der Form
* <!-- BeginEditable "name"> ... <!-- EndEditable> durchzuführen
**/
class PTemplate extends PComponent {
    /**
    * Array der Platzhalter im Dokument. Jedes Array Element ist ein PContainer und somit
    * in der Lage Inhalte für einen Platzhalter aufzunehmen.
    **/
    var $m_aVars;
    /**
    * Flag, ob Druckversion erzeugt werden soll
    * 0 - Normale Ansicht, 1 - Druckversion
    **/
    var $m_print;
    var $m_aBlocks;

    var $oldinclude;

    /**
    * Konstruktor der PTemplate-Klasse. Die angegebene Datei bzw. Quelltext wird geparst und die
    * vorhandenen Platzhalter ermittelt.
    * <b>Paramater:</b>
    * $code - Quelltext des Templates
    * $file - Dateiname des Templates
    * $print - optionaler Parameter, wenn auf 1 gesetzt => Durckversion erzeugen:
    *		( nur durch<blockquote><blockquote><!-- BEGIN_PRINTER_FRIENDLY_COPY -->
    *      .
    *      .
    * <!-- END_PRINTER_FRIENDLY_COPY --></blockquote></blockquote> gekennzeichnete Blöcke werden ausgegeben )
    **/
    function PTemplate( $code = '', $file = '', $print = 0, $oldinclude = FALSE ) {
        $this->PComponent();
        $this->m_print    = $print;
        $this->oldinclude = $oldinclude;

        if ( $file!='' ) {
            if ( !@is_readable($file) ) {
                $spelling      = array('templates', 'Templates', 'TEMPLATES', 'TEmplates');
                $countSpelling = count($spelling);
                $filenotfound  = 1;
                for ( $i = 0; $i < $countSpelling; $i++) {
                    $tmp_filename = preg_replace( "/templates\//i", $spelling[$i].'/', $file );
                    if ( @is_readable($tmp_filename) ) {
                        $this->m_strText = @file_get_contents($tmp_filename);
                        unset($filenotfound);
                        break;
                    }
                }
            } else $this->m_strText = @file_get_contents($file);
        }
        if ( $code != '' || isset($filenotfound) ) $this->m_strText = $code;

        $this->parse();
        if ( isset($filenotfound) ) {
            error_reporting(E_ALL);
            trigger_error("Template '$file' Not Found (in $_SERVER[PHP_SELF])", E_USER_ERROR);
        }
    }
    // Constructor: PTemplate


    /**
    * Diese Methode entfernt aus dem Template sämtliche "../" aus Pfadangaben. Dreamweaver erzeugt
    * diese bei Erstellung eines Templates in Unterverzeichnissen.<b></b>
    **/
    function removeDir($pattern="",$replace="") {
        if ( !empty($pattern)) {
          $pattern = preg_quote($pattern);
          $this->m_strText=preg_replace("/(((src)|(href)|(background)|(action))=['\"]?)$pattern/is", "\\1$replace", $this->m_strText);
        }
        else
          $this->m_strText=preg_replace("/(((src)|(href)|(background)|(action))=['\"]?)\.\.\//is", "\\1/", $this->m_strText);
        //$this->m_strText=str_replace('../', '', $this->m_strText);
    }
    // removeDir



    function addSelectOptions($blockname, $options = array(), $selected = NULL) {
        // test if select block exists
        if (
            $this->block_exists($blockname) ||
            $this->block_exists($blockname = '__SELECT__'.trim($blockname))
        ) $selectBlock = $this->extractBlock($blockname);
            else return FALSE;

        $optionBlock = $selectBlock->extractBlock($blockname.'_OPTIONS');
        $emptyOption = preg_replace("/(<option[^>]*) value=\"[^\"]*\"/is", "\\1", $optionBlock->m_strText);
        $emptyOption = preg_replace("/(<option[^>]*) class=\"{[^\"]*}\"/is", "\\1", $emptyOption);

        // add options
        foreach ( $options as $entry ) {
            $optionTxt = preg_replace("/(<option[^>]*?>.*?){.*?}(.*?<\/option>)/is", "\\1$entry[1]\\2", $emptyOption);
            $optionTxt = preg_replace("/(<option[^>]*)>/is", "\\1 value=\"$entry[0]\">", $optionTxt);
            if ( !empty($entry[2]) ) $optionTxt = preg_replace("/(<option(?![^>]*class=\"[^\"]*\")[^>]*)>/is", "\\1 class=\"$entry[2]\">", $optionTxt);
            if ( $selected == $entry[0] ) $optionTxt = preg_replace("/(<option[^>]*)>/is", "\\1 selected=\"selected\">", $optionTxt);
            $selectBlock->addComponent($blockname.'_OPTIONS', $optionTxt);
        }

        $this->m_aVars[$blockname] = $selectBlock;
        return TRUE;
    }
    // PTemplate::addSelectOptions


    /**
    * Diese Methode deactiviert die Kommentare zur Kennzeichnung von editierbaren Bereichen im Quelltext,
    * durch Einfuegen zusaetlicher Zeichen im Kommentar. (Spaeteres Parsen der Kommentare schlaegt dadurch fehl.)
    * Dies ist notwendig, wenn die erzeugte HTML-Seite von einem anderen Skript als 'virtual include' eingelesen wird
    * (Wird eventuell Standardverhalten in zukuenftigen Releases)
    * <b>Parameter:</b>
    * <b>Rückgabe:</b>
    * 	boolean:TRUE / boolean:FALSE
    **/
    function deactivateBlockComments() {
        $pattern = array('<!-- #BeginEditable', '<!-- TemplateBeginEditable', '<!-- InstanceBeginEditable', '<!-- #EndEditable -->', '<!-- TemplateEndEditable -->', '<!-- InstanceEndEditable -->');
        $replace = array('<!-- :: #BeginEditable', '<!-- :: TemplateBeginEditable', '<!-- :: InstanceBeginEditable', '<!-- :: #EndEditable -->', '<!-- :: TemplateEndEditable -->', '<!-- :: InstanceEndEditable -->');
        if ( !($this->m_strText=str_replace($pattern, $replace, $this->m_strText)) ) return FALSE;
        return TRUE;
    }
    // PTemplate::deactivateBlockComments


    /**
    * Diese Methode entfernt die Kommentare zur Kennzeichnung von editierbaren Bereichen im Quelltext.
    * <b>Parameter:</b>
    * <b>Rückgabe:</b>
    * 	boolean:TRUE / boolean:FALSE
    **/
    function removeBlockComments() {
        $pattern = '/<!-- ?(#BeginEditable|TemplateBeginEditable|InstanceBeginEditable|#EndEditable|TemplateEndEditable|InstanceEndEditable).*?-->/is';
        if ( !($this->m_strText = preg_replace($pattern, '', $this->m_strText) ) ) return FALSE;
        if ( !($this->m_strText = str_replace($pattern, $replace, $this->m_strText)) ) return FALSE;
        return TRUE;
    }
    // PTemplate::removeBlockComments



    // fuegt inhalt eines virtual include scripts ein ( webabfrage des geincludeten scripts )
    function include_virtual($script='') {
        // update: prevend recursiv include of scripts (R.Kropp 09.07.2004)
        if ( empty($_REQUEST['recursive']) ) {

            if ( (FALSE === strpos($script, 'http://')) && (FALSE === strpos($script, 'https://')) ) {
                if ( empty($_SERVER['HTTPS']) ) $server = 'http://';
                    else $server = 'https://';
                if ( @checkdnsrr($_SERVER['SERVER_NAME'], 'A') ) $server .= $_SERVER['SERVER_NAME'];
                    else $server .= 'localhost';
                if ( '/' !== $script[0] ) $script = $server.dirname($_SERVER['PHP_SELF']).'/'.$script;
                    else $script = $server.$script;
            }

            $script = preg_replace("/^([^#]+).*?$/is", "\\1", $script);	// anker entfernen
            if ( FALSE !== strpos($script, '?') ) $script = str_replace('?', '?recursive=1&', $script);
                else $script .= '?recursive=1';

            $string = @file_get_contents($script);
            return $string;
        } else return 'Fehler: Verschachtelung von Virtual includes nicht erlaubt!';
    }
    // include_virtual


    // fuegt quelltext eines virtual include scripts ein ( verwendung der php-function include() )
    function include_virtual_old( $script='' ) {
        // get additional parameters
        $script=explode('?', rawurldecode($script));
        if ( !empty($script[1]) ) {
            $paramlist=explode('#', $script[1]);
            $paramlist=explode('&', $paramlist[0]);
            foreach ($paramlist as $param) {
                $param=explode('=', $param);
                $$param[0]=$param[1];
            }
        }

        // change to the directory of the include file
        $old_dir=getcwd();
        $new_dir=dirname($script[0]);
        $script=basename($script[0]);

        // include file
        chdir($new_dir);
        ob_start();
            include($script);
            $output=ob_get_contents();
        ob_end_clean();
        chdir($old_dir);

        // correct file pathes
        preg_match_all("/(( src)|(href)|(background)|(action))=\"(.*?)\"/is", $output, $matches);
        $matches=array_unique($matches[6]);

        foreach ( $matches as $file )
            if ( trim($file)=='' ) continue;
                elseif ( strtolower(substr($file,0,7))!='http://' && $file[0]!='.' && $file[0]!='/' ) $output=preg_replace('/'.preg_quote($file,'/').'/is', '../'.$file, $output);

        return $output;
    }
    // include_virtual_old


   /**
   * Diese Methode parst den Quelltext und ermittelt die Platzhalter. (Reset des Templates)
   * Dabei werden auch 'virtual includes' geparst.<b></b>
   **/
   function parse() {
        $this->m_aVars = array();
        $this->m_aBlock = array();

        // enable virtual includes in templates (R.Kropp 26.07.2002)
        $this->m_strText=preg_replace("/<!-- *#include virtual=\"(.*?)\" *-->/is", "{[\\1]}", $this->m_strText);
        if ( preg_match_all("/\{\[(.*?)\]\}/is", $this->m_strText, $matches) ) {
            foreach ( $matches[1] as $key=>$value ) {
                $script = $value;
                if ( $this->oldinclude === TRUE ) {
                    // if ( FALSE && substr($script,0,1) != '.' && strtolower(substr($script,0,7)) != 'http://' ) $script = $_SERVER['DOCUMENT_ROOT'].'/'.$script;
                    $tmp = $this->include_virtual_old($script);
                } else $tmp = $this->include_virtual($script);

                $quote           = preg_quote($value, '/');
                $this->m_strText = preg_replace("/\{\[$quote\]\}/is", "$tmp", $this->m_strText);
            }
        }
        // End: virtual includes

        // Scan for Variables/Placeholders (geaendert: R.Kropp 04.06.2004, Umstellung auf regulaeren Ausdruck)
        if ( preg_match_all("/\{([a-zA-Z_].*?)\}/s", $this->m_strText, $matches) ) {
            foreach ($matches[1] as $var) {
                $var = trim($var);
                if ( strlen($var) <= 50 && strlen($var) > 0 ) $this->m_aVars[$var] = new PContainer();
            }
        }
   } // parse


   /**
   * Diese Methode durchsucht den Quelltext nach dem benannten editierbaren Bereich
   * und gibt TRUE oder FALSE zurück, je nach Ergebnis der Suche
   * <b>Parameter:</b>
   * $name - Name des editierbaren Bereiches
   * <b>Rückgabe:</b>
   * boolean TRUE/FALSE (editierbarer Bereich existiert bzw. existiert nicht
   **/
   function block_exists($name) {
        if ( ($pos=strpos($this->m_strText,"<!-- #BeginEditable \"$name\" -->"))!==FALSE ) {
            return TRUE;
        } elseif ( ($pos=strpos($this->m_strText,"<!-- TemplateBeginEditable name=\"$name\" -->"))!==FALSE ) {
            return TRUE;
        } elseif ( ($pos=strpos($this->m_strText,"<!-- InstanceBeginEditable name=\"$name\" -->"))!==FALSE ) {
            return TRUE;
        } else return FALSE;
    } // block_exists


   /**
   * Diese Methode extrahiert aus dem Quelltext den benannten editierbaren Bereich
   * und ersetzt ihn durch einen Platzhalter gleichen Namens.
   * <b>Parameter:</b>
   * $name - Name des editierbaren Bereiches
   * <b>Rückgabe:</b>
   * Template, das den editierbaren Bereich als Quelltext enthält
   **/
   function extractBlock($name) {
        if ( ($pos=strpos($this->m_strText,"<!-- #BeginEditable \"$name\" -->"))!==FALSE ) {
            $startText      = "<!-- #BeginEditable \"$name\" -->";
            $startText_kurz = '<!-- #BeginEditable ';
            $endText        = '<!-- #EndEditable -->';
            //$beginLen=26;
        } elseif ( ($pos=strpos($this->m_strText,"<!-- TemplateBeginEditable name=\"$name\" -->"))!==FALSE ) {
            $startText      = "<!-- TemplateBeginEditable name=\"$name\" -->";
            $startText_kurz = "<!-- TemplateBeginEditable name=";
            $endText        = "<!-- TemplateEndEditable -->";
            //$beginLen=38;
        } elseif ( ($pos=strpos($this->m_strText,"<!-- InstanceBeginEditable name=\"$name\" -->"))!==FALSE ) {
            $startText      = "<!-- InstanceBeginEditable name=\"$name\" -->";
            $startText_kurz = "<!-- InstanceBeginEditable name=";
            $endText        = "<!-- InstanceEndEditable -->";
            //$beginLen=38;
        } else return FALSE;

        $count_start = 1;
        $current_pos = $pos;
        do {
            $e_pos = (int)strpos($this->m_strText, $endText, 1+$current_pos);
            $s_pos = (int)strpos($this->m_strText, $startText_kurz, 1+$current_pos);
            if ( $e_pos && ( ($e_pos < $s_pos) || $s_pos==0) ) {
                $current_pos = $e_pos;
                $count_start--;
            } elseif ( $e_pos && $s_pos && ($e_pos > $s_pos) ) {
                $count_start++;
                $current_pos = $s_pos;
            } else return FALSE;
        } while ( $count_start > 0 );

        $tmplBlock = new PTemplate( substr($this->m_strText,$n=$pos+strlen($startText),$current_pos-$n), FALSE );
        foreach ( $this->m_aVars as $varname=>$varcont ) {
            if ( @array_key_exists($varname, $tmplBlock->m_aVars) ) {
                if ( is_object($varcont) ) $tmplBlock->m_aVars[$varname] = clone $varcont;
                    else $tmplBlock->m_aVars[$varname] = $varcont;
            }
        }
        $this->m_strText      = substr_replace( $this->m_strText,'{'.$name.'}',$pos,$current_pos-$pos+strlen($endText) );
        $this->m_aVars[$name] = new PContainer();
        return $tmplBlock;
    }
    // extractBlock


    /**
    * Diese Methode fügt eine Komponente an die Stelle eines Platzhalters ein. Es können auch
    * mehrere Komponenten pro Platzhalter sein.
    * <b>Parameter:</b>
    * $var - Name des Platzhalters
    * $comp - Die einzufügende Komponente
    **/
    function addComponent($var, $comp) {
        if ( @is_object($this->m_aVars[$var]) ) {
            $n = $this->m_aVars[$var]->add($comp);
            return $n;
        } else return (-1);
    }
    //addComponent

    /**
    * Überladene Methode von PComponent. Gibt das Template samt aller ersetzten Platzhalter aus.
    * <b>Parameter:</b>
    * $tpl_print - 0 -> normale Ausgabe
    *              1 -> nur Bereich fuer Druckausgabe (BEGIN_PRINTER_FRIENDLY_COPY)
    * <b>Rückgabe:</b>
    * Das Template als String (HTML)
    **/
    function outputStr($tpl_print=0) {
        $text=$this->m_strText;

        if ( is_array($this->m_aVars) )
            foreach ( $this->m_aVars as $varname=>$varcont ) $text = str_replace("{".$varname."}", $varcont->outputStr(), $text);

        // if printable version choosen => only echo printable blocks
        if ( $this->m_print==1 || !empty($tpl_print) ) {
            $tmp_text='';
            if ( @preg_match_all("/<\!-- BEGIN_PRINTER_FRIENDLY_COPY -->.*?<\!-- END_PRINTER_FRIENDLY_COPY -->/is", $text, $matches) ) {
                foreach ($matches as $key=>$value) $tmp_text.=$value[0];
                $text=@preg_replace("/(<body.*?>).*?<\/body>/is", "<body bgcolor=\"#FFFFFF\" link=\"#3333FF\"><blockquote>$tmp_text</blockquote></body>", $text);
                $text=@preg_replace("/(<meta name=\"?robots.*?>)/is", "", $text);
                $text=@str_replace("<head>", "<head>\n<meta name=\"robots\" content=\"noindex,nofollow\" />\n", $text);
            }
        }
        return $text;
    } //outputStr

    /**
    * Gibt das Template samt aller ersetzten Platzhalter aus, dabei werden Platzhalterkommentare geloescht.
    * <b>Parameter:</b>
    * keine
    * <b>Rückgabe:</b>
    * Das Template als String (HTML)
    **/
    function outputStrClean() {

      $text=$this->outputStr();
      $text=preg_replace('/\<!-- (?:Instance|Template|#Begin|#End).*--\>/iU', '', $text);

      return $text;

    }

} //class PTemplate


/**
* Die Klasse PTemplate2 verwaltet Templates (ähnlich Dreamweaver) und ist in der Lage
* Ersetzungen von Platzhaltern der Form {name} und editierbaren Bereichen der Form
* <!-- BeginEditable2 "name"> ... <!-- EndEditable2 "name"> durchzuführen
* Damit sind auch verschachtelte editierbare Bereiche m&ouml;glich.
**/
class PTemplate2 extends PTemplate
{
    /**
    * Konstruktor der PTemplate2-Klasse. Die angegebene Datei bzw. Quelltext wird geparst und die
    * vorhandenen Platzhalter ermittelt.
    * <b>Paramater:</b>
    * $code - Quelltext des Templates
    * $file - Dateiname des Templates
    **/
    function PTemplate2($code = "", $file = "") {
        $this->PTemplate($code,$file);
    }

    /**
    * Diese Methode extrahiert aus dem Quelltext den benannten editierbaren Bereich
    * und ersetzt ihn durch einen Platzhalter gleichen Namens.
    * <b>Parameter:</b>
    * $name - Name des editierbaren Bereiches
    * <b>Rückgabe:</b>
    * Template, das den editierbaren Bereich als Quelltext enthält
    **/
    function extractBlock($name) {
        $pos=strpos($this->m_strText,"<!-- #BeginEditable2 \"$name\" -->");
        if ( $pos!=false ) {
            $epos=strpos($this->m_strText,"<!-- #EndEditable2 \"$name\" -->",$pos+1);
            if ( $epos!=false ) {
                $tmp=new PTemplate2(substr($this->m_strText,$n=$pos+strlen($name)+27,$epos-$n),"");
                reset($tmp->m_aVars);
                while( list($varname,$varcont) = each($tmp->m_aVars) ) {
                    $tmp->m_aVars[$varname]=$this->m_aVars[$varname];
                    $a=array_keys($this->m_aVars);
                    for($i=0;$i<count($this->m_aVars);$i++) {
                        if ($a[i]==$varname) {
                            $this->m_aVars=array_splice($this->m_aVars,$i);
                            break;
                        }
                    }
                }
                $this->m_strText=substr_replace($this->m_strText,"{".$name."}",$pos,$epos-$pos+25+strlen($name));
                $this->m_aVars[$name]=new PContainer();
                return $tmp;
            }
        }
    } //extractBlock

} //class PTemplate2


/**
* Die Klasse PTemplate3 verwaltet Templates (ähnlich Dreamweaver) und ist in der Lage
* Ersetzungen von Platzhaltern der Form {name} und editierbaren Bereichen der Form
* <!-- BeginEditable "name"> ... <!-- EndEditable "name"> durchzuführen
* Weiterhin ist das Einlesen von externen Dateien moeglich.
**/
class PTemplate3 extends PTemplate {
    /**
    * Konstruktor der PTemplate3-Klasse. Die angegebene Datei bzw. Quelltext wird geparst und die
    * vorhandenen Platzhalter ermittelt.
    *
    * <b>Paramater:</b>
    * $code - Quelltext des Templates
    * $file - Dateiname des Templates
    **/
    function PTemplate3($code = '', $file = '') {
        $this->PTemplate($code,$file);
    }

    /**
    * Parst die erstellte Seite nach <!-- #FileInclude "name" --> und fuegt
    * diese Datei per include() in den Ausgabestrom ein
    *
    * <b>Parameter</b>
    * keine
    **/
    function outputPrint() {
        $phpmylib_text=$this->outputStr();
        if ( strstr($phpmylib_text,"<!-- #FileInclude") ) {
            $phpmylib_position=0;
            while(($phpmylib_aktpos=strpos($phpmylib_text,"<!-- #FileInclude \"",$phpmylib_position)) && ($phpmylib_position<strlen($phpmylib_text))) {
                echo substr($phpmylib_text,$phpmylib_position,$phpmylib_aktpos-$phpmylib_position);
                $phpmylib_tag=substr($phpmylib_text,$phpmylib_aktpos);
                $phpmylib_tag=substr($phpmylib_tag,0,strpos($phpmylib_tag,"-->"));
                $phpmylib_filename=substr(strstr($phpmylib_tag,"\""),1);
                $phpmylib_filename=substr($phpmylib_filename,0,strpos($phpmylib_filename,"\""));

                if ( !empty($phpmylib_filename) && file_exists($phpmylib_filename) ) {
                    $phpmylib_dir=dirname($phpmylib_filename);
                    $phpmylib_file=basename($phpmylib_filename);
                    $phpmylib_pfad=getcwd();
                    chdir($phpmylib_dir);
                    include("$phpmylib_file");
                    chdir($phpmylib_pfad);
                }
                $phpmylib_position=strpos($phpmylib_text,"-->",$phpmylib_aktpos)+3;
            }
            echo substr($phpmylib_text,$phpmylib_position);
        } else echo $phpmylib_text;
    } // outputPrint

} //class PTemplate3

?>