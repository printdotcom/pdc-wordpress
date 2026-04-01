<?php

/**
 * Logger
 *
 * Provides admin-specific hooks, pages, and integrations for the plugin.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/includes
 * @since 1.2.0
 */

namespace PdcPod\Includes;

class Logger {


	/**
	 * @var Logger The single instance of the class
	 */
	private static $instance = null;

	/**
	 * @var string The absolute path to the log file
	 */
	private $log_file;

	/**
	 * Private constructor to enforce Singleton pattern
	 */
	private function __construct() {
		$this->set_log_file_path();
		$this->ensure_log_directory_exists();
	}

	/**
	 * Get the singleton instance
	 *
	 * @return Logger
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Sets the path to the log file securely in the WP uploads directory
	 */
	private function set_log_file_path() {
		$upload_dir     = wp_upload_dir();
		$log_dir        = trailingslashit( $upload_dir['basedir'] ) . 'pdc_pod_logs';
		$this->log_file = $log_dir . '/debug.log';
	}

	/**
	 * Ensures the log directory exists and attempts to protect it
	 */
	private function ensure_log_directory_exists() {
		$dir = dirname( $this->log_file );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );

			// Protect the directory from direct browser access
			file_put_contents( $dir . '/index.php', '<?php // Silence is golden' );
			file_put_contents( $dir . '/.htaccess', 'Deny from all' );
		}
	}

	/**
	 * Static method to write a message to the log file.
	 *
	 * @param string $message The log message.
	 * @param string $level   The log level ('error' or 'debug').
	 * @param array  $context Optional context array to append as JSON.
	 */
	public static function log( $message, $level = 'debug', $context = array() ) {
		$level_hierarchy = array(
			'none'  => 0,
			'error' => 1,
			'debug' => 2,
		);

		$configured_level_str = get_option( PDC_POD_NAME . '-loglevel', 'error' );

		if ( ! isset( $level_hierarchy[ $configured_level_str ] ) ) {
			$configured_level_str = 'error';
		}

		$configured_level      = $level_hierarchy[ $configured_level_str ];
		$current_message_level = isset( $level_hierarchy[ $level ] ) ? $level_hierarchy[ $level ] : 2;

		if ( $current_message_level > $configured_level ) {
			return;
		}

		$instance = self::get_instance();

		$max_size = 2 * 1024 * 1024; // 5 Megabytes in bytes
		if ( file_exists( $instance->log_file ) && filesize( $instance->log_file ) >= $max_size ) {
			$instance->clear_log();
			self::log( 'Log file exceeded max size (2MB) and was automatically cleared.', 'debug' );
		}

		$timestamp   = current_time( 'Y-m-d H:i:s' );
		$level_str   = strtoupper( $level );
		$context_str = ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '';
		$log_entry   = "[{$timestamp}] [{$level_str}] {$message}{$context_str}" . PHP_EOL;

		error_log( $log_entry, 3, $instance->log_file );
	}

	/**
	 * Gathers system information for debugging
	 *
	 * @return string
	 */
	private function get_system_info() {
		global $wp_version;
		$theme = wp_get_theme();

		$info  = "=== System Information ===\n";
		$info .= 'WordPress Version: ' . $wp_version . "\n";
		$info .= 'PHP Version: ' . phpversion() . "\n";
		$info .= 'Plugin Version: ' . PDC_POD_VERSION . "\n";
		$info .= 'Print.com Environment: ' . get_option( PDC_POD_NAME . '-env' ) . "\n";
		$info .= 'Server Software: ' . $_SERVER['SERVER_SOFTWARE'] . "\n";
		$info .= 'Active Theme: ' . $theme->get( 'Name' ) . ' (' . $theme->get( 'Version' ) . ")\n";
		$info .= 'Multisite: ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		$info .= "\n--- Active Plugins ---\n";
		foreach ( $all_plugins as $plugin_path => $plugin_data ) {
			if ( in_array( $plugin_path, $active_plugins, true ) ) {
				$info .= $plugin_data['Name'] . ' ' . $plugin_data['Version'] . "\n";
			}
		}

		$info .= "==========================\n\n";

		return $info;
	}

	/**
	 * Triggers a forced file download containing system info and the log file contents
	 */
	public function download_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to access this file.' );
		}

		$system_info = $this->get_system_info();
		$log_content = "=== Print.com Log ===\n";

		if ( file_exists( $this->log_file ) ) {
			$log_content .= file_get_contents( $this->log_file );
		} else {
			$log_content .= "No log entries found. The log file does not exist yet.\n";
		}

		$final_output = $system_info . $log_content;

		if ( ob_get_length() ) {
			ob_end_clean();
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="pdc-pod-log.log"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $final_output ) );

		echo $final_output;
		exit;
	}

	/**
	 * Empties the contents of the log file.
	 * * @return bool True on success, false on failure.
	 */
	public function clear_log() {
		if ( file_exists( $this->log_file ) ) {
			$cleared = file_put_contents( $this->log_file, '' ) !== false;

			if ( $cleared ) {
				self::log( 'Log file manually cleared.', 'debug' );
			}

			return $cleared;
		}

		return false;
	}
}
