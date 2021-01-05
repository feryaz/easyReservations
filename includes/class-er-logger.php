<?php
/**
 * Provides logging capabilities for debugging purposes.
 *
 * @class          ER_Logger
 * @package        easyReservations/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Logger class.
 */
class ER_Logger {

	/**
	 * Cache logs that could not be written.
	 *
	 * If a log is written too early in the request, pluggable functions may be unavailable. These
	 * logs will be cached and written on 'plugins_loaded' action.
	 *
	 * @var array
	 */
	protected $cache = array();

	/**
	 * Stores open file handles.
	 *
	 * @var array
	 */
	protected $handles = array();

	/**
	 * How long a log file should get
	 *
	 * @var int
	 */
	protected $log_size_limit = 5 * 1024 * 1024;

	/**
	 * ER_Logger constructor.
	 *
	 * @param null $log_size_limit
	 */
	public function __construct( $log_size_limit = null ) {
		add_action( 'plugins_loaded', array( $this, 'write_cached_logs' ) );
	}

	/**
	 * Destructor.
	 *
	 * Cleans up open file handles.
	 */
	public function __destruct() {
		foreach ( $this->handles as $handle ) {
			if ( is_resource( $handle ) ) {
				fclose( $handle ); // @codingStandardsIgnoreLine.
			}
		}
	}

	/**
	 * Add a log entry
	 *
	 * @param string       $level
	 * @param string       $message
	 * @param string|array $context
	 */
	public function log( $level, $message, $context = 'log' ) {
		if ( is_string( $context ) ) {
			$context = array(
				'source' => $context,
			);
		}

		$this->handle( current_time( 'timestamp', 1 ), $level, $message, $context );
	}

	/**
	 * Adds an emergency level message.
	 *
	 * System is unusable.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 *
	 * @see ER_Logger::log
	 *
	 */
	public function emergency( $message, $handle = 'log' ) {
		$this->log( ER_Log_Levels::EMERGENCY, $message, $handle );
	}

	/**
	 * Adds an alert level message.
	 *
	 * Action must be taken immediately.
	 * Example: Entire website down, database unavailable, etc.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 *
	 * @see ER_Logger::log
	 *
	 */
	public function alert( $message, $handle = 'log' ) {
		$this->log( ER_Log_Levels::ALERT, $message, $handle );
	}

	/**
	 * Adds a critical level message.
	 *
	 * Critical conditions.
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 *
	 * @see ER_Logger::log
	 *
	 */
	public function critical( $message, $handle = 'log' ) {
		$this->log( ER_Log_Levels::CRITICAL, $message, $handle );
	}

	/**
	 * Adds an error level message.
	 *
	 * Runtime errors that do not require immediate action but should typically be logged
	 * and monitored.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 *
	 * @see ER_Logger::log
	 *
	 */
	public function error( $message, $handle = 'log' ) {
		$this->log( ER_Log_Levels::ERROR, $message, $handle );
	}

	/**
	 * Adds a warning level message.
	 *
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things that are not
	 * necessarily wrong.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 *
	 * @see ER_Logger::log
	 *
	 */
	public function warning( $message, $handle = 'log' ) {
		$this->log( ER_Log_Levels::WARNING, $message, $handle );
	}

	/**
	 * Adds a notice level message.
	 *
	 * Normal but significant events.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 *
	 * @see ER_Logger::log
	 *
	 */
	public function notice( $message, $handle = 'log' ) {
		$this->log( ER_Log_Levels::NOTICE, $message, $handle );
	}

	/**
	 * Adds a info level message.
	 *
	 * Interesting events.
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 *
	 * @see ER_Logger::log
	 *
	 */
	public function info( $message, $handle = 'log' ) {
		$this->log( ER_Log_Levels::INFO, $message, $handle );
	}

	/**
	 * Adds a debug level message.
	 *
	 * Detailed debug information.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 *
	 * @see ER_Logger::log
	 *
	 */
	public function debug( $message, $handle = 'log' ) {
		$this->log( ER_Log_Levels::DEBUG, $message, $handle );
	}

	/**
	 * Handle a log entry.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message Log message.
	 * @param array  $context
	 *
	 * @return bool False if value was not handled and true if value was handled.
	 */
	public function handle( $timestamp, $level, $message, $context ) {
		if ( isset( $context['source'] ) && $context['source'] ) {
			$handle = $context['source'];
		} else {
			$handle = 'log';
		}

		$handle = $handle ? $handle : 'log';

		$time_string = date( 'c', $timestamp );

		$level_string = strtoupper( $level );
		$entry        = "{$time_string} {$level_string} {$message}";

		return $this->add( $entry, $handle );
	}

