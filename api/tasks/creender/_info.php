<?php

if (!$Row) {
    exit();
}

$ret['clone_values'] = [
    "choices", "comment", "answer", "description",
    "photos_educator", "annotations", "do_not_ask_for_comment",
    "comment_is_mandatory", "no_show_question", "no_delay",
    "no_dblclick", "allow_multiple_choices", "enable_demo_mode",
    "demo_password"
];
$datasets = creender_listDatasets($Row['project_id']);
$ret['info']['data']['creender_datasets'] = $datasets;
foreach ($datasets as $index => $dataset) {
    $ret['clone_values'][] = "dataset_" . $index;
}
