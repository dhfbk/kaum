<?php

if (!$TaskID) {
    exit();
}

$clusterNo = intdiv($Info['students'], $Info['type_info']['annotations']);

$photoIDs = [];
foreach ($Info['type_info'] as $index => $value) {
    if (!preg_match('/dataset_([0-9]+)/', $index, $matches)) {
        continue;
    }
    $datasetID = $matches[1];
    $datasetAmount = $value;

    $query = "SELECT * FROM `creender_rows`
        WHERE dataset_id = '{$datasetID}' ORDER BY RAND()
        LIMIT {$datasetAmount}";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $photoIDs[] = $row;
    }
}

shuffle($photoIDs);

// Get educators' photos
// $eduPhotos = [];
// for ($i = 0; $i < $Info['type_info']['photos_educator']; $i++) {
//     $eduPhotos[] = $photoIDs[$i];
// }
// $photoIDs = array_slice($photoIDs, $Info['type_info']['photos_educator']);

$per_cluster = intdiv(count($photoIDs), $clusterNo);
$reminder = count($photoIDs) % $clusterNo;
$totals = [];
for ($i = 0; $i < $clusterNo; $i++) {
    $totals[$i] = $per_cluster + ($reminder > $i ? 1 : 0);
}

$j = 0;
foreach ($totals as $index => $value) {
    for ($i = 0; $i < $value; $i++) {
        $data = [];
        $data['cluster'] = $index + 1;
        $row = $photoIDs[$j++];
        $data['row'] = $row['id'];
        $data['task'] = $TaskID;
        $query = queryinsert("creender_ds_task_cluster", $data);
        $mysqli->query($query);
    }
}

// foreach ($eduPhotos as $photo) {
//     $data = [];
//     $data['cluster'] = 0;
//     $row = $photo;
//     $data['row'] = $row['id'];
//     $data['task'] = $TaskID;
//     $query = queryinsert("creender_ds_task_cluster", $data);
//     $mysqli->query($query);
// }

$query = "SELECT * FROM users WHERE task = '{$TaskID}' AND deleted = '0' ORDER BY id";
$result = $mysqli->query($query);
$i = 0;
while ($RowUser = $result->fetch_array(MYSQLI_ASSOC)) {
    $data = json_decode($RowUser['data'], true);
    $data['rc_cluster'] = $i + 1;
    $dataJson = addslashes(json_encode($data));
    $query = "UPDATE users SET data = '$dataJson' WHERE id = '{$RowUser['id']}'";
    $mysqli->query($query);
    $i = ($i + 1) % $clusterNo;
}


