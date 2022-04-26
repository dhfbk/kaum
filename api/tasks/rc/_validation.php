<?php

validate($Info['type_info'], [
    'channel_name' => 'required|min:3',
    'description' => 'required'
]);

if (!preg_match('/^[a-z0-9-]+$/', $Info['type_info']['channel_name'])) {
    dieWithError("The channel name must include only lowercase letters, numbers and dashes");
}

if (strpos($Info['type_info']['channel_name'], "--") !== false) {
    dieWithError("The channel name cannot contain two consecutive dashes");
}

if ($Info['type_info']['channel_name'][0] == "-") {
    dieWithError("The channel name cannot begin with a dash");
}

if ($Info['type_info']['channel_name'][strlen($Info['type_info']['channel_name']) - 1] == "-") {
    dieWithError("The channel name cannot end with a dash");
}
