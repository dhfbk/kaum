<?php

switch ($InputData['sub']) {
    case "listDatasets":
        $projectID = 0;
        if ($InputData['project_id']) {
            $RowProject = checkProject($InputData['project_id']);
            $projectID = $RowProject['id'];
        }
        $ret['datasets'] = creender_listDatasets($ProjectID);
        break;

    case "addDataset":
        checkAdmin();
        $Info = json_decode($_REQUEST['info'], true);
        if (!$_FILES['f'] || !count($_FILES['f']['name'])) {
            dieWithError("No files found");
        }
        if (count($_FILES['f']['name']) != 1) {
            dieWithError("Only one file can be uploaded");
        }
        validate($Info, [
            'name' => 'required|min:' . $Options['creender_dataset_name_minlength'],
        ]);
        if ($_FILES['f']['type'][0] != "application/zip") {
            dieWithError("Uploaded file is not a zip archive");
        }

        $zip = new ZipArchive;
        $res = $zip->open($_FILES['f']['tmp_name'][0], ZipArchive::RDONLY);
        if ($res !== true) {
            dieWithError("Unable to open file");
        }
        $fileList = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $parts = pathinfo($filename);
            $ext = strtolower($parts['extension']);
            if (!in_array($ext, $creenderAllowedExtensions)) {
                continue;
            }
            $fileList[] = ['name' => $filename, 'basename' => $parts['basename']];
        }
        if (!count($fileList)) {
            dieWithError("Unable to find images in ZIP file");
        }

        $query = queryinsert("creender_datasets", ["name" => $Info['name']]);
        $result = $mysqli->query($query);
        if (!$result) {
            dieWithError($mysqli->error);
        }
        $DatasetID = $mysqli->insert_id;

        $baseFolder = $Options['creender_images_path'];
        $thisFolder = $baseFolder . "/" . $DatasetID;
        mkdir($thisFolder);
        $ret['parts'] = [];

        foreach ($fileList as $filePath) {
            $content = json_encode($filePath);
            $query = queryinsert("creender_rows", ["dataset_id" => $DatasetID, "content" => $content]);
            $result = $mysqli->query($query);
            $FileID = $mysqli->insert_id;

            $longID = str_pad($FileID, 4, "0", STR_PAD_LEFT);
            $partInfo = creender_getPartsInfo($longID);
            $ret['parts'][$FileID] = $partInfo;

            $firstFileFolder = $thisFolder . "/" . $partInfo['f'];

            if (!file_exists($firstFileFolder)) {
                mkdir($firstFileFolder);
            }

            $thisFileFolder = $firstFileFolder . "/" . $partInfo['s'];
            $content = $zip->getFromName($filePath['name']);
            file_put_contents($thisFileFolder, $content);
        }

        $ret['files'] = $fileList;
        $ret['folder'] = $baseFolder;

        touch($baseFolder . "/prova");

        // $content = $zip->getFromName($filename);

        $ret['req'] = $_REQUEST;
        break;
}
