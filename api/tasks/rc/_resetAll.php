<?php

if (!$Reset) {
    exit();
}

$listing = \ATDev\RocketChat\Groups\Group::listing();
foreach ($listing as $group) {
    $group->delete();
}
$listing = \ATDev\RocketChat\Users\User::listing();
foreach ($listing as $user) {
    foreach ($user->getRoles() as $role) {
        if ($role == "admin" || $role == "app") {
            continue 2;
        }
    }
    $user->delete();
}
