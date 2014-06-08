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
 * Joomla Message Queue filesystem adapter class.
 *
 * @package  Joomla.Platform
 * @since    xx.x
 */
class JQueueAdapterFilesystem extends JQueueAdapterBase
{
	/**
	 * Adapter error prefix.
	 */
	const PREFIX = 'Message queue filesystem adapter';

	/**
	 * Constructor.
	 * 
	 * @param   JRegistry  $config  Registry object with configuration data.
	 * 
	 * @since   xx.x
	 */
	public function __construct(JRegistry $config)
	{
		parent::__construct($config);

		// Get queue directory path.
		$path = $this->getPath();

		// Check that the path exists and if not, create it.
		try
		{
			if (!file_exists($path))
			{
				mkdir($path, 0755, true);
			}
		}
		catch (Exception $e)
		{
			throw new RuntimeException(self::PREFIX . ' error: Cannot create path: ' . $path);
		}
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
	 * @throws  RuntimeException
	 *
	 * @since   xx.x
	 */
	public function drop($qid, $mid)
	{
		// Garbage collect.
		$this->gc($qid);

		// Get file specified by message id (assuming it is in flight).
		$filelist = $this->getFiles($mid . '\.inflight\.');

		// Not found, so probably expired or handled by another process.
		if (empty($filelist))
		{
			return false;
		}

		// Delete the file.
		if (!unlink($this->getPath() . '/' . $filelist[0]))
		{
			throw new RuntimeException(self::PREFIX . ' error: Cannot delete file: ' . $filelist[0]);
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
		// Get list of files currently in flight.
		$filelist = $this->getFiles($qid . '-(.*)\.inflight\.');

		// No messages currently in flight.
		if (empty($filelist))
		{
			return;
		}

		// Determine the path to the queue.
		$path = $this->getPath();

		// Check for expired in-flight messages.
		foreach ($filelist as $file)
		{
			// Parse filename to determine time when message was retrieved from the queue.
			list($basename, $expiry) = explode('.inflight.', $file);
			list($year, $month, $day, $hour, $minute, $second) = explode('-', $expiry);
			$expires = mktime($hour, $minute, $second, $month, $day, $year);

			// If in-flight time has expired, return message to active state.
			if (time() > $expires)
			{
				// Expired so rename file.
				if (!rename($path . '/' . $file, $path . '/' . $basename . '.message'))
				{
					throw new RuntimeException(self::PREFIX . ' error: Cannot rename file: ' . $file);
				}
			}
		}
	}

	/**
	 * Get a list of files matching a pattern.
	 * 
	 * @param   string  $pattern  Pattern regex (without delimiters).
	 * 
	 * @return  array   Array of filenames matching the pattern.
	 * 
	 * @since   xx.x
	 */
	protected function getFiles($pattern)
	{
		// Get a filesystem iterator.
		$iterator = new FilesystemIterator($this->getPath());

		// Iterate over the filesystem with a filter for the queue.
		$filter = new RegexIterator($iterator, '/' . $pattern . '/');
		$filelist = array();

		foreach ($filter as $entry)
		{
			$filelist[] = $entry->getFilename();
		}

		return $filelist;
	}

	/**
	 * Get the filesystem path.
	 * 
	 * @return  string  Filesystem path.
	 * 
	 * @since   xx.x
	 */
	protected function getPath()
	{
		return $this->config->get('path', JPATH_ROOT . '/tmp/queue');
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
		// Garbage collect.
		$this->gc($qid);

		// Get current active messages.
		$filelist = $this->getFiles($qid . '-(.*)\.message$');

		// No messages in the queue.
		if (empty($filelist))
		{
			return false;
		}

		// Sort messages so we get the oldest first (granularity 1 second).
		sort($filelist);

		// Rename the file to indicate "in flight".
		$expires = time() + $timeout;
		$oldname = $this->getPath() . '/' . $filelist[0];
		$newname = str_replace('.message', '.inflight.' . date('Y-m-d-H-i-s', $expires), $oldname);

		if (!rename($oldname, $newname))
		{
			throw new RuntimeException(self::PREFIX . ' error: Cannot rename file: ' . $oldname);
		}

		// Read the message from the file.
		if (!$message = file_get_contents($newname))
		{
			throw new RuntimeException(self::PREFIX . ' error: Cannot read from file: ' . $newname);
		}

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
	 * @throws  RuntimeException
	 * 
	 * @since   xx.x
	 */
	public function push($qid, JQueueMessage $message = null)
	{
		// Get queue directory path.
		$path = $this->getPath();

		// Construct filename.
		$filename = $qid . '-' . date('Y-m-d-H-i-s-') . md5(rand(1000000, 9999999));

		// Insert message id into message.
		$message->messageid = $filename;

		// Output the message file.
		$handle = fopen($path . '/' . $filename . '.message', 'wb');

		if (!fwrite($handle, $this->serialise($message)))
		{
			throw new RuntimeException(self::PREFIX . ' error: Cannot write to file: ' . $filename);
		}

		fclose($handle);

		return $filename;
	}
}
