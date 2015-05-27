<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

header('Content-type: text/plain');

define  ('APP_HOME', getenv('APP_HOME'));
require APP_HOME . '/vendor/autoload.php';

$sns = New Wrappers\SimpleNotificationService\SNS(
    getenv('KEY'), getenv('SECRET')
);

// Create a Topic
$topicArn = $sns->createTopic('Test Topic');

print_r($sns->listTopics());

// Set the Topic's Display Name (required)
//$sns->setTopicAttributes($topicArn, 'DisplayName', 'My SNS Topic Display Name');

//print_r($sns->listTopics());