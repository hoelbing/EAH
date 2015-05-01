<?php
/**
* PHPMyLib (required file)
*
* @author i-fabrik GmbH
* @copyright 2002 by i-fabrik GmbH
* @version $Id: PObject.php,v 1.13 2006/03/21 11:57:41 ralph Exp $
*
*/

/**
* Diese Funktion liefert die aktuelle Version von PHPMyLib
* 	Warnung: nur fuer Kompatibilitaet mit aelteren Projekten vorhanden
**/
function getPVersion(&$lo, &$hi) {
    $hi = 1;
    $lo = 3;
} //getPVersion


/**
* Diese Klasse ist die Basisklasse fuer alle weiteren Klassen von PHPMyLib.
**/
class PObject {

    /**
    * Die Methode prueft, ob es sich bei $obj um eine von PObject abgeleitete Klasse handelt,
    * oder um eine Klasse des Typs PObject selbst
    *
    * <b>Parameter:</b>
    * $obj - Die Instanz einer Klasse, die ueberprueft werden soll
    *
    * <b>Rueckgabe:</b>
    * TRUE oder FALSE, je nach Ergebnis der ueberpruefung.
    **/
    function isPObject(&$obj) {
        if ( 
            isset($obj) && 
            is_object($obj) && 
            ( is_a($obj, 'PObject') || is_subclass_of($obj, 'PObject') )
        ) return TRUE; else return FALSE;
    }
    // PObject::isPObject


    /**
    * Die Methode prueft, ob es sich bei $obj um eine von PComponent abgeleitete Klasse handelt,
    * oder um eine Klasse des Typs PComponent selbst
    *
    * <b>Parameter:</b>
    * $obj - Die Instanz einer Klasse, die ueberprueft werden soll
    *
    * <b>Rueckgabe:</b>
    * true oder false, je nach Ergebnis der ueberpruefung.
    **/
    function isPComponent(&$obj) {
        // test, ob Objekt vom Typ PContainer od. PComponent uebergeben wurde
        if ( 
            isset($obj) &&
            is_object($obj) &&
            ( is_a($obj, 'PComponent') || is_subclass_of($obj, 'PComponent') )
        ) return TRUE;


        // falls kein Objekt uebergeben wurde, versuche PText-Objekt zuerzeugen
        if ( ! is_null($obj) && (FALSE !== $obj) && ((is_string($obj) && trim($obj) != '') || is_int($obj)) ) {
            $obj = new PText($obj);
            return TRUE;
        }
        return FALSE;
    }
    // PObject::isPComponent


    /**
    * Die Methode prueft, ob es sich bei $obj um eine von PTemplate abgeleitete Klasse handelt,
    * oder um eine Klasse des Typs PTemplate selbst
    *
    * <b>Parameter:</b>
    * $obj - Die Instanz einer Klasse, die ueberprueft werden soll
    *
    * <b>Rueckgabe:</b>
    * TRUE oder FALSE, je nach Ergebnis der ueberpruefung.
    **/
    function isPTemplate(&$obj) {
        // test, ob Objekt vom Typ PTemplate
        if ( 
            isset($obj) && 
            is_object($obj) && 
            ( is_a($obj, 'PTemplate') || is_subclass_of($obj, 'PTemplate') ) 
        ) return TRUE;

        return FALSE;
    }
    // PObject::isPTemplate


    /**
    * Diese Methode liefert den Klassennamen der aktuellen Instanz zurueck
    * <b>Parameter:</b>
    * keine
    *
    * <b>Rueckgabe:</b>
    * Klassenname der aktuellen Instanz
    **/
    function getClassName() {
       return get_class($this);
    }
    // PObject::getClassName

}
// class PObject


?>
