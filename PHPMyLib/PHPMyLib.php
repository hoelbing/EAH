<?php
/**
* main include script for PHPMyLib (php4 and php5 compatible) 
*
* @author R.Kropp, i-fabrik GmbH 
* @copyright 2002 by i-fabrik GmbH 
* @version $Id: PHPMyLib.php,v 1.18 2006/11/10 17:40:53 ralph Exp $ 
*
*/
if ( !defined('PML_DEBUG') ) define ('PML_DEBUG', 0);

if ( !defined('PML_INCLUDED') && !defined('PHPMyLib_INCLUDED') ) {

    define('PML_INCLUDED',     1);
    define('PML_MAIN_VERSION', 3);
    define('PML_PATH',         dirname(__FILE__).'/');  // set include path from this script 

    // for backward compatibility 
    define('PHPMyLib_INCLUDED', 1);

    // set subdirectory dependent on php version
    if ( version_compare(phpversion(), '5.0.0' ) < 0 ) define('PML_SUBDIR', ''); else define('PML_SUBDIR', 'php5/');

    // define which scripts should be included 
    $PML_include = array();
    $PML_include[] = 'PUtil.php';                  // library with helper functions (i.e. character conversion, HTTP authentification)
    //$PML_include[] = 'PPowerslave.php';          // functions for Powerslave user
    $PML_include[] = 'PLocal.php';                 // library for multi lingual websites (all texts were defined by keys in seperate files)
    $PML_include[] = 'PAdmin.php';                 // base class for an admin tool 
    $PML_include[] = PML_SUBDIR.'PObject.php';     // template library 
    $PML_include[] = PML_SUBDIR.'PComponent.php';  // template library 
    $PML_include[] = PML_SUBDIR.'PPage.php';       // template library 

    // include existing files 
    foreach ( $PML_include as $PML_file ) {        
        if ( @is_readable(PML_PATH.$PML_file) ) {
            if ( defined('PML_DEBUG') && PML_DEBUG > 0 ) echo PML_PATH.$PML_file.'<br>';
            require_once(PML_PATH.$PML_file);
        } elseif ( @is_readable('PHPMyLib/'.$PML_file) ) {
            if ( defined('PML_DEBUG') && PML_DEBUG > 0 ) echo 'PHPMyLib/'.$PML_file.'<br>';
            require_once('PHPMyLib/'.$PML_file);
        }
    }

    unset($PML_include);
    unset($PML_file);



/**
* Diese Funktion liefert die aktuelle Version von PHPMyLib als String zurück
**/
function PVersion() {

    $files = array('PHPMyLib', PML_SUBDIR.'PObject', PML_SUBDIR.'PPage', PML_SUBDIR.'PComponent', 'PUtil', 'PPowerslave', 'PLocal', 'PAdmin');
    foreach ( $files as $file ) {
        $content  = file(PML_PATH.$file.'.php');
        $content  = $content[6];
        preg_match("/$file\.php,v ([0-9]+)\.([0-9]+) /i", $content, $match);
        $version += $match[2];
    }

    return (PML_MAIN_VERSION.'.'.substr($version, 0, 1).'.'.substr($version, 1));
} //PVersion



}

//print_r(get_included_files());

?>