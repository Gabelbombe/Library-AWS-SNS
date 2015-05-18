# Simple Notification Service PHP 5.4+ Wrapper

This API wrapper is a lightweight alternative to the official [Amazon aws-sdk-for-php](http://aws.amazon.com/sdkforphp) for access to Amazon SNS (Simple Notification Service) using PHP

Find out more about Amazon SNS here - http://aws.amazon.com/sns

To use this wrapper you must be using PHP5 with cURL, and have an [Amazon AWS account](http://aws.amazon.com)

Example usage:

```php
<?php
require APP_DIR . '\Wrappers\SimpleNotificationService\SimpleNotificationService.php';

USE \Wrappers\SimpleNotificationService\SimpleNotificationService AS SNS;

// Create an instance
$sns = New SNS(AMAZON_ACCESS_KEY_ID, AMAZON_SECRET_ACCESS_KEY);

// Create a Topic
$topicArn = $sns->createTopic('My New SNS Topic');

// Set the Topic's Display Name (required)
$sns->setTopicAttributes($topicArn, 'DisplayName', 'My SNS Topic Display Name');

// Subscribe to this topic
$sns->subscribe($topicArn, 'email', 'example@github.com');

// And send a message to subscribers of this topic
$sns->publish($topicArn, 'Hello, world!');
```

## API Methods
Available methods:

* `addPermission($topicArn, $label, $permissions)`
* `confirmSubscription($topicArn, $token)`
* `createTopic($name)`
* `deleteTopic($topicArn)`
* `getTopicAttributes($topicArn)`
* `listSubscriptions()`
* `listSubscriptionsByTopic($topicArn)`
* `listTopics()`
* `publish($topicArn, $message)`
* `removePermission($topicArn, $label)`
* `setTopicAttributes($topicArn, $attrName, $attrValue)`
* `subscribe($topicArn, $protocol, $endpoint)`
* `unsubscribe($subscriptionArn)`

To set the API region (us-east-1, us-west-2, us-west-1, eu-west-1, etc):

* `setRegion($region)`

*The default API region is us-east-1*

## Further Example
Make sure to catch Exceptions where necessary:

```php
<?php
require APP_DIR . '\Wrappers\SimpleNotificationService\SimpleNotificationService.php';

USE \Wrappers\SimpleNotificationService\SimpleNotificationService AS SNS;

// Create an instance
$sns = New SNS(AMAZON_ACCESS_KEY_ID, AMAZON_SECRET_ACCESS_KEY);
$sns->setRegion('eu-west-1');

try 
{
	$topics = $sns->listTopics();
}

catch(SNSException $e) 
{
	// Amazon SNS returned an error
	echo 'SNS returned the error "' . $e->getMessage() . '" and code ' . $e->getCode();
}

catch(APIException $e) 
{
	// Problem with the API
	echo 'There was an unknown problem with the API, returned code ' . $e->getCode();
}
```
