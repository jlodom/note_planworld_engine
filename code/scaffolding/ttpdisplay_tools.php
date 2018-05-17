<?php

/* Comments on this library coming later. It should be fairly obvious what it does.
		Simplicity and HTML5 standards compliance are to be prized above all else.
		Note that it makes no assumptions about CSS -- you have to supply your own CSS file which is passed as an argument. */

/* Must use double quotes here for carriage return to be properly rendered. */
if(!(defined('CR'))){
	define('CR', "\r\n"); /* Double quotes for value because of how PHP interprets quote literals. */
}

function PrintHeader($cssPath = '', $pageTitle = 'A Website'){
	print('<!DOCTYPE HTML>' . CR);
	print('<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . CR);
	print('<html>' . CR);
	print(' <head>' . CR);
	print('  <link rel="stylesheet" type="text/css" href="' . $cssPath . '">' . CR);
	print('  <title>' . $pageTitle . '</title>' . CR);
	print(' </head>' . CR);
	print(' <body>' . CR);
	print('&nbsp<br />' . CR);
	print('<h3>' . $pageTitle . '</h3>' . CR);
	print('&nbsp<br />' . CR);
}


function PrintFooter(){
	print('' . CR);
	print(' </body>' . CR);
	print('</html>' . CR);
}


function PrintLoginForm($loginTarget='index.php', $loginMessage='', $loginResult=''){
	print(' <form name ="display_login_form" action="' . $loginTarget . '" method="POST">' . CR);
	if(!empty($loginMessage)){
		print('  <br /><span class="notice">' .  SanitizeMessages($loginMessage) . '</span><br />&nbsp;<br />' . CR);
	}
	print('  <fieldset>' . CR);
	print('   <label for="loginusername">Username</label>' . CR);
	print('   <input type="text" name="loginusername" />' . CR);
	print('  </fieldset>' . CR);
	print('  <fieldset>' . CR);
	print('   <label for="loginpassword">Password</label>' . CR);
	print('   <input type="password" name="loginpassword" />' . CR);
	print('  </fieldset>' . CR);
	print('  <fieldset>' . CR);
	print('   <button type="submit">Log In</button>' . CR);
	print('  </fieldset>' . CR);
	print('  <br />' . $loginResult . '<br />&nbsp;<br />' . CR);
	print(' </form>' . CR);
}



function PrintUsernameForm($loginTarget='index.php', $loginMessage='', $loginResult=''){
	print(' <form name ="display_username_form" action="' . $loginTarget . '" method="POST">' . CR);
	if(!empty($loginMessage)){
		print('  <br /><span class="notice">' .  SanitizeMessages($loginMessage) . '</span><br />&nbsp;<br />' . CR);
	}
	print('  <fieldset>' . CR);
	print('   <label for="newusername">Username</label>' . CR);
	print('   <input type="text" name="newusername" />' . CR);
	print('  </fieldset>' . CR);
	print('  <fieldset>' . CR);
	print('   <button type="submit">Create Username</button>' . CR);
	print('  </fieldset>' . CR);
	print('  <br />' . $loginResult . '<br />&nbsp;<br />' . CR);
	print(' </form>' . CR);
}


function PrintLogoutForm($logoutTarget='index.php', $logoutMessage=''){
	print(' <form class="smallform" name ="display_logout_form" action="' . $logoutTarget . '" method="POST">' . CR);
	if(!(empty($logoutMessage))){
		print('  <br />&nbsp;<br />' .  SanitizeMessages($logoutMessage) . '<br />' . CR);
	}
	print('   <input type="hidden" name="logoutbool" value="true" />' . CR);
	print('   <button type="submit">Log Out</button>' . CR);
	print(' </form>' . CR);
}

/* A function to make messages better for display. Strips HTML and adds a break whenever a sentence ends. */
function SanitizeMessages($unsanitizedMessage=''){
	return $sanitizedMessage = str_replace('. ','.<br />',strip_tags($unsanitizedMessage));
}



/* Given an arbitrary array of arrays, a keylist to the array on each line (i.e. the arrays in the array) and a title, print a standard HTML table. */
function PrintComplexTable($threeDArray, $keyArray, $title){
	echo '<table>' . CR .  '<caption>' . $title . '</caption>' . CR;
	/* Print Header Row */
	echo '<tr>';
	foreach($keyArray as $keyString){
		echo '<th>' . $keyString . '</th>';
	}
	echo '</tr>' . CR;
	foreach($threeDArray as $twoDArray){
		echo '<tr>';
		foreach($keyArray as $keyString){
			echo '<td>' . $twoDArray[$keyString] . '</td>';
		}
		echo '</tr>';
	}
	echo '</table>' . CR;
}



function PrintSimpleUserTable($printUserIdArray, $lookupTable, $title){
	echo '<table>' . CR .  '<caption>' . $title . '</caption>' . CR;
	echo '<tr><th>Row</th><th>Userid</th><th>Username</th></tr>' . CR;
	$rowCount = 1;
	foreach($printUserIdArray as $printUserId){
		echo '<tr><td>' . $rowCount . '</td>';
		echo '<td>' . $printUserId . '</td>';
		echo '<td>';
		if(array_key_exists((string)$printUserId, $lookupTable)){
			echo $lookupTable[(string)$printUserId];
		}
		echo '</td></tr>' . CR;
		$rowCount++;
	}
	echo '</table>' . CR;
}


function PrintEvenSimplerUserTable($printUserIdArray, $title){
	echo '<table>' . CR .  '<caption>' . $title . '</caption>' . CR;
	echo '<tr><th>Row</th><th>Username</th></tr>' . CR;
	$rowCount = 1;
	foreach($printUserIdArray as $printUserId){
		echo '<tr><td>' . $rowCount . '</td>';
		echo '<td>' . $printUserId . '</td>';
		echo '</tr>' . CR;
		$rowCount++;
	}
	echo '</table>' . CR;
}


?>