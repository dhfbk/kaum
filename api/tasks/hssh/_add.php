<?php

foreach ($Info['type_info']['added_datasets'] as $type => $datasetID) {
	$query = "UPDATE hssh_datasets SET task_id = '{$TaskID}' WHERE id = '$datasetID'";
	$mysqli->query($query);
	$Info['type_info']['dataset_' . $type] = $datasetID;
}
unset($Info['type_info']['added_datasets']);
unset($Info['type_info']['custom_ch']);
unset($Info['type_info']['custom_gr']);

$query = queryupdate("tasks", ["data" => json_encode($Info)], ["id" => $TaskID]);
$result = $mysqli->query($query);
