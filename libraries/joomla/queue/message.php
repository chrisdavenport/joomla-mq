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
 * JQueueMessage Class
 *
 * @package     Joomla.Platform
 * @subpackage  Mq
 * @since       xx.x
 */
class JQueueMessage
{
	/**
	 * Message id assigned by source application.
	 *
	 * @var int
	 * @since  xx.x
	 */
	public $appid = '';

	/**
	 * Expiry date/time. (NOT USED)
	 *
	 * @var string
	 * @since  xx.x
	 */
	public $expires = '';

	/**
	 * External filename. (NOT USED)
	 *
	 * @var string
	 * @since  xx.x
	 */
	public $external = '';

	/**
	 * Message id (assigned by adapter).
	 *
	 * @var string
	 * @since  xx.x
	 */
	public $messageid = '';

	/**
	 * Message type. (NOT USED)
	 *
	 * @var string
	 * @since  xx.x
	 */
	public $type = '';

	/**
	 * Destination queue name. (NOT USED)
	 *
	 * @var string
	 * @since  xx.x
	 */
	public $destination = '';

	/**
	 * Payload.
	 *
	 * @var string
	 * @since  xx.x
	 */
	public $payload = '';

	/**
	 * Reply queue name. (NOT USED)
	 *
	 * @var string
	 * @since  xx.x
	 */
	public $reply = '';

	/**
	 * Source queue name. (NOT USED)
	 *
	 * @var string
	 * @since  xx.x
	 */
	public $source = '';
}
