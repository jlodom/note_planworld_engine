**Readme For Planworld Alphas**
--------------------------------------
_Last Updated 220191012_ by jlodom00

_Releases in V3.00 Alpha Series_

    20191012 - "Sevenoaks" - Proper release of the features targeted in "Gordon" with many additions and bugfixes including always lowercasing usernames in important library calls. Most importantly, blocklists have been implemented. Enough of the API is now built that developers can create a proper client targeting it. We expect that the API will change as developers work with it. Only pseudo-authentication is supported via the "debug" API calls.

    20191005 - "Gordon" - Interim Release to get watchlist and basic plan-handling functionality as well as other improvements out to potential programmers before testing is finished. Necessitated by some discussion of how blocklists will be implemented. 

    20180409 - "Loudon" - Fixed sendlist db query. Also make a note -- PHP 5.5 or later is required. Need to eliminate old JSON file and depedency. MAKE ANOTHER NOTE -- The NOTE Production Database contains invalid usernames. We filter for this in PW->getallusers, but in the future we should have a cleaner process to eliminate them. ALSO getallusers is part of scaffolding and the API userslocalGet call but it doesn't return all users now, just the local ones who are properly formatted.

    20170406 - "Louisbourg" - Send Only Release Without Clients


_License_
This release does not yet include proper attribution for authors (Seth Fitzsimmons, Baker Franke, Johnnie Odom et al) but it is licensed using the GPLv2 as the previous version was. Future releases will more properly note this.
As of October 2019 this software uses [HTML Purifier]( http://htmlpurifier.org/ )


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
6. Make the directory at backend/standalone/HTMLPurifier/DefinitionCache/Serializer writable (probably by using "chmod 777 backend/standalone/HTMLPurifier/DefinitionCache/Serializer" )
7. (Optional) If testing the environment, create a file in the backend directory called debug.on to enable the "debug" API category. Currently this is the only way to retrieve a token using the API. When transitioning to a production environment both the debug.php file in clientlib and the debug.on file should be removed.
8. (Optional) Edit scaffolding/debug_client.html and change "var planworldbasedefault" and the nodeURL form input to the base client API url of your server (i.e. the URL a web browser would use to get to the client directory).



_Testing Setup And Alpha Administration_

1. You know that the engine is responding to API calls if you can open a web browser and go to http://myserver.com/path/to/client/3.00/system/version.xml (where /path/to/client/ is the URL path to the client directory) and get a page with "3.00" as a response. If you are having difficulty at this step, the problem is probably with your Apache configuration (especially your website config and very likely your Directory options).

2. Planworld is empty when you start, so you will need to use the webpages in the scaffolding directory to add users. If you have difficulty here, check your mysql setup.

3. Once you have users you can test API functionality with the debug client included in scaffolding/debug_client.html. See the Client API notes below for rough usage of this page.


_The Planworld Client API_

The major new feature of Planworld v3 is a REST API that can be used to write clients independent of the Planworld server code. This will allow for much more rapid and diverse development of user functionality in the future. It will also allow for major changes to the underlying server engine without disrupting the user experience. To deliver the API there is also a lot of work modernizing and cleaning up the underlying engine.


*URL Structure*

The basic structure of the API REST calls was determined around [2010](https://github.com/joshuawdavidson/Planworld-v3-Client-API-Documentation) and has had only minor changes since then (although it does differ from the link above at present). A typical API call will look like the following:

http://servername.com/some/path/client/0.00/category/verb/argument1/argument2.format?token=tokenumber

A real-world example of this would be:

https://somewhere.com/planworld/client/3.00/send/send/alice.xml?token=45678912

Breaking down those components:

BASE PATH: http://servername.com/some/path/client/
	This is the web URL to the client folder on the Planworld engine web server. If you go to this address and receive the message "No REST Url." then you know you are on the right track.
	
VERSION: 0.00/
	In order to make future-proofing easier, the version of the API used is represented here as a number with two decimal places. Currently only a value of 3.00 is supported.
	
CATEGORY: category/

	This portion of the path is meant to make grouping planworld calls/functions/verbs easier, especially for those implementing the engine. If you have trouble making an API call, (especially using the debug client), check to make sure that this value is correct for the verb that is being called. Current values are "system", "plan", "send", "watch", and "block".
	
VERB: verb/
	This is the actual verb or planworld function being called. These have different properties depending upon whether information is being retrieved (GET) or sent (POST) and the same verb may perform both roles. 
	
ARGUMENTS: argument1/argument2
	Planworld verbs may take an arbitrary number of arguments. Each of these is a separate directory-separator, but the last one should not have a trailing slash.
	
FORMAT: .format
	Data will be returned to the client in the format specified here. Allowed values are txt, xml, and json. The URL always replaces the last forward slash with a period followed by format (i.e. /urlend.format instead of /urlend/). For xml and json, data is returned as an array whose key is the name of the Planworld verb.
	
TOKEN: ?token=tokenumber
	Authentication is currently handled by a tokening system. The mid-term plan (post v3.00) is to move to OAuth2, and so the token is implemented as a parameter rather than an integral part of the URL. In this alpha tokens are numeric, but by release they should be alphanumeric. Not all verbs require a token -- for example the "version" can be called anonymously.
	
	
*Posting Data*

For calls that send data to the engine (such as posting sends and plans), the data should be posted under the name "planworld_post". They use the same URL format as any other call, just with a HTTP POST instead of a GET.
	
	
*Currently Supported Calls*


Category - debug (The file implementing these API calls should be deleted in production.)
	pseudologin : GET Takes two arguments -- the username and password (as returned by the "adduser" scaffolding tool) of the user logging in. Returns a token that should be used for all API calls requiring a token.

Category - system
	version : GET Returns the version of the Planworld API supported. No arguments. Does not require token.
	userslocal : GET Returns all the current users registered locally to this Planworld node. Requires a token.

Category - plan (Tokens required for all)
	plan : GET Returns a user's plan. Takes one argument -- the username of the plan to be read.
	plan : POST Returns a boolean indicating success or failure. Deletes a user's current plan and replaces it with the data in the POST. The plan data should be in basic HTML format (which could encompass plain text). Scripts and other potentially harmful content will be stripped out by HTMLPurifier.

Category - send (Tokens required for all)
	send : GET Returns the full send conversation between the user and another user. Takes one argument -- the username of the other user in the conversation.
	send : POST Send a new message to another user. Takes one argument -- the username of the other user in the conversation.	
	sendlist : GET Returns a list of every user with whom the current user has had a send conversation (similar to a watchlist). No arguments.

	
Category - watch (Tokens required for all)
	watchlist : GET Returns a user's full watchlist.
	watchlistgroup : GET Returns the same data as watchlist but organized in user-defined groups (the default group is "People").
	groupcreate : POST Creates a watchlist group. The post data should contain the name of the group to be created.
	groupmoveuser : POST Moves a user to a specified watchlist group. Takes one URL argument -- the name of the user. The name of the group should be in the post data.
	add: POST Adds a user to a watchlist. The post data should contain the username to be added.
	remove: POST Removes a user from a watchlist. The post data should contain the username to be removed.

Category - block (Tokens required for all)
	blocklist : GET Returns a user's full watchlist.
	blockedby : GET Returns a list of users who are blocking the requested user. 
	add: POST Adds a user to a blocklist. The post data should contain the username to be added.
	remove: POST Removes a user from a blocklist. The post data should contain the username to be removed.


_Writing a Client_

For this alpha release, you will need to create a user before making any client API calls. Afterwards all client functionality can be simulated using the APIs. Clients may be written on any platform and in any programming language -- as long as they can make GET and POST HTTP calls to the API URLs anything is possible. The API generally conforms to the conventions established by REST but we do expect some changes and improvements as we refine it through real-world client creation.

Future releases will include example clients in a variety of languages. Currently the "Cend" client is available which implements the "send" catgeory calls using ReactJS. Otherwise the debug_client.html file may be used to simulate API calls. As of the "Sevenoaks" release we are turning our attention towards creating other reference clients.


_Blocklists_

As social media has become more sophisticated and as online harassment has grown there has been a lively debate in the existing Planworld communities regarding what can be done to address the various ills afflicting social media as relates to Planworld. This discussion is not over and will require further sophistication in the future. As an interim step, blocklists have been implemented in the API. When a user blocks another user it cuts off all new contact between the two: Previous sends and watchlist entries remain, but no new sends can be sent and new watchlist information will not be sent. Most vitally, plans cannot be read and snoops will be discarded. This process works both ways: A user who blocks another user will not only be protected from that user, they are also blocking themselves from that user.


_Planworld v3 Upgrade Work_

Much work has already been done on Planworld Version 3. Unfortunately, we cannot expose this previous work until some updates are made to the underlying libraries that form the lowest layer of the application. The database library that NOTE Planworld v2 uses is not only obsolete, but the successor library is obsolete also. Therefore it is necessary to replace every database call in the libraries with a much more forward-compatible call to the PHP PDO libraries. 

As large sections of the Planworld libraries are rewritten, the pre-existing API code can be layered on top of it and new functionality can be rolled out even before the entire codebase has been upgraded. 

The current work is to take functions from the old lib files that make database calls and rewrite them in the new files, one by one, to use PDO.

The basic rules for this work are:
1. Old SQL calls should be rewritten to use PDO.
2. Where possible, the function should be made more paranoid -- it should assume that bad data will enter it and should be constructed to deal gracefully with failure.
3. Comments should be added.
4. Formatting should be cleaned up
5. Obsolete calls (such as those directly creating display formatting) should be eliminated.


*EXAMPLE*

Here is a "before and after" example of the kind of work that needs to happen with the libraries. If you would like to contribute in this area, rather than working on clients, please contact Johnnie Odom and he will make the current v2 library code available for you to update.

*Before*
```
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
```

*After*

```
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
```