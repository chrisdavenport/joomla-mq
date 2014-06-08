<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  Mq
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

require_once __DIR__ . '/JQueueInspector.php';

/**
 * Test class for JQueueAdapterFilesystem.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mq
 * @since       xx.x
 */
class JQueueAdapterFilesystemTest extends TestCase
{
	/**
	 * Queue object instance.
	 *
	 * @var    JQueue
	 * @since  xx.x
	 */
	private $object;

	/**
	 * Tests that a queue with a bad path will throw an exception.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testBadPath()
	{
		$this->setExpectedException('RuntimeException');

		$queue = $this->object->getAdapter('badpathqueue');
	}

	/**
	 * Tests a queue with a path that must be created.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testNewPath()
	{
		$queue = $this->object->getAdapter('tempqueue');
	}

	/**
	 * Tests that receiving from an empty queue returns false.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemReceiveEmpty()
	{
		$this->assertFalse($this->object->receive('filequeue'));
	}

	/**
	 * Tests that sending a message to a filesystem adapter returns a message id.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemSend()
	{
		$messageid = $this->object->send('filequeue', 'Test message payload');

		$this->assertThat(
			substr($messageid, 0, 10),
			$this->equalTo('filequeue-'),
			'Tests that the send method returned a message id with the queue name as prefix.'
		);
	}

	/**
	 * Tests that a received message is contained in the correct object.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemReceiveCheck()
	{
		$this->object->send('filequeue', 'Test payload');

		$this->assertThat(
			$this->object->receive('filequeue'),
			$this->isInstanceOf('JQueueMessage'),
			'Tests that a received message is contained in a JQueueMessage object.'
		);
	}

	/**
	 * Tests that the received message has the correct message id.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemCheckMessageId()
	{
		$messageid = $this->object->send('filequeue', 'Test payload');
		$message = $this->object->receive('filequeue');

		$this->assertThat(
			$message->messageid,
			$this->equalTo($messageid),
			'Tests that the received message has the correct message id.'
		);
	}

	/**
	 * Tests that the received message has the correct application id.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemCheckApplicationId()
	{
		$messageid = $this->object->send('filequeue', 'Test payload', 'app-id-value');
		$message = $this->object->receive('filequeue');

		$this->assertThat(
			$message->appid,
			$this->equalTo('app-id-value'),
			'Tests that the received message has the correct application id.'
		);
	}

	/**
	 * Tests that the received message has the correct payload.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemCheckPayload()
	{
		$messageid = $this->object->send('filequeue', 'Test payload');
		$message = $this->object->receive('filequeue');

		$this->assertThat(
			$message->payload,
			$this->equalTo('Test payload'),
			'Tests that the received message has the correct payload.'
		);
	}

	/**
	 * Tests that messages can correctly carry objects.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemCheckPayloadObject()
	{
		$dummyConfig = array(
			'test1' => 'Test level 1',
			'test2' => array(
				'test21' => 'Test level 2',
			),
		);
		$dummyPayload = new JRegistry($dummyConfig);

		$messageid = $this->object->send('filequeue', $dummyPayload);
		$message = $this->object->receive('filequeue');

		$this->assertThat(
			$message->payload,
			$this->equalTo($dummyPayload),
			'Tests that the received message has the correct payload object.'
		);
	}

	/**
	 * Tests that a batch of messages can be sent and received.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemBatchMessages()
	{
		$qid = 'filequeue';

		// Create some random messages.
		$payloads = array();

		for ($i = 0; $i <= 10; $i++)
		{
			$payloads[$i] = rand(100000, 999999);
		}

		// Send them to the queue.
		$messageids = array();

		foreach ($payloads as $index => $message)
		{
			$messageids[$index] = $this->object->send($qid, $message, $index);
		}

		// Now receive them back.
		$received = array();

		while ($message = $this->object->receive($qid))
		{
			$received[$message->appid] = $message;

			$this->assertThat(
				$message->messageid,
				$this->equalTo($messageids[$message->appid]),
				'Tests that each received message has the correct message id.'
			);

			$this->assertThat(
				$message->payload,
				$this->equalTo($payloads[$message->appid]),
				'Tests that each received message has the correct payload.'
			);
		}

		// Check that all messages were received.
		foreach ($messageids as $index => $payload)
		{
			$this->assertTrue(
				isset($received[$index])
			);
		}

		// Check that we have them all.
		$this->assertCount(
			count($messageids),
			$received,
			'We counted them out and we counted them all back in.'
		);

		// Now delete them all.
		foreach ($received as $message)
		{
			$this->assertTrue(
				$this->object->delete($qid, $message->messageid)
			);
		}

		// Make sure there are no messages left in the queue.
		$this->assertFalse(
			$this->object->receive($qid)
		);
	}

	/**
	 * Tests that dropping a nonexistent message returns false.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemDropNonexistentMessage()
	{
		$this->assertFalse(
			$this->object->delete('filequeue', 'doesnotexist')
		);
	}

	/**
	 * Tests that garbage collector will restore expired files.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemGarbageExpired()
	{
		$qid = 'filequeue';

		// Create some random messages.
		$payloads = array();

		for ($i = 0; $i <= 10; $i++)
		{
			$payloads[$i] = rand(100000, 999999);
		}

		// Send them to the queue.
		$messageids = array();

		foreach ($payloads as $index => $message)
		{
			$messageids[$index] = $this->object->send($qid, $message, $index);
		}

		// Now receive them back with a very short expiry time (1 second).
		$messages = array();

		while ($message = $this->object->receive($qid, 1))
		{
			$messages[] = $message;
		}

		// Wait for more than the expiry time.
		sleep(2);

		// Now try to drop the messages.
		foreach ($messages as $message)
		{
			$this->assertFalse(
				$this->object->delete($qid, $message->messageid)
			);
		}
	}

	/**
	 * Sets up the fixture.
	 *
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	protected function setUp()
	{
		parent::setUp();

		$config = array(
			'badpathqueue' => array(			// A queue with a bad path specified.
				'adapter' => 'filesystem',
				'path' => '/badpath',
			),
			'tempqueue' => array(				// A queue with a path that must be created.
				'adapter' => 'filesystem',
				'path' => JPATH_BASE . '/tmp/test-' . date('Y-m-d-H-i-s'),
			),
			'filequeue' => array(
				'adapter' => 'filesystem',
				'path' => JPATH_BASE . '/tmp/queue',
			),
		);

		$this->object = new JQueueInspector(new JRegistry($config));

		$this->clearAllMessages();
	}

	/**
	 * Clear all messages from the queue directory.
	 * 
	 * @return  void
	 */
	protected function clearAllMessages()
	{
		$iterator = new FilesystemIterator(JPATH_ROOT . '/tmp/queue');

		foreach ($iterator as $entry)
		{
			unlink(JPATH_ROOT . '/tmp/queue/' . $entry->getFilename());
		}
	}
}
