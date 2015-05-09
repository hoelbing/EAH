<?php
/**
 * Postbuch - Universitaet Leipzig
 * Konfigurationseinleser
 *
 * @author Erik Reuter
 * @copyright 2007 i-fabrik GmbH
 * @version $Id: include_main.inc.php,v 1.1 2007/01/26 14:55:39 erik Exp $
 * 
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
 * include_once bindet eine angegebene Datei ein und fuehrt sie als PHP-Skript aus. Dieses Verhalten 
 * ist identisch zu include, mit dem einzigen Unterschied, dass die Datei, wenn sie bereits eingebunden
 * wurde, nicht erneut eingebunden wird. Wie der Name schon sagt, wird sie nur einmal eingebunden werden. 
 * 
 * Hier werden die nachfolgend aufgefuehrten Dateien aus dem Gesamtverzeichnis bzw. Unterverzeichnissen 
 * des Postbuchs eingebunden.
 * @param: logindaten.inc.php
 * @param: config.inc.php 
 * @param: PHPMyLib/PHPMyLib.php
 * @param: includes/functions.inc.php
 * @param: includes/projekt.inc.php
 */
  include_once('logindaten.inc.php');
  include_once('config.inc.php');
  include_once('PHPMyLib/PHPMyLib.php');
  include_once('includes/functions.inc.php');
  include_once('includes/projekt.inc.php');

/**
 * sql_connect(): Ã–ffnet eine Verbindung zu einem MySQL-Server
 * Hierbei gilt es zu beachten, das in den Dateien common.mysql.php und common.mysqli.php Funktionen 
 * geschrieben wurden, die es ermoeglichen die mysql/ mysqli Befehle wie nachfolgend aufzurufen. 
 * @param: Serveradresse
 * @param: Nutzer
 * @param: Passwort
 */
  $dbverbindung=sql_connect($dblogin['server'],$dblogin['nutzer'],$dblogin['passwort']);
  
/**
 * sql_select_db: Auswahl einer MySQL Datenbank
 * Hierbei gilt es zu beachten, das in den Datein common.mysql.php und common.mysqli.php Funktionen 
 * geschrieben wurden, die es ermoeglichen die mysql/ mysqli Befehle wie nachfolgend aufzurufen.
 */
  sql_select_db($dblogin['database']);

/**
 * include_once bindet eine angegebene Datei ein und fuehrt sie als PHP-Skript aus. Dieses Verhalten
 * ist identisch zu include, mit dem einzigen Unterschied, dass die Datei, wenn sie bereits eingebunden
 * wurde, nicht erneut eingebunden wird. Wie der Name schon sagt, wird sie nur einmal eingebunden werden.
 * Hier wird die nachfolgend aufgefuehrte Datei aus dem Gesamtverzeichnis bzw. Unterverzeichnissen 
 * des Postbuchs eingebunden.
 * @param: inc/session.inc.php
 */   
  include_once('includes/session.inc.php');

?>
