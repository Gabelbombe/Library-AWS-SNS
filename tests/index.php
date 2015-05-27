<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

header('Content-type: text/plain');

define  ('APP_HOME', getenv('APP_HOME'));
require APP_HOME . '/vendor/autoload.php';

$sns = New Wrappers\SimpleNotificationService\SNS(
    getenv('KEY'), getenv('SECRET')
);



$sms   = '12064209564';
$topic = 'SNSTest';

if (filter_input(INPUT_GET, 'type', FILTER_VALIDATE_REGEXP, [
    'options' => [
        'regexp' => '/^[a-zA-Z]+$/'
    ]
]))
{
    switch (strtolower($_GET['type']))
    {
        case ('subscribe'):

            $arn = false;

            foreach($sns->listTopics() AS $topics)
            {
                if ($topic == preg_replace('/^.*:\s*/', '', $topics['TopicArn']))
                {
                    echo "Removing duplicate ARN: {$topics['TopicArn']}\n\n";

                    $sns->deleteTopic($topics['TopicArn']);
                    break;
                }
            }

            $arn = $sns->createTopic($topic);

            $sns->setTopicAttributes($arn, 'DisplayName', '....')
                ->subscribe($arn, 'sms', $sms);

            echo "Subscription request sent to: {$sms}\n";

        break;


        case ('publish'):

            if ($message = filter_input(INPUT_GET, 'msg', FILTER_SANITIZE_STRING))
            {
                if (256 <= mb_strlen($message, 'utf8')) Throw New \Exception ('Input message can be no larger than 256 bytes');

                foreach($sns->listTopics() AS $topics)
                {
                    if ($topic == preg_replace('/^.*:\s*/', '', $topics['TopicArn']))
                    {
                        echo "Sending message: {$message}";

                        echo "To Topic ARN: {$topics['TopicArn']}\n";
                        echo "Confirmation: " . $sns->publish($topics['TopicArn'], $message);
                        break;
                    }
                }
            }

        break;


        case ('list'):
            echo "\nTopics are: \n\n" . print_r($sns->listTopics(), 1);
            echo "\n";
            echo "\nSubscriptions are: \n\n" . print_r($sns->listSubscriptions(), 1);
            break;

    }
}