<?php

/* _Includes_ */
$_base = dirname(__FILE__) . '/../';
require_once('Planworld.php'); //stand-in for Planworld
//require_once('Planwatch.php'); Re-Enable when we have Planwatch in implementation


/**
 * User class for planworld.
 * @author Seth Fitzsimmons
 */
class User {

	/** general user information */
	var $archive;
	var $lastLogin;
	var $lastUpdate;
	var $last_ip;
	var $plan;
	var $planwatch;
	var $snitchDisplayNum;
	var $snitchEnabled;
	var $theme;
	var $timezone = PW_TIMEZONE;
	var $type;
	var $userID;
	var $userInfo;
	var $username;
	var $views;
	var $watchOrder;
	var $prefs;
	var $editor;

	/** flags (booleans) */
	var $admin;
	var $remoteUser;
	var $shared = false;
	var $snitch;
	var $valid;
	var $world;

	var $changed = false;

	var $dbh;

	/**
	 * Factory
	 * @param uid User to initialize
	 * @public
	 * @static
	 * @returns User
	 */
	static function &factory ($uid) {
		if (Planworld::isUser($uid) && !Planworld::isRemoteUser($uid)) {
			$temp = new User($uid);
			return $temp;
		}
		else if (!Planworld::isUser($uid) && !strstr($uid, '@')) {
				$temp = new User($uid);
				return $temp;
			}
		else {
			list(,$host) = split('@', $uid);
			$nodeinfo = Planworld::getNodeInfo($host);
			$temp = new RemoteUser($uid, $nodeinfo);
			return $temp;
		}
	}


	function User ($uid) {
		/* establish a database connection */
		$this->dbh = Planworld::_connect();
		$this->type = 'local';
		if (is_string($uid)) {
			$this->username = $uid;
		}
		else if (is_int($uid)) {
				$this->userID = (int)$uid;
			}
		/* check if this user exists */
		if (isset($this->userID) || $this->isUser()) {
			$this->load();
		}
	}



	/* 	The original version of this function returns false rather than Planworld error, so we continue that habit.
			Obviously the function loads user information.
	*/
	function load(){
		$continue = false;
		$query = ''; /* Placed here to define. Handled appropriately if continues null. */
		$queryArray = array();
		try{
			if(!$this->dbh){
				throw new PDOException('Database connection not initialized.');
			}
			if (isset($this->username)){
				$query = $this->dbh->prepare('SELECT * FROM users WHERE username= :username');
				$queryArray = array('username' => addslashes($this->username));
				$continue = true;
			}
			else if (isset($this->userID)){
					$query = $this->dbh->prepare('SELECT * FROM users WHERE id= :uid');
					$queryArray = array('uid' => $this->userID);
					$continue = true;
				}
			else{
				$continue = false;
			}
		}
		catch(PDOException $badquery){
			$continue = false;
		}
		if($continue){
			try{
				$query->execute($queryArray);
				$result = $query->fetch();
				if (!$result){
					return false;
				}
				else{
					$this->userID = (int) $result['id'];
					$this->username = $result['username'];
					$this->remoteUser = ($result['remote'] == 'Y') ? true : false;
					$this->world = ($result['world'] == 'Y') ? true : false;
					$this->snitch = ($result['snitch'] == 'Y') ? true : false;
					$this->archive = $result['archive'];
					$this->snitchDisplayNum = $result['snitch_views'];
					$this->views = $result['views'];
					$this->watchOrder = $result['watch_order'];
					$this->theme = $result['theme_id'];
					$this->snitchEnabled = $result['snitch_activated'];
					$this->lastLogin = $result['last_login'];
					$this->lastUpdate = $result['last_update'];
					if ($tz = $this->getPreference('timezone')) {
						$this->timezone = $tz;
					}
					$this->changed = false;
					return true;
				}
			}
			catch(PDOException $badquery){
				return false;
			}
		}
		else{
			return false;
		}
		return false;

	}





	/**
	 * Create this user
	 */
	function create () {
		/* create the user */
		if (isset($this->username)){
				$this->userID = Planworld::addUser($this->username);
		}

		/* set the valid flag */
		$this->valid = true;
	}


