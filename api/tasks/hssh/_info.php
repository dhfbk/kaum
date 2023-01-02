<?php

if (!$Row) {
    exit();
}

$ret['clone_values'] = ['annotations', 'dataset_ch', 'dataset_gr', 'datasets'];
$datasets = hssh_listDatasets($Row['project_id']);
$ret['info']['data']['hssh_datasets'] = $datasets;
