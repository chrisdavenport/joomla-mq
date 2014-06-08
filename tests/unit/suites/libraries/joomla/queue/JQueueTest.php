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
 * Test class for JQueue.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mq
 * @since       xx.x
 */
class JQueueTest extends TestCase
{
	/**
	 * The instance of the object to test.
	 *
	 * @var    JQueue
	 * @since  xx.x
	 */
	private $object;

	/**
	 * Tests that the default queue adapter type is 'filesystem'.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testDefaultConfig()
	{
		// Check the object type.
		$this->assertThat(
			$this->object->getAdapter('nullqueue'),
			$this->isInstanceOf('JQueueAdapterFilesystem'),
			'Tests that the default queue adapter is an instance of JQueueAdapterFilesystem.'
		);
	}

	/**
	 * Tests that requesting a filesystem adapter actually delivers one.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemConfig()
	{
		// Check the object type.
		$this->assertThat(
			$this->object->getAdapter('filequeue'),
			$this->isInstanceOf('JQueueAdapterFilesystem'),
			'Tests that requesting a filesystem adapter delivers an instance of JQueueAdapterFilesystem.'
		);
	}

	/**
	 * Tests that requesting a database adapter actually delivers one.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testDatabaseConfig()
	{
		// Check the object type.
		$this->assertThat(
			$this->object->getAdapter('dbqueue'),
			$this->isInstanceOf('JQueueAdapterDatabase'),
			'Tests that requesting a database adapter delivers an instance of JQueueAdapterDatabase.'
		);
	}

	/**
	 * Tests that requesting a System V adapter actually delivers one.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testSystemVConfig()
	{
		// Check the object type.
		$this->assertThat(
			$this->object->getAdapter('sysvqueue'),
			$this->isInstanceOf('JQueueAdapterSystemv'),
			'Tests that requesting a System V adapter delivers an instance of JQueueAdapterSystemv.'
		);
	}

	/**
	 * Tests that requesting a Simple adapter actually delivers one.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testSimpleConfig()
	{
		// Check the object type.
		$this->assertThat(
			$this->object->getAdapter('simplequeue'),
			$this->isInstanceOf('JQueueAdapterSimple'),
			'Tests that requesting a Simple adapter delivers an instance of JQueueAdapterSimple.'
		);
	}

	/**
	 * Tests that requesting the filesystem adapter twice delivers the same adapter instance.
	 *
	 * @return  void
	 *
	 * @since   xx.x
	 */
	public function testFilesystemConfigTwice()
	{
		$adapter1 = $this->object->getAdapter('filequeue');
		$adapter2 = $this->object->getAdapter('filequeue');

		$this->assertThat(
			$adapter1,
			$this->equalTo($adapter2),
			'Tests that requesting the same adapter twice actually delivers the same adapter instance.'
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
			'nullqueue' => array(),				// A queue with no adapter specified.
			'filequeue' => array(				// A filesystem queue.
				'adapter' => 'filesystem',
				'path' => '/var/www/workspace/message-queues/joomla-cms/tmp/queue',
			),
			'dbqueue' => array(					// A database queue.
				'adapter' => 'database',
			),
			'sysvqueue' => array(				// A System V queue.
				'adapter' => 'systemv',
			),
			'simplequeue' => array(				// A Simple queue.
				'adapter' => 'simple',
			),
		);

		$this->object = new JQueueInspector(new JRegistry($config));
	}
}
