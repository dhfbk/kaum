<?php

function hssh_listDatasets($projectID) {
	global $mysqli;

    $ret = [];
    $ret['ch'] = [];
    $ret['gr'] = [];
    $query = "SELECT * FROM hssh_datasets
        WHERE (
            project_id = '{$projectID}' OR
            (project_id IS NULL AND task_id IS NULL)
        )
        AND name IS NOT NULL
        AND deleted = '0'
        ORDER BY name";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $ret[$row['type']][$row['id']] = $row['name'];
    }

    return $ret;
}