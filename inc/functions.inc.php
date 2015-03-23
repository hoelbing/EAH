<?php
/**
* Postbuch - Universitaet Leipzig
* allgemeine Funktionen
*
* @author Erik Reuter
* @copyright 2007 i-fabrik GmbH
* @version $Id: functions.inc.php,v 1.6 2007/01/30 12:15:39 erik Exp $
*
*/

  // Umrechnen des MySQL-Datum in Europaeisches Format
	function convert_time($timestring="0000-00-00 00:00:00") {

		if ($timestring=="")
			return "";

		if ($timestring=="0000-00-00 00:00:00")
			return "";

		if (defined('DATUMTRENNER'))
			$trennzeichen=DATUMTRENNER;
    else
     $trennzeichen=".";

		$zeittext =substr($timestring,8,2);
		$zeittext.=$trennzeichen;
		$zeittext.=substr($timestring,5,2);
		$zeittext.=$trennzeichen;
		$zeittext.=substr($timestring,0,4);
		if (strlen($timestring)>10) {
			$zeittext.=", ";
			$zeittext.=substr($timestring,11,5);
			$zeittext.=" Uhr";
		}

		return $zeittext;
	}

  // Umrechnung eines Datums in das SQL-Format
  function convert_sqltime($zeit) {

    if ($zeit=="")
      return "";

     preg_match("/([0-9]{1,2})[\.,\/]([0-9]{1,2})[\.,\/]?([0-9]{2,4})?.*?([0-9]{1,2}:[0-9]{2})/",$zeit,$daten);
    if (empty($daten[0]))
       preg_match("/([0-9]{1,2})[\.,\/]([0-9]{1,2})[\.,\/]?([0-9]{2,4})?/",$zeit,$daten);
    // Keine Jahresangaben
     if ($daten[2]=="00") {
      $daten[3]="0000";
    } elseif ($daten[3]=="") {
       $daten[3]=strftime("%Y");
       if (strftime("%Y-%m-%d")>sprintf("%04d-%02d-%02d",$daten[3],$daten[2],$daten[1]))
       $daten[3]++;
     } elseif ($daten[3]<80)
       $daten[3]+=2000;
     elseif ($daten[3]<100)
       $daten[3]+=1900;

     if ($daten[4]=="")
       $daten[4]="00:00";
     $uhrzeit=explode(":",$daten[4]);
     $sqlzeit=sprintf("%04d-%02d-%02d %02d:%02d:00",$daten[3],$daten[2],$daten[1],$uhrzeit[0],$uhrzeit[1]);

     return $sqlzeit;
   }

  /** getMaxTagMonat
   *
   * gibt den letzten tag eines monates zurueck
   *
   * @param   $monat  number
   * @param   $jahr   number
   *
   * @return  string
   */
  function getMaxTagMonat($monat,$jahr) {
      $max_monat = 31;

        while( !checkdate($monat,$max_monat,$jahr) ) $max_monat--;

        return $max_monat;
  }

  // Konvertiert Datum in Text
  //
  //  Typen
  //    kurz     - nur Tag als Abkuerzung
  //    lang_tag - nur Tag ausgeschrieben
  //    lang     - Tag und komplettes Datum mit ausgeschreibenem Monatnamen
  //
  function date2text($tag,$monat,$jahr,$typ='ohne') {

    $tag=floor($tag);
    $monat=floor($monat);
    $jahr=floor($jahr);

    $monatsnamen=array('','Januar','Februar','M&auml;rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember');
    $tagesnamen_lang=array('Sonntag','Montag','Dienstag','Mittwoch','Donnenstag','Freitag','Samstag');
    $tagesnamen_kurz=array('So','Mo','Di','Mi','Do','Fr','Sa');

    $datumtext='';

    if ($typ!='ohne') {
      if (!checkdate($monat,$tag,$jahr))
        return(convert_sprache('* Fehler! Datum ung&uuml;ltig *'));
      $wochentag=strftime('%w',mktime(12,0,0,$monat,$tag,$jahr));
      switch ($typ) {
        case 'kurz':
          return convert_sprache($tagesnamen_kurz[$wochentag]);
          break;
        case 'lang_tag':
          return convert_sprache($tagesnamen_lang[$wochentag]);
          break;
        case 'lang':
          $datumtext=convert_sprache($tagesnamen_lang[$wochentag]).', ';
          break;
      }
    }

    if ($tag>0)
      $datumtext.=$tag.'. ';

    if ($monat>0)
      $datumtext.=convert_sprache($monatsnamen[$monat]).' ';

    if ($jahr>0)
      $datumtext.=$jahr;

    return trim($datumtext);

  }

  // trim ueber alle Eintrage eines Felder
  function trim_array($feld) {

    foreach($feld as $key=>$value) {
      if (is_array($value))
        $feld[$key]=trim_array($value);
      else
        $feld[$key]=trim($value);
    }

    return $feld;
  }

  // Hilfsfunktion, die alle Dateien aus einem Verzeichnis und dessen Unterverzeichnissen einliest
  function list_directory($directory) {

    $dateiliste=array();

    $verzeichnis=opendir($directory);
    while (($dateiname=readdir($verzeichnis))!==FALSE) {
      if (!in_array(strtolower($dateiname),array('.','..','cvs','_cvs'))) {
        if (is_dir($directory.$dateiname))
          $dateiliste=array_merge($dateiliste,list_directory($directory.$dateiname.'/'));
        else
          $dateiliste[]=$directory.$dateiname;
      }
    }

    return $dateiliste;
  }

  // Berechnung der aktuellen Version des Tools
  function calc_version() {

    $lastedit="";
    $version=0;

    $dateiliste=list_directory('./');

    $anzahl=count($dateiliste);
    for ($l=0;$l<$anzahl;$l++) {
      if (preg_match("/.*\.php/",$dateiliste[$l])) {
        $content=file($dateiliste[$l]);
        $content=$content[7];
        preg_match("/$file,v ([0-9]+)\.([0-9]+) ([0-9]{4}\/[0-9]{2}\/[0-9]{2})/i", $content, $match);
        if ($match[3]>$lastedit)
          $lastedit=$match[3];
        $version+=$match[2];
      }
    }

     return (MAINVERSION.".".MINORVERSION." (Build $version/".ANWENDER."/".convert_time($lastedit).")");

  }

  // Umrechnen eines HEX-Farbwertes in ein RGB-Feld
  function hex2rgb($hex) {

    $hex=$hex.$hex;

    $rot=hexdec(substr($hex,0,2));
    $gruen=hexdec(substr($hex,2,2));
    $blau=hexdec(substr($hex,4,2));

    $farbe['rot']=$rot;
    $farbe['gruen']=$gruen;
    $farbe['blau']=$blau;

    return $farbe;

  }

  // Weiterzaehlen einer bestimmten Anzahl Tage
  function add_days($sqldatum,$anzahltage) {

    $tag=substr($sqldatum,8,2)+0;
    $monat=substr($sqldatum,5,2)+0;
    $jahr=substr($sqldatum,0,4)+0;

    for ($l=0;$l<$anzahltage;$l++) {
      $tag++;
      if (!checkdate($monat,$tag,$jahr)) {
        $tag=1;
        $monat++;
        if ($monat>12) {
          $monat=1;
          $jahr++;
        }
      }
    }

    return sprintf('%04d-%02d-%02d',$jahr,$monat,$tag);

  }

	// Testet die Korrektheit eines Datumstextes, evtl. Ergaenzung des Jahres
	// gibt das korrekte Datum
  function test_datum($datumtext) {

    $datumsteile=explode('.',$datumtext);
    if (count($datumsteile)<2)
      $datumsteile=explode('/',$datumtext);

		$tag=$datumsteile[0];
		$monat=$datumsteile[1];
		$jahr=$datumsteile[2];

		// Kein Monat gefunden
		if ($monat==0)
			return false;

		if ($jahr>0 && $jahr<100)
		  $jahr+=2000;

		if ($jahr==0) {
			$jahr=strftime('%Y');
			if (strftime('%Y-%m-%d')<strftime('%Y').sprintf('-%02d-%02d',$monat,$tag))
				$jahr-=1;
		}

		if (!checkdate($monat,$tag,$jahr))
			return false;

		$datumneu=sprintf('%02d.%02d.%04d',$tag,$monat,$jahr);

		return $datumneu;

  }

	// Test auf Gueltigkeit einer eingegebenen E-Mail-Adresse
	function check_mail($email, $domains = array()) {

	    if ( !@is_array($domains) )$domains = array($domains);

	    if (strstr($email,',')) {
	        $email=str_replace(' ','',$email);
	        $adressen=explode(',',$email);
	    } else $adressen=array($email);

	    foreach($adressen as $email) {
	    if (!empty($email) && (!strstr($email,'@') || !strstr($email,'.') || strstr($email,' ') || strstr($email,"\n") || in_array(substr($email,strpos($email,"@")+1),$domains) || (!checkdnsrr(substr($email,strpos($email,"@")+1),"MX") && !checkdnsrr(substr($email,strpos($email,"@")+1),"A")) ))

	        return FALSE; // Wenn die Emailadresse fehlerhaft ist
	    }
	    return TRUE;
	} // ifab_check_mail

?>
