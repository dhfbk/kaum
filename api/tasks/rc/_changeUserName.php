<?php

if (!$Username) {
    exit();
}

/*
    Constants: RC_URL
    Variables: $Username, $NewValue
*/

\ATDev\RocketChat\Chat::setUrl(RC_URL);
$result = \ATDev\RocketChat\Chat::login("admin", $rcPassword);
if (!$result) {
    $error = \ATDev\RocketChat\Chat::getError();
    dieWithError($error);
}

$user = new \ATDev\RocketChat\Users\User($Username);
$user->info();
$user->setName($NewValue);
$result = $user->update();
if (!$result) {
    $error = $user->getError();
    dieWithError($error);
}

// $ret['rc'] = "OK";
