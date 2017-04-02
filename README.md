# phergie/phergie-irc-plugin-react-autojoin

A plugin for [Phergie](http://github.com/phergie/phergie-irc-bot-react/) to
join channels when connecting to servers.

[![Build Status](https://secure.travis-ci.org/phergie/phergie-irc-plugin-react-autojoin.png?branch=master)](http://travis-ci.org/phergie/phergie-irc-plugin-react-autojoin)

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "phergie/phergie-irc-plugin-react-autojoin": "~2"
    }
}
```

See Phergie documentation for more information on installing plugins.

## Configuration

```php
return array(
    'plugins' => array(

        new \Phergie\Irc\Plugin\React\AutoJoin\Plugin(array(

            // Required: list of channels to join
            'channels' => '#channel1,#channel2,#channelN',
            // or
            'channels' => array('#channel1', '#channel2', '#channelN'),

            // Optional: channel keys
            'keys' => 'key1,key2,keyN',
            // or
            'keys' => array('key1', 'key2', 'keyN'),

            // Optional: if true, doesn't join channels until
            // the NickServ plugin has successfully logged in
            'wait-for-nickserv' => true,

            // Optional: if true, joins all channels in 'channels' config
            // Defaults to false, and don't rejoin automatically
            'auto-rejoin' => true,
            // or
            'auto-rejoin' => '#channel1,#channel2',
            // or
            'auto-rejoin' => array('#channel1', '#channel2'),

        )),

        // If wait-for-nickserv is enabled, the NickServ plugin must also be used
        new \Phergie\Irc\Plugin\React\NickServ(array(
            /* .... */
        )),

    ),
);
```

The option `wait-for-nickserv` depends on the [NickServ plugin](https://github.com/phergie/phergie-irc-plugin-react-nickserv) version >=1.3.0.

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
./vendor/bin/phpunit
```

## License

Released under the BSD License. See `LICENSE`.
