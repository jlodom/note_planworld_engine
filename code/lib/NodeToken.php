<?php
/* $Id: NodeToken.php,v 2.0 2017/03/11 JLO2 Exp $ */

/* These are the token routines for use with the version 3 client API.
 * They require a tokens table in the DB with four fields: token, uid, expire, clientname
 * Also let's take a look at the validation logic again.
 */

/* includes */
$_base = dirname(__FILE__) . '/../'; /* This file lives in the lib folder, so root is one level above. */
require_once($_base . 'config.php');
require_once($_base . 'lib/Planworld.php');
require_once($_base . 'lib/User.php');

/* Class to handle remote authentication. */
class NodeToken {

	var $tokenNumber;
	var $uid;
	var $username;
	var $expire;
	var $clientname;
	var $dbh;
	var $valid; /* Whether the token is currently good. */

	/* Constructor. Sets up database connection, no more. */
	function NodeToken () {
		$this->dbh = Planworld::_connect();
		$this->valid = false;
	}


	/* Fill a freshly-constructed token with new values. */
	function createToken($usernameFromAuth, $clientname){
		$badToken = true;
		$token = false;
		$this->username = $usernameFromAuth;
		while($badToken){
			$proposedTokenNumber = NODE_TOKEN_PREFIX + rand(1, 999999);
			$checkToken = new NodeToken();
			if ($checkToken->retrieveToken($proposedTokenNumber) == false){
				$this->tokenNumber = $proposedTokenNumber;
				$badToken = false;
			}
			else{
				$proposedTokenNumber = 0;
				$badToken = true;
			}
			unset($checkToken);
		}
		if(!ctype_alnum($clientname)){
			$clientname = "anoncow";
		}
		$this->clientname = $clientname;
		$this->expire = time() + TOKEN_LIFE;
		$tempUser = new User($this->username);
		$this->uid = $tempUser->getUserID();
		if($this->uid > 0){
			$this->destroyUserClientTokens($this->uid, $this->clientname);
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('INSERT INTO tokens (token, uid, expire, clientname) VALUES (:token, :uid, :expire, :clientname)');
				$queryArray = array('token' => $this->tokenNumber, 'uid' => $this->uid, 'expire' => $this->expire, 'clientname' => $this->clientname);
				$query->execute($queryArray);
				$this->valid = true;
			}
			catch(PDOException $badquery){
				$this->valid = false;
			}
		}
		else{
			$this->valid = false;
		}
		return $this->valid;
	}


	/* Populate a token object with existing token information from DB. */
	function retrieveToken($token){
		$this->valid = false;
		if($this->validTokenFormat($token)){  /* Prevent SQL injection or other exploits. */
			$this->tokenNumber = $token;
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('SELECT uid, expire, clientname FROM tokens WHERE token=:token');
				$queryArray = array('token' => $this->tokenNumber);
				$query->execute($queryArray);
				$result = $query->fetch();
				$this->uid = (int)$result['uid'];
				$this->clientname = (string)$result['clientname'];
				$this->expire = (int)$result['expire'];
				$currentTime = time();
				if($currentTime < $this->expire){
					$this->valid = true;
					/* Take a moment to update the user last_login time for the API. */
					$query2 = $this->dbh->prepare('UPDATE users SET last_login=:lastlogin WHERE id = :uid');
      $queryArray2 = array('uid' => $this->uid, 'lastlogin' => $currentTime);
      				$query2->execute($queryArray2); 
				}
				/* Token exists but is expired. Get rid of it. */
				else{
					$this->destroyToken($this->tokenNumber);
					$this->valid = false;
				}
				$tempUser = new User($this->uid);
				$this->username = $tempUser->getUsername();
				if($this->username == false){
					$this->valid = false;
				}
			}
			catch(PDOException $badquery){
				$this->valid = false;
				return false;
			}
		}
		else{
			$this->valid = false;
		}
		return $this->valid;
	}


	/* Destroy a specific token, usually because of expiration. */
	function destroyToken($token){
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('DELETE FROM tokens WHERE token=:token');
				$queryArray = array('token' => $token);
				return $query->execute($queryArray);
			}
			catch(PDOException $badquery){
				return false;
			}
	}


	/* Destroy tokens that are expire before now. */
	function destroyExpiredTokens($time){
		if(is_numeric($time)){
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('DELETE FROM tokens WHERE expire<=:time');
				$queryArray = array('time' => $time);
				return $query->execute($queryArray);
			}
			catch(PDOException $badquery){
				return false;
			}
		}
		else{
			return false;
		}
	}


	/* Utility function. Weed out duplicate user tokens with the same client name. */
	function destroyUserClientTokens($uid, $clientname){
		if(is_numeric($uid)){
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('DELETE FROM tokens WHERE uid=:uid AND clientname=:clientname');
				$queryArray = array('uid' => $uid, 'clientname' => $clientname);
				return $query->execute($queryArray);
			}
			catch(PDOException $badquery){
				return false;
			}
		}
		else{
			return false;
		}
	}


	/* Destroy all tokens for the given user. */
	function destroyUserTokens($uid){
		if(is_numeric($uid)){
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('DELETE FROM tokens WHERE uid= uid');
				$queryArray = array('uid' => $uid);
				return $query->execute($queryArray);
			}
			catch(PDOException $badquery){
				return false;
			}
		}
		else{
			return false;
		}
	}


	/* Test the validity of a token's format.
	In particular, this guards against SQL injection. */
	function validTokenFormat($token){
		$isValid = false;
		if(ctype_alnum($token)){
			$isValid = true;
		}
		else{
			$isValid = false;
		}
		return $isValid;
	}


	/* End. */
}

?>