<?php
/* Snoop.php
 * v. 3.0beta 20190703 JLO2
 * v 1.10.2.2 2003/11/02 16:12:35 seth Exp $ */

/* Includes and Housekeeping */
require_once($_base . 'lib/Planworld.php');
require_once($_base . 'backend/epi-utils.php');
date_default_timezone_set(PW_TIMEZONE);

class Snoop
{
    /**
     * Calls a remote method via xml-rpc.
     * @param method Method to call.
     * @param params Parameters to use.
     * @private
     */
    public function _call($server, $method, $params=null)
    {
        return xu_rpc_http_concise(array('method' => $method,
                     'args'   => $params,
                     'host'   => $server['Hostname'],
                     'uri'    => $server['Path'],
                     'port'   => $server['Port'],
                     'debug'  => 0)); // 0=none, 1=some, 2=more
    }

    /**
     * Pulls references from $content.
     * @param content Content to search.
     * @returns matches Array of references.
     * @private
     */
    public function _getReferences($content)
    {
        /* find references in plan */
        preg_match_all("/!([a-z0-9\-\.@]+)(!|:[^!]+!)/i", $content, $matches, PREG_PATTERN_ORDER);
        return $matches;
    }

    /**
     * void Snoop::addReference ($from, $to)
     * Add a snoop reference by $from for $to
     */
    public function addReference($from, $to, $date=null)
    {
        $dbh = Planworld::_connect();

        if (!isset($date)) {
            $date = mktime();
        }

        if ($from == 0 || $from == '' || $to == 0 || $to == '') {
            return false;
        }
      
        $query = $dbh->prepare('INSERT INTO snoop (uid, s_uid, referenced) VALUES(:to, :from, :date)');
        $queryArray = array('to' => $to, 'from' => $from, 'date' => $date);
        $result = $query->execute($queryArray);
        if ($result) {
            return PLANWORLD_OK;
        } else {
            return PLANWORLD_ERROR;
        }
        return PLANWORLD_ERROR;
    }

    /**
     * void Snoop::removeReference ($from, $to)
     * Removes a snoop reference by $from for $to
     */
    public function removeReference($from, $to)
    {
        $dbh = Planworld::_connect();
        $query = $dbh->prepare('DELETE FROM snoop WHERE uid=:to AND s_uid=:from');
        $queryArray = array('to' => $to, 'from' => $from);
        $result = $query->execute($queryArray);
        if ($result) {
            return PLANWORLD_OK;
        } else {
            return PLANWORLD_ERROR;
        }
        return PLANWORLD_ERROR;
    }

    /**
     * void Snoop::clearReferences ($uid)
     * Clear all snoop references by $uid
     */
    public function clearReferences($uid)
    {
        $dbh = Planworld::_connect();
        $query = $dbh->prepare('DELETE FROM snoop WHERE uid=:uid');
        $queryArray = array('uid' => $uid);
        $result = $query->execute($queryArray);
        if ($result) {
            return PLANWORLD_OK;
        } else {
            return PLANWORLD_ERROR;
        }
        return PLANWORLD_ERROR;
    }

    /**
     * void Snoop::clearRemoteReferences ($uid)
     * Clear all remote snoop references by $uid
     */
    public function clearRemoteReferences($node, $uid)
    {
        Snoop::_call($node, 'planworld.snoop.clear', $uid . '@' . PW_NAME);
    }

    /**
     * Case-insensitive array diff that prunes duplicates
     */
    public function snoop_diff($old, $new)
    {
        $old = array_map('strtolower', $old);
        $new = array_map('strtolower', $new);
        return array_unique(array_diff($old, $new));
    }

