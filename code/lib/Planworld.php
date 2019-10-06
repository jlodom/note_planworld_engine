<?php

/* Every Planworld library file calls Planworld.php,
	so anything we want to be used everywhere should be placed here.
	For example, setting the timezone, other universal includes, cross-file constants, etc.*/

// Temporary until coding complete.
error_reporting(E_ALL);

/* _Initial Includes_ */
$_base = dirname(__FILE__) . '/../';
require_once($_base . 'config.php');

/* Define timezone and other localization before anything else. */
date_default_timezone_set(PW_TIMEZONE);

/* _More Includes_ */
require_once($_base . 'backend/epi-utils.php'); /* XML RPC Utility Calls [xu_somecall]. Old code but still used by the world. */

/* _Constants_ */
if(!defined('PLANWORLD_OK')){
	define('PLANWORLD_OK', 0); /* PLANWORLD_OK Operation succeeded. */
}
if(!defined('PLANWORLD_ERROR')){
	define('PLANWORLD_ERROR', -1); /* PLANWORLD_ERROR Operation failed. */
}

class Planworld {

	public static function _connect () {
		static $dbh;
		if (!isset($dbh)){
			try{
				$dbh = new PDO(PW_DB_TYPE . ':host=' . PW_DB_HOST . ';dbname=' . PW_DB_NAME,  PW_DB_USER, PW_DB_PASS);
				$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			}
			catch(PDOException $nodbh){
				return false; /* Previously this returned PLANWORLD_ERROR (i.e. -1) but if we set it to false it makes it easier to throw exceptions up the call stack. */
			}
		}
		return $dbh;
	}

