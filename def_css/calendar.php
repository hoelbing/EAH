<?php
/**
 * Postbuch - Universitaet Leipzig
 * ausgabe: css calendar
 *
 * @author heiko pfefferkorn
 * @copyright 2007 i-fabrik GmbH
 * @version $Id: calendar.php,v 1.3 2007/02/21 13:50:50 heiko Exp $
 * 
 * Im Rahmen der Veranstaltung Softwarequalit�t im SS 2015 des Studigang Wirstschaftsingenieurwesen
 * mit Fachrichtung Informationstechnik soll das Postuch ,das urspr�nglich von Erik Reuter von der 
 * Universit�t Leipzig entwickelt wurde, auf die Bed�rfnisse der EAH Jena angepasst werden.
 *  
 * Im Rahmen der Vorlesung wird sich Gedanken �ber einen Anforderungskatalog gemacht, der im Laufe der 
 * Zeit eingearbeitet werden soll. Die Anforderungen werden mit Hilfe des Webportal www.agilespecs.com
 * zusammengefasst und verwaltet. 
 * 
 * @author: Tobias M�ller, Bj�rn Hoffmann, Maik Tanneberg
 * 
 */

    $_FORMVARS  = array_merge($_SERVER,$_COOKIE,$_GET,$_FILES,$_POST);

    // content-type setzen
    header("Content-type: text/css");

	$_FORMVARS['color'] = strip_tags($_FORMVARS['color']);

    // zentrale include-datei inkl. db-anmeldung einbinden
    $verzeichnis = getcwd();
    chdir("../");
	include_once('include_main.inc.php');
    //chdir($verzeichnis);

    $ausgabe = '';
	$farbe   = (!empty($_FORMVARS['color']) && in_array($_FORMVARS['color'], $feld_farben)) ? $_FORMVARS['color'] : $feld_farben[0];

    // sessionverwaltung erfolgt datenbankbasiert
    session_start();
    $namred_data['sessionid'] = session_id();

    // template initialisieren
    $tpl = new PTemplate( NULL, $templatefiles['css_calendar'] );
	$tpl->addComponent('farbe', new PText($farbe));
    $tpl->addComponent('pfad',  new PText("../"));

	$ausgabe = $tpl->outputStr();

	// Klammern ersetzen '{' & '}' duerfen im template nicht vorhanden sein da sonst die templatebiliothek durcheinander kommt
	$ausgabe = str_replace( "�<�", "{", $ausgabe);
	$ausgabe = str_replace( "�>�", "}", $ausgabe);

	// generierten inhalt ausgeben */
	echo $ausgabe;
?>
