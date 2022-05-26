<?php

if (!$RowTask) {
	exit();
}

$ret['task_info'] = $RowTask['data']['type_info'];
unset($ret['task_info']['photos_educator']);
unset($ret['task_info']['annotations']);
foreach ($ret['task_info'] as $index => $value) {
    if (!preg_match('/dataset_([0-9]+)/', $index, $matches)) {
        continue;
    }
    unset($ret['task_info']['dataset_' . $matches[1]]);
}

