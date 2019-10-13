<?php
/* $Id: Planwatch.php,v 1.9.2.7 2003/11/02 16:12:35 seth Exp $ */

/* Basic Setup */
$_base = dirname(__FILE__) . '/../';
require_once($_base . 'lib/Planworld.php');
require_once($_base . 'lib/Snoop.php');
date_default_timezone_set(PW_TIMEZONE);

/**
 * Planwatch
 */
class Planwatch {
  var $changed;
  var $dbh;
  var $planwatch;
  var $groupData;
  var $groups;
  var $numNew = 0;
  var $user;

  /* Creates a planwatch object for $user
   */
  function __construct (&$user) {
    $this->user = $user;
    $this->dbh = Planworld::_connect();
    if(!$this->dbh){
      throw new PDOException('Database connection not initialized.');
    }
    if ($this->user->isUser()) {
      $this->load();
    }
  }


  /* Loads planwatch data into this object.
   * Previous versions used a sort variable as an argument. This has now been removed because it is up to the client to sort. */
  function load() {
    /* One major change from the original code is that we do all the DB calls at once to clear any locks and reduce overall latency. */
    try{
      $currentUid = $this->user->getUserID();
      $query1 = $this->dbh->prepare('SELECT u.username, u.id, p.last_view, u.last_update, g.name AS name, g.gid AS gid, g.uid AS owner, m.seen AS hasmessage FROM (pw_groups AS g, planwatch AS p, users AS u) LEFT JOIN message AS m ON (m.uid=p.w_uid AND m.to_uid=p.uid) WHERE p.uid=:uid AND p.w_uid=u.id AND g.gid=p.gid');
      $query2 = $this->dbh->prepare('SELECT u.username, u.id, u.last_update FROM users AS u INNER JOIN message ON u.id=message.uid LEFT JOIN online ON message.uid=online.uid WHERE message.to_uid=:uid AND message.seen=0');
      $query3 = $this->dbh->prepare('SELECT username, id, last_update, sent, seen FROM send, users WHERE send.uid=users.id AND to_uid=:uid AND seen=0 ORDER BY username');
      $queryArray = array('uid' => $currentUid);
      $query1->execute($queryArray);
      $result1=$query1->fetchAll();
      $query2->execute($queryArray);
      $result2=$query2->fetchAll();
      $query3->execute($queryArray);
      $result3=$query3->fetchAll();

      if (!($result1)){
        return PLANWORLD_ERROR;
      }
      else {
        $this->planwatch = array();
        $this->groupData = array();
        $this->groupData['Send'] = array();
        $this->groups = array();
        foreach($result1 as $row) {
          $watchuser = addslashes($row['username']);
          $group = $row['name'];
          $this->planwatch[$watchuser] = array((int) $row['id'],
            (int) $row['last_update'],
            (int) $row['last_view'],
            false,
            ((isset($row['hasmessage']) && $row['hasmessage'] == 0) ? true : false)
          );
          /* create a pointer to this entry within the appropriate group */
          $this->groupData[$group][$watchuser] = &$this->planwatch[$watchuser];
          /* if it's new, increment the number of new plans */
          if (($row['last_update']) > ($row['last_view'])) {
            $this->numNew++;
          }
        }
      }

      /* Get snoop group */
      foreach (Snoop::getReferences($this->user) as $u) {
        $username = $u['userName'];
        if (!isset($this->planwatch[$username])) {
          /* Create a new planwatch entry if one doesn't already exist */
          $this->planwatch[$username] = array($u['userID'], $u['lastUpdate'], 9999999999);
        } else {
          $this->planwatch[$username]['count'] = 2;
        }
        $this->groupData['Snoop'][$username] = &$this->planwatch[$username];
      }

    }
    catch(PDOException $badquery){
      return PLANWORLD_ERROR;
    }


    /* Everything from this point on is additive. */

    /* Get primary send group */
    if (isset($result2)) {
      foreach($result2 as $row) {
        $username = $row['username'];
        if (!isset($this->planwatch[$username])) {
          $this->planwatch[$username] = array($row['id'], $row['last_update'], 9999999999, false, true);
        }
        else if (isset($this->groupData['Snoop'][$username])) {
            $this->planwatch[$username]['count'] = 3;
          }
        else {
          $this->planwatch[$username]['count'] = 2;
        }
      }
    }

    /* get secondary send group */
    if (isset($result3)) {
      foreach($result3 as $row) {
        $username = $row['username'];
        if (!isset($this->planwatch[$username])) {
          $this->planwatch[$username] = array($row['id'], $row['last_update'], 9999999999, false, true);
        } else if (isset($this->groupData['Snoop'][$username])) {
            $this->planwatch[$username]['count'] = 3;
          } else {
          $this->planwatch[$username]['count'] = 2;
        }
        $this->groupData['Send'][$username] = &$this->planwatch[$username];
      }
    }
    if (empty($this->groupData['Send'])) unset($this->groupData['Send']);
    $this->changed = false;
  }

