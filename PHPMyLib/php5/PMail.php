<?php
/**
* MailUpdate-Tool 
*
* @author R.Kropp, i-fabrik GmbH
* @copyright 2002 by i-fabrik GmbH
* @version $Id: PMail.php,v 1.3 2007/01/29 12:18:18 ralph Exp $
*
*/
require_once('PHPMyLib/PHPMyLib.php');

// wie trim, nur wird string direkt verändert (ohne Rückgabewert)
function ifab_trim(&$string) {
	$string=trim($string);
}
//ifab_trim


/*********************************************************/
/**	Klasse für die Administration des MailUpdate-Tools	**/
/*********************************************************/ 
class PAdmin extends PUtil {
	var $errorMsg;
	var $news;
	var $ident;
	var $kunde=array(2);

	/**
	* Constructor
	* <b>Parameter:</b> $exec - gibt an, welche Funktion ausgeführt werden soll
	*			values:	0 - Start (Login)
	*					1 - ...
	**/
	function PAdmin( $exec, $ku_id='', $ti_id='', $in_id='' ) {
		$dbID=$this->PA_login();                       				 // Benutzerauthentifizierung

		// weitere Sicherheitskriterien überprüfen
		if ($exec=='PA_newUser' || $exec=='PA_adm' || $exec=='PA_src') {
			// nur als Admin erlaubt!!
			if ($this->kunde[0]!=2) $exec='PA_overview';
		} elseif ($exec=='PA_syn' || $exec=='PA_usr' || $exec=='PA_pass' || $exec=='PA_news' || $exec=='PA_showArt') {
			// nur bei eigenen Newslisten oder als Admin erlaubt!!
			if ( $this->kunde[0]!=2 && !in_array($ku_id, $this->kunde) ) $exec='PA_overview';
		} else $exec='PA_overview';
		
		// falls Kunde nur eine Website hat ==> gleich zur Newslisten-Administration springen
		if( $exec=='PA_overview' && $this->kunde[0]!=2 && count($this->kunde)==1 ) {
			$exec='PA_usr';
			$ku_id=$this->kunde[0];
		}

		$this->news=$this->$exec($dbID,$ku_id, $ti_id, $in_id);	// gewählte Funktion ausführen
		$this->PA_procTemplate();											// Template initialisieren und ausgeben
	}
	//Constructor: PAdmin


	// Benutzerauthentifizierung ////////////////////////////////////////////////

	// Datenbank-Connection herstellen
	function PA_db_connect($user='', $pass='') {
		$host		= 'localhost';
		$database= 'mail_update';
		$user		= $_SERVER['MUPDUser'];
		$pass		= $_SERVER['MUPDPass'];

		$dbID = @mysql_connect($host, $user, $pass)
					or $this->errorMsg='Verbindung zur Datenbank fehlgeschlagen!!';
		if ($dbID) @mysql_select_db($database, $dbID)
					or $this->errorMsg='Verbindung zur Datenbank fehlgeschlagen!!';
		return($dbID);
	}
	//PA_db_connect

	// check login and jump to main admin login page if failure
	function PA_login() {
		global $dbID;
		ifab_login($dbID, 'local');			// call login function
		$dbID=$this->PA_db_connect();

		// fetch user_id from mupd db
		$sql="SELECT ku_id FROM kunde WHERE ku_home='".substr($_SESSION['AUTH_SERVER'][$_SERVER['SERVER_ADDR']]['HOME'],6)."'"; 
		$sql=@mysql_query($sql);
		if ( @mysql_num_rows($sql)>0 ) {
			$this->kunde=array();
			while ( $res=@mysql_fetch_row($sql) ) $this->kunde[]=$res[0];
			return $dbID;
		} else {
			header('Location: '.IFAB_HTTP.'?AUTH_USER=1&'.IFABSID);
			exit();
		}
	} //PA_login
	// Ende: Benutzerauthentifizierung /////////////////////////////////////

	
	function PA_procTemplate($ps_dwt='') {
		global $server;
		$template=@gen_template(IFAB_DWT_ADMIN, FALSE, 'Mail-Update');
		$template->addComponent( 'inhalt', preg_replace("/(<t[dh].*?>)/is", "\\1", $this->news) );
		$template->addComponent( 'errorMsg', preg_replace("/(<t[dh].*?>)/is", "\\1", $this->errorMsg) );
		$template->addComponent( 'logout', new PLink(IFAB_HTTP.'?'.IFABSID, 'Zurück zur Hauptadministration') );
		if ( $this->kunde[0]==2 ) {
			foreach ( $server as $ip=>$data) {
				$template->addComponent( 'logout', '&nbsp;&nbsp;&nbsp;' );
				if ( $_SERVER['SERVER_ADDR']!=$ip ) $template->addComponent('logout', new PLink($data[0].IFAB_SCRIPT_MUPD.'?'.IFABSID, $data[2]));
			}
		}
		echo $template->outputStr();			// fertige Website ausgeben
	} //PA_procTemplate


	function PA_getmail($dbID, $type, $ku_id, $ti_id) {
		$ifabAdr[]="goetz.schlegel@i-fabrik.de";
		$ifabAdr[]="ralph.kropp@i-fabrik.de";
		$ifabAdr[]="erik.reuter@i-fabrik.de";
		$ifabAdr[]="goetz.schlegel@ifabrik.de";
		$ifabAdr[]="ralph.kropp@ifabrik.de";
		$ifabAdr[]="erik.reuter@ifabrik.de";

		$sql     = "SELECT ku_home FROM kunde WHERE ku_id='$ku_id'";
		$sql     = @mysql_query($sql, $dbID);
		$ku_home = @mysql_result($sql,0,0);

		$file = '/home/'.$ku_home.'/.procmailrc';
		if ( !is_readable($file) ) return array('Keine .procmailrc gefunden!!');
	
		$file = file($file);
		for ( $i = 0; $i < count($file); $i++ ) {
			if ( preg_match("/\| +\/usr\/bin\/perl +-w +\/usr\/local\/bin\/savemail.pl *$ku_id *$ti_id */", trim($file[$i])) ) {

				if ( 'S' === $type ) {
					$tmp = stripslashes(preg_replace("/\*.\^From:\.\*(.*)/i","\\1", $file[$i-2]));
					if ( !@in_array($tmp, $match) ) {
						if ( $this->kunde[0] == 2 || ( !in_array( trim($tmp), $ifabAdr) && !preg_match("/@i-fabrik\.de/is", trim($tmp)) ) )
							$match[] = $tmp;
					}
				}
				if ( 'G' == $type ) {
					$tmp = stripslashes( preg_replace("/\*.\^To:\.\*(.*)/i","\\1", $file[$i-1]) );
					if (!@in_array($tmp, $match)) $match[]=$tmp;
				}
			}
		}

		if ( !is_array($match) ) $match = array('Keine Adressen gefunden!!');
		return $match;
	} // PA_getmail

	//////////////////////////////////////////////////////////////////
	// Funktionen zur Anzeige der einzelnen Seiten (Teilbereiche)	//
	//////////////////////////////////////////////////////////////////
	
	// Startseite ////////////////////////////////////////////////////
	function PA_overview($dbID) {
		// Admin: alle Abonnenten anzeigen, ansonsten nur die vom Kunden
		if ( $this->kunde[0]==2 ) {
			$content="	<tr><td colspan=3><b><font size=3>Benutzer-Administration Programm E-Mail-Update</font></b></td></tr>
						<tr><td colspan=3><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_newUser\">Abonnent neu anlegen</b></a></td></tr>
						<tr><td colspan=3><b>Folgende Abonnenten können bearbeitet werden : </b></td></tr>";

			$sql="SELECT ku_id, ku_name FROM kunde WHERE ku_id<>2 ORDER by ku_name";
			$sql=@mysql_query($sql, $dbID);

			while ( $res=@mysql_fetch_array($sql) ) {
				$content.="	<tr><td heigth=\"35\"><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_adm&ku_id=$res[ku_id]\">$res[ku_name]</b></a></td>
								<td><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_usr&ku_id=$res[ku_id]\">Website-Administration ( $res[ku_name] )</b></a></td>
								<td><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_syn&ku_id=$res[ku_id]\">Synonyme</b></a></td>
							</tr>";
			}
		} else {
			$content="	<tr><td colspan=3><font size=3><b>Benutzer-Administration Programm E-Mail-Update</b></font></td></tr>
						<tr><td colspan=3><b>Folgende Abonnenten können bearbeitet werden : </b></td></tr>";

			$sql="SELECT ku_id, ku_name FROM kunde WHERE ku_id IN (".join(",", $this->kunde).")";
			$sql=@mysql_query($sql, $dbID);

			while ( $res=@mysql_fetch_array($sql) ) {
				$content.="	<tr><td heigth=\"35\"><b>$res[ku_name]</b></td>
								<td><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_usr&ku_id=$res[ku_id]\">Website-Administration</b></a></td>
								<td><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_syn&ku_id=$res[ku_id]\">Synonyme</b></a></td>
							</tr>";
			}
		}

		$content="<table width=\"100%\" height=\"80%\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\" align=\"center\">$content</table>";
		return $content;
	} //PA_overview


	// neuen User anlegen ////////////////////////////////////////////////////
	function PA_newUser($dbID) {
		$sql="SELECT MAX(ku_id) AS max FROM kunde" ; 
		$sql=@mysql_query($sql, $dbID);
		$ku_id=1+@mysql_result($sql,0,0); 

		@mysql_query("INSERT INTO kunde SET ku_id=$ku_id, ku_name='neu_$ku_id'", $dbID);

		return $this->PA_overview($dbID);
	} //PA_newUser
	
