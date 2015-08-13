README
======

What is PhpConsoleLogger?
-----------------
PhpConsoleLogger is a small class that implements a way to display messages in a console with an interface which is
PSR-3 compatible. This means you can drop it in as a logger on code that is PSR-3 LoggerAwareInterface compatible and away
you go.

This logger is really meant to be used for command line scripts due to shell-specific colouring commands.

You might want to use this on those command line scripts we all use to do things like migrations, data fixes etc; without
the overhead of adding a full-on PHP console suite.

Requirements
------------
Very little: PHP 5.4+.

Installation
------------
The preferred method is through composer, via ```composer require luispabon/PhpConsoleLogger```. You can always download
and install manually, but you'd need to somehow shoehorn both psr/log and PhpConsoleLogger into your autoload mechanism.

Contributing
------------
If you use Github, you already know the drill: fork, branch and pull request.

Examples
-------------
I have provided with an example (code and output below) you can run by running ```php example/example.php```.

```php
<?php
require '../vendor/autoload.php';
require 'ExampleClass.php';

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

// Messages with exceptions and traces
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

// Passing on an exception directly as a message (or any object that implements __toString)
$console->debug($ex);

// Since we're PSR-3, we can be injected on objects that understand LoggerAwareInterface - example class does
$exampleClass->setLogger($console);
$exampleClass->runLoggerAwareExample();

// You get the idea
$console->notice('That\'s it.');
$console->info('C\'est fini.');
```

Which looks like:
![](example/example.png)
