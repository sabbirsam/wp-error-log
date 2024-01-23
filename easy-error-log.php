<?php
/**
 * Plugin Name: Easy Error Log
 *
 * @author            Sabbir Sam, devsabbirahmed
 * @copyright         2022- devsabbirahmed
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Easy Error Log
 * Plugin URI: https://github.com/sabbirsam/Admin-Chat-Box/tree/free
 * Description: Experience hassle-free debugging by conveniently defining error modes and debug log constants within the config file. No need to delve into core files – simply toggle the settings. Logs PHP errors and access all errors in a single, user-friendly dashboard page, making it effortless to identify and rectify issues.
 * Version:           1.0.0
 * Requires at least: 5.9 or higher
 * Requires PHP:      5.4 or higher
 * Author:            SABBIRSAM
 * Author URI:        https://github.com/sabbirsam/
 * Text Domain:       err
 * Domain Path: /languages/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined('ABSPATH') || die('Hey, what are you doing here? You silly human!');

if ( file_exists(__DIR__ . '/vendor/autoload.php') ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

use ERROR\Inc\ERR_Activate; //phpcs:ignore
use ERROR\Inc\ERR_Deactivate;  //phpcs:ignore

define( 'EASY_ERROR_LOG_VERSION', '1.0.0' );
define( 'EASY_ERROR_LOG_FILE', __FILE__ );
define( 'EASY_ERROR_LOG_DIR_URL', plugin_dir_url( __FILE__ ) );


if ( ! class_exists('ERR_Error') ) {
	/**
	 * Main Class handaling ERR_Error.
	 */
	class ERR_Error {
		/**
		 * This is __constructor
		 */
		public function __construct() {
			$this->includes();
			add_action( 'admin_menu', array( $this, 'add_error_page' ) );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue' ] );
			add_action( 'admin_bar_menu', array( $this, 'add_my_page_to_admin_bar' ), 100 );
		}

		/**
		 * Classes which include plugins loaded file.
		 */
		public function includes() {
			add_action('plugins_loaded', array( $this, 'err_load' ));
		}

		/**
		 * Enqueue plugin files.
		 *
		 * @param string $screen   use to get the current page screen.
		 */
		public function admin_enqueue( $screen ) {
			if ( 'tools_page_errors' === $screen ) {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );

				wp_enqueue_style(
					'err-admin-css',
					EASY_ERROR_LOG_DIR_URL . 'assets/easy-errors.css',
					'',
					time(),
					'all'
				);

				wp_enqueue_script(
					'err-admin-js',
					EASY_ERROR_LOG_DIR_URL . 'assets/easy-errors.js',
					[ 'jquery' ],
					time(),
					true
				);
			}
		}

		/**
		 * Language load.
		 */
		public function err_load() {
			load_plugin_textdomain('err', false,__DIR__ . 'languages');
		}

		/**
		 * Add error page.
		 */
		public function add_error_page() {
			// Check if wp-config.php exists and add Easy Error Log and debug if not found.
			$debug_error_mode_enabled = get_option('debug_error_mode_enabled', 0);
			if ( 0 === $debug_error_mode_enabled ) {
				$config_path = ABSPATH . 'wp-config.php';
				if ( file_exists($config_path) ) {
					$config_contents = file_get_contents($config_path);

					// Check if both WP_DEBUG and WP_DEBUG_LOG are defined, if not, add them.
					if ( ! preg_match('/define\s*\(\s*\'WP_DEBUG\'\s*,\s*([^\)]+)\);/s', $config_contents) ||
						! preg_match('/define\s*\(\s*\'WP_DEBUG_LOG\'\s*,\s*([^\)]+)\);/s', $config_contents) ) {

						// If WP_DEBUG is not defined, add it along with WP_DEBUG_LOG.
						$replacement = "define('WP_DEBUG', false); define('WP_DEBUG_LOG', false);";

						// Adjust the regular expression to match the existing WP_DEBUG line.
						$pattern = '/define\s*\(\s*\'WP_DEBUG\'\s*,\s*([^;]+)\);\s*$/m';

						if ( preg_match($pattern, $config_contents, $matches) ) {
							// If WP_DEBUG mode found/active, replace it with both definitions.
							$config_contents = str_replace($matches[0], $replacement, $config_contents);
						}
					}

					// Write the updated content back to wp-config.php.
					file_put_contents($config_path, $config_contents);

					update_option('debug_error_mode_enabled', 1);
				}
			}

			/**
			 * Add WP Error Dashboard in Tools.
			 */
			add_management_page( 'WP Errors', 'WP Errors', 'manage_options', 'errors', array( $this, 'display_errors' ) );
		}

		/**
		 * Display errors files.
		 */
		public function display_errors() {

			$wp_debug = ( defined( 'WP_DEBUG' ) && WP_DEBUG === true );
			$mode = $wp_debug ? 'active' : 'inactive';
			$status = $wp_debug ? 'ON' : 'OFF';

			if ( isset( $_POST['toggle_debug_mode'], $_POST['toggle_debug_mode_nonce'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['toggle_debug_mode_nonce'] ) ), 'toggle_debug_mode_nonce' ) ) {
				// Toggle the WP_DEBUG mode in wp-config.php.
				$config_path = ABSPATH . 'wp-config.php';
				if ( file_exists( $config_path ) ) {
					$config_contents = file_get_contents( $config_path );

					if ( $wp_debug ) {
						$config_contents = preg_replace( '/define\s*\(\s*\'WP_DEBUG\'\s*,\s*true\s*\)\s*;/', "define('WP_DEBUG', false);", $config_contents );

						$config_contents = preg_replace( '/define\s*\(\s*\'WP_DEBUG_LOG\'\s*,\s*true\s*\)\s*;/', "define('WP_DEBUG_LOG', false);", $config_contents );
					} else {
						$config_contents = preg_replace( '/define\s*\(\s*\'WP_DEBUG\'\s*,\s*false\s*\)\s*;/', "define('WP_DEBUG', true);", $config_contents );

						$config_contents = preg_replace( '/define\s*\(\s*\'WP_DEBUG_LOG\'\s*,\s*false\s*\)\s*;/', "define('WP_DEBUG_LOG', true);", $config_contents );
					}

					file_put_contents( $config_path, $config_contents );

					?>
					<!-- Reload the page to refresh  -->
					<script>
					setTimeout(function() {
						window.location.reload(true);
					}, 500);
					</script>
					<?php
					exit;
				}
			}

			?>
			<br>
			<div class="wpel-buttons" style="display: flex; gap: 16px;">
				<form method="post" action="">
					<?php wp_nonce_field( 'clean_debug_log_nonce', 'clean_debug_log_nonce' ); ?>
					<input type="hidden" name="action" value="clean_debug_log">
					<button type="submit" class="button"><?php echo esc_html__( 'Clean Debug Log', 'err' ); ?></button>
				</form>
				<form method="post" action="">
					<?php wp_nonce_field( 'download_debug_log_nonce', 'download_debug_log_nonce' ); ?>
					<input type="hidden" name="action" value="download_debug_log">
					<button type="submit" class="button"><?php echo esc_html__( 'Download Debug Log', 'err' ); ?></button>
				</form>

				<form method="post" action="">
					<?php wp_nonce_field( 'toggle_debug_mode_nonce', 'toggle_debug_mode_nonce' ); ?>
					<input type="hidden" name="toggle_debug_mode" value="1">
					<button type="submit" class="button"><?php echo esc_html__( 'Toggle Debug Mode:', 'err' ); ?> <span
							style="color: <?php echo 'active' === $mode ? 'red' : 'green'; ?>"><?php echo esc_html( $status ); ?></span></button>
				</form>

			</div>

			<br>
			<code contenteditable="true">error_log( 'Data Received: ' . print_r( $, true ) );</code>
			<br>
			<br>
			<code contenteditable="true">error_log( 'Data Received:-  ' );</code>
			<?php

			/**
			 * Clean Log
			 */
			if ( isset( $_POST['action'] ) && 'clean_debug_log' === $_POST['action'] && check_admin_referer( 'clean_debug_log_nonce', 'clean_debug_log_nonce' ) ) {
				$debug_log = WP_CONTENT_DIR . '/debug.log';
				if ( file_exists($debug_log) ) {
					file_put_contents($debug_log, '');
				}
			}

			/**
			 * Download
			 */
			if ( isset( $_POST['action'] ) && 'download_debug_log' === $_POST['action'] && check_admin_referer( 'download_debug_log_nonce', 'download_debug_log_nonce' ) ) {
				$debug_log = WP_CONTENT_DIR . '/debug.log';
				if ( file_exists($debug_log) ) {
					// JavaScript-based download.
					?>
				<script>
				var downloadLink = document.createElement('a');
				downloadLink.href = <?php echo json_encode( esc_url( site_url( '/wp-content/debug.log' ) ) ); ?>;
				downloadLink.download = 'debug.log';
				downloadLink.style.display = 'none';
				document.body.appendChild(downloadLink);
				downloadLink.click();
				document.body.removeChild(downloadLink);
				</script>
					<?php
				} else {
					echo 'Debug log file not found';
				}
			}

			/**
			 * Show log in page
			 */

			$debug_log = WP_CONTENT_DIR . '/debug.log';

			$output = '';
			if ( file_exists($debug_log) ) {
				$debug_log_entries = file($debug_log, FILE_IGNORE_NEW_LINES);
				if ( empty($debug_log_entries) ) {
					?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Debug mode:', 'err' ); ?><span
						style="color: <?php echo 'active' === $mode ? 'red' : 'green'; ?>"><?php echo esc_html( $mode ); ?></span>
				</h1>

				<table class="wp-list-table widefat fixed striped">
					<thead class="wp-error-head">
						<tr class="wp-error-row">
							<th class="wp-error-table-header"><?php echo esc_html__( 'Error Message', 'err' ); ?></th>
						</tr>
					</thead>
					<tbody class="wp-error-body">
						<tr class="wp-error-body-row">
							<td class="wp-error-body-data"><?php echo esc_html__( 'Debug log empty. No error found', 'err' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
					<?php
				} else {
					?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Errors', 'err' ); ?></h1>
				<table class="wp-list-table widefat fixed striped">
					<thead class="wp-error-head">
						<tr class="wp-error-row wp-error-heading-text">
							<th class="wp-error-table-header"><?php echo esc_html__( 'Error Message', 'err' ); ?></th>
						</tr>
					</thead>
					<tbody class="wp-error-body">
						<?php
						foreach ( $debug_log_entries as $data ) {
							?>
						<tr class="wp-error-body-row">
							<td class="wp-error-body-data"><?php echo esc_html( $data ); ?></td>
						</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
					<?php
				}
			} else {
				$output .= '<h3>Debug log file not found. Hence, no error found yet</h3>';
			}
			echo esc_html($output);
		}

		/**
		 * Function it check debug log and add count errors.
		 *
		 * @param string $wp_admin_bar   use to add error page in the admin bar.
		 */
		public function add_my_page_to_admin_bar( $wp_admin_bar ) {
			$debug_log = WP_CONTENT_DIR . '/debug.log';
			$error_count = 0;
			if ( file_exists($debug_log) ) {
				$debug_log_entries = file( $debug_log, FILE_IGNORE_NEW_LINES );
				$error_count = count($debug_log_entries);
			}

			$wp_admin_bar->add_node(array(
				'id'    => 'my-errors-page',
				'title' => "WP Errors-<span style='color:red;font-weight:bold;' class='update-plugins count-$error_count'><span class='update-count'>$error_count</span></span>",
				'href'  => admin_url('tools.php?page=errors'),
			));
		}

		/**
		 * Activation Hook
		 */
		public function err_activate() {
			ERR_Activate::err_activate();
		}
		/**
		 * Deactivation Hook
		 */
		public function err_deactivate() {
			ERR_Deactivate::err_deactivate();
		}
	}
	/**
	 * Instantiate an Object Class
	 */
	$err = new ERR_Error();
	register_activation_hook (__FILE__, array( $err, 'err_activate' ) );
	register_deactivation_hook (__FILE__, array( $err, 'err_deactivate' ) );
}