	// User-Administration ///////////////////////////////////////////////////
	function PA_adm($dbID, $ku_id ) {
		global $newslist;
		global $ti_id;
		global $action;

		switch ($action):

		// Daten eines Kunden aktualisieren
		case "Update Abonnement":
			if ( $_POST[ku_varLineBreak]!="X" ) $_POST[ku_varLineBreak]="";
			if ( $_POST[ku_start]!="1" )
			{// Startseitenfunktion aus table 'titel' entfernen
				$_POST[ku_start]="";
				$sql="DELETE FROM titel WHERE ku_id='$ku_id' AND ti_beze='Startseiten-Funktion'";
				@mysql_query($sql, $dbID);
			}else
			{// Startseitenfunktion in table 'titel' eintragen
				$sql="SELECT ti_beze FROM titel WHERE ku_id='$ku_id' AND ti_beze='Startseiten-Funktion'";
				$sql=@mysql_query($sql, $dbID);
				if (@mysql_num_rows($sql)==0)
				{
					$sql="SELECT MAX(ti_id) AS max FROM titel WHERE ku_id='$ku_id'";
					$sql=@mysql_query($sql, $dbID); // höchste zur Zeit vergebene ti_id des Kunden
					$max=1+@mysql_result($sql,0,0);
					
					$sql="INSERT INTO titel SET ku_id='$ku_id', ti_id='0', ti_beze='Startseiten-Funktion'";
					@mysql_query($sql, $dbID);
				}
			} 
			foreach ($_POST as $key => $value)
				if ( substr($key,0,3)=="ku_" ) $query[]="$key='".trim($value)."'";
			$query=join(", ", $query);

			$sql="UPDATE kunde SET $query WHERE ku_id='$ku_id'" ;
			@mysql_query($sql, $dbID);
			break;

		// Nachfrage ob Kunde wirklich gelöscht werden soll
		case "Delete Abonnement":
			$content.="	<br><br><br><b>Sie sind in Begriff, den Kunden $_POST[ku_name] zu l&ouml;schen !<br>
						<br>Sind Sie wirklich sicher ?? <br><br>
						<a href=\"$_SERVER[PHP_SELF]?exec=PA_adm&ku_id=$ku_id&really=1\">
						Kunde $_POST[ku_name] jetzt l&ouml;schen</a><b><br><br><br>
						<a href=\"$_SERVER[HTTP_REFERER]\">
						Kunde nicht l&ouml;schen und zur&ouml;ck</a>";
			return $content;
			break;

		// Newslist-Administration für übergebenen Kunden
		case "Themen bearbeiten":
			switch ($newslist):
			case "new":		// Neue Newsliste anlegen
				$sql="SELECT MAX(ti_id) AS max FROM titel WHERE ku_id='$ku_id'";
				$sql=@mysql_query($sql, $dbID); // höchste zur Zeit vergebene ti_id des Kunden
				$max=1+@mysql_result($sql,0,0);

				$sql="INSERT INTO titel SET ku_id='$ku_id', ti_id='$max', ti_beze='$_POST[ti_beze]',
						ti_templ='$_POST[ti_templ]', ti_css='$_POST[ti_css]'"; 
				@mysql_query($sql, $dbID);
				break;

			case "update":	// Ändern einer vorhandenen Newsliste
				if ( is_array($_POST['showDate']) )		// summarize the bits from date options
					foreach($_POST['showDate'] as $key=>$value) {
						$key=explode("_", $key);
						$tmp_showDate[$key[0]][$key[1]]=$value;
					}
				if ( is_array($_POST['titleStyle']) )		// summarize the bits from title options
					foreach($_POST['titleStyle'] as $key=>$value) {
						$key=explode("_", $key);
						$tmp_titleStyle[$key[0]][$key[1]]=$value;
					}

				for ($k=0;$k<count($_POST['listType']);$k++) {
					$listType[]=$_POST['listType'][$k];
					if ($tmp_showDate[$k][0]) $showDate[]=$tmp_showDate[$k][1]+$tmp_showDate[$k][2]+$tmp_showDate[$k][4]+$tmp_showDate[$k][8];else $showDate[]=-1;
					$titleStyle[]=$tmp_titleStyle[$k][1]+$tmp_titleStyle[$k][2]+$tmp_titleStyle[$k][4]+$tmp_titleStyle[$k][8]+$tmp_titleStyle[$k][16];
					if ($listType[$k]==0 || $listType[$k]==3 || $listType[$k]==6 || $listType[$k]==10 || $listType[$k]==12 || $listType[$k]==12) break;
				}
				$listType=join("|", $listType);
				$showDate=join("|", $showDate);
				$titleStyle=join("|", $titleStyle);

				$sql="UPDATE titel SET ti_beze='$_POST[ti_beze]', ti_templ='$_POST[ti_templ]', ku_varNews='$_POST[ku_varNews]',
							ti_css='$_POST[ti_css]', listType='$listType', showDate='$showDate', titleStyle='$titleStyle',
							ti_oldMessages='$_POST[ti_oldMessages]', linkProperties='$_POST[linkProperties]',
							img_width='$_POST[img_width]', img_height='$_POST[img_height]', tmb_width='$_POST[tmb_width]', tmb_height='$_POST[tmb_height]', ti_countBR='$_POST[ti_countBR]'
						WHERE ku_id=$ku_id and ti_id=$ti_id";
				@mysql_query($sql, $dbID);
				break;

			case "delete":	// Nachfrage, ob Newsliste wirklich gelöschen werden soll
				$sql="SELECT ti_beze, ku_name FROM titel T, kunde K WHERE K.ku_id=T.ku_id AND K.ku_id=$ku_id AND ti_id=$ti_id";
				$sql=@mysql_query($sql, $dbID);
				$res=@mysql_fetch_row($sql);

				$content="<br><br><br><b>Sie sind in Begriff, die Newsliste $res[0] <br>des Kunden $res[1] zu l&ouml;schen !<br>
							<br>Sind Sie wirklich sicher ?? <br><br>
							<form name=\"form\" method=\"post\" action=\"$_SERVER[PHP_SELF]?exec=PA_adm&ku_id=$ku_id&ti_id=$ti_id\">
								<input type='hidden' name='action' value='Themen bearbeiten'>
								<input type='hidden' name='newslist' value='delete2'>
								Newsliste $res[0] jetzt <b><input type='submit' name='submit' value='L&ouml;schen'>
							</form><br><br><br>
							<a href=\"$_SERVER[HTTP_REFERER]&action=Themen+bearbeiten\">Website nicht l&ouml;schen und zur&ouml;ck</a>"; 
				return $content;
				break;

			case "delete2":	// Löschen der Newsliste
				mysql_query("DELETE FROM titel WHERE ku_id=$ku_id AND ti_id=$ti_id", $dbID);
				mysql_query("DELETE FROM inhalt WHERE ku_id=$ku_id AND ti_id=$ti_id", $dbID);
				mysql_query("DELETE FROM bild WHERE ku_id=$ku_id AND ti_id=$ti_id", $dbID);
				break;

			endswitch;

			// mögliche Arten von Listen ermitteln
			//$file=file("/usr/local/etc/httpd/php-bin/include/PHPMyLib/PMail.php");
			//$file="/usr/local/etc/httpd/php-bin/include/PHPMyLib/PMail.php";
      	    $included = get_included_files();
			for ( $i = 0; $i < count($included); $i++ ) {
    	      	if ( strstr($included[$i], 'PMail.php') ) {
    	      		$file = $included[$i];
    	      		break;
    	      	}
			}
			unset($included);
			$file = file($file);
			for ( $j = 0; $j < count($file); $j++ ) 
			    if ( preg_match("/\/\/!![0-9]+\t(.*)/i", $file[$j], $matches) ) $listArray[]=$matches[1];

			// Themen des Kunden auflisten
			$content.="</table><center><table border=0 cellpadding=3><br><b>Bitte bearbeiten Sie Ihre Themen !</b><br><br><hr><br>";

			// falls Startseiten-Funktion aktiviert: //////////////////////////////////////
			$sql="SELECT ti_id, ti_beze, ku_varNews, ti_templ, ti_css, listType, showDate, titleStyle, ti_oldMessages, linkProperties, ti_countBR, tmb_width, tmb_height
					FROM titel WHERE ku_id=$ku_id AND ti_beze='Startseiten-Funktion'";
			$sql=@mysql_query($sql, $dbID);
			
			if (@mysql_num_rows($sql)==1) {
				$thema=@mysql_fetch_array($sql);
			
				$showDate=explode("|", $thema['showDate']);
				$listType=explode("|", $thema['listType']);
				$titleStyle=explode("|", $thema['titleStyle']);

				$content.="<form name=\"form\" method=\"post\" action=\"$_SERVER[PHP_SELF]?exec=PA_adm&ku_id=$ku_id&ti_id=$thema[ti_id]\">
					<tr><td colspan=4>
							<table cellpadding='3' cellspacing='0' border=0 width='100%'>
							<tr><td colspan=2>
								<table width='100%' cellpadding='0' cellspacing='0'><tr>
									<td><b>Startseiten-Funktion (ID $thema[ti_id]): </b></td>
									<td align='right'>
									<input type='hidden' name=\"ti_beze\" value='Startseiten-Funktion'>
									<input type='hidden' name='newslist' value='update'>
									<input type='hidden' name='action' value='Themen bearbeiten'>
									<input type=\"submit\" name=\"submit\" value=\"Daten &auml;ndern\"></td></tr>
								</table></td>
								<td colspan='2' rowspan='2' height='100%' align='center'>
								<table cellpadding='5' cellspacing='0'><tr>
									<td align='center'>Datum</td>
									<td align='center'><b>F</b></td>
									<td align='center'><i>K</i></td>
									<td align='center'><u>U</u></td>
									<td align='center'>nobr</td>
									<td align='center'>&nbsp;&nbsp;&nbsp;</td>
									<td align='center'>Titel:</td>
									<td align='center'><b>F</b></td>
									<td align='center'><i>K</i></td>
									<td align='center'><u>U</u></td>
									<td align='center'>&nbsp;!br&nbsp;</td>
									<td align='center'>nobr</td></tr>";

						if ( $showDate[0]>=0 ) {
							$selected[0]='checked';
							if ($showDate[0] & 1)	$selected[1]="checked";
							if ($showDate[0] & 2)	$selected[2]="checked";
							if ($showDate[0] & 4)	$selected[4]="checked";
							if ($showDate[0] & 8)	$selected[8]="checked";
						}
						if ($titleStyle[0] & 1)	$t_selected[1]="checked";
						if ($titleStyle[0] & 2)	$t_selected[2]="checked";
						if ($titleStyle[0] & 4)	$t_selected[4]="checked";
						if ($titleStyle[0] & 8)	$t_selected[8]="checked";
						if ($titleStyle[0] & 16)	$t_selected[16]="checked";
						$content.="<tr><td align='center'><input type='checkbox' name='showDate[0_0]' value=1 $selected[0] title='Datum anzeigen?'></td>
									<td align='center'><input type='checkbox' name='showDate[0_1]' value=1 $selected[1]></td>
									<td align='center'><input type='checkbox' name='showDate[0_2]' value=2 $selected[2]></td>
									<td align='center'><input type='checkbox' name='showDate[0_4]' value=4 $selected[4]></td>
									<td align='center'><input type='checkbox' name='showDate[0_8]' value=8 $selected[8]></td>
									<td align='center'>&nbsp;&nbsp;&nbsp;</td>
									<td align='center'>&nbsp;&nbsp;&nbsp;</td>
									<td align='center'><input type='checkbox' name='titleStyle[0_1]' value=1 $t_selected[1]></td>
									<td align='center'><input type='checkbox' name='titleStyle[0_2]' value=2 $t_selected[2]></td>
									<td align='center'><input type='checkbox' name='titleStyle[0_4]' value=4 $t_selected[4]></td>
									<td align='center'><input type='checkbox' name='titleStyle[0_8]' value=8 $t_selected[8]></td>
									<td align='center'><input type='checkbox' name='titleStyle[0_16]' value=16 $t_selected[16]></td><tr>
								</table></td></tr>";


				// Art der Newslisten wählen:
				$content.="<tr><td><b>Typ:</b></td>
						<td><select name='listType[]'>";
				for($j=0;$j<count($listArray);$j++)
				{
					$content.="<option value='$j'";
					if ($listType[0]==$j) $content.=" selected";
					$content.=">$listArray[$j]</option>";
				}
				$content.="</select></td></tr></table></td></tr>
							<tr><td><b>weitere Meldungen: </b></td>
								<td><input type=\"text\" size=\"40\" name=\"ti_oldMessages\" value='$thema[ti_oldMessages]'></td>
								<td><b>Link-Eigenschaften (zB frames): </b></td>
								<td><input type=\"text\" size=\"40\" name=\"linkProperties\" value='$thema[linkProperties]'></td></tr>";

				// max thumbnail size // BR-tags between the titles of following messages
				$content.="<tr><td><b>Max. Thumbsize:</b></td>
									<td><input type=\"text\" name=\"tmb_width\" value=\"$thema[tmb_width]\" size=\"5\" maxlength=\"4\"> x
										<input type=\"text\" name=\"tmb_height\" value=\"$thema[tmb_height]\" size=\"5\" maxlength=\"4\"></td>
									<td><b>BR-Tags zw. Titeln: </b></td>
									<td><input type=\"text\" name=\"ti_countBR\" value=\"$thema[ti_countBR]\" size=\"2\" maxlength=\"1\"></td></tr>";

				$content.="<tr><td colspan=4><br><hr><br><br></td></tr></form>";
			}
			// Ende: falls Startseiten-Funktion aktiviert: ////////////////////////////////

			$sql="	SELECT ti_id, ti_beze, ku_varNews, ti_templ, ti_css, listType, showDate, titleStyle, ti_oldMessages,
								linkProperties, ti_countBR, img_width, img_height, tmb_width, tmb_height
					FROM titel WHERE ku_id=$ku_id AND ti_beze not like 'Startseiten-Funktion'";
			$sql=@mysql_query($sql, $dbID);

			while ($thema = @mysql_fetch_array($sql))
			{
				$showDate=explode("|", $thema['showDate']);
				$listType=explode("|", $thema['listType']);
				$titleStyle=explode("|", $thema['titleStyle']);

				unset($selected);
				for ($k=0;$k<count($showDate);$k++)
				{
					if ($showDate[$k]>=0)
					{
						$selected[$k][0]="checked";
						if ($showDate[$k] & 1)	$selected[$k][1]="checked";
						if ($showDate[$k] & 2)	$selected[$k][2]="checked";
						if ($showDate[$k] & 4)	$selected[$k][4]="checked";
						if ($showDate[$k] & 8)	$selected[$k][8]="checked";
					}
				}

				unset($t_selected);
				for ($k=0;$k<count($titleStyle);$k++)
				{
					if ($titleStyle[$k] & 1)	$t_selected[$k][1]="checked";
					if ($titleStyle[$k] & 2)	$t_selected[$k][2]="checked";
					if ($titleStyle[$k] & 4)	$t_selected[$k][4]="checked";
					if ($titleStyle[$k] & 8)	$t_selected[$k][8]="checked";
					if ($titleStyle[$k] & 16)	$t_selected[$k][16]="checked";
				}

				for ($k=0;$k<count($listType);$k++)
					if ($listType[$k]==0 || $listType[$k]==3 || $listType[$k]==6 || $listType[$k]==10 || $listType[$k]==12 || $listType[$k]==15) break;

				$content.="
					<tr><td width=\"80\"><b>Newsliste $thema[ti_id]: </b></td>
						<form name=\"form\" method=\"post\" action=\"$_SERVER[PHP_SELF]?exec=PA_adm&ku_id=$ku_id&ti_id=$thema[ti_id]\">
						<td><table width='100%' cellpadding='0' cellspacing='0'><tr>
							<td><input type=\"text\" size=\"15\" name=\"ti_beze\" value=$thema[ti_beze]>
								<b>Platzhalter:</b> 
								<input type=\"text\" size=\"9\" name=\"ku_varNews\" value=$thema[ku_varNews]></td>                
							<td align='right'>
								<input type='hidden' name='newslist' value='update'>
								<input type='hidden' name='action' value='Themen bearbeiten'></td></tr></table></td>
						<td colspan='2'>
							<table width='100%' cellpadding='0' cellspacing='0'><tr>
								<td align='left'><input type=\"submit\" name=\"submit\" value=\"Daten &auml;ndern\"></td>
								<td align='center'><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_news&ku_id=$ku_id&ti_id=$thema[ti_id]\">Artikel diser Liste</a></b></td>
								<td align='right'><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_adm&ku_id=$ku_id&ti_id=$thema[ti_id]&newslist=delete&action=Themen+bearbeiten\">Website l&ouml;schen</a></b></td></tr>
							</table></td></tr>

					<tr><td><b>Template: </b></td>
						<td><input type=\"text\" size=\"40\" name=\"ti_templ\" value=\"$thema[ti_templ]\"></td>
						<td><b>css-Datei: </b></td>
						<td><input type=\"text\" size=\"40\" name=\"ti_css\" value=\"$thema[ti_css]\"></td></tr>

					<tr><td><b>weitere Meldungen: </b></td>
						<td><input type=\"text\" size=\"40\" name=\"ti_oldMessages\" value='$thema[ti_oldMessages]'></td>
						<td><b>Link-Eigenschaften (zB frames): </b></td>
						<td><input type=\"text\" size=\"40\" name=\"linkProperties\" value='$thema[linkProperties]'></td></tr>

					<tr><td><b>E-Mail-Absender: </b></td><td>";
				$content.=join("<br>", $this->PA_getmail($dbID, 'S', $ku_id, $thema[ti_id]));	// Mail-Empfänger aus .procmailrc auslesen
				$content.="</td><td><b>E-Mail-Empf&auml;nger: <b></td><td>";
				$content.=join("<br>", $this->PA_getmail($dbID, 'G', $ku_id, $thema[ti_id]));	// Mail-Empfänger aus .procmailrc auslesen
				$content.="</td></tr>";

				// max image size // BR-tags between the titles of following messages
				$content.="<tr><td><b>Max. Imagesize:</b></td>
									<td><input type=\"text\" name=\"img_width\" value=\"$thema[img_width]\" size=\"5\" maxlength=\"4\"> x
										<input type=\"text\" name=\"img_height\" value=\"$thema[img_height]\" size=\"5\" maxlength=\"4\"></td>
									<td><b>BR-Tags zw. Titeln: </b></td>
									<td><input type=\"text\" name=\"ti_countBR\" value=\"$thema[ti_countBR]\" size=\"2\" maxlength=\"1\"></td></tr>";

				// max image size
				$content.="<tr><td><b>Max. Thumbsize:</b></td>
									<td><input type=\"text\" name=\"tmb_width\" value=\"$thema[tmb_width]\" size=\"5\" maxlength=\"4\"> x
										<input type=\"text\" name=\"tmb_height\" value=\"$thema[tmb_height]\" size=\"5\" maxlength=\"4\"></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td></tr>";

				// Art der Newslisten wählen:
				$content.="<tr><td colspan='4'>
							<table cellpadding='0' cellspacing='0' width='100%'>
								<tr><td colspan='2'></td>
									<td align='center'>Datum</td>
									<td align='center'><b>F</b></td>
									<td align='center'><i>K</i></td>
									<td align='center'><u>U</u></td>
									<td align='center'>nobr</td>
									<td align='center'>&nbsp;&nbsp;&nbsp;</td>
									<td align='center'>Titel:</td>
									<td align='center'><b>F</b></td>
									<td align='center'><i>K</i></td>
									<td align='center'><u>U</u></td>
									<td align='center'>&nbsp;!br&nbsp;</td>
									<td align='center'>nobr</td></tr>";
										
				for ($i=0;$i<=$k;$i++)
				{
					$content.="<tr><td></td></tr><tr><td width='80'><b>$i. Ebene: </b></td>
						<td><select name='listType[]'>";
					for($j=0;$j<count($listArray);$j++)
					{
						$content.="<option value='$j'";
						if ($listType[$i]==$j) $content.=" selected";
						$content.=">$listArray[$j]</option>";
					}
					$content.="</select></td>";

					$content.="	<td align='center'><input type='checkbox' name='showDate[".$i."_0]' value=1 ".$selected[$i][0]."></td>
								<td align='center'><input type='checkbox' name='showDate[".$i."_1]' value=1 ".$selected[$i][1]."></td>
								<td align='center'><input type='checkbox' name='showDate[".$i."_2]' value=2 ".$selected[$i][2]."></td>
								<td align='center'><input type='checkbox' name='showDate[".$i."_4]' value=4 ".$selected[$i][4]."></td>
								<td align='center'><input type='checkbox' name='showDate[".$i."_8]' value=8 ".$selected[$i][8]."></td>
								<td align='center'>&nbsp;&nbsp;&nbsp;</td>
								<td align='center'>&nbsp;&nbsp;&nbsp;</td>
								<td align='center'><input type='checkbox' name='titleStyle[".$i."_1]' value=1 ".$t_selected[$i][1]."></td>
								<td align='center'><input type='checkbox' name='titleStyle[".$i."_2]' value=2 ".$t_selected[$i][2]."></td>
								<td align='center'><input type='checkbox' name='titleStyle[".$i."_4]' value=4 ".$t_selected[$i][4]."></td>
								<td align='center'><input type='checkbox' name='titleStyle[".$i."_8]' value=8 ".$t_selected[$i][8]."></td>
								<td align='center'><input type='checkbox' name='titleStyle[".$i."_16]' value=16 ".$t_selected[$i][16]."></td></tr>";
				}
				$content.="</table></td></tr>";
				$content.="<tr><td colspan=4><br><hr><br><br></td></tr></form>";
			} //while

			$content.="
					<tr><td colspan=4><b>Neue Newsliste anlegen: </b></td></tr>
					<tr><form name=\"form_new\" method=\"post\" action=\"$_SERVER[PHP_SELF]?exec=PA_adm&ku_id=$ku_id\">
						<input type='hidden' name='action' value='Themen bearbeiten'>
						<input type='hidden' name='newslist' value='new'>
						<td><b>Bezeichnung: </b></td>
						<td><input type=\"text\" size=\"15\" name=\"ti_beze\"></td>
						<td colspan='2'><input type=\"submit\" name=\"submit\" value=\"Anlegen\"></td></tr>
					<tr><td><b>Template: <b></td>
						<td><input type=\"text\" size=\"40\" name=\"ti_templ\"></td>
						<td colspan='2'>
							<table width='100%' cellpadding='0' cellspacing='0'><tr>
								<td><b>css-Datei: </b></td>
								<td><input type=\"text\" size=\"40\" name=\"ti_css\" value=\"$thema[ti_css]\"></td></tr>
							</table></td></tr>
					<tr><td colspan=4><br>
							<p align=\"center\"><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_adm&ku_id=$ku_id\">Zur&uuml;ck zum Obermen&uuml;</a></b></p></td></tr>
					<tr><td height=\"80%\"></td></tr>";

			return $content;
			break;

		// Kunden löschen
		default:
			global $really;

			if ($really==1)
			{
				//mysql_query("DELETE FROM bild WHERE ku_id='$ku_id'", $dbID);
				@mysql_query("DELETE FROM inhalt WHERE ku_id='$ku_id'", $dbID);
				@mysql_query("DELETE FROM kunde WHERE ku_id='$ku_id'", $dbID);
				@mysql_query("DELETE FROM titel WHERE ku_id='$ku_id'", $dbID);
				@mysql_query("DELETE FROM synonym WHERE ku_id='$ku_id'", $dbID);

				return $this->PA_overview($dbID);
			}
			break;
		endswitch;

		// Fomular mit Kundendaten generieren ////////////////////////////
		$sql="SELECT ku_name, ku_user, ku_pass, ku_www, ku_varLineBreak, ku_varfont, ku_fonttitle, ku_home, ku_linkStyle, ku_start
				FROM kunde WHERE ku_id='$ku_id'";
		$sql=@mysql_query($sql, $dbID);
		$res=@mysql_fetch_array($sql);

		if ( $res['ku_varLineBreak']=="X" ) $check="checked";
		if ( $res['ku_start']=="1" ) $check2="checked";
		if ( $res['ku_linkStyle']=="fett" ) $fett="selected";
			elseif ( $res['ku_linkStyle']=="kursiv" ) $kursiv="selected";
			else $normal="selected";

		$content.="<form name=\"form1\" method=\"post\" action=\"$_SERVER[PHP_SELF]?exec=PA_adm&ku_id=$ku_id\">
					<tr><td><font face=\"sans-serif, Helvetica, Arial\" size=4>
							<b>Bitte passen Sie nun die Daten des Abonnenten<br>$res[ku_name] an!</b></font><br><br></td>
						<td></td></tr>
					<tr><td><b>Kunden-ID: </b></td>
						<td><b>$ku_id</b></td></tr>                     
					<tr><td><b>Abonnement-Name: </b></td>
						<td><input type=\"text\" size=\"50\" name=\"ku_name\" value='$res[ku_name]'></td></tr>
					<tr><td><b>Login: </b></td>
						<td><input type=\"text\" size=\"50\" name=\"ku_user\" value='$res[ku_user]'></td></tr>                 
					<tr><td><b>Web-Adresse: </b></td>
						<td><input type=\"text\" size=\"50\" name=\"ku_www\" value='$res[ku_www]'></td></tr>                 
					<tr><td><b>Sollen Dokumentenzeilen-Umbrüche<br>gegen HTML-Umbrüche ersetzt werden?: </b></td>
						<td><input type=\"checkbox\" name=\"ku_varLineBreak\" value='X' $check></td></tr>
					<tr><td><b>Schriftart für den Text: </b></td>
						<td><input type=\"text\" size=\"50\" name=\"ku_varfont\" value='$res[ku_varfont]'></td></tr>                 
					<tr><td><b>Schriftart für &Uuml;berschriften : </b></td>
						<td><input type=\"text\" size=\"50\" name=\"ku_fonttitle\" value='$res[ku_fonttitle]'></td></tr>                 
					<tr><td><b>Schriftstyle bei Link :</b></td>
						<td><select name=ku_linkStyle>
								<option value=\"\" $normal>normal</option>
								<option $fett>fett</option>
								<option $kursiv>kursiv</option>
							</select></td></tr>
					<tr><td><b>Home-Verzeichnis auf dem C3PO: </b></td>
						<td><input type=\"text\" size=\"50\" maxlength=\"25\" name=\"ku_home\" value=$res[ku_home]></td></tr>
					<tr><td><b>Startseiten-Funktion aktivieren:</b></td>
						<td><input type=\"checkbox\" name=\"ku_start\" value='1' $check2></td></tr>
				</table><br>
				<table align=\"center\"><tr><td>
				<p align=\"center\">
				<input type=\"submit\" name=\"action\" value=\"Update Abonnement\">&nbsp;&nbsp;&nbsp;&nbsp;
				<input type=\"submit\" name=\"action\" value=\"Themen bearbeiten\">&nbsp;&nbsp;&nbsp;&nbsp;
				<input type=\"submit\" name=\"action\" value=\"Delete Abonnement\">
				</form><br><br>
				<b><a href=\"$_SERVER[PHP_SELF]\">Zur&uuml;ck zum Hauptmen&uuml;</a></b></p></td></tr>" ;             
		// Ende: Fomular-Generierung //////////////////////////////////////////////////
		$content="<table width=\"100%\" height=\"80%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\">$content</table>";
		return $content;
	} //PA_adm


