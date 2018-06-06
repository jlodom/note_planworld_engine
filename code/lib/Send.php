<?php
/**
 * Send functions.
 */

require_once('Planworld.php');

class Send {

	/**
	 * Return all messages between $uid and $to_uid
	 */

	function getMessages ($uid, $to_uid) {
		$planworldForGetMessages = new Planworld();
		if(($planworldForGetMessages->isUser($uid)) && ($planworldForGetMessages->isUser($to_uid))){
			$dbh = Planworld::_connect();
			try{
				$query = $dbh->prepare('UPDATE send SET seen= :currenttime WHERE uid= :uid AND to_uid= :to_uid AND seen = 0');
				$queryArray = array('currenttime' => time(), 'uid' => $uid, 'to_uid' => $to_uid);
				$query->execute($queryArray);
				$query2 = $dbh->prepare('select u.username as fromuser, u2.username as touser, s.sent, s.seen, s.message from users u, send s LEFT JOIN users as u2 on u2.id=s.to_uid where ((s.uid=:uid AND s.to_uid=:to_uid) OR (s.uid=:to_uid AND s.to_uid=:uid)) and s.uid=u.id ORDER BY s.sent ASC');
				$queryArray2 = array('uid' => $uid, 'to_uid' => $to_uid);
				$query2->execute($queryArray2);
				$result = $query2->fetchAll();
				if (!$result){
					return PLANWORLD_ERROR;
				}
				else {
					return $result;
				}
			}
			catch(PDOException $badquery){
				return PLANWORLD_ERROR;
			}
		}
		return PLANWORLD_ERROR;
	}


	/**
	 * Send a message from $uid to $to_uid
	 */
	function sendMessage ($uid, $to_uid, $message) {
		$dbh = Planworld::_connect();
		if(!$dbh){
			return false;
		}
		$planworldForSendMessages = new Planworld();
		/* If the message is being sent to a remote user, do this. */
		if ($planworldForSendMessages->isRemoteUser($to_uid)) {
			list($to_user, $host) = split("@", $planworldForSendMessages->idToName($to_uid));
			$from_user = $planworldForSendMessages->idToName($uid) . "@" . PW_NAME;
			$nodeinfo = $planworldForSendMessages->getNodeInfo($host);
			xu_rpc_http_concise(array('method' => 'planworld.send.sendMessage',
					'args'   => array($from_user, $to_user, $message),
					'host'   => $nodeinfo['Hostname'],
					'uri'    => $nodeinfo['Path'],
					'port'   => $nodeinfo['Port'],
					'debug'  => 0));
			try{
				$query = $dbh->prepare('INSERT INTO send (uid, to_uid, sent, seen, message) VALUES (:uid,:to_uid,:currenttime,:currenttime,:message)');
				$queryArray = array('uid' => $uid, 'to_uid' => $to_uid, 'currenttime' => time(), 'message'=> $planworldForSendMessages->basicTextSanitization($message));
				return $query->execute($queryArray);
			}
			catch(PDOException $badquery){
				return false;
			}
		}
		/* If the message is being sent to a local user, do this. */
		else {
			/* This is sort of a secret feature. Forwarding of send messages from one user to another (i.e. if you had an old user or a shared user. */
			$fwd = $planworldForSendMessages->getPreference($to_uid, 'send_forward');
			$to_uid_final = $to_uid;
			$message_final = $message;
			if (($fwd != PLANWORLD_ERROR) && ($fwd)) {
				$to_uid_final = $planworldForSendMessages->nameToId($fwd);
				/* error_log("forwarding to ${fwd_uid} ({$fwd})"); */
				/* If the message is being forwarded to a remote user, do this. */
				if ($planworldForSendMessages->isRemoteUser($to_uid_final)) {
					$message_final = "[fwd:" . $planworldForSendMessages->idToName($to_uid) . "@" . PW_NAME . "] " . $message;
					list($to_user, $host) = split("@", $fwd);
					if (!$planworldForSendMessages->isRemoteUser($uid)) {
						$from_user = $planworldForSendMessages->idToName($uid) . "@" . PW_NAME;
					}
					else {
						$from_user = $planworldForSendMessages->idToName($uid);
						list($f_user, $f_host) = split('@', $from_user);
						if ($f_host == $host) {
							$from_user = $f_user;
						}
					}
					$nodeinfo = $planworldForSendMessages->getNodeInfo($host);
					xu_rpc_http_concise(array('method' => 'planworld.send.sendMessage',
							'args'   => array($from_user, $to_user, $message_final),
							'host'   => $nodeinfo['Hostname'],
							'uri'    => $nodeinfo['Path'],
							'port'   => $nodeinfo['Port'],
							'debug'  => 0));
				}
				else {
					$message_final = "[fwd:" . $planworldForSendMessages->idToName($to_uid) . "] " . $message;
				}
			}
			try{
				$query = $dbh->prepare('INSERT INTO send (uid, to_uid, sent, seen, message) VALUES (:uid,:to_uid,:currenttime,:currenttime,:message)');
				$queryArray = array('uid' => $uid, 'to_uid' => $to_uid_final, 'currenttime' => time(), 'message'=> $planworldForSendMessages->basicTextSanitization($message_final));
				return $query->execute($queryArray);
			}
			catch(PDOException $badquery){
				return false;
			}
		}
		return false;
	}


