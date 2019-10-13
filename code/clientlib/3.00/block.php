<?php

function blocklistGet($arrayRestInputs){
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/User.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/User.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if($objectToken->retrieveToken($arrayRestInputs['token'])){
      $thisUserUid = $objectToken->uid;
      $thisUserObject = new User($thisUserUid);
      return $thisUserObject->getBlockListNames();
    }
    else{
      return array();
    }
  }
  else{
    return array();
  }
}


function blockedbyGet($arrayRestInputs){
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/User.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/User.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if($objectToken->retrieveToken($arrayRestInputs['token'])){
      $thisUserUid = $objectToken->uid;
      $thisUserObject = new User($thisUserUid);
      return $thisUserObject->getBlockedByNames();
    }
    else{
      return array();
    }
  }
  else{
    return array();
  }
}



function addPost($arrayRestInputs){
  $boolReturn = false;
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/User.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/User.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (!(empty($arrayRestInputs['post'])))){
      $stringUserToAdd = $arrayRestInputs['post'];;
      if((Planworld::isUser($stringUserToAdd))){
        $thisUserUid = $objectToken->uid;
        $thisUserObject = new User($thisUserUid);
        $objectUserToAdd = new User($stringUserToAdd);
        $boolUserInBlockList = $thisUserObject->isUserIdInBlocklist($objectUserToAdd->userID);
        if(!$boolUserInPlanwatch){
          $boolReturn = $thisPlanwatch->add($stringUserToAdd);
        }
      }
    }
  }
  return $boolReturn;
}


function removePost($arrayRestInputs){
  $boolReturn = false;
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/User.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/User.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (!(empty($arrayRestInputs['post'])))){
      $stringUserToRemove = $arrayRestInputs['post'];;
      if((Planworld::isUser($stringUserToAdd))){
        $thisUserUid = $objectToken->uid;
        $thisUserObject = new User($thisUserUid);
        $objectUserToRemove = new User($stringUserToRemove);
        $boolUserInBlockList = $thisUserObject->isUserIdInBlocklist($objectUserToRemove->userID);
        if(!$boolUserInPlanwatch){
          $boolReturn = $thisPlanwatch->remove($stringUserToRemove);
        }
      }
    }
  }
  return $boolReturn;
}


?>