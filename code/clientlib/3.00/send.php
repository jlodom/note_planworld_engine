<?php


/* SEND CATEGORY VERBS FOR PLANWORLD CLIENT LAYER. */

/* Get a send conversation. Takes one argument -- the username at the other end of the conversation. */
function sendGet($arrayRestInputs){
	if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Send.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
		require_once($arrayRestInputs['enginebasedir'] . '/lib/Send.php');
		require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
		$objectToken = new NodeToken ();
		if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (count($arrayRestInputs['arguments']) > 0)){
			$arguments = $arrayRestInputs['arguments'];
			$toUser = new User($arguments[0]);
			$objectSend = new Send();
			$sendReturn = $objectSend->getMessages($objectToken->uid, $toUser->getUserID());
			if($sendReturn < 0){
				return '';
			}
			else{
				if(is_array($sendReturn)){
					$limit = count($sendReturn);
					for($i = 0; $i < $limit; $i++){
						$sendReturn[$i]['sent'] = date(DATE_ATOM, $sendReturn[$i]['sent']);
						$sendReturn[$i]['seen'] = date(DATE_ATOM, $sendReturn[$i]['seen']);
					}
				}
				return $sendReturn;
			}
		}
		else{
			return ''; /* BUG: If the client does not send a token, an empty string is returned. An invalid token should result in an error somewhere. */
		}
	}
	else{
		return '';
	}
}


/* Send a message to a user. Takes two arguments -- user to send to and message to send. ) */
function sendPost($arrayRestInputs){
	if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Send.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
		require_once($arrayRestInputs['enginebasedir'] . '/lib/Send.php');
		require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
		$objectToken = new NodeToken ();
		if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (count($arrayRestInputs['arguments']) > 0) && (!(empty($arrayRestInputs['post'])))){
			$arguments = $arrayRestInputs['arguments'];
			$toUser = new User($arguments[0]);
			$objectSend = new Send();
			$sendReturn = $objectSend->sendMessage($objectToken->uid, $toUser->getUserID(), $arrayRestInputs['post']);
			return $sendReturn;
		}
		else{
			return false;
		}
	}
	else{
		return false;
	}
}


/* Get a list of send conversations with the other user, the time of the last message, and whether that message was seen. Takes no arguments. */
function sendlistGet($arrayRestInputs){
	if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Send.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
		require_once($arrayRestInputs['enginebasedir'] . '/lib/Send.php');
		require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
		$objectToken = new NodeToken ();
		if($objectToken->retrieveToken($arrayRestInputs['token'])){
			$objectSend = new Send();
			$sendlistReturn = $objectSend->getSendList($objectToken->uid);
			if($sendlistReturn < 0){
				return '';
			}
			else{
				if(is_array($sendlistReturn)){
					$limit = count($sendlistReturn);
					for($i = 0; $i < $limit; $i++){
						$sendlistReturn[$i]['senddate'] = date(DATE_ATOM, $sendlistReturn[$i]['senddate']);
						$sendlistReturn[$i]['seen'] = date(DATE_ATOM, $sendlistReturn[$i]['seen']);
					}
				}
				return $sendlistReturn;
			}
		}
		else{
			return '';
		}
	}
	else{
		return '';
	}
}
?>