<?php

$creenderAllowedExtensions = ["jpeg", "jpg", "png"];

function creender_getPartsInfo($longID) {
    $first_part = substr($longID, 0, strlen($longID) - 3);
    $second_part = substr($longID, -3);
    return ["f" => $first_part, "s" => $second_part];
}

function creender_listDatasets($projectID = 0, $userID = 0) {
	global $mysqli;

    $ret = [];
    $orUser = "";
    if ($userID) {
        $orUser = " OR user_id = '{$userID}' ";
    }
    else {
        $orUser = " OR user_id = '{$_SESSION['Login']}' ";
    }
    $query = "SELECT d.id, d.project_id, d.task_id, d.name, COUNT(r.id) num
        FROM creender_datasets d
        LEFT JOIN creender_rows r ON r.dataset_id = d.id
        WHERE (
            project_id = '{$projectID}' OR
            (project_id IS NULL AND task_id IS NULL AND user_id IS NULL)
            {$orUser}
        )
        AND name IS NOT NULL
        AND deleted = '0'
        GROUP BY d.id, d.task_id, d.name
        ORDER BY name";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        // $name = "{$row['name']} ({$row['num']})";
        // if ($row['task_id']) {
        //     $name = "T{$row['task_id']} - $name";
        // }
        $ret[$row['id']] = $row;
    }

    return $ret;
}
