<?php

/* Basic client security check called by clien/index.php. */

/* This file lives in the clientlib folder instead of a subfolder (because it is version independent), so root is one level above. */
$_base = dirname(__FILE__) . '/../'; require_once($_base . 'config.php');
require_once($_base . 'lib/Security.php');
require_once($_base . 'lib/NodeToken.php');

function securityCheck($ip, $token){
	$boolPass = false;
	$securityCheckObject = new Security();
	$nodeTokenCheck = new NodeToken();
	if($nodeTokenCheck->retrieveToken($token)){
		if($securityCheckObject->ipCheck($ip)){
			$boolPass = true;
		}
		else{
			$securityCheckObject->addIpFailure($ip);
			$boolPass = false;
		}
	}
	else{
		$securityCheckObject->addIpFailure($ip);
		$boolPass = false;
	}
	return $boolPass;

}



?>