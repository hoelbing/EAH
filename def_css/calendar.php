<?php
/**
 * Postbuch - Universitaet Leipzig
 * ausgabe: css calendar
 *
 * @author heiko pfefferkorn
 * @copyright 2007 i-fabrik GmbH
 * @version $Id: calendar.php,v 1.3 2007/02/21 13:50:50 heiko Exp $
 * 
 * Im Rahmen der Veranstaltung Softwarequalitaet im SS 2015 des Studigang Wirstschaftsingenieurwesen
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
 * array_merge - Faengt die Elemente von zwei oder mehr Arrays zusammen, indem die Werte des einenan das Ende
 * des vorherigen angehaengt werden. Das daraus resultierende Array wird zurueckgegeben.
 *
 * @param: $_FORMVARS
 *
 * In der Variable $_FORMVARS werden die Variablen $_SERVER,$_COOKIE,$_GET,$_FILES,$_POST und $_SESSION zusammengefuehrt
 */
    $_FORMVARS  = array_merge($_SERVER,$_COOKIE,$_GET,$_FILES,$_POST);
  
/**
 * header() - wird zum Senden von HTTP-Anfangsinformationen (Headern) im Rohformat benutzt.
 * 
 * Hier wird der Content-Type text/css gesetzt.
 */
    header("Content-type: text/css");

/**
 *  strip_tags - Entfernt HTML- und PHP-Tags aus einem String
 */     
	$_FORMVARS['color'] = strip_tags($_FORMVARS['color']);

/**
 * getcwd() - Gibt das aktuelle Arbeitsverzeichnis zurueck und schreibt es in die Variable $verzeichnis
 */	
	$verzeichnis = getcwd();

/**
 * chdir - Wechseln des Verzeichnisses  
 */ 
    chdir("../");
    //chdir($verzeichnis);
    
/**
 * include_once bindet eine angegebene Datei ein und fuehrt sie als PHP-Skript aus. Dieses Verhalten
 * ist identisch zu include, mit dem einzigen Unterschied, dass die Datei, wenn sie bereits eingebunden
 * wurde, nicht erneut eingebunden wird. Wie der Name schon sagt, wird sie nur einmal eingebunden werden.
 *
 * Hier wird die Datei: include_main.inc.php aus dem Gesamtverzeichnis des Postbuchs eingebunden.Diese 
 * enthaelt die Anmeldung an der MySQL Datenbank
 * 
 * @param: include_main.inc.php
 */    
	include_once('include_main.inc.php');
	
/**
 * Vermutung: Die Variable $ausgabe wird hier zum Beginn leer gesetzt.
 */  
    $ausgabe = '';
    
/**
 * in_array - Untersucht on ein Wert in einem Array vorhanden ist
 * 
 * empty - bestimmt ob eine Variable leer ist
 * 
 */   
	$farbe   = (!empty($_FORMVARS['color']) && in_array($_FORMVARS['color'], $feld_farben)) ? $_FORMVARS['color'] : $feld_farben[0];

/**
 * session_start() erzeugt eine Session oder nimmt die aktuelle wieder auf, die auf der Session-Kennung basiert, die mit einer GET- oder
 * POST-Anfrage oder mit einem Cookie uebermittelt wurde.
 * 
 * Sessionverwaltung erfolgt datenbankbasiert
 */
	session_start();
/**
 * session_id() wird verwendet, um die Session-ID der aktuellen Session zu erhalten oder zu setzen.
 */	
    $namred_data['sessionid'] = session_id();

/**
 * Initialisierung des Template
 * 
 * Hinzufuegen der Templatecomponenete farbe die auf PText wirkt
 * Hinzufuegen der Templatecomponenete pfad die auf PText wirkt
 * 
 */
    // template initialisieren
    $tpl = new PTemplate( NULL, $templatefiles['css_calendar'] );
	$tpl->addComponent('farbe', new PText($farbe));
    $tpl->addComponent('pfad',  new PText("../"));

	$ausgabe = $tpl->outputStr();
/**
 * Diese Funktion gibt einen String oder ein Array zurueck, in dem alle Vorkommen 
 * von search innerhalb von subject durch den angegebenen replace-Wert ersetzt wurden.
 * 
 * mixed str_replace ( mixed $search , mixed $replace , mixed $subject [, int &$count ] )
 * 
 * Klammern ersetzen '{' & '}' duerfen im template nicht vorhanden sein da sonst die 
 * templatebiliothek durcheinander kommt
 */
	$ausgabe = str_replace( "¦<¦", "{", $ausgabe);
	$ausgabe = str_replace( "¦>¦", "}", $ausgabe);

/**
 * echo — Gibt einen oder mehrere Strings aus
 * Hier: Ausgabe des Inhaltes der in die Variable $ausgabe geschrieben wurde.
 * @var: $ausgabe
 */
	echo $ausgabe;
?>