    /**
     * void Snoop::process ($user, $new, $old)
     * Find new / removed snoop references in $user's plan.
     */
    public function process(&$user, $new, $old)
    {

    /* find references in old plan */
        $dbh = Planworld::_connect();
        try {
            if (!$dbh) {
                throw new PDOException('Database connection not initialized.');
            }
            $query = $dbh->prepare('SELECT username FROM snoop, users WHERE snoop.uid = users.id AND s_uid = :uid');
            $queryArray = array('uid' => $user->getUserID());
            $query->execute($queryArray);
            $old_matches = $query->fetchAll(PDO::FETCH_COLUMN);
            if (!$old_matches) {
                return PLANWORLD_ERROR;
            } else {
                /* find references in new plan */
                $new_matches = Snoop::_getReferences($new);

                /* find differences */
                $users_to_add = Snoop::snoop_diff($new_matches[1], $old_matches);
                $users_to_del = Snoop::snoop_diff($old_matches, $new_matches[1]);

                $success = true;
                foreach ($users_to_add as $u) {
                    if (strstr($u, '@')) {
                        list($username, $host) = explode('@', $u);
                    }

                    $sid = Planworld::nameToID($u);
                    /* valid local user */
                    if (!isset($host) && $sid > 0) {
                        $success = $success && Snoop::addReference($user->getUserID(), $sid);
                    }
                    /* remote planworld user */

                    elseif (isset($host) && $node = Planworld::getNodeInfo($host)) {
                        unset($host); /* JLO2 4/12/10 Required to stop permasnoops after calling remote users. */

                        if ($node['Version'] < 2) {
                            Snoop::_call($node, 'snoop.addReference', array($username, $user->getUsername() . '@' . PW_NAME));
                        } else {
                            Snoop::_call($node, 'planworld.snoop.add', array($username, $user->getUsername() . '@' . PW_NAME));
                        }
                    }
                }

                foreach ($users_to_del as $u) {
                    if (strstr($u, '@')) {
                        list($username, $host) = explode('@', $u);
                    }

                    $sid = Planworld::nameToID($u);
                    if (!isset($host) && $sid > 0) {
                        /* valid local user */

                        $success = $success && Snoop::removeReference($user->getUserID(), $sid);
                    } elseif (isset($host) && $node = Planworld::getNodeInfo($host)) {
                        /* remote planworld user */
                        unset($host); /* JLO2 4/12/10 Required to stop permasnoops after calling remote users. */

                        if ($node['Version'] < 2) {
                            Snoop::_call($node, 'snoop.removeReference', array($username, $user->getUsername() . '@' . PW_NAME));
                        } else {
                            Snoop::_call($node, 'planworld.snoop.remove', array($username, $user->getUsername() . '@' . PW_NAME));
                        }
                    }
                }
                return $success;
            }
        } catch (PDOException $badquery) {
            return PLANWORLD_ERROR;
        }
    }


    /* Previous version had sort arguments. The client should do the sorting, so we removed them.
     * The code allows users to be sent in as usernames, objects, or user ids, and the original code did a different query based on that.
     * We have simplified so that user id is always used. */
    public static function getReferences(&$user)
    {
        $dbh = Planworld::_connect();
        $userid = -1;
        if (is_int($user)) {
            $userid = $user;
        } elseif (is_string($user)) {
            $userid = Planworld::nameToID($user);
        } elseif (is_object($user)) {
            $userid = $user->getUserID();
        }
        try {
            if (!$dbh) {
                throw new PDOException('Database connection not initialized.');
            }
            $query = $dbh->prepare('SELECT s_uid, referenced, username, last_update FROM snoop,users WHERE uid=:uid AND users.id=s_uid');
            $queryArray = array('uid' => $userid);
            $query->execute($queryArray);
            $result = $query->fetchAll();
            if (!$result) {
                return array();
            } else {
                $return = array();
                /* April fool's easter egg */
                if (date('n-j') == '4-1') {
                    $uid = Planworld::getRandomUser();
                    $return[] = array("userID" => $uid,
              "userName" => Planworld::idToName($uid),
              "date" => mktime(0, 0, 0, 4, 1, date('Y')),
              "lastUpdate" => Planworld::getLastUpdate($uid));
                }

                foreach ($result as $row) {
                    $return[] = array("userID" => (int) $row['s_uid'],
                    "userName" => $row['username'],
                    "date" => (int) $row['referenced'],
                    "lastUpdate" => (int) $row['last_update']);
                }

                return $return;
            }
        } catch (PDOException $badquery) {
            return PLANWORLD_ERROR;
        }
    }
}