	// Synonyme-Verwaltung
	function PA_syn($dbID, $ku_id)
	{
		global $back;

		if ( $back!="PA_usr" ) $HTTP_REFERER=$_SERVER['PHP_SELF'];
			else $HTTP_REFERER="$_SERVER[PHP_SELF]?exec=PA_usr&ku_id=$ku_id";

		// Bei Update Änderungen/Löschungen ausführen
		if ($_POST['action']=="Update")
		{
			if (!$_POST['syn_old']) $_POST['syn_old']=array();
			foreach ($_POST['syn_old'] as $key => $syn_old)
			{
				$syn_kurz=trim($_POST['syn_kurz'][$key]);
				$syn_lang=trim($_POST['syn_lang'][$key]);
				$del="del_$syn_old";
				$del=$_POST[$del];

				if ( $del || $syn_kurz=="" ) $sql="DELETE FROM synonym WHERE ku_id=$ku_id AND syn_kurz='$syn_old'";
					else $sql="UPDATE synonym SET syn_kurz='$syn_kurz', syn_lang='$syn_lang' WHERE ku_id=$ku_id AND syn_kurz='$syn_old'";
				@mysql_query($sql, $dbID);
			}

			// wenn neues Synonym eingegeben wurde:
			if ($_POST['syn_kurz_neu']!="")
			{
				$sql="REPLACE INTO synonym SET ku_id=$ku_id, syn_kurz='$_POST[syn_kurz_neu]', syn_lang='$_POST[syn_lang_neu]'";
				@mysql_query($sql, $dbID);
			}
		}

		// Übersicht der Synonyme generieren
		$sql="SELECT syn_kurz, syn_lang FROM synonym WHERE ku_id=$ku_id";
		$sql=@mysql_query($sql, $dbID);

		$content.="<form name=\"form1\" method=\"post\" action=\"$_SERVER[PHP_SELF]?exec=PA_syn&ku_id=$ku_id&back=$back\"><tr><td>
					<table width=\"50%\" heigth=\"70%\" align=\"center\" valign=\"center\" border=\"1\"><tr><th>Synonym-Kurz</th><th>Synonym-Lang</th><th>&nbsp;</th></tr>";

		while ($res=@mysql_fetch_array($sql))
		{
			$content.="	<tr><td height=\"20\"><input type=\"text\" name=\"syn_kurz[]\" size=\"12\" value=\"$res[syn_kurz]\"></td>
							<td><input type=\"text\" name=\"syn_lang[]\" size=\"60\" value=\"$res[syn_lang]\"></td>
							<td><span class=\"tred\">L&ouml;schen<input type=\"checkbox\" name=\"del_$res[syn_kurz]\" value=\"1\"></span></td></tr>
							<input type='hidden' name='syn_old[]' value='$res[syn_kurz]'>";
		} 

		$content.="	<tr><td height=\"20\"><input type=\"text\" name=\"syn_kurz_neu\" size=\"12\" ></td>
						<td><input type=\"text\" name=\"syn_lang_neu\" size=\"60\"></td><td>&nbsp;</td></tr></table>
					<tr><td>
						<p align=\"center\"><b>
						<br><input type=\"submit\" name=\"action\" value=\"Update\">
						<br><br>
						<a href=\"$HTTP_REFERER\">Zur&uuml;ck zum Hauptmen&uuml;</a>
						</b><p></td></tr></form>";
		// Ende: Übersicht

		return $content;
	} //PA_syn

	// Bearbeitung der Newslisten eines Kunden
	function PA_usr( $dbID, $ku_id ) {
		if ($_POST['action']=='update') {
			$sql="UPDATE kunde SET ku_anztage='$_POST[ku_anztage]' WHERE ku_id=$ku_id";	// Anzahl der Tage, die ein Artikel standardmäßig angezeigt wird, ändern
			@mysql_query($sql, $dbID);
		}

		// Kundendaten ermitteln
		$sql="SELECT ku_name, ku_anztage, ku_www FROM kunde WHERE ku_id=$ku_id";
		$sql=@mysql_query($sql, $dbID);
		$res=mysql_fetch_array($sql);
		$ku_www=$res['ku_www'];

		if ($this->kunde[0]==2 || count($this->kunde)>1)
			$content.="<tr><td heigth=30><b><a href=\"$_SERVER[PHP_SELF]\">Zur Mail-Update Startseite</a></b></td></tr>";

		$content.="<tr><td heigth=30 colspan=3><b>Newslisten-Administration: $res[ku_name]</b></td>
					<td width=\"30%\" align=\"right\"><!--<a href=\"hilfe.html\" target=\"blank_\"><img src=\"img/hilfe.jpg\" border=\"0\"></a>--></td></tr>
				<tr><td colspan=2><b>Anzahl der Tage, die ein neuer Artikel angezeigt wird :<b></td>
					<td><form name=\"form1\" method=\"post\" action=\"$_SERVER[PHP_SELF]?exec=PA_usr&ku_id=$ku_id\">
						<input type=\"text\" name=\"ku_anztage\" size=\"5\" value=\"$res[ku_anztage]\">
						<input type='hidden' name='action' value='update'>
						<input type=\"submit\" name=\"submit\" value=\"Tage &nbsp;&nbsp; &auml;ndern\"></form></td></tr>";
//				<tr><td heigth=30 colspan=3><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_pass&ku_id=$ku_id\">Passwort &auml;ndern</a></b></td></tr>
		$content.="<tr><td colspan=4><b>Die folgenden Themen k&ouml;nnen bearbeitet werden.<br>
					Bitte klicken Sie auf den gew&uuml;nschten Link ! : </b></td></tr><tr><td>&nbsp;</td></tr>" ;

		// Themen des Kunden ermitteln
		$sql="SELECT ti_id, ti_beze FROM titel WHERE ku_id='$ku_id'";
		$sql=mysql_query($sql, $dbID);

		while ( $res = mysql_fetch_array($sql) ) {
			$content.="<tr><td heigth=30 valign=top><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_news&ku_id=$ku_id&ti_id=$res[ti_id]\">$res[ti_beze]</a></b></td>
							<td  valign=top><b><a href=\"$ku_www/mail_update/news.php?id=$res[ti_id]\" target=\"_blanc\">Online-Ansicht</a></b></td>";
			if ( $res['ti_beze']!="Startseiten-Funktion" ) {
				$content.="<td valign=top><b>Mail-Adresse:</b><br>&nbsp;&nbsp;";
				$content.=join( "<br>&nbsp;&nbsp;", $this->PA_getmail($dbID, 'G', $ku_id, $res['ti_id']) );
				$content.="</b></td><td valign=top><b>Ihre Absender-Adresse:</b><br>&nbsp;&nbsp;";
				$content.=join( "<br>&nbsp;&nbsp;", $this->PA_getmail($dbID, 'S', $ku_id, $res['ti_id']) );
				$content.="</b></td>";
			} else $content.="<td></td><td></td>";
			$content.="</tr>";
		}

		$content.="</tr><td heigth=30 valign=top><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_syn&ku_id=$ku_id&back=PA_usr\">Vereinbarte Synonyme</a></b></td></tr>" ;

		$content="<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\" align=\"center\">$content</table>";
		return $content;
	} //PA_usr

