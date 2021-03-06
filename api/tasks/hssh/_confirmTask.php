<?php

if (!$TaskID) {
    exit();
}

$clusterNo = intdiv($Info['students'], $Info['type_info']['annotations']);

foreach (['ch', 'gr'] as $type) {
    $datasetID = $Info['type_info']['dataset_' . $type];
    $query = "SELECT * FROM hssh_rows WHERE dataset_id = '$datasetID' ORDER BY id";
    $result = $mysqli->query($query);
    $per_cluster = intdiv($result->num_rows, $clusterNo);
    $reminder = $result->num_rows % $clusterNo;
    $totals = [];
    for ($i = 0; $i < $clusterNo; $i++) {
        $totals[$i] = $per_cluster + ($reminder > $i ? 1 : 0);
    }
    foreach ($totals as $index => $value) {
        for ($i = 0; $i < $value; $i++) {
            $data = [];
            $data['cluster'] = $index + 1;
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $data['row'] = $row['id'];
            $data['task'] = $TaskID;
            $query = queryinsert("hssh_ds_task_cluster", $data);
            $mysqli->query($query);
        }
    }
}

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
// $ret['clusterNo'] = $clusterNo;

