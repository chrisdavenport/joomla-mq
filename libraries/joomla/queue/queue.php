<?php
/**
 * @package     Joomla.Platform
 * @subpackage  mq
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Joomla Message Queue class.
 *
 * @package  Joomla.Platform
 * @since    xx.x
 */
class JQueue
{
	/**
	 * Configuration object.
	 * 
	 * @var    JRegistry
	 * @since  xx.x
	 */
	protected $config;

	/**
	 * Constructor.
	 * 
	 * @param   JRegistry  $config  Registry object with configuration data.
	 * 
	 * @since   xx.x
	 */
	public function __construct(JRegistry $config = null)
	{
		$this->config = is_null($config) ? new JRegistry : $config;
	}

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
	public function delete($qid, $mid)
	{
		return $this->getAdapter($qid)->drop($qid, $mid);
	}

	/**
	 * Gets the adapter used by the queue.
	 *
	 * @param   string  $qid  Queue id.
	 *
	 * @return  JQueueAdapter  Queue-specific adapter object.
	 *
	 * @throws  RuntimeException
	 * 
	 * @since   xx.x
	 */
	protected function getAdapter($qid)
	{
		static $adapters = array();

		// Get the queue configuration data.
		$queueConfig = new JRegistry($this->config->get($qid));

		// Get the adapter name (defaults to "filesystem").
		$adapterName = $queueConfig->get('adapter', 'filesystem');

		// Calculate signature.
		$signature = md5(serialize($queueConfig));

		// If we already have an adapter object, re-use it.
		if (isset($adapters[$signature]))
		{
			return $adapters[$signature];
		}

		// Determine the adapter class name and instantiate the adapter.
		$className = 'JQueueAdapter' . ucfirst($adapterName);
		$adapter = new $className($queueConfig);

		// Cache it for possible re-use.
		$adapters[$signature] = $adapter;

		return $adapter;
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
	public function receive($qid, $timeout = 300)
	{
		return $this->getAdapter($qid)->pull($qid, $timeout);
	}

	/**
	 * Send a message to a queue.
	 *
	 * @param   string  $qid      Queue id.
	 * @param   string  $payload  Payload to send.
	 * @param   string  $appid    Optional application message id.
	 *
	 * @return  string  Message id.
	 *
	 * @since   xx.x
	 */
	public function send($qid, $payload = '', $appid = '')
	{
		// Get the queue adapter.
		$adapter = $this->getAdapter($qid);

		// Wrap the payload.
		$message = new JQueueMessage;
		$message->appid = $appid;
		$message->payload = $payload;

		// Hand the message to the adapter.
		$messageId = $adapter->push($qid, $message);

		return $messageId;
	}
}
