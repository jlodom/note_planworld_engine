<?php


/* SYSTEM CATEGORY VERBS FOR PLANWORLD CLIENT LAYER. */

/* Planworld Version
	Max version supported by server.
	Arguments: None. Return: Version Float. */
function versionGet ($arrayRestInputs) {

	$versionReturn = '0';

	if(file_exists($arrayRestInputs['enginebasedir'] . '/config.php')){
		require_once($arrayRestInputs['enginebasedir'] . '/config.php');
		if(defined('PW_VERSION')){
			$versionReturn = PW_VERSION;
		}
	}
	return $versionReturn;
}

?>