<?php
/**
 * Postbuch - Universitaet Leipzig
 * ausgabe: css main
 *
 * @author heiko pfefferkorn
 * @copyright 2007 i-fabrik GmbH
 * @version $Id: main.php,v 1.3 2007/02/21 13:50:50 heiko Exp $
 *
 * Im Rahmen der Veranstaltung Softwarequalität im SS 2015 des Studigang Wirstschaftsingenieurwesen
 * mit Fachrichtung Informationstechnik soll das Postuch ,das ursprünglich von Erik Reuter von der 
 * Universität Leipzig entwickelt wurde, auf die Bedürfnisse der EAH Jena angepasst werden.
 * 
 * Im Rahmen der Vorlesung wird sich Gedanken über einen Anforderungskatalog gemacht, der im Laufe der 
 * Zeit eingearbeitet werden soll. Die Anforderungen werden mit Hilfe des Webportal www.agilespecs.com
 * zusammengefasst und verwaltet. 
 * 
 * @author: Tobias Möller, Björn Hoffmann, Maik Tanneberg
 */

/**
 * array_merge - Fügt die Elemente von zwei oder mehr Arrays zusammen, indem die Werte des einenan das Ende
 * des vorherigen angehängt werden. Das daraus resultierende Array wird zurückgegeben.
 *
 * @var: $_FORMVARS
 *
 * In der Variable $_FORMVARS werden die Variablen $_SERVER,$_COOKIE,$_GET,$_FILES,$_POST und $_SESSION zusammengeführt
 */
    $_FORMVARS  = array_merge($_SERVER,$_COOKIE,$_GET,$_FILES,$_POST);

/**
 * header() - wird zum Senden von HTTP-Anfangsinformationen (Headern) im Rohformat benutzt.
 *
 * Hier wird der Content-Type text/css gesetzt.
 */
    header("Content-type: text/css");

/**
 *  strip_tags — Entfernt HTML- und PHP-Tags aus einem String
 */    
    $_FORMVARS['fsize'] = strip_tags($_FORMVARS['fsize']);
	$_FORMVARS['color'] = strip_tags($_FORMVARS['color']);

/**
 * getcwd() - Gibt das aktuelle Arbeitsverzeichnis zurück und schreibt es in die Variable $verzeichnis
 */
	$verzeichnis = getcwd();

/**
 * chdir — Wechseln des Verzeichnisses
 */	
	chdir("../");
	//chdir($verzeichnis);
	
/**
 * include_once bindet eine angegebene Datei ein und führt sie als PHP-Skript aus. Dieses Verhalten
 * ist identisch zu include, mit dem einzigen Unterschied, dass die Datei, wenn sie bereits eingebunden
 * wurde, nicht erneut eingebunden wird. Wie der Name schon sagt, wird sie nur einmal eingebunden werden.
 *
 * Hier wird die Datei: include_main.inc.php aus dem Gesamtverzeichnis des Postbuchs eingebunden.Diese
 * enthält die Anmeldung an der MySQL Datenbank
 * @param: include_main.inc.php
 */
	include_once('include_main.inc.php');
    
    $ausgabe = '';
	$fsize   = (!empty($feld_schriftgroessen[$_FORMVARS['fsize']][1])) ? $feld_schriftgroessen[$_FORMVARS['fsize']][1] : $feld_schriftgroessen[$nutzereinstellung['schriftgroesse']][1];
	$farbe   = (!empty($_FORMVARS['color']) && in_array($_FORMVARS['color'], $feld_farben)) ? $_FORMVARS['color'] : $feld_farben[0];

    // sessionverwaltung erfolgt datenbankbasiert
    session_start();
    $namred_data['sessionid'] = session_id();

    // template initialisieren
    $tpl = new PTemplate(NULL, $templatefiles['css_main']);

	$tpl->addComponent('fsize', new PText($fsize));
	$tpl->addComponent('farbe', new PText($farbe));
    $tpl->addComponent('pfad',  new PText("../"));

	$ausgabe = $tpl->outputStr();
	
/**
 * str_replace — Ersetzt alle Vorkommen des Suchstrings durch einen anderen String
 * Diese Funktion gibt einen String oder ein Array zurück, in dem alle Vorkommen von search innerhalb von subject durch den angegebenen
 * replace-Wert ersetzt wurden.
 *
 * mixed str_replace ( mixed $search , mixed $replace , mixed $subject [, int &$count ] )
 *
 * Klammern ersetzen '{' & '}' duerfen im template nicht vorhanden sein, da sonst die Templatebiliothek durcheinander kommt
 *
 * ¦<¦ - ist ein Zeichen aus dem DWT Vermutung: Der Browser kommt mit dem DWT Zeichen nicht zurecht und benötigt {}
 */
	$ausgabe = str_replace( "¦<¦", "{", $ausgabe);
	$ausgabe = str_replace( "¦>¦", "}", $ausgabe);

/**
 * echo - Gibt alle Parameter aus.
 * echo ist nicht wirklich eine Funktion sondern ein Sprach-Konstrukt, daher brauchen Sie keine Klammern verwenden. echo verhält sich 
 * im Gegensatz zu einigen anderen Sprach-Konstrukten nicht wie eine Funktion, deshalb kann es nicht immer in einem Funktionskontext
 * verwendet werden. Hinzu kommt, dass bei der Angabe mehrerer Parameter für echo diese nicht von Klammern umschlossen sein dürfen.
 *
 * @var: $ausgabe;
 */	
	echo $ausgabe;
?>
