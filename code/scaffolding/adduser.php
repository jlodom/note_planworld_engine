<?php

	/* This file exists to test Planworld functionality as it is being built. */

	$_base = dirname(__FILE__) . '/../';
	require_once($_base . '/lib/Planworld.php');
	require_once($_base . '/lib/User.php');
	require_once($_base . '/lib/NodeToken.php');
	require_once('ttpdisplay_tools.php');


	$message = '';
	if(isset($_POST['newusername'])){
		$username = $_POST['newusername'];
		if(!ctype_alnum($username)){
			$message = "Usernames for this test site must be alphanumeric";
		}
		else if(Planworld::isUser($username)){
			$message = 'User ' . $username . ' already exists.';
		}
		else{
			$newby = User::factory($username);
			$newby->create();
			$userExists = new User($username);
			$userId = $userExists->userID;
			$pass = str_replace('.', '',(str_replace('/', '', crypt($stringLoginUser, ((int)$intLoginUser + 45678)))));
			$message = 'User ' . $username . ' created with password ' . $pass;
		}
	}

	PrintHeader('fullpage.css', 'Test Planworld User Creation');
	PrintUsernameForm('adduser.php', 'Input a username to add that user to Planworld', $message);




	PrintFooter();

?>