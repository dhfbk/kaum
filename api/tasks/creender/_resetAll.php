<?php

if (!$Reset) {
    exit();
}

// $query = "DELETE FROM creender_datasets WHERE project_id IS NOT NULL OR task_id IS NOT NULL";
// $mysqli->query($query);
// $query = "DELETE FROM creender_rows WHERE dataset_id != ALL(SELECT id FROM creender_datasets)";
// $mysqli->query($query);

foreach (["creender_datasets", "creender_rows", "creender_annotations", "creender_choices", "creender_ds_task_cluster"] as $table) {
    $table = addslashes($table);
    $query = "TRUNCATE `{$table}`";
    $mysqli->query($query);
}
