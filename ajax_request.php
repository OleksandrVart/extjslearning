<?php

function getParam($param) {
    return isset($_REQUEST[$param]) ? $_REQUEST[$param] : null;
}
$cmd = getParam('cmd');

switch ($cmd) {
    case 'task_2':
        $add = getParam('add');
        if ($add = getParam('add')) {
            $add = ' ('.$add.')';
        }
        $data = [
            0 => ['id' => 1, 'name' => 'Load name 1'.$add],
            1 => ['id' => 2, 'name' => 'Load name 2'.$add],
            2 => ['id' => 3, 'name' => 'Load name 3'.$add],
            3 => ['id' => 4, 'name' => 'Load name 4'.$add],
        ];
        break;
    case 'task_3':
        break;
}

echo json_encode($data);