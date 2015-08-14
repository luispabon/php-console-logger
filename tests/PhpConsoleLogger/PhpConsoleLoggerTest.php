<?php
namespace PhpConsoleLogger\Tests;

use PhpConsoleLogger\Console\Logger;
use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Exception\Exception;

class PhpConsoleLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    public function setUp()
    {
        $this->logger = $this->getMock('PhpConsoleLogger\Console\Logger', ['output', 'format']);
    }

    /**
     * @test
     * @dataProvider validScalarValues
     */
    public function logAcceptsScalarValues($value)
    {
        $logLevel = LogLevel::INFO;
        $message = (string) $value;

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
        $message = (string) $object;

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
            [new Exception()],
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