	/**
	 * Fetches a preference for this user.
	 */
	function getPreference ($name) {
		if (isset($this->prefs[$name])){
			return $this->prefs[$name];
		}
		else if((!empty(trim($name))) && (in_array($name, Planworld::getArrayOfAllowedPreferences()))){
				try{
					if(!$this->dbh){
						throw new PDOException('Database connection not initialized.');
					}
					$query = $this->dbh->prepare('SELECT value FROM preferences WHERE uid= :uid AND name= :name');
					$queryArray = array('uid' => $this->userID, 'name' => $name);
					$query->execute($queryArray);
					$result = $query->fetch();
					if(isset($result['value'])){
						if((strcasecmp($result['value'], 'true')) == 0){
							$this->prefs[$name] = true;
							return true;
						}
						else if((strcasecmp($result['value'], 'false')) == 0){
								$this->prefs[$name] = false;
								return false;
							}
						else{
							$this->prefs[$name] = $result['value'];
							return $result['value'];
						}
					}
					else{
						$this->prefs[$name] = false;
						return false;
					}
				}
				catch(PDOException $badquery){
					$this->prefs[$name] = false;
					return false;
				}
			}
		else{
			return false;
		}
		return false;
	}



    /**
     * Sets a preference for this user.
     * @public
     * @returns string
     */
    function setPreference ($name, $val) {
	    		$success = false;
		if(!(in_array($name, Planworld::getArrayOfAllowedPreferences()))){
			$success = false;
		}
	  else{
		  $this->clearPreference ($name); /* Don't check for true return because the preference may start out blank. */
			/* A huge flaw in this method is that preference calues are not checked for validity.
				But then, right now the journal divider preference is sort of impossible to check and awful.
				We might consider breaking it out into a separate table along with shared. */


	  }


      $query = "DELETE FROM preferences WHERE uid=" . $this->userID . " AND name='" . $name . "'";
      $this->dbh->query($query);

      /* don't add false preferences (lack is assumed to be false) */
      if (!$val || strtolower($val) != 'false' || $val != '') {
	$query = "INSERT INTO preferences (uid, name, value) VALUES (" . $this->userID . ", '{$name}', '{$val}')";
	$this->dbh->query($query);
      }

      $this->prefs[$name] = $val;
    }