	// Login und Passwort ändern:
	function PA_pass( $dbID, $ku_id ) {
		$sql="SELECT ku_name, ku_user, ku_pass FROM kunde WHERE ku_id=$ku_id";
		$sql=@mysql_query($sql, $dbID);
		$res=@mysql_fetch_array($sql);

		$content.="<table width=\"80%\" border=\"0\" cellspacing=\"4\" cellpadding=\"5\" align=\"center\"> 
			<form name=\"form1\" method=\"post\" action=\"$_SERVER[PHP_SELF]?exec=PA_pass&ku_id=$ku_id\">
			<tr><td colspan=2><font size=4><b>Passwort-&Auml;nderung:</b></font></td></tr>";

		$errorMsg="<tr><td>&nbsp;</td></tr>";

		if ( !empty($_POST['action']) ) {
			if ( $_POST['pass_old']==$res['ku_pass'] && trim($_POST['pass_new1'])!="" && $_POST['pass_new1']==$_POST['pass_new2'] ) {
				$sql = "UPDATE kunde SET ku_pass=PASSWORD('$_POST[pass_new1]') WHERE ku_id=$ku_id";
				@mysql_query($sql, $dbID);
			} else $errorMsg="<tr><td colspan=2><font color=#FF0000><b>Fehleingabe !!!</b></font></td></tr>";
		}

		$content.="$errorMsg<tr><td><b><br>Website: </b></td>
				<td><br><br><b>$res[ku_name]</b></td></tr>
			<tr><td><b>Login:</b></td>
				<td><b>$res[ku_user]</b></td></tr>
			<tr><td><b>Passwort alt :</b></td>
				<td><input type=\"password\" size=\"50\" name=\"pass_old\"></td></tr>
			<tr><td><b>Passwort neu :</b></td>
				<td><input type=\"password\" size=\"50\" name=\"pass_new1\"></td></tr>
			<tr><td><b>Passwort wiederholen :</b></td>
				<td><input type=\"password\" size=\"50\" name=\"pass_new2\"></td></tr> 
			</table>
			<br><br><p align=\"center\">
			<input type=\"submit\" name=\"action\" value=\"&Auml;ndern\"></form>
			<br><br><a href=$_SERVER[PHP_SELF]?exec=PA_usr&ku_id=$ku_id><b>Zur&uuml;ck</b></a></p>" ;
  		return $content;
	} //PA_pass

	// Artikel einer gewählten Newsliste verwalten
	function PA_news( $dbID, $ku_id, $ti_id )
	{
		// Webadresse und Vorhandensein der Startfunktion überprüfen
		$sql="SELECT ku_www, ku_start, ku_name FROM kunde WHERE ku_id=$ku_id";
		$sql=@mysql_query($sql, $dbID);
		$ku_info=@mysql_fetch_row($sql);

		// bei Update der Artikel: //////////////////////////////////////////////
		if ($_POST['action']=="Änderung übernehmen")
		{
			if ( !is_array($_POST['in_id']) ) $_POST['in_id']=array();
			foreach ( $_POST['in_id'] as $key => $in_id )
			{
				// Artikel löschen
				if ( $_POST['del'][$in_id] == 'X')
				{
					$sql="DELETE FROM inhalt WHERE ku_id=$ku_id AND ti_id=$ti_id AND in_id=$in_id";
					@mysql_query($sql, $dbID);
					continue;
				} //Ende: Artikel löschen
	
				// Eingangsdatum ändern
				$set=array();
				$in_date=explode(".", trim($_POST['in_date'][$in_id]));
				if (checkdate($in_date[1], $in_date[0], $in_date[2])) $set[]="in_date='$in_date[2]/$in_date[1]/$in_date[0]'";
				// noch anzuzeigende Dauer ändern
				$set[]="in_tage='".(int)trim($_POST['in_tage'][$in_id])."'";
				// Flag setzen, ob Artikel angezeigt werden soll
				$set[]="in_zeig='".trim($_POST['in_zeig'][$in_id])."'";
				// Position bei Sortierung ändern
				$set[]="in_sort='".trim($_POST['in_sort'][$in_id])."'";
				// falls 'Anzeige auf Startseite' geändert
				if ( $ku_info[1] ) $set[]="in_teaser='".trim($_POST['in_teaser'][$in_id])."'";
	
				$set=join(", ", $set);
				$sql="UPDATE inhalt SET $set WHERE ku_id=$ku_id AND ti_id=$ti_id AND in_id=$in_id";
				@mysql_query($sql, $dbID);
			}
	
			// Sortierreihenfolge updaten:
			if ( $_POST['ti_ord']!="" )
			{
				$sql="UPDATE titel SET ti_ord='$_POST[ti_ord]' WHERE ku_id='$ku_id' AND ti_id='$ti_id'";
				@mysql_query($sql, $dbID);
			}
		} // Ende: Update Artikel////////////////////////////////////////////////

		// bei Update der Remind-Einstellungen: //////////////////////////////////////////////
		if ( $_POST['action']=="Speichern" )
		{
			if ( $_POST['remind']==1 ) $remind=$_POST['days_remind']; else $remind=-1*$_POST['days_remind'];
			$sql_remind="UPDATE titel SET ti_remind='$remind' WHERE ku_id='$ku_id' AND ti_id='$ti_id'";
			mysql_query($sql_remind, $dbID);
		} // bei Update der Remind-Einstellungen: //////////////////////////////////////////////

		//$content.="</table><p>";
		$content.="<p>";

		$ti_info=@mysql_query("SELECT ti_beze, ABS(ti_remind), SIGN(ti_remind) FROM titel WHERE ku_id='$ku_id' AND ti_id='$ti_id'", $dbID);
		$ti_info=@mysql_fetch_row($ti_info);
		$content.="<h1>&nbsp;Newsliste <i><b>$ti_info[0]</b></i>";

		if ( $this->kunde[0]==2 || count($this->kunde)>1 )
			$content.=" von $ku_info[2]</h1>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><a href=\"$_SERVER[PHP_SELF]\">Zur&uuml;ck zur Hauptseite</a></b>";
		//$content.="<br><br><a href=\"hilfe.html\" target=\"blank_\"><img src=\"img/hilfe.jpg\" border=\"0\"></a></p>";

		// form for configuration of remind-tool
		unset($row);
		if ( trim($ti_info[0])!="Startseiten-Funktion" )
		{
			if ($ti_info[2]==1) $chk_remind=array("checked"=>"");else $chk_remind="";
			$table=new PTable();
			$row[0]="<div align=right>Erinnerungs-Mails aktivieren: </div>";
			$row[1]="&nbsp;";
			$row[2]=new PInput( "checkbox", "remind", "1", $chk_remind );
			$table->addRow($row);
			$row[0]="<div align=right>Wieviel Tage vor Ablauf der letzten News soll die Erinnerungs-Mail<br>geschickt werden? (min. 1 Tag)</div>";
			$row[1]="&nbsp;";
			$row[2]=new PInput( "text", "days_remind", $ti_info[1], array("maxlength"=>"4", "size"=>"4") );
			$row[3]=new PInput( "submit", "action", "Speichern" );
			$table->addRow($row);
			unset($row);
	
			$form=new PForm("$_SERVER[PHP_SELF]?exec=PA_news&ku_id=$ku_id&ti_id=$ti_id", "Post");
			$form->add($table);
			$content.=$form->outputStr();
		}
		// End: configuration form for remind-tool


		// begin form for editing the articles
		$content.="<form name=\"form1\" method=\"post\" action=\"$_SERVER[PHP_SELF]?exec=PA_news&ku_id=$ku_id&ti_id=$ti_id\">
				<table width=\"100%\" border=\"1\" cellspacing=\"2\" cellpadding=\"2\">";

		$sql="SELECT in_id, in_titel, in_zeig, in_teaser, DATE_FORMAT(in_date, '%d.%m.%Y') AS in_date, in_tage, in_sort, in_code,
			(in_tage - ( TO_DAYS(NOW())-TO_DAYS(in_update) )) AS day FROM inhalt
			WHERE ku_id='$ku_id' AND ti_id='$ti_id'";
		$sql=@mysql_query($sql, $dbID);
		if ( @mysql_num_rows($sql) > 0 )
		{       
			$content.="<tr><th>ID</th><th>Titel</th><th>Datum</th><th colspan=2>Anzeige</th><th>Artikel noch ? Tage anz.</th><th colspan=2><span class=\"tred\">Löschen</span></th><th colspan=2>Sortierung</th></tr>";

			while ($thema=@mysql_fetch_array($sql))
			{ 
				if ($thema['day']<0) $thema['day']=0;
				if ($thema['in_zeig']=="X") $in_zeig="checked"; else $in_zeig="";
				if ($thema['in_teaser'] || $thema['in_teaser']=="X") $in_teaser="checked"; else $in_teaser="";

				$content.="<tr><td width=2>$thema[in_id]</td> 
					<input type='hidden' name='in_id[]' value='$thema[in_id]'>
					<td width=140 height=\"25\" cellspacing=\"2\"><b><a href=\"".$this->PA_showArt($dbID, $ku_id, $ti_id, $thema[in_id])."\" target=\"_blanc\">$thema[in_titel]</a></b></td>
					<td width=30 height=\"25\" cellspacing=\"2\"><input type=\"text\" size=\"9\" name=\"in_date[".$thema[in_id]."]\" value=\"$thema[in_date]\"></td>  
					<td width=30>anzeigen</td>
					<td width=5><input type=\"checkbox\" name=\"in_zeig[".$thema[in_id]."]\" value=\"X\" $in_zeig></td>
					<td width=120>noch <input type=\"text\" size=\"3\" maxlength=3 name=\"in_tage[".$thema[in_id]."]\" value=\"$thema[day]\"> <nobr>Tage anz.<nobr></td>
					<td width=30><span class=\"tred\">l&ouml;schen</span></td>
					<td width=5><input type=\"checkbox\" name=\"del[".$thema[in_id]."]\" value=\"X\"></td>  
					<td width=30>Sortierung</td>
					<td width=10><input type=\"text\" size=\"3\" maxlength=3 name=\"in_sort[".$thema[in_id]."]\" value=\"$thema[in_sort]\"></td>";
				// wenn Startseitenfunktion vorhanden, dann Auswahlfeld für diesen Artikel anzeigen
				if ( $ku_info[1] ) $content.="
					<td width=5><nobr>auf Startseite: <input type=\"checkbox\" name=\"in_teaser[".$thema[in_id]."]\" value=\"1\" $in_teaser></nobr></td>";
				// wenn Admin, dann Link zum Quelltxt anzeigen
				if ( $this->kunde[0]==2 ) $content.="<td width=30><a href=\"$_SERVER[PHP_SELF]?exec=PA_src&ku_id=$ku_id&ti_id=$ti_id&in_id=$thema[in_id]\">Quelltext</a></td>";
					else $content.="<td width=1></td>";
					//<td width=5><input type=\"checkbox\" name=\"in_teaser/$thema[in_id]\" value=\"X\" $che></td>                                                        
				$content.="</tr>\n\n";
			} // while
		}else $content.="<tr><td align=\"center\"><br><b>Es sind keine Artikel vorhanden!</b><br></td></tr>";

		$sql="SELECT ti_ord FROM titel WHERE ti_id='$ti_id' AND ku_id='$ku_id'";
		$sql=@mysql_query($sql, $dbID);
		$order=@mysql_result($sql,0,0);
		if (trim($order)=="in_id DESC") $order="in_id";
		$$order="checked";
		
		$content.="</table><br><table align=\"left\">
			<tr><td><b>Datensätze sortieren nach </b></td><td><input type=radio name=\"ti_ord\" $dat value=\"dat\"></td><td> Datum </td></tr>
			<tr><td> </td><td> <input type=radio name=\"ti_ord\" $in_titel value=\"in_titel\"></td><td> Titel(alphabetisch) </td></tr>                         
			<tr><td> </td><td> <input type=radio name=\"ti_ord\" $in_id value=\"in_id DESC\"></td><td> ID (Reihenfolge, in der Mails geschickt wurden)  </td></tr>                      
			<tr><td> </td><td> <input type=radio name=\"ti_ord\" $in_sort value=\"in_sort\"></td><td> Sortierung, wie oben angegeben (1 steht an oberster Stelle (dann 2,3,4,5 ...))
					<br>Sie können auch nur bestimmte Artikel numerieren, der Rest wird nach Aktualität geordnet.  </td></tr>
			<tr><td><input type=\"submit\" name=\"action\" value=\"&Auml;nderung &uuml;bernehmen\"></td><td colspan=2><b><font color=\"red\">
					&nbsp;&nbsp;Achtung!! H&auml;ckchen bei L&ouml;schen &uuml;berschreibt H&auml;ckchen bei Anzeigen!!</font></b></td></tr>
			<tr><td align=center><br><br><b><a href=\"$_SERVER[PHP_SELF]?exec=PA_usr&ku_id=$ku_id\">Zur&uuml;ck zur Newslisten-&Uuml;bersicht</a></b></td>
				<td></td><td align=left><br><br>
				<b><a href=\"$ku_info[0]/mail_update/news.php?id=$ti_id\" target=\"_blanc\">Online-Ansicht dieser Newsliste</a></b></td></tr>
			</table></form>";

		return $content;
	} //PA_news

