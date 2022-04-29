<?php

if (!$RowProject) {
	exit();
}

$save_game = null;
if ($RowTask['data']['type_info']['save_game']) {
	$save_game = $RowTask['data']['type_info']['save_game'][$RowUser['id']];
}
$ret['save_game'] = $save_game;
$ret['language'] = "it";
$ret['true'] = HSSH_True;
$ret['false'] = HSSH_False;

// $ret['project'] = $RowProject;
// $ret['task'] = $RowTask['data']['type_info']['save_game'];

