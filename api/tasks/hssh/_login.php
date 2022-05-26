<?php

if (!$RowProject) {
	exit();
}

$save_game = null;
if ($RowTask['data']['type_info']['save_game']) {
	$save_game = $RowTask['data']['type_info']['save_game'][$RowUser['id']];
}
$ret['save_game'] = $save_game;
$ret['language'] = $RowProject['data']['language'];
$ret['trueLabel'] = HSSH_True;
$ret['falseLabel'] = HSSH_False;

// $ret['project'] = $RowProject;
// $ret['task'] = $RowTask['data']['type_info']['save_game'];

