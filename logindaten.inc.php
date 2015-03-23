<?php
/**
* Postbuch - Universitaet Leipzig
* Hauptkonfiguration
*
* @author Erik Reuter
* @copyright 2007 i-fabrik GmbH
* @version $Id: logindaten.inc.php,v 1.2 2007/01/26 14:55:39 erik Exp $
*
*/


  include_once('common.mysql.php');

  define('DBPREFIX','');



  $dblogin['server']    = 'localhost';
  $dblogin['nutzer']    = $_SERVER['DBUser'];
  $dblogin['passwort']  = $_SERVER['DBPass'];
  $dblogin['database']  = 'postbuch_demo';

?>
