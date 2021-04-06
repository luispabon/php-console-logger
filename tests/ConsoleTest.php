<?php

namespace Console\Tests;

use AuronConsultingOSS\Logger\Console;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

class ConsoleTest extends TestCase
{
    /**
     * @var Console|MockObject
     */
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->getMockBuilder(Console::class)->onlyMethods(['output', 'format'])->getMock();
    }

    /**
     * @test
     * @dataProvider validScalarValues
     */
    public function logAcceptsScalarValues($value): void
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
     */
    public function logDoesntAcceptUnstringableObjects(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message can only be a string, number or an object which can be cast to a string');

        $this->logger->log(LogLevel::ALERT, new \stdClass());
    }

    /**
     * @test
     * @dataProvider validStringableObjects
     */
    public function logAcceptsStringableObjects($object): void
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
     */
    public function logDoesntAcceptNonsensicalLogLevels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Console method not recognised');

        $this->logger->log(uniqid('foo', true), 'foo');
    }

    /**
     * @test
     */
    public function logParsesExceptionInContext(): void
    {
        $exceptionMessage = 'foo';
        $message          = 'bar';
        $logLevel         = LogLevel::CRITICAL;

        $exception = new \Exception($exceptionMessage);

        $context = ['exception' => $exception];

        // Bit of a stretch to check for the exception trace and all, just check the general format and contents
        $argumentCheckCallback = function ($arg) use ($message, $exception) {
            $pattern = sprintf('/^%s \/ Exception\: %s\; message\: %s\; trace\:/', $message, get_class($exception),
                $exception->getMessage());

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
     * @dataProvider nonExceptionContexts
     */
    public function logParsesContextWithoutException(array $context): void
    {
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
    public function formatGeneratesCorrectEntryHeaderWithoutTimestamps(): void
    {
        $message  = 'foobar';
        $logLevel = LogLevel::WARNING;

        // Not mocking format this time
        $logger = $this->getMockBuilder(Console::class)->onlyMethods(['output'])->getMock();

        // Bit of a stretch to check for the exception trace and all, just check the general format and contents
        $argumentCheckCallback = function ($arg) use ($logLevel, $message) {
            self::assertMatchesRegularExpression("/^\\033\[/", $arg);
            self::assertMatchesRegularExpression("/{$logLevel}/i", $arg);
            self::assertMatchesRegularExpression("/{$message}/", $arg);
            self::assertMatchesRegularExpression("/\\033\[0m/", $arg);

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
    public function formatGeneratesCorrectEntryHeaderWithTimestamps(): void
    {
        $message  = 'foobar';
        $logLevel = LogLevel::WARNING;

        // Not mocking format this time
        $logger = $this
            ->getMockBuilder(Console::class)
            ->onlyMethods(['output'])
            ->setConstructorArgs([true])
            ->getMock();

        // Bit of a stretch to check for the exception trace and all, just check the general format and contents
        $argumentCheckCallback = function ($arg) use ($logLevel, $message) {
            self::assertMatchesRegularExpression("/^\\033\[/", $arg);
            self::assertMatchesRegularExpression("/{$logLevel}/i", $arg);
            self::assertMatchesRegularExpression("/{$message}/", $arg);
            self::assertMatchesRegularExpression("/\\033\[0m/", $arg);
            self::assertMatchesRegularExpression(
                '/(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})\+(\d{2})\:(\d{2})/',
                $arg
            );

            return true;
        };

        $logger
            ->expects(self::once())
            ->method('output')
            ->with(self::callback($argumentCheckCallback));

        $logger->log($logLevel, $message);
    }

    /***** Data providers *****/

    private $scalarValues = [
        'float'            => [19.8],
        'string'           => ['foo'],
        'negative integer' => [-8],
        'null'             => [null],
        'empty string'     => [''],
        'false'            => [false],
        'true'             => [true],
    ];

    public function validScalarValues(): array
    {
        return $this->scalarValues;
    }

    public function validStringableObjects(): array
    {
        return [
            'stringable class'  => [new stringableClass()],
            'generic exception' => [new \Exception()],
        ];
    }

    public function nonExceptionContexts(): array
    {
        return [
            'no exception on context'                  => [['foo' => 'bar']],
            'exception key does not contain exception' => [['exception' => 'yes']],
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
