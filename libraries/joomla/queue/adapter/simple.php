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
 * Joomla Message Queue simple adapter class.
 *
 * @package  Joomla.Platform
 * @since    xx.x
 */
class JQueueAdapterSimple extends JQueueAdapterBase
{
	/**
	 * Adapter error prefix.
	 */
	const PREFIX = 'Message queue simple adapter';

	/**
	 * Message queue.
	 */
	private $queue = array();

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
		// Does the queue even exist?
		if (!isset($this->queue[$qid]))
		{
			return false;
		}

		// Run the garbage collector.
		$this->gc($qid);

		// Look for the entry requested.
		foreach ($this->queue[$qid] as $key => $entry)
		{
			if ($entry['id'] == $mid && $entry['status'] == 'inflight')
			{
				unset($this->queue[$qid][$key]);

				return true;
			}
		}

		return false;
	}

	/**
	 * Garbage collector.
	 *
	 * This checks all current in-flight messages and if expired, returns them
	 * to the active queue.
	 *
	 * @param   string  $qid  Queue id.
	 *
	 * @return  void
	 * 
	 * @since   xx.x
	 */
	protected function gc($qid)
	{
		// Look for expired in-flight messages.
		foreach ($this->queue[$qid] as $key => $entry)
		{
			if ($entry['status'] == 'inflight' && time() > $entry['expires'])
			{
				// Return message to active status.
				$this->queue[$qid][$key]['status'] = 'active';
				unset($this->queue[$qid][$key]['expires']);
			}
		}
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
	 * @return  JQueueMessage object or false if no message available.
	 * 
	 * @throws  RuntimeException
	 *
	 * @since   xx.x
	 */
	public function pull($qid, $timeout = 300)
	{
		// Check for empty queue.
		if (!isset($this->queue[$qid]))
		{
			return false;
		}

		// Run the garbage collector.
		$this->gc($qid);

		// Find the oldest active entry in the queue.
		$index = null;

		foreach ($this->queue[$qid] as $key => $entry)
		{
			if ($entry['status'] == 'active')
			{
				$index = $key;
				break;
			}
		}

		// All entries in the queue are in-flight.
		if (is_null($index))
		{
			return false;
		}

		// Calculate the expiry time.
		$expires = time() + $timeout;

		// Flag the queue entry as in-flight and set the expiry time.
		$this->queue[$qid][$index]['status'] = 'inflight';
		$this->queue[$qid][$index]['expires'] = $expires;

		// Take a copy of the message.
		$message = $this->queue[$qid][$index]['message'];

		// Unserialise the message.
		if (!$message = $this->unserialise($message))
		{
			throw new RuntimeException(self::PREFIX . ' error: Cannot unserialise message');
		}

		// Put expiry time into message.
		$message->expires = date('Y-m-d H:i:s', $expires);

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
		// Add the message id.
		$message->messageid = crc32(serialize(array($message, time())));

		$this->queue[$qid][] = array(
			'id' => $message->messageid,
			'status' => 'active',
			'message' => $this->serialise($message),
		);

		return $message->messageid;
	}
}
