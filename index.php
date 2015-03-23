<?php
/**
* Postbuch - Universitaet Leipzig
* Hauptdatei
*
* @author Erik Reuter
* @copyright 2007 i-fabrik GmbH
* @version $Id: index.php,v 1.41 2007/02/12 13:14:16 heiko Exp $
*
*/

	include_once('include_main.inc.php');

	session_start();
	$sessionid=session_id();
	session_register('nutzer_id');
	session_register('postbuch_modus');
	session_register('filterdaten');

	$_FORMVARS=array_merge($_SERVER,$_COOKIE,$_GET,$_FILES,$_POST,$_SESSION);
	$_FORMVARS=trim_array($_FORMVARS);

	$tpl_rahmen=new PTemplate(NULL,$templatefiles['rahmen']);

	$anmeldefehler=false;

	if (!empty($_FORMVARS['action'])) {
		switch ($_FORMVARS['action']) {
			case 'anmelden':
				$sql_abfrage="SELECT * FROM ".DBPREFIX."nutzer WHERE login='".$_FORMVARS['login']."' AND passwort=MD5('".$_FORMVARS['passwort']."')";
				$sql_ergebnis=sql_query($sql_abfrage);

				if ($sql_daten=sql_fetch_array($sql_ergebnis,SQL_ASSOC)) {
					$nutzer_id=$_FORMVARS['nutzer_id']=$_SESSION['nutzer_id']=$sql_daten['nutzer_id'];
					sql_query("UPDATE ".DBPREFIX."nutzer SET sessionid='".$sessionid."' WHERE nutzer_id='".$_FORMVARS['nutzer_id']."'");
					sql_query("UPDATE ".DBPREFIX."nutzer SET sessionid=NOW() WHERE nutzer_id='".$_FORMVARS['nutzer_id']."'");
				} else
					$anmeldefehler=true;
				break;

			case 'abmelden':
				$nutzer_id=$_FORMVARS['nutzer_id']=$_SESSION['nutzer_id']=NULL;
	        	break;
		}
	}






	if (empty($_FORMVARS['nutzer_id'])) {
		$tpl_inhalt=new PTemplate(NULL,$templatefiles['login']);
		$tpl_inhalt->addComponent('formaction',new PText($_FORMVARS['PHP_SELF'].'?action=anmelden&PHPSESSID='.$sessionid));
		$tpl_rahmen->addComponent('inhalt',$tpl_inhalt);
		$tpl_rahmen->addComponent('body_id',new PText('login'));

	} else {

		if (empty($_FORMVARS['modus']))
			$_FORMVARS['modus']=$nutzereinstellung['startmodus'];

		if (empty($_FORMVARS['postbuch_modus']))
			$postbuch_modus=$_FORMVARS['postbuch_modus']=$_SESSION['postbuch_modus']='eingang';

		$fehlerfilter = 0;

		// Start : Auswertung Aktionen
		if (!empty($_FORMVARS['action'])) {
			switch ($_FORMVARS['action']) {
				case 'loeschen':
					$sql_abfrage  ="select * from ".DBPREFIX."postbuch where postbuch_id='".$_FORMVARS['postbuch_id']."'";
					$sql_ergebnis =sql_query($sql_abfrage);
					$sql_daten    =sql_fetch_array($sql_ergebnis,SQL_ASSOC);

					if ($sql_daten['einrichtung_id']==$_FORMVARS['einrichtung_id']) {
						sql_query("delete from ".DBPREFIX."postbuch where postbuch_id='".$_FORMVARS['postbuch_id']."'");
						sql_query("delete from ".DBPREFIX."bemerkung where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					}
					break;

				case 'filtersetzen':
					// Test ob Datum korrekt
					if (!empty($_FORMVARS['startdatum'])) {
						if (!($datumneu=test_datum($_FORMVARS['startdatum'])))
							$fehlerfilter|=1;
						else
							$_FORMVARS['startdatum']=$datumneu;
					}

					if (!empty($_FORMVARS['enddatum'])) {
						if (!($datumneu=test_datum($_FORMVARS['enddatum'])))
							$fehlerfilter|=2;
						else
						$_FORMVARS['enddatum']=$datumneu;
					}

					if ($fehlerfilter==0 && !empty($_FORMVARS['startdatum']) && !empty($_FORMVARS['enddatum'])) {
						if (convert_sqltime($_FORMVARS['enddatum'])<convert_sqltime($_FORMVARS['startdatum']))
							$fehlerfilter|=3;
					}

					if ($fehlerfilter)
						$_FORMVARS['modus']='filter';
					else {
						$_FORMVARS['filterdaten']['medium']     =$_FORMVARS['medium'];
						$_FORMVARS['filterdaten']['startdatum'] =convert_sqltime($_FORMVARS['startdatum']);
						$_FORMVARS['filterdaten']['enddatum']   =convert_sqltime($_FORMVARS['enddatum']);
						$_FORMVARS['filterdaten']['bezeichnung']=$_FORMVARS['bezeichnung'];
						$_FORMVARS['filterdaten']['plz']        =$_FORMVARS['plz'];
						$_FORMVARS['filterdaten']['ort']        =$_FORMVARS['ort'];
						$_FORMVARS['filterdaten']['land']       =$_FORMVARS['land'];
						$_FORMVARS['filterdaten']['fax']        =$_FORMVARS['fax'];
						$_FORMVARS['filterdaten']['email']      =$_FORMVARS['email'];
						$_SESSION['filterdaten']                =$_FORMVARS['filterdaten']; // in Session ablegen
					}
					break;

				case 'filterloeschen':
					unset($_FORMVARS['filterdaten'][$_FORMVARS['filter']]);
					$_SESSION['filterdaten']=$_FORMVARS['filterdaten'];
					break;

				case 'nutzereinstellung':
					$fehlernutzereinstellung = 0;

					if ($_FORMVARS['listenmodus']=='liste' && $_FORMVARS['eintragliste']<1)
						$fehlernutzereinstellung|=1;

					if ($_FORMVARS['listenmodus']=='tage' && $_FORMVARS['eintragtag']<1)
						$fehlernutzereinstellung|=2;

					if ((!empty($_FORMVARS['passwort']) || !empty($_FORMVARS['passwort2'])) && $_FORMVARS['passwort']!=$_FORMVARS['passwort2'])
						$fehlernutzereinstellung|=4;

					if ($fehlernutzereinstellung)
						$_FORMVARS['modus']='einstellungen';
					else {
						sql_query("REPLACE INTO ".DBPREFIX."nutzer_einstellung(nutzer_id,varname,wert) VALUES('".$_FORMVARS['nutzer_id']."','listenmodus','".$_FORMVARS['listenmodus']."')");
						sql_query("REPLACE INTO ".DBPREFIX."nutzer_einstellung(nutzer_id,varname,wert) VALUES('".$_FORMVARS['nutzer_id']."','eintragliste','".$_FORMVARS['eintragliste']."')");
						sql_query("REPLACE INTO ".DBPREFIX."nutzer_einstellung(nutzer_id,varname,wert) VALUES('".$_FORMVARS['nutzer_id']."','eintragtag','".$_FORMVARS['eintragtag']."')");
						sql_query("REPLACE INTO ".DBPREFIX."nutzer_einstellung(nutzer_id,varname,wert) VALUES('".$_FORMVARS['nutzer_id']."','schriftgroesse','".$_FORMVARS['schriftgroesse']."')");
						sql_query("REPLACE INTO ".DBPREFIX."nutzer_einstellung(nutzer_id,varname,wert) VALUES('".$_FORMVARS['nutzer_id']."','farbe','".$_FORMVARS['farbe']."')");

						if (!empty($_FORMVARS['passwort']))
							sql_query("UPDATE ".DBPREFIX."nutzer SET passwort=MD5('".$_FORMVARS['passwort']."') WHERE nutzer_id='".$_FORMVARS['nutzer_id']."'");
					}
					break;

				case 'nutzerloeschen':
					if ($_FORMVARS['nutzer_id']!=$_FORMVARS['cnutzer_id']) {
						$sql_abfrage ="SELECT * FROM ".DBPREFIX."nutzer WHERE nutzer_id='".$_FORMVARS['nutzer_id']."'";
						$sql_ergebnis=sql_query($sql_abfrage);
						$sql_daten   =sql_fetch_array($sql_ergebnis,SQL_ASSOC);

						$berechtigung=$nutzerberechtigung[$sql_daten['nutzertyp']];
						$anzahl      =count($menuedaten);
						$loeschenok  =false;

						for ($l=0;$l<$anzahl;$l++) {
							if ($menuedaten[$l]['modus']=='nutzer') {
								if (in_array($menuedaten[$l]['berechtigung'],$berechtigung))
									$loeschenok=true;
								break;
							}
						}

						if ($loeschenok) {
							sql_query("DELETE FROM ".DBPREFIX."nutzer WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");
							sql_query("DELETE FROM ".DBPREFIX."einrichtung_nutzer_link WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");
							sql_query("DELETE FROM ".DBPREFIX."nutzer_einstellung WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");
						}
					}
					break;
			}
		} // Ende : Auswertung Aktionen


		// Nutzerdaten einlesen
		$sql_abfrage = "SELECT * FROM ".DBPREFIX."nutzer WHERE nutzer_id='".$_FORMVARS['nutzer_id']."'";
		$sql_ergebnis= sql_query($sql_abfrage);
		$sql_daten   = sql_fetch_array($sql_ergebnis,SQL_ASSOC);

		$berechtigung=$nutzerberechtigung[$sql_daten['nutzertyp']];
		$anzahl      =count($menuedaten);
		$menue_ok    =false;

		for ($l=0;$l<$anzahl;$l++) {
			if ($menuedaten[$l]['modus']==$_FORMVARS['modus']) {
				if (in_array($menuedaten[$l]['berechtigung'],$berechtigung))
					$menue_ok=true;
				break;
			}
		}

		if (!$menue_ok)
			$_FORMVARS['modus']='liste';

		$sql_abfrage  = "SELECT * FROM ".DBPREFIX."nutzer_einstellung WHERE nutzer_id='".$_FORMVARS['nutzer_id']."'";
		$sql_ergebnis = sql_query($sql_abfrage);
		while ($sql_daten=sql_fetch_array($sql_ergebnis,SQL_ASSOC))
			$nutzereinstellung[$sql_daten['varname']] = $sql_daten['wert'];

		// Auswahl der Einrichtung
		$tpl_einrichtung       = new PTemplate(NULL, $templatefiles['einrichtung']);
		$tpl_einr_selectoption = $tpl_einrichtung->extractBlock('SelectOption_Einrichtung');

		$sql_abfrage  = "SELECT E.* FROM ".DBPREFIX."einrichtung AS E,".DBPREFIX."einrichtung_nutzer_link AS L WHERE L.einrichtung_id=E.einrichtung_id AND L.nutzer_id='".$_FORMVARS['nutzer_id']."' ORDER BY bezeichnung";
		$sql_ergebnis = sql_query($sql_abfrage);

		if (sql_num_rows($sql_ergebnis)>1) {
			$tpl_einrichtung->extractBlock('Einrichtung');
			$tpl_einrichtung->addComponent('formaction',new PText($_FORMVARS['PHP_SELF'].'?modus='.$_FORMVARS['modus'].'&PHPSESSID='.$sessionid));

			$selectoptionen = eval($tpl_einr_selectoption->outputStr());
			$select         = new PSelect('einrichtung_id', $selectoptionen,$_FORMVARS['einrichtung_id']);

			while ($sql_daten=sql_fetch_array($sql_ergebnis,SQL_ASSOC)) {
				if (empty($_FORMVARS['einrichtung_id']))
					$_FORMVARS['einrichtung_id']=$sql_daten['einrichtung_id'];

				$select->addEntry($sql_daten['bezeichnung'], $sql_daten['einrichtung_id']);
			}

			$tpl_einrichtung->addComponent('select_einrichtung', $select);
		} else {
			$tpl_einrichtung->extractBlock('EinrichtungForm');

			$sql_daten = sql_fetch_array($sql_ergebnis, SQL_ASSOC);

			$tpl_einrichtung->addComponent('einrichtung', new PText($sql_daten['bezeichnung']));

			$_FORMVARS['einrichtung_id'] = $sql_daten['einrichtung_id'];
		}

		$tpl_rahmen->addComponent('einrichtung', $tpl_einrichtung);

		switch ($_FORMVARS['modus']) {
			case 'nutzerbearbeiten':
				if ($_FORMVARS['cnutzer_id']>0) {
					$sql_abfrage  = "SELECT * FROM ".DBPREFIX."nutzer WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'";
					$sql_ergebnis = sql_query($sql_abfrage);
					$sql_daten    = sql_fetch_array($sql_ergebnis,SQL_ASSOC);

					$_FORMVARS['anzeigename'] = $sql_daten['anzeigename'];
					$_FORMVARS['titel']       = $sql_daten['titel'];
					$_FORMVARS['vorname']     = $sql_daten['vorname'];
					$_FORMVARS['name']        = $sql_daten['name'];
					$_FORMVARS['login']       = $sql_daten['login'];
					$_FORMVARS['nutzertyp']   = $sql_daten['nutzertyp'];

					// Zuordnungen auslesen
					$_FORMVARS['ceinrichtung_id'] = array();

					$sql_abfrage  = "SELECT * from ".DBPREFIX."einrichtung_nutzer_link WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'";
					$sql_ergebnis = sql_query($sql_abfrage);

					$_FORMVARS['ceinrichtung_id'][] = 0;

					while ($sql_daten=sql_fetch_assoc($sql_ergebnis))
						$_FORMVARS['ceinrichtung_id'][] = $sql_daten['einrichtung_id'];
				} else {
					$_FORMVARS['ceinrichtung_id']   = array();
					$_FORMVARS['ceinrichtung_name'] = array();
				}

			case 'nutzerbearbeiten1':
				$anzahl = count($_FORMVARS['ceinrichtung_id']);

				foreach($_FORMVARS as $key=>$value) {
					if (substr($key,0,13)=='btn_loeschen_') {
						$position = abs(substr($key,13));

						for ($l=$position;$l<$anzahl-1;$l++) {
							$_FORMVARS['ceinrichtung_id'][$l]   = $_FORMVARS['ceinrichtung_id'][$l+1];
							$_FORMVARS['ceinrichtung_name'][$l] = $_FORMVARS['ceinrichtung_name'][$l+1];
						}

						$anzahl--;
						unset($_FORMVARS['ceinrichtung_id'][$anzahl]);
						unset($_FORMVARS['ceinrichtung_name'][$anzahl]);
					}
				}

				// evtl. neuen Einrichtung einbauen
        		if ($_FORMVARS['ceinrichtung_id'][0]>0 || ($_FORMVARS['ceinrichtung_id'][0]==-1 && !empty($_FORMVARS['ceinrichtung_name'][0]))) {
					for ($l=$anzahl-1;$l>=0;$l--) {
						$_FORMVARS['ceinrichtung_id'][$l+1]   = $_FORMVARS['ceinrichtung_id'][$l];
						$_FORMVARS['ceinrichtung_name'][$l+1] = $_FORMVARS['ceinrichtung_name'][$l];
					}
					$anzahl++;
				}

				if (empty($_FORMVARS['btn_save']))
					$fehler=65536;
				else {
					$fehler=0;

					if (empty($_FORMVARS['name']))
						$fehler|=1;

					$sql_abfrage  = "SELECT * FROM ".DBPREFIX."nutzer WHERE login='".$_FORMVARS['login']."' AND nutzer_id<>'".$_FORMVARS['cnutzer_id']."'";
					$sql_ergebnis = sql_query($sql_abfrage);

					if(sql_fetch_assoc($sql_ergebnis))
						$fehler|=2;

					if ($_FORMVARS['cnutzer_id']==-1 && empty($_FORMVARS['passwort']))
						$fehler|=4;

					if ($_FORMVARS['passwort']!=$_FORMVARS['passwort2'])
						$fehler|=4;

					if ($anzahl<2)
						$fehler|=8;
				}

				if ($fehler) {
					$tpl_rahmen->addComponent('menue',menue_ausfuellen('nutzer'));

					$tpl_inhalt = new PTemplate(NULL, $templatefiles['nutzerbearbeiten']);

					$tpl_inhalt->addComponent('formaction', new PText($_FORMVARS['PHP_SELF'].'?PHPSESSID='.$sessionid));
					$tpl_inhalt->addComponent('hidden',     new PInput('hidden','cnutzer_id',$_FORMVARS['cnutzer_id']));
					$tpl_inhalt->addComponent('hidden',     new PInput('hidden','modus','nutzerbearbeiten1'));
					$tpl_inhalt->addComponent('hidden',     new PInput('hidden','einrichtung_id',$_FORMVARS['einrichtung_id']));

					if ($fehler & 1)
						$tpl_inhalt->addComponent('fehler_name', new PText(' fehler'));

					if ($fehler & 2)
						$tpl_inhalt->addComponent('fehler_login', new PText(' fehler'));

					if ($fehler & 4)
						$tpl_inhalt->addComponent('fehler_passwort', new PText(' fehler'));

					if ($fehler & 8)
						$tpl_inhalt->addComponent('fehler_einrichtung', new PText(' fehler'));

					if ($_FORMVARS['nutzer_id']==-1)
						$tpl_inhalt->addComponent('seitentitel', new PText('Neueintrag'));
					else
						$tpl_inhalt->addComponent('seitentitel', new PText('Daten bearbeiten'));

					$tpl_selectoption = $tpl_inhalt->extractBlock('SelectOption_Typ');
					$selectoptionen   = eval($tpl_selectoption->outputStr());
					$select           = new PSelect('nutzertyp',$selectoptionen,$_FORMVARS['nutzertyp']);
					foreach($feld_nutzertyp as $key=>$value)
						$select->addEntry($value,$key);
					$tpl_inhalt->addComponent('select_typ_select',$select);

					$tpl_inhalt->addComponent('anzeigename', new PText(htmlentities(stripslashes($_FORMVARS['anzeigename']))));
					$tpl_inhalt->addComponent('titel',       new PText(htmlentities(stripslashes($_FORMVARS['titel']))));
					$tpl_inhalt->addComponent('vorname',     new PText(htmlentities(stripslashes($_FORMVARS['vorname']))));
					$tpl_inhalt->addComponent('name',        new PText(htmlentities(stripslashes($_FORMVARS['name']))));
					$tpl_inhalt->addComponent('login',       new PText(htmlentities(stripslashes($_FORMVARS['login']))));

					// Liste der Einrichtungen
					$tpl_selectoption = $tpl_inhalt->extractBlock('SelectOption_Einrichtung');
					$selectoptionen   = eval($tpl_selectoption->outputStr());
					$select           = new PSelect('ceinrichtung_id[0]',$selectoptionen);
					$select->addEntry('* Auswahl *','0');
					$sql_abfrage  = "SELECT * FROM ".DBPREFIX."einrichtung ORDER BY bezeichnung";
					$sql_ergebnis = sql_query($sql_abfrage);
					while ($sql_daten=sql_fetch_assoc($sql_ergebnis))
						if (!in_array($sql_daten['einrichtung_id'],$_FORMVARS['ceinrichtung_id']))
							$select->addEntry($sql_daten['bezeichnung'],$sql_daten['einrichtung_id']);
					$select->addEntry('* Neueintrag *','-1');
					$tpl_inhalt->addComponent('select_einrichtung',$select);

					// Weitere Einrichtungen auflisten
					$tpl_einrichtungeintrag = $tpl_inhalt->extractBlock('EinrichtungEintrag');
					$anzahl                 = count($_FORMVARS['ceinrichtung_id']);
					for ($l=1;$l<$anzahl;$l++) {
						$tpl_inhalt->addComponent('hidden', new PInput('hidden','ceinrichtung_id['.$l.']',$_FORMVARS['ceinrichtung_id'][$l]));
						$tpl_einrichtungeintrag->parse();
						$tpl_einrichtungeintrag->addComponent('nr', new PText($l));

						if ($_FORMVARS['ceinrichtung_id'][$l]==-1)
							$tpl_einrichtungeintrag->addComponent('value', new PText($_FORMVARS['ceinrichtung_name'][$l]));
						else {
							$sql_abfrage  = "SELECT * FROM ".DBPREFIX."einrichtung WHERE einrichtung_id='".$_FORMVARS['ceinrichtung_id'][$l]."'";
							$sql_ergebnis = sql_query($sql_abfrage);
							$sql_daten    = sql_fetch_assoc($sql_ergebnis);
							$tpl_einrichtungeintrag->addComponent('value', new PText($sql_daten['bezeichnung']));
						}

						$tpl_inhalt->addComponent('EinrichtungEintrag',$tpl_einrichtungeintrag);
					}
					$tpl_rahmen->addComponent('inhalt',$tpl_inhalt);

					break;
				} else {
					if ($_FORMVARS['cnutzer_id']==-1) {
						sql_query("INSERT ".DBPREFIX."nutzer(status) VALUES('A')");
						$_FORMVARS['cnutzer_id'] = sql_insert_id();
					}

					sql_query("UPDATE ".DBPREFIX."nutzer SET anzeigename='".$_FORMVARS['anzeigename']."' WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");
					sql_query("UPDATE ".DBPREFIX."nutzer SET titel='".$_FORMVARS['titel']."' WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");
					sql_query("UPDATE ".DBPREFIX."nutzer SET vorname='".$_FORMVARS['vorname']."' WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");
					sql_query("UPDATE ".DBPREFIX."nutzer SET name='".$_FORMVARS['name']."' WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");
					sql_query("UPDATE ".DBPREFIX."nutzer SET login='".$_FORMVARS['login']."' WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");

					if (!empty($_FORMVARS['passwort']))
						sql_query("UPDATE ".DBPREFIX."nutzer SET passwort=MD5('".$_FORMVARS['passwort']."') WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");

					sql_query("UPDATE ".DBPREFIX."nutzer SET nutzertyp='".$_FORMVARS['nutzertyp']."' WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");

					// Einrichtungen
					sql_query("DELETE FROM ".DBPREFIX."einrichtung_nutzer_link WHERE nutzer_id='".$_FORMVARS['cnutzer_id']."'");
					for ($l=1;$l<$anzahl;$l++) {
						if ($_FORMVARS['ceinrichtung_id'][$l]==-1) {
							sql_query("INSERT ".DBPREFIX."einrichtung(bezeichnung) VALUES('".$_FORMVARS['ceinrichtung_name'][$l]."')");
							$_FORMVARS['ceinrichtung_id'][$l] = sql_insert_id();
						}

						sql_query("INSERT ".DBPREFIX."einrichtung_nutzer_link(nutzer_id,einrichtung_id) VALUES('".$_FORMVARS['cnutzer_id']."','".$_FORMVARS['ceinrichtung_id'][$l]."')");
						echo sql_error();
					}
				}

			case 'nutzer':
				$tpl_rahmen->addComponent('menue',menue_ausfuellen('nutzer'));

				$tpl_inhalt = new PTemplate(NULL,$templatefiles['nutzer']);
				$tpl_inhalt->addComponent('formaction_loeschen',new PText($_FORMVARS['PHP_SELF'].'?PHPSESSID='.$sessionid));

				$tpl_buchstabe = $tpl_inhalt->extractBlock('Buchstabe');
				$tpl_eintrag   = $tpl_inhalt->extractBlock('Eintrag');

				$sql_abfrage  = "SELECT * FROM ".DBPREFIX."nutzer ORDER BY name";
				$sql_ergebnis = sql_query($sql_abfrage);

				$akt_buchstabe = '';

				while ($sql_daten=sql_fetch_array($sql_ergebnis,SQL_ASSOC)) {
					if (($buchstabe=strtoupper(substr($sql_daten['name'],0,1)))!=$akt_buchstabe) {
						if ($akt_buchstabe!='')
							$tpl_inhalt->addComponent('inhalt',$tpl_buchstabe);

						$akt_buchstabe=$buchstabe;

						$tpl_buchstabe->parse();
						$tpl_buchstabe->addComponent('buchstabe',       new PText($akt_buchstabe));
						$tpl_buchstabe->addComponent('buchstabe_klien', new PText(strtolower($akt_buchstabe)));
					}

					$tpl_eintrag->parse();
					$tpl_eintrag->addComponent('titel', new PText($sql_daten['name']));
					$zusatzinfo = trim($sql_daten['titel'].' '.$sql_daten['vorname']);

					if (!empty($zusatzinfo))
						$tpl_eintrag->addComponent('titel', new PText(', '.$zusatzinfo));

					$tpl_eintrag->addComponent('cnutzer_id',      new PText($sql_daten['nutzer_id']));
					$tpl_eintrag->addComponent('link_bearbeiten', new PText($_FORMVARS['PHP_SELF'].'?modus=nutzerbearbeiten&cnutzer_id='.$sql_daten['nutzer_id'].'&PHPSESSID='.$sessionid));

					$tpl_buchstabe->addComponent('eintrag',$tpl_eintrag);

				}

				$tpl_inhalt->addComponent('inhalt',$tpl_buchstabe);

				$tpl_blaettern=new PTemplate(NULL,$templatefiles['blaettern']);

				$tpl_blaettern->extractBlock('ButtonVorOff');
				$tpl_blaettern->extractBlock('ButtonVor');
				$tpl_blaettern->extractBlock('ButtonBearbeiten');
				$tpl_blaettern->extractBlock('ButtonLoeschen');
				$tpl_blaettern->extractBlock('ButtonZurueck');
				$tpl_blaettern->extractBlock('ButtonZurueckOff');
				$tpl_blaettern->extractBlock('ButtonBeantworten');
				$tpl_blaettern->extractBlock('ButtonWeiterleiten');
				$tpl_blaettern->addComponent('link_neueintrag',new PText($_FORMVARS['PHP_SELF'].'?modus=nutzerbearbeiten&cnutzer_id=-1&PHPSESSID='.$sessionid));

				$tpl_rahmen->addComponent('blaettern',$tpl_blaettern);

				$tpl_rahmen->addComponent('inhalt',$tpl_inhalt);

				break;

			case 'einstellungen':
				$tpl_rahmen->addComponent('menue', menue_ausfuellen('einstellungen'));

				$tpl_inhalt = new PTemplate(NULL, $templatefiles['einstellungen']);

				$tpl_inhalt->addComponent('formaction', new PText($_FORMVARS['PHP_SELF'].'?PHPSESSID='.$sessionid));
				$tpl_inhalt->addComponent('hidden',     new PInput('hidden','einrichtung_id',$_FORMVARS['einrichtung_id']));
				$tpl_inhalt->addComponent('hidden',     new PInput('hidden','modus','liste'));
				$tpl_inhalt->addComponent('hidden',     new PInput('hidden','action','nutzereinstellung'));

				$tpl_selectoptionen = $tpl_inhalt->extractBlock('SelectOption_Typ');
				$selectoptionen     = eval($tpl_selectoptionen->outputStr());

				if ($fehlernutzereinstellung)
					$select = new PSelect('listenmodus', $selectoptionen,$_FORMVARS['listenmodus']);
				else
					$select = new PSelect('listenmodus', $selectoptionen,$nutzereinstellung['listenmodus']);

				foreach($feld_listenmodus as $key=>$value)
					$select->addEntry($value,$key);

				$tpl_inhalt->addComponent('select_typ_select',$select);

				$tpl_selectoptionen = $tpl_inhalt->extractBlock('SelectOption_Farbe');
				$selectoptionen = eval($tpl_selectoptionen->outputStr());

				if ($fehlernutzereinstellung) {
					$select = new PSelect('farbe', $selectoptionen,$_FORMVARS['farbe']);
					$tpl_inhalt->addComponent('farbe',new PText($_FORMVARS['farbe']));
				} else {
					$select = new PSelect('farbe', $selectoptionen,$nutzereinstellung['farbe']);
					$tpl_inhalt->addComponent('farbe',new PText($nutzereinstellung['farbe']));
				}

				foreach($feld_farben as $value)
					$select->addEntry('#'.$value,$value);


				$tpl_inhalt->addComponent('select_farbe',$select);

				$tpl_selectoptionen = $tpl_inhalt->extractBlock('SelectOption_Schriftgroesse');
				$selectoptionen = eval($tpl_selectoptionen->outputStr());

				if ($fehlernutzereinstellung)
					$select = new PSelect('schriftgroesse', $selectoptionen,$_FORMVARS['schriftgroesse']);
				else
					$select = new PSelect('schriftgroesse', $selectoptionen,$nutzereinstellung['schriftgroesse']);

				foreach($feld_schriftgroessen as $key=>$value)
					$select->addEntry($value[0],$key);

				$tpl_inhalt->addComponent('select_schriftgroesse',$select);

				if ($fehlernutzereinstellung) {
					$tpl_inhalt->addComponent('eintragliste', new PText($_FORMVARS['eintragliste']));
					$tpl_inhalt->addComponent('eintragtag',   new PText($_FORMVARS['eintragtag']));
				} else {
					$tpl_inhalt->addComponent('eintragliste', new PText($nutzereinstellung['eintragliste']));
					$tpl_inhalt->addComponent('eintragtag',   new PText($nutzereinstellung['eintragtag']));
				}

				$tpl_rahmen->addComponent('inhalt',$tpl_inhalt);
				break;

			case 'ausdruck':
				$tpl_rahmen->addComponent('menue', menue_ausfuellen('ausdruck'));

				$tpl_inhalt = new PTemplate(NULL, $templatefiles['ausdruck']);
				$tpl_inhalt->addComponent('formaction', new PText('liste_pdf.php'));
				$tpl_inhalt->addComponent('hidden',     new PInput('hidden','einrichtung_id',$_FORMVARS['einrichtung_id']));

		        $tpl_rahmen->addComponent('inhalt', $tpl_inhalt);
				break;

			// Festlegung des Suchfilters
			case 'filter':
				$tpl_rahmen->addComponent('menue', menue_ausfuellen('filter'));

				$tpl_inhalt = new PTemplate(NULL, $templatefiles['filter']);
				$tpl_inhalt->addComponent('formaction', new PText($_FORMVARS['PHP_SELF'].'?PHPSESSID='.$sessionid));
				$tpl_inhalt->addComponent('hidden',     new PInput('hidden','einrichtung_id',$_FORMVARS['einrichtung_id']));
				$tpl_inhalt->addComponent('hidden',     new PInput('hidden','modus','liste'));
				$tpl_inhalt->addComponent('hidden',     new PInput('hidden','action','filtersetzen'));

				if ($fehlerfilter) {
					if (empty($_FORMVARS['medium']))
						$tpl_inhalt->addComponent('extraalle', new PText(' checked'));
					else
						$tpl_inhalt->addComponent('extra'.$_FORMVARS['medium'], new PText(' checked'));

					$tpl_inhalt->addComponent('startdatum', new PText($_FORMVARS['startdatum']));
					$tpl_inhalt->addComponent('enddatum',   new PText($_FORMVARS['enddatum']));

					if ($fehlerfilter & 1)
						$tpl_inhalt->addComponent('fehler_startdatum', new PText(' fehler'));

					if ($fehlerfilter & 2)
						$tpl_inhalt->addComponent('fehler_enddatum', new PText(' fehler'));

					$tpl_inhalt->addComponent('bezeichnung', new PText($_FORMVARS['bezeichnung']));
					$tpl_inhalt->addComponent('plz',         new PText($_FORMVARS['plz']));
					$tpl_inhalt->addComponent('ort',         new PText($_FORMVARS['ort']));
					$tpl_inhalt->addComponent('land',        new PText($_FORMVARS['land']));
					$tpl_inhalt->addComponent('fax',         new PText($_FORMVARS['email']));
				} else {
					if (empty($_FORMVARS['filterdaten']['medium']))
						$tpl_inhalt->addComponent('extraalle', new PText(' checked'));
					else
						$tpl_inhalt->addComponent('extra'.$_FORMVARS['filterdaten']['medium'], new PText(' checked'));

					$tpl_inhalt->addComponent('startdatum',  new PText(substr(convert_time($_FORMVARS['filterdaten']['startdatum']),0,10)));
					$tpl_inhalt->addComponent('enddatum',    new PText(substr(convert_time($_FORMVARS['filterdaten']['enddatum']),0,10)));
					$tpl_inhalt->addComponent('bezeichnung', new PText($_FORMVARS['filterdaten']['bezeichnung']));
					$tpl_inhalt->addComponent('plz',         new PText($_FORMVARS['filterdaten']['plz']));
					$tpl_inhalt->addComponent('ort',         new PText($_FORMVARS['filterdaten']['ort']));
					$tpl_inhalt->addComponent('land',        new PText($_FORMVARS['filterdaten']['land']));
					$tpl_inhalt->addComponent('fax',         new PText($_FORMVARS['filterdaten']['fax']));
					$tpl_inhalt->addComponent('email',       new PText($_FORMVARS['filterdaten']['email']));
				}

				$tpl_rahmen->addComponent('inhalt',$tpl_inhalt);
				break;

			// Bearbeitung der Daten
			case 'weiterleiten':
			case 'beantworten':

				$postbuch_modus = $_FORMVARS['postbuch_modus'] = $_SESSION['postbuch_modus']='ausgang';

				if ($_FORMVARS['modus']=='weiterleiten')
					$_FORMVARS['referenz_typ']='W';
				else {
					$_FORMVARS['referenz_typ']='A';
					// Daten der Originalnachricht auslesen
					$sql_abfrage="SELECT * FROM ".DBPREFIX."postbuch WHERE postbuch_id='".$_FORMVARS['referenz']."'";
					$sql_ergebnis=sql_query($sql_abfrage);
					if ($sql_daten=sql_fetch_assoc($sql_ergebnis)) {
						$_FORMVARS['bezeichnung'] = $sql_daten['bezeichnung'];
						$_FORMVARS['str']         = $sql_daten['str'];
						$_FORMVARS['plz']         = $sql_daten['plz'];
						$_FORMVARS['ort']         = $sql_daten['ort'];
						$_FORMVARS['land']        = $sql_daten['land'];
						$_FORMVARS['fax']         = $sql_daten['fax'];
						$_FORMVARS['email']       = $sql_daten['email'];
					}
				}

			case 'bearbeiten':
				if ($_FORMVARS['postbuch_id']>0) {
					$sql_abfrage  = "SELECT P.*,B.bemerkung FROM ".DBPREFIX."postbuch AS P LEFT JOIN ".DBPREFIX."bemerkung AS B ON P.postbuch_id=B.postbuch_id WHERE P.postbuch_id='".$_FORMVARS['postbuch_id']."'";
					$sql_ergebnis = sql_query($sql_abfrage);
					$sql_daten    = sql_fetch_array($sql_ergebnis,SQL_ASSOC);

					$_FORMVARS['einrichtung_id'] = $sql_daten['einrichtung_id'];
					$_FORMVARS['medium']         = $sql_daten['medium'];
					$_FORMVARS['datum']          = convert_time($sql_daten['datum']);

					if ($sql_daten['datumextern']!='0000-00-00')
						$_FORMVARS['datumextern'] = convert_time($sql_daten['datumextern']);

					$_FORMVARS['bezeichnung'] = $sql_daten['bezeichnung'];
					$_FORMVARS['str']         = $sql_daten['str'];
					$_FORMVARS['plz']         = $sql_daten['plz'];
					$_FORMVARS['ort']         = $sql_daten['ort'];
					$_FORMVARS['land']        = $sql_daten['land'];
					$_FORMVARS['fax']         = $sql_daten['fax'];
					$_FORMVARS['email']       = $sql_daten['email'];
					$_FORMVARS['bemerkung']   = $sql_daten['bemerkung'];
				} else {
					$_FORMVARS['medium'] = 'post';
					$_FORMVARS['datum']  = strftime('%d.%m.%Y');
				}

			case 'bearbeiten1':
				if ($_FORMVARS['modus']!='bearbeiten1')
					$fehler = 65536;
				else {
					if (empty($_FORMVARS['datum']))
						$fehler|=1;

					if ($datumneu=test_datum($_FORMVARS['datum']))
						$_FORMVARS['datum'] = $datumneu;
					else
						$fehler|=1;

					if (empty($_FORMVARS['bezeichnung']))
						$fehler|=2;

					if (!empty($_FORMVARS['datumextern'])) {
						if ($datumneu=test_datum($_FORMVARS['datumextern']))
							$_FORMVARS['datumextern'] = $datumneu;
						else
							$fehler|=4;
					}

					if (!empty($_FORMVARS['email'])) {
						if (!check_mail($_FORMVARS['email']))
							$fehler|=8;
					}
				}

				if (!$fehler) {
					if ($_FORMVARS['postbuch_id']==-1) {
						sql_query("insert ".DBPREFIX."postbuch(typ,referenz,referenz_typ) values('".$_FORMVARS['postbuch_modus']."','".$_FORMVARS['referenz']."','".$_FORMVARS['referenz_typ']."')");
						$_FORMVARS['postbuch_id'] = sql_insert_id();
					} else
					    sql_query("delete from ".DBPREFIX."bemerkung where postbuch_id='".$_FORMVARS['postbuch_id']."'");

					sql_query("update ".DBPREFIX."postbuch set einrichtung_id='".$_FORMVARS['einrichtung_id']."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set datum='".convert_sqltime($_FORMVARS['datum'])."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set datumextern='".convert_sqltime($_FORMVARS['datumextern'])."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set medium='".$_FORMVARS['medium']."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set kurzname='".$_FORMVARS['kurzname']."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set bezeichnung='".$_FORMVARS['bezeichnung']."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set str='".$_FORMVARS['str']."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set plz='".$_FORMVARS['plz']."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set ort='".$_FORMVARS['ort']."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set land='".$_FORMVARS['land']."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set fax='".$_FORMVARS['fax']."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");
					sql_query("update ".DBPREFIX."postbuch set email='".$_FORMVARS['email']."' where postbuch_id='".$_FORMVARS['postbuch_id']."'");

					if (!empty($_FORMVARS['bemerkung']))
						sql_query("insert ".$DBPREFIX."bemerkung(postbuch_id,bemerkung) values('".$_FORMVARS['postbuch_id']."','".$_FORMVARS['bemerkung']."')");

					// Speichern & neu gewaehlt -> Felder leeren und weiter eingeben (datum und typ bleiben erhalten)
					if (!empty($_FORMVARS['btn_speichern_neu'])) {
						$_FORMVARS['postbuch_id'] = -1;
						$_FORMVARS['datumextern'] = '';
						$_FORMVARS['bezeichnung'] = '';
						$_FORMVARS['str']         = '';
						$_FORMVARS['plz']         = '';
						$_FORMVARS['ort']         = '';
						$_FORMVARS['land']        = '';
						$_FORMVARS['fax']         = '';
						$_FORMVARS['email']       = '';
						$_FORMVARS['bemerkung']   = '';

						$fehler = 65536;
					}
				}

				if ($fehler) {
					$tpl_inhalt = new PTemplate(NULL, $templatefiles['erfassung']);

					$tpl_inhalt->addComponent('formaction', new PText($_FORMVARS['PHP_SELF'].'?PHPSESSID='.$sessionid));

					if ($_FORMVARS['postbuch_modus']=='eingang') {
						$tpl_inhalt->addComponent('datum',       new PText(htmlentities(stripslashes($_FORMVARS['datum']))));
						$tpl_inhalt->addComponent('datumextern', new PText(htmlentities(stripslashes($_FORMVARS['datumextern']))));

						$tpl_inhalt->extractBlock('EingabeDatumAusgang');
						$tpl_inhalt->extractBlock('Empfaenger');

						$tpl_rahmen->addComponent('menue',   menue_ausfuellen('eingang'));
						$tpl_rahmen->addComponent('body_id', new PText('eingang'));
					} else {
						$tpl_inhalt->addComponent('datum', new PText(htmlentities(stripslashes($_FORMVARS['datum']))));

						$tpl_inhalt->extractBlock('EingabeDatumEingang');
						$tpl_inhalt->extractBlock('Absender');

						$tpl_rahmen->addComponent('menue',   menue_ausfuellen('ausgang'));
						$tpl_rahmen->addComponent('body_id', new PText('ausgang'));
					}

					if ($fehler & 1)
						$tpl_inhalt->addComponent('fehler_datum', new PText(' fehler'));

					if ($fehler & 2)
						$tpl_inhalt->addComponent('fehler_bezeichnung', new PText(' fehler'));

					if ($fehler & 4)
						$tpl_inhalt->addComponent('fehler_datumextern', new PText(' fehler'));

					if ($fehler & 8)
						$tpl_inhalt->addComponent('fehler_email', new PText(' fehler'));

					$tpl_inhalt->addComponent('hidden', new PInput('hidden','einrichtung_id',$_FORMVARS['einrichtung_id']));
					$tpl_inhalt->addComponent('hidden', new PInput('hidden','postbuch_id',$_FORMVARS['postbuch_id']));
					$tpl_inhalt->addComponent('hidden', new PInput('hidden','referenz',$_FORMVARS['referenz']));
					$tpl_inhalt->addComponent('hidden', new PInput('hidden','referenz_typ',$_FORMVARS['referenz_typ']));
					$tpl_inhalt->addComponent('hidden', new PInput('hidden','modus','bearbeiten1'));

					if ($_FORMVARS['postbuch_id']==-1) {
						if ($_FORMVARS['referenz']!=0) {
							if ($_FORMVARS['referenz_typ']=='W')
								$tpl_inhalt->addComponent('titel', new PText(TITEL_NEUEINTRAG_REFERENZ_WEITERLEITUNG));
							else
								$tpl_inhalt->addComponent('titel', new PText(TITEL_NEUEINTRAG_REFERENZ_ANTWORT));
							$tpl_inhalt->extractBlock('SpeichernNeu');
						} else
							$tpl_inhalt->addComponent('titel', new PText(TITEL_NEUEINTRAG));
					} else {
						$tpl_inhalt->addComponent('titel', new PText(TITEL_BEARBEITEN));
						$tpl_inhalt->extractBlock('SpeichernNeu');
					}

					$tpl_inhalt->addComponent('extra'.$_FORMVARS['medium'],new PText(' checked'));
					$tpl_inhalt->addComponent('kurzname',    new PText(htmlentities(stripslashes($_FORMVARS['kurzname']))));
					$tpl_inhalt->addComponent('bezeichnung', new PText(htmlentities(stripslashes($_FORMVARS['bezeichnung']))));
					$tpl_inhalt->addComponent('str',         new PText(htmlentities(stripslashes($_FORMVARS['str']))));
					$tpl_inhalt->addComponent('plz',         new PText(htmlentities(stripslashes($_FORMVARS['plz']))));
					$tpl_inhalt->addComponent('ort',         new PText(htmlentities(stripslashes($_FORMVARS['ort']))));
					$tpl_inhalt->addComponent('land',        new PText(htmlentities(stripslashes($_FORMVARS['land']))));
					$tpl_inhalt->addComponent('fax',         new PText(htmlentities(stripslashes($_FORMVARS['fax']))));
					$tpl_inhalt->addComponent('email',       new PText(htmlentities(stripslashes($_FORMVARS['email']))));
					$tpl_inhalt->addComponent('bemerkung',   new PText(htmlentities(stripslashes($_FORMVARS['bemerkung']))));

					$tpl_rahmen->addComponent('inhalt', $tpl_inhalt);

					break;
				}

			case 'eingang':
			case 'ausgang':
			case 'liste':
				if ($_FORMVARS['modus']=='eingang')
					$postbuch_modus = $_FORMVARS['postbuch_modus'] = $_SESSION['postbuch_modus']='eingang';
				if ($_FORMVARS['modus']=='ausgang')
					$postbuch_modus = $_FORMVARS['postbuch_modus'] = $_SESSION['postbuch_modus']='ausgang';

				// Suchfilter bauen
				$tpl_filter         = new PTemplate(NULL, $templatefiles['filterliste']);
				$tpl_filter_eintrag = $tpl_filter->extractBlock('Filter');
				$tpl_filter_leer    = $tpl_filter->extractBlock('FilterLeer');

				$anzahlfilter = 0;
				$suchfilter   = '';

				if (!empty($_FORMVARS['filterdaten']['medium'])) {
					$suchfilter.="AND P.medium='".$_FORMVARS['filterdaten']['medium']."' ";
					$tpl_filter_eintrag->parse();
					$tpl_filter_eintrag->addComponent('titel', new PText('Medium'));

					switch ($_FORMVARS['filterdaten']['medium']) {
						case 'post':
							$tpl_filter_eintrag->addComponent('value', new PText('Post'));
							break;
						case 'email':
							$tpl_filter_eintrag->addComponent('value', new PText('E-Mail'));
							break;
						case 'fax':
							$tpl_filter_eintrag->addComponent('value', new PText('Fax'));
							break;
					}

					$tpl_filter_eintrag->addComponent('link', new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&action=filterloeschen&filter=medium&PHPSESSID='.$sessionid));
					$tpl_filter->addComponent('filter', $tpl_filter_eintrag);
					$anzahlfilter++;
				}

				if (!empty($_FORMVARS['filterdaten']['startdatum'])) {
					$suchfilter.= "AND P.datum>='".$_FORMVARS['filterdaten']['startdatum']."' ";
					$tpl_filter_eintrag->parse();
					$tpl_filter_eintrag->addComponent('titel', new PText('Startdatum'));
					$tpl_filter_eintrag->addComponent('value', new PText(substr(convert_time($_FORMVARS['filterdaten']['startdatum']),0,10)));
					$tpl_filter_eintrag->addComponent('link',  new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&action=filterloeschen&filter=startdatum&PHPSESSID='.$sessionid));
					$tpl_filter->addComponent('filter', $tpl_filter_eintrag);
					$anzahlfilter++;
				}

				if (!empty($_FORMVARS['filterdaten']['enddatum'])) {
					$suchfilter.= "AND P.datum<='".$_FORMVARS['filterdaten']['enddatum']."' ";
					$tpl_filter_eintrag->parse();
					$tpl_filter_eintrag->addComponent('titel', new PText('Enddatum'));
					$tpl_filter_eintrag->addComponent('value', new PText(substr(convert_time($_FORMVARS['filterdaten']['enddatum']),0,10)));
					$tpl_filter_eintrag->addComponent('link',  new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&action=filterloeschen&filter=enddatum&PHPSESSID='.$sessionid));
					$tpl_filter->addComponent('filter', $tpl_filter_eintrag);
					$anzahlfilter++;
				}

				if (!empty($_FORMVARS['filterdaten']['bezeichnung'])) {
					$suchfilter.= "AND P.bezeichnung like '".str_replace('*','%',$_FORMVARS['filterdaten']['bezeichnung'])."' ";
					$tpl_filter_eintrag->parse();

					if ($_FORMVARS['postbuch_modus']=='eingang')
						$tpl_filter_eintrag->addComponent('titel', new PText('Absender'));
					else
						$tpl_filter_eintrag->addComponent('titel', new PText('Empf&auml;nger'));

					$tpl_filter_eintrag->addComponent('value', new PText($_FORMVARS['filterdaten']['bezeichnung']));
					$tpl_filter_eintrag->addComponent('link',  new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&action=filterloeschen&filter=bezeichnung&PHPSESSID='.$sessionid));
					$tpl_filter->addComponent('filter', $tpl_filter_eintrag);
					$anzahlfilter++;
				}

				if (!empty($_FORMVARS['filterdaten']['plz'])) {
					$suchfilter.= "AND P.plz like '".str_replace('*','%',$_FORMVARS['filterdaten']['plz'])."' ";
					$tpl_filter_eintrag->parse();
					$tpl_filter_eintrag->addComponent('titel', new PText('PLZ'));
					$tpl_filter_eintrag->addComponent('value', new PText($_FORMVARS['filterdaten']['plz']));
					$tpl_filter_eintrag->addComponent('link',  new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&action=filterloeschen&filter=plz&PHPSESSID='.$sessionid));
					$tpl_filter->addComponent('filter', $tpl_filter_eintrag);
					$anzahlfilter++;
				}

				if (!empty($_FORMVARS['filterdaten']['ort'])) {
					$suchfilter.= "AND P.bezeichnung like '".str_replace('*','%',$_FORMVARS['filterdaten']['ort'])."' ";
					$tpl_filter_eintrag->parse();
					$tpl_filter_eintrag->addComponent('titel', new PText('Ort'));
					$tpl_filter_eintrag->addComponent('value', new PText($_FORMVARS['filterdaten']['ort']));
					$tpl_filter_eintrag->addComponent('link',  new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&action=filterloeschen&filter=ort&PHPSESSID='.$sessionid));
					$tpl_filter->addComponent('filter', $tpl_filter_eintrag);
					$anzahlfilter++;
				}

				if (!empty($_FORMVARS['filterdaten']['land'])) {
					$suchfilter.= "AND P.land like '".str_replace('*','%',$_FORMVARS['filterdaten']['land'])."' ";
					$tpl_filter_eintrag->parse();
					$tpl_filter_eintrag->addComponent('titel', new PText('Land'));
					$tpl_filter_eintrag->addComponent('value', new PText($_FORMVARS['filterdaten']['land']));
					$tpl_filter_eintrag->addComponent('link',  new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&action=filterloeschen&filter=land&PHPSESSID='.$sessionid));
					$tpl_filter->addComponent('filter', $tpl_filter_eintrag);
					$anzahlfilter++;
				}

				if (!empty($_FORMVARS['filterdaten']['fax'])) {
					$suchfilter.= "AND replace(replace(replace(P.fax,' ',''),'/',''),'-','') like '%".str_replace(array('*','/','-',' '),array('%','','',''),$_FORMVARS['filterdaten']['fax'])."%' ";
					$tpl_filter_eintrag->parse();
					$tpl_filter_eintrag->addComponent('titel', new PText('Fax'));
					$tpl_filter_eintrag->addComponent('value', new PText($_FORMVARS['filterdaten']['fax']));
					$tpl_filter_eintrag->addComponent('link',  new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&action=filterloeschen&filter=fax&PHPSESSID='.$sessionid));
					$tpl_filter->addComponent('filter', $tpl_filter_eintrag);
					$anzahlfilter++;
				}
				if (!empty($_FORMVARS['filterdaten']['email'])) {
					$suchfilter.= "AND P.email like '".str_replace('*','%',$_FORMVARS['filterdaten']['email'])."' ";
					$tpl_filter_eintrag->parse();
					$tpl_filter_eintrag->addComponent('titel', new PText('E-Mail'));
					$tpl_filter_eintrag->addComponent('value', new PText($_FORMVARS['filterdaten']['email']));
					$tpl_filter_eintrag->addComponent('link',  new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&action=filterloeschen&filter=email&PHPSESSID='.$sessionid));
					$tpl_filter->addComponent('filter', $tpl_filter_eintrag);
					$anzahlfilter++;
				}

				if ($anzahlfilter%2==1)
					$tpl_filter->addComponent('filter', $tpl_filter_leer);

				// Unterscheidung der einzelnen Anzeigetypen
				switch ($nutzereinstellung['listenmodus']) {
					// Nur ein Eintrag auf der Seite zum blaettern
					case 'einzel':
						$tpl_inhalt = new PTemplate(NULL, $templatefiles['einzelanzeige']);

						$tpl_blaettern = new PTemplate(NULL, $templatefiles['blaettern']);
						$tpl_blaettern->extractBlock('BlaetternTyp');
						$tpl_blaettern->addComponent('link_neueintrag', new PText($_FORMVARS['PHP_SELF'].'?modus=bearbeiten&postbuch_id=-1&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&PHPSESSID='.$sessionid));

						if ($anzahlfilter>0)
							$tpl_inhalt->addComponent('filterliste', $tpl_filter);

						if ($_FORMVARS['postbuch_modus']=='eingang') {
							$tpl_rahmen->addComponent('menue',   menue_ausfuellen('eingang'));
							$tpl_rahmen->addComponent('body_id', new PText('eingang'));
							$tpl_inhalt->extractBlock('DatumAusgang');
							$tpl_inhalt->extractBlock('BezeichnungAusgang');
							$tpl_inhalt->extractBlock('WeiterleitungVon');
							$tpl_inhalt->extractBlock('AntwortAuf');

							$sql_abfrage = "SELECT P.*,B.bemerkung FROM ".DBPREFIX."postbuch AS P LEFT JOIN ".DBPREFIX."bemerkung AS B ON P.postbuch_id=B.postbuch_id WHERE P.einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='eingang' ".$suchfilter."ORDER BY datum DESC,P.postbuch_id";
						} else {
							$tpl_rahmen->addComponent('menue',   menue_ausfuellen('ausgang'));
							$tpl_rahmen->addComponent('body_id', new PText('ausgang'));
							$tpl_inhalt->extractBlock('DatumEingang');
							$tpl_inhalt->extractBlock('BezeichnungEingang');
							$tpl_inhalt->extractBlock('WeiterleitungAn');
							$tpl_inhalt->extractBlock('AntwortAn');

							$tpl_blaettern->extractBlock('ButtonBeantworten');
							$tpl_blaettern->extractBlock('ButtonWeiterleiten');

							$sql_abfrage = "SELECT P.*,B.bemerkung FROM ".DBPREFIX."postbuch AS P LEFT JOIN ".DBPREFIX."bemerkung AS B ON P.postbuch_id=B.postbuch_id WHERE P.einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='ausgang' ".$suchfilter."ORDER BY datum DESC,P.postbuch_id";
						}

						if(empty($_FORMVARS['seite']))
							$_FORMVARS['seite'] = 0;

						$sql_ergebnis = sql_query($sql_abfrage);

						$anzahl_seiten = sql_num_rows($sql_ergebnis);

						$_FORMVARS['seite'] = min($anzahl_seiten-1,abs($_FORMVARS['seite']));

						if ($_FORMVARS['seite']==0) {
							$tpl_blaettern->extractBlock('ButtonVor');
						} else {
							$tpl_blaettern->extractBlock('ButtonVorOff');
							$tpl_blaettern->addComponent('link_vor', new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&seite='.(max(0,$_FORMVARS['seite']-1)).'&PHPSESSID='.$sessionid));
						}

						if ($_FORMVARS['seite']==$anzahl_seiten-1) {
							$tpl_blaettern->extractBlock('ButtonZurueck');
						} else {
							$tpl_blaettern->extractBlock('ButtonZurueckOff');
							$tpl_blaettern->addComponent('link_zurueck', new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&seite='.(min($anzahl_seiten-1,$_FORMVARS['seite']+1)).'&PHPSESSID='.$sessionid));
						}

						if (sql_num_rows($sql_ergebnis)==0) {
							$tpl_inhalt->extractBlock('Daten');
							$tpl_blaettern->extractBlock('ButtonBearbeiten');
							$tpl_blaettern->extractBlock('ButtonLoeschen');
							$tpl_rahmen->addComponent('blaettern', $tpl_blaettern);
						} else {
							$tpl_inhalt->extractBlock('Fehler');

							sql_data_seek($sql_ergebnis,$_FORMVARS['seite']);
							$sql_daten = sql_fetch_array($sql_ergebnis,SQL_ASSOC);

							$tpl_blaettern->addComponent('link_bearbeiten',   new PText($_FORMVARS['PHP_SELF'].'?modus=bearbeiten&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&postbuch_id='.$sql_daten['postbuch_id'].'&PHPSESSID='.$sessionid));
							$tpl_blaettern->addComponent('link_beantworten',  new PText($_FORMVARS['PHP_SELF'].'?modus=beantworten&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&referenz='.$sql_daten['postbuch_id'].'&postbuch_id=-1&PHPSESSID='.$sessionid));
							$tpl_blaettern->addComponent('link_weiterleiten', new PText($_FORMVARS['PHP_SELF'].'?modus=weiterleiten&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&referenz='.$sql_daten['postbuch_id'].'&postbuch_id=-1&PHPSESSID='.$sessionid));
							$tpl_rahmen->addComponent('blaettern', $tpl_blaettern);

							$tpl_inhalt->addComponent('postbuch_id', new PText($sql_daten['postbuch_id']));
							$tpl_inhalt->addComponent('eintrag_nr',  new PText($_FORMVARS['seite']+1));

							switch($sql_daten['medium']) {
								case 'post':
									$tpl_inhalt->addComponent('medium', new PText('Post'));
									break;
								case 'fax':
									$tpl_inhalt->addComponent('medium', new PText('Fax'));
									break;
								case 'email':
									$tpl_inhalt->addComponent('medium', new PText('E-Mail'));
									break;
							}

							$tpl_inhalt->addComponent('datum',       new PText(convert_time($sql_daten['datum'])));
							$tpl_inhalt->addComponent('bezeichnung', new PText(htmlentities($sql_daten['bezeichnung'])));
							$tpl_inhalt->addComponent('str',         new PText(htmlentities($sql_daten['str'])));
							$tpl_inhalt->addComponent('plz',         new PText(htmlentities($sql_daten['plz'])));
							$tpl_inhalt->addComponent('ort',         new PText(htmlentities($sql_daten['ort'])));
							$tpl_inhalt->addComponent('land',        new PText(htmlentities($sql_daten['land'])));
							$tpl_inhalt->addComponent('fax',         new PText(htmlentities($sql_daten['fax'])));
							$tpl_inhalt->addComponent('email',       new PText(htmlentities($sql_daten['email'])));
							$tpl_inhalt->addComponent('bemerkung',   new PText(htmlentities($sql_daten['bemerkung'])));

							// Weiterleitungen/Antworten
							if ($_FORMVARS['postbuch_modus']=='eingang')	{
								$sql_abfrage_verweis="SELECT * FROM ".DBPREFIX."postbuch WHERE referenz='".$sql_daten['postbuch_id']."'";
								$sql_ergebnis_verweis=sql_query($sql_abfrage_verweis);
								while ($sql_daten_verweis=sql_fetch_assoc($sql_ergebnis_verweis)) {
									$empfaengerzeile='';
									$empfaengerzeile.=$sql_daten_verweis['bezeichnung'];
									if (!empty($sql_daten_verweis['str']))
										$empfaengerzeile.=', '.$sql_daten_verweis['str'];
									if (!empty($sql_daten_verweis['ort']))
										$empfaengerzeile.=', '.trim($sql_daten_verweis['plz'].' '.$sql_daten_verweis['ort']);
									if (!empty($sql_daten_verweis['land']))
										$empfaengerzeile.=', '.$sql_daten_verweis['land'];
									if (!empty($sql_daten_verweis['fax']))
										$empfaengerzeile.=', Fax: '.$sql_daten_verweis['fax'];
									if (!empty($sql_daten_verweis['email']))
										$empfaengerzeile.=', E-Mail: '.$sql_daten_verweis['email'];
									$empfaengerzeile.="\n";
									if ($sql_daten_verweis['referenz_typ']=='W')
										$tpl_inhalt->addComponent('weiterleitung',new PText($empfaengerzeile));
									else
										$tpl_inhalt->addComponent('antwort',new PText($empfaengerzeile));
								}
							} else {
                $sql_abfrage_verweis="SELECT * FROM ".DBPREFIX."postbuch WHERE postbuch_id='".$sql_daten['referenz']."'";
								$sql_ergebnis_verweis=sql_query($sql_abfrage_verweis);
								if ($sql_daten_verweis=sql_fetch_assoc($sql_ergebnis_verweis)) {
									$empfaengerzeile='';
									$empfaengerzeile.=$sql_daten_verweis['bezeichnung'];
									if (!empty($sql_daten_verweis['str']))
										$empfaengerzeile.=', '.$sql_daten_verweis['str'];
									if (!empty($sql_daten_verweis['ort']))
										$empfaengerzeile.=', '.trim($sql_daten_verweis['plz'].' '.$sql_daten_verweis['ort']);
									if (!empty($sql_daten_verweis['land']))
										$empfaengerzeile.=', '.$sql_daten_verweis['land'];
									if (!empty($sql_daten_verweis['fax']))
										$empfaengerzeile.=', Fax: '.$sql_daten_verweis['fax'];
									if (!empty($sql_daten_verweis['email']))
										$empfaengerzeile.=', E-Mail: '.$sql_daten_verweis['email'];
									$empfaengerzeile.="\n";
									if ($sql_daten['referenz_typ']=='W')
										$tpl_inhalt->addComponent('weiterleitung',new PText($empfaengerzeile));
									else
										$tpl_inhalt->addComponent('antwort',new PText($empfaengerzeile));
								}
							}

							// Loeschlink
							$tpl_inhalt->addComponent('formaction_loeschen',     new PText($_FORMVARS['PHP_SELF'].'?PHPSESSID='.$sessionid));
							$tpl_inhalt->addComponent('seite_loeschen',          new PText($_FORMVARS['seite']));
							$tpl_inhalt->addComponent('einrichtung_id_loeschen', new PText($_FORMVARS['einrichtung_id']));
						}

						$tpl_rahmen->addComponent('inhalt', $tpl_inhalt);
						break;

					// Aufklappbare Tage
 					case 'tage':
						$tpl_inhalt = new PTemplate(NULL, $templatefiles['liste']);

						$tpl_datum=$tpl_inhalt->extractBlock('Datum');
						$tpl_eintrag=$tpl_inhalt->extractBlock('Eintrag');

						if ($anzahlfilter>0)
							$tpl_inhalt->addComponent('filterliste', $tpl_filter);

						if ($_FORMVARS['postbuch_modus']=='eingang') {
							$tpl_rahmen->addComponent('menue',   menue_ausfuellen('eingang'));
							$tpl_rahmen->addComponent('body_id', new PText('eingang'));

							$sql_abfrage_tage="SELECT DISTINCT datum FROM ".DBPREFIX."postbuch as P WHERE einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='eingang' ".$suchfilter."ORDER BY datum DESC";
							$sql_abfrage = "SELECT P.*,B.bemerkung FROM ".DBPREFIX."postbuch AS P LEFT JOIN ".DBPREFIX."bemerkung AS B ON P.postbuch_id=B.postbuch_id WHERE P.einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='eingang' ".$suchfilter."ORDER BY datum DESC,P.postbuch_id";
						} else {
							$tpl_rahmen->addComponent('menue',   menue_ausfuellen('ausgang'));
							$tpl_rahmen->addComponent('body_id', new PText('ausgang'));
							$tpl_eintrag->extractBlock('BtnBeantworten');
							$tpl_eintrag->extractBlock('BtnWeiterleiten');

							$sql_abfrage_tage = "SELECT DISTINCT datum FROM ".DBPREFIX."postbuch as P WHERE einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='ausgang' ".$suchfilter."ORDER BY datum DESC";
							$sql_abfrage      = "SELECT P.*,B.bemerkung FROM ".DBPREFIX."postbuch AS P LEFT JOIN ".DBPREFIX."bemerkung AS B ON P.postbuch_id=B.postbuch_id WHERE P.einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='ausgang' ".$suchfilter."ORDER BY datum DESC,P.postbuch_id";
						}

						if(empty($_FORMVARS['seite']))
							$_FORMVARS['seite'] = 0;

						// Blaettern ueber Tage
						$sql_ergebnis_tage = sql_query($sql_abfrage_tage);

						$tpl_blaettern = new PTemplate(NULL,$templatefiles['blaettern']);

						$tpl_blaettern->extractBlock('ButtonBearbeiten');
						$tpl_blaettern->extractBlock('ButtonLoeschen');
						$tpl_blaettern->extractBlock('ButtonBeantworten');
						$tpl_blaettern->extractBlock('ButtonWeiterleiten');

						$tpl_blaettern->addComponent('link_neueintrag', new PText($_FORMVARS['PHP_SELF'].'?modus=bearbeiten&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&postbuch_id=-1&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&PHPSESSID='.$sessionid));

						$anzahl_eintraege_tage = sql_num_rows($sql_ergebnis_tage);
						$anzahl_seiten         = ceil($anzahl_eintraege_tage/$nutzereinstellung['eintragtag']);
						$_FORMVARS['seite']    = min($anzahl_seiten-1,abs($_FORMVARS['seite']));

						if ($_FORMVARS['seite']==0) {
							$tpl_blaettern->extractBlock('ButtonVor');
						} else {
							$tpl_blaettern->extractBlock('ButtonVorOff');
							$tpl_blaettern->addComponent('link_vor', new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&seite='.(max(0,$_FORMVARS['seite']-1)).'&PHPSESSID='.$sessionid));
						}

						if ($_FORMVARS['seite']==$anzahl_seiten-1) {
							$tpl_blaettern->extractBlock('ButtonZurueck');
						} else {
							$tpl_blaettern->extractBlock('ButtonZurueckOff');
							$tpl_blaettern->addComponent('link_zurueck', new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&seite='.(min($anzahl_seiten-1,$_FORMVARS['seite']+1)).'&PHPSESSID='.$sessionid));
						}

						$tpl_rahmen->addComponent('blaettern', $tpl_blaettern);

						// Eintraege listen

						$sql_ergebnis = sql_query($sql_abfrage);

						if ($anzahl_eintraege_tage>0) {
							$tpl_inhalt->extractBlock('Fehler');
							sql_data_seek($sql_ergebnis_tage, $_FORMVARS['seite']*$nutzereinstellung['eintragtag']);
						}

						// Loeschlink
						$tpl_inhalt->addComponent('formaction_loeschen',     new PText($_FORMVARS['PHP_SELF'].'?PHPSESSID='.$sessionid));
						$tpl_inhalt->addComponent('seite_loeschen',          new PText($_FORMVARS['seite']));
						$tpl_inhalt->addComponent('einrichtung_id_loeschen', new PText($_FORMVARS['einrichtung_id']));

						$position = 0;

						while (($sql_daten_tage=sql_fetch_array($sql_ergebnis_tage,SQL_ASSOC)) && $position++<$nutzereinstellung['eintragtag']) {
							$tpl_datum->parse();
							$tpl_datum->addComponent('datum', new PText(convert_time($sql_daten_tage['datum'])));

							$wochentag_id = strftime('%u',mktime(12,0,0,substr($sql_daten_tage['datum'],5,2),substr($sql_daten_tage['datum'],8,2),substr($sql_daten_tage['datum'],0,4)));

							$tpl_datum->addComponent('wochentag', new PText($feld_wochentage[$wochentag_id]));

							if ($_FORMVARS['postbuch_modus']=='eingang')
								$sql_abfrage = "SELECT P.*,B.bemerkung FROM ".DBPREFIX."postbuch AS P LEFT JOIN ".DBPREFIX."bemerkung AS B ON P.postbuch_id=B.postbuch_id WHERE P.einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='eingang' AND datum='".$sql_daten_tage['datum']."' ".$suchfilter."ORDER BY P.postbuch_id";
							else
								$sql_abfrage = "SELECT P.*,B.bemerkung FROM ".DBPREFIX."postbuch AS P LEFT JOIN ".DBPREFIX."bemerkung AS B ON P.postbuch_id=B.postbuch_id WHERE P.einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='ausgang' AND datum='".$sql_daten_tage['datum']."' ".$suchfilter."ORDER BY P.postbuch_id";
							$sql_ergebnis = sql_query($sql_abfrage);

							while ($sql_daten=sql_fetch_array($sql_ergebnis,SQL_ASSOC)) {
								$tpl_eintrag->parse();
								$tpl_eintrag->addComponent('postbuch_id',new PText($sql_daten['postbuch_id']));
								$titeltext = '';
								$titeltext.= $sql_daten['bezeichnung'];

								if (!empty($sql_daten['str']))
									$titeltext.=', '.$sql_daten['str'];

								if (!empty($sql_daten['ort']))
									$titeltext.=', '.trim($sql_daten['plz'].' '.$sql_daten['ort']);

								$tpl_eintrag->addComponent('titel',new PText($titeltext));
								$tpl_eintrag->addComponent('link_bearbeiten',   new PText($_FORMVARS['PHP_SELF'].'?modus=bearbeiten&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&postbuch_id='.$sql_daten['postbuch_id'].'&PHPSESSID='.$sessionid));
								$tpl_eintrag->addComponent('link_beantworten',  new PText($_FORMVARS['PHP_SELF'].'?modus=beantworten&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&referenz='.$sql_daten['postbuch_id'].'&postbuch_id=-1&PHPSESSID='.$sessionid));
								$tpl_eintrag->addComponent('link_weiterleiten', new PText($_FORMVARS['PHP_SELF'].'?modus=weiterleiten&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&referenz='.$sql_daten['postbuch_id'].'&postbuch_id=-1&PHPSESSID='.$sessionid));

								$infotext = '';
								$infotext.= $sql_daten['bezeichnung'].'<br />';

								if (!empty($sql_daten['str']))
									$infotext.= $sql_daten['str'].'<br />';
								if (!empty($sql_daten['ort']))
									$infotext.= trim($sql_daten['plz'].' '.$sql_daten['ort']).'<br />';
								if (!empty($sql_daten['land']))
									$infotext.= $sql_daten['land'].'<br />';
								if (!empty($sql_daten['fax']))
									$infotext.= 'Fax: '.$sql_daten['fax'].'<br />';
								if (!empty($sql_daten['email']))
									$infotext.= 'E-Mail: '.$sql_daten['email'].'<br />';
								if (!empty($sql_daten['bemerkung']))
									$infotext.= '<br />'.nl2br($sql_daten['bemerkung']);

								if ($_FORMVARS['postbuch_modus']=='eingang')	{
									$sql_abfrage_verweis="SELECT * FROM ".DBPREFIX."postbuch WHERE referenz='".$sql_daten['postbuch_id']."' AND referenz_typ='W'";
									$sql_ergebnis_verweis=sql_query($sql_abfrage_verweis);
									if (sql_num_rows($sql_ergebnis_verweis)>0)
										$infotext.='<br />Weitergeleitet an:<br />';
									while ($sql_daten_verweis=sql_fetch_assoc($sql_ergebnis_verweis)) {
										$empfaengerzeile='';
										$empfaengerzeile.=$sql_daten_verweis['bezeichnung'];
										if (!empty($sql_daten_verweis['str']))
											$empfaengerzeile.=', '.$sql_daten_verweis['str'];
										if (!empty($sql_daten_verweis['ort']))
											$empfaengerzeile.=', '.trim($sql_daten_verweis['plz'].' '.$sql_daten_verweis['ort']);
										if (!empty($sql_daten_verweis['land']))
											$empfaengerzeile.=', '.$sql_daten_verweis['land'];
										if (!empty($sql_daten_verweis['fax']))
											$empfaengerzeile.=', Fax: '.$sql_daten_verweis['fax'];
										if (!empty($sql_daten_verweis['email']))
											$empfaengerzeile.=', E-Mail: '.$sql_daten_verweis['email'];
										$empfaengerzeile.='<br />';
										$infotext.=$empfaengerzeile;
									}

									$sql_abfrage_verweis="SELECT * FROM ".DBPREFIX."postbuch WHERE referenz='".$sql_daten['postbuch_id']."' AND referenz_typ='A'";
									$sql_ergebnis_verweis=sql_query($sql_abfrage_verweis);
									if (sql_num_rows($sql_ergebnis_verweis)>0)
										$infotext.='<br />Antwort an:<br />';
									while ($sql_daten_verweis=sql_fetch_assoc($sql_ergebnis_verweis)) {
										$empfaengerzeile='';
										$empfaengerzeile.=$sql_daten_verweis['bezeichnung'];
										if (!empty($sql_daten_verweis['str']))
											$empfaengerzeile.=', '.$sql_daten_verweis['str'];
										if (!empty($sql_daten_verweis['ort']))
											$empfaengerzeile.=', '.trim($sql_daten_verweis['plz'].' '.$sql_daten_verweis['ort']);
										if (!empty($sql_daten_verweis['land']))
											$empfaengerzeile.=', '.$sql_daten_verweis['land'];
										if (!empty($sql_daten_verweis['fax']))
											$empfaengerzeile.=', Fax: '.$sql_daten_verweis['fax'];
										if (!empty($sql_daten_verweis['email']))
											$empfaengerzeile.=', E-Mail: '.$sql_daten_verweis['email'];
										$empfaengerzeile.='<br />';
										$infotext.=$empfaengerzeile;
									}
								} else {
	                $sql_abfrage_verweis="SELECT * FROM ".DBPREFIX."postbuch WHERE postbuch_id='".$sql_daten['referenz']."'";
									$sql_ergebnis_verweis=sql_query($sql_abfrage_verweis);
									if ($sql_daten_verweis=sql_fetch_assoc($sql_ergebnis_verweis)) {
										if ($sql_daten['referenz_typ']=='W')
											$infotext.='<br />Weitergeleitet von:<br />';
										else
											$infotext.='<br />Antwort auf:<br />';
										$empfaengerzeile='';
										$empfaengerzeile.=$sql_daten_verweis['bezeichnung'];
										if (!empty($sql_daten_verweis['str']))
											$empfaengerzeile.=', '.$sql_daten_verweis['str'];
										if (!empty($sql_daten_verweis['ort']))
											$empfaengerzeile.=', '.trim($sql_daten_verweis['plz'].' '.$sql_daten_verweis['ort']);
										if (!empty($sql_daten_verweis['land']))
											$empfaengerzeile.=', '.$sql_daten_verweis['land'];
										if (!empty($sql_daten_verweis['fax']))
											$empfaengerzeile.=', Fax: '.$sql_daten_verweis['fax'];
										if (!empty($sql_daten_verweis['email']))
											$empfaengerzeile.=', E-Mail: '.$sql_daten_verweis['email'];
										$empfaengerzeile.='<br />';
										$infotext.=$empfaengerzeile;
									}
								}

								$tpl_eintrag->addComponent('vorschau', new PText(htmlentities(trim($infotext))));
								$tpl_datum->addComponent('eintrag',    $tpl_eintrag);
							}

							$tpl_inhalt->addComponent('inhalt',$tpl_datum);
						}

						if ($aktdatum!='')
							$tpl_inhalt->addComponent('inhalt', $tpl_datum);
						$tpl_rahmen->addComponent('inhalt', $tpl_inhalt);
						break;

					// Liste nach Eintraegen mit Zwischenueberschrift der Tage
					case 'liste':
					default:
						$tpl_rahmen->addComponent('accordion', new PText(',true'));

						$tpl_inhalt = new PTemplate(NULL, $templatefiles['liste']);

						$tpl_datum   = $tpl_inhalt->extractBlock('Datum');
						$tpl_eintrag = $tpl_inhalt->extractBlock('Eintrag');

						if ($anzahlfilter>0)
							$tpl_inhalt->addComponent('filterliste', $tpl_filter);

						if ($_FORMVARS['postbuch_modus']=='eingang') {
							$tpl_rahmen->addComponent('menue',   menue_ausfuellen('eingang'));
							$tpl_rahmen->addComponent('body_id', new PText('eingang'));

							$sql_abfrage = "SELECT P.*,B.bemerkung FROM ".DBPREFIX."postbuch AS P LEFT JOIN ".DBPREFIX."bemerkung AS B ON P.postbuch_id=B.postbuch_id WHERE P.einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='eingang' ".$suchfilter."ORDER BY datum DESC,P.postbuch_id";
						} else {
							$tpl_rahmen->addComponent('menue',   menue_ausfuellen('ausgang'));
							$tpl_rahmen->addComponent('body_id', new PText('ausgang'));
							$tpl_eintrag->extractBlock('BtnBeantworten');
							$tpl_eintrag->extractBlock('BtnWeiterleiten');

							$sql_abfrage="SELECT P.*,B.bemerkung FROM ".DBPREFIX."postbuch AS P LEFT JOIN ".DBPREFIX."bemerkung AS B ON P.postbuch_id=B.postbuch_id WHERE P.einrichtung_id='".$_FORMVARS['einrichtung_id']."' AND typ='ausgang' ".$suchfilter."ORDER BY datum DESC,P.postbuch_id";
						}

						$sql_ergebnis = sql_query($sql_abfrage);

						if(empty($_FORMVARS['seite']))
							$_FORMVARS['seite'] = 0;

						// Blaettern
						$tpl_blaettern = new PTemplate(NULL, $templatefiles['blaettern']);
						$tpl_blaettern->extractBlock('ButtonBearbeiten');
						$tpl_blaettern->extractBlock('ButtonLoeschen');
						$tpl_blaettern->extractBlock('ButtonBeantworten');
						$tpl_blaettern->extractBlock('ButtonWeiterleiten');

						$tpl_blaettern->addComponent('link_neueintrag', new PText($_FORMVARS['PHP_SELF'].'?modus=bearbeiten&postbuch_id=-1&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&PHPSESSID='.$sessionid));

						$anzahl_eintraege   = sql_num_rows($sql_ergebnis);
						$anzahl_seiten      = ceil($anzahl_eintraege/$nutzereinstellung['eintragliste']);
						$_FORMVARS['seite'] = min($anzahl_seiten-1,abs($_FORMVARS['seite']));

						if ($_FORMVARS['seite']==0) {
							$tpl_blaettern->extractBlock('ButtonVor');
						} else {
							$tpl_blaettern->extractBlock('ButtonVorOff');
							$tpl_blaettern->addComponent('link_vor', new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&seite='.(max(0,$_FORMVARS['seite']-1)).'&PHPSESSID='.$sessionid));
						}

						if ($_FORMVARS['seite']==$anzahl_seiten-1) {
							$tpl_blaettern->extractBlock('ButtonZurueck');
						} else {
							$tpl_blaettern->extractBlock('ButtonZurueckOff');
							$tpl_blaettern->addComponent('link_zurueck', new PText($_FORMVARS['PHP_SELF'].'?modus=liste&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&seite='.(min($anzahl_seiten-1,$_FORMVARS['seite']+1)).'&PHPSESSID='.$sessionid));
						}

						$tpl_rahmen->addComponent('blaettern', $tpl_blaettern);

						if ($anzahl_eintraege>0) {
							$tpl_inhalt->extractBlock('Fehler');
							sql_data_seek($sql_ergebnis,$_FORMVARS['seite']*$nutzereinstellung['eintragliste']);
						}

						// Loeschlink
						$tpl_inhalt->addComponent('formaction_loeschen',     new PText($_FORMVARS['PHP_SELF'].'?PHPSESSID='.$sessionid));
						$tpl_inhalt->addComponent('seite_loeschen',          new PText($_FORMVARS['seite']));
						$tpl_inhalt->addComponent('einrichtung_id_loeschen', new PText($_FORMVARS['einrichtung_id']));

						// Eintraege listen
						$aktdatum    = '';
						$position    = 0;

						while (($sql_daten=sql_fetch_array($sql_ergebnis,SQL_ASSOC)) && $position++<$nutzereinstellung['eintragliste']) {
							if ($sql_daten['datum']!=$aktdatum) {
								if ($aktdatum!='')
									$tpl_inhalt->addComponent('inhalt', $tpl_datum);

								$tpl_datum->parse();
								$tpl_datum->addComponent('datum', new PText(convert_time($sql_daten['datum'])));

								$wochentag_id = strftime('%u',mktime(12,0,0,substr($sql_daten['datum'],5,2),substr($sql_daten['datum'],8,2),substr($sql_daten['datum'],0,4)));
								$tpl_datum->addComponent('wochentag', new PText($feld_wochentage[$wochentag_id]));
								$aktdatum = $sql_daten['datum'];
							}

							$tpl_eintrag->parse();
							$tpl_eintrag->addComponent('postbuch_id', new PText($sql_daten['postbuch_id']));

							$titeltext = '';
							$titeltext.= $sql_daten['bezeichnung'];

							if (!empty($sql_daten['str']))
								$titeltext.=', '.$sql_daten['str'];

							if (!empty($sql_daten['ort']))
								$titeltext.=', '.trim($sql_daten['plz'].' '.$sql_daten['ort']);

							$tpl_eintrag->addComponent('titel',             new PText($titeltext));
							$tpl_eintrag->addComponent('link_bearbeiten',   new PText($_FORMVARS['PHP_SELF'].'?modus=bearbeiten&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&postbuch_id='.$sql_daten['postbuch_id'].'&PHPSESSID='.$sessionid));
							$tpl_eintrag->addComponent('link_beantworten',  new PText($_FORMVARS['PHP_SELF'].'?modus=beantworten&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&referenz='.$sql_daten['postbuch_id'].'&postbuch_id=-1&PHPSESSID='.$sessionid));
							$tpl_eintrag->addComponent('link_weiterleiten', new PText($_FORMVARS['PHP_SELF'].'?modus=weiterleiten&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&referenz='.$sql_daten['postbuch_id'].'&postbuch_id=-1&PHPSESSID='.$sessionid));
							$infotext = '';
							$infotext.= $sql_daten['bezeichnung'].'<br />';

							if (!empty($sql_daten['str']))
								$infotext.= $sql_daten['str'].'<br />';

							if (!empty($sql_daten['ort']))
								$infotext.= trim($sql_daten['plz'].' '.$sql_daten['ort']).'<br />';
							if (!empty($sql_daten['land']))
								$infotext.= $sql_daten['land'].'<br />';

							if (!empty($sql_daten['fax']))
								$infotext.= 'Fax: '.$sql_daten['fax'].'<br />';

							if (!empty($sql_daten['email']))
								$infotext.= 'E-Mail: '.$sql_daten['email'].'<br />';

							if (!empty($sql_daten['bemerkung']))
								$infotext.= '<br />'.nl2br($sql_daten['bemerkung']);

							if ($_FORMVARS['postbuch_modus']=='eingang')	{
								$sql_abfrage_verweis="SELECT * FROM ".DBPREFIX."postbuch WHERE referenz='".$sql_daten['postbuch_id']."' AND referenz_typ='W'";
								$sql_ergebnis_verweis=sql_query($sql_abfrage_verweis);
								if (sql_num_rows($sql_ergebnis_verweis)>0)
									$infotext.='<br />Weitergeleitet an:<br />';
								while ($sql_daten_verweis=sql_fetch_assoc($sql_ergebnis_verweis)) {
									$empfaengerzeile='';
									$empfaengerzeile.=$sql_daten_verweis['bezeichnung'];
									if (!empty($sql_daten_verweis['str']))
										$empfaengerzeile.=', '.$sql_daten_verweis['str'];
									if (!empty($sql_daten_verweis['ort']))
										$empfaengerzeile.=', '.trim($sql_daten_verweis['plz'].' '.$sql_daten_verweis['ort']);
									if (!empty($sql_daten_verweis['land']))
										$empfaengerzeile.=', '.$sql_daten_verweis['land'];
									if (!empty($sql_daten_verweis['fax']))
										$empfaengerzeile.=', Fax: '.$sql_daten_verweis['fax'];
									if (!empty($sql_daten_verweis['email']))
										$empfaengerzeile.=', E-Mail: '.$sql_daten_verweis['email'];
									$empfaengerzeile.='<br />';
									$infotext.=$empfaengerzeile;
								}

								$sql_abfrage_verweis="SELECT * FROM ".DBPREFIX."postbuch WHERE referenz='".$sql_daten['postbuch_id']."' AND referenz_typ='A'";
								$sql_ergebnis_verweis=sql_query($sql_abfrage_verweis);
								if (sql_num_rows($sql_ergebnis_verweis)>0)
									$infotext.='<br />Antwort an:<br />';
								while ($sql_daten_verweis=sql_fetch_assoc($sql_ergebnis_verweis)) {
									$empfaengerzeile='';
									$empfaengerzeile.=$sql_daten_verweis['bezeichnung'];
									if (!empty($sql_daten_verweis['str']))
										$empfaengerzeile.=', '.$sql_daten_verweis['str'];
									if (!empty($sql_daten_verweis['ort']))
										$empfaengerzeile.=', '.trim($sql_daten_verweis['plz'].' '.$sql_daten_verweis['ort']);
									if (!empty($sql_daten_verweis['land']))
										$empfaengerzeile.=', '.$sql_daten_verweis['land'];
									if (!empty($sql_daten_verweis['fax']))
										$empfaengerzeile.=', Fax: '.$sql_daten_verweis['fax'];
									if (!empty($sql_daten_verweis['email']))
										$empfaengerzeile.=', E-Mail: '.$sql_daten_verweis['email'];
									$empfaengerzeile.='<br />';
									$infotext.=$empfaengerzeile;
								}
							} else {
                $sql_abfrage_verweis="SELECT * FROM ".DBPREFIX."postbuch WHERE postbuch_id='".$sql_daten['referenz']."'";
								$sql_ergebnis_verweis=sql_query($sql_abfrage_verweis);
								if ($sql_daten_verweis=sql_fetch_assoc($sql_ergebnis_verweis)) {
									if ($sql_daten['referenz_typ']=='W')
										$infotext.='<br />Weitergeleitet von:<br />';
									else
										$infotext.='<br />Antwort auf:<br />';
									$empfaengerzeile='';
									$empfaengerzeile.=$sql_daten_verweis['bezeichnung'];
									if (!empty($sql_daten_verweis['str']))
										$empfaengerzeile.=', '.$sql_daten_verweis['str'];
									if (!empty($sql_daten_verweis['ort']))
										$empfaengerzeile.=', '.trim($sql_daten_verweis['plz'].' '.$sql_daten_verweis['ort']);
									if (!empty($sql_daten_verweis['land']))
										$empfaengerzeile.=', '.$sql_daten_verweis['land'];
									if (!empty($sql_daten_verweis['fax']))
										$empfaengerzeile.=', Fax: '.$sql_daten_verweis['fax'];
									if (!empty($sql_daten_verweis['email']))
										$empfaengerzeile.=', E-Mail: '.$sql_daten_verweis['email'];
									$empfaengerzeile.='<br />';
									$infotext.=$empfaengerzeile;
								}
							}

							$tpl_eintrag->addComponent('vorschau', new PText(htmlentities(trim($infotext))));
							$tpl_datum->addComponent('eintrag',    $tpl_eintrag);
						}

						if ($aktdatum!='')
							$tpl_inhalt->addComponent('inhalt', $tpl_datum);

						$tpl_rahmen->addComponent('inhalt', $tpl_inhalt);
						break;
				}
				break;
		}
	}

	$tpl_rahmen->addComponent('schriftgroesse', new PText($nutzereinstellung['schriftgroesse']));
	$tpl_rahmen->addComponent('farbe', new PText($nutzereinstellung['farbe']));
	echo $tpl_rahmen->outputStrClean();

	session_write_close();
	sql_close($dbverbindung);
?>
