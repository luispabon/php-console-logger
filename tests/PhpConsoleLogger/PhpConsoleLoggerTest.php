<?php
namespace PhpConsoleLogger\Tests;

use PhpConsoleLogger\Console\Logger;
use Psr\Log\LogLevel;

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
}
