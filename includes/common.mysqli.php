<?php
/**
 * NamRed 3.0
 * Funktionsdefinition MySQL
 *
 * @author Michael Weinrich
 * @copyright 2006 i-fabrik GmbH
 * @version $Id: common.mysqli.php,v 1.3 2006/11/03 09:59:56 michael Exp $
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

  // SQL-Befehlsanpassung fuer mySQLi
  $MYSQL_CONNECTION_ID = NULL;

  function sql_connect($server,$nutzer,$passwort)
  {
    global $MYSQL_CONNECTION_ID;
    return $MYSQL_CONNECTION_ID = mysqli_connect($server,$nutzer,$passwort);
  }

  function sql_set_charset($charset)
  {
    global $MYSQL_CONNECTION_ID;
    return mysqli_set_charset($MYSQL_CONNECTION_ID, $charset);
  }

  function sql_select_db($database)
  {
    global $MYSQL_CONNECTION_ID;
    return mysqli_select_db($MYSQL_CONNECTION_ID, $database);
  }

  function sql_query($abfrage)
  {
    global $MYSQL_CONNECTION_ID;

    $ergebnis = @mysqli_query($MYSQL_CONNECTION_ID, $abfrage);

    //if (defined('DEBUG') && mysqli_error($MYSQL_CONNECTION_ID)!='')
    if (!$ergebnis)
    {
      var_dump($MYSQL_CONNECTION_ID);
      echo $abfrage.'<br>';
      echo mysqli_error($MYSQL_CONNECTION_ID).'<br>';
    }

    return $ergebnis;
  }

  function sql_insert_id()
  {
    global $MYSQL_CONNECTION_ID;
    return mysqli_insert_id($MYSQL_CONNECTION_ID);
  }

  function sql_close($verbindung)
  {
    return mysqli_close($verbindung);
  }

  function sql_fetch_row($ergebnis)
  {
    global $MYSQL_CONNECTION_ID;
    return $ergebnis ? mysqli_fetch_row($ergebnis) : $ergebnis;
  }

  function sql_fetch_array($ergebnis,$typ=MYSQLI_BOTH)
  {
    return $ergebnis ? mysqli_fetch_array($ergebnis,$typ) : $ergebnis;
  }

  function sql_fetch_assoc($ergebnis)
  {
    return $ergebnis ? mysqli_fetch_assoc($ergebnis) : $ergebnis;
  }

  function sql_result($ergebnis,$datensatz,$feld=0)
  {
    global $MYSQL_CONNECTION_ID;

    if(!$ergebnis)
    {
      return false;
    }
    $tmp = array();
    mysqli_data_seek($ergebnis, $datensatz);
    $tmp = mysqli_fetch_row($ergebnis);

    return $tmp[$feld];
  }

  function sql_num_rows($ergebnis)
  {
    return mysqli_num_rows($ergebnis);
  }

  function sql_data_seek($ergebnis,$satznummer)
  {
    return mysqli_data_seek($ergebnis,$satznummer);
  }

  function sql_error()
  {
    global $MYSQL_CONNECTION_ID;
    return mysqli_error($MYSQL_CONNECTION_ID);
  }

  define('SQL_ASSOC', MYSQLI_ASSOC);
  define('SQL_NUM',   MYSQLI_NUM);
  define('SQL_BOTH',  MYSQLI_BOTH);

?>