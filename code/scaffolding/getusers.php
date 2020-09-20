<?php

/* This file exists to test Planworld functionality as it is being built. */

$_base = dirname(__FILE__) . '/../';
require_once($_base . '/lib/Planworld.php');
require_once($_base . '/lib/User.php');
require_once('ttpdisplay_tools.php');


$message = '';
$arrayUsers = array();
if((isset($_POST['loginusername'])) && (isset($_POST['loginpassword']))){
	$username = $_POST['loginusername'];
	$password = $_POST['loginpassword'];
	if(!ctype_alnum($username)){
		$message = "Usernames for this test site must be alphanumeric";
	}
	else{
		$userExists = new User($username);
		$userId = $userExists->userID;
		$comparepass = str_replace('.', '',(str_replace('/', '', crypt($username, ((int)$userId + 45678)))));
		if(strcmp($password, $comparepass) == 0){
			$pw = new Planworld();
			$arrayUsers = $pw->getAllUsers();
		}
		else{
			$message = 'Bad username or password.';
		}
	}
}

PrintHeader('fullpage.css', 'Test Planworld User Listing');
PrintLoginForm('getusers.php', 'Enter username and password to print a list of all users on this system.', $message);

/* Print the user list below the login form. */
if((is_array($arrayUsers)) && !(empty($arrayUsers))){
	PrintEvenSimplerUserTable($arrayUsers, 'Usernames On This System');
}

PrintFooter();

?>