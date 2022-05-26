<?php

if (!$TaskID) {
    exit();
}

// Save choice list
if (!empty($Info['type_info']['save_values']['save'])) {
    $Data = [
        "name" => $Info['type_info']['save_values']['name'],
        "choices" => $Info['type_info']['choices']
    ];
    creender_addChoiceList($Data);
}

unset($Info['type_info']['save_values']);

$query = queryupdate("tasks", ["data" => json_encode($Info)], ["id" => $TaskID]);
$result = $mysqli->query($query);

