<?php
namespace PhpConsoleLogger\Tests;

use AuronConsultingOSS\PhpConsoleLogger\Logger;
use Psr\Log\LogLevel;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    public function setUp()
    {
        $this->logger = $this->getMock('AuronConsultingOSS\PhpConsoleLogger\Logger', ['output', 'format']);
    }

    /**
     * @test
     * @dataProvider validScalarValues
     */
    public function logAcceptsScalarValues($value)
    {
        $logLevel = LogLevel::INFO;
        $message  = (string) $value;

        $this->logger
            ->expects(self::once())
            ->method('format')
            ->with($logLevel, $value)
            ->will(self::returnValue($message));

        $this->logger
            ->expects(self::once())
            ->method('output')
            ->with($message);

        $this->logger->log($logLevel, $value);
    }

    /**
     * @test
     * @expectedException \Psr\Log\InvalidArgumentException
     * @expectedExceptionMessage Message can only be a string, number or an object which can be cast to a string
     */
    public function logDoesntAcceptUnstringableObjects()
    {
        $this->logger->log(LogLevel::ALERT, new \stdClass());
    }

    /**
     * @test
     * @dataProvider validStringableObjects
     */
    public function logAcceptsStringableObjects($object)
    {
        $logLevel = LogLevel::INFO;
        $message  = (string) $object;

        $this->logger
            ->expects(self::once())
            ->method('format')
            ->with($logLevel, $object)
            ->will(self::returnValue($message));

        $this->logger
            ->expects(self::once())
            ->method('output')
            ->with($message);

        $this->logger->log($logLevel, $object);
    }

    /**
     * @test
     * @expectedException \Psr\Log\InvalidArgumentException
     * @expectedExceptionMessage Logger method not recognised
     */
    public function logDoesntAcceptNonsensicalLogLevels()
    {
        $this->logger->log(uniqid('foo', true), 'foo');
    }

    /**
     * @test
     */
    public function logParsesExceptionInContext()
    {
        $exceptionMessage = 'foo';
        $message          = 'bar';
        $logLevel         = LogLevel::CRITICAL;

        $exception = new \Exception($exceptionMessage);

        $context = ['exception' => $exception];

        // Bit of a stretch to check for the exception trace and all, just check the general format and contents
        $argumentCheckCallback = function ($arg) use ($message, $exception) {
            $pattern = sprintf('/^%s \/ Exception\: %s\; message\: %s\; trace\:/', $message, get_class($exception), $exception->getMessage());

            return preg_match($pattern, $arg) === 1;
        };

        $this->logger
            ->expects(self::once())
            ->method('format')
            ->with($logLevel, self::callback($argumentCheckCallback))
            ->will(self::returnValue($message));

        $this->logger
            ->expects(self::once())
            ->method('output')
            ->with($message);

        $this->logger->log($logLevel, $message, $context);
    }

    /**
     * @test
     */
    public function logParsesContextWithoutException()
    {
        $context  = ['foo' => 'bar'];
        $message  = 'foobar';
        $logLevel = LogLevel::INFO;

        // Bit of a stretch to check for the exception trace and all, just check the general format and contents
        $argumentCheckCallback = function ($arg) use ($message, $context) {
            $pattern = sprintf('/^%s \/ Context\: %s/', $message, json_encode($context));

            return preg_match($pattern, $arg) === 1;
        };

        $this->logger
            ->expects(self::once())
            ->method('format')
            ->with($logLevel, self::callback($argumentCheckCallback))
            ->will(self::returnValue($message));

        $this->logger
            ->expects(self::once())
            ->method('output')
            ->with($message);

        $this->logger->log($logLevel, $message, $context);
    }

    /**
     * @test
     */
    public function formatGeneratesCorrectEntryHeader()
    {
        $message  = 'foobar';
        $logLevel = LogLevel::WARNING;

        // Not mocking format this time
        $logger = $this->getMock('AuronConsultingOSS\PhpConsoleLogger\Logger', ['output']);

        // Bit of a stretch to check for the exception trace and all, just check the general format and contents
        $argumentCheckCallback = function ($arg) use ($logLevel, $message) {
            self::assertRegExp("/^\\033\[/", $arg);
            self::assertRegExp("/{$logLevel}/i", $arg);
            self::assertRegExp("/{$message}/", $arg);
            self::assertRegExp("/\\033\[0m/", $arg);

            return true;
        };

        $logger
            ->expects(self::once())
            ->method('output')
            ->with(self::callback($argumentCheckCallback));

        $logger->log($logLevel, $message);
    }

    /**
     * @test
     */
    public function logIsVoid()
    {
        // There doesn't seem any way at all to silence (or capture, even with ob) stdout
        // and I don't want any boilerplate logic to inject fake in memory output - the following
        // silences the logger completely, in a hackish sort of way
        $logger = $this->getMock('AuronConsultingOSS\PhpConsoleLogger\Logger', ['format']);

        self::assertNull($logger->log(LogLevel::WARNING, 'foobar'));
    }


    /***** Data providers *****/

    private $scalarValues = [
        [19.8],
        ['foo'],
        [-8],
        [null],
        [''],
        [false],
        [true],
    ];

    public function validScalarValues()
    {
        return $this->scalarValues;
    }

    public function validStringableObjects()
    {
        return [
            [new stringableClass()],
            [new \Exception()],
        ];
    }
}

class stringableClass
{
    public function __toString()
    {
        return 'foo';
    }
}