  /**
   * void save ()
   * Saves planwatch data.
   */
  function save(){
    if ($this->changed) {
      foreach ($this->planwatch as $u=>$entry) {
        if (isset($entry[3]) && $entry[3]) {
          try{
            $currentUid = $this->user->getUserID();
            $watchUid = Planworld::nameToID($u);
            $entry1 = $entry[1];
            $query = $this->dbh->prepare('UPDATE planwatch SET last_view=:entry1 WHERE uid=:uid and w_uid=:wuid');
            $queryArray = array('uid' => $currentUid, 'wuid' => $watchUid, 'entry1' => $entry1);
            $query->execute($queryArray);
          }
          catch(PDOException $badquery){
            return PLANWORLD_ERROR;
          }
        }
      }
    }
  }

  /**
   * bool inPlanwatch ($uid)
   * Returns whether $uid is in this user's planwatch or not.
   */
  function inPlanwatch ($uid) {
    if (is_object($uid)) {
      $username = $uid->getUsername();
    } else if (is_string($uid)) {
        $username = $uid;
      } else {
      return false;
    }

    if (isset($this->planwatch[$username]) && $this->planwatch[$username][2] != 9999999999) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * void markSeen ($uid)
   * Marks $uid as having had his/her plan read.
   */
  function markSeen ($uid) {
    if (is_object($uid)) {
      if ($this->planwatch[$uid->getUsername()][1] > $this->planwatch[$uid->getUsername()][2]) {
        $this->numNew--;
      }

      /* update in-place and mark as changed */
      /* set view time to now */
      $this->planwatch[$uid->getUsername()][2] = mktime();
      /* set changed flag to true */
      $this->planwatch[$uid->getUsername()][3] = true;

      $this->changed = true;
    }
  }

  /**
   * int getNumNew ()
   * Returns the number of users that are marked as new.
   */
  function getNumNew () {
    return $this->numNew;
  }

  /**
   * int getNum ()
   * Returns the number of users on said planwatch.
   */
  function getNum () {
    return sizeof($this->planwatch);
  }

  /**
   * array getList ()
   * Returns an array containing this user's planwatch.
   */
  function &getList () {
    return $this->groupData;
  }


  /**
   * array getGroups ()
   * Returns an array with all of the groups.
   */
  function getGroups () {
    $this->groups = array();
    try{
      $currentUid = $this->user->getUserID();
      $query = $this->dbh->prepare('SELECT gid, uid AS owner, name, pos FROM pw_groups WHERE uid=0 OR uid IS NULL OR uid=:uid ORDER BY pos, name');
      $queryArray = array('uid' => $currentUid);
      $query->execute($queryArray);
      $result=$query->fetchAll();
      if (!($result)){
        return PLANWORLD_ERROR;
      }
      else{
        foreach($result as $row) {
          $group = $row['name'];
          $this->groups[$group] = array((int) $row['gid'],
            ($row['owner'] == $this->user->getUserID()) ? true : false,
            (int) $row['pos']);
        }
        return $this->groups;
      }
    }
    catch(PDOException $badquery){
      return PLANWORLD_ERROR;
    }
  }

  function getGroupNames () {
    $arrayGroupNames = array();
    try{
      $currentUid = $this->user->getUserID();
      $query = $this->dbh->prepare('SELECT name FROM pw_groups WHERE uid=0 OR uid IS NULL OR uid=:uid ORDER BY name');
      $queryArray = array('uid' => $currentUid);
      $query->execute($queryArray);
      $result=$query->fetchAll();
      if (!($result)){
        return PLANWORLD_ERROR;
      }
      else{
        foreach($result as $row) {
          $arrayGroupNames[] = $row['name'];
        }
        return $arrayGroupNames;
      }
    }
    catch(PDOException $badquery){
      return PLANWORLD_ERROR;
    }
  }


  /**
   * void move ($uid, $gid)
   * Moves $uid into group $gid.
   */
  function move ($uid, $gid) {
    $intUid = $this->user->getUserID();
    $intWUid = -1;
    if(is_int($uid)){
      $intWUid = $uid;
    }
    else{
      $intWUid = Planworld::nameToID($uid);
    }
    $query = $this->dbh->prepare('UPDATE planwatch SET gid=:gid WHERE w_uid=:wuid AND uid=:uid');
    $queryArray = array('wuid' => $intWUid, 'uid' => $intUid, 'gid' => $gid);
    $query->execute($queryArray);
  }


  /**
   * int addGroup ($name)
   * Create a group named $name
   */
  function addGroup ($name) {
    try{
      $intUid = $this->user->getUserID();
      $id = (int) $this->dbh->nextId('groupid'); // GUESS WHAT THIS DOESN'T WORK OUTSIDE OF PEAR OOPS. FIX
      $query = $this->dbh->prepare('INSERT INTO pw_groups (gid, uid, name) VALUES (:id, :uid, :name)');
      $queryArray = array('id' => $id, 'uid' => $intUid, 'name' => $name);
      $query->execute($queryArray);
      return true;
    }
    catch(PDOException $badquery){
      return false;
    }
  }

  /**
   * void removeGroup ($gid)
   * Remove group with id $gid.
   */
  function removeGroup ($gid) {
    $intUid = $this->user->getUserID();
    $queryArray = array('gid' => $gid, 'uid' => $intUid);
    /* delete the group */
    $query = $this->dbh->prepare('DELETE FROM pw_groups WHERE gid=:gid AND uid=:uid');
    $query1->execute($queryArray);
    /* move entries from that group into the unsorted category */
    $query2 = $this->dbh->prepare('UPDATE planwatch SET gid=1 WHERE gid=:gid AND uid=:uid');
    $query2->execute($queryArray);
  }


  function renameGroup ($gid, $name) {
    $intUid = $this->user->getUserID();
    $query = $this->dbh->prepare('UPDATE pw_groups SET name=:name WHERE gid=:gid AND uid=:uid');
    $queryArray = array('gid' => $gid, 'uid' => $intUid, 'name' => $name);
    $query->execute($queryArray);
  }

  /**
   * void remove ($uid)
   * Removes $uid from this user's planwatch.
   */
  function remove ($uid) {
    unset($this->planwatch[$uid]);
    $intUid = $this->user->getUserID();
    $intWUid = -1;
    if(is_int($uid)){
      $intWUid = $uid;
    }
    else{
      $intWUid = Planworld::nameToID($uid);
    }
    $query = $this->dbh->prepare('DELETE FROM planwatch WHERE w_uid=:wuid AND uid=:uid');
    $queryArray = array('wuid' => $intWUid, 'uid' => $intUid);
    $query->execute($queryArray);
  }

  /**
   * void add ($uid)
   * Adds $uid to this user's planwatch.
   */
  function add ($uid) {
    /* no need to fill this entry, as the planwatch will probably be reloaded before it's used */
    $intUid = $this->user->getUserID();
    $intWUid = -1;
    if(is_int($uid)){
      $intWUid = $uid;
    }
    else{
      $intWUid = Planworld::nameToID($uid);
    }
    try{
      $query = $this->dbh->prepare('INSERT INTO planwatch (w_uid, uid) VALUES (:wuid, :uid)');
      $queryArray = array('wuid' => $intWUid, 'uid' => $intUid);
      $query->execute($queryArray);
      return true;
    }
    catch(PDOException $badquery){
      return false;
    }
  }

  /* JLO2 20191005 - This can't possibly be as simple as I think it is. */
  function getInteroperableWatchlist(){
    $arrayInteroperableList = array();
    foreach ($this->planwatch as $username => $watchrow){
      if(!($this->user->doesBlockRelationshipExist($username))){
        $watchlineArray = array(
          'username' => $username,
          'lastupdate' => date(DATE_ATOM, $watchrow[1]),
          'lastview' => date(DATE_ATOM, $watchrow[2]),
          'hasmessage' => $watchrow[3]
        );
        $arrayInteroperableList[] = $watchlineArray;
      }
    }
    return $arrayInteroperableList;
  }


  /* Separate call to get group information. */
  function getInteroperableWatchlistGroups(){
    $arrayGroupLevelList = array();
    $intCounter = 0;
    foreach ($this->groupData as $groupname => $grouparray){
      $arrayGroupLevelList[$intCounter] = array();
      $arrayGroupLevelList[$intCounter]['groupname'] = $groupname;
      $arrayGroupLevelList[$intCounter]['membership'] = array();
      foreach ($grouparray as $username => $watchrow){
        $watchlineArray = array(
          'username' => $username,
          'lastupdate' => date(DATE_ATOM, $watchrow[1]),
          'lastview' => date(DATE_ATOM, $watchrow[2]),
          'hasmessage' => $watchrow[3]
        );
        $arrayGroupLevelList[$intCounter]['membership'][] = $watchlineArray;
      }
      $intCounter++;
    }
    return $arrayGroupLevelList;
  }




}
?>