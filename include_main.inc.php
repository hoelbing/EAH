<?php
/**
* Postbuch - Universitaet Leipzig
* Konfigurationseinleser
*
* @author Erik Reuter
* @copyright 2007 i-fabrik GmbH
* @version $Id: include_main.inc.php,v 1.1 2007/01/26 14:55:39 erik Exp $
*
*/

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
