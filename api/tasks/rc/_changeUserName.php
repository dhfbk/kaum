<?php

if (!$Username) {
    exit();
}

/*
    Variables: $Username, $NewValue
*/

if ($Field == "name") {
    $user = new \ATDev\RocketChat\Users\User($Username);
    $user->info();
    $user->setName($NewValue);
    $result = $user->update();
    if (!$result) {
        $error = $user->getError();
        dieWithError($error);
    }
}

// $ret['rc'] = "OK";
