<?php
/**
 * Postbuch - Universitaet Leipzig
 * Hauptkonfiguration
 *
 * @author Erik Reuter
 * @copyright 2007 i-fabrik GmbH
 * @version $Id: logindaten.inc.php,v 1.2 2007/01/26 14:55:39 erik Exp $
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
 * include_once bindet eine angegebene Datei ein und fuehrt sie als PHP-Skript aus. Dieses Verhalten
 * ist identisch zu include, mit dem einzigen Unterschied, dass die Datei, wenn sie bereits eingebunden
 * wurde, nicht erneut eingebunden wird. Wie der Name schon sagt, wird sie nur einmal eingebunden werden.
 *
 * Hier wird die Datei: include_main.inc.php aus dem Gesamtverzeichnis des Postbuchs eingebunden.
 * @param: common.mysql.php
 */
  include_once('includes/common.mysql.php');

/**
 * define - Definiert waehrend der Laufzeit eine benannte Konstante. 
 * Hier wird die Konstante DBPREFIX definiert.
 */
  define('DBPREFIX','');
  
/**
 * 
 */
  $dblogin = array();
  $dblogin['server']    = 'localhost';
  $dblogin['nutzer']    = 'postbuch';
  $dblogin['passwort']  = 'postbuch';
  $dblogin['database']  = 'postbuch';

?>