<?php
/**
* NamRed 1.7
* Funktionsdefinition MySQL
*
* @author Erik Reuter
* @copyright 2005 Erik Reuter
* @version $Id: common.mysql.php,v 1.2 2005/12/13 17:26:10 sebastian Exp $
*
* Im Rahmen der Veranstaltung Sofwareqaulität im SS 2015 des Studigang Wirstschaftsingenieurwesen
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
 * Die Standdardbegfehle für MySQL unterliegen einem ständigen Wandel. Durch die 
 * Deklararation von Funktionen mit dem sql_"Funktion" mit dem returnwert der aktuellen
 * Entwicklung von mysql/ mysqli wird eine ständige Anpassung der MySQL Befehle umgangen.
 */

/**
 * mysql_connect - Stellt Verbindung zur SQL-Datenbank her.
 * @param: $server
 * @param: $nutzer
 * @param: $passwort
 */
  function sql_connect($server,$nutzer,$passwort) {
    return mysql_connect($server,$nutzer,$passwort);
  }

/**
 * mysql_select_db — Auswahl einer MySQL Datenbank
 * @param: $database
 */
  function sql_select_db($database) {
    return mysql_select_db($database);
  }

/**
 * 
 */
  function sql_query($abfrage) {
    $ergebnis=mysql_query($abfrage);

    if (defined('DEBUG') && mysql_error()!='') {
      echo $abfrage.'<br>';
      echo mysql_error().'<br>';
    }

    return $ergebnis;
  }

/**
 * mysql_fetch_row — Liefert einen Datensatz als indiziertes Array
 * @param: $ergebnis
 */  
  function sql_fetch_row($ergebnis) {
    return mysql_fetch_row($ergebnis);
  }

/**
 * mysql_insert_id — Liefert die ID, die in der vorherigen Abfrage erzeugt wurde
 */
  function sql_insert_id() {
    return mysql_insert_id();
  }

/**
 * mysql_close — Schließt eine Verbindung zu MySQL ("Server")
 * @param: $verbindung
 */
  function sql_close($verbindung) {
    return mysql_close($verbindung);
  }

/**
 * mysql_fetch_array — Liefert einen Datensatz als assoziatives Array, als numerisches Array oder beides 
 * @param: $ergebnis
 * @param: $typ
 */
  function sql_fetch_array($ergebnis,$typ) {
    return mysql_fetch_array($ergebnis,$typ);
  }
/**
 * mysql_fetch_assoc — Liefert einen Datensatz als assoziatives Array -> Die Nummern als Indizes sind kein Muss
 * es ist auch möglich, die Indizes zu benennen. Dies nennt sich dann ein "Assoziatives Array". 
 */  
  function sql_fetch_assoc($ergebnis) {
    return mysql_fetch_assoc($ergebnis); 
  }   

/**
 * mysql_result — Liefert Ergebnis der Abfrage in der jeweiligen Datenbank
 * @param: $ergebnis
 * @param: $datensatz
 * @param: $feld mit Startwert 0
 */  
  function sql_result($ergebnis,$datensatz,$feld=0) {
    return mysql_result($ergebnis,$datensatz,$feld);
  }
  
/**
 * mysql_num_rows — Liefert die Anzahl der Zeilen einer Ergebnismenge. 
 * Diese Funktion ist nur gültig für Befehle wie SELECT oder SHOW, die eine 
 * tatsächliche Ergebnismenge zurückeben. 
 * @param: $ergebnis
 */
  function sql_num_rows($ergebnis) {
    return mysql_num_rows($ergebnis);
  }
  
/**
 * 
 */  
  function sql_data_seek($ergebnis,$satznummer) {
    return mysql_data_seek($ergebnis,$satznummer);
  }
/**
 * 
 */
  function sql_error() {
    return mysql_error();  
  }
  
  define('SQL_ASSOC',MYSQL_ASSOC);
  define('SQL_NUM',MYSQL_NUM);
  define('SQL_BOTH',MYSQL_BOTH);
  
?>