	// Erzeugung eines Formulars zur Quelltext-Editierung eines Artikels
	function PA_src($dbID, $ku_id, $ti_id, $in_id) {
		if ( $_POST['action'] ) {
			$sql="UPDATE inhalt SET in_titel='$_POST[in_titel]', in_code='$_POST[in_code]'
					WHERE ku_id=$ku_id AND ti_id=$ti_id AND in_id=$in_id";
			@mysql_query($sql, $dbID);
		}

		$sql="SELECT in_titel, in_code FROM inhalt WHERE ku_id='$ku_id' AND ti_id='$ti_id' AND in_id='$in_id'";
		$sql=@mysql_query($sql, $dbID);
		$res=@mysql_fetch_row($sql);

		$source="<p align=center><form name=\"form\" method=\"post\" action=\"$_SERVER[PHP_SELF]?exec=PA_src&ku_id=$ku_id&ti_id=$ti_id&in_id=$in_id\">
			<input type='hidden' name='src_id' value='$src_id'>
			<br><br><b>Titel:</b><br>
			<textarea cols=65 rows=2 name=\"in_titel\">$res[0]</textarea>
			<br><b>Nachricht:</b><br>
			<textarea cols=65 rows=30 name=\"in_code\">$res[1]</textarea><br>
			<input type=\"submit\" name=\"action\" value=\"Daten &nbsp;&nbsp; &auml;ndern\"></p>
			<br><p>	<b><a href=\"".$this->PA_showArt($dbID, $ku_id, $ti_id, $in_id)."\" target=\"_blanc\">Vorschau</a></b><br><br>
					<b><a href=\"$_SERVER[PHP_SELF]?exec=PA_news&ku_id=$ku_id&ti_id=$ti_id\">zur&uumlck</a></b></p>";
		return $source;
	} //PA_src

	// Vorschau auf einen Artikel
	function PA_showArt( $dbID, $ku_id, $ti_id, $in_id ) {
		$sql="SELECT ku_www FROM kunde WHERE ku_id=$ku_id";
		$sql=@mysql_query($sql, $dbID);
		$link=@mysql_result($sql,0,0);
		$link.="/mail_update/news.php?id=$ti_id&in_id=$in_id&PA_adm=1";

		return $link;
	}
	// PA_showArt
}
// end:class:PAdmin

/*********************************************************/
/**	Klasse für die Anzeige von News ( MailUpdate-Tool )	**/
/*********************************************************/ 
class PMail extends PUtil {

	var $errorMsg='';
	var $news=array();	// 1. Artikel der generierten Newsliste
	var $news2=array();	// restliche generierte Newsliste
	var $back='';			// zurück-button
	var $image;				// einzelnes Bild für extra Platzhalter
	var $ident;				// Websiteinfos (array)
	var $nextLevel;
	var $backLink;
	var $level=0;
	var $template=FALSE;
	var $tpl_firstArticle=FALSE;
	var $tpl_secondArticle=FALSE;
	var $tpl_backBlock=FALSE;
	//var $tpl_noNews=FALSE;
	//var $linkToMore=FALSE;
	//var $linkToMore2=FALSE;

	// Constructor
	function PMail( $ti_id ) {
		$newsList=array();
		if ( empty($_GET['level']) ) $this->level=0; else $this->level=(int)$_GET['level'];
		$this->nextLevel=1+$this->level;

		$dbID=$this->db_connect();															// Datenbank-Verbindung herstellen
		@$this->login($ti_id);																// falls verlangt:	Benutzerauthentifizierung (in DB::titel konfigurieren)
		$this->set_kuInfos($dbID, $ti_id);												// Konfiguration der NewsListe aus Datenbank ermitteln

		if ( !empty($_REQUEST['img']) )													// output image (and exit script)
			@$this->output_img($_REQUEST['img'], $this->ident['tmb_width'], $this->ident['tmb_height']);

		// initialize template and load template blocks if not startlist or virtual
		if ( empty($_GET['virtual']) && (empty($this->ident['ku_start']) || $this->ident['ti_beze']!='Startseiten-Funktion') )
			$this->loadTemplate($this->level);

		$listTypes=explode('|', $this->ident['listType']);							// NewsListTypen für verschiedene Verschachtelungstiefen ermitteln
		$this->ident['listType']=$listTypes[$this->level];							// aufzufufende Funktion für aktuelle Verschachtelungsebene (Art der NewsListe)

		$this->ident['showDate']=explode('|', $this->ident['showDate']);		// Anzeige des Eingangsdatums der Mails: 0 - nein, 1 - ja
		$this->ident['showDate']=$this->ident['showDate'][$this->level];		// für diese Ebene ermitteln, ob Datum angezeigt werden soll

		$this->ident['titleStyle']=explode('|', $this->ident['titleStyle']);	// Format der Überschriften ( mail-subjects ): 0 - normal, 1 - fett, 2 - kursiv
		$this->ident['titleStyle']=$this->ident['titleStyle'][$this->level];	// Format der Überschrift für diese Ebene ermitteln
		if ( $this->ident['ku_start'] && $this->ident['ti_beze']=='Startseiten-Funktion' ) {
			// falls Startseite
			foreach($this->getStartList($dbID) as $art) $newsList[]=$art;		// Artikelliste aus Datenbank extrahieren
		} else {
			// falls nicht Startseite
			if ( isset($_REQUEST['in_id']) ) $newsList=$this->getNewsList($dbID,$_REQUEST['in_id'],1); // speziell ausgewählten Artikel aus Datenbank extrahieren
			foreach ($this->getNewsList($dbID, $_REQUEST['in_id']) as $art)	// Artikelliste aus Datenbank extrahieren
				$newsList[]=$art;
		}

		if ( count($newsList)>0 ) {														// Output generieren
			if ( $this->tpl_firstArticle ) $this->genOutput_2($newsList);
				else $this->genOutput($newsList);
		}

		// falls tiefste Ebene, ZURÜCK-Button generieren:
		if ( ($this->level+1) == count($listTypes) && $this->level>0 ) {
			$this->back='<br><a href="'.$this->backLink.'" '.$this->ident['linkProperties'].'>zur&uuml;ck</a>';
			if ( strpos($this->ident['ku_varfont'], 'font') ) $this->back=$this->ident['ku_varfont'].$this->back.'</font>';
		}

		if ( ($this->ident['ku_start'] && $this->ident['ti_beze']=='Startseiten-Funktion') || $_GET['virtual'] ) {           // bei Startseite nur einfache Ausgabe
                  ob_start();
			if ( count($this->news)==0 && count($this->news2)==0 ) echo 'Es sind zur Zeit keine Artikel vorhanden!';
			if ( count($this->news) > 0 ) echo $this->news = join( str_repeat('<br>',$this->ident['ti_countBR']), $this->news );
			if ( count($this->news2) > 0 ) {
				$this->news2 = join(str_repeat('<br>',$this->ident['ti_countBR']), $this->news2);
				if( trim($this->ident['ti_oldMessages'])!='' ) echo '<br>'.$this->ident['ti_oldMessages'].'<br>';
				echo '<br>'.$this->news2;
			}
                     $content = ob_get_contents();
                     $content = trim( str_replace('***SPAM***', '', $content) );
                  ob_end_clean();
                  echo $content;
		} else $this->procTemplate( count($newsList) );								// Template initialisieren und ausgeben
	}
	// Constructor: PMail


	// Datenbank-Connection herstellen
	function db_connect() {
		$login['host'] = 'localhost';
		$login['user'] = $_SERVER['MUPDUser'];
		$login['password'] = $_SERVER['MUPDPass'];
		$login['database'] = "mail_update";
	
		$dbID = @mysql_connect ($login['host'], $login['user'], $login['password'])
					or $this->errorMsg='Verbindung zur Datenbank fehlgeschlagen!!';
		if ($dbID) @mysql_select_db($login['database'], $dbID)
					or $this->errorMsg='Verbindung zur Datenbank fehlgeschlagen!!';

		if ( !$dbID ) {														//bei fehlgeschlagener Verbindung
			$this->loadTemplate($this->level);							// Template initialisieren
			$this->errorMsg.='<br><br><a href="'.$this->backLink.'">zur&uuml;ck</a>';
			$this->procTemplate();											// Template ausgeben
			exit();
		} else return($dbID);
	}
	// db_connect

	// Benutzerauthentifizierung (falls ti_user und ti_pass gesetzt)//////////////////////////////
	function login($ti_id) {
		$m_home = preg_replace( "/\/home\/(.*?)\/.*/i", "\\1", $_SERVER['DOCUMENT_ROOT'] );	//home-verzeichnis ermitteln
		$sql="SELECT ti_user, ti_pass, PASSWORD('$_SERVER[PHP_AUTH_PW]') FROM titel T, kunde K WHERE ti_id='$ti_id' AND ku_home='$m_home' AND T.ku_id=K.ku_id AND NOT TRIM(ti_user)='' ";
		$sql=@mysql_query($sql);

		if ( $res=@mysql_fetch_row($sql) ) {			// restrict only, if user password set
			if( !$_SERVER['PHP_AUTH_USER'] ) {
				Header('status: 401 Unauthorized');		// For Roxen cgi-wrapper
				Header('HTTP/1.0 401 Unauthorized');
				Header('WWW-authenticate: basic realm="Geschützter Bereich"');
				Header("KiSS: $_SERVER[PHP_AUTH_USER], $_SERVER[PHP_AUTH_PW]");
				PUtil::showError();
				exit();
			}
			if ( $_SERVER['PHP_AUTH_USER']!=$res[0] || $res[1]!=$res[2] ) {
				Header('status: 401 Unauthorized'); // For Roxen cgi-wrapper
				Header('HTTP/1.0 401 Unauthorized');
				Header('WWW-authenticate: basic realm="Geschützter Bereich"');
				Header("KiSS2: $_SERVER[PHP_AUTH_USER], $_SERVER[PHP_AUTH_PW]");
				PUtil::showError();
				exit();
			}
		}
	} //login
	// Ende: Benutzerauthentifizierung ///////////////////////////////////////////////////////////


	// Konfiguration der NewsListe ermitteln
	function set_kuInfos($dbID, $ti_id) {
		$m_home = preg_replace( "/\/home\/(.*?)\/.*/i", "\\1", $_SERVER['DOCUMENT_ROOT'] );	//home-verzeichnis ermitteln (=$PA_home falls von Admin-Tool aufgerufen)
		$sql="SELECT a.ku_id, ti_id, ti_beze, ku_version, ku_varNews, ku_varLineBreak, ti_templ, ti_ord,
				ku_varfont, ku_fonttitle, ku_anztage, ku_pfad, ti_css, ku_www, ku_datenpfad, listType, showDate,
				titleStyle, ku_linkStyle, ku_home, ku_start, ti_oldMessages, linkProperties, ti_countBR, tmb_width, tmb_height, teaserLength
				FROM kunde a, titel b WHERE a.ku_id=b.ku_id AND ku_home='$m_home' AND ti_id='$ti_id'";
		if ( $sql = @mysql_query($sql) ) {
			$this->ident=mysql_fetch_array ($sql);		// in Variable $ident Kundennummer und Thema schreiben
			mysql_free_result ($sql);					// Variable $result wieder freigeben
			return TRUE;
		} else return FALSE;
	}
	// set_kuInfos


	// init template (load template blocks)
	function loadTemplate($level) {

		if( file_exists($_SERVER['DOCUMENT_ROOT'].'/templates/') ) $dwt_path=$_SERVER['DOCUMENT_ROOT'].'/templates';
			elseif ( file_exists($_SERVER['DOCUMENT_ROOT'].'/Templates/') ) $dwt_path=$_SERVER['DOCUMENT_ROOT'].'/Templates';
				else die('Templatepfad wurde nicht gefunden!!');

		//Template einlesen
		if ( $this->ident['ti_templ'] ) $dwt=$dwt_path.'/'.$this->ident['ti_templ'];
		if ( !file_exists($dwt) ) $dwt=$this->ident['ti_templ'];			// test if template exists in relative path
		if ( !file_exists($dwt) || $dwt=='' )
			$dwt = $dwt_path.'/mail_upd.dwt';									// wenn bisher kein Template gefunden, Standard-Template benutzen

		if ( !file_exists($dwt) ) die('Template konnte leider nicht gefunden werden!!<br><br>'.$this->errorMsg);

		if ( !empty($_REQUEST['print']) ) $template=new PTemplate('', $dwt, 1, FALSE);
			else $template=new PTemplate('', $dwt, 0, FALSE);
		$template->removeDir();

		if ( empty($_SERVER['HTTP_REFERER']) ) $this->backLink='javascript:history.back();';
			else $this->backLink=$_SERVER['HTTP_REFERER'];

		// if different layouts for newslist levels -> discard wrong design blocks
		$tmpl=FALSE;
		for ($i=10;$i>=0;$i--) {
			if ( $template->block_exists('MAILUPDATE_ebene_'.$i) ) {
				if ( ($i > $level) || $tmpl ) $template->extractBlock('MAILUPDATE_ebene_'.$i);
					else $tmpl=TRUE;
			}
		}

		$this->tpl_firstArticle=$template->extractBlock('DESIGN_Artikel_1');
		$this->tpl_secondArticle=$template->extractBlock('DESIGN_Artikel_2');
		$this->tpl_backBlock=$template->extractBlock('backBlock');
		$this->template=$template;
	}
	// loadTemplate

