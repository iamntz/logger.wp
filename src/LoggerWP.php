<?php

namespace iamntz\loggerWP;

use \Katzgrau\KLogger\Logger;
use \Psr\Log\LogLevel;

class LoggerWP
{

/*
$this->log->emergency('Message', []);
$this->log->alert('Message', []);
$this->log->critical('Message', []);
$this->log->error('Message', []);
$this->log->warning('Message', []);
$this->log->notice('Message', []);
$this->log->info('Message', []);
$this->log->debug('Message', []);
 */

	/**
	 * Magic method to wrap the available log levels
	 *
	 * @method __call
	 */
	public function __call($errorLevel, $args)
	{
		if (empty($args[0])) {
			return;
		}

		if (!isset($args[1])) {
			$args[1] = [];
		}

		if (!isset($args[2])) {
			// adding a third argument to allow a more granular error logging
			$args[2] = true;
		}

		$errorLevel = $this->getValidErrorLevel($errorLevel);

		if (!$errorLevel) {
			return;
		}

		if ($args[2]) {
			$this->writeError($args[0], $args[1], $errorLevel);
		}
	}

	/**
	 * Gets last `$limit` errors
	 *
	 * @method getLogs
	 *
	 * @param  integer   $limit
	 * @param  string    $level what level of errors should return
	 *
	 * @return string
	 */
	public function getLogs($limit = 10, $level = null)
	{
		$this->maybeCreateDirStucture();
		$lines = file($this->getPath($this->_getLogFilename()));
		$pattern = "/^\[[\d]{4}-\d{2}-\d{2}\s\d{1,2}:\d{2}:\d{2}\]\s\[(\w+)\]/im";

		$errors = [];

		$currentLevel = null;
		$currentIndex = null;

		foreach ((array) $lines as $key => $line) {
			preg_match($pattern, $line, $matches);
			$includeError = is_null($level) || $currentLevel === $level;

			if (!empty($matches)) {
				$currentLevel = $matches[1];
				$currentIndex = $key;

				$separatorClassName = apply_filters('iamntz/loggerwp/logs-separator-class-name', 'loggerwp');
				$separator = apply_filters(
					'iamntz/loggwerwp/logs/separator-markup',
					sprintf('<div class="%2$s--separator %2$s--errorLevel-%1$s" data-text="%1$s"></div>', $currentLevel, $separatorClassName)
				);

				$errors[$currentLevel][$currentIndex][] = "\n" . $separator;
			}

			if (isset($errors[$currentLevel][$currentIndex])) {
				$errors[$currentLevel][$currentIndex][] = $line;
			}
		}

		if ($level !== 'all' && isset($errors[$level])) {
			$errors = $errors[$level];
		}

		$limitedResults = array_slice($errors, -$limit, $limit);
		$limitedResults = implode("\n", $this->array_flatten($limitedResults));

		return preg_replace("~\n\n~", "\n", $limitedResults);
	}

	/**
	 * Deletes the user log file
	 *
	 * @method deleteLogFile
	 */
	public function deleteLogFile()
	{
		if (file_exists($this->getPath($this->_getLogFilename()))) {
			unlink($this->getPath($this->_getLogFilename()));
		}
	}

	/**
	 * Flatten multidimensional array
	 *
	 * @method array_flatten
	 *
	 * @param  array        $array
	 *
	 * @return array
	 */
	private function array_flatten($array)
	{
		$return = [];
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$return = array_merge($return, $this->array_flatten($value));
			} else {
				$return[$key] = $value;
			}
		}

		return $return;
	}

	/**
	 * Write the error to the error file
	 *
	 * @method writeError
	 *
	 * @param  string     $message    The error message
	 * @param  array      $details    The error details
	 * @param  string     $errorLevel
	 */
	private function writeError($message, $details = [], $errorLevel)
	{
		$this->maybeCreateDirStucture();
		$fileName = $this->_getLogFilename();

		$logger = new Logger($this->getPath(), $errorLevel, [
			'filename' => $this->_getLogFilename(),
			'extension' => '',
			'dateFormat' => 'Y-m-d G:i:s',
		]);

		if (is_a($details, '\StdClass')) {
			$details = json_decode(json_encode($details), true);
		}

		$details = array_filter($details);
		$message = trim($message);

		call_user_func_array([$logger, $errorLevel], [$message, $details]);
	}

	/**
	 * Gets the log path
	 *
	 * @method getPath
	 *
	 * @param  string  $append optional file name
	 *
	 * @return string  log path
	 */
	private function getPath($append = '')
	{
		$upload = wp_upload_dir();

		return $upload['basedir'] . '/' . $this->getLogsFolderName() . '/' . $append;
	}

	/**
	 * Gets log folder name
	 *
	 * @method getLogsFolderName
	 *
	 * @return string
	 */
	protected function getLogsFolderName()
	{
		return apply_filters('iamntz/loggerwp/log-path', 'loggerwp');
	}

	/**
	 * Generate user log file name
	 *
	 * @method _getLogFilename
	 *
	 * @return string
	 */
	private function _getLogFilename()
	{
		$user = wp_get_current_user();
		$userLogFileName = $user->user_login . '-' . date('W-Y');

		if (defined('AUTH_SALT') && !empty(AUTH_SALT)) {
			$userLogFileName .= '___' . substr(sha1(AUTH_SALT), 1, 10);
		}

		return $this->getLogFilename($userLogFileName, $user);
	}

	/**
	 * Apply filter to user filename
	 *
	 * @method getLogFilename
	 *
	 * @param  string         $userLogFileName
	 * @param  \WP_User       $user
	 *
	 * @return sting
	 */
	protected function getLogFilename($userLogFileName, $user)
	{
		return apply_filters('iamntz/loggerwp/log-file', "${userLogFileName}.log", $user);
	}

	/**
	 * Generates folder structure needed for logging
	 *
	 * @method maybeCreateDirStucture
	 */
	private function maybeCreateDirStucture()
	{
		if (!is_dir($this->getPath())) {
			wp_mkdir_p($this->getPath());
		}

		$this->maybeCreateFile('index.php');
		$this->maybeCreateFile($this->_getLogFilename());
	}

	/**
	 * Create a file if that file is not available
	 *
	 * @method maybeCreateFile
	 *
	 * @param  string          $fileName
	 */
	private function maybeCreateFile($fileName)
	{
		if (!file_exists($this->getPath($fileName))) {
			$file = fopen($this->getPath($fileName), 'w') or die("Can't open file `{$fileName}`");
			fclose($file);
		}
	}

	/**
	 * Validate error levels
	 *
	 * @method getValidErrorLevel
	 *
	 * @param  string             $level
	 *
	 * @return string|boolean
	 */
	private function getValidErrorLevel($level)
	{
		$errorLevels = [
			LogLevel::EMERGENCY,
			LogLevel::ALERT,
			LogLevel::CRITICAL,
			LogLevel::ERROR,
			LogLevel::WARNING,
			LogLevel::NOTICE,
			LogLevel::INFO,
			LogLevel::DEBUG,
		];

		if (!in_array($level, $errorLevels)) {
			return;
		}

		return $level;
	}

}
