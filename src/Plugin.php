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
use Phergie\Irc\Event\EventInterface;

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
    }

    /**
     * Indicates that the plugin monitors events indicating either:
     * - a NickServ auth event (if wait-for-nickserv is set); or
     * - an end or lack of a message of the day,
     * at which point the client should be authenticated and
     * in a position to join channels.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return $this->awaitNickServ
            ? array(
                'nickserv.identified' => 'joinChannels',
            )
            : array(
                'irc.received.rpl_endofmotd' => 'joinChannels',
                'irc.received.err_nomotd' => 'joinChannels',
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
}
