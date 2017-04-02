<?php
/**
 * Phergie (http://phergie.org)
 *
 * @link http://github.com/phergie/phergie-irc-plugin-react-autojoin for the canonical source repository
 * @copyright Copyright (c) 2008-2014 Phergie Development Team (http://phergie.org)
 * @license http://phergie.org/license Simplified BSD License
 * @package Phergie\Irc\Plugin\React\AutoJoin
 */

namespace Phergie\Irc\Plugin\React\AutoJoin;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface;
use Phergie\Irc\Event\UserEventInterface;

/**
 * Plugin for automatically joining channels.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\AutoJoin
 */
class Plugin extends AbstractPlugin
{
    /**
     * Comma-delimited list of channels to join
     *
     * @var string
     */
    protected $channels;

    /**
     * Comma-delimited list of channel keys
     *
     * @var string|null
     */
    protected $keys = null;

    /**
     * Whether to listen for NickServ identification
     * instead of the MOTD.
     *
     * @var bool
     */
    protected $awaitNickServ = false;

    /**
     * Array list of channels to rejoin
     *
     * @var array
     */
    protected $rejoinChannels = false;

    /**
     * Array list of channels keys to rejoin
     *
     * @var array
     */
    protected $rejoinKeys = array();

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     * channels - required, either a comma-delimited string or array of names
     * of channels to join
     *
     * keys - optional, either a comma-delimited string or array of keys
     * corresponding to the channels to join
     *
     * wait-for-nickserv - optional, set to true to wait for the NickServ plugin
     * to successfully authenticate before joining channels
     *
     * auto-rejoin - optional, set to true to rejoin all channels in 'channels'
     * option, or set a comma-delimited string or array of names of channels to
     * rejoin only in these channels.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (!isset($config['channels'])) {
            throw new \DomainException('$config must contain a "channels" key');
        }

        $this->channels = is_array($config['channels'])
            ? implode(',', $config['channels'])
            : $config['channels'];

        if (isset($config['keys'])) {
            $this->keys = is_array($config['keys'])
                ? implode(',', $config['keys'])
                : $config['keys'];
        }

        if (!empty($config['wait-for-nickserv'])) {
            $this->awaitNickServ = true;
        }

        if (isset($config['auto-rejoin']) && $config['auto-rejoin']) {
            if ($config['auto-rejoin'] === true) {
                $this->rejoinChannels = explode(',', $this->channels);
            } elseif (is_string($config['auto-rejoin'])) {
                $this->rejoinChannels = explode(',', $config['auto-rejoin']);
            } elseif (is_array($config['auto-rejoin'])) {
                $this->rejoinChannels = $config['auto-rejoin'];
            } else {
                throw new \InvalidArgumentException('"auto-rejoin" must be '.
                    'boolean, string or array');
            }

            if ($this->keys != null) {
                $channels = explode(',', $this->channels);
                $keys = explode(',', $this->keys);
                foreach ($this->rejoinChannels as $key => $value) {
                    $index = array_search($value, $channels);
                    $this->rejoinKeys[$key] = $index !== false
                        ? $keys[$index] : null;
                }
            } else {
                $this->rejoinKeys = array_fill(0,
                    count($this->rejoinChannels), null);
            }
        }
    }

    /**
     * Indicates that the plugin monitors events indicating either:
     * - a NickServ auth event (if wait-for-nickserv is set); or
     * - an end or lack of a message of the day,
     * at which point the client should be authenticated and
     * in a position to join channels.
     * If auto-rejoin is set the plugin also monitors part and kick events.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array_merge(
            $this->awaitNickServ
                ? array(
                    'nickserv.identified' => 'joinChannels',
                )
                : array(
                    'irc.received.rpl_endofmotd' => 'joinChannels',
                    'irc.received.err_nomotd' => 'joinChannels',
                ),
            $this->rejoinChannels
                ? array(
                    'irc.received.part' => 'onPartChannels',
                    'irc.received.kick' => 'onKickChannels',
                )
                : array()
            );
    }

    /**
     * Joins the provided list of channels.
     *
     * @param mixed $dummy Unused, as it only matters that one of the
     *        subscribed events has occurred, not what it is
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function joinChannels($dummy, EventQueueInterface $queue)
    {
        $queue->ircJoin($this->channels, $this->keys);
    }

    /**
     * Rejoins a channel in provided list of channels on a part event.
     *
     * @param \Phergie\Irc\Event\UserEventInterface $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function onPartChannels(UserEventInterface $event, EventQueueInterface $queue)
    {
        if ($event->getNick() == $event->getConnection()->getNickname()
            && in_array($event->getSource(), $this->rejoinChannels)) {
            $index = array_search($event->getSource(), $this->rejoinChannels);
            $queue->ircJoin($this->rejoinChannels[$index],
                $this->rejoinKeys[$index]);
        }
    }

    /**
     * Rejoins a channel in provided list of channels on a kick event.
     *
     * @param \Phergie\Irc\Event\UserEventInterface $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function onKickChannels(UserEventInterface $event, EventQueueInterface $queue)
    {
        if ($event->getParams()['user'] == $event->getConnection()->getNickname()
            && in_array($event->getSource(), $this->rejoinChannels)) {
            $index = array_search($event->getSource(), $this->rejoinChannels);
            $queue->ircJoin($this->rejoinChannels[$index],
                $this->rejoinKeys[$index]);
        }
    }
}