	/* Return a watchlist of send information.
		Validation and date processing handled by api library, although we do some validation here as a backstop.
		We grab two simple queries and combine them in code because the unified query was 5X slower and it made the API slow.
		On the reference Planworld database, the complex query was nearing 6 seconds, and the code below returns in 1 */

	function getSendList ($uid) {
		$planworldForGetSendList = new Planworld();
		if($planworldForGetSendList->isUser($uid)){
			$dbh = Planworld::_connect();
			$outboundreturn = '';
			$inboundreturn = '';
			$indexedreturn = array();
			$indexedkeylist = array();
			$finalarray = array();
			try{
				$query1 = $dbh->prepare('SELECT U.username AS name, MAX(S.SENT) AS othersenddate, MAX(S.SEEN) as selfseen FROM send S, users U WHERE U.id=S.uid  AND S.to_uid=:uid GROUP BY S.uid  ORDER BY name');
				$queryArray1 = array('uid' => $uid);
				$query1->execute($queryArray1);
				$return1 = $query1->fetchAll();
				if (!$return1){
					return PLANWORLD_ERROR;
				}
				else {
					$inboundreturn = $return1;
				}
			}
			catch(PDOException $badquery){
				return PLANWORLD_ERROR;
			}
			/* Get message information about messages from the self to the other. */
			try{
				$query2 = $dbh->prepare('SELECT U.username AS name, MAX(S.sent) AS selfsenddate, MAX(S.SEEN) as otherseen FROM send S, users U WHERE U.id=S.to_uid AND S.uid=:uid GROUP BY S.to_uid ORDER BY name');
				$queryArray2 = array('uid' => $uid);
				$query2->execute($queryArray2);
				$return2 = $query2->fetchAll();
				if (!$return2){
					return PLANWORLD_ERROR;
				}
				else {
					$outboundreturn = $return2;
				}
			}
			catch(PDOException $badquery){
				return PLANWORLD_ERROR;
			}
			foreach($inboundreturn as $row){
				$indexedreturn[$row["name"]] = array("name"=> $row["name"], "othersenddate"=> $row["othersenddate"], "selfsenddate"=> 0, "selfseen"=> $row["selfseen"], "otherseen"=> 0);
				$indexedkeylist[] = $row["name"];
			}

			foreach($outboundreturn as $row){
				if(in_array($row["name"], $indexedkeylist)){
					$indexedreturn[$row["name"]]["selfsenddate"]=$row["selfsenddate"];
					$indexedreturn[$row["name"]]["otherseen"]=$row["otherseen"];
				}
				else{
					$indexedreturn[$row["name"]] = array("name"=> $row["name"], "othersenddate"=>0, $row["selfsenddate"], "selfseen"=>0, "otherseen"=> $row["otherseen"]);
					$indexedkeylist[] = $row["name"];
				}
			}

			foreach($indexedreturn as $row){
				$finalarray[] = $row;
			}

			return $finalarray;
		}
		return PLANWORLD_ERROR;
	}


}
?>