	/**
	 * Clears a preference for this user.
	 */
	function clearPreference ($name) {
		$success = false;
		if(!(in_array($name, Planworld::getArrayOfAllowedPreferences()))){
			$success = false;
		}
		else{
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('DELETE FROM preferences WHERE uid= :uid AND name LIKE(:prefname)');
				$queryArray = array('uid' => $this->userID, 'prefname' => $name);
				$success = $query->execute($queryArray);
				/* We do this after the query but within the block so that if it blows up the catch will still give us a false return.
				The point is that if we get a false, we should re-investigate because preference state is likely unpredictable. */
				unset($this->prefs[$name]);
			}
			catch(PDOException $badquery){
				$success = false;
			}
		}
		return $success;
	}




	/**
	 * Saves user information.
	 * @public
	 * @returns bool Whether any data was actually saved
	 */
	// STILL REQUIRES CLEANUP, DOCUMENTATION, ONE NOTED CHANGE, AND A CHECK OF ALL IF AND CONDITIONALS.
	function save () {
		$boolSaveResult = false;
		$errorCount = 0;
		if ($this->changed) {
			/* column <-> variable mapping */
			$info = array();
			$info['username'] = strval($this->username);
			$info['remote'] = (strval($this->remoteUser)) ? 'Y' : 'N';
			$info['world'] = (strval($this->world)) ? 'Y' : 'N';
			$info['snitch'] = (strval($this->snitch)) ? 'Y' : 'N';
			$info['snitch_views'] = $this->snitchDisplayNum;
			$info['archive'] = strval($this->archive);
			$info['watch_order'] = strval($this->watchOrder);
			$info['theme_id'] = $this->theme;
			$info['snitch_activated'] = $this->snitchEnabled;
			$info['last_login'] = $this->lastLogin;
			$info['last_update'] = $this->lastUpdate;
			$info['last_ip'] = strval($this->last_ip);
			$whereKey = '';
			$whereValue = '';
			$boolContinue = false;
			if(isset($this->username)){
				$whereKey = 'username';
				$whereValue = $this->username;
				$boolContinue = true;
			}
			else if(isset($this->userID)){
					$whereKey = 'id';
					$whereValue = $this->userID;
					$boolContinue = true;
				}
			else{
				$boolContinue = false;
			}
			if($boolContinue){
				/* Run an update for each attribue on this user, but only if the attribute value is not empty. */
				foreach($info as $key => $value){
					if(!empty($value)){
						try{
							if(!$this->dbh){
								throw new PDOException('Database connection not initialized.');
							}
							$query = $this->dbh->prepare('UPDATE users SET :key = :value WHERE :wherekey = :wherevalue');
							$queryArray = array('wherekey' => $whereKey, 'wherevalue' => $whereValue, 'key' => $key, 'value' => $value);
							$query->execute($queryArray);
						}
						catch(PDOException $badquery){
							$errorCount++;
						}
					}
					else{
						$errorCount++;
					}
				}
				/* This can be adjusted depending upon one's tolerance for errors. */
				if($errorCount == 0){
					$boolSaveResult = true;
					$this->changed = false;
				}
				else if(($errorCount < 12) && ($errorCount > 0)){
						$boolSaveResult = true;
						$this->changed = true;
					}
				else{
					$boolSaveResult = false;
					$this->changed = true;
				}
			}
			else{
				$boolSaveResult = false;
			}
		}
		else{
			/* No change, but we were successful anyway. */
			$boolSaveResult = true;
		}
		/* save any changed planwatch data if need be */
		if (isset($this->planwatch)) {
			$this->planwatch->save();
		}
		return $boolSaveResult;
	}


	/**
	 * Loads a Planwatch object containing this users planwatch.
	 * @public
	 */
	function loadPlanwatch () {
		if (!isset($this->planwatch)) {
			// FIX AND ADD WHEN PLANWATCH IMPLEMENTED
			//$this->planwatch = new Planwatch($this);
			return false;
		}
		else{
			return PLANWORLD_ERROR;
		}
	}


	/**
	 * Set snitch registration status.
	 * @param val True / false registration
	 * @public
	 * @returns void
	 */
	function setSnitch ($val) {
		$this->changed = true;
		if (!$val && $this->snitch) {
			$this->clearSnitch();
		}
		else if ($val && $val != $this->snitch) {
				$this->startSnitch();
			}
		else {
			$this->changed = false;
		}
		$this->snitch = $val;
	}


	/**
	 * Clear this user's snitch list.
	 * @private
	 * @returns boolean
	 */
	function clearSnitch () {
		try{
			if(!$this->dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$this->snitch = false;
			$this->snitchEnabled = 0;
			$this->changed = true;
			$query = $this->dbh->prepare('DELETE FROM snitch WHERE uid= :uid');
			$queryArray = array('uid' => $this->userID);
			return $query->execute($queryArray);
		}
		catch(PDOException $badquery){
			return false;
		}
	}


	/**
	 * Resets this user's snitch views.
	 * @public
	 * @returns boolean
	 */
	function resetSnitchViews () {
		try{
			if(!$this->dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $this->dbh->prepare('UPDATE snitch SET views=0 WHERE uid=:uid');
			$queryArray = array('uid' => $this->userID);
			return $query->execute($queryArray);
		}
		catch(PDOException $badquery){
			return false;
		}

	}

	/**
	 * Is this user snitch registered?
	 * @public
	 * @returns bool
	 */
	function getSnitch () {
		return $this->snitch;
	}


	/**
	 * Returns the number of snitch views that this user has had.
	 * @public
	 */
	function getNumSnitchViews () {

		try{
			if(!$this->dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $this->dbh->prepare('SELECT COUNT(*) as count FROM snitch WHERE uid=:uid');
			$queryArray = array('uid' => $this->userID);
			$query->execute($queryArray);
			$result = $query->fetch();
			return $result['count'];
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
	}




	/* The previous version of this function allowed ordering.
		We will assume in the future that the client will handle ordering. */
	function getSnitchViews () {
		$arrayDicedSnitchViews = array();
		try{
			if(!$this->dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $this->dbh->prepare('SELECT snitch.s_uid, snitch.last_view, snitch.views, u2.username as s_name, u2.last_update FROM users, snitch LEFT JOIN users as u2 ON snitch.s_uid=u2.id WHERE snitch.uid=:uid AND users.id=:uid AND snitch.last_view > users.snitch_activated ORDER BY u2.last_update');
			$queryArray = array('uid' => $this->userID);
			$query->execute($queryArray);
			$result = $query->fetchall();
			$this->loadPlanwatch();
			foreach ($result as $row){
				$next = array(
					'name' => $row['s_name'],
					'date' => Planworld::getRFC3339DateFromTimeStamp($row['last_view']),
					'views' => $row['views'],
					'lastupdate' => Planworld::getRFC3339DateFromTimeStamp($row['last_update']),
					//'inplanwatch' => (bool) $this->planwatch->inPlanwatch($row['s_name'])
					'inplanwatch' => false // DUMMY CODE FIX WHEN PLANWATCH IMPLEMENTED
				);
				$arrayDicedSnitchViews[] = $next;
			}
			return $arrayDicedSnitchViews;

		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR;
		}
		return PLANWORLD_ERROR;
	}


	/**
	 * Increment the number of plan views for this user.
	 * @public
	 * @returns int
	 */
	function addView () {
		$this->views++;
		try{
			if(!$this->dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $this->dbh->prepare('UPDATE users SET views= :views WHERE id= :uid');
			$queryArray = array('views' => $this->views,'uid' => $this->userID);
			$query->execute($queryArray);
		}
		catch(PDOException $badquery){
			return PLANWORLD_ERROR; /* We know if this returns a negative number that something went awry. */
		}
		return $this->views;
	}



	/**
	 * Add a view by $user to this user's snitch list.
	 * @param user Viewing user.
	 * @returns true (1) on success and -1 (Planworld error) on failure
	 */
	function addSnitchView (&$user) {

		/* First add an entry to the Snitch Tracker */
		$intCurrentViews = 0;
		$intSnitchRows = -1;
		$suidForAddSnitchView = $user->getUserID();
		if($suidForAddSnitchView){
			/* Check whether a snitch relationship already exists between these One users. This will determine the next query. */
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$queryOne = $this->dbh->prepare('SELECT views from snitch where uid=:uid AND s_uid=:s_uid');
				$queryOneArray = array('uid' => $this->userID, 's_uid' => $suidForAddSnitchView);
				$queryOne->execute($queryOneArray);
				$resultOne = $queryOne->fetchall();
				if(is_numeric(count($resultOne))){
					$intNewViewCount = 1;
					$intSnitchRows = count($resultOne);
					if($intSnitchRows == 1){
						$intNewViewCount = $resultOne[0]['views'] + 1;
						$queryTwo = $this->dbh->prepare('UPDATE snitch SET last_view=:last_view, views=:views WHERE uid=:uid AND s_uid=:s_uid');
						$queryTwoArray = array('uid' => $this->userID, 's_uid' => $suidForAddSnitchView, 'last_view' => time(), 'views' => $intNewViewCount);
						return $queryTwo->execute($queryTwoArray);
					}
					else if ($intSnitchRows == 0){
							$queryThree = $this->dbh->prepare('INSERT INTO snitch (uid, s_uid, last_view, views) VALUES (:uid,:s_uid,:last_view,:views)');
							$queryThreeArray = array('uid' => $this->userID, 's_uid' => $suidForAddSnitchView, 'last_view' => time(), 'views' => $intNewViewCount);
							return $queryThree->execute($queryThreeArray);
						}
					else{
						return PLANWORLD_ERROR;
					}
				}
				else{
					return PLANWORLD_ERROR;
				}
			}
			catch(PDOException $badquery){
				return PLANWORLD_ERROR;
			}
		}
		else {
			return PLANWORLD_ERROR;
		}
	}




	/**
	 * Starts this user's snitch list.
	 * @private
	 * @returns void
	 */
	function startSnitch () {
		$this->snitch = true;
		$this->snitchEnabled = time();
		$this->changed = true;
	}




	/**
	 * void setLastLogin ($ts)
	 * update's users last login time to $ts (timestamp)
	 */
	function setLastLogin ($ts) {
		$this->changed = true;
		$this->lastLogin = $ts;
	}

	/**
	 * void setLastUpdate ($ts)
	 * update's users last update time to $ts (timestamp)
	 */
	function setLastUpdate ($ts) {
		$this->changed = true;
		$this->lastUpdate = $ts;
	}

	/**
	 * void setLastIP ($ip)
	 * update user's last known ip address
	 */
	function setLastIP ($ip) {
		$this->changed = true;
		$this->last_ip = $ip;
	}

	/**
	 * Get archive settings.
	 * @public
	 * @returns string
	 */
	function getArchive () {
		return $this->archive;
	}

	/**
	 * Set this user's archival settings
	 * @param val (Y) Public / (P) private / (N) off archiving
	 * @public
	 * @returns bool
	 */
	function setArchive ($val) {
		$val = strtoupper($val);
		if ($val == 'Y' || $val == 'P' || $val == 'N') {
			$this->archive = $val;
			$this->changed = true;
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Return the last time this user logged in.
	 * @public
	 * @returns int
	 */
	function getLastLogin () {
		return $this->lastLogin;
	}

	/**
	 * Return the last time this user updated his/her plan.
	 * @public
	 * @returns int
	 */
	function getLastUpdate () {
		return $this->lastUpdate;
	}


	/* FLAG FUNCTIONS */

	/**
	 * Is this user an admin?
	 * @public
	 * @returns bool
	 */
	function isAdmin () {
		if (!isset($this->admin)) {
			$this->admin = $this->getPreference('admin');
		}
		return $this->admin;
	}


	/**
	 * Is this user's plan archived?
	 * @public
	 * @returns bool
	 */
	function isArchived () {
		return ($this->archive == 'Y' || $this->archive == 'P') ? true : false;
	}


	/**
	 * Is this user's plan archived publicly?
	 * @public
	 * @returns bool
	 */
	function isArchivedPublicly () {
		return ($this->archive == 'Y') ? true : false;
	}


	/**
	 * Has this user been changed?
	 * @public
	 * @returns bool
	 */
	function isChanged () {
		return $this->changed;
	}

	/**
	 * Is this user remote?
	 * @public
	 * @returns bool
	 */
	function isRemoteUser () {
		return $this->remoteUser;
	}

	/**
	 * Is this a shared plan?
	 * @public
	 * @returns bool
	 */
	function isShared () {
		return $this->getPreference('shared') && $this->shared;
	}

	/**
	 * Mark this as an actively shared plan (not the logged-in user).
	 */
	function setShared () {
		$this->shared = true;
	}

	/**
	 * Set the user who is editing this shared plan.
	 */
	function setEditingUser ($user) {
		$this->editor = $user;
	}

	/**
	 * Is this shared for $uid to edit?
	 */
	function isSharedFor (&$uid) {
		if (is_object($uid)) {
			$username = $uid->getUsername();
		} else if (is_string($uid)) {
				$username = $uid;
			} else {
			return false;
		}
		return $this->getPreference('shared') && $this->getPreference('shared_' . $username);
	}


	/**
	 * Return a list of the users for whom this plan is shared.
	 In previous versions this returned a list with newlines. Now it returns an array.
	 It is not called by any library functions -- previously it was called from interface.
	 */
	function getSharedUsers () {
		try{
			if(!$this->dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $this->dbh->prepare('SELECT name FROM preferences WHERE name LIKE "shared_%" AND uid= :uid');
			$queryArray = array('uid' => $this->userID);
			$query->execute($queryArray);
			$result = $query->fetchAll();
			if (!$result){
				return PLANWORLD_ERROR;
			}
			else{
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



	/**
	 * Return a list of users whose plans this user is allowed to edit.
	 */

	function getPermittedPlans () {
		try{
			if(!$this->dbh){
				throw new PDOException('Database connection not initialized.');
			}
			$query = $this->dbh->prepare("SELECT uid, username FROM preferences as p, users as u WHERE p.name= :sharedname AND u.id=p.uid");
			$queryArray = array('sharedname' => 'shared_' . $this->username);
			$query->execute($queryArray);
			$result = $query->fetchAll();
			if (!$result){
				return false;
			}
			else{
				$return = array();
				foreach($result as $row){
					$return[] = $row['username'];
				}
				return $return;
			}
		}
		catch(PDOException $badquery){
			return false;
		}
		return false;
	}






	/**
	 * Does this user exist?
	 * @public
	 * @returns bool
	 */
	function isUser () {
		if (!isset($this->valid)) {
			$planworldJustForIsUser = new Planworld();
			$this->valid = $planworldJustForIsUser->isUser($this->username, true);
		}
		return $this->valid;
	}


	/**
	 * Is this user's plan world-accessible?
	 * @public
	 * @returns bool
	 */
	function isWorld () {
		return $this->getWorld();
	}


	/**
	 * Is this user new? (NPD: for welcome pages)
	 * @public
	 * @returns bool
	 */
	function isNew() {
		return !$this->lastUpdate;
	}

	/**
	 * Get number of users to display on snitch list.
	 * @public
	 * @returns int
	 */
	function getSnitchDisplayNum () {
		return $this->snitchDisplayNum;
	}

	/**
	 * Set number of users to display on snitch list.
	 * @param num Number of users to display
	 * @public
	 * @returns void
	 */
	function setSnitchDisplayNum ($num) {
		$this->snitchDisplayNum = $num;
		$this->changed = true;
	}


	/**
	 * Get this user's preferred timezone.
	 * @public
	 * @returns string
	 */
	function getTimezone () {
		return $this->timezone;
	}

	/**
	 * Set the local timezone for this user.
	 * @param theme Timezone to use
	 * @public
	 * @returns void
	 */
	function setTimezone ($timezone) {
		$this->setPreference('timezone', $timezone);
		$this->timezone = $timezone;
	}

	/**
	 * Get this user's type
	 * @public
	 * @returns string
	 */
	function gettype() {
		return $this->type;
	}

	/**
	 * Returns this user's userid.
	 * @public
	 * @returns int
	 */
	function getUserID () {
		return $this->userID;
	}

	/**
	 * Returns this user's username.
	 * @public
	 * @returns string
	 */
	function getUsername () {
		return addslashes($this->username);
	}

	/**
	 * Returns this user's planwatch ordering.
	 * @public
	 * @returns string
	 */
	function getWatchOrder () {
		return $this->watchOrder;
	}

	/**
	 * Set this user's planwatch ordering.
	 * @param type Type of ordering
	 * @public
	 * @returns void
	 */
	function setWatchOrder ($type) {
		$this->watchOrder = $type;
		$this->changed = true;
	}

	/**
	 * Is this user's plan world-accessible?
	 * @public
	 * @returns bool
	 */
	function getWorld () {
		return $this->world;
	}

	/**
	 * Set this user's world-accessibility
	 * @param val True / false world-accessibility
	 * @public
	 * @returns void
	 */
	function setWorld ($val) {
		$this->world = $val;
		$this->changed = true;
	}


	/* The following functions were moved here from the Online library. 20171121 JLO2 */

	function isUserOnline(){
					try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('SELECT count(last_access) as onlinecount FROM online where uid=:uid');
				$queryArray = array('uid' => $this->userID);
				$result = $query->execute($queryArray);
								if (!$result){
					return false;
				}
				else{
					$intOnlineCount = (int) $result['onlinecount'];
					if($intOnlineCount > 0){
						return true;
					}
					else{
						return false;
					}
				}
				return false;
			}
			catch(PDOException $badquery){
				return false;
			}
	}


	/* Update the user's online status. If the user is not online, add them. If they are online, update them.
		Set last login based on the user's current token. This last would need to change if we stop using tokens. */
	function userOnline($stringWhatUserIsDoing = ''){
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}

				if($this->isUserOnline()){

				}
				else{

				}
			}
			catch(PDOException $badquery){
				return false;
			}
	}


	/* Sets the user to offline (i.e. removes them from the Online table. */
	function userOffline(){
			try{
				if(!$this->dbh){
					throw new PDOException('Database connection not initialized.');
				}
				$query = $this->dbh->prepare('DELETE FROM online WHERE uid=:uid');
				$queryArray = array('uid' => $this->userID);
				return $query->execute($queryArray);
			}
			catch(PDOException $badquery){
				return false;
			}
	}


	/* END CLASS */
}
?>