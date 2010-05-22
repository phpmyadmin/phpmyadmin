<?php
/**
* @package JAMA
* 
* Error handling
* @author Michael Bommarito
* @version 01292005
*/

//Language constant
define('LANG', 'EN');


//Error type constants
define('ERROR', E_USER_ERROR);
define('WARNING', E_USER_WARNING);
define('NOTICE', E_USER_NOTICE);

//All errors may be defined by the following format:
//define('ExceptionName', N);
//$error['lang'][N] = 'Error message';
$error = array();

/*
I've used Babelfish and a little poor knowledge of Romance/Germanic languages for the translations
here.  Feel free to correct anything that looks amiss to you.
*/

define('PolymorphicArgumentException', -1);
$error['EN'][-1] = "Invalid argument pattern for polymorphic function.";
$error['FR'][-1] = "Modèle inadmissible d'argument pour la fonction polymorphe.".
$error['DE'][-1] = "Unzulässiges Argumentmuster für polymorphe Funktion.";

define('ArgumentTypeException', -2);
$error['EN'][-2] = "Invalid argument type.";
$error['FR'][-2] = "Type inadmissible d'argument.";
$error['DE'][-2] = "Unzulässige Argumentart.";

define('ArgumentBoundsException', -3);
$error['EN'][-3] = "Invalid argument range.";
$error['FR'][-3] = "Gamme inadmissible d'argument.";
$error['DE'][-3] = "Unzulässige Argumentstrecke.";

define('MatrixDimensionException', -4);
$error['EN'][-4] = "Matrix dimensions are not equal.";
$error['FR'][-4] = "Les dimensions de Matrix ne sont pas égales.";
$error['DE'][-4] = "Matrixmaße sind nicht gleich.";

define('PrecisionLossException', -5);
$error['EN'][-5] = "Significant precision loss detected.";
$error['FR'][-5] = "Perte significative de précision détectée.";
$error['DE'][-5] = "Bedeutender Präzision Verlust ermittelte.";

define('MatrixSPDException', -6);
$error['EN'][-6] = "Can only perform operation on symmetric positive definite matrix.";
$error['FR'][-6] = "Perte significative de précision détectée.";
$error['DE'][-6] = "Bedeutender Präzision Verlust ermittelte.";

define('MatrixSingularException', -7);
$error['EN'][-7] = "Can only perform operation on singular matrix.";

define('MatrixRankException', -8);
$error['EN'][-8] = "Can only perform operation on full-rank matrix.";

define('ArrayLengthException', -9);
$error['EN'][-9] = "Array length must be a multiple of m.";

define('RowLengthException', -10);
$error['EN'][-10] = "All rows must have the same length.";

/**
* Custom error handler
* @param int $type Error type: {ERROR, WARNING, NOTICE}
* @param int $num Error number
* @param string $file File in which the error occured
* @param int $line Line on which the error occured
*/
function JAMAError( $type = null, $num = null, $file = null, $line = null, $context = null ) {
  global $error;
  
  $lang = LANG;
  if( isset($type) && isset($num) && isset($file) && isset($line) )  {
    switch( $type ) {
      case ERROR:
        echo '<div class="errror"><b>Error:</b> ' . $error[$lang][$num] . '<br />' . $file . ' @ L' . $line . '</div>';
        die();
        break;
      
      case WARNING:
        echo '<div class="warning"><b>Warning:</b> ' . $error[$lang][$num] . '<br />' . $file . ' @ L' . $line . '</div>';
        break;
      
      case NOTICE:
        //echo '<div class="notice"><b>Notice:</b> ' . $error[$lang][$num] . '<br />' . $file . ' @ L' . $line . '</div>';
        break;

      case E_NOTICE:
        //echo '<div class="errror"><b>Notice:</b> ' . $error[$lang][$num] . '<br />' . $file . ' @ L' . $line . '</div>';
        break;

      case E_STRICT:
        break;
		
	  case E_WARNING:
	  	break;

      default:
        echo "<div class=\"error\"><b>Unknown Error Type:</b> $type - $file @ L{$line}</div>";
        die();
        break;
    }
  } else {
    die( "Invalid arguments to JAMAError()" );
  }
}

// TODO MarkBaker
//set_error_handler('JAMAError');
//error_reporting(ERROR | WARNING);

