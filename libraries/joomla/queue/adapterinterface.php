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
 * Joomla Message Queue interface.
 *
 * @package  Joomla.Platform
 * @since    xx.x
 */
interface JQueueAdapterinterface
{
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
	public function push($qid, JQueueMessage $message);

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
	 * @since   xx.x
	 */
	public function pull($qid, $timeout);

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
	public function drop($qid, $mid);
}
