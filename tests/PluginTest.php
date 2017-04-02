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
     * Data provider for testRejoinChannels().
     *
     * @return array
     */
    public function dataProviderRejoinChannels()
    {
        $data = array();

        // Channels string, no keys
        $data[] = array(
            array(
                'channels' => '#channel1',
                'auto-rejoin' => true,
            ),
            null,
        );

        // Channels string, keys string
        $data[] = array(
            array(
                'channels' => '#channel1',
                'keys' => 'key1',
                'auto-rejoin' => true,
            ),
            'key1',
        );

        // Channels array, keys array, rejoin array
        $data[] = array(
            array(
                'channels' => array('#channel1', '#channel2'),
                'keys' => array('key1', 'key2'),
                'auto-rejoin' => array('#channel1'),
            ),
            'key1',
        );

        return $data;
    }

    /**
     * Tests rejoining channels on part and kick events.
     *
     * @param array $config Plugin configuration
     * @param string|null $key Expected parameter to ircJoin()
     * @dataProvider dataProviderRejoinChannels
     */
    public function testRejoinChannels(array $config, $key)
    {
        $connection = $this->getMockConnection('mynickname');
        $event = $this->getMockUserEvent('mynickname','#channel1', $connection);
        $queue = Phake::mock('\Phergie\Irc\Bot\React\EventQueueInterface');
        $plugin = new Plugin($config);
        $plugin->onPartChannels($event, $queue);
        $plugin->onKickChannels($event, $queue);
        Phake::verify($queue, Phake::times(2))->ircJoin('#channel1', $key);
    }

    /**
     * Tests ignore other users part and kick events.
     *
     * @param array $config Plugin configuration
     * @param string|null $key Expected parameter to ircJoin()
     * @dataProvider dataProviderRejoinChannels
     */
    public function testDontRejoinChannelsOnOtherUser(array $config, $key)
    {
        $connection = $this->getMockConnection('mynickname');
        $event = $this->getMockUserEvent('othernickname','#channel1', $connection);
        $queue = Phake::mock('\Phergie\Irc\Bot\React\EventQueueInterface');
        $plugin = new Plugin($config);
        $plugin->onPartChannels($event, $queue);
        $plugin->onKickChannels($event, $queue);
        Phake::verify($queue, Phake::never())->ircJoin('#channel1', $key);
    }

    /**
     * Tests ignore other channels part and kick events.
     * (this seems strange, but on data set #3 makes sense)
     *
     * @param array $config Plugin configuration
     * @param string|null $key Expected parameter to ircJoin()
     * @dataProvider dataProviderRejoinChannels
     */
    public function testDontRejoinChannelsOnOtherChannel(array $config, $key)
    {
        $connection = $this->getMockConnection('mynickname');
        $event = $this->getMockUserEvent('mynickname','#channel2', $connection);
        $queue = Phake::mock('\Phergie\Irc\Bot\React\EventQueueInterface');
        $plugin = new Plugin($config);
        $plugin->onPartChannels($event, $queue);
        $plugin->onKickChannels($event, $queue);
        Phake::verify($queue, Phake::never())->ircJoin('#channel1', $key);
        Phake::verify($queue, Phake::never())->ircJoin('#channel2', $key);
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
            array(
                array(
                    'channels' => '#channel1',
                    'auto-rejoin' => true,
                ),
                array(
                    'irc.received.rpl_endofmotd' => 'joinChannels',
                    'irc.received.err_nomotd' => 'joinChannels',
                    'irc.received.part' => 'onPartChannels',
                    'irc.received.kick' => 'onKickChannels',
                ),
            ),
            array(
                array(
                    'channels' => '#channel1',
                    'wait-for-nickserv' => true,
                    'auto-rejoin' => array('#channel1'),
                ),
                array(
                    'nickserv.identified' => 'joinChannels',
                    'irc.received.part' => 'onPartChannels',
                    'irc.received.kick' => 'onKickChannels',
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

    /**
     * Returns a mock user event.
     *
     * @return \Phergie\Irc\Event\UserEventInterface
     */
    protected function getMockUserEvent($nickname, $channel, $connection)
    {
        $mock = Phake::mock('\Phergie\Irc\Event\UserEventInterface');
        Phake::when($mock)->getNick()->thenReturn($nickname);
        Phake::when($mock)->getSource()->thenReturn($channel);
        Phake::when($mock)->getConnection()->thenReturn($connection);
        Phake::when($mock)->getParams()->thenReturn(array(
            'channel' => $channel,
            'channels' => $channel,
            'user' => $nickname,
        ));
        return $mock;
    }

    /**
     * Returns a mock connection.
     *
     * @return \Phergie\Irc\ConnectionInterface
     */
    protected function getMockConnection($nickname)
    {
        $mock = Phake::mock('\Phergie\Irc\ConnectionInterface');
        Phake::when($mock)->getNickname()->thenReturn($nickname);
        return $mock;
    }
}
