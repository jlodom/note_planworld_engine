<?php

/* Client Abstraction Layer - Clients access this page to interact with Planworld through the client REST API.
   The idea is that it does not call deeper into the Planworld engine unless it is needed.
   This file does not call the engine libraries directly, but calls additional client files that in turn call libraries.

   This code handles all the interaction with the URL and sending and receiving.
   API requests are in the form:
   http://planworldbase/client/ClientVersion/Category/planworldVerb/argument1/argument2.format?token=TOKENID
   There can be as many arguments as needed. Token is not required. Category exists to make engine code easier in implementation.
   The client version is represented as a string containing a number with two decimal places.
   Clients can do a GET or a POST, sometimes with the same verb.
   POST data must be named "planworld_post"


*/

/* INITIAL SETUP - Variables here may need to be modified. */

/* Main Variables - May require editing during setup. */
$pathClientBase = dirname(__FILE__);
$pathClientLibBase = dirname(__FILE__) . '/../clientlib';
$arraySupportedFormats = array('txt', 'xml', 'json');
$pathClientPrefix = '/client';

/* TODO: Do we need a separate config file for the client API? Things like $arraySupportedFormats and the client path? */

/* Import JSON implementation if PHP is before 5.2 */
if (!defined('PHP_VERSION_ID')) {
  $version = explode('.', PHP_VERSION);
  define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
if(PHP_VERSION_ID < 50200){
  include_once('oldjson.php');
}



/* FUNCTIONS */

/*	Change the HTTP Header to set status code for REST compliance.
		Arguments: Numeric status code.
		Return: Void. */
function statusCode($code){

  switch($code){
  case 200:
    header('HTTP/1.1 200 OK');
    break;
  case 201:
    header('HTTP/1.1 201 Created');
    break;
  case 400:
    header('HTTP/1.1 400 Bad Request');
    break;
  case 401:
    header('HTTP/1.1 401 Not Authorized');
    break;
  case 403:
    header('HTTP/1.1 403 Forbidden');
    break;
  case 404:
    header('HTTP/1.1 404 Not Found');;
    break;
  case 501:
    header('HTTP/1.1 501 Not Implemented');
    break;
  case 503:
    header('HTTP/1.1 503 Service Unavailable');
    break;
  default:
    header('HTTP/1.1 404 Not Found');
  }

}


/* A basic function for taking a PHP array and turning it into XML with rows.
	Lovingly borrowed and modified from Stack Overflow
	http://stackoverflow.com/questions/1397036/how-to-convert-array-to-simplexml
	Where $key = 'row'; the original code had $key = 'row_'. $key; */
function array_to_xml( $data, $xml_data ) {
  foreach( $data as $key => $value ) {
    if( is_numeric($key) ){
      $key = 'row';
    }
    if( is_array($value) ) {
      $subnode = $xml_data->addChild($key);
      array_to_xml($value, $subnode);
    } else {
      $xml_data->addChild("$key",htmlspecialchars("$value"));
    }
  }
  return $xml_data;
}



/* MAIN METHOD */

/* Receive the API request. This works in tandem with an .htaccess file in the same directory. */
if(!empty($_REQUEST['apiurl'])){

  /* Key Variables. */
  $stringToken = null;
  $floatClientVersion = 0;
  $stringCategory = '';
  $stringPlanworldVerb = '';
  $stringPlanworldVerbSuffix = 'Get';
  $stringFormat = 'txt';
  $arrayArguments = array();
  $postData = null;
  $boolValidUrl = true;
  $boolSecurityCheckPassed = true;
  $urlPath = '';
  $urlQuery = '';
  $pathArray = array();

  /* Url Parsing, Key Variable Assignment, and Element Validation */
  $urlArray = parse_url($_REQUEST['apiurl']);
  if(array_key_exists('path', $urlArray)){
    $urlPath = $urlArray['path'];
  }
  /* Get Token */
  if(array_key_exists('token', $_REQUEST)){
    if(ctype_alnum($_REQUEST['token'])){ /* Do some proper validation when validators have been built. */
      $stringToken = strval($_REQUEST['token']);
      if(file_exists($pathClientLibBase . '/security.php')){
        include_once($pathClientLibBase . '/security.php');
        $boolSecurityCheckPassed = securityCheck($_SERVER['REMOTE_ADDR'], $stringToken);
      }
    }
  }
  /*Get Format */
  $urlPath = trim(rtrim($urlPath,'/'));
  $intFormatLastPeriod = strrpos($urlPath, '.');
  $intFormatLastSlash = strrpos($urlPath, '/');
  if((($intFormatLastPeriod - $intFormatLastSlash) > 0) && ($intFormatLastPeriod !== false)){
    $stringFormat = strtolower(ltrim(substr($urlPath, $intFormatLastPeriod), '.'));
    $urlPath = rtrim(substr($urlPath, 0, $intFormatLastPeriod), '/');
    if(!(in_array($stringFormat, $arraySupportedFormats))){
      $stringFormat = 'txt';
    }
  }
  /* Examine for Basic Elements */
  /* Check for off-by-one here. */

  $pathArray = explode('/', $urlPath);
  if(count($pathArray) < 3){
    $boolValidUrl = false;
  }
  else{
    /* API Call Version */
    $floatClientVersion = number_format(floatval($pathArray[0]), 2); /* A bad value will return 0 anyway. We are hardcoding decimals to 2. */
    if($floatClientVersion == 0){
      $boolValidUrl = false;
    }
    /* Category */
    if(ctype_alnum($pathArray[1])){
      $stringCategory = $pathArray[1];
    }
    else{
      $boolValidUrl = false;
    }
    /* Verb */
    if(ctype_alnum($pathArray[2])){
      $stringPlanworldVerb = $pathArray[2];
    }
    else{
      $boolValidUrl = false;
    }
    /* Get post data if available. */
    if(!empty($_POST['planworld_post'])){ /* Is this supposed to be hard-coded? */
      $stringPlanworldVerbSuffix = 'Post';
      $postData = $_POST['planworld_post'];
    }
    /* Save the other array elements as arguments. */
    if(count($pathArray) > 3){
      $arrayArguments = array_splice($pathArray, 3);
    }
  }


  /* Execute Planworld Verb */
  if(($boolValidUrl) && ($boolSecurityCheckPassed)){
    if(file_exists($pathClientLibBase . '/' . $floatClientVersion . '/' . $stringCategory . '.php')){
      include_once($pathClientLibBase . '/' . $floatClientVersion . '/' . $stringCategory . '.php');
      $functionVerb = $stringPlanworldVerb . $stringPlanworldVerbSuffix;
      if(function_exists($functionVerb)){
        $arrayRestInputs = array(
          'token' => $stringToken,
          'arguments' => $arrayArguments,
          'post' => $postData,
          'enginebasedir' => dirname(__FILE__) . '/../'
        );
        $output = $functionVerb($arrayRestInputs);
        $finalOutput = '';
        if(is_array($output)){
          if(strcmp($stringFormat, 'xml') == 0){
            $xmlOutput = new SimpleXMLElement('<?xml version="1.0" ?><' . $functionVerb . '></' . $functionVerb . '>');
            array_to_xml($output, $xmlOutput);
            $finalOutput = $xmlOutput->asXML();
          }
          else if(strcmp($stringFormat, 'json') == 0){
              $jsonArray = array($stringPlanworldVerb => $output);
              $finalOutput = json_encode($jsonArray);
            }
          else{
            $finalOutput = print_r($output,true); /* This is not debug code, this is to output text for now. */
          }
        }
        /* Added to let us do simple boolean returns for methods that post. We can adjust the output results if desired. 20191007 JLO2 */
        else if(is_bool($output)){
            $stringOutput = 'FALSE';
            if($output){
              $stringOutput = 'TRUE';
            }
            if(strcmp($stringFormat, 'xml') == 0){
              $finalOutput = '<?xml version="1.0" ?><' . $functionVerb . '>' . htmlspecialchars($stringOutput) . '</' . $functionVerb . '>';
            }
            else if(strcmp($stringFormat, 'json') == 0){
                $jsonArray = array($stringPlanworldVerb => $stringOutput);
                $finalOutput = json_encode($jsonArray);
              }
            else{
              $finalOutput = print_r($finalOutput,true); /* This is not debug code, this is to output text for now. */
            }
          }
        else if(is_object($output)){
            // We do not currently handle objects.
          }
        else{
          $stringOutput = strval($output);
          $finalOutput = $stringOutput;
          if(strcmp($stringFormat, 'xml') == 0){
            $finalOutput = '<?xml version="1.0" ?><' . $functionVerb . '>' . htmlspecialchars($stringOutput) . '</' . $functionVerb . '>';
          }
          else if(strcmp($stringFormat, 'json') == 0){
              $jsonArray = array($stringPlanworldVerb => $stringOutput);
              $finalOutput = json_encode($jsonArray);
            }
          else{
            $finalOutput = print_r($finalOutput,true); /* This is not debug code, this is to output text for now. */
          }
        }
        statusCode(200);
        echo $finalOutput;
      }
      else{
        statusCode(400);
        echo 'Either you are requesting an incorrect method, or this node has not implemented this method.';
      }
    }
    else if(file_exists($pathClientLibBase . '/' . $floatClientVersion)){
        statusCode(501);
        echo 'Invalid Planworld REST category.';
      }
    else{
      statusCode(501);
      echo 'Unsupported Planworld API version.';
    }
  }
  else if(!($boolSecurityCheckPassed)){
      statusCode(403);
      echo 'IP Locked by Intruder Detection or Invalid Token.';

    }
  else{
    statusCode(400);
    echo 'Invalid REST Url.';
  }
}
else{
  statusCode(400);
  echo 'No REST Url.';
}


?>