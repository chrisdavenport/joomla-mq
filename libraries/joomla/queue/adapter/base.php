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
 * Joomla Message Queue abstract base adapter class.
 *
 * @package  Joomla.Platform
 * @since    xx.x
 */
abstract class JQueueAdapterBase implements JQueueAdapterinterface
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
	public function __construct(JRegistry $config)
	{
		$this->config = $config;
	}

	/**
	 * Serialiser.
	 * 
	 * This uses the PHP serialiser by default, but can be overridden in child classes.
	 * 
	 * @param   mixed  $data  Raw data to be serialised.
	 * 
	 * @return  string  Serialised data.
	 * 
	 * @since   xx.x
	 */
	protected function serialise($data)
	{
		return serialize($data);
	}

	/**
	 * Unserialiser.
	 * 
	 * This uses the PHP unserialiser by default, but can be overridden in child classes.
	 * 
	 * @param   string  $data  Serialised data to be unserialised.
	 * 
	 * @return  mixed  Unserialised data.
	 * 
	 * @since   xx.x
	 */
	protected function unserialise($data)
	{
		return unserialize($data);
	}
}
