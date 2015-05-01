<?php
/**
 * Im Rahmen der Veranstaltung Sofwareqaulitaet im SS 2015 des Studigang Wirstschaftsingenieurwesen
 * mit Fachrichtung Informationstechnik soll das Postuch ,das urspruenglich von Erik Reuter von der
 * Universitaet Leipzig entwickelt wurde, auf die Beduerfnisse der EAH Jena angepasst werden.
 *
 * Im Rahmen der Vorlesung wird sich Gedanken ueber einen Anforderungskatalog gemacht, der im Laufe der
 * Zeit eingearbeitet werden soll. Die Anforderungen werden mit Hilfe des Webportal www.agilespecs.com
 * zusammengefasst und verwaltet.
 *
 * @author: Tobias Moeller, Bjoern Hoffmann, Maik Tanneberg
 */

/**
 * Jetzt unnoetig 
 */
  function replacechars($text) {

    $text=str_replace("Ã¤","a",$text);
    $text=str_replace("A","A",$text);
    $text=str_replace("Ã¶","o",$text);
    $text=str_replace("Ã–","O",$text);
    $text=str_replace("Ã¼","u",$text);
    $text=str_replace("Ãœ","U",$text);
    $text=str_replace("ÃŸ","s",$text);

    return($text);

  }

  ini_set("display_errors", 1);
  
/**
  * include_once bindet eine angegebene Datei ein und fuehrt sie als PHP-Skript aus. Dieses Verhalten
  * ist identisch zu include, mit dem einzigen Unterschied, dass die Datei, wenn sie bereits eingebunden
  * wurde, nicht erneut eingebunden wird. Wie der Name schon sagt, wird sie nur einmal eingebunden werden.
  *
  * Hier werden die Dateien: include_main.inc.php und inc/class.ezpdf.php aus dem Gesamtverzeichnis des 
  * Postbuchs eingebunden.
  * @param: include_main.inc.php
  * @param: inc/class.ezpdf.php 
  */
  include_once('include_main.inc.php');
  include_once('inc/class.ezpdf.php');
  
 /**
  * session_start() erzeugt eine Session oder nimmt die aktuelle wieder auf, die auf der Session-Kennung
  * basiert, die mit einer GET- oder POST-Anfrage oder mit einem Cookie uebermittelt wurde.
  */
  session_start();
  