	// Template initialisieren und ausgeben
	function procTemplate($cNews=1) {
		// if there is no news to show -> extract 'mail_update'-block if it exists
		$tpl_noNews=$this->template->extractBlock('DESIGN_no_News');
		if ( empty($cNews) ) {
			if ( !$tpl_noNews ) $this->template->addComponent($this->ident['ku_varNews'], 'Es sind zur Zeit keine Artikel vorhanden!');
				else $this->template->addComponent('DESIGN_no_News', $tpl_noNews);
			$mail_update=$this->template->extractBlock('mail_update');
		}

		// falls vorhanden: $this->image bei Platzhalter 'Bild' einfügen (0 - kein Bild, 1 - Bild)
		if ( !empty($this->image) ) {
			$this->template->extractBlock("Bild0");
			$this->template->addComponent("bild", $this->image);
		} elseif ( !$this->template->extractBlock("Bild1") ) {
			$this->template->extractBlock("Bild0");
			$this->template->addComponent("bild", "Kein Bild vorhanden!");
		}

		// if a designBlocks for news messages exists wrap this block around messages
		if ( $articleBlock=$this->template->extractBlock('articleBlock') ) {
			foreach ( $this->news as $key=>$art ) {
				$articleBlock->parse();
				$articleBlock->addComponent('inhalt', $this->news[$key]);
				$this->news[$key]=$articleBlock->outputStr();
			}
		}
		if ( ($articleBlock2=$this->template->extractBlock('articleBlock2')) || ($articleBlock2=$articleBlock) ) {
			foreach ( $this->news2 as $key=>$art ) {
				$articleBlock2->parse();
				$articleBlock2->addComponent('inhalt', $this->news2[$key]);
				$this->news2[$key]=$articleBlock2->outputStr();
			}
		}
		// End: designBlocks

		// add link to complete article, if it exists with design from the template block 'linkBlock'
		$linkBlock=$this->template->extractBlock('linkBlock');

		// list older messages ( if other design than first message, array:news2)
		if ( count($this->news2) > 0 ) {
			if ( trim($this->ident['ti_oldMessages'])!='' )
				$this->template->addComponent('alt', $this->ident['ku_varfont'].'<b>'.$this->ident['ti_oldMessages']."</b></font><br><br>");
			if ( !empty($linkBlock) && @is_object($linkBlock) ) {
				foreach ( $this->news2 as $key=>$value ) {
					$linkBlock->parse();
					$searchStr=preg_quote('<!-- #BeginTitle -->')."(.*?)".preg_quote('<!-- #EndTitle -->');
					preg_match("/$searchStr/is", $value, $match);
					if ( !empty($match[1]) ) {
						$linkBlock->addComponent('link', $match[1]);
						$this->news2[$key]=preg_replace("/$searchStr/is", $linkBlock->outputStr(), $value);
					}
				}
			}
			$this->news2=join( str_repeat('<br>',$this->ident['ti_countBR']), $this->news2 );
			if ( $this->tpl_secondArticle )  $this->template->addComponent('DESIGN_Artikel_2', $this->news2);
				else $this->template->addComponent('alt', $this->news2);
		} else $this->template->extractBlock('additionalBlock');		// delete optional html-block if only one article

		// add back-link, if it exists  with design from the template block 'backBlock'
		if ( $this->tpl_backBlock && trim($this->back)!='' ) {
			$this->back=str_replace('<br>', '', $this->back);
			$this->tpl_backBlock->addComponent('back', $this->back);					// add complete back link (old version)
			$this->tpl_backBlock->addComponent('link', $this->backLink);			// add only the target of the backlink (rest is defined in template)
			$this->template->addComponent('backBlock', $this->tpl_backBlock);
		}

		if ( count($this->news) > 0 ) $this->news=join( str_repeat('<br>',$this->ident['ti_countBR']), $this->news ); else $this->news='';
		if ( $this->tpl_firstArticle ) $this->template->addComponent('DESIGN_Artikel_1', $this->news);
			else $this->template->addComponent($this->ident['ku_varNews'], $this->news);
		$this->template->addComponent('back', $this->back);
		$this->template->addComponent('errorMsg', $this->errorMsg);
		$this->template->addComponent('druckLink', $_SERVER['REQUEST_URI'].'&print=1');
		$this->template->deactivateBlockComments();	// remove editable-block comments (because of errors when using virtual includes)

		$output=preg_replace("/((src)|(href)|(background)|(action))=\"([^\.\/])/is", "\\1=\"../\\6", $this->template->outputStr());
		$output=str_replace('../http://', 'http://', $output);
		$output=str_replace('../javascript:', 'javascript:', $output);
		//$output=str_replace('href="//', 'href="/', $output);

              // remove spamfilter comments
              $output = trim( str_replace('***SPAM***', '', $output) );

		echo $output;
		//echo $this->template->outputStr();	// fertige Website ausgeben
	}
	// procTemplate

	// Selectieren der aktuellen Artikel (wenn keine Starseite)
	function getNewsList($dbID, $in_id = '', $flag = 0) {
		global $PA_adm;
		$array=array();

		if ( $in_id!='' ) {
			if ($flag) $in_query="in_id='$in_id' AND ";			//falls spezieller Artikel gesucht
				elseif (!$PA_adm) $in_query="in_id<>'$in_id' AND ";	//falls alles ausser speziellen Artikel gesucht
					else $in_query="0 AND ";
		} else $in_query="";

		if ( empty($ident['ti_ord']) ) $ident['ti_ord'] = 'dat' ;	//falls keine Reihenfolge

		$sql="SELECT in_code, in_titel, in_zeig, DATE_FORMAT(in_date, '%d.%m.%Y') AS date, in_tage, in_id, ti_id,
				(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(in_date)) AS dat, derived FROM inhalt
				WHERE $in_query ku_id=".$this->ident['ku_id']." AND ti_id=".$this->ident['ti_id']."
				AND in_zeig='X' AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(in_update) - (in_tage*86400)) < 0 
				ORDER BY ".$this->ident['ti_ord'].", dat"; // Themen zur angeforderten Seite holen

		// wenn aus Admin-Tool gelinkt, Vorschau anzeigen
		if ( $PA_adm && $flag )
			$sql="SELECT in_code, in_titel, in_zeig, DATE_FORMAT(in_date, '%d.%m.%Y') AS date, in_tage, in_id, ti_id,
				(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(in_date)) AS dat, derived FROM inhalt
				WHERE in_id='$in_id' AND ku_id=".$this->ident['ku_id']." AND ti_id=".$this->ident['ti_id'];

