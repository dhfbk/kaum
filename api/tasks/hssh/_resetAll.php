<?php

if (!$Reset) {
    exit();
}

foreach (["hssh_annotations", "hssh_datasets", "hssh_ds_task_cluster", "hssh_rows"] as $table) {
    $table = addslashes($table);
    $query = "TRUNCATE `{$table}`";
    $mysqli->query($query);
}
