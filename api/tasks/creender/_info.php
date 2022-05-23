<?php

if (!$Row) {
    exit();
}

$ret['clone_values'] = ["choices", "comment", "answer", "description", "photos_educator", "annotations"];
$datasets = creender_listDatasets($Row['project_id']);
foreach ($datasets as $index => $dataset) {
    $ret['clone_values'][] = "dataset_" . $index;
}
