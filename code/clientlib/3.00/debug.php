<?php


/* DEBUG CATEGORY VERBS FOR PLANWORLD CLIENT LAYER.

  __VERY IMPORTANT__
  1. This file is only active if there is a file called debug.on in the backend directory, which is not created by default in a new installation.
  2. In production environments both this file and the debug.on file should be entirely removed.
  These API calls are HIGHLY DANGEROUS and not meant for running in a live environment.

*/




function pseudologinGet($arrayRestInputs){
  $tokenValue = '0';
  if((file_exists($arrayRestInputs['enginebasedir'] . '/lib/Planworld.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/User.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php')) && (file_exists($arrayRestInputs['enginebasedir'] . '/backend/debug.on')) ){
    require_once($arrayRestInputs['enginebasedir'] . '/lib/Planworld.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/User.php');
    require_once($arrayRestInputs['enginebasedir'] . '/lib/NodeToken.php');
    if(count($arrayRestInputs['arguments']) > 1){
      $arguments = $arrayRestInputs['arguments'];
      $stringLoginUser = $arguments[0];
      $stringPsuedoPassword = $arguments[1];
      if(Planworld::isUser($stringLoginUser)){
        $objectLoginUser = new User($stringLoginUser);
        $intLoginUser = $objectLoginUser->userID;
        $comparepass = str_replace('.', '', (str_replace('/', '', crypt($stringLoginUser, ((int)$intLoginUser + 45678)))));
        if(strcmp($stringPsuedoPassword, $comparepass) == 0){
          $nodeToken = new NodeToken ();
          if($nodeToken->createToken($stringLoginUser, 'enginedebug')){
            $tokenValue = $nodeToken->tokenNumber;
          }
        }
      }
    }
  }
  return $tokenValue;
}

?>