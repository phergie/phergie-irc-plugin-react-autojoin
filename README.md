# phergie/phergie-irc-plugin-react-autojoin

A plugin for [Phergie](http://github.com/phergie/phergie-irc-bot-react/) to
join channels when connecting to servers.

[![Build Status](https://secure.travis-ci.org/phergie/phergie-irc-plugin-react-autojoin.png?branch=master)](http://travis-ci.org/phergie/phergie-irc-plugin-react-autojoin)

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "phergie/phergie-irc-plugin-react-autojoin": "1.0.*"
    }
}
```

See Phergie documentation for more information on installing plugins.

## Configuration

```php
new \Phergie\Irc\Plugin\React\AutoJoin\Plugin(array(

    // Required: list of channels to join
    'channels' => '#channel1,#channel2,#channelN',
    // or
    'channels' => array('#channel1', '#channel2', '#channelN'),

    // Optional: channel keys
    'keys' => 'key1,key2,keyN',
    // or
    'keys' => array('key1', 'key2', 'keyN'),

))
```

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
cd tests
../vendor/bin/phpunit
```

## License

Released under the BSD License. See `LICENSE`.
