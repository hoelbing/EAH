<?php
/**
 * NamRed 1.7
 * Funktionsdefinition MySQL
 *
 * @author Erik Reuter
 * @copyright 2005 Erik Reuter
 * @version $Id: common.mysql.php,v 1.2 2005/12/13 17:26:10 sebastian Exp $
 *
 * Im Rahmen der Veranstaltung Softwarequalität im SS 2015 des Studigang Wirstschaftsingenieurwesen
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
 * Deklararation von Funktionen mit dem sql_"Funktion" mit dem Returnwert der aktuellen
 * Entwicklung von mysql/ mysqli wird eine ständige Anpassung der MySQL Befehle umgangen.
 */

/**
 * mysql_connect - Öffnet eine neue Verbindung (oder nutzt eine bestehende Verbindung) zu einem mySQL
 * Server.
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 * @param: $server
 * @param: $nutzer
 * @param: $passwort
 */
  function sql_connect($server,$nutzer,$passwort) {
    return mysql_connect($server,$nutzer,$passwort);
  }

/**
 * mysql_select_db — Setzt die aktive Datenbank auf dem Server die mit der angegebenen Verbindung 
 * assoziiert ist.
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 * @param: $database
 */
  function sql_select_db($database) {
    return mysql_select_db($database);
  }

/**
 * mysql_query() sendet eine einzelne Abfrage (mehrere Abfragen werden nicht unterstützt) zu dem
 * momentan aktiven Schema auf dem Server, der mit der übergebenen Verbings-Kennung 
 * Verbindungs-Kennung (Die MySQL-Verbindung. Wird die Verbindungskennung nicht angegeben, wird die 
 * letzte durch mysql_connect() geöffnete Verbindung angenommen) assoziiert ist. 
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 * @param: $abfrage
 */
  function sql_query($abfrage) {
    $ergebnis=mysql_query($abfrage);
	
    if (defined('DEBUG') && mysql_error()!=''){
      echo $abfrage.'<br>';
      echo mysql_error().'<br>';
    }

    return $ergebnis;
  }

/**
 * mysql_fetch_row — Liefert ein numerisch indizertes Array, das der geholten Zeile entspricht und
 * bewegt den internen Datensatzzeiger vorwärts.
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 * @param: $ergebnis
 */  
  function sql_fetch_row($ergebnis) {
    return mysql_fetch_row($ergebnis);
  }

/**
 * mysql_insert_id — Liefert die ID, die für eine AUTO_INCREMENT Spalte durch die vorherige Abfrage 
 * (meist INSERT) erzeugt wurde.
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 */
  function sql_insert_id() {
    return mysql_insert_id();
  }

/**
 * mysql_close — schließt die nicht persistente Verbindung zum MySQL-Server, die mit der angegebenen 
 * Verbindungs-Kennung verknüpft ist. Die Verwendung von mysql_close() ist für gewöhnlich nicht notwendig, 
 * weil offene, nicht persistente Verbindungen automatisch mit Beendigung des PHP-Skripts geschlossen werden.
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 * @param: $verbindung
 */
  function sql_close($verbindung) {
    return mysql_close($verbindung);
  }

/**
 * mysql_fetch_array — Liefert einen Datensatz als assoziatives Array, als numerisches Array oder beides
 * zurück, dass der gelesenen Zeile entspricht und bewegt den internen Datenzeiger vorwärts.  
 *
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 * @param: $ergebnis
 * @param: $typ
 */
  function sql_fetch_array($ergebnis,$typ) {
    return mysql_fetch_array($ergebnis,$typ);
  }
  
/**
 * mysql_fetch_assoc — Liefert ein assoziatives Array, das der geholten Zeile entspricht und bewegt den 
 * internen Datensatzzeiger vorwärts. mysql_fetch_assoc() entspricht in der Funktionsweise exakt dem Aufruf
 * von mysql_fetch_array() mit Angabe von MYSQL_ASSOC als optionalen zweiten Parameter. Diese Funktion liefert 
 * Ihnen nur ein assoziatives Array.  -> Die Nummern als Indizes sind kein Muss, es ist auch möglich, die Indizes
 * zu benennen. Dies nennt sich dann ein "Assoziatives Array".
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 * @param: $ergebnis
 */  
  function sql_fetch_assoc($ergebnis) {
    return mysql_fetch_assoc($ergebnis); 
  }   

/**
 * mysql_result — Liefert Ergebnismenge der Abfrage aus der jeweiligen Datenbank
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
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
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 * @param: $ergebnis
 */
  function sql_num_rows($ergebnis) {
    return mysql_num_rows($ergebnis);
  }
  
/**
 * mysql_data_seek() - bewegt den internen Datensatz-Zeiger eines Anfrageergebnisses das mit der übergebenen
 * Resultkennung assoziiert ist, zu dem Datensatz mit der übergebenen Zeilennummer. Der nächste Aufruf einer
 * MySQL fetch Funktion, wie etwa mysql_fetch_row() liefert die entsprechende Zeile.
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 * @param: $ergebnis
 * @param: $satznummer -> Die gewünschte Zeilennummer des neuen Ergebnis-Zeigers.
 */  
  function sql_data_seek($ergebnis,$satznummer) {
    return mysql_data_seek($ergebnis,$satznummer);
  }
/**
 * mysql_error() - Liefert den Fehlertext der letzten MySQL Funktion. Fehler, die vom MySQL Server kommen, 
 * führen nicht mehr zu einer Ausgabe von Warnungen. Stattdessen sollten Sie die Funktion mysql_error() verwenden,
 * um den Fehlertext zu erhalten.
 * 
 * define: Definiert während der Laufzeit eine benannte Konstante.
 * 
 * Warnung: Diese Erweiterung ist seit PHP 5.5.0 als veraltet markiert und wird in der Zukunft entfernt
 * werden.
 */
  function sql_error() {
    return mysql_error();  
  }
  
  define('SQL_ASSOC',MYSQL_ASSOC);
  define('SQL_NUM',MYSQL_NUM);
  define('SQL_BOTH',MYSQL_BOTH);
  
?>