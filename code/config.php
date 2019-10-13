<?php


/* change this to reflect your node configuration settings */
define('PW_NAME', 'localtest');
define('PW_INCEPTION', '4/5/17');
define('PW_VERSION', '3.00');

/* database information */
define('PW_DB_TYPE', 'mysql'); /* Acceptable values are 'mysql' and 'pgsql' */
define('PW_DB_HOST', 'localhost');
define('PW_DB_USER', 'pwmysqluser');
define('PW_DB_PASS', 'planworld');
define('PW_DB_NAME', 'PLANWORLD_PUBLIC_MYSQL');

/* set the idle timeout for "online users" to 10 minutes */
define('PW_IDLE_TIMEOUT', 600);

/* set the default timezone */
define('PW_TIMEZONE', 'America/Chicago');

/* API definitions */
define("TOKEN_LIFE",	2592000); /* Default is six hours - 6 hours * 60 minutes * 60 seconds = 21600 seconds. */
define("NODE_TOKEN_PREFIX",	999000000);
define("TRUSTEDAPPKEY", "BogusKey");
define("TESTLEVEL", "LOCALTEST"); /* Options are PRODUCTION, TEST, and LOCALTEST */
define("LOCKTIME", 86400); /* Time for values like blocklists and sensitive preferences to be locked before next change. */

/* Cross-library definitions */
define('PLANWORLD_OK', 0); /* PLANWORLD_OK Operation succeeded. */
define('PLANWORLD_ERROR', -1); /* PLANWORLD_ERROR Operation failed. */
define('CR', "\n");
?>
