## What is this?

A small utility that will allow you to easily log various errors per user basis. Basically this is a wrapper for [katzgrau/klogger](https://github.com/katzgrau/KLogger).

## Installing

```
composer require iamntz/logger.wp
```

#### Customizing paths and names

By default, logs will be saved in `wp-content/uploads/loggerwp/` folder. You can change this by using the `iamntz/loggerwp/log-path` hook. E.g.:

```
add_filter('iamntz/loggerwp/log-path', function(){ return 'my-awesome-path'; });
```

You can also change the log file name. By default is *per user* and it follows the pattern: `ID-week-year-AUTH_SALT` (`AUTH_SALT` being hashed).


## Using

You have several error levels, following [PSR3 specs](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md):

```
$logs = new LoggerWP;

$logs->emergency('Message', [], $enabled);
$logs->alert('Message', [], $enabled);
$logs->critical('Message', [], $enabled);
$logs->error('Message', [], $enabled);
$logs->warning('Message', [], $enabled);
$logs->notice('Message', [], $enabled);
$logs->info('Message', [], $enabled);
$logs->debug('Message', [], $enabled);
```

Each method receives the same arguments: a text message, an array and a boolean. Last two are optional.

## Reading logs

You can also read logs:

```
$logs = new LoggerWP;

$logs->getErrors(50, 'warning'); // gets last 50 warnings
$logs->getErrors(5, 'debug'); // gets last 50 debug messages
```


#### Hints

You could define various levels of verbosity:

```
// in your plugin/theme file

if (!defined('MY_PLUGIN_VERBOSE_LEVEL')) {
  define('MY_PLUGIN_VERBOSE_LEVEL', WP_DEBUG);
}

define('MY_PLUGIN_VERBOSE_LEVEL__VVV', MY_PLUGIN_VERBOSE_LEVEL === 'vvv' );
define('MY_PLUGIN_VERBOSE_LEVEL__VV', MY_PLUGIN_VERBOSE_LEVEL__VVV || MY_PLUGIN_VERBOSE_LEVEL === 'vv' );
define('MY_PLUGIN_VERBOSE_LEVEL__V', MY_PLUGIN_VERBOSE_LEVEL__VV || MY_PLUGIN_VERBOSE_LEVEL === 'v' );
```

Then you could define `MY_PLUGIN_VERBOSE_LEVEL` constant in your `wp-config.php` file. And finally, you can use it:

```
$logs->debug('Message', [], MY_PLUGIN_VERBOSE_LEVEL__VVV);
$logs->alert('Message', [], MY_PLUGIN_VERBOSE_LEVEL);
$logs->emergency('Fatal error!');
```


## Like it?

You can get [hosting](https://m.do.co/c/c95a44d0e992), [donate](https://www.paypal.me/iamntz) or buy me a [gift](http://iamntz.com/wishlist).

## License

MIT.
