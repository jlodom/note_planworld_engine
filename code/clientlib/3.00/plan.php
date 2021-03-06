
<?php


/* SEND CATEGORY VERBS FOR PLANWORLD CLIENT LAYER. */

function planGet($arrayRestInputs){
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/User.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/User.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/Planwatch.php');
    $objectToken = new NodeToken ();
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (count($arrayRestInputs['arguments']) > 0)){
      $thisUserUid = $objectToken->uid;
      $thisUserObject = new User($thisUserUid);
      $arguments = $arrayRestInputs['arguments'];
      $userPlanToGet = $arguments[0];
      if(Planworld::isUser($userPlanToGet)){
        $userPlanToGetObject = new User($userPlanToGet);
        if(!$thisUserObject->doesBlockRelationshipExist($userPlanToGetObject->getUserID())){
         /* Make sure the user's Planwatch gets updated. This may be a costly call. */
          $thisUserObject->loadPlanwatch();
		  $thisUserObject->planwatch->markSeen($userPlanToGetObject);
          $thisUserObject->save();
          return $userPlanToGetObject->getPlanSimple($thisUserObject);
        }
        else{
          return 'Either you or this user is blocked by the other.';
        }
      }
      else{
        return '';
      }
    }
    else{
      return '';
    }
  }
  else{
    return '';
  }
}


function planPost($arrayRestInputs){
  $boolSuccess = false;
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/User.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/backend/HTMLPurifier.standalone.php')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/User.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    require_once($arrayRestInputs['enginebasedir'] . '/backend/HTMLPurifier.standalone.php');
    $objectToken = new NodeToken ();
    if(($objectToken->retrieveToken($arrayRestInputs['token'])) && (!(empty($arrayRestInputs['post'])))){
      $thisUserUid = $objectToken->uid;
      $thisUserObject = new User($thisUserUid);
      $arguments = $arrayRestInputs['arguments'];
      $rawPlanNew = $arrayRestInputs['post'];
      $hpConfig = HTMLPurifier_Config::createDefault();
      $hp = new HTMLPurifier($hpConfig);
      $finalPlanNew = $hp->purify($rawPlanNew);
      $boolSuccess = $thisUserObject->setPlanSimple($finalPlanNew);
    }
  }
  return $boolSuccess;
}


?>