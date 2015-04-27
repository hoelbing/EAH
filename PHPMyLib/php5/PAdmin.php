<?php
/**
* Portal i-fabrik 
*
* @author R.Kropp (i-fabrik GmbH)
* @copyright 2002 by i-fabrik GmbH
* @version $Id: PAdmin.php,v 1.2 2006/01/24 18:23:07 ralph Exp $
*
*/

//if ( !isset($PHPMyLib_path) ) $PHPMyLib_path = "PHPMyLib/";
//require_once($PHPMyLib_path."PUtil.php");

/**
*	Basisklasse für ein Administrations-Tool mit bereits integrierter Nutzer-&#47;Rechteverwaltung
*
*
*	<b>Konfigurationsparameter:<&#47;b>
*
*	define admin-rights (bit-wise)
*	$var['adm_level']=array(
*	'1'	=>	array('Nutzerverwaltung', "user_admin"),			&#47;&#47;required entry (leave unchanged)
*	'2'	=>	array('Neueintrag', "new_entry"),
*	'Bit'	=>	array('Name im Menü&#47;Rechteverwaltung', "aufzurufende Funktion bei Auswahl")	);
*
*	array with admin levels(bits) that don't represent links in main menu:
*	$var['adm_no_menu']=array(2, 4);
*	
*	Template für das Admin-Tool
*	$var['adm_dwt']=".&#47;templates&#47;adm.dwt";
*
*	ID einer bestehenden Datenbankverbindung
*	$var['dbID']
*
*
*	<b>Benötigte Datenbank-Tabelle:<&#47;b> access
*
*	CREATE TABLE access (
*		id int(11) NOT NULL auto_increment,
*		user varchar(50) NOT NULL default '',
*		passwd varchar(16) NOT NULL default '',
*		level int(11) NOT NULL default '0',
*		comment varchar(255) NOT NULL default '',
*		PRIMARY KEY  (id),
*		UNIQUE KEY user (user)
*	) TYPE=MyISAM;
*
*	Warnung: Nutzer mit der id=1 ist ein Administrator mit sämtlichen Rechten!!
**/
class icAdmin extends PUtil
{

	var $admin=array();				// infos of the current user (id, level, user, comment)

	/**
	*	Template - Objekt
	**/
	var $template;					// admin template

	/**
	*	Objekt vom Typ PText, welches den Titel des aktuellen Menüpunktes enthält (für Platzhalter {title} im Template)
	**/
	var $title="Administration";	// title of the shown page

	/**
	*	Objekt vom Typ PText, welches eine Meldung speichert, die im Feld {msg} des Templates ausgegeben wird
	**/
	var $msg="";					// variable which stores messages for the template field {msg}

	var $errorMsg="";				// variable which stores error messages for the template field {errorMsg}

	/**
	*	Objekt vom Typ PContainer, welches den Inhalt der aktuellen Seite speichert, der im Feld {content} des Templates ausgegeben wird
	**/
	var $content;					// main content (object of type PContainer)

	/**
	*	Konstruktor: startet die Programmausführung
	*	<b>Parameter:</b>
	*		$var - Array mit den Konfigurationsparametern und den übergebenen Variablen (alle Post/Get-Variablen sollten als $var['Bezeichner'] übergeben werden
	**/
	function icAdmin($var)
	{
		// authentification ( get id, level of the user )
		$this->admin=$this->authentify3( $var['dbID'], "Portal (i-fabrik)", "access", "user", "passwd", "id, level, user, comment" );

		// test if user has the right admin level for this menu point
		if ( !empty($var['exec']) && $this->admin[0]!=1 && ($this->admin[1] & (int)$var['exec'])==0 ) $var['exec']=-1;

		// create content object
		$this->content=new PContainer();


		if ( isset($var['exec']) && $var['exec']==-1 )
		{
			// wrong admin rights, print error message
			$this->msg="Fehler: Ihnen fehlen die nötigen Rechte!!";
			unset($var['exec']);
		}elseif ( isset($var['adm_level'][$var['exec']]) && !in_array($var['exec'], $var['adm_no_menu']) )
		{
			// execute choosen menu point
			$this->content->add( $this->$var['adm_level'][$var['exec']][1]($var) );
		}else
		{
			// undefined or no menu point choosen
			if ( !empty($var['exec']) ) $this->msg="Fehler: Undefinierter Menüpunkt. Sollte der Fehler wiederholt auftreten, wenden Sie sich bitte an <a href=\"mailto:info@i-fabrik.de\">info@i-fabrik.de</a>.";
			unset($var['exec']);
		}

		//create main page (menu)
		if ( empty($var['exec']) ) $this->content->add( $this->startpage($var) );
			else $this->content->add( array("<p>", new PLink($_SERVER['PHP_SELF'], "Zur&uuml;ck zur Startseite"), "</p>") );

		// template processing
		if ( !empty($var['adm_level'][$var['exec']][0]) ) $this->title="Administration - ".$var['adm_level'][$var['exec']][0];
		$this->template=new PTemplate("", $var['adm_dwt']);
		$this->template->addComponent("title", $this->title);
		$this->template->addComponent("msg", $this->msg);
		$this->template->addComponent("errorMsg", $this->errorMsg);
		$this->template->addComponent("content", $this->content);


		ob_start();
			echo $this->template->outputstr();
			// output headers (with content length from output buffering
			header ("HTTP/1.0 200");
			header ("Last-Modified: ".@gmdate("D, d M Y H:i:s")." GMT");
			header ("Accept-Ranges: bytes");
			header ("Content-Length: ".@ob_get_length());
			header ("Content-Type: text/html");
		ob_end_flush();
	} //icAdmin


