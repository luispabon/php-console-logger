<?php
namespace PhpConsoleLogger\Console;

use Psr\Log\LogLevel;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;

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
 * @link      https://github.com/luispabon/PhpConsoleLogger
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 * @version   0.1-dev
 * @package   PhpConsoleLogger
 */
class Logger extends AbstractLogger
{
    /**
     * Console-coloured prefixes to add to log messages.
     *
     * @var array
     */
    private $logPrefixesPerLevel = [
        LogLevel::INFO      => '1;32m [ Info ]     ',
        LogLevel::NOTICE    => '1;35m [ Notice ]   ',
        LogLevel::DEBUG     => '1;34m [ Debug ]    ',
        LogLevel::WARNING   => '1;33m [ Warning ]  ',
        LogLevel::ALERT     => '3;33m [ Alert ]    ',
        LogLevel::ERROR     => '1;31m [ Error ]    ',
        LogLevel::EMERGENCY => '3;33m [ Emergency ]',
        LogLevel::CRITICAL  => '1;31m [ Critical ] ',
    ];

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
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = [])
    {
        // Do not allow users to supply nonsense on the log level
        if (array_key_exists($level, $this->logPrefixesPerLevel) === false) {
            throw new InvalidArgumentException('Logger method not recognised');
        }

        // Parse message into a string we can use
        $parsedMessage = $this->parseMessage($message);

        // Examine context, and alter the message accordingly
        $parsedMessage .= $this->parseContext($context);

        // Speak!
        $this->outputToConsole($level, $parsedMessage);
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
    private function parseMessage($message)
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
            case 'null':
            case 'object':
                $parsedMessage = (string) $message;
                break;

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
     * @param array $context
     *
     * @return string
     *
     * @see: http://www.php-fig.org/psr/psr-3/#1-3-context
     */
    private function parseContext(array $context)
    {
        $parsedContext = '';
        $contextCopy   = $context;

        // Exception?
        if (array_key_exists('exception', $contextCopy) === true && $contextCopy['exception'] instanceof \Exception === true) {
            $exception = $contextCopy['exception'];

            // Construct
            $parsedContext .= ' / Exception: ' . get_class($exception);
            $parsedContext .= '; message: ' . $exception->getMessage();
            $parsedContext .= '; at: ' . $exception->getFile() . '@' . $exception->getLine();
            $parsedContext .= '; trace: ' . json_encode($exception->getTrace());
            unset($contextCopy['exception']);
        }

        // Anything else?
        if (count($contextCopy) > 0) {
            $parsedContext .= ' / Context: ' . json_encode($context);
        }

        return $parsedContext;
    }

    /**
     * Actually write to console.
     *
     * @param string $level
     * @param string $message
     */
    private function outputToConsole($level, $message)
    {
        // Output to console
        $message = "\033[" . $this->logPrefixesPerLevel[$level] . "\033[0m " . $message;
        echo $message, PHP_EOL;
        flush();
    }
}
