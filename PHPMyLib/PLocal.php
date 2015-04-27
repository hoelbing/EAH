<?php
/**
* PHPMyLib library for multi lingual websites
*
* @author i-fabrik GmbH
* @copyright 2002 by i-fabrik GmbH
* @version $Id: PLocal.php,v 1.11 2005/12/12 14:59:07 ralph Exp $
*
*/


/**
*	Klasse:				icText
*	Zweck:				Textformatierung, Textersetzung für Lokalisierungen
*	Funktionsweise:		in den Files definierte Texte werden in globalen (sprachunabhängigen) Textkeys(Konstanten) gespeichert
*						(aus Kompatibilitätsgründen, werden die textkeys auch noch in einem assoziativen Array gespeichert)
**/
class icText {
	var $language;	// Sprache
	var $folder = array( 1=>'txt', 2=>'eng', -1=>'.' );
	var $File   = array();

	/**
	*	Konstructor
	*	<b>Parameter:</b>
	*	enum $language
	*	<blockquote>	1	-	deutsch
	*					2	-	english
	*					-1	-	keine Sprache definiert (angegebene Dateien werden aus aktuellem Ordner gelesen)
	*				( falls kein Parameter, dann deutsch (=1) )	</blockquote>
	*	mixed $files ( String filename oderr Array of files )
	**/
	function icText( $language=1, $files='' ) {
		switch ( trim($language) ):
		// textfiles are in current directory
		case -1:
			$this->language=-1;
			break;
		// textfiles are in folder eng
		case 'english':
		case 2:
			$this->language=2;
			break;
		// textfiles are in folder txt
		case 'deutsch':
		case 1:
		default:
			$this->language=1;
			break;
		endswitch;

		// eventuelle Files aktivieren
		if ( !is_array($files) ) $this->addFile($files);
			else foreach ($files as $file) $this->addFile($file);

		// try to load standard text files
		$this->addFile( 'standard.csv' );				// initial translation table
	}
	// icText::icText

	/**
	*	Zusätzliche Textfiles einlesen
	*	<b>Format:</b>
	*		 	-text_key,"lokalisierter Text"
	*			-Platzhalter sind erlaubt ( %s, %d, ... )
	*			-mit Semikolon beginnende Zeilen werden ignoriert ( Kommentar )
	**/
	function addFile( $file ) { 
		$array = array();
		$file  = $this->folder[$this->language].'/'.$file;
		( $file = @file($file) ) || $file = array();

		for ($i=0;$i<count($file);$i++) {
			$line = trim($file[$i]);
			// Semikolon markiert Kommentare
			if ( substr($line,0,1)==';' || trim($line)=='' ) continue;
			$line  = explode(',', $line);
			$key   = trim( array_shift($line) );
			$value = trim( join(',', $line) );
			if ( substr($value,0,1) == '"' ) {
				$value = str_replace( "\\\"", '&&&&&', substr($value,1) );
				while ( ($pos=strpos($value, '"'))===false && (1+$i)<count($file) )
					$value.="\n".str_replace( "\\\"", '&&&&&', $file[++$i]);
				if ( $pos!==false ) $value = substr($value,0,$pos);
				$value = str_replace( '&&&&&', '"', $value );
			}
			$array[$key] = trim($value);

			// new version: define textkeys as global constants
			define($key, trim($value));
		}
		$this->File = array_merge( $this->File, $array );
	}
	// icText::addFile

	// load Textfile
	// Filename:	-text_key
	// Content:		"lokalisierter Text"
	//				-Platzhalter sind erlaubt ( %s, %d, ... )
	function addElement( $file ) {
		$tmp[$file] = join('', file($this->folder[$language].'/'.$file.'.csv'));
		$this->File = array_merge( $this->File, $tmp );
	}
	// icText::addElement

	/**
	*	returns the localized text for the given key
	*	( if $key not found then returns the key unchanged )
	*	<b>Warning!</b> no longer used, only for compatibility with older projects included
	**/
	function get( $key ) {
		if ($this->File[$key]) return $this->File[$key]; else return $key;
	}
	// icText::get

}
// class icText

?>