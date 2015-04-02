<?php
/**
* Postbuch - Universitaet Leipzig
* Konfigurationseinleser
*
* @author Erik Reuter
* @copyright 2007 i-fabrik GmbH
* @version $Id: include_main.inc.php,v 1.1 2007/01/26 14:55:39 erik Exp $
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
	
  // Einbindung der anderen Dateien des Postbuches
  include_once('logindaten.inc.php');
  include_once('config.inc.php');
  include_once('PHPMyLib/PHPMyLib.php');
  include_once('inc/functions.inc.php');
  include_once('inc/projekt.inc.php');

  // Datenbankverbindung
  $dbverbindung=sql_connect('localhost','EAHUsersql1','apDJdehv');
  sql_select_db('EAHUsersql1');

  include_once('inc/session.inc.php');

?>
