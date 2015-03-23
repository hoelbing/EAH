<?php
/**
* NamRed 1.7
* Funktionsdefinition MySQL
*
* @author Erik Reuter
* @copyright 2005 Erik Reuter
* @version $Id: common.mysql.php,v 1.2 2005/12/13 17:26:10 sebastian Exp $
*
*/     

  // SQL-Befehlsanpassung fuer mySQL

  function sql_connect($server,$nutzer,$passwort) {
    return mysql_connect($server,$nutzer,$passwort);
  }

  function sql_select_db($database) {
    return mysql_select_db($database);
  }

  function sql_query($abfrage) {
    $ergebnis=mysql_query($abfrage);

    if (defined('DEBUG') && mysql_error()!='') {
      echo $abfrage.'<br>';
      echo mysql_error().'<br>';
    }

    return $ergebnis;
  }

  function sql_fetch_row($ergebnis) {
    return mysql_fetch_row($ergebnis);
  }

  function sql_insert_id() {
    return mysql_insert_id();
  }

  function sql_close($verbindung) {
    return mysql_close($verbindung);
  }

  function sql_fetch_array($ergebnis,$typ) {
    return mysql_fetch_array($ergebnis,$typ);
  }
  
  function sql_fetch_assoc($ergebnis) {
    return mysql_fetch_assoc($ergebnis); 
  }   

  function sql_result($ergebnis,$datensatz,$feld=0) {
    return mysql_result($ergebnis,$datensatz,$feld);
  }

  function sql_num_rows($ergebnis) {
    return mysql_num_rows($ergebnis);
  }
  
  function sql_data_seek($ergebnis,$satznummer) {
    return mysql_data_seek($ergebnis,$satznummer);
  }

  function sql_error() {
    return mysql_error();  
  }
  
  define('SQL_ASSOC',MYSQL_ASSOC);
  define('SQL_NUM',MYSQL_NUM);
  define('SQL_BOTH',MYSQL_BOTH);
  
?>