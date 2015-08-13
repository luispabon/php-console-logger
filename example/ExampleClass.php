<?php
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ExampleClass implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function prepare()
    {
        $this->doSomething();
    }

    private function doSomething()
    {
        throw new BadMethodCallException('Something dodgy has just happened');
    }

    public function runLoggerAwareExample()
    {
        // Do stuff
        $this->logger->info('This message comes from a class that understands LoggerAwareInterface');
    }
}