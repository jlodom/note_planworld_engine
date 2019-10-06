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


function groupcreatePost($arrayRestInputs){
  $boolReturn = false;
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (count($arrayRestInputs['arguments']) > 0)){
      $arguments = $arrayRestInputs['arguments'];
      $stringNewGroupName = $arguments[0];
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


// WOAHWOAHWOAH I MAY BE DOING POSTING WRONG HERE. CHECK WHERE THE POST DATA IS COMING FROM. THESE MIGHT BE GETS OR NOT NOT SURE

function groupmoveuserPost($arrayRestInputs){
  $boolReturn = false;
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    $objectToken = new NodeToken ();
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (count($arrayRestInputs['arguments']) > 0)){
      $arguments = $arrayRestInputs['arguments'];
      $stringUserToMove = $arguments[0];
      $stringGroupToMoveTo = $arguments[1];
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
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (count($arrayRestInputs['arguments']) > 0)){
      $arguments = $arrayRestInputs['arguments'];
      $stringUserToAdd = $arguments[0];
      if((Planworld::isUser($stringUserToAdd))){
        $thisUserUid = $objectToken->uid;
        $thisUserObject = new User($thisUserUid);
        $thisPlanwatch = new Planwatch($thisUserObject);
        $boolUserInPlanwatch = $thisPlanwatch->inPlanwatch($stringUserToAdd);
        if(!$boolUserInPlanwatch){
          $boolReturn = $thisPlanwatch->add($stringUserToAdd);
          $thisPlanwatch->save();
        }
      }
    }
  }
  return $boolReturn;
}


?>