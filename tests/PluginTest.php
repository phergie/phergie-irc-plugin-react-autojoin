<?php
/**
 * Phergie (http://phergie.org)
 *
 * @link http://github.com/phergie/phergie-irc-plugin-react-autojoin for the canonical source repository
 * @copyright Copyright (c) 2008-2014 Phergie Development Team (http://phergie.org)
 * @license http://phergie.org/license Simplified BSD License
 * @package Phergie\Irc\Plugin\React\AutoJoin
 */

namespace Phergie\Irc\Tests\Plugin\React\AutoJoin;

use Phake;
use Phergie\Irc\Bot\React\EventQueueInterface;
use Phergie\Irc\Event\EventInterface;
use Phergie\Irc\Plugin\React\AutoJoin\Plugin;

/**
 * Tests for the Plugin class.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\AutoJoin
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests specifying configuration without a channels list.
     */
    public function testInstantiateWithoutChannels()
    {
        try {
            $plugin = new Plugin(array());
            $this->fail('Expected exception was not thrown');
        } catch (\DomainException $e) {
            $this->assertSame('$config must contain a "channels" key', $e->getMessage());
        }
    }

    /**
     * Data provider for testJoinChannels().
     *
     * @return array
     */
    public function dataProviderJoinChannels()
    {
        $data = array();

        // Channels string, no keys
        $data[] = array(
            array(
                'channels' => '#channel1',
            ),
            '#channel1',
            null,
        );

        // Channels string, keys string
        $data[] = array(
            array(
                'channels' => '#channel1',
                'keys' => 'key1',
            ),
            '#channel1',
            'key1',
        );

        // Channels array, keys array
        $data[] = array(
            array(
                'channels' => array('#channel1', '#channel2'),
                'keys' => array('key1', 'key2'),
            ),
            '#channel1,#channel2',
            'key1,key2',
        );

        return $data;
    }

    /**
     * Tests joining channels.
     *
     * @param array $config Plugin configuration
     * @param string $channels Expected parameter to ircJoin()
     * @param string|null $keys Expected parameter to ircJoin()
     * @dataProvider dataProviderJoinChannels
     */
    public function testJoinChannels(array $config, $channels, $keys)
    {
        $event = Phake::mock('\Phergie\Irc\Event\EventInterface');
        $queue = Phake::mock('\Phergie\Irc\Bot\React\EventQueueInterface');
        $plugin = new Plugin($config);
        $plugin->joinChannels($event, $queue);
        Phake::verify($queue)->ircJoin($channels, $keys);
    }

    /**
     * Tests joining channels on NickServ authentication.
     */
    public function testJoinChannelsOnAuth()
    {
        $connection = Phake::mock('\Phergie\Irc\ConnectionInterface');
        $queue = Phake::mock('\Phergie\Irc\Bot\React\EventQueueInterface');
        $plugin = new Plugin(array(
            'channels' => '#channel1',
            'wait-for-nickserv' => true,
        ));
        $plugin->joinChannels($connection, $queue);
        Phake::verify($queue)->ircJoin('#channel1', null);
    }

    /**
     * Data provider for testGetSubscribedEvents
     *
     * @return array
     */
    public function dataProviderGetSubscribedEvents()
    {
        return array(
            array(
                array(
                    'channels' => '#channel1',
                ),
                array(
                    'irc.received.rpl_endofmotd' => 'joinChannels',
                    'irc.received.err_nomotd' => 'joinChannels',
                ),
            ),
            array(
                array(
                    'channels' => '#channel1',
                    'wait-for-nickserv' => true,
                ),
                array(
                    'nickserv.identified' => 'joinChannels',
                ),
            ),
        );
    }

    /**
     * Tests that getSubscribedEvents() returns the correct event listeners.
     *
     * @param array $config
     * @param array $events
     * @dataProvider dataProviderGetSubscribedEvents
     */
    public function testGetSubscribedEvents(array $config, array $events)
    {
        $plugin = new Plugin($config);
        $this->assertEquals($events, $plugin->getSubscribedEvents());
    }
}
