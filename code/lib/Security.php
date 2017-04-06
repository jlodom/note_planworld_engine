<?php

/* Library for dealing with Planworld engine security.
	 Currently does a simple test to make sure that brute force attacks are not going on with tokens. */

/* includes */
$_base = dirname(__FILE__) . '/../'; /* This file lives in the lib folder, so root is one level above. */
require_once($_base . 'config.php');
require_once($_base . 'lib/User.php');

/* Class to handle remote authentication. */
class Security {

	var $timeout = 0;
	var $maxBadLogins = 1;
	var $dbh;


	/* Constructor. Sets up database connection, no more. */
	function Security() {
		$this->dbh = Planworld::_connect();
		if(defined("IPTIMEOUT")){
			$this->timeout = IPTIMEOUT;
		}
		else{
			$this->timeout = 1200;
		}
		if(defined("MAXBADLOGINS")){
			$this->maxBadLogins = MAXBADLOGINS;
		}
		else{
			$this->maxBadLogins = 1;
		}

	}


	function ipCheck($ip){
		$boolIpCheckPassed = false;
		if($this->currentIpFailures($ip) < $this->maxBadLogins){
			$boolIpCheckPassed = true;
		}
		else{
			$boolIpCheckPassed = false;
		}
		return $boolIpCheckPassed;
	}

	function resetIpTimeout($ip){
		$boolSuccess=false;
		if (filter_var($ip, FILTER_VALIDATE_IP)){
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('UPDATE security SET errorcount=0, lasterror=0 where ip=:ip');
				$queryArray = array('ip' => $ip);
				$result=$query->execute($queryArray);
				$boolSuccess = true;
			}
			catch(PDOException $badquery){
				$boolSuccess = false;
			}
		}
		else{
			$boolSuccess = false;
		}
		return $boolSuccess;

	}


	function addIpFailure($ip){
		$boolSuccess=false;
		if (filter_var($ip, FILTER_VALIDATE_IP)){
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$queryCount = $this->dbh->prepare('SELECT COUNT("ip") as count FROM security WHERE ip=:ip');
				$queryCountArray = array('ip' => $ip);
				$queryCount->execute($queryCountArray);
				$resultCount = $queryCount->fetch();
				$intCount = (int)$resultCount['count'];
				if($intCount > 0){
					$intCurrentFailures = $this->currentIpFailures($ip);
					$queryUpdate = $this->dbh->prepare('UPDATE security SET errorcount=:errorcount, lasterror=:timestamp WHERE ip=:ip');
					$queryUpdateArray = array('ip' => $ip, 'errorcount' => ($intCurrentFailures + 1), 'timestamp' => time()); /* $intCurrentFailures++ doesn't work */
					$queryUpdate->execute($queryUpdateArray);
				}
				else{
					$queryInsert = $this->dbh->prepare('INSERT INTO security (ip, errorcount, lasterror) VALUES (:ip,"1",:timestamp)');
					$queryInsertArray = array('ip' => $ip, 'timestamp' => time());
					$queryInsert->execute($queryInsertArray);
				}
				$boolSuccess = true;
			}
			catch(PDOException $badquery){
				$boolSuccess = false;
			}
		}
		else{
			$boolSuccess = false;
		}
		return $boolSuccess;

	}


	function currentIpFailures($ip){
		$intCurrentFailures = 0;
		if (filter_var($ip, FILTER_VALIDATE_IP)){
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('SELECT errorcount, lasterror FROM security WHERE ip=:ip');
				$queryArray = array('ip' => $ip);
				$query->execute($queryArray);
				$result = $query->fetch();
				$timeLastError = (int)$result['lasterror'];
				if(time() - $timeLastError > $this->timeout){
					$intCurrentFailures = 0;
					$this->resetIpTimeout($ip);
				}
				else{
					$intCurrentFailures = (int)$result['errorcount'];
				}
			}
			catch(PDOException $badquery){
				pi(); /* NO OP */
			}
		}
		return $intCurrentFailures;

	}



	/* End. */
}

?>