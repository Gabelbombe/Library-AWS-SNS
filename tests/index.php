<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

header('Content-type: text/plain');

define  ('APP_HOME', getenv('APP_HOME'));
require APP_HOME . '/vendor/autoload.php';

$sns = New Wrappers\SimpleNotificationService\SNS(
    getenv('KEY'), getenv('SECRET')
);

print_r($sns);