<?php
/**
 * nam.RED
 * Sessionverwaltung
 *
 * @author Erik Reuter
 * @copyright 2006 i-fabrik GmbH
 * @version $Id: session.inc.php,v 1.1 2007/01/22 11:19:43 erik Exp $
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
 *
 */


function sessionOpen ($save_path,$session_name)
{
	// global $db_ses;
    // nothing, the session data will be generated when sessionWrite()
    // is called
}

function sessionClose()
{	
	// global $db_ses;
    // dummy function, not necessary, because
    // we are using a database, so no file has to be closed...
}

function sessionRead($id)
{
    if (!defined(MAXSESSIONTIME))
    	define('MAXSESSIONTIME',1440);
    
    sql_query("DELETE FROM ".DBPREFIX."session WHERE dt<DATE_SUB(now(),INTERVAL ".MAXSESSIONTIME." SECOND)");

    $ergebnis=sql_query("SELECT value FROM ".DBPREFIX."session where id='$id'");
    if (list($sess_data) = sql_fetch_row($ergebnis)) {
  	return $sess_data;
	//return stripslashes($sess_data);
	}
	  else
		return '';
	}

function sessionWrite($id,$sess_data)
{

    $sess_data = addslashes($sess_data);
    sql_query("REPLACE INTO ".DBPREFIX."session (id,value,dt) VALUES ('$id','$sess_data',now())");

}

function sessionDestroy($id)
{
    sql_query("DELETE FROM ".DBPREFIX."session WHERE id='$id'");
    return true;
}

function sessionGC($maxlifetime)
{
    if (!defined(MAXSESSIONTIME))
      define('MAXSESSIONTIME',1440);

    global $db_ses;
    sql_query("DELETE FROM ".DBPREFIX."session WHERE dt<DATE_SUB(now(),INTERVAL ".MAXSESSIONTIME." SECOND)");
}

session_set_save_handler('sessionOpen','sessionClose','sessionRead','sessionWrite','sessionDestroy','sessionGC');

?>
