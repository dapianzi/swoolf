# swoolf
a simple swoole server framework

### App

### Loader
autoload class.     
register customer namespace dir root by `Loader::regNamespace()`
```php
\Swoolf\Loader::regNamespace('Custom', APP_PATH.'/custom');
// your class file : "APP_PATH/custom/myClass.php"
$cls = new \Custom\myClass();

```

### Facade
use other class/instance as facade of App().
```php
$app = new App();
$app->facade::reg('facade_name', ClassName);
$app->facade_name::echo('use Log by facade in app');
```

### Event
event listener
```php
\Swoolf\Event::add('actionBefore', function(){
    echo 'Before';
});
// somewhere
function Action() {
    // before action
    \Swoolf\Event::emit('actionBefore', $args);
    // do action 
    // after action
    \Swoolf\Event::emit('actionAfter', $args);
}
// remove event listener
// anonymous function can't be removed.
\Swoolf\Event::remove('actionBefore', 'func_name');
```

### Log
runtime log with 5 levels:
+ echo
+ err
+ warm
+ info
+ ok

### Utils
useful tools.

### Vendor
custom library dir.
