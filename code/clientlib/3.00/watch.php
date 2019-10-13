<?php


/* WATCHLIST CATEGORY VERBS FOR PLANWORLD CLIENT LAYER. */



function watchlistGet($arrayRestInputs){
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if($objectToken->retrieveToken($arrayRestInputs['token'])){
      $thisUserUid = $objectToken->uid;
      $thisUserObject = new User($thisUserUid);
      $thisPlanwatch = new Planwatch($thisUserObject);
      return $thisPlanwatch->getInteroperableWatchlist();
    }
    else{
      return '';
    }
  }
  else{
    return '';
  }
}


function watchlistgroupGet($arrayRestInputs){
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if($objectToken->retrieveToken($arrayRestInputs['token'])){
      $thisUserUid = $objectToken->uid;
      $thisUserObject = new User($thisUserUid);
      $thisPlanwatch = new Planwatch($thisUserObject);
      return $thisPlanwatch->getInteroperableWatchlistGroups();
    }
    else{
      return '';
    }
  }
  else{
    return '';
  }
}


function addgroupPost($arrayRestInputs){
  $boolReturn = false;
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (!(empty($arrayRestInputs['post'])))){
      $stringNewGroupName = $arrayRestInputs['post'];
      if(Planworld::isValidWatchlistGroupName($stringNewGroupName)){
        $thisUserUid = $objectToken->uid;
        $thisUserObject = new User($thisUserUid);
        $thisPlanwatch = new Planwatch($thisUserObject);
        $boolNameDoesNotAlreadyExist = true;
        $arrayOfCurrentGroupNames = $thisPlanwatch->getGroupNames();
        foreach($arrayOfCurrentGroupNames as $currentGroupName){
          if(strcasecmp($currentGroupName, $stringNewGroupName) == 0){
            $boolNameDoesNotAlreadyExist = false;
          }
        }
        if($boolNameDoesNotAlreadyExists){
          $boolReturn = $thisPlanwatch->addGroup($stringNewGroupName);
          $thisPlanwatch->save();
        }
      }
    }
  }
  return $boolReturn;
}



function moveusergroupPost($arrayRestInputs){
  $boolReturn = false;
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (count($arrayRestInputs['arguments']) > 0) && (!(empty($arrayRestInputs['post'])))){
      $arguments = $arrayRestInputs['arguments'];
      $stringUserToMove = $arguments[0];
      $stringGroupToMoveTo = $arrayRestInputs['post'];
      if((Planworld::isValidWatchlistGroupName($stringGroupToMoveTo)) && (Planworld::isUser($stringUserToMove))){
        $thisUserUid = $objectToken->uid;
        $thisUserObject = new User($thisUserUid);
        $thisPlanwatch = new Planwatch($thisUserObject);
        $boolGroupExists = false;
        $boolUserInPlanwatch = $thisPlanwatch->inPlanwatch($stringUserToMove);
        $matrixGroups = $thisPlanwatch->getGroups();
        $intGidForGroupMove = -1;
        foreach($matrixGroups as $nameGroup => $rowGroup){
          if(strcasecmp($nameGroup, $stringGroupToMoveTo) == 0){
            $boolGroupExists = true;
            $intGidForGroupMove = $rowGroup['gid'];
          }
        }
        if($boolGroupExists && $boolUserInPlanwatch){
          $boolReturn = $thisPlanwatch->move($stringUserToMove, $intGidForGroupMove);
          $thisPlanwatch->save();
        }
      }
    }
  }
  return $boolReturn;
}


function addPost($arrayRestInputs){
  $boolReturn = false;
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (!(empty($arrayRestInputs['post'])))){
      $stringUserToAdd = $arrayRestInputs['post'];;
      if((Planworld::isUser($stringUserToAdd))){
        $thisUserUid = $objectToken->uid;
        $thisUserObject = new User($thisUserUid);
        $thisPlanwatch = new Planwatch($thisUserObject);
        $boolUserInPlanwatch = $thisPlanwatch->inPlanwatch($stringUserToAdd);
        $boolBlockedRelationshipExists = $thisUserObject->doesBlockRelationshipExist($stringUserToAdd);
        if((!$boolUserInPlanwatch) && (!$boolBlockedRelationshipExists) ){
          $boolReturn = $thisPlanwatch->add($stringUserToAdd);
        }
      }
    }
  }
  return $boolReturn;
}

function removePost($arrayRestInputs){
  $boolReturn = false;
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (!(empty($arrayRestInputs['post'])))){
      $stringUserToRemove = $arrayRestInputs['post'];;
      if((Planworld::isUser($stringUserToRemove))){
        $thisUserUid = $objectToken->uid;
        $thisUserObject = new User($thisUserUid);
        $thisPlanwatch = new Planwatch($thisUserObject);
        $boolUserInPlanwatch = $thisPlanwatch->inPlanwatch($stringUserToRemove);
        if($boolUserInPlanwatch){
          $boolReturn = $thisPlanwatch->remove($stringUserToRemove);
        }
      }
    }
  }
  return $boolReturn;
}


?>