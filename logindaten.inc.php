<?php
/**
* Postbuch - Universitaet Leipzig
* Hauptkonfiguration
*
* @author Erik Reuter
* @copyright 2007 i-fabrik GmbH
* @version $Id: logindaten.inc.php,v 1.2 2007/01/26 14:55:39 erik Exp $
*
* Im Rahmen der Veranstaltung Sofwareqaulit�t im SS 2015 des Studigang Wirstschaftsingenieurwesen
* mit Fachrichtung Informationstechnik soll das Postuch ,das urspr�nglich von Erik Reuter von der 
* Universit�t Leipzig entwickelt wurde, auf die Bed�rfnisse der EAH Jena angepasst werden.
* 
* Im Rahmen der Vorlesung wird sich Gedanken �ber einen Anforderungskatalog gemacht, der im Laufe der 
* Zeit eingearbeitet werden soll. Die Anforderungen werden mit Hilfe des Webportal www.agilespecs.com
* zusammengefasst und verwaltet. 
* 
* @author: Tobias M�ller, Bj�rn Hoffmann, Maik Tanneberg
*/

/**
 * include_once bindet eine angegebene Datei ein und f�hrt sie als PHP-Skript aus. Dieses Verhalten
 * ist identisch zu include, mit dem einzigen Unterschied, dass die Datei, wenn sie bereits eingebunden
 * wurde, nicht erneut eingebunden wird. Wie der Name schon sagt, wird sie nur einmal eingebunden werden.
 *
 * Hier wird die Datei: include_main.inc.php aus dem Gesamtverzeichnis des Postbuchs eingebunden
 * @param: common.mysql.php
 */
  include_once('common.mysql.php');

  define('DBPREFIX','');

  $dblogin['server']    = 'localhost';
  $dblogin['nutzer']    = $_SERVER['DBUser'];
  $dblogin['passwort']  = $_SERVER['DBPass'];
  $dblogin['database']  = 'postbuch_demo';

?>
