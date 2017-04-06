**Readme For Planworld Alphas**
--------------------------------------
_Last Updated 20170406_ by jlodom00


_Releases in V3.00 Alpha Series_
20170406 - "Louisbourg" - Send Only Release Without Clients

_License_
This release does not yet include proper attribution for authors (Seth Fitzsimmons, Baker Franke, Johnnie Odom et al) but it is licensed using the GPLv2 as the previous version was. Future releases will more properly note this.

_Introduction_
Use these instructions to set up an independent test instance of the in-progress Planworld v3 code for testing and client development. It contains only a tiny portion of the full Planworld functionality, and the code is not as well-documented or cleaned up as it will be by release. The instructions are clear to me (jlodom00), but I deal with this sort of thing all the time so feel free to reach out ( jlodom00@alumni.amherst.edu ) if you need help.


_Prerequisites:_
1. Apache Web Server configured with rewrite and headers modules and with "AllowOverride All" enabled on the site.
2. PHP 5.2 or greater with common extensions (pdo w/ mysql support, crypt, json, xml ... more to be added I'm sure).
3. MySQL or MariaDB.


_Installation:_
1. Create the planworld MySQL account and planworld database by running the MySQL commands contained in setup/planworld_mysql_db_create.sql
2. Copy everything in the code folder to the place on your apache server where planworld will run.
3. Edit code/config.php to match your configuration (it may not need any changes).
4. Modify client/htaccess.rename to fit the paths on your server and rename it to .htaccess (with leading period).
5. Edit client/index.php and change the path for $pathClientPrefix.
6. (Optional) Edit scaffolding/debug_client.html and change "var planworldbasedefault" and the nodeURL form input to the base client API url of your server (i.e. the URL a web browser would use to get to the client directory).


_Testing Setup And Alpha Administration_

1. You know that the engine is responding to API calls if you can open a web browser and go to http://myserver.com/path/to/client/3.00/system/version.xml (where /path/to/client/ is the URL path to the client directory) and get a page with "3.00" as a response. If you are having difficulty at this step, the problem is probably with your Apache configuration (especially your website config and very likely your Directory options).

2. Planworld is empty when you start, so you will need to use the webpages in the scaffolding directory to add users, create tokens for them, and see what users exist on your system. If you have difficulty here, check your mysql setup.

3. Once you have users and tokens, you can test API functionality with the debug client included in scaffolding/debug_client.html. See the Client API notes below for rough usage of this page.


_The Planworld Client API_

The major new feature of Planworld v3 is a RESTful API that can be used to write clients independent of the Planworld server code. This will allow for much more rapid and diverse development of user functionality in the future. It will also allow for major changes to the underlying server engine without disrupting the user experience. To deliver the API there is also a lot of work modernizing and cleaning up the underlying engine.

*URL Structure*

The basic structure of the API REST calls was determined around 2010 and has had only minor changes since then. A typical API call will look like the following:

http://servername.com/some/path/client/0.00/category/verb/argument1/argument2.format?token=tokenumber

A real-world example of this would be:

https://somewhere.com/planworld/client/3.00/send/send/alice.xml?token=45678912

Breaking down those components:

BASE PATH: http://servername.com/some/path/client/
	This is the web URL to the client folder on the Planworld engine web server. If you go to this address and receive the message "No REST Url." then you know you are on the right track.
	
VERSION: 0.00/
	In order to make future-proofing easier, the version of the API used is represented here as a number with two decimal places. Currently only a value of 3.00 is supported.
	
CATEGORY: category/

	This portion of the path is meant to make grouping planworld calls/functions/verbs easier, especially for those implementing the engine. If you have trouble making an API call, (especially using the debug client), check to make sure that this value is correct for the verb that is being called. Current values are "system" and "send".
	
VERB: verb/
	This is the actual verb or planworld function being called. These have different properties depending upon whether information is being retrieved (GET) or sent (POST) and the same verb may perform both roles. Current values are "version" (pairing with the "system" category), "send", and "sendlist" (both pairing with the "send" category). Of the four verbs listed, all support GET but only "send" supports POST.
	
ARGUMENTS: argument1/argument2
	Planworld verbs may take an arbitrary number of arguments. Each of these is a separate directory-separator, but the last one should not have a trailing slash.
	
FORMAT: .format
	Data will be returned to the client in the format specified here. Allowed values are txt, xml, and json. The URL always replaces the last forward slash with a period followed by format (i.e. /urlend.format instead of /urlend/). For xml and json, data is returned as an array whose key is the name of the Planworld verb.
	
TOKEN: ?token=tokenumber
	Authentication is currently handled by a tokening system. The mid-term plan (post v3.00) is to move to OAuth, and so the token is implemented as a parameter rather than an integral part of the URL. In this alpha tokens are numeric, but by release they should be alphanumeric. Not all verbs require a token -- for example the "version" can be called anonymously.
	
*Posting Data*

For calls that send data to the engine (such as posting sends and plans), the data should be posted under the name "planworld_post". They use the same URL format as any other call, just with a HTTP POST instead of a GET.
	
*Currently Supported Calls*

Category - system
	version : GET Returns the version of the Planworld API supported. No arguments. Does not require token.

Category - Send (Tokens required for all)
	send : GET Returns the full send conversation between the user and another user. Takes one argument -- the username of the other user in the conversation.
	send : POST Send a new message to another user. Takes one argument -- the username of the other user in the conversation.	
	sendlist : GET Returns a list of every user with whom the current user has had a send conversation (similar to a watchlist). No arguments.
	
*Writing a Client*

For this alpha release, you will need to create a user and a token for that user before client calls can be made because there is no way to create tokens through the client API. Clients may be written on any platform and in any programming language -- as long as they can make GET and POST HTTP calls to the API URLs. The API is a REST API and generally conforms to the conventions established by that style.

Future releases will include example clients in a variety of languages. They have been omitted from this release because we needed to ship.


_Planworld v3 Upgrade Work_

Much work has already been done on Planworld Version 3. Unfortunately, we cannot expose this previous work until some updates are made to the underlying libraries that form the lowest layer of the application. The database library that NOTE Planworld v2 uses is not only obsolete, but the successor library is obsolete also. Therefore it is necessary to replace every database call in the libraries with a much more forward-compatible call to the PHP PDO libraries. 

As large sections of the Planworld libraries are rewritten, the pre-existing API code can be layered on top of it and new functionality can be rolled out even before the entire codebase has been upgraded. Even though the current release only supports "send", the plumbing that has been done to make even this functionality possible forms a foundation for the rest of the work.

The current work is to take functions from the old lib files that make database calls and rewrite them in the new files, one by one, to use PDO.

The basic rules for this work are:
1. Old SQL calls should be rewritten to use PDO.
2. Where possible, the function should be made more paranoid -- it should assume that bad data will enter it and should be constructed to deal gracefully with failure.
3. Comments should be added.
4. Formatting should be cleaned up
5. Obsolete calls (such as those directly creating display formatting) should be eliminated.


*EXAMPLE*

Here is a "before and after" example of the kind of work that needs to happen with the libraries. If you would like to contribute in this area, rather than working on clients, please contact Johnnie Odom and he will make libraries available for you to update.

*Before*

  /*
   string Planworld::idToName ($uid)
   converts numeric $uid to string representation
  */
  function idToName ($uid) {
    static $table; /* persistent lookup table */
    if (is_int($uid)) {
      $dbh = Planworld::_connect();
      if (isset($table[$uid])) {
        return $table[$uid];
      }
      else {
        $query = "SELECT username FROM users WHERE id={$uid}";
        $result = $dbh->query($query);
        if (isset($result) && !DB::isError($result)) {
          if ($result->numRows() < 1) return PLANWORLD_ERROR;
          $row = $result->fetchRow();
          $table[$uid] = $row['username'];
          return $table[$uid];
        }
        else {
          return PLANWORLD_ERROR;
        }
      }
    }
    else {
      return PLANWORLD_ERROR;
    }
  }


*After*


/*
   string Planworld::idToName ($uid)
   converts numeric $uid to string representation
   Note that the argument must not look like a string -- it must be sent as an int.
  */
  function idToName ($uid) {
    static $table; /* persistent lookup table */
    if (is_int($uid)) {
      $dbh = Planworld::_connect();
      if (isset($table[$uid])) {
        return $table[$uid];
      }
      else {
	      try{
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