	/**
	 * Open log file for writing.
	 *
	 * @param string $handle Log handle.
	 * @param string $mode Optional. File mode. Default 'a'.
	 *
	 * @return bool Success.
	 */
	protected function open( $handle, $mode = 'a' ) {
		if ( $this->is_open( $handle ) ) {
			return true;
		}

		$file = self::get_log_file_path( $handle );

		if ( $file ) {
			if ( ! file_exists( $file ) ) {
				$temphandle = @fopen( $file, 'w+' ); // @codingStandardsIgnoreLine.
				if ( ! $temphandle ) {
					wp_mkdir_p( RESERVATIONS_LOG_DIR );
				}

				@fclose( $temphandle ); // @codingStandardsIgnoreLine.

				if ( defined( 'FS_CHMOD_FILE' ) ) {
					@chmod( $file, FS_CHMOD_FILE ); // @codingStandardsIgnoreLine.
				}
			}

			$resource = @fopen( $file, $mode ); // @codingStandardsIgnoreLine.

			if ( $resource ) {
				$this->handles[ $handle ] = $resource;

				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a handle is open.
	 *
	 * @param string $handle Log handle.
	 *
	 * @return bool True if $handle is open.
	 */
	protected function is_open( $handle ) {
		return isset( $this->handles[ $handle ] ) && is_resource( $this->handles[ $handle ] );
	}

	/**
	 * Close a handle.
	 *
	 * @param string $handle Log handle.
	 *
	 * @return bool success
	 */
	protected function close( $handle ) {
		$result = false;

		if ( $this->is_open( $handle ) ) {
			$result = fclose( $this->handles[ $handle ] ); // @codingStandardsIgnoreLine.
			unset( $this->handles[ $handle ] );
		}

		return $result;
	}

	/**
	 * Add a log entry to chosen file.
	 *
	 * @param string $entry Log entry text.
	 * @param string $handle Log entry handle.
	 *
	 * @return bool True if write was successful.
	 */
	protected function add( $entry, $handle ) {
		$result = false;

		if ( $this->should_shuffle( $handle ) ) {
			$this->shuffle( $handle );
		}

		if ( $this->open( $handle ) && is_resource( $this->handles[ $handle ] ) ) {
			$result = fwrite( $this->handles[ $handle ], $entry . PHP_EOL ); // @codingStandardsIgnoreLine.
		} else {
			$this->cache_log( $entry, $handle );
		}

		return false !== $result;
	}

	/**
	 * Rename file to .old and delete existing .old
	 *
	 * @param $handle
	 */
	public function shuffle( $handle ) {
		$this->close( $handle );

		$filename = self::get_log_file_path( $handle );

		if ( file_exists( $filename . '.old' ) ) {
			unlink( $filename . '.old' );
		}

		rename( $filename, $filename . '.old' );
	}

	/**
	 * Check if log file should be rotated.
	 *
	 * Compares the size of the log file to determine whether it is over the size limit.
	 *
	 * @param string $handle Log handle.
	 *
	 * @return bool True if if should be rotated.
	 */
	protected function should_shuffle( $handle ) {
		$file = self::get_log_file_path( $handle );
		if ( $file ) {
			if ( $this->is_open( $handle ) ) {
				$file_stat = fstat( $this->handles[ $handle ] );

				return $file_stat['size'] > $this->log_size_limit;
			} elseif ( file_exists( $file ) ) {
				return filesize( $file ) > $this->log_size_limit;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get a log file path.
	 *
	 * @param string $handle Log name.
	 *
	 * @return bool|string The log file path or false if path cannot be determined.
	 */
	public static function get_log_file_path( $handle ) {
		return trailingslashit( RESERVATIONS_LOG_DIR ) . self::get_log_file_name( $handle );
	}

	/**
	 * Get a log file name.
	 *
	 * File names consist of the handle, followed by the date, followed by a hash, .log.
	 *
	 * @param string $handle Log name.
	 *
	 * @return bool|string The log file name or false if cannot be determined.
	 */
	public static function get_log_file_name( $handle ) {
		return sanitize_file_name( $handle . '.log' );
	}

	/**
	 * Cache log to write later.
	 *
	 * @param string $entry Log entry text.
	 * @param string $handle Log entry handle.
	 */
	protected function cache_log( $entry, $handle ) {
		$this->cache[] = array(
			'entry'  => $entry,
			'handle' => $handle,
		);
	}

	/**
	 * Write cached logs.
	 */
	public function write_cached_logs() {
		foreach ( $this->cache as $key => $log ) {
			$this->add( $log['entry'], $log['handle'] );
			unset( $this->cache[ $key ] );
		}
	}

	/**
	 * Get all log files
	 *
	 * @return array
	 */
	public static function get_log_files() {
		$files  = @scandir( RESERVATIONS_LOG_DIR ); // @codingStandardsIgnoreLine.
		$result = array();

		if ( ! empty( $files ) ) {
			foreach ( $files as $key => $value ) {
				if ( ! in_array( $value, array( '.', '..' ), true ) ) {
					if ( ! is_dir( $value ) && strstr( $value, '.log' ) ) {
						$result[ sanitize_title( $value ) ] = $value;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Clear entries from chosen file.
	 *
	 * @param string $handle Log handle.
	 *
	 * @return bool
	 */
	public function clear( $handle = 'log' ) {
		$result = false;

		// Close the file if it's already open.
		$this->close( $handle );

		/**
		 * $this->open( $handle, 'w' ) == Open the file for writing only. Place the file pointer at
		 * the beginning of the file, and truncate the file to zero length.
		 */
		if ( $this->open( $handle, 'w' ) && is_resource( $this->handles[ $handle ] ) ) {
			$result = true;
		}

		do_action( 'easyreservations_log_clear', $handle );

		return $result;
	}

	/**
	 * Clear all logs older than a defined number of days. Defaults to 30 days.
	 */
	public function clear_expired_logs() {
		$days      = absint( apply_filters( 'easyreservations_logger_days_to_retain_logs', 30 ) );
		$timestamp = strtotime( "-{$days} days" );
		$this->delete_logs_before_timestamp( $timestamp );
	}

	/**
	 * Delete all logs older than a defined timestamp.
	 *
	 * @param integer $timestamp Timestamp to delete logs before.
	 */
	public static function delete_logs_before_timestamp( $timestamp = 0 ) {
		if ( ! $timestamp ) {
			return;
		}

		$log_files = self::get_log_files();

		foreach ( $log_files as $log_file ) {
			$last_modified = filemtime( trailingslashit( RESERVATIONS_LOG_DIR ) . $log_file );

			if ( $last_modified < $timestamp ) {
				@unlink( trailingslashit( RESERVATIONS_LOG_DIR ) . $log_file ); // @codingStandardsIgnoreLine.
			}
		}
	}
}