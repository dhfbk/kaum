<?php

if (!$RowProject) {
    exit();
}

validate($Info['type_info'], [
    'annotations' => 'required|min:1|max:' . $Info['students'],
]);

$datasetsToUse = [
    "ch" => [],
    "gr" => []
];
// $Info['type_info']['datasets']
$datasets = hssh_listDatasets($RowProject['id']);
$allDatasets = [];
foreach ($datasets as $datasetList) {
    foreach ($datasetList as $dataset) {
        $allDatasets[$dataset['id']] = $dataset;
    }
}
foreach ($Info['type_info']['datasets'] as $index => $value) {
    if (!$value) {
        continue;
    }
    if (!isset($allDatasets[$index])) {
        continue;
    }
    $datasetsToUse[$allDatasets[$index]['type']][] = $index;
}
if (count($datasetsToUse['ch']) == 0 || count($datasetsToUse['gr']) == 0) {
    dieWithError("Select at least one set of sentences for each type (graffiti, dialogues)");
}
$Info['type_info']['datasets_ok'] = $datasetsToUse;

// $ret['rows'] = [];

// if (!$CheckOnly) {
//     $Info['type_info']['added_datasets'] = [];
//     $datasets = hssh_listDatasets($RowProject['id']);
//     foreach (['ch', 'gr'] as $type) {

//         // File uploaded
//         if ($Info['type_info']['custom_' . $type]) {
//             if (!$_FILES[$type] || $_FILES[$type]['error'][0] != 0) {
//                 print_r($_FILES);
//                 dieWithError("Error in uploading {$type} dataset");
//             }
//             $filename = $_FILES[$type]['tmp_name'][0];
//             $handle = fopen($filename, "r");
//             if (!$handle) {
//                 dieWithError("Error in parsing uploaded {$type} dataset");
//             }
//             $data = [
//                 "type" => $type,
//                 "name" => $_FILES[$type]['name'][0]
//             ];
//             $query = queryinsert("hssh_datasets", $data);
//             $result = $mysqli->query($query);
//             if (!$result) {
//                 dieWithError($mysqli->error);
//             }

//             $datasetID = $mysqli->insert_id;
//             $Info['type_info']['added_datasets'][$type] = $datasetID;

//             while (($line = fgets($handle)) !== false) {
//                 $line = trim($line);
//                 if (!$line) {
//                     continue;
//                 }
//                 $parts = explode("\t", $line);
//                 $parts = array_map("trim", $parts);
//                 $data = [
//                     "dataset_id" => $datasetID,
//                     "content" => $parts[0],
//                     "goldLabel" => 0,
//                     "goldTokens" => ""
//                 ];
//                 if (count($parts) > 1) {
//                     $data["goldLabel"] = $parts[1];
//                     if (count($parts) > 2) {
//                         $data["goldTokens"] = $parts[2];
//                     }
//                 }
//                 $ret['rows'][] = $data;
//                 $query = queryinsert("hssh_rows", $data);
//                 $result = $mysqli->query($query);
//             }

//             fclose($handle);
//         }

//         // Existing dataset
//         else {
//             if (!isset($datasets[$type][$Info['type_info']['dataset_' . $type]])) {
//                 dieWithError("Error in {$type} dataset");
//             }
//         }
//     }
// }

// unset($Info['type_info']['custom_ch']);
// unset($Info['type_info']['custom_gr']);
