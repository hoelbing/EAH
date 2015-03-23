<?php
/**
* Postbuch - Universitaet Leipzig
* Projektfunktionen
*
* @author Erik Reuter
* @copyright 2007 i-fabrik GmbH
* @version $Id: projekt.inc.php,v 1.5 2007/01/31 14:16:50 erik Exp $
*
*/

  /*
  *
  *  Erstellt das Menuetemplate fuer den aktuellen Modus
  *
  */
  function menue_ausfuellen($modus_aktiv) {

    global $templatefiles;
    global $menuedaten;
    global $_FORMVARS;
    global $sessionid;
    global $nutzerberechtigung;

		// Nutzerberechtigung auslesen
		$sql_abfrage="SELECT * FROM ".DBPREFIX."nutzer WHERE nutzer_id='".$_FORMVARS['nutzer_id']."'";
		$sql_ergebnis=sql_query($sql_abfrage);
		$sql_daten=sql_fetch_array($sql_ergebnis,SQL_ASSOC);

		$berechtigung=$nutzerberechtigung[$sql_daten['nutzertyp']];

    $tpl_menue=new PTemplate(NULL,$templatefiles['menue']);
    $tpl_eintrag=$tpl_menue->extractBlock('Eintrag');
    $tpl_aktiv=$tpl_menue->extractBlock('Aktiv');

    $anzahl=count($menuedaten);

    for ($l=0;$l<$anzahl;$l++) {
			if (!empty($menuedaten[$l]['bezeichnung']) && in_array($menuedaten[$l]['berechtigung'],$berechtigung)) {
				$tpl_eintrag->parse();
				$tpl_eintrag->addComponent('titel',new PText($menuedaten[$l]['bezeichnung']));
				$tpl_eintrag->addComponent('link',new PText($_FORMVARS['PHP_SELF'].'?modus='.$menuedaten[$l]['modus'].'&einrichtung_id='.$_FORMVARS['einrichtung_id'].'&PHPSESSID='.$sessionid));
				if ($menuedaten[$l]['modus']==$modus_aktiv)
				  $tpl_eintrag->addComponent('aktiv',$tpl_aktiv);
				$tpl_menue->addComponent('eintrag',$tpl_eintrag);
			}
    }

    $tpl_menue->addComponent('link_abmelden',new PText($_FORMVARS['PHP_SELF'].'?action=abmelden&PHPSESSID='.$sessionid));

    return $tpl_menue;

  }

?>
