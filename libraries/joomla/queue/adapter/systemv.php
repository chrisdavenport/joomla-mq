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
 * Joomla Message Queue Unix System V adapter class.
 *
 * @package  Joomla.Platform
 * @since    xx.x
 */
class JQueueAdapterSystemv extends JQueueAdapterBase
{
	/**
	 * Adapter error prefix.
	 */
	const PREFIX = 'Message queue System V adapter';

	/**
	 * Delete a message from a queue.
	 *
	 * Not used in this adapter.  There is no "in flight" state.
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
		return true;
	}

	/**
	 * Get a handle for a queue.
	 *
	 * @param   string  $qid  Queue id.
	 *
	 * @return  resource  System V message queue handle.
	 *
	 * @since   xx.x
	 */
	protected function getQueue($qid)
	{
		// Queue "name" must be an integer for this adapter.
		if (!is_numeric($qid))
		{
			$qid = crc32($qid);
		}

		// Get the queue handle.
		$queue = msg_get_queue((int) $qid);

		return $queue;
	}

	/**
	 * Receive a message from a queue.
	 *
	 * This call removes the message from the queue.  There is no "in flight" state.
	 *
	 * @param   string   $qid      Queue id.
	 * @param   integer  $timeout  Not available in this adapter.
	 *
	 * @return  JQueueMessage object
	 * 
	 * @throws  RuntimeException
	 *
	 * @since   xx.x
	 */
	public function pull($qid, $timeout = 300)
	{
		// Get the queue.
		$queue = $this->getQueue($qid);

		// Get the maximum size of a message.
		$maxMessageSize = $this->config->get('maxmessagesize', 1024);

		// Receive a message (non-blocking).
		if (!msg_receive($queue, 0, $type, $maxMessageSize, $message, true, MSG_IPC_NOWAIT | MSG_NOERROR, $error))
		{
			// No message available so return false;
			if ($error == 42)
			{
				return false;
			}

			throw new RuntimeException(self::PREFIX . ' error: ' . $error);
		}

		// Unserialise the message.
		$message = $this->unserialise($message);

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
	 * @throws  RuntimeException
	 * 
	 * @since   xx.x
	 */
	public function push($qid, JQueueMessage $message = null)
	{
		// Add the message id.
		$message->messageid = crc32(serialize(array($message, time())));

		// Get the queue.
		$queue = $this->getQueue($qid);

		// Send the message (non-blocking).
		if (!msg_send($queue, 1, $this->serialise($message), true, false, $error))
		{
			throw new RuntimeException(self::PREFIX . ' error: ' . $error);
		}

		return $message->messageid;
	}
}
