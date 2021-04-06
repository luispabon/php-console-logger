<?php
declare(strict_types = 1);

namespace AuronConsultingOSS\Logger;

use DateTime;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Throwable;

/**
 * PhpConsoleLogger - a simple PSR-3 compliant console logger.
 *
 * I wrote this originally to provide with a simple way to speak to the console from command line scripts in a colour
 * coded way. Colour coding is compatible with the majority of UNIX shells. Untested with Windows console.
 *
 * This class implements Psr\Log\LoggerInterface, you should be able to feed instances to any LoggerAwareInterface
 * classes you want to use this with.
 *
 * @author    Luis Pabon <luis.pabon@auronconsulting.co.uk>
 * @copyright 2015 Luis Pabon
 * @link      https://github.com/AuronConsultingOSS/PhpConsoleLogger
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @package   PhpConsoleLogger
 */
class Console extends AbstractLogger
{
    /**
     * Console-coloured prefixes to add to log messages.
     *
     * @var string[]
     */
    private $logPrefixesPerLevel = [
        LogLevel::INFO      => '1;32m [ Info %s]     ',
        LogLevel::NOTICE    => '1;35m [ Notice %s]   ',
        LogLevel::DEBUG     => '1;34m [ Debug %s]    ',
        LogLevel::WARNING   => '1;33m [ Warning %s]  ',
        LogLevel::ALERT     => '3;33m [ Alert %s]    ',
        LogLevel::ERROR     => '1;31m [ Error %s]    ',
        LogLevel::EMERGENCY => '3;33m [ Emergency %s]',
        LogLevel::CRITICAL  => '1;31m [ Critical %s] ',
    ];

    /**
     * @var bool
     */
    private $enableTimestamp;

    /**
     * @codeCoverageIgnore
     */
    public function __construct(bool $enableTimestamp = true)
    {
        $this->enableTimestamp = $enableTimestamp;
    }

    /**
     * Logs to an arbitrary log level.
     *
     * We only recognise info/notice/debug/warning/alert/error/emergency/critical levels, anything else will
     * throw an InvalidArgumentException. Please provide log level via Psr\Log\LogLevel constants to ensure goodness.
     *
     * Message must be a string-able value.
     *
     * Context is optional and can contain any arbitrary data as an array. If providing an exception, you MUST provide
     * it within a key of 'exception'.
     *
     * @see: http://www.php-fig.org/psr/psr-3/#1-2-message
     * @see: http://www.php-fig.org/psr/psr-3/#1-3-context
     *
     * @param mixed   $level
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void|null
     *
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = [])
    {
        // Do not allow users to supply nonsense on the log level
        if (array_key_exists($level, $this->logPrefixesPerLevel) === false) {
            throw new InvalidArgumentException('Console method not recognised');
        }

        // Parse message into a string we can use
        $parsedMessage = $this->parseMessage($message);

        // Examine context, and alter the message accordingly
        $parsedMessage .= $this->parseContext($context);

        // Speak!
        $this->output($this->format($level, $parsedMessage));
    }

    /**
     * Parses the log message and returns as a useable string, or Psr\Log\InvalidArgumentException if non parseable.
     *
     * We only recognise info/notice/debug/warning/alert/error/emergency/critical levels, anything else will
     * throw an InvalidArgumentException.
     *
     * @param mixed $message
     *
     * @return string
     *
     * @throws InvalidArgumentException
     *
     * @see: http://www.php-fig.org/psr/psr-3/#1-2-message
     */
    private function parseMessage($message): string
    {
        $parsedMessage = null;

        /**
         * According to PSR-3 we can accept string-like values (eg stuff we can parse easily into a string, such as obviously
         * strings and numbers, and objects that can be cast to strings).
         */
        switch (gettype($message)) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'string':
            case 'NULL':
                $parsedMessage = (string) $message;
                break;

            case 'object':
                if (method_exists($message, '__toString') !== false) {
                    $parsedMessage = (string) $message;
                    break;
                }
            // Otherwise, go on to default below

            default:
                throw new InvalidArgumentException('Message can only be a string, number or an object which can be cast to a string');
        }

        return $parsedMessage;
    }

    /**
     * Parses the context array and returns a useable string we can pipe to the console. It will extract and parse
     * exceptions into a useful string. Any array like values within (exception trace, or anything other than an
     * exception) will be json encoded.
     *
     * @param mixed[] $context
     *
     * @return string
     *
     * @see: http://www.php-fig.org/psr/psr-3/#1-3-context
     */
    private function parseContext(array $context): string
    {
        $contextCopy   = $context;
        $exceptionText = '';
        $extraContext  = '';

        // Parse exception out into a string
        if (
            array_key_exists('exception', $contextCopy) === true &&
            $contextCopy['exception'] instanceof Throwable === true
        ) {
            $exception = $contextCopy['exception'];

            // Construct
            $format        = ' / Exception: %s; message: %s; trace: %s';
            $exceptionText = sprintf(
                $format,
                get_class($exception),
                $exception->getMessage(),
                json_encode($exception->getTrace())
            );

            // Remove exception to avoid showing up on context below
            unset($contextCopy['exception']);
        }

        // Anything else?
        if (count($contextCopy) > 0) {
            $extraContext = ' / Context: ' . json_encode($contextCopy);
        }

        $parsedContext = sprintf(
            '%s%s',
            $exceptionText,
            $extraContext
        );

        // Add an extra separator in case we've added stuff in here for readability
        if ($parsedContext !== '') {
            $parsedContext .= PHP_EOL;
        }

        return $parsedContext;
    }

    /**
     * Format message for the console.
     *
     * @param string $level
     * @param string $message
     *
     * @return string
     */
    protected function format(string $level, string $message): string
    {
        $timestamp = '';
        if ($this->enableTimestamp === true) {
            $timestamp = sprintf("\e[1m- %s ", (new DateTime())->format(DATE_ATOM));
        }

        // Evaluate %s within the log prefix to add formatted timestamp to
        $prefix = sprintf($this->logPrefixesPerLevel[$level], $timestamp);

        // Double quotes are important to avoid needing to insert the unicode equivalent
        return sprintf("\e[%s\e[0m %s%s", $prefix, $message, PHP_EOL);
    }

    /**
     * Actually output now to STDOUT.
     *
     * @param string $string
     *
     * @codeCoverageIgnore cannot test writing to stdout, so ignore coverage here
     */
    protected function output(string $string): void
    {
        fwrite(STDOUT, $string);
    }
}
