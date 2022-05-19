<?php

define('HSSH_True', "1T!");
define('HSSH_False', "0F!");

function hssh_getTokens($content) {
    return preg_split('/\s+/', $content);
}

function hssh_listDatasets($projectID) {
    global $mysqli;

    $ret = [];
    $ret['ch'] = [];
    $ret['gr'] = [];
    $query = "SELECT d.id, d.task_id, d.name, d.type, COUNT(r.id) num
        FROM hssh_datasets d
        LEFT JOIN hssh_rows r ON r.dataset_id = d.id
        WHERE (
            project_id = '{$projectID}' OR
            (project_id IS NULL AND task_id IS NULL)
        )
        AND name IS NOT NULL
        AND deleted = '0'
        GROUP BY d.id, d.task_id, d.name
        ORDER BY name";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $name = "{$row['name']} ({$row['num']})";
        if ($row['task_id']) {
            $name = "T{$row['task_id']} - $name";
        }
        $ret[$row['type']][$row['id']] = $name;
    }

    return $ret;
}
