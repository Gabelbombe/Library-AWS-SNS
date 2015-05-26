<?php
Namespace Wrappers\SimpleNotificationService
{
    /**
     * Lightweight API interface with the Amazon Simple Notification Service
     * @link http://aws.amazon.com/sns/
     * @link http://docs.amazonwebservices.com/sns/latest/api/
     *
     * @copyright 2015 Jd Daniel
     * SimpleNotificationService PHP class
     *
     * @package SimpleNotificationService
     * @link https://github.com/ehime/Library-AWS-SNS
     * @version 0.1
     */

    Class SimpleNotificationService
    {
        private $accessKey      = null,
                $secretKey      = null;

        private $protocol       = 'https://',   // http is allowed
                $endpoint       = '';           // Defaults to us-east-1

        private $endpoints = [
            'us-east-1'         => 'sns.us-east-1.amazonaws.com',
            'us-west-2'         => 'sns.us-west-2.amazonaws.com',
            'us-west-1'         => 'sns.us-west-1.amazonaws.com',
            'eu-west-1'         => 'sns.eu-west-1.amazonaws.com',
            'eu-central-1'      => 'ec2.eu-central-1.amazonaws.com',
            'ap-southeast-1'    => 'sns.ap-southeast-1.amazonaws.com',
            'ap-southeast-2'    => 'sns.ap-southeast-2.amazonaws.com',
            'ap-northeast-1'    => 'sns.ap-northeast-1.amazonaws.com',
            'sa-east-1'         => 'sns.sa-east-1.amazonaws.com',
        ];

        /**
         * Instantiate the object - set accessKey and secretKey and set default region
         *
         * @param string $accessKey
         * @param string $secretKey
         * @param string $region [optional]
         * @throws \InvalidArgumentException
         */
        public function __construct($accessKey, $secretKey, $region = 'us-east-1')
        {
            $this->accessKey = $accessKey;
            $this->secretKey = $secretKey;

            if (empty($this->accessKey) || empty($this->secretKey))

                Throw New \InvalidArgumentException('Must define Amazon access key and secret key');

            $this->setRegion($region);
        }

        /**
         * Set the SNS endpoint/region
         *
         * @link http://docs.amazonwebservices.com/general/latest/gr/index.html?rande.html
         * @param string $region
         * @return string
         * @throws \InvalidArgumentException
         */
        public function setRegion($region)
        {
            if (! isset($this->endpoints[$region]))

                Throw New \InvalidArgumentException('Region unrecognised');

            return $this->endpoint = $this->endpoints[$region];
        }


        //
        // Public interface methods
        //

        /**
         * Add permissions to a topic
         *
         * Example:
         *    $AmazonSNS->addPermission(
         *      'topic:arn:123',
         *      'New Permission', [
         *          '987654321000' => 'Publish',
         *          '876543210000' => [
         *              'Publish',
         *              'SetTopicAttributes'
         *          ]
         *      ]);
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_AddPermission.html
         * @param string $topicArn
         * @param string $label Unique name of permissions
         * @param array $permissions [optional] Array of permissions - member ID AS keys, actions AS values
         * @return bool
         * @throws \InvalidArgumentException
         */
        public function addPermission($topicArn, $label, array $permissions = [])
        {
            if (empty($topicArn) || empty($label)) 

                Throw New \InvalidArgumentException('Must supply TopicARN and a Label for this permission');

            // Add standard params AS normal
            $params = [
                'TopicArn'  => $topicArn,
                'Label'     => $label
            ];

            // Compile permissions into separate sequential arrays
            $memberFlatArray     = [];
            $permissionFlatArray = [];

            foreach ($permissions AS $member => $permission)
            {
                if (is_array($permission))
                {
                    // Array of permissions
                    foreach ($permission AS $singlePermission)
                    {
                        $memberFlatArray[]      = $member;
                        $permissionFlatArray[]  = $singlePermission;
                    }
                }

                else
                {
                    // Just a single permission
                    $memberFlatArray[]      = $member;
                    $permissionFlatArray[]  = $permission;
                }
            }

            // Dummy check
            if (count($memberFlatArray) !== count($permissionFlatArray))
            {
                // Something went wrong
                Throw New \InvalidArgumentException('Mismatch of permissions to users');
            }

            // Finally add to params
            for ($x = 1; $x <= count($memberFlatArray); $x++)
            {
                $params['ActionName.member.' . $x]   = $permissionFlatArray[$x];
                $params['AWSAccountID.member.' . $x] = $memberFlatArray[$x];
            }

            // Finally send request
            $this->request('AddPermission', $params);

            return true;
        }

        /**
         * Confirm a subscription to a topic
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_ConfirmSubscription.html
         * @param string $topicArn
         * @param string $token
         * @param bool|null $authenticateOnUnsubscribe [optional]
         * @return string - SubscriptionARN
         * @throws \InvalidArgumentException
         */
        public function confirmSubscription($topicArn, $token, $authenticateOnUnsubscribe = null)
        {
            if (empty($topicArn) || empty($token)) 

                Throw New \InvalidArgumentException('Must supply a TopicARN and a Token to confirm subscription');

            $params = [
                'TopicArn'  => $topicArn,
                'Token'     => $token
            ];

            if (! is_null($authenticateOnUnsubscribe))
            {
                $params['AuthenticateOnUnsubscribe'] = $authenticateOnUnsubscribe;
            }

            $resultXml = $this->request('ConfirmSubscription', $params);

            return strval($resultXml->ConfirmSubscriptionResult->SubscriptionArn);
        }

        /**
         * Create an SNS topic
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_CreateTopic.html
         * @param string $name
         * @return string - TopicARN
         * @throws \InvalidArgumentException
         */
        public function createTopic($name)
        {
            if (empty($name))

                Throw New \InvalidArgumentException('Must supply a Name to create topic');

            $resultXml = $this->request('CreateTopic', [
                'Name' => $name
            ]);

            return strval($resultXml->CreateTopicResult->TopicArn);
        }

        /**
         * Delete an SNS topic
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_DeleteTopic.html
         * @param string $topicArn
         * @return bool
         * @throws \InvalidArgumentException
         */
        public function deleteTopic($topicArn)
        {
            if (empty($topicArn))

                Throw New \InvalidArgumentException('Must supply a TopicARN to delete a topic');

            $this->request('DeleteTopic', [
                'TopicArn' => $topicArn
            ]);

            return true;
        }

        /**
         * Get the attributes of a topic like owner, ACL, display name
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_GetTopicAttributes.html
         * @param string $topicArn
         * @return array
         * @throws \InvalidArgumentException
         */
        public function getTopicAttributes($topicArn)
        {
            if (empty($topicArn))

                Throw New \InvalidArgumentException('Must supply a TopicARN to get topic attributes');

            $resultXml = $this->request('GetTopicAttributes', [
                'TopicArn' => $topicArn
            ]);

            // Get attributes
            $attributes = $resultXml->GetTopicAttributesResult->Attributes->entry;

            // Unfortunately cannot use processXmlToArray here, so process manually
            $returnArray = [];

            // Process into array
            foreach ($attributes AS $attribute)
            {
                // Store attribute key AS array key
                $returnArray[strval($attribute->key)] = strval($attribute->value);
            }

            return $returnArray;
        }

        /**
         * List subscriptions that user is subscribed to
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_ListSubscriptions.html
         * @param string|null $nextToken [optional] Token to retrieve next page of results
         * @return array
         */
        public function listSubscriptions($nextToken = null)
        {
            $params = [];

            if (! is_null($nextToken)) $params['NextToken'] = $nextToken;

            $resultXml = $this->request('ListSubscriptions', $params);

            // Get subscriptions
            $subs = $resultXml->ListSubscriptionsResult->Subscriptions->member;

            return $this->processXmlToArray($subs);
        }

        /**
         * List subscribers to a topic
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_ListSubscriptionsByTopic.html
         * @param string $topicArn
         * @param string|null $nextToken [optional] Token to retrieve next page of results
         * @return array
         * @throws \InvalidArgumentException
         */
        public function listSubscriptionsByTopic($topicArn, $nextToken = null)
        {
            if (empty($topicArn))

                Throw New \InvalidArgumentException('Must supply a TopicARN to show subscriptions to a topic');

            $params = [
                'TopicArn' => $topicArn
            ];

            if (! is_null($nextToken)) {
                $params['NextToken'] = $nextToken;
            }

            $resultXml = $this->request('ListSubscriptionsByTopic', $params);

            // Get subscriptions
            $subs = $resultXml->ListSubscriptionsByTopicResult->Subscriptions->member;

            return $this->processXmlToArray($subs);
        }

        /**
         * List SNS topics
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_ListTopics.html
         * @param string|null $nextToken [optional] Token to retrieve next page of results
         * @return array
         */
        public function listTopics($nextToken = null)
        {
            $params = [];

            if (! is_null($nextToken))
            {
                $params['NextToken'] = $nextToken;
            }

            $resultXml = $this->request('ListTopics', $params);

            // Get Topics
            $topics = $resultXml->ListTopicsResult->Topics->member;

            return $this->processXmlToArray($topics);
        }

        /**
         * Publish a message to a topic
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_Publish.html
         * @param string $topicArn
         * @param string $message
         * @param string $subject [optional] Used when sending emails
         * @return string
         * @throws \InvalidArgumentException
         */
        public function publish($topicArn, $message, $subject = '')
        {
            if (empty($topicArn) || empty($message))

                Throw New \InvalidArgumentException('Must supply a TopicARN and Message to publish to a topic');

            $params = [
                'TopicArn'  => $topicArn,
                'Message'   => $message,
            ];

            if (! empty($subject))
            {
                $params['Subject'] = $subject;
            }

            $resultXml = $this->request('Publish', $params);

            return strval($resultXml->PublishResult->MessageId);
        }

        /**
         * Remove a set of permissions indentified by topic and label that was used when creating permissions
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_RemovePermission.html
         * @param string $topicArn
         * @param string $label
         * @return bool
         * @throws \InvalidArgumentException
         */
        public function removePermission($topicArn, $label)
        {
            if (empty($topicArn) || empty($label))

                Throw New \InvalidArgumentException('Must supply a TopicARN and Label to remove a permission');

            $this->request('RemovePermission', [
                'Label' => $label
            ]);

            return true;
        }

        /**
         * Set a single attribute on a topic
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_SetTopicAttributes.html
         * @param string $topicArn
         * @param string $attrName
         * @param mixed $attrValue
         * @return bool
         * @throws \InvalidArgumentException
         */
        public function setTopicAttributes($topicArn, $attrName, $attrValue)
        {
            if (empty($topicArn) || empty($attrName) || empty($attrValue))

                Throw New \InvalidArgumentException('Must supply a TopicARN, AttributeName and AttributeValue to set a topic attribute');

            $this->request('SetTopicAttributes', [
                'TopicArn'       => $topicArn,
                'AttributeName'  => $attrName,
                'AttributeValue' => $attrValue,
            ]);

            return true;
        }

        /**
         * Subscribe to a topic
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_Subscribe.html
         * @param string $topicArn
         * @param string $protocol - http/https/email/email-json/sms/sqs
         * @param string $endpoint
         * @return bool
         * @throws \InvalidArgumentException
         */
        public function subscribe($topicArn, $protocol, $endpoint)
        {
            if (empty($topicArn) || empty($protocol) || empty($endpoint))

                Throw New \InvalidArgumentException('Must supply a TopicARN, Protocol and Endpoint to subscribe to a topic');

            $this->request('Subscribe', [
                'TopicArn' => $topicArn,
                'Protocol' => $protocol,
                'Endpoint' => $endpoint
            ]);

            return true;
        }

        /**
         * Unsubscribe a user from a topic
         *
         * @link http://docs.amazonwebservices.com/sns/latest/api/API_Unsubscribe.html
         * @param string $subscriptionArn
         * @return bool
         * @throws \InvalidArgumentException
         */
        public function unsubscribe($subscriptionArn)
        {
            if (empty($subscriptionArn))

                Throw New \InvalidArgumentException('Must supply a SubscriptionARN to unsubscribe from a topic');

            $this->request('Unsubscribe', [
                'SubscriptionArn' => $subscriptionArn
            ]);

            return true;
        }


        //
        // Private functions
        //

        /**
         * Perform and process a cURL request
         *
         * @param string $action
         * @param array $params [optional]
         * @return SimpleXMLElement
         * @throws SNSException|APIException
         */
        private function request($action, $params = [])
        {
            // Add in required params
            $params = ([
                'Action'            => $action,
                'AWSAccessKeyId'    => $this->accessKey,
                'Timestamp'         => gmdate('Y-m-d\TH:i:s\Z'),
                'SignatureVersion'  => 2,
                'SignatureMethod'   => 'HmacSHA256',

            ] + $params);


            // Sort and encode into string
            uksort($params, 'strnatcmp');

            $queryString = '';

            foreach ($params AS $key => $val)
            {
                $queryString .= "&{$key}=" . rawurlencode($val);
            }

            $queryString = substr($queryString, 1);

            // Form request string
            $requestString = "GET\n{$this->endpoint}\n/\n{$queryString}";

            // Create signature - Version 2
            $params['Signature'] = base64_encode(
                hash_hmac('sha256', $requestString, $this->secretKey, true)
            );

            // Finally create request
            $request = $this->protocol . $this->endpoint . '/?' . http_build_query($params);

            // Instantiate cUrl and perform request
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL             => $request,
                CURLOPT_RETURNTRANSFER  => true,

            ]);

            $output = curl_exec($ch);
            $info   = curl_getinfo($ch);

            // Close cUrl
            curl_close($ch);

            // Load XML response
            $xmlResponse = simplexml_load_string($output);

            // Check return code
            if (false === $this->validateResponse($info['http_code']))
            {
                // Response not in 200 range
                if (isset($xmlResponse->Error))
                {
                    // Amazon returned an XML error
                    Throw New SNSException(strval($xmlResponse->Error->Code) . ': ' . strval($xmlResponse->Error->Message), $info['http_code']);
                }

                else
                {
                    // Some other problem
                    Throw New APIException('There was a problem executing this request', $info['http_code']);
                }
            }

            else
            {
                // All good
                return $xmlResponse;
            }
        }

        /**
         * Check the curl response code - anything in 200 range
         *
         * @param int $code
         * @return bool
         */
        private function validateResponse($code)
        {
            return 2 === floor($code / 100);
        }

        /**
         * Transform the standard AmazonSNS XML array format into a normal array
         *
         * @param \SimpleXMLElement $xmlArray
         * @return array
         */
        private function processXmlToArray(\SimpleXMLElement $xmlArray)
        {
            $returnArray = [];

            // Process into array
            foreach ($xmlArray AS $xmlElement)
            {
                $elementArray = [];

                // Loop through each element
                foreach ($xmlElement AS $key => $element)
                {
                    // Use strval() to make sure no SimpleXMLElement objects remain
                    $elementArray[$key] = strval($element);
                }

                // Store array of elements
                $returnArray[] = $elementArray;
            }

            return $returnArray;
        }
    }

    // Exception thrown if there's a problem with the API
    Class APIException Extends \Exception
    {
        // ....
    }

    // Exception thrown if Amazon returns an error
    Class SNSException Extends \Exception
    {
        // ....
    }
}