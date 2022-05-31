<?php

if (!$RowProject) {
    exit();
}

// No files in Creender
unset($Info['type_info']['files']);

$Datasets = creender_listDatasets($RowProject['id']);
$validation_rules = [];
$sum = 0;
foreach ($Datasets as $dataset) {
    $validation_rules["dataset_" . $dataset['id']] = 'integer|max:' . $dataset['num'];
    if (!empty($Info['type_info']['dataset_' . $dataset['id']])) {
        $sum += $Info['type_info']['dataset_' . $dataset['id']];
    }
}
$validation_rules['annotations'] = "required|min:1|max:" . $Info['students'];
// $validation_rules['photos_educator'] = "required|min:0";
$validation_rules['comment'] = "required";
$validation_rules['answer'] = "required";
$validation_rules['description'] = "required";

array_walk_recursive($Info['type_info'], function(&$v) {
    if (is_string($v)) {
        $v = trim($v);
    }
});

// $Info['type_info'] = array_map("trim", $Info['type_info']);
$validation_rules['choices'] = "required|min:2";
// TODO: add choicelist name if present
// $validation_rules['name'] = "required|min:" . $Options['creender_choicelist_name_minlength'];

if ($sum == 0) {
    dieWithError("You must select some photos");
}

if ($sum < $validation_rules['photos_educator']) {
    dieWithError("The number of photos must be at least {$Info['type_info']['photos_educator']} (photos in educator profile)");
}

validate($Info['type_info'], $validation_rules);

// dieWithError("All OK", 400, ["info" => $Info]);


