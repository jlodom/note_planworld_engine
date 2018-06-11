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

function userslocalGet($arrayRestInputs){
	$arrayUsers = array();
	if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Planworld.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
		require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
		$objectToken = new NodeToken ();
		if($objectToken->retrieveToken($arrayRestInputs['token'])){
			if($objectToken->valid){
				require_once($arrayRestInputs['enginebasedir'] . '/lib/Planworld.php');
				$pw = new Planworld();
				$arrayUsers = $pw->getAllUsers();
			}
		}
	}
	return $arrayUsers;
}

?>