	/*
   int Planworld::addUser ($uid)
   adds user with name $uid; returns user id
   In a more sophisticated world we would return different errors based on issue.
  */
	public static function addUser ($uid) {
		if (empty($uid)) {
			return false;
		}
		/* This statement is an excellent candidate for revision in a future release. JLO2 20161212 */
		if (strstr($uid, '@')) {
			$remote = 'Y';
		}
		else {
			$remote = 'N';
		}
		try{
			$intId = Planworld::incrementUserSeq();
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $dbh->prepare('INSERT INTO users (id, username, remote, first_login) VALUES (:id, :username, :remote, :first_login)');
			$queryArray = array('id' => $intId, 'username' => addslashes($uid), 'remote' => $remote, 'first_login'=> time());
			$result = $query->execute($queryArray);
			if(count($result) < 1){
				return PLANWORLD_ERROR;
			}
			else{
				return $intId;
			}
		}
		catch(PDOException $badquery){
			echo 'OOO';
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}


	/* int incrementUserSeq
		Exists because we are not using auto-incrementation anymore.
	*/
		public static function incrementUserSeq() {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$queryRead = $dbh->prepare('SELECT id FROM userid_seq');
			$queryRead->execute();
			$resultRead = $queryRead->fetch();
			$intNewId = (int)$resultRead['id'] + 1;
			$query = $dbh->prepare('UPDATE userid_seq SET id=:id');
			$queryArray = array('id' => $intNewId);
			$query->execute($queryArray);
			return $intNewId;
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}


   /* There is a debate on how one ought to iterate through strings in php ... https://stackoverflow.com/questions/4601032/php-iterate-on-string-characters */
   public static function isValidWatchlistGroupName($stringName){
     $boolReturn = true;
     $stringNameArray = str_split($stringName);
     foreach($stringNameArray as $char){
       if((!(ctype_alnum($char))) && ($char != "_") && ($char != "-") && ($char != " ")){
         $boolReturn = false;
       }
     }
     return $boolReturn;
   }


   public static function isValidUserName($stringName){
     $boolReturn = true;
     $stringNameArray = str_split($stringName);
     foreach($stringNameArray as $char){
       if((!(ctype_alnum($char))) && ($char != "@")){
         $boolReturn = false;
       }
     }
     return $boolReturn;
   }


	/*
   int Planworld::nameToID ($uid)
   converts textual $uid to numeric representation
   If the user is not found but seems remote, add user.
  */
	function nameToID ($uid) {
		static $table; /* persistent lookup table */
		if (is_string($uid)) {
			if (isset($table[$uid])) {
				return $table[$uid];
			}
			else {
				try{
					$dbh = Planworld::_connect();
					if(!$dbh){
						throw new PDOException('Database connection not initialized.');
					}
					$query = $dbh->prepare('SELECT id FROM users WHERE username= :username');
					$queryArray = array('username' => $uid);
					$query->execute($queryArray);
					$result = $query->fetch();
					/* Handle remote user who has not been added yet. */
					if ((!$result) && strstr($uid, '@')) {
						return Planworld::addUser($uid);
					}
					else if (!$result) {
							return PLANWORLD_ERROR;
						}
					else {
						$table[$uid] = (int) $result['id'];
						return (int) $table[$uid];
					}
				}
				catch(PDOException $badquery){
					return PLANWORLD_ERROR;
				}
			}
		}
		else {
			return PLANWORLD_ERROR;
		}
	}


	/*
   string Planworld::idToName ($uid)
   converts numeric $uid to string representation
   Note that the argument must not look like a string -- it must be sent as an int.
  */
	function idToName ($uid) {
		static $table; /* persistent lookup table */
		if (is_int($uid)) {
			if (isset($table[$uid])) {
				return $table[$uid];
			}
			else {
				try{
					$dbh = Planworld::_connect();
					if(!$dbh){
						throw new PDOException('Database connection not initialized.');
					}
					$query = $dbh->prepare('SELECT username FROM users WHERE id= :uid');
					$queryArray = array('uid' => $uid);
					$query->execute($queryArray);
					$result = $query->fetch();
					if (!$result){
						return PLANWORLD_ERROR;
					}
					else {
						$table[$uid] = $result['username'];
						return $table[$uid];
					}
				}
				catch(PDOException $badquery){
					return PLANWORLD_ERROR;
				}
			}
		}
		else {
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}


	/*
   bool Planworld::isUser ($uid)
   returns whether $uid is an actual user
   If uid is numeric, make sure it is sent as an int and not a string.
   This function is static because of how it is called from User.php and likely others.
  */
public static function isUser ($uid, $force=false) {
		static $table;
		$query = ''; /* Placed here to define. Handled appropriately if continues null. */
		if (isset($table[$uid]) && !$force) {
			return $table[$uid];
		}
		else if(empty($uid)){
			return PLANWORLD_ERROR;
		}
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			if (is_int($uid)) {
				$query = $dbh->prepare('SELECT COUNT(id) AS count FROM users WHERE id= :uid');
			}
			else if (is_string($uid)) {
					$uid = addslashes($uid);
					$query = $dbh->prepare('SELECT COUNT(id) AS count FROM users WHERE username= :uid');
				}
			$queryArray = array('uid' => $uid);
			$query->execute($queryArray);
			$result = $query->fetch();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else if($result['count'] < 1){
					$table[$uid] = false;
					return false;
				}
			else if($result['count'] == 1){
					$table[$uid] = true;
					return true;
				}
			else{
				return PLANWORLD_ERROR;
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}


	/*
   bool Planworld::isRemoteUser ($uid)
   returns whether $uid is a remote user (assuming that $uid is a valid user)
  */
	function isRemoteUser ($uid) {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			if (is_int($uid)) {
				$query = $dbh->prepare('SELECT remote FROM users WHERE id= :uid');
			}
			else if (is_string($uid)) {
					$uid = addslashes($uid);
					$query = $dbh->prepare('SELECT remote FROM users WHERE username= :uid');
				}
			$queryArray = array('uid' => $uid);
			$query->execute($queryArray);
			$result = $query->fetch();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else{
				return ($result['remote'] == 'Y') ? true : false;
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}



	/*
   bool Planworld::isWorldViewable ($uid)
   returns whether $uid has a world-viewable plan
  */
	function isWorldViewable ($uid) {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			if (is_int($uid)) {
				$query = $dbh->prepare('SELECT world FROM users WHERE id= :uid');
			}
			else if (is_string($uid)) {
					$uid = addslashes($uid);
					$query = $dbh->prepare('SELECT world FROM users WHERE username= :uid');
				}
			$queryArray = array('uid' => $uid);
			$query->execute($queryArray);
			$result = $query->fetch();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else{
				return ($result['world'] == 'Y') ? true : false;
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}


	/*
   int Planworld::getRandomUser ()
   pick a user (with a plan) at random
   Changed in version 3 to grab all UIDs with local plans and get the random value from PHP.
   This was done because the long SQL query is faster than expected and no two databases use
   the same syntax for retrieving random values. Better to use a cross-db compatible syntax
   and then have PHP do the randomization.
  */
	function getRandomUser() {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}   $query = $dbh->prepare('SELECT DISTINCT uid FROM plans');
			$query->execute();
			$result = $query->fetchAll();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else{
				$randomKey = array_rand($result, 1);
				return (int) $result[$randomKey]['uid'];
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
	}




	/**
	 * array Planworld::getNodeInfo ($host)
	 * Returns node information for $host.
	 */
	function getNodeInfo ($host) {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $dbh->prepare('SELECT name, hostname, path, port, version FROM nodes WHERE name= :host');
			$queryArray = array('host' => $host);
			$query->execute($queryArray);
			$result = $query->fetch();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else {
				$return = array('Name' => $result['name'],
					'Hostname' => $result['hostname'],
					'Path' => $result['path'],
					'Port' => (int) $result['port'],
					'Version' => (int) $result['version']);
				return $return;
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}



	/**
	 * array Planworld::getNodes ()
	 * Return the node list.
	 */
	function getNodes () {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $dbh->prepare('SELECT name, hostname, path, port, version FROM nodes ORDER BY name');
			$query->execute();
			$result = $query->fetchAll();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else {
				$return = array();
				foreach($result as $row){
					$return[] = array('Name' => $row['name'],
						'Hostname' => $row['hostname'],
						'Path' => $row['path'],
						'Port' => (int) $row['port'],
						'Version' => (int) $row['version']);
				}
				return $return;
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}



	/**
	 * array Planworld::getTimezones ()
	 * Return the list of available timezones.
	 */
	function getTimezones () {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $dbh->prepare('SELECT name FROM timezones ORDER BY name');
			$query->execute();
			$result = $query->fetchAll();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else {
				$return = array();
				foreach($result as $row){
					$return[] = $row['name'];
				}
				return $return;
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}





	function getAllUsers () {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			/* 20180410 JLO2 - Until login code is implemented in the scaffolding system, a simpler version of this query should be used.
			$query = $dbh->prepare('SELECT username FROM users WHERE last_login!=0 AND remote="N" ORDER BY username'); */
			$query = $dbh->prepare('SELECT username FROM users WHERE remote="N" ORDER BY username');
			$query->execute();
			$result = $query->fetchAll();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else {
				$return = array();
				/* Rudimentary data sanitization, but we need to validate the DB too as an administrative function. */
				foreach($result as $row){
					if(ctype_alnum(trim($row['username']))){
						$return[] = trim($row['username']);
					}
				}
				return $return;
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}




	/**
	 * array Planworld::getAllUsersWithPlans ($start)
	 * Returns a list of usernames (optionally filtered) for users who have plans.
	 * The original version of this function built the query with if/then statements.
	 * The PDO version is a bit more convoluted due to how prepared statements properly work.
	 * Note: (JLO2) I don't underand what the '#' entry is supposed to do. At first I thought it
	 * might look for all plans ending with a number, but looking at the original regex it appears
	 * to ask specifically for plans starting with a number, of which there is only one at this time
	 * and that one appears to be a mistake rather than some sort of clever system function.
	 * Note also that the LIKE value is weakly escaped (try doing something like $start = '%an' )
	 * but I will allow it because it is a filter rather than an exploit and might prove useful.
	 */

	function getAllUsersWithPlans ($start = null) {
		$query = '';
		$result = '';
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			if(!isset($start)){
				$query = $dbh->prepare('SELECT username FROM users, plans WHERE users.id=plans.uid  ORDER BY username');
				$query->execute();
			}
			else if ($start == '#'){
					$query = $dbh->prepare('SELECT username FROM users, plans WHERE users.id=plans.uid  AND users.username REGEXP "^[0-9].*"  ORDER BY username');
					$query->execute();
				}
			else{
				$query = $dbh->prepare('SELECT username FROM users, plans WHERE users.id=plans.uid  AND users.username LIKE :start  ORDER BY username');
				$queryArray = array('start' => $start . '%');
				$query->execute($queryArray);
			}
			$result = $query->fetchAll();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else {
				$return = array();
				foreach($result as $row){
					$return[] = $row['username'];
				}
				return $return;
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}





	/**
	 * Fetch the $num most recent updates.
	 * JLO2 20150424
	 * The PDO implementation differs from the original implementation in two key respects.
	 * 1. PDO does not support a LIMIT statement, and it appears that LIMIT is not cross-platform anyway.
	 *    The work-around for this is to fetch all rows and then limit the array that is created.
	 * 2. The return is no longer a raw /DBSQL row but is instead a matrix (array of arrays).
	 *    This means that functions which call this method will need to be adjusted.
	 */
	function getLastUpdates ($num=false) {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $dbh->prepare('SELECT username, last_update FROM users WHERE remote="N" ORDER BY last_update DESC');
			$query->execute();
			$result = $query->fetchAll();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else {
				$return = array();
				if((is_int($num)) && ($num > 0) && ($num < count($result))){
					$result = array_slice($result, 0, $num);
				}
				foreach($result as $row){
					$return[] = array('Username' => $row['username'], 'Last_update' => $row['last_update']);
				}
				return $return;
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}





	/**
	 * Fetch the $num newest users.
	 * JLO2 20150424
	 * The PDO implementation differs from the original implementation in two key respects.
	 * 1. PDO does not support a LIMIT statement with BindParam (I dislike BindParam), and it appears that LIMIT is not cross-platform anyway.
	 *    The work-around for this is to fetch all rows and then limit the array that is created.
	 *  I can hear some of you screaming now, but if DB indexes are correct the query should be fast
	 *  and the work done on the DB side is roughly the same in any case.
	 * 2. The return is no longer a raw /DBSQL row but is instead a matrix (array of arrays).
	 *    This means that functions which call this method will need to be adjusted.
	 */
	function getNewUsers ($num=false) {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $dbh->prepare('SELECT username, first_login, last_update FROM users WHERE remote="N" AND last_login > 0 ORDER BY first_login DESC');
			$query->execute();
			$result = $query->fetchAll();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else {
				$return = array();
				if((is_int($num)) && ($num > 0) && ($num < count($result))){
					$result = array_slice($result, 0, $num);
				}
				foreach($result as $row){
					$return[] = array('Username' => $row['username'], 'First_login' => $row['first_login'], 'Last_update' => $row['last_update']);
				}
				return $return;
			}
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}







	/**
	 * int | array(int) Planworld::getLastLogin ($uid, $host)
	 * Gets the last login time for $uid (from $host, if applicable)
	 */

	function getLastLogin ($uid, $host=null) {
		$dbh = Planworld::_connect();
		$boolReturnArray = true;
		/* The operation is presumed to be happening on the local node. */
		if((!isset($host)) && ($dbh)){
			if(!is_array($uid)){
				$uid = array($uid);
				$boolReturnArray = false;
			}
			$return = array();
			foreach ($uid as $single){
				if(is_int($single)){
					$query = $dbh->prepare('SELECT id, last_login FROM users WHERE id= :single');
					$queryArray = array('single' => $single);
					$query->execute($queryArray);
					$result = $query->fetch();
					$return[$result['id']] = (int) $result['last_login'];
				}
				else{
					$query = $dbh->prepare('SELECT username as id, last_login FROM users WHERE username= :single');
					$queryArray = array('single' => $single);
					$query->execute($queryArray);
					$result = $query->fetch();
					$return[$result['id']] = (int) $result['last_login'];
				}
			}
			if(!$boolReturnArray){
				return $return[$uid[0]];
			}
			else{
				return $return;
			}
		}
		/* $host is set so this is a remote operation. Find the node to send the operation to. */
		else if ($node = Planworld::getNodeInfo($host)) {
				/* remote fetch-by-username (forced) */
				if ($node['Version'] < 2) {
					$result = Planworld::_call($node, 'users.getLastLogin', array($uid));
				}
				else {
					$result = Planworld::_call($node, 'planworld.user.getLastLogin', array($uid));
				}
				/* Question: Should we have previously declared result since otherwise it is declared during if/then?
			 Obviously the code has been working for years as-is but I (JLO2) still don't like having variables
			 out of scope, even if only incidentally.
		*/
				/* Freshening the remote cache and returning: Many values were received. */
				if (is_array($result)) {
					/* freshen the cache */
					foreach ($result as $u=>$t) {
						try{
							if(!$dbh){
								throw new PDOException('Database connection not initialized.');
							}
							$query = $dbh->prepare('UPDATE users SET last_login= :last_login WHERE username= :username');
							$fullu = $u . '@' . $host;
							$queryArray = array('last_login' => $t, 'username' => $fullu);
							$query->execute($queryArray);
						}
						catch(PDOException $badquery){
							pi(); /* Using in place of No-Op as we do not need to do anything if this fails. */
						}
					}
				}
				/* Freshening the remote cache and returning: A single value was received. */
				else {
					try{
						if(!$dbh){
							throw new PDOException('Database connection not initialized.');
						}
						$query = $dbh->prepare('UPDATE users SET last_login= :last_login WHERE username= :username');
						$fullu = $uid . '@' . $host;
						$queryArray = array('last_login' => $result, 'username' => $fullu);
						$query->execute($queryArray);
					}
					catch(PDOException $badquery){
						pi(); /* Using in place of No-Op as we do not need to do anything if this fails. */
					}
				}
				return $result;
			}
		/* $host is set but the node is unknown to us. Return false because of unknown node. */
		else{
			return false;
		}
	}


	/**
	 * int | array(int) Planworld::getLastUpdate ($uid, $host)
	 * Gets the last update time for $uid (from $host, if applicable)
	 */
	function getLastUpdate ($uid, $host=null) {
		$dbh = Planworld::_connect();
		$boolReturnArray = true;
		/* The operation is presumed to be happening on the local node. */
		if((!isset($host)) && ($dbh)){
			if(!is_array($uid)){
				$uid = array($uid);
				$boolReturnArray = false;
			}
			$return = array();
			foreach ($uid as $single){
				if(is_int($single)){
					$query = $dbh->prepare('SELECT id, last_update FROM users WHERE id= :single');
					$queryArray = array('single' => $single);
					$query->execute($queryArray);
					$result = $query->fetch();
					$return[$result['id']] = (int) $result['last_update'];
				}
				else{
					$query = $dbh->prepare('SELECT username as id, last_update FROM users WHERE username= :single');
					$queryArray = array('single' => $single);
					$query->execute($queryArray);
					$result = $query->fetch();
					$return[$result['id']] = (int) $result['last_update'];
				}
			}
			if(!$boolReturnArray){
				return $return[$uid[0]];
			}
			else{
				return $return;
			}
		}
		/* $host is set so this is a remote operation. Find the node to send the operation to. */
		else if ($node = Planworld::getNodeInfo($host)) {
				/* remote fetch-by-username (forced) */
				if ($node['Version'] < 2) {
					$result = Planworld::_call($node, 'users.getLastUpdate', array($uid));
				}
				else {
					$result = Planworld::_call($node, 'planworld.user.getLastUpdate', array($uid));
				}
				/* Question: Should we have previously declared result since otherwise it is declared during if/then?
			 Obviously the code has been working for years as-is but I (JLO2) still don't like having variables
			 out of scope, even if only incidentally.
		*/
				/* Freshening the remote cache and returning: Many values were received. */
				if (is_array($result)) {
					/* freshen the cache */
					foreach ($result as $u=>$t) {
						try{
							if(!$dbh){
								throw new PDOException('Database connection not initialized.');
							}
							$query = $dbh->prepare('UPDATE users SET last_update= :last_login WHERE username= :username');
							$fullu = $u . '@' . $host;
							$queryArray = array('last_update' => $t, 'username' => $fullu);
							$query->execute($queryArray);
						}
						catch(PDOException $badquery){
							pi(); /* Using in place of No-Op as we do not need to do anything if this fails. */
						}
					}
				}
				/* Freshening the remote cache and returning: A single value was received. */
				else {
					try{
						if(!$dbh){
							throw new PDOException('Database connection not initialized.');
						}
						$query = $dbh->prepare('UPDATE users SET last_update= :last_login WHERE username= :username');
						$fullu = $uid . '@' . $host;
						$queryArray = array('last_update' => $result, 'username' => $fullu);
						$query->execute($queryArray);
					}
					catch(PDOException $badquery){
						pi(); /* Using in place of No-Op as we do not need to do anything if this fails. */
					}
				}
				return $result;
			}
		/* $host is set but the node is unknown to us. Return false because of unknown node. */
		else{
			return false;
		}
	}


	/**
	 * Fetches a preference for this user.
	 */
	function getPreference ($uid, $name) {
		try{
			$dbh = Planworld::_connect();
			if(!$dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $dbh->prepare('SELECT value FROM preferences WHERE uid= :uid AND name= :name');
			$queryArray = array('uid' => $uid, 'name' => $name);
			$query->execute($queryArray);
			$result = $query->fetch();
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
		return PLANWORLD_ERROR;
	}


	public static function getRFC3339DateFromTimeStamp($timestampUnix){
		$returnDate = new DateTime();
		$returnDate->setTimestamp($timestampUnix);
		return $returnDate->format(DateTime::RFC3339);
	}

	function basicTextSanitization($textArbitrary){
		$textSanitized = htmlentities(strip_tags(addslashes($textArbitrary)));
		return $textSanitized;
	}

	/* THINK LONG AND HARD ABOUT WHAT YOU ARE DOING HERE VS. CONFIG.PHP */
	public static function getArrayOfAllowedPreferences(){
		return array('admin','journal','journal_divider','journal_entries','journal_order','send_forward','snitchtracker','timezone');
	}

}
?>