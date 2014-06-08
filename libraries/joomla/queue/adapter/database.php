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
 * Joomla Message Queue database adapter class.
 *
 * @package  Joomla.Platform
 * @since    xx.x
 */
class JQueueAdapterDatabase extends JQueueAdapterBase
{
	/**
	 * Adapter error prefix.
	 */
	const PREFIX = 'Message queue database adapter';

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
		// Garbage collect.
		$this->gc($qid);

		// Delete message if it's within its in flight period.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->delete($db->qn('#__mq_messages'))
			->where($db->qn('queuename') . ' = ' . $db->q($qid))
			->where($db->qn('id') . ' = ' . $db->q($mid))
			->where($db->qn('expires') . ' > ' . $db->q(date($db->getDateFormat(), time())));

		$db->setQuery($query)->execute();

		// Make sure we actually deleted the record.
		if ($db->getAffectedRows() == 0)
		{
			return false;
		}

		return true;
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
	 * @throws  RuntimeException
	 *
	 * @since   xx.x
	 */
	protected function gc($qid)
	{
		// Get list of messages currently in flight.
		// If any have expired, reset them to active state.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->update($db->qn('#__mq_messages'))
			->set($db->qn('expires') . ' = ' . $db->q($db->getNullDate()))
			->where($db->qn('queuename') . ' = ' . $db->q($qid))
			->where($db->qn('expires') . ' > ' . $db->q($db->getNullDate()))
			->where($db->qn('expires') . ' < ' . $db->q(date($db->getDateFormat(), time())));

		if (!$db->setQuery($query)->execute())
		{
			throw new RuntimeException(self::PREFIX . ' error: Cannot update database');
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
	 * @return  JQueueMessage object
	 * 
	 * @since   xx.x
	 */
	public function pull($qid, $timeout = 300)
	{
		// Garbage collect.
		$this->gc($qid);

		// Get current active message.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('*')
			->from($db->qn('#__mq_messages'))
			->where($db->qn('queuename') . ' = ' . $db->q($qid))
			->where($db->qn('expires') . ' = ' . $db->q($db->getNullDate()))
			->order($db->qn('id'));

		if (!$qrecord = $db->setQuery($query, 0, 1)->loadObject())
		{
			// No messages in the queue.
			return false;
		}

		// Set expiry date/time to indicate "in flight".
		$expires = date($db->getDateFormat(), time() + $timeout);
		$query = $db->getQuery(true);
		$query
			->update($db->qn('#__mq_messages'))
			->set($db->qn('expires') . ' = ' . $db->q($expires))
			->where($db->qn('id') . ' = ' . (int) $qrecord->id)
			->where($db->qn('expires') . ' = ' . $db->q($db->getNullDate()));

		if (!$db->setQuery($query)->execute())
		{
			return false;
		}

		// Make sure we actually got the record.
		if ($db->getAffectedRows() == 0)
		{
			return false;
		}

		// Set up the message.
		$message = new JQueueMessage;
		$message->appid = $qrecord->appid;
		$message->messageid = $qrecord->id;
		$message->payload = $this->unserialise($qrecord->payload);
		$message->expires = $expires;

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
		// Write message to the queue table.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->insert($db->qn('#__mq_messages'))
			->set($db->qn('queuename') . ' = ' . $db->q($qid))
			->set($db->qn('appid') . ' = ' . $db->q($message->appid))
			->set($db->qn('payload') . ' = ' . $db->q($this->serialise($message->payload)))
			->set($db->qn('expires') . ' = ' . $db->q($db->getNullDate()));

		if (!$db->setQuery($query)->execute())
		{
			throw new RuntimeException(self::PREFIX . ' error: Cannot write to database');
		}

		return $db->insertid();
	}
}
