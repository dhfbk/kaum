<?php

if (!$RowUser) {
    exit();
}

$ret['log'] = [];

$user = new \ATDev\RocketChat\Users\User($RowUser['username']);
$user->info();
$user->setPassword($clearPassword);
$result = $user->update();
if (!$result) {
    $error = $user->getError();
    dieWithError($error);
}
