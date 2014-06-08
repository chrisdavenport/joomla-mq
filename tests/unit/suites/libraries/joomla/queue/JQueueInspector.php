<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  Mq
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Class to expose protected properties and methods in JQueue for testing purposes.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mq
 * @since       xx.x
 */
class JQueueInspector extends JQueue
{
	/**
	 * Sets any property from the class.
	 *
	 * @param   string  $property  The name of the class property.
	 * @param   string  $value     The value of the class property.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function __set($property, $value)
	{
		return $this->$property = $value;
	}

	/**
	 * Gets any property from the class.
	 *
	 * @param   string  $property  The name of the class property.
	 *
	 * @return  mixed   The value of the class property.
	 *
	 * @since   11.1
	 */
	public function get($property)
	{
		return $this->$property;
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
	public function getAdapter($qid)
	{
		return parent::getAdapter($qid);
	}
}
