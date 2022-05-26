<?php

if (!$TaskID) {
    exit();
}

foreach ($Info['type_info']['added_datasets'] as $type => $datasetID) {
    $query = "UPDATE hssh_datasets SET task_id = '{$TaskID}' WHERE id = '$datasetID'";
    $mysqli->query($query);
    $Info['type_info']['dataset_' . $type] = $datasetID;
    if ($Info['type_info']['save_' . $type]) {
        $query = "UPDATE hssh_datasets SET project_id = '{$ProjectID}' WHERE id = '$datasetID'";
        $mysqli->query($query);
    }
}

unset($Info['type_info']['added_datasets']);

$query = queryupdate("tasks", ["data" => json_encode($Info)], ["id" => $TaskID]);
$result = $mysqli->query($query);

