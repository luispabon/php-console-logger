```php
<?php
require '../vendor/autoload.php';

$console = new PhpConsoleLogger\Console\Logger();

// Straight string messages
$console->info('This is an info message');
$console->notice('This is a notice message');
$console->debug('This is a debug message');
$console->warning('This is a warning message');
$console->alert('This is an alert message');
$console->error('This is an error message');
$console->emergency('This is an emergency message');
$console->critical('This is a critical message');

// Messages with exceptions
try {
    $exampleClass = new ExampleClass();
    $exampleClass->prepare();
} catch (Exception $ex) {
    $console->error('Whoopsies', ['exception' => $ex]);
}

// Messages with random data
$console->warning('Some data on context', ['foo' => 'bar']);

// Messages with random data plus exception
$console->alert('Some data on context, as well as an exception', ['foo' => 'bar', 'exception' => $ex]);

// Passing on an exception directly as a message
$console->debug($ex);

// You get the idea
$console->notice('That\'s it.');
$console->info('C\'est fini.');

class ExampleClass
{
    public function prepare()
    {
        $this->doSomething();
    }

    private function doSomething()
    {
        throw new BadMethodCallException('Something dodgy has just happened');
    }
}
```

Which looks like:
![](https://github.com/luispabon/PhpConsoleLogger/raw/dev/example/example.png)
