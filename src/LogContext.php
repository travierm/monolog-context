<?php

namespace Cumulati\Monolog;

use Monolog\Logger;

class LogContext
{
	use CtxId;
	use Levels;
	use Timers;
	use Counters;

	/**
	 * The default logger to use
	 *
	 * @var Logger
	 */
	private static $defaultLogger = null;

	/**
	 * The logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * The context
	 *
	 * @var array
	 */
	private $context = [];

	/**
	 * Parent context, if extended
	 *
	 * @var LogContext
	 */
	private ?LogContext $parent = null;

	/**
	 * Instantiate a new LogContext
	 *
	 * @param array $ctx         initial context
	 * @param LogContext $parent the parent context to extend
	 */
	public function __construct(
		array $ctx = [],
		?LogContext $parent = null,
	) {
		$this->parent = $parent;
		$this->ctxId = $this->generateCtxId();

		$this->addContext($ctx);
	}

	/**
	 * Make a new LogContext
	 *
	 * @param array $ctx         initial context
	 * @param LogContext $parent the parent context to extend
	 */
	public static function make(
		array $ctx = [],
		?LogContext $parent = null,
	) {
		return new static($ctx, $parent);
	}

	/**
	 * Set the logger
	 *
	 * @param Logger $logger
	 */
	public function setLogger(Logger $logger = null)
	{
		$this->logger = $logger;
	}

	/**
	 * Set the default logger
	 *
	 * @param Logger $logger
	 */
	public static function setDefaultLogger(Logger $logger = null)
	{
		static::$defaultLogger = $logger;
	}

	/**
	 * Get the logger. Return default logger if none
	 *
	 * @return Logger
	 */
	public function getLogger(): ?Logger
	{
		if ($this->logger) {
			return $this->logger;
		}

		return static::$defaultLogger;
	}

	/**
	 * Add context
	 *
	 * @param array $context the context to add
	 */
	public function addContext(array $context)
	{
		if (empty($context)) {
			return;
		}

		if (array_key_exists($this->getTimerKey(), $context)) {
			unset($context[$this->getTimerKey()]);
		}

		if (array_key_exists($this->getCounterKey(), $context)) {
			unset($context[$this->getCounterKey()]);
		}

		$this->context = array_replace_recursive($this->context, $context);
	}

	/**
	 * Delete context
	 *
	 * @param string|array $keys the keys in which to delete
	 */
	public function deleteContext(...$keys)
	{
		foreach ($keys as $k) {
			if (is_array($k)) {
				return $this->deleteContext(...$k);
			}

			unset($this->context[$k]);
		}
	}

	/**
	 * Set the context, replacing existing context
	 *
	 * @param array $context the context
	 */
	public function setContext(array $context = [])
	{
		$this->context = $context;
	}

	/**
	 * Get the context
	 *
	 * @return array
	 */
	public function getContext(): array
	{
		return $this->context;
	}

	/**
	 * Write a message to the log.
	 *
	 * @param  string  $level
	 * @param  string  $message
	 * @param  array   $context
	 * @return void
	 */
	private function writeLog($level, $message, $context)
	{
		$logger = $this->getLogger();
		if (empty($logger)) {
			return;
		}

		$counts = $this->applyCounters($context);
		$timers = $this->applyTimers($context);
		$context = array_replace_recursive($this->context, $context);

		if (!empty($counts)) {
			$context[$this->getCounterKey()] = $counts;
		}

		if (!empty($timers)) {
			$context[$this->getTimerKey()] = $timers;
		}

		if ($this->getAppendCtxId()) {
			$context[$this->getCtxIdKey()] = $this->getCtxId();
		}

		$logger->{$level}($message, $context);
	}
}