		$sql = @mysql_query ($sql);
		while ($res=@mysql_fetch_array($sql)) {
			$res['in_code']=str_replace('§', '$', $res['in_code']);

			// convert unicode to ascii
			$res['in_code']=@mb_convert_encoding($res['in_code'], 'ISO-8859-1', 'utf-8, ISO-8859-1');
			$res['in_titel']=@mb_convert_encoding($res['in_titel'], 'ISO-8859-1', 'utf-8, ISO-8859-1');

			// Umlaute ersetzen
			$res['in_code']=PUtil::replace_uml($res['in_code'], 0);
			$res['in_titel']=PUtil::replace_uml($res['in_titel'], 0);

			$array[]=$res;
		}
		return ($array);
	}
	// getNewsList
	
	// Selectieren der aktuellen Artikel für Startseite
	function getStartList($dbID, $in_id = '') {
		$array=array();

		if ($this->ident['ti_ord'] == '') $this->ident['ti_ord'] = 'dat' ;	//falls keine Reihenfolge

		// falls Artikel nur aus bestimmten Mailinglisten auf der Startseite angezeigt werden sollen
		// Funktion überprüfen (R.Kropp)
		if ( trim($this->ident['ku_varNews'])>0 && !strstr($this->ident['ku_varNews'], '|') ) {
			$sql="SELECT ti_id, ti_beze FROM titel WHERE ku_id='".$this->ident['ku_id']."'";
			$sql=@mysql_query($sql);
			while ( $res=@mysql_fetch_row($sql) ) {
				$sql2="SELECT in_code, in_titel, in_zeig, DATE_FORMAT(in_date, '%d.%m.%Y') AS date, in_tage, in_id, I.ti_id,
					(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(in_date)) AS dat, linkProperties, UNIX_TIMESTAMP(in_date) AS unix FROM inhalt I, titel T
					WHERE T.ti_id='$res[0]' AND I.ti_id=T.ti_id AND I.ku_id=T.ku_id AND in_teaser='1' AND I.ku_id=".$this->ident['ku_id']."
					AND in_zeig='X' AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(in_update) - (in_tage*86400)) < 0
					ORDER BY ".$this->ident['ti_ord'].", dat
					LIMIT 0,".trim($this->ident['ku_varNews']); // Themen zur angeforderten Seite holen
				$sql2 = @mysql_query($sql2);
				$i=0;
				while ( $res2=@mysql_fetch_array($sql2) ) {
					if ( $i==0 ) {
						$res2['in_titel']="<b><a href=\"/mail_update/news.php?id=$res[0]\">$res[1]</a></b><br><br>$res2[in_titel]";
						$i++;
					}
					$res2['in_code']=str_replace("§", "$", $res2['in_code']);

					// convert unicode to ascii
					$res2['in_code']=@mb_convert_encoding($res2['in_code'], 'ISO-8859-1', 'utf-8, ISO-8859-1');
					$res2['in_titel']=@mb_convert_encoding($res2['in_titel'], 'ISO-8859-1', 'utf-8, ISO-8859-1');

					// Umlaute ersetzen
					$res2['in_code']=PUtil::replace_uml($res2['in_code'], 0);
					$res2['in_titel']=PUtil::replace_uml($res2['in_titel'], 0);

					// falls nicht ifabrik newslist on prosoft intranet show artikel on startpage
					if ( !strstr($res2['in_titel'], "i-fabrik News") ) $array[]=$res2;
				}
			}
		} else {
			if ( trim($this->ident['ku_varNews'])=='$' ) $query="AND in_code like '%$%'";
				elseif ( strstr($this->ident['ku_varNews'], "|") ) $query="AND T.ti_id in (".str_replace( '|', ',', trim($this->ident['ku_varNews']) ).")";
					else $query='';

			$sql="SELECT in_code, in_titel, in_zeig, DATE_FORMAT(in_date, '%d.%m.%Y') AS date, in_tage, in_id, I.ti_id,
					(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(in_date)) AS dat, linkProperties FROM inhalt I, titel T
					WHERE I.ti_id=T.ti_id AND I.ku_id=T.ku_id AND in_teaser='1' AND I.ku_id=".$this->ident['ku_id']."
					AND in_zeig='X' AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(in_update) - (in_tage*86400)) < 0  $query
					ORDER BY ".$this->ident['ti_ord'].", dat"; // Themen zur angeforderten Seite holen
			$sql = @mysql_query($sql);
			while ($res=@mysql_fetch_array($sql)) {
				$res['in_code']=str_replace('§', '$', $res['in_code']);

				// convert unicode to ascii
				$res['in_code']=@mb_convert_encoding($res['in_code'], 'ISO-8859-1', 'utf-8, ISO-8859-1');
				$res['in_titel']=@mb_convert_encoding($res['in_titel'], 'ISO-8859-1', 'utf-8, ISO-8859-1');

				// Umlaute ersetzen
				$res['in_code']=PUtil::replace_uml($res['in_code'], 0);
				$res['in_titel']=PUtil::replace_uml($res['in_titel'], 0);

				$array[]=$res;
			}
		}
		return ($array);
	}
	// getStartList

	// Link-Synonyme parsen
	function loadLink($link) {
		// link[0] - Website
		// link[1] - Pfad+Dokument
		// link[2] - Parameter
		// link[3] - Anzuzeigender Text/Bild
		$link = preg_replace("/%link\[|\]/is", '', $link);

		//Synonyme im link ersetzen
		$sql = "SELECT syn_kurz, syn_lang FROM synonym WHERE ku_id=".$this->ident['ku_id'];
		$sql = @mysql_query($sql);
		while ($res = @mysql_fetch_row($sql))
			$link = ereg_replace ($res[0], $res[1], $link);

		$link = explode('|', $link) ;							// String anhand Trennzeichen in Array zerlegen
		array_walk($link, 'ifab_trim');

		$link[0]=preg_replace("/^.*?\"(.*?)\".*/s", "\\1", $link[0]);
		$link[0]=trim($link[0]);

		if ( $link[0] != '' ) {
			//Parameter für ein Script an link anhängen
			if (trim($link[2]) != '') {
				$link[2] = explode(',', trim($link[2]));
				array_walk($link[2], 'ifab_trim');
				$link[2]='?'.join('&', $link[2]);
				$link[2] =str_replace ('-', '=', $link[2]);
			}

			// Text/Bild für Link
			$link[3]=preg_replace("/(<img.*?)>/is", "\\1 border=\"0\">", $link[3]);

			// Link zusammen bauen
			$addr = "$link[0]$link[1]$link[2]";
			$addr = str_replace(' ', '', $addr);			//überflüssige Leerzeichen entfernen
			$link = "<a href=\"$addr\" ".$this->ident['linkProperties'].">$link[3]</a>" ;

			switch ($this->ident['ku_linkStyle']):
			case 'fett':
				$link="<b>$link</b>";
				break;
			case 'kursiv':
				$link="<i>$link</i>";
				break;
			default:
				break;
			endswitch;
		}else $link = '' ;
		return $link;
	}
	// loadLink

	// Preis eines Artikels aus Datenbank extrahieren und in Artikel einfügen
	function loadPrice( $ArtNr ) {
		//@mysql_close();
		$dbID=@mysql_connect('localhost', $_SERVER['PSUser'], $_SERVER['PSPass'], TRUE);
		$sql="SELECT Preis FROM Artikel WHERE ArtNr='$ArtNr'";
		$sql=@mysql_db_query('Kaufhaus', $sql, $dbID);
		$preis=@mysql_result($sql,0,0);

		@mysql_close($dbID);
		//@mysql_connect( "localhost", $_SERVER['MUPDUser'], $_SERVER['MUPDPass'] );
		//@mysql_select_db("mail_update");

		return $preis;
	}
	// loadPrice


	// output_img
	function output_img($img, $tmb_width, $tmb_height) {
		$img=$_SERVER['DOCUMENT_ROOT'].trim(urldecode($img));
		$info=getimagesize($img);
		$new_width=$info[0];
		$new_height=$info[1];

		if ( $info[0] > $tmb_width ) {
			$new_width=$tmb_width;
			$new_height=$info[1] / $info[0] * $tmb_width;
		}
		if ( $new_height > $tmb_height ) {
			$new_width=$info[0] / $info[1] * $tmb_height;
			$new_height=$tmb_height;
		}

		switch ($info[2]):
			case 1:	$src=imagecreatefromgif($img);	break;
			case 2:	$src=imagecreatefromjpeg($img);	break;
			case 3:	$src=imagecreatefrompng($img);	break;
			default:	die('Fehler: Der Bildtyp konnte nicht bestimmt werden!'); break;
		endswitch;

		$dst=imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $info[0], $info[1]);

		header('Content-type: '.$info['mime']);
		imagejpeg($dst);
		exit(0);
	}
	// output_img

	//////////////////////////////////////
	//	showArtikel - Funktionen		//
	//////////////////////////////////////

	//////////////////////////////////
	// zeigt einen Artikel komplett	//
	function complete($art, $countNews=1) {
		// Tags löschen um Quelltext zu vereinfachen
		$art['in_code'] = preg_replace("/<span.*?>|<\/span>|<o:p>|<\/o:p>|<\?.*?>/is", '', $art['in_code']);
		$art['in_code'] = preg_replace("/<p class=[^> ]*/is", '<p ', $art['in_code']);
		$art['in_code'] = preg_replace("/<!\[.*?\]>/is", '', $art['in_code']);
		$art['in_code'] = PUtil::preg_replace_div($art['in_code']);

		// Ersetzungen für alte Mails:
		$art['in_code'] = preg_replace("/<img src=\"..\/image.php|<img src=\"image.php/is", "<img src=\"/mail_update/image.php", $art['in_code']);
		$art['in_code'] = preg_replace("/<font.*?>|<\/font>|<html>|<\/html>|<body.*?>|<\/body>/is", '', $art['in_code']);

		// Links parsen
		$art['in_code'] = preg_replace("/%link\[.*?\]/ise", "\$this->loadLink(\"\\0\");", $art['in_code']);
		//Preis aus DB ermitteln
		$art['in_code'] = preg_replace("/%preis_prosoft\[([0-9]+)\]/ise", "\$this->loadPrice(\"\\1\");", $art['in_code']);

		$art['in_code'] = str_replace('$', '', $art['in_code']);
		$art['in_code'] = trim($art['in_code']);

		// falls newlines durch <br>-tags ersetzt werden sollen und noch keine <br>-tags vorhanden sind
		if ( $this->ident['ku_varLineBreak']=='X' && !strpos($art['in_code'], '<br>') ) $art['in_code']=nl2br($art['in_code']);

		// get title
		$compl=$this->titel($art, 0);

		// bei Startseiten-Funktion Link zur gewählten news-liste bauen
		if ( $this->ident['ku_start'] && $this->ident['ti_beze']=="Startseiten-Funktion" && $countNews > 1 ) {
			global $id2;
			if (!$id2) $ti_id=$art['ti_id'];else $ti_id=$id2;
			$compl = "<a href=\"$_SERVER[PHP_SELF]?id=$ti_id\">$compl</a>";
		}

		// set font-tag, if not on startpage (no virtual include)
		if ( (!$this->ident['ku_start'] || $this->ident['ti_beze']!='Startseiten-Funktion') && !empty($this->ident['ku_varfont']) ) {
			$art['in_code'] = preg_replace("/<td(.*?)>/is", "<td\\1>".$this->ident['ku_varfont'], $art['in_code']);
			$art['in_code'] = $this->ident['ku_varfont'].$art['in_code'].'</font>';
		}

		$compl.='<br>'.$art['in_code'];
		return($compl);
	}
	// complete


	//////////////////////////////////////
	// zeigt den Teaser eines Artikels	//
	function teaser($art) {	//($login,$ident,$them, $addr)
		// falls kein $-Zeichen vorhanden, nur Titel anzeigen
		if ( strpos($art['in_code'], '$')===FALSE ) return $this->titel($art);

		// Tags löschen um Quelltext zu vereinfachen
		$art['in_code'] = preg_replace("/<span.*?>|<\/span>|<o:p>|<\/o:p>|<\?.*?>/is", "", $art['in_code']);
		$art['in_code'] = preg_replace("/<p class.*?>/is", "<p>", $art['in_code']);
		$art['in_code'] = PUtil::preg_replace_div($art['in_code']);

		// Ersetzungen für alte Mails:
		$art['in_code'] = preg_replace("/<img src=\"..\/image.php|<img src=\"image.php/si", "<img src=\"/mail_update/image.php", $art['in_code']);
		$art['in_code'] = preg_replace("/<font.*?>|<\/font>|<html>|<\/html>|<body.*?>|<\/body>|<div.*?>|<\/div>/is", "", $art['in_code']);
		// Ersetzungen, da Teaser
		$art['in_code'] = preg_replace("/<li>|<\/li>|<ul>|<\/ul>|<div.*?>|<\/div>/is", '', $art['in_code']);
 
		// wenn Mail als Tabelle aufgebaut ist, erste Zelle als Teaser
//zu testen: 2x '\' vor dem $-symbol
		if (preg_match("/^[^\$]*<table/si", $art['in_code']))
			$art['in_code']=preg_replace("/^.*?<td.*?>(.*?)<\/td>.*?$/si", "\\1", $art['in_code']);

		// nur Text bis $
		$art['in_code'] = substr($art['in_code'], 0,strpos($art['in_code'],'$'));

		// Link zu vollständigem Artikel (oder zur level0-Newsliste, falls Startseite)
		if ( $this->ident['ku_start'] && $this->ident['ti_beze']=="Startseiten-Funktion" )
					$art['in_code'] .= " <nobr><a href=\"$_SERVER[PHP_SELF]?id=$art[ti_id]&in_id=$art[in_id]\" ".$this->ident['linkProperties'].">... (mehr)</a></nobr>";
			else $art['in_code'] .= " <nobr><a href=\"$_SERVER[PHP_SELF]?id=$art[ti_id]&in_id=$art[in_id]&level=".($this->nextLevel)."\" ".$this->ident['linkProperties'].">... (mehr)</a></nobr>" ;

		$art['in_code']=preg_replace("/%link\[.*?\]/ise", "\$this->loadLink(\"\\0\");", $art['in_code']);
		$art['in_code'] = trim($art['in_code']); 

		// set font-tag, if not on startpage (no virtual include)
		if ( !($this->ident['ku_start'] && $this->ident['ti_beze']=="Startseiten-Funktion") && trim($this->ident['ku_varfont'])!="" )
		{
			$art['in_code'] = preg_replace("/<td(.*?)>/is", "<td\\1>".$this->ident['ku_varfont'], $art['in_code']);   
			$art['in_code'] = $this->ident['ku_varfont']."$art[in_code]</font>";
		}

		$teaser=$this->titel($art, 0)."<br>$art[in_code]\n<!-- endMail -->";
		return($teaser);
	}
	// teaser

	//////////////////////////////////////
	// zeigt den Titel eines Artikels	//
	function titel($art, $link=1) {
		$title=$art['in_titel'];

		// modify font style of the title
		if ( $this->ident['titleStyle'] & 1 ) $title="<b>$title</b>";							//falls gewünscht, Titel der Mail fett anzeigen
		if ( $this->ident['titleStyle'] & 2 ) $title="<i>$title</i>";							//falls gewünscht, Titel der Mail kursiv anzeigen
		if ( $this->ident['titleStyle'] & 4 ) $title="<u>$title</u>";							//falls gewünscht, Titel der Mail unterstrichen anzeigen

		// show date in title, if $this->ident['showDate'] is positive 
		if ($this->ident['showDate']>=0) {
			$tmpdate=" (vom $art[date])";
			if ($this->ident['showDate'] & 8) $tmpdate=' <nobr>'.trim($tmpdate).'</nobr>';//falls gewünscht, kein Zeilenumbruch im Eingangsdatum der Mail
			if ($this->ident['showDate'] & 1) $tmpdate="<b>$tmpdate</b>";						//falls gewünscht, Eingangsdatum der Mail fett anzeigen
			if ($this->ident['showDate'] & 2) $tmpdate="<i>$tmpdate</i>";						//falls gewünscht, Eingangsdatum der Mail kursiv anzeigen
			if ($this->ident['showDate'] & 4) $tmpdate="<u>$tmpdate</u>";						//falls gewünscht, Eingangsdatum der Mail unterstrichen anzeigen
			$title.=$tmpdate;
		}


		// Link zu vollständigem Artikel (oder zur level0-Newsliste, falls Startseite; kein Link falls Titel eines kompletten Artikels)
		if ($link) {
			if ( $this->ident['ku_start'] && $this->ident['ti_beze']=="Startseiten-Funktion" ) {
				$title="<!-- #BeginTitle --><a href=\"$_SERVER[PHP_SELF]?id=$art[ti_id]&in_id=$art[in_id]\" ".$this->ident['linkProperties'].">$title</a><!-- #EndTitle -->";
			} else $title="<!-- #BeginTitle --><a href=\"$_SERVER[PHP_SELF]?id=$art[ti_id]&in_id=$art[in_id]&level=".($this->nextLevel).'" '.$this->ident['linkProperties'].">$title</a><!-- #EndTitle -->";
		}

		// set font-tag of the title, if not on startpage (no virtual include)
		if ( (!$this->ident['ku_start'] || $this->ident['ti_beze']!='Startseiten-Funktion') && empty($_GET['virtual']) && !empty($this->ident['ku_fonttitle']) )
			$title=$this->ident['ku_fonttitle'].$title.'</font>';

		if ( !($this->ident['titleStyle'] & 8) )	$title.='<br>';								//falls gewünscht, Zeilenumbruch nach Titel der Mail (standard ist Zeilenumbruch an)
		if ( $this->ident['titleStyle'] & 16 )		$title="<nobr>$title</nobr>";				//falls gewünscht, kein Zeilenumbruch im kompletten Titel der Mail

		return($title);
	}
	// titel

	//////////////////////////////////////////////////
	// zeigt den Rest (ohne Teaser) eines Artikels	//
	function oTeaser($art) {
		$pos=strpos($art['in_code'], '$');

		// falls kein $-Zeichen vorhanden, alles anzeigen
		if ( $pos === FALSE ) return  $this->complete($art);

		// wenn Mail als Tabelle aufgebaut ist: Tabellen komplett entfernen
		if ( $pos > strpos($art['in_code'], '<table') ) {
			$tmp=explode('$', $art['in_code']);
			$art['in_code']=preg_replace("/<\/td>|<\/tr>|<\/table>|<table.*?>|<tr.*?>|<td.*?>/is", '', $tmp[1]);
		} else $art['in_code'] = substr($art['in_code'], $pos+1);		//sonst Text ab $

		
		// Tags löschen um Quelltext zu vereinfachen
		$art['in_code'] = preg_replace("/<span.*?>|<\/span>|<o:p>|<\/o:p>|<\?.*?>/is", '', $art['in_code']);
		$art['in_code'] = preg_replace("/<p class.*?>/is", '<p>', $art['in_code']);
		$art['in_code'] = PUtil::preg_replace_div($art['in_code']);	// div-tags vereinfachen

		// Ersetzungen für alte Mails:
		$art['in_code'] = preg_replace("/<img src=\"..\/image.php|<img src=\"image.php/is", "<img src=\"/mail_update/image.php", $art['in_code']);
		$art['in_code'] = preg_replace("/<font.*?>|<\/font>|<html>|<\/html>|<body.*?>|<\/body>|<div.*?>|<\/div>/is", "", $art['in_code']);

		$art['in_code']=preg_replace("/%link\[.*?\]/ise", "\$this->loadLink(\"\\0\");", $art['in_code']);
		$art['in_code'] = trim($art['in_code']); 

		// set font-tag, if not on startpage (no virtual include)
		if ( !($this->ident['ku_start'] && $this->ident['ti_beze']=='Startseiten-Funktion') && trim($this->ident['ku_varfont'])!='' ) {
			$art['in_code'] = preg_replace("/<td(.*?)>/is", "<td\\1>".$this->ident['ku_varfont'], $art['in_code']);
			$art['in_code'] = $this->ident['ku_varfont']."$art[in_code]</font>";
		}

		$oTeaser=$this->titel($art, 0).'<br>'.$art['in_code'];
		return $oTeaser;
	}
	// oTeaser


	//////////////////////////////////////////////////////////
	// html-code zur Anzeige des ersten angehängten Bildes	//
	function getAttachment( $art, $thumb=0 ) {
		$art=preg_replace( "/^<!--X-Derived:\s*?(\/mail_update\/res\/)?([^\/]*?)-->.*/si", "\\2", trim($art['derived']) );
		$art='/mail_update/res/'.trim($art);

		if ( !($size=@getimagesize($_SERVER['DOCUMENT_ROOT'].$art)) ) {
			if ( $size=@getimagesize($_SERVER['DOCUMENT_ROOT'].'/img/default.jpg') ) $art='/img/default.jpg';
				elseif ( $size=@getimagesize($_SERVER['DOCUMENT_ROOT'].'/image/default.jpg') ) $art='/image/default.jpg';
					else return FALSE;
		}
		if ( $thumb==1 && $this->ident['tmb_width']>0 && $this->ident['tmb_height']>0 ) $art='<img src="'.$_SERVER['PHP_SELF'].'?id='.$_REQUEST['id'].'&img='.urlencode($art).'">';
			else $art='<img src="'.$art.'" '.$size3.'>';
		return $art;
	}
	// getAttachmant


	//////////////////////////////////////////////////////////
	// zeigt einen Artikel komplett, ohne Attachments		//
	function compl_oAttach($art) {
		// Tags löschen um Quelltext zu vereinfachen
		$art['in_code'] = preg_replace("/<span.*?>|<\/span>|<o:p>|<\/o:p>|<\?.*?>/is", "", $art['in_code']);
		$art['in_code'] = preg_replace("/<p class.*?>/is", "<p>", $art['in_code']);
		$art['in_code'] = PUtil::preg_replace_div($art['in_code']);	// div-tags vereinfachen

		// Ersetzungen für alte Mails:
		$art['in_code'] = preg_replace("/<img src=\"..\/image.php|<img src=\"image.php/is", "<img src=\"/mail_update/image.php", $art['in_code']);
		$art['in_code'] = preg_replace("/<font.*?>|<\/font>|<html>|<\/html>|<body.*?>|<\/body>/is", "", $art['in_code']);

		// Links parsen
		$art['in_code'] = preg_replace("/%link\[.*?\]/ise", "\$this->loadLink(\"\\0\");", $art['in_code']);
		//Preis aus DB ermitteln
		$art['in_code'] = preg_replace("/%preis_prosoft\[([0-9]+)\]/ise", "\$this->loadPrice(\"\\1\");", $art['in_code']);

		$art['in_code'] = str_replace('$', '', $art['in_code']) ;
		$art['in_code'] = trim($art['in_code']);

		// Attachments entfernen
		$art['in_code'] = preg_replace("/(.*)<!-- Attachments -->.*/si", "\\1", $art['in_code']);
		if ( !empty($this->image) )		// remove images if atachment found (atachments not marked when sent with mozilla)
			$art['in_code'] = preg_replace("/<p><a href=[^>]+><img src=[^>]+><\/a><\/p>/is", '', $art['in_code']);

		$compl=$this->titel($art, 0);

		// bei Startseiten-Funktion Link zur gewählten news-liste bauen
		if ( $this->ident['ku_start'] && $this->ident['ti_beze']=='Startseiten-Funktion' ) {
			global $id2;
			if (!$id2) $id2=$art['ti_id'];
			$compl="<a href=\"$_SERVER[PHP_SELF]?id=$id2\">$compl</a>";
		}

		// set font-tag, if not on startpage (no virtual include)
		if ( !($this->ident['ku_start'] && $this->ident['ti_beze']=='Startseiten-Funktion') && trim($this->ident['ku_varfont'])!='' ) {
			$art['in_code'] = preg_replace("/<td(.*?)>/is", "<td\\1>".$this->ident['ku_varfont'], $art['in_code']);
			$art['in_code'] = $this->ident['ku_varfont']."$art[in_code]</font>";
		}

		$compl.="<br>$art[in_code]";
		return($compl);
	}
	// compl_oAttach


	//////////////////////////////////////
	//	NewsList - Funktion					//
	////////////////////////////////////////////////////////////////////////////////
	//	erzeugt aus der Datenbank-Liste die richtige Darstellung für die Website	//
	////////////////////////////////////////////////////////////////////////////////
	function genOutput($newsList) {

		$this->news=array();
		$this->news2=array();

		switch ($this->ident['listType']):
		//!!0	alle Artikel: komplett 
		case 0:
			//$this->news.=$this->complete(array_shift($newsList));
			foreach($newsList as $art)	$this->news[]=$this->complete($art);
			break;

		//!!1	alle Artikel: Teaser
		case 1:
			//$this->news.=$this->teaser(array_shift($newsList));
			foreach ($newsList as $art)	$this->news[]=$this->teaser($art);
			break;

		//!!2	alle Artikel: nur Titel
		case 2:
			//$this->news.=$this->titel(array_shift($newsList));
			foreach ($newsList as $art)	$this->news[]=$this->titel($art);
			break;

		//!!3	alle Artikel: ohne Teaser
		case 3:
			//$this->news.=$this->teaser(array_shift($newsList));
			foreach ($newsList as $art)	$this->news[]=$this->oTeaser($art);
			break;

		//!!4	ein Artikel: komplett, Rest: Teaser
		case 4:
			$this->news[]=$this->complete(array_shift($newsList));
			foreach ($newsList as $art) $this->news2[]=$this->teaser($art);
			break;

		//!!5	ein Artikel: komplett, Rest: nur Titel
		case 5:
			$this->news[]=$this->complete(array_shift($newsList));
			foreach ($newsList as $art)	$this->news2[]=$this->titel($art);
			break;

		//!!6	ein Artikel: komplett, Rest: ohne Teaser
		case 6:
			$this->news[].=$this->complete(array_shift($newsList));
			foreach ($newsList as $art)	$this->news2[].=$this->oTeaser($art);
			break;

		//!!7	ein Artikel: Teaser, Rest: Titel
		case 7:
			$this->news[]=$this->teaser(array_shift($newsList));
			foreach ($newsList as $art)	$this->news2[]=$this->titel($art);
			break;

		//!!8	ein Artikel: ohne Teaser, Rest: Teaser
		case 8:
			$this->news[]=$this->oTeaser(array_shift($newsList));
			foreach ($newsList as $art)	$this->news2[]=$this->teaser($art);
			break;

		//!!9	ein Artikel: ohne Teaser, Rest: Titel
		case 9:
			$this->news[]=$this->oTeaser(array_shift($newsList));
			foreach ($newsList as $art)	$this->news2[]=$this->titel($art);
			break;
	
		//!!10	ein Artikel: komplett
		case 10:
			$this->news[]=$this->complete($newsList[0], @count($newsList));
			break;

		//!!11	ein Artikel: Teaser
		case 11:
			$this->news[]=$this->teaser($newsList[0]);
			break;

		//!!12	ein Artikel: ohne Teaser
		case 12:
			$this->news[]=$this->oTeaser($newsList[0]);
			break;

		//!!13	ein Artikel: Titel
		case 13:
			$this->news[]=$this->titel($newsList[0]);
			break;

		//!!14	ein Artikel: komplett, Rest: Titel, Bild in Platzhalter
		case 14:
			$this->image=$this->getAttachment($newsList[0]);
			$this->news[]=$this->compl_oAttach(array_shift($newsList));
			foreach ($newsList as $art)	$this->news2[]=$this->titel($art);
			break;

		//!!15	ein Artikel: komplett, Bild in Platzhalter
		case 15:
			$this->image=$this->getAttachment($newsList[0]);
			$this->news[]=$this->compl_oAttach($newsList[0]);
			break;

		//!!16	ein Artikel: Teaser, Bild in Platzhalter
		case 16:
			$this->image[]=$this->getAttachment($newsList[0], 1);
			$this->news[]=$this->teaser($newsList[0]);
			break;

		//!!17	erster Artikel: Teaser, Bild in Platzhalter, Rest: Teaser
		case 17:
			$this->image=$this->getAttachment($newsList[0], 1);
			$this->news[]=$this->teaser( array_shift($newsList) );
			foreach ( $newsList as $art) $this->news2[]=$this->teaser($art);
			break;

		default:
			$this->news[]="Zur Zeit existieren leider keine Artikel!!";
			break;
		endswitch;

	}
	// genOutput



	/////////////////////////////////////////////////////////////////
	//	Neue NewsList - Funktion (Typ durch Template bestimmt)		//
	////////////////////////////////////////////////////////////////////////////////
	//	erzeugt aus der Datenbank-Liste die richtige Darstellung für die Website	//
	////////////////////////////////////////////////////////////////////////////////
	function genOutput_2($newsList) {
		$this->news=array();
		$this->news2=array();

		$firstList=TRUE;
		$article_template=$this->tpl_firstArticle;
		foreach ( $newsList as $article) {
			$article=$this->parse_article($article);
			$article_template->parse();
			$article_template->addComponent('titel', $article['titel']);
			$article_template->addComponent('titel_kurz', $article['titel_kurz']);
			$article_template->addComponent('datum', $article['datum']);
			if ( ($article_template->addComponent('komplett', $article['komplett']))==-1 )
				if ( ($article_template->addComponent('teaser', $article['teaser']))==-1 )
					$article_template->addComponent('ohne_teaser', $article['ohne_teaser']);

			$anhang_templ=$article_template->extractBlock('DESIGN_Anhang');

			if ( (isset($anhang_templ->m_aVars['image_url']) || isset($anhang_templ->m_aVars['image_url_small'])) ) {
			// if array with image urls and field {image_url} exists
				foreach ($article['image_url'] as $url) {
					$anhang_templ->parse();
					$anhang_templ->addComponent('image_url', '/mail_update/res/'.$url);
					$anhang_templ->addComponent('image_url_small', $_SERVER['PHP_SELF'].'?id='.$_REQUEST['id'].'&img=..'.$url);
					$article_template->addComponent('DESIGN_Anhang', $anhang_templ);
					$article_template->extractBlock('DESIGN_ohneAnhang');
				}
			} elseif ( isset($anhang_templ->m_aVars['anhang']) ) {
			// show all attachments and template block 'anhang' exists
				if ( trim($article['anhang'])!='' ) {
					$anhang_templ->addComponent('anhang', $article['anhang']);
					$article_template->addComponent('DESIGN_Anhang', $anhang_templ);
					$article_template->extractBlock('DESIGN_ohneAnhang');
				}
			} elseif ( isset($article_template->m_aVars['anhang']) ) {
			// show all attachments and template block 'anhang' doesn't exists
				if ( trim($article['anhang'])!='' ) {
					$article_template->addComponent('anhang', $article['anhang']);
					if ( @is_object($anhang_templ) ) $article_template->addComponent('DESIGN_Anhang', $anhang_templ);
					$article_template->extractBlock('DESIGN_ohneAnhang');
				} elseif ( !empty($article['image_url']) ) {
					foreach ($article['image_url'] as $url)
						$article_template->addComponent('anhang', array( new PImage('/mail_update/res/'.$url), '<br><br>') );
					if ( @is_object($anhang_templ) ) $article_template->addComponent('DESIGN_Anhang', $anhang_templ);
					$article_template->extractBlock('DESIGN_ohneAnhang');
				}
			} // else: add nothing if no template variables {anhang} or {image_url} exists

			$article_template->addComponent('link', $article['link']);
			if ( $firstList ) {
				$this->news[]=$article_template->outputStr();
				$firstList=FALSE;
			} else $this->news2[]=$article_template->outputStr();
			if ( $this->tpl_secondArticle ) $article_template=$this->tpl_secondArticle; else break;
		}
	}
	// genOutput_2

	function parse_article($article) {
		// Tags löschen um Quelltext zu vereinfachen
		$article['in_code'] = preg_replace("/<span.*?>|<\/span>|<o:p>|<\/o:p>|<\?.*?>/is", '', $article['in_code']);
		$article['in_code'] = preg_replace("/<p class=[^> ]*/is", '<p ', $article['in_code']);
		$article['in_code'] = preg_replace("/<!\[.*?\]>/is", '', $article['in_code']);
		$article['in_code'] = PUtil::preg_replace_div($article['in_code']);
		$article['in_code'] = strip_tags($article['in_code'], '<br><p><a>');
		while ( preg_match("/<br( \/)?>\s*<br( \/)?>\s*<br( \/)?>/is", $article['in_code']) ) $article['in_code'] = preg_replace("/<br( \/)?>\s*<br( \/)?>\s*<br( \/)?>/is", '<br><br>', $article['in_code']);

		// Attachments (with html from mail)
		$output['anhang']='';
		if ($_REQUEST['dbg']) {print_r( $article );exit;}
		if ( ($pos=strpos($article['in_code'], '<!-- Attachments -->'))!==FALSE ) {
			$output['anhang'] = substr($article['in_code'], $pos);
			$output['anhang'] = "\r\n".trim(str_replace('<!-- endMail -->', '', $output['anhang']))."\r\n<!-- endAttachments -->\r\n";
			$article['in_code'] = trim(substr($article['in_code'],0,$pos))."<!-- endMail -->";
		}
		if ( preg_match("/^.*?((<p><a href=[^>]+><img src=[^>]+><\/a><\/p>)+)/is", $article['in_code']) ) {
			$output['anhang'] = preg_replace("/^.*?(<p><a href=[^>]+><img src=[^>]+><\/a><\/p>.*?)$/is", "\\1", $article['in_code']);
			$output['anhang'] = "\r\n<!-- #Attachments -->\r\n".trim(str_replace('<!-- endMail -->', '', $output['anhang']))."\r\n<!-- endAttachments -->\r\n";
			$article['in_code'] = preg_replace("/<p><a href=[^>]+><img src=[^>]+><\/a><\/p>.*?$/is", '', $article['in_code']);
			$article['in_code'] = trim($article['in_code']).'<!-- endMail -->';
		}
		// Attachment (only urls from images)
		$output['image_url']=array();
		preg_match_all("/<!--X-Derived:(.*?res\/)?(.*?)-->/si", trim($article['derived']), $match );
		foreach($match[2] as $attachment)
			if ( exif_imagetype('./res/'.trim($attachment)) && strpos(trim($attachment), $article['in_code'])===FALSE ) $output['image_url'][]=trim($attachment);

		// Links parsen
		$article['in_code'] = preg_replace("/%link\[.*?\]/ise", "\$this->loadLink(\"\\0\");", $article['in_code']);

		// Zeilenumbrueche am anfang und ende wegschneiden
		$article['in_code'] = trim($article['in_code']);

		// falls newlines durch <br>-tags ersetzt werden sollen und noch keine <br>-tags vorhanden sind
		if ( $this->ident['ku_varLineBreak']=='X' && !strpos($article['in_code'], '<br>') ) $article['in_code']=nl2br($article['in_code']);

		// titel
		$output['titel'] = strip_tags($article['in_titel']);
		// begrenzte Titellaenge (Laenge aus DB::teaserLength)
		if ( $this->ident['teaserLength']>0 ) $output['titel_kurz']=strtok( wordwrap($output['titel'], $this->ident['teaserLength'], 'µ'), 'µ');

		// datum
		$output['datum']=$article['date'];

		// link
		if ( $this->ident['ku_start'] && $this->ident['ti_beze']=='Startseiten-Funktion' ) {
			$output['link']="$_SERVER[PHP_SELF]?id=$article[ti_id]&in_id=$article[in_id]";
		} else $output['link']=$_SERVER['PHP_SELF']."?id=$article[ti_id]&in_id=$article[in_id]&level=".($this->nextLevel);

		// teaser
		if ( $this->ident['teaserLength']>0 ) {								// if >0 then cut teaser after max 'teaserLength' chars
			$output['teaser'] = $article['in_code'];
			$output['teaser'] = wordwrap($output['teaser'], $this->ident['teaserLength'], '$');
			$output['teaser'] = substr( $article['in_code'], 0, (int)strpos($output['teaser'],'$') );
		} else {
			$output['teaser'] = substr( $article['in_code'], 0, (int)strpos($article['in_code'],'$') );
			$output['teaser'] = preg_replace("/<li>|<\/li>|<ul>|<\/ul>|<div.*?>|<\/div>/is", '', $output['teaser']);
			// wenn Teaser unvollstaendige Tabellen enthaelt -> erste Zelle als Teaser
			if ( substr_count(strtolower($output['teaser']), '<table') > substr_count(strtolower($output['teaser']), '</table>') )
				$output['teaser'] = preg_replace("/^.*?<td.*?>(.*?)<\/td>.*?$/si", "\\1", $output['teaser']);
		}

		// ohne_teaser
		if ( strpos($article['in_code'], '$') > strpos($article['in_code'], '<table') ) {
		// wenn Mail als Tabelle aufgebaut ist
			$output['ohne_teaser']=strstr("$", $article['in_code']);
			$art['in_code']=preg_replace("/<\/td>|<\/tr>|<\/table>|<table.*?>|<tr.*?>|<td.*?>/is", "", $tmp[1]);
		} elseif ( !($output['ohne_teaser']=strstr($article['in_code'], '$')) ) $output['ohne_teaser']=$article['in_code'];
		$output['ohne_teaser']=str_replace('$', '', $output['ohne_teaser']);

		// komplett
		$output['komplett']=str_replace('$', '', $article['in_code']);

		return $output;
	}
	// parse_article
}
// end:class:PMail

?>