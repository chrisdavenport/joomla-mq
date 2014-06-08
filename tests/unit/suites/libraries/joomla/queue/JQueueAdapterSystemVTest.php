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
 * Test class for JQueueAdapterSystemv.
 * 
 * @package     Joomla.UnitTest
 * @subpackage  Mq
 * @since       xx.x
 */
class JQueueAdapterSystemVTest extends TestCase
{
	/**
	 * The instance of the object to test.
	 *
	 * @var    JQueueAdapterSystemv
	 * @since  xx.x
	 */
	private $object;

	/**
	 * Tests that an empty queue returns false.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testPullEmpty()
	{
		$this->assertFalse($this->object->pull('sysvqueue'));
	}

	/**
	 * Tests that sending a message to the adapter returns a numeric message id.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testPush()
	{
		$messageid = $this->object->push('sysvqueue', new JQueueMessage);

		$this->assertTrue(is_numeric($messageid));
	}

	/**
	 * Tests that a received message is contained in the correct object.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testPullCheck()
	{
		$this->object->push('sysvqueue', new JQueueMessage);

		$this->assertThat(
			$this->object->pull('sysvqueue'),
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
	public function testCheckMessageId()
	{
		// Send the message and receive it back.
		$messageid = $this->object->push('sysvqueue', new JQueueMessage);
		$message = $this->object->pull('sysvqueue');

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
	public function testCheckApplicationId()
	{
		// Some random application id.
		$appid = 'fcvjqwe;ljd-09234';

		// Setup the message to be sent.
		$message = new JQueueMessage;
		$message->appid = $appid;
		$message->payload = 'Test payload';

		// Send the message and receive it back.
		$messageid = $this->object->push('sysvqueue', $message);
		$message = $this->object->pull('sysvqueue');

		$this->assertThat(
			$message->appid,
			$this->equalTo($appid),
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
	public function testCheckPayload()
	{
		// Some random application id.
		$payload = 'vh2407vkjsd0923r';

		// Setup the message to be sent.
		$message = new JQueueMessage;
		$message->payload = $payload;

		// Send the message and receive it back.
		$messageid = $this->object->push('sysvqueue', $message);
		$message = $this->object->pull('sysvqueue');

		$this->assertThat(
			$message->payload,
			$this->equalTo($payload),
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
	public function testCheckPayloadObject()
	{
		$dummyConfig = array(
			'test1' => 'Test level 1',
			'test2' => array(
				'test21' => 'Test level 2',
			),
		);
		$dummyPayload = new JRegistry($dummyConfig);

		// Setup the message to be sent.
		$message = new JQueueMessage;
		$message->payload = $dummyPayload;

		// Send the message and receive it back.
		$messageid = $this->object->push('sysvqueue', $message);
		$message = $this->object->pull('sysvqueue');

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
	public function testBatchMessages()
	{
		$qid = 'sysvqueue';

		// Create some random messages.
		$payloads = array();

		for ($i = 0; $i <= 10; $i++)
		{
			$payloads[$i] = rand(100000, 999999);
		}

		// Send them to the queue.
		$messageids = array();

		foreach ($payloads as $index => $payload)
		{
			$message = new JQueueMessage;
			$message->payload = $payload;
			$message->appid = $index;

			$messageids[$index] = $this->object->push($qid, $message);
		}

		// Now receive them back.
		$received = array();

		while ($message = $this->object->pull($qid))
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
			$this->assertThat(
				isset($received[$index]),
				$this->isTrue(),
				'Tests that all messages were received.'
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
			$this->assertThat(
				$this->object->drop($qid, $message->messageid),
				$this->isTrue(),
				'Tests that all messages have been dropped.'
			);
		}

		// Make sure there are no messages left in the queue.
		$this->assertThat(
			$this->object->pull($qid),
			$this->isFalse(),
			'Tests that there are no messages left in the queue.'
		);
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
			'maxmessagesize' => 1024,
		);

		$this->object = new JQueueAdapterSystemv(new JRegistry($config));

		// Clear out any messages left over from previous tests.
		$queue = msg_get_queue(crc32('sysvqueue'));
		$error = 0;

		while ($error != 42)
		{
			msg_receive($queue, 0, $type, 1024, $message, true, MSG_IPC_NOWAIT | MSG_NOERROR, $error);
		}
	}
}