	//create main page (menu)
	function startpage($var)
	{
		$content=new PContainer();
		$count=0;

		$list=new PList();
		foreach ($var['adm_level'] as $bit=>$description)
		{
			if ( in_array($bit, $var['adm_no_menu']) ) continue;
			if ( $this->admin[0]==1 || ($this->admin[1] & $bit) )
			{
				$list->add( new PLink("$_SERVER[PHP_SELF]?var[exec]=$bit", $description[0]) );
				$level=$bit;
				$count++;
			}
		}
		if ($count>1)
		{
			$content->add("<b>Menü</b>");
			$content->add($list);
		}elseif ($count==1)
		{
			header("Location: $_SERVER[PHP_SELF]?var[exec]=$level");
			exit();
		}else $this->msg="Fehler: Ihnen fehlen die Rechte für dieses Tool. Bitte wenden Sie sich an den Administrator.";
		return $content;
	} //startpage


	// create form for user administration
	function user_admin($var)
	{
		// Functionality
		$output=new PContainer();
		$edit="neuen Account anlegen:";
		$button="Anlegen";

		switch ($var['action']):
		// new admin
		case "Anlegen":
			for($i=0;$i<count($var['adm_level']);$i++) $level+=$var["level_$i"];
			$sql="INSERT INTO access SET user='$var[user]', passwd=PASSWORD('$var[pass]'), level='$level'";
			@mysql_query($sql);
			if ( mysql_error()!="" ) $this->errorMsg="Neuer User konnte nicht erstellt werden!!";
				else $this->errorMsg="Neuer User wurde erstellt!!";
			break;
		// Form for editing an existing admin
		case "edit":
			$edit="Account von $var[user] editieren:";
			$button="Ändern";
			$output->add( new PInput("hidden", "var[ID]", $var['ID']) );
			break;
		case "Ändern":
			if ($this->admin[0]==$var['ID']) break;		// eigener account darf nicht geändert werden
			for($i=0;$i<count($var['adm_level']);$i++) $level+=$var["level_$i"];
			$sql="UPDATE access SET user='$var[user]', passwd=PASSWORD('$var[pass]'), level='$level' WHERE id='$var[ID]'";
			@mysql_query($sql);
			if ( mysql_error()!="" ) $this->errorMsg="Änderungen konnten nicht übernommen werden!!";
				else $this->errorMsg="Änderungen wurden übernommen!!";
			break;
		// delete an admin
		case "delete":
			if ($this->admin[0]==$var['ID']) break;		// eigener account darf nicht geändert werden
			$sql="DELETE FROM access WHERE id='$var[ID]'";
			@mysql_query($sql);
			if ( mysql_error()!="" ) $this->errorMsg="User konnte nicht gelöscht werden!!";
				else $this->errorMsg="User wurde gelöscht!!";
			break;
		// change admin rights
		case "Rechte setzen":
			foreach ($var as $key =>$value)
			{	// count bits
				$key=explode("_", $key);
				if ($key[0]=="level" && $key[2]!="") $a_Level[$key[2]]+=$value;
			}
			foreach ($a_Level as $ID=>$value)
			{	// write bits to database
				if ( $ID==$this->admin[0] && ($value & 1)==0 && $ID>1 ) $value++;	// eigene Benutzerverwaltungsrechte lassen sich nicht entfernen
				$sql="UPDATE access SET level='$value' WHERE id='$ID'";
				@mysql_query($sql);
				if ( mysql_error()!="" ) $this->errorMsg="Rechte konnten nicht gesetzt werden!!";
					else $this->errorMsg="echte wurden neu gesetzt!!";
			}
			break;
		endswitch;

		// Output
		//$output->add( "<br><br><b>Benutzerverwaltung:</b><br><br>" );

		$table=new PTable($tl_adm_new);

		// neuen Admin anlegen
			$row[0]="<b>$edit</b>";
		$table->addRow($row);
			$row[0]="Login";
			$row[1]=new PInput("text", "var[user]", "");
		$table->addRow($row);
			$row[0]="Password";
			$row[1]=new PInput("text", "var[pass]", "");
		$table->addRow($row);
			$row[0]="Level";
			for ($i=0;$i<count($var['adm_level']);$i++) $row[(1+$i)]=new PInput("checkbox", "var[level_$i]", pow(2,$i));
		$table->addRow($row);
			unset($row);
			$row[0]=new PContainer();
			$row[0]->add( new PTag("center") );
			$row[0]->add( new PInput("submit", "var[action]", $button) );
			if ($var['action']=="edit") $row[1]=new PInput("submit", "var[action]", "Editierung abbrechen");
		$table->addRow($row);
		$output->add($table);
		$output->add("<br><br>");

		$table=new PTable($tl_adm_list);
		//$table->addRow( array(new PText("&nbsp;")) );
			$head[0]="<b>ID</b>";
			$head[1]="<b>Login</b>";
			for ($i=0;$i<count($var['adm_level']);$i++) $head[2+$i]="<b>Level $i</b>";
			$head[]="&nbsp;";
		//$table->addRow($head);

		if ($this->admin[0]>1) $sql="SELECT id, user, level FROM access WHERE id>0";
			else $sql="SELECT id, user, level FROM access";

		$line=0;
		$sql=@mysql_query($sql);
		while ( $res=@mysql_fetch_row($sql) )
		{
			if ( (($line++)%20)==0 ) $table->addRow($head);
			$row[0]=new PContainer();
			$row[0]->add( $res[0] );
			$row[0]->add( new PInput("hidden", "var[level_x_$res[0]]", "0") );
			$row[1]=$res[1];
			for ($i=0;$i<count($var['adm_level']);$i++)
			{
				if ( $res[2] & pow(2,$i) ) $checked="checked"; else $checked="";
				$row[2+$i]=new PInput( "checkbox", "var[level_".$i."_$res[0]]", pow(2,$i), array($checked=>"") );
			}
			$row[2+$i]=new PLink( "$_SERVER[PHP_SELF]?var[exec]=1&var[action]=edit&var[ID]=$res[0]&var[user]=$res[1]", "Edit");
			$row[3+$i]=new PLink( "$_SERVER[PHP_SELF]?var[exec]=1&var[action]=delete&var[ID]=$res[0]", "Delete" );
			if ( $res[0]==$this->admin[0] ) $row[3+$i]="&nbsp;";
			$table->addRow($row);
		}
		unset($row);
		$row[0]=new PContainer();
		$row[0]->add( new PTag("center") );
		$row[0]->add( new PInput("submit", "var[action]", "Rechte setzen") );
		$table->addRow($row);
		$output->add($table);

		// Level-Erklärung
		$table=new PTable();
		if ($this->admin[0]>1) $table->addRow( array("<b>Level</b>", "&nbsp;", "<b>Funktion</b>") );
			else $table->addRow( array("<b>Admin-Level</b>", "&nbsp;", "<b>Funktion</b>", "&nbsp;", "<b>Bit</b>") );
		$i=0;
		foreach ( $var['adm_level'] as $bit=>$description )
		{
			if ($this->admin[0]>1) $table->addRow( array("$i", "&nbsp;&nbsp;", $description[0]) );
				else $table->addRow( array("$i", "&nbsp;&nbsp;", $description[0], "&nbsp;&nbsp;", $bit) );
			$i++;
		}
		$output->add("<br>");
		$output->add($table);

		$form=new PForm( "$_SERVER[PHP_SELF]?var[exec]=1", "POST" );
		$form->add($output);
		return $form;
	} //user_admin


} //class icAdmin

?>