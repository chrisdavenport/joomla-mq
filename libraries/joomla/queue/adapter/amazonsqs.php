<?php
/**
 * @package     Joomla.Platform
 * @subpackage  mq
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

require JPATH_LIBRARIES . '/aws-autoloader.php';

use Aws\Sqs\SqsClient;

/**
 * Joomla Message Queue Amazon SQS adapter class.
 * 
 * This class assumes that you have the Amazon PHP SDK installed
 * in the Joomla /libraries directory.  To install, simply download
 * the ZIP and unpack into /libraries.  This will create the following
 * directories in /libraries: Aws, Doctrine, Guzzle, Monolog, Psr
 * and Symfony.  Not all of these libraries are actually used by
 * the SQS system.
 * 
 * To use this adapter you must pass the following configuration
 * variables to the class constructor in the JRegistry object:
 *   [queue-name].key = [Amazon key]
 *   [queue-name].secret = [Amazon secret key]
 *   [queue-name].region = [Amazon region code]
 *   [queue-name].url = [URL of the Amazon SQS queue to be used]
 *
 * @package  Joomla.Platform
 * @since    xx.x
 */
class JQueueAdapterAmazonsqs extends JQueueAdapterBase
{
	/**
	 * Adapter error prefix.
	 */
	const PREFIX = 'Message queue Amazon SQS adapter';

	/**
	 * Delete a message from a queue.
	 *
	 * This can only be done after the message has been received
	 * and only within the "in flight" period for that message.
	 *
	 * @param   string  $qid  Queue id.
	 * @param   string  $mid  Message id.
	 * 
	 * @return  boolean
	 *
	 * @since   xx.x
	 */
	public function drop($qid, $mid)
	{
		// Get the Amazon SQS client.
		$client = SqsClient::factory(
			array(
				'key'    => $this->config->get('key'),
				'secret' => $this->config->get('secret'),
				'region' => $this->config->get('region'),
			)
		);

		// Delete the message.
		$result = $client->deleteMessage(
			array(
				'QueueUrl' => $this->config->get('url'),
				'ReceiptHandle' => $mid,
			)
		);

		return true;
	}

	/**
	 * Receive a message from a queue.
	 *
	 * This does not remove the message from the queue, it merely "locks" it for
	 * a predefined "in flight" period, after which it is either deleted or is
	 * once again available for being received.
	 *
	 * @param   string   $qid      Queue id.
	 * @param   integer  $timeout  Number of seconds that message will be "in flight".
	 *
	 * @return  JQueueMessage object
	 * 
	 * @since   xx.x
	 */
	public function pull($qid, $timeout = 300)
	{
		// Get the Amazon SQS client.
		$client = SqsClient::factory(
			array(
				'key'    => $this->config->get('key'),
				'secret' => $this->config->get('secret'),
				'region' => $this->config->get('region'),
			)
		);

		// Receive a message (if one is available).
		$result = $client->receiveMessage(
			array(
				'QueueUrl' => $this->config->get('url'),
				'MaxNumberOfMessages' => 1,
				'VisibilityTimeout' => $timeout,
			)
		);

		// No messages in the queue.
		$messages = $result->get('Messages');

		if (empty($messages))
		{
			return false;
		}

		// Set up the message.
		$message = $this->unserialise($messages[0]['Body']);
		$message->messageid = $messages[0]['ReceiptHandle'];

		return $message;
	}

	/**
	 * Push a message.
	 * 
	 * @param   string         $qid      Queue id.
	 * @param   JQueueMessage  $message  Message object.
	 * 
	 * @return  string  Message id.
	 * 
	 * @since   xx.x
	 */
	public function push($qid, JQueueMessage $message = null)
	{
		// Get the Amazon SQS client.
		$client = SqsClient::factory(
			array(
				'key'    => $this->config->get('key'),
				'secret' => $this->config->get('secret'),
				'region' => $this->config->get('region'),
			)
		);

		// Send the message.
		$response = $client->sendMessage(
			array(
				'QueueUrl'    => $this->config->get('url'),
				'MessageBody' => $this->serialise($message),
			)
		);

		return $response->get('MessageId');
	}
}
