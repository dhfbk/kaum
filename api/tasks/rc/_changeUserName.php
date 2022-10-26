<?php

if (!$Username) {
    exit();
}

/*
    Variables: $Username, $NewValue
*/

if ($Field == "name") {
    if (!$NewValue) {
        $NewValue = $Username;
    }
    $user = new \ATDev\RocketChat\Users\User($Username);
    $user->info();
    if ($user->getUsername()) {
        $user->setName($NewValue);
        $result = $user->update();
        if (!$result) {
            $error = $user->getError();
            dieWithError($error);
        }
    }
}

// $ret['rc'] = "OK";