/** session_id() wird verwendet, um die Session-ID der aktuellen Session zu erhalten oder zu setzen.
  * Hier wird der Variable: $sessionid der Rueckgabewert der Funktion session_id(); zugewiesen
  */
  $sessionid=session_id();

  $_SESSION["nutzer_id"];

	$_FORMVARS=array_merge($_SERVER,$_COOKIE,$_GET,$_FILES,$_POST,$_SESSION);
	$_FORMVARS=trim_array($_FORMVARS);


  /*
  if ($_FORMVARS['nutzer_id']==0)
	  exit();
  */

  $sql_startdatum=convert_sqltime($_FORMVARS['startdatum']);
  $sql_enddatum=convert_sqltime($_FORMVARS['enddatum']);

	$sql_abfrage="SELECT * FROM ".DBPREFIX."einrichtung WHERE einrichtung_id='".$_FORMVARS['einrichtung_id']."'";
  $sql_ergebnis=sql_query($sql_abfrage);
  $sql_daten=sql_fetch_array($sql_ergebnis,SQL_ASSOC);

  $einrichtungbezeichnung=$sql_daten['bezeichnung'];

  $pdf = new Cezpdf('a4');

  // Schriften

	$schriftfamilie=array(
	  'b'=>'Helvetica.afm',
		'i'=>'Helvetica-Oblique.afm',
		'bi'=>'Helvetica-BoldOblique.afm',
		'ib'=>'Helvetica-BoldOblique.afm',
		'bb'=>'Helvetica-Bold.afm');
	$pdf->setFontFamily('Helvetica.afm',$schriftfamilie);

  $pdf->selectFont('./fonts/Helvetica.afm');

  $pdf->addInfo('Author', 'Postbuch');
  $pdf->addInfo('Title', 'Postbuch - '.$einrichtungbezeichnung);
  $pdf->addInfo('Creator', 'iPosBu');
  $pdf->addInfo('Producer', 'iPosBu - www.ifabrik.de');
  $pdf->addInfo('Subject', 'Postbuch');

  $pdf->setStrokeColor(0,0,0,1);
  $pdf->setColor(0,0,0,1);

  // Kopf fuer alle Seiten
	$seitenkopf=$pdf->openObject();
	$pdf->saveState();

  $pdf->setStrokeColor(0.3,0.3,0.3,1);
  $pdf->setColor(0.3,0.3,0.3,1);

  $postbuchtext='Postbuch - '.( ($_FORMVARS['typ']=='eingang') ? 'Eingang' : 'Ausgang' );
	$pdf->addText(40,816,18,$postbuchtext);

  $breite=$pdf->getTextWidth(9,$einrichtungbezeichnung);
  $pdf->addText(570-$breite,825,9,$einrichtungbezeichnung);

	$pdf->line(40,810,570,810);

	$pdf->restoreState();
	$pdf->closeObject();

	$pdf->addObject($seitenkopf,"all");

  // Werte initialisieren
  $pdf_position=780;
  $pdf_aktseite=1;
  $letztes_datum='';


  $pdf->setStrokeColor(0.3,0.3,0.3,1);
  $pdf->setColor(0.3,0.3,0.3,1);

  $breite=$pdf->getTextWidth(9,'Seite 1');
  $pdf->addText(570-$breite,814,9,'Seite 1');

  $pdf->setStrokeColor(0,0,0,1);
  $pdf->setColor(0,0,0,1);

  // Daten anzeigen

	if (!empty($_FORMVARS['startdatum']) && !empty($_FORMVARS['enddatum'])) {

    if (convert_sqltime($_FORMVARS['startdatum'])>convert_sqltime($_FORMVARS['enddatum'])) {
    	$tempdatum=$_FORMVARS['startdatum'];
    	$_FORMVARS['startdatum']=$_FORMVARS['enddatum'];
    	$_FORMVARS['enddatum']=$tempdatum;
    }

	}

	$suchfilter='';
	if (!empty($_FORMVARS['startdatum']))
		$suchfilter.="AND datum>='".convert_sqltime($_FORMVARS['startdatum'])."' ";
	if (!empty($_FORMVARS['enddatum']))
		$suchfilter.="AND datum<='".convert_sqltime($_FORMVARS['enddatum'])."' ";

  $sql_abfrage="SELECT * FROM ".DBPREFIX."postbuch AS P LEFT JOIN ".DBPREFIX."bemerkung AS B ON P.postbuch_id=B.postbuch_id WHERE einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='".$_FORMVARS['typ']."' ".$suchfilter."ORDER BY datum,P.postbuch_id";
  $sql_ergebnis=sql_query($sql_abfrage);

  while ($sql_daten=sql_fetch_array($sql_ergebnis,SQL_ASSOC)) {

    $platzbedarf=24;
    if (!empty($sql_daten['bemerkung'])) {
    	$bemerkungzeilen=explode("\n",$sql_daten['bemerkung']);
	    $anzahl_bemerkung=count($bemerkungzeilen);
    	$platzbedarf+=14*$anzahl_bemerkung;
    }
		if ($sql_daten['datum']!=$letztes_datum) {
			$platzbedarf+=18;
		}

    if ($pdf_position<45+$platzbedarf) {
      $pdf->newPage();
      $pdf_aktseite++;

		  $pdf->setStrokeColor(0.3,0.3,0.3,1);
		  $pdf->setColor(0.3,0.3,0.3,1);

		  $breite=$pdf->getTextWidth(9,'Seite '.$pdf_aktseite);
		  $pdf->addText(570-$breite,814,9,'Seite '.$pdf_aktseite);

		  $pdf->setStrokeColor(0,0,0,1);
		  $pdf->setColor(0,0,0,1);

      $pdf_position=780;
    }

		if ($sql_daten['datum']!=$letztes_datum) {

		  $pdf->setStrokeColor(0.8,0.8,0.8,1);
		  $pdf->setColor(0.8,0.8,0.8,1);
		  $pdf->filledRectangle(40,$pdf_position,530,15);

		  $pdf->setStrokeColor(0,0,0,1);
		  $pdf->setColor(0,0,0,1);
		  $pdf->addText(45,$pdf_position+2,12,convert_time($sql_daten['datum']));

      $letztes_datum=$sql_daten['datum'];

      $pdf_position-=14;

		}

    $adresse=$sql_daten['bezeichnung'];

    switch ($sql_daten['medium']) {

      case 'post':
        $pdf->addText(42,$pdf_position-1,6,'Post',-35);
        if (!empty($sql_daten['str']))
          $adresse.=', '.$sql_daten['str'];
        if (!empty($sql_daten['ort']))
          $adresse.=', '.trim($sql_daten['plz'].' '.$sql_daten['ort']);
        if (!empty($sql_daten['land']))
          $adresse.=', '.$sql_daten['land'];
        break;

      case 'fax':
        $pdf->addText(42,$pdf_position,6,'Fax',-35);
        if (!empty($sql_daten['fax']))
	        $adresse.=', Fax: '.$sql_daten['fax'];
        break;

      case 'email':
        $pdf->addText(42,$pdf_position-2,6,'Mail',-35);
        if (!empty($sql_daten['email']))
          $adresse.=', '.$sql_daten['email'];
        break;

    }


    $pdf->addText(53,$pdf_position,12,$adresse);

    $pdf_position-=14;

    if (!empty($sql_daten['bemerkung'])) {
	    for ($l=0;$l<$anzahl_bemerkung;$l++) {
	    	$pdf->addText(60,$pdf_position,12,$bemerkungzeilen[$l]);
	    	$pdf_position-=14;
	    }

    }


    $pdf_position-=10;

  }


  // PDF-Datei senden

  // echo $pdf->output();

	$pdf->ezStream();

?>


