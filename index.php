<?php

require_once 'GameApi.php';

try {
    $api = new GameApi();
    echo $api->run();
} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage()));
}