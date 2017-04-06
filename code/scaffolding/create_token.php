<?php

	/* This file exists to test Planworld functionality as it is being built. */

	$_base = dirname(__FILE__) . '/../';
	require_once($_base . '/lib/Planworld.php');
	require_once($_base . '/lib/User.php');
	require_once($_base . '/lib/NodeToken.php');
	require_once('ttpdisplay_tools.php');


	$message = '';
	if((isset($_POST['loginusername'])) && (isset($_POST['loginpassword']))){
		$username = $_POST['loginusername'];
		$password = $_POST['loginpassword'];
		if(!ctype_alnum($username)){
			$message = "Usernames for this test site must be alphanumeric";
		}
		else{
			$userExists = new User($username);
			$userId = $userExists->userID;
			$comparepass = crypt($username, ((int)$userId + 45678));
			if(strcmp($password, $comparepass) == 0){
				$nodeToken = new NodeToken ();
				if($nodeToken->createToken($username, 'testtokencreator')){
					$message = 'User token is ' . $nodeToken->tokenNumber;
				}
			}
			else{
				$message = 'Bad username or password.';
			}
		}
	}

	PrintHeader('fullpage.css', 'Test Planworld Token Handling');
	PrintLoginForm('create_token.php', 'Enter username and password to create a Planworld token.', $message);
	PrintFooter();

?>