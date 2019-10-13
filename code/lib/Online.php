<?php
/**
 * $Id: Online.php,v 1.6.2.2 2003/11/02 16:12:35 seth Exp $
 * Utility class for determining online users.
 */

/* includes */
$_base = dirname(__FILE__) . '/../'; /* This file lives in the lib folder, so root is one level above. */
require_once($_base . 'config.php');
require_once($_base . 'lib/User.php');
require_once($_base . 'lib/Planworld.php');
date_default_timezone_set(PW_TIMEZONE);

class Online
{

    /**
     * void Online::clearIdle ()
     * Removes users who have been idle too long
     */
    public static function clearIdle()
    {
        try {
            $dbh = Planworld::_connect();
            $query = $dbh->prepare('DELETE FROM online WHERE last_access < :timeconsideredidle');
            $queryArray = array('timeconsideredidle' => (time() - PW_IDLE_TIMEOUT));
            $query->execute($queryArray);
            return true;
        } catch (PDOException $badprequery) {
            return PLANWORLD_ERROR;
        }
    }


    /**
     * void Online::updateUser (&$user, &$target)
     * Update's $user's status to $target
     */
    public static function updateUser(&$user, $target)
    {
        $stringTarget = '';
        if (is_object($target)) {
            $stringTarget = $target->getUsername();
        } else {
            $stringTarget = addslashes($target);
        }
        if (!(empty($stringTarget))) {
            $dbh = Planworld::_connect();
            $uid = $user->getUserID();
            $boolUserIsOnline = false;
            try {
                $preQuery = $dbh->prepare('select count(uid) as count from online where uid= :uid');
                $preQueryArray = array('uid' => $uid);
                $preQuery->execute($preQueryArray);
                $result = $preQuery->fetchAll();
                if (!($result)) {
                    $boolUserIsOnline = false;
                } else {
                    if ($result[0]['count'] > 0) {
                        $boolUserIsOnline = true;
                    } else {
                        $boolUserIsOnline = false;
                    }
                }
            } catch (PDOException $badprequery) {
                return PLANWORLD_ERROR;
            }
            /* If the user is indeed already online, we just update their status. */
            if ($boolUserIsOnline) {
                try {
                    $query = $dbh->prepare('UPDATE online SET last_access= :currenttime , what= :target WHERE uid= :uid');
                    $queryArray = array('currenttime' => time(), 'target' => $stringTarget, 'uid' => $uid);
                    $query->execute($queryArray);
                    return true;
                } catch (PDOException $badquery) {
                    return PLANWORLD_ERROR;
                }
            }
            /* If the user is not already online, make them online. */
            else {
                return Online::addUser($user, $target);
            }
        } else {
            return PLANWORLD_ERROR;
        }
    }


    /**
     * void Online::addUser($user, $target)
     * Adds $user to the list of online users (with status $target)
     */
    public static function addUser(&$user, $target)
    {
        $stringTarget = '';
        if (is_object($target)) {
            $stringTarget = $target->getUsername();
        } else {
            $stringTarget = addslashes($target);
        }
        $dbh = Planworld::_connect();
        $uid = $user->getUserID();
        $currentTime = time();
        try {
            $dbh = Planworld::_connect();
            $query = $dbh->prepare('INSERT INTO online (uid, login, last_access, what) VALUES ( :uid, :currenttime, :currenttime, :target)');
            $queryArray = array('uid' => $uid, 'currenttime' => $currentTime, 'target' => $stringTarget);
            $query->execute($queryArray);
            return true;
        } catch (PDOException $badprequery) {
            return PLANWORLD_ERROR;
        }
    }



    /**
     * array Online::getOnlineUsers ()
     * Returns a list of all users who are currently online.
     */
    public static function getOnlineUsers()
    {
        try {
            $dbh = Planworld::_connect();
            $query = $dbh->prepare('SELECT users.username as name, online.last_access as lastAccess, online.login as login, online.what as what FROM users, online WHERE users.id = online.uid ORDER BY last_access DESC');
            $query->execute();
            $return = $query->fetchall();
            $finalArray = array();
            foreach ($return as $row) {
                $finalArray[] = array('name' => $row['name'],
              'lastAccess' => (int) $row['lastAccess'],
              'login' => (int) $row['login'],
              'what' => $row['what']);
            }
            return $finalArray;
        } catch (PDOException $badprequery) {
            return PLANWORLD_ERROR;
        }
    }

    /* End Class */
}
