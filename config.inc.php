<?php
/**
 * Postbuch - Universitaet Leipzig
 * Hauptkonfiguration
 *
 * @author Erik Reuter
 * @copyright 2007 i-fabrik GmbH
 * @version $Id: config.inc.php,v 1.24 2007/02/21 14:38:45 erik Exp $
 *
 * Im Rahmen der Veranstaltung Softwarequalität im SS 2015 des Studigang Wirstschaftsingenieurwesen
 * mit Fachrichtung Informationstechnik soll das Postuch ,das ursprünglich von Erik Reuter von der 
 * Universität Leipzig entwickelt wurde, auf die Bedürfnisse der EAH Jena angepasst werden.
 * 
 * Im Rahmen der Vorlesung wird sich Gedanken über einen Anforderungskatalog gemacht, der im Laufe der 
 * Zeit eingearbeitet werden soll. Die Anforderungen werden mit Hilfe des Webportal www.agilespecs.com
 *  zusammengefasst und verwaltet. 
 * 
 * @author: Tobias Möller, Björn Hoffmann, Maik Tanneberg
 */

	$templatepfad = 'templates/';
	
/**
  * Die Dateiendung dwt steht für Adobe Dreamweaver Template.
  */
	$templatefiles = array('ausdruck'        => $templatepfad.'ausdruck.dwt',
	                      'blaettern'        => $templatepfad.'blaettern.dwt',
	                      'css_main'         => $templatepfad.'css_main.dwt',
	                      'css_calendar'     => $templatepfad.'css_calendar.dwt',
	                      'css_rewrite'      => $templatepfad.'css_rewrite.dwt',
	                      'einrichtung'      => $templatepfad.'einrichtungen.dwt',
	                      'einstellungen'    => $templatepfad.'einstellungen.dwt',
	                      'einzelanzeige'    => $templatepfad.'einzelanzeige.dwt',
	                      'erfassung'        => $templatepfad.'erfassen.dwt',
	                      'filter'           => $templatepfad.'filter.dwt',
	                      'filterliste'      => $templatepfad.'filterliste.dwt',
	                      'liste'            => $templatepfad.'liste.dwt',
	                      'login'            => $templatepfad.'login.dwt',
	                      'menue'            => $templatepfad.'menue.dwt',
	                      'nutzer'           => $templatepfad.'nutzer.dwt',
	                      'nutzerbearbeiten' => $templatepfad.'nutzer-eingabe.dwt',
	                      'rahmen'           => $templatepfad.'rahmen.dwt');

	if (!defined('DBPREFIX')) define('DBPREFIX','');
	if (!defined('TITEL_NEUEINTRAG')) define('TITEL_NEUEINTRAG','Neueintrag');
	if (!defined('TITEL_NEUEINTRAG_REFERENZ_WEITERLEITUNG')) define('TITEL_NEUEINTRAG_REFERENZ_WEITERLEITUNG','Neueintrag Weiterleitung');
	if (!defined('TITEL_NEUEINTRAG_REFERENZ_ANTWORT')) define('TITEL_NEUEINTRAG_REFERENZ_ANTWORT','Neueintrag Antwort');
	if (!defined('TITEL_BEARBEITEN')) define('TITEL_BEARBEITEN','Daten bearbeiten');

	// Menüdaten
	$menuedaten[0]['bezeichnung']  = 'Eingang';
	$menuedaten[0]['modus']        = 'eingang';
	$menuedaten[0]['berechtigung'] = 'leser';
	$menuedaten[1]['bezeichnung']  = 'Ausgang';
	$menuedaten[1]['modus']        = 'ausgang';
	$menuedaten[1]['berechtigung'] = 'leser';
	$menuedaten[2]['bezeichnung']  = 'Filter';
	$menuedaten[2]['modus']        = 'filter';
	$menuedaten[2]['berechtigung'] = 'leser';
	$menuedaten[3]['bezeichnung']  = 'Ausdruck';
	$menuedaten[3]['modus']        = 'ausdruck';
	$menuedaten[3]['berechtigung'] = 'leser';
	$menuedaten[4]['bezeichnung']  = 'Einstellungen';
	$menuedaten[4]['modus']        = 'einstellungen';
	$menuedaten[4]['berechtigung'] = 'leser';
	$menuedaten[5]['bezeichnung']  = 'Sicherung';
	$menuedaten[5]['modus']        = 'sicherung';
	$menuedaten[5]['berechtigung'] = 'hauptnutzer';
	$menuedaten[6]['bezeichnung']  = 'Nutzer';
	$menuedaten[6]['modus']        = 'nutzer';
	$menuedaten[6]['berechtigung'] = 'admin';

	// ... unsichtbare Modi
	$menuedaten[7]['bezeichnung']   = '';
	$menuedaten[7]['modus']         = 'liste';
	$menuedaten[7]['berechtigung']  = 'leser';
	$menuedaten[8]['bezeichnung']   = '';
	$menuedaten[8]['modus']         = 'bearbeiten';
	$menuedaten[8]['berechtigung']  = 'nutzer';
	$menuedaten[9]['bezeichnung']   = '';
	$menuedaten[9]['modus']         = 'bearbeiten1';
	$menuedaten[9]['berechtigung']  = 'nutzer';
	$menuedaten[10]['bezeichnung']  = '';
	$menuedaten[10]['modus']        = 'weiterleiten';
	$menuedaten[10]['berechtigung'] = 'nutzer';
	$menuedaten[11]['bezeichnung']  = '';
	$menuedaten[11]['modus']        = 'beantworten';
	$menuedaten[11]['berechtigung'] = 'nutzer';
	$menuedaten[12]['bezeichnung']  = '';
	$menuedaten[12]['modus']        = 'nutzerbearbeiten';
	$menuedaten[12]['berechtigung'] = 'admin';
	$menuedaten[13]['bezeichnung']  = '';
	$menuedaten[13]['modus']        = 'nutzerbearbeiten1';
	$menuedaten[13]['berechtigung'] = 'admin';

	// Standardwerte Nutzereinstellungen
	$nutzereinstellung = array('startmodus'     => 'eingang',
	                           'listenmodus'    => 'liste', // liste/einzel/tage
	                           'eintragliste'   => 20, // Anzahl der Tage bei 'normaler' Liste
	                           'eintragtag'     => 10, // Anzahl Tage bei aufklappenden Tagen
														 'farbe'          => 'ff800f',
	                           'schriftgroesse' => 'small');



	$feld_listenmodus = array('einzel' => 'Einzelanzeige (Datens&auml;tze einzeln bl&auml;ttern)',
	                          'liste'  => 'Listenanzeige (immer komplett ge&ouml;ffnet)',
	                          'tage'   => 'Tagesliste (separat aufklappbar)');

	$feld_schriftgroessen = array('small' => array('Normal','66.5%'),
	                              'big'   => array('Gro&szlig;','72.5%'));

	$feld_farben = array('ff800f','bb8545','ffe375','6f83c0','35c2de','62c29c','bcbdc1');

	$feld_nutzertyp = array('leser'       => 'Leser',
	                        'nutzer'      => 'Bearbeiter',
	                        'hauptnutzer' => 'Hauptnutzer',
	                        'admin'       => 'Verwalter');

	$feld_wochentage = array('','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag');

	$nutzerberechtigung = array('admin'       => array('leser','nutzer','hauptnutzer','admin'),
	                            'hauptnutzer' => array('leser','nutzer','hauptnutzer'),
	                            'nutzer'      => array('leser','nutzer'),
	                            'leser'       => array('leser'));

	define('MAXSESSIONTIME',10800);
?>
