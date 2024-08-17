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
 * Plugin URI: https://github.com/sabbirsam/wp-error-log
 * Description: Experience hassle-free debugging by conveniently defining error modes and debug log constants within the config file. No need to delve into core files – simply toggle the settings. Logs PHP errors and access all errors in a single, user-friendly dashboard page, making it effortless to identify and rectify issues.
 * Version:           1.4.0
 * Requires at least: 5.9 or higher
 * Requires PHP:      5.4 or higher
 * Author:            SABBIRSAM
 * Author URI:        https://github.com/sabbirsam/
 * Text Domain:       easy-error-log
 * Domain Path: /languages/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined('ABSPATH') || die('Hey, what are you doing here? You silly human!');

if ( file_exists(__DIR__ . '/vendor/autoload.php') ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

use EEL\Inc\EEL_Activate; //phpcs:ignore
use EEL\Inc\EEL_Deactivate;  //phpcs:ignore

define( 'EASY_ERROR_LOG_VERSION', '1.4.0' );
define( 'EASY_ERROR_LOG_FILE', __FILE__ );
define( 'EASY_ERROR_LOG_DIR_URL', plugin_dir_url( __FILE__ ) );


if ( ! class_exists('EEL_Error') ) {
	/**
	 * Main Class handaling EEL_Error.
	 */
	class EEL_Error {
		/**
		 * This is __constructor.
		 */
		public function __construct() {
			$this->includes();
			add_action( 'admin_menu', array( $this, 'add_error_page' ) );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'fe_scripts' ] );
			add_action( 'admin_bar_menu', array( $this, 'add_my_page_to_admin_bar' ), 100 );
			
			// Add AJAX handlers.
			add_action( 'wp_ajax_display_error_log', array( $this, 'display_error_log_callback' ) );
			add_action( 'wp_ajax_nopriv_display_error_log', array( $this, 'display_error_log_callback' ) );

			add_action( 'wp_ajax_clean_debug_log', array( $this, 'clean_debug_log_callback' ) );
			add_action('wp_ajax_reset_debug_constant', array( $this, 'reset_debug_constant_callback' ));
			add_action( 'wp_ajax_toggle_debug_mode', array( $this, 'toggle_debug_mode_callback' ) );

			add_action( 'wp_ajax_get_debug_mode_status', array( $this, 'get_debug_mode_status_callback' ) );

			add_action( 'wp_ajax_download_debug_log', array( $this, 'download_debug_log_callback' ) );

			add_action( 'wp_ajax_get_error_count', array( $this, 'get_error_count_callback' ) );
			add_action( 'wp_ajax_nopriv_get_error_count', array( $this, 'get_error_count_callback' ) );
			
			add_action('wp_ajax_check_debug_constants_status', array( $this, 'check_debug_constants_status_callback' ));
			
			add_action( 'init', array( $this, 'system_info' ) );

			add_action('wp_footer', [ $this, 'display_error_floating_widget' ], 99 );

			add_action( 'wp_ajax_toggle_widgets_mode', array( $this, 'toggle_widgets_mode_callback' ) );
			add_action( 'wp_ajax_get_widgets_mode_status', array( $this, 'get_widgets_mode_status_callback' ) );

		}

		/**
		 * Classes which include plugins loaded file.
		 */
		public function includes() {
			add_action('plugins_loaded', array( $this, 'eel_load' ));
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

				// Localize the script with new data.
				$ajax_url = admin_url( 'admin-ajax.php' );
				wp_localize_script( 'err-admin-js', 'ajax_object', array( 'ajax_url' => $ajax_url ) );
			}
		}

		/**
		 * Fe Scripts load
		 */
		public function fe_scripts() {
			wp_enqueue_script(
				'err-fe-js',
				EASY_ERROR_LOG_DIR_URL . 'assets/fe-easy-errors.js',
				[ 'jquery' ],
				time(),
				true
			);

			wp_enqueue_style(
				'err-fe-css',
				EASY_ERROR_LOG_DIR_URL . 'assets/fe-error-style.css',
				'',
				time(),
				'all'
			);


			// Localize the script with new data.
			$ajax_url = admin_url( 'admin-ajax.php' );
			wp_localize_script( 'err-fe-js', 'ajax_fe_object', array( 'ajax_url' => $ajax_url ) );
		}

		/**
		 * Language load.
		 */
		public function eel_load() {
			load_plugin_textdomain('easy-error-log', false,__DIR__ . 'languages');
		}

		/**
		 * System Information.
		 */
		public function system_info() {
			global $wpdb;

			// Ensure the get_plugin_data function is available
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$home_url = esc_url_raw(home_url());
			$site_url = esc_url_raw(site_url());
			$wp_content_path = defined('WP_CONTENT_DIR') ? esc_html(WP_CONTENT_DIR) : esc_html__('N/A', 'wpnts');
			$wp_path = defined('ABSPATH') ? esc_html(ABSPATH) : esc_html__('N/A', 'wpnts');
			$wp_version = get_bloginfo('version');
			$multisite = is_multisite() ? 'Yes' : 'No';
			$memory = ini_get('memory_limit');
			$memory = !$memory || -1 === $memory ? wp_convert_hr_to_bytes(WP_MEMORY_LIMIT) : wp_convert_hr_to_bytes($memory);
			$memory = is_numeric($memory) ? size_format($memory) : 'N/A';
			$wp_debug = defined('WP_DEBUG') && WP_DEBUG ? 'Active' : 'Inactive';
			$language = get_locale();
			$os = defined('PHP_OS') ? esc_html(PHP_OS) : esc_html__('N/A', 'wpnts');
			$server_info = isset($_SERVER['SERVER_SOFTWARE']) ? esc_html(sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']))) : esc_html__('Unknown', 'wpnts');
			$php_version = phpversion();
			$post_max_size = size_format(wp_convert_hr_to_bytes(ini_get('post_max_size')));
			$time_limit = ini_get('max_execution_time');
			$mysql_version = $wpdb->db_version();
			$max_upload_size = size_format(wp_max_upload_size());
			$mbstring = extension_loaded('mbstring') ? 'Installed' : 'Not installed';
			$xml = extension_loaded('xml') ? 'Installed' : 'Not installed';
			$dom = extension_loaded('dom') ? 'Installed' : 'Not installed';
	
			$libxml = extension_loaded('libxml') ? (defined('LIBXML_VERSION') && LIBXML_VERSION > 20760 ? 'Installed - Version: ' . LIBXML_DOTTED_VERSION : 'Lower version than required') : 'Not installed';
			$pdo = extension_loaded('pdo') ? 'Installed - PDO Drivers: ' . implode(', ', pdo_drivers()) : 'Not installed';
			$zip = class_exists('ZipArchive') ? 'Installed' : 'Not installed';
			$curl = extension_loaded('curl') ? 'Installed - Version: ' . curl_version()['version'] : 'Not installed';
	
			// Theme information.
			$themeObject = wp_get_theme();
			$theme_info = array(
				'Name' => esc_html($themeObject->get('Name')),
				'Version' => esc_html($themeObject->get('Version')),
				'Author' => esc_html($themeObject->get('Author')),
				'AuthorURI' => esc_html($themeObject->get('AuthorURI')),
			);
		
			// Active plugins information.
			$active_plugins = (array) get_option('active_plugins', array());
			if (is_multisite()) {
				$active_plugins = array_merge($active_plugins, array_keys(get_site_option('active_sitewide_plugins', array())));
			}
			$plugins_info = array();
			foreach ($active_plugins as $plugin) {
				$plugin_data = @get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
				if (!empty($plugin_data['Name'])) {
					$plugins_info[] = array(
						'Name' => !empty($plugin_data['PluginURI']) ? '<a href="' . esc_url($plugin_data['PluginURI']) . '" title="' . esc_attr__('Visit plugin homepage', 'wpdatatables') . '" target="_blank">' . esc_html($plugin_data['Name']) . '</a>' : esc_html($plugin_data['Name']),
						'Author' => esc_html($plugin_data['AuthorName']),
						'Version' => esc_html($plugin_data['Version']),
						'Description' => esc_html($plugin_data['Description']),
					);
				}
			}


			// New Information Sections
			$apache_status = function_exists('apache_get_version') ? esc_html(apache_get_version()) : esc_html__('N/A', 'wpnts');
			$database_name = $wpdb->dbname;
			$database_charset = $wpdb->charset;
			$database_collate = $wpdb->collate;
		
		
			$current_user = wp_get_current_user();
			$basic_user_info = array(
				'ID' => esc_html($current_user->ID),
				'user_login' => esc_html($current_user->user_login),
				'user_pass' => esc_html($current_user->user_pass),
				'user_nicename' => esc_html($current_user->user_nicename),
				'user_email' => esc_html($current_user->user_email),
				'user_url' => esc_html($current_user->user_url),
				'user_registered' => esc_html($current_user->user_registered),
				'user_activation_key' => esc_html($current_user->user_activation_key),
				'user_status' => esc_html($current_user->user_status),
				'user_firstname' => esc_html($current_user->user_firstname),
				'user_lastname' => esc_html($current_user->user_lastname),
				'display_name' => esc_html($current_user->display_name),
				'roles' => implode(', ', $current_user->roles),
				'user_email_verified' => 'N/A',
				'user_locale' => get_user_meta($current_user->ID, 'locale', true),
			);
			


	
	
		
			// Return the information as an associative array.
			return array(
				'home_url' => $home_url,
				'site_url' => $site_url,
				'wp_content_path' => $wp_content_path,
				'wp_path' => $wp_path,
				'wp_version' => $wp_version,
				'multisite' => $multisite,
				'memory_limit' => $memory,
				'wp_debug' => $wp_debug,
				'language' => $language,
				'os' => $os,
				'server_info' => $server_info,
				'php_version' => $php_version,
				'post_max_size' => $post_max_size,
				'time_limit' => $time_limit,
				'mysql_version' => $mysql_version,
				'max_upload_size' => $max_upload_size,
				'mbstring' => $mbstring,
				'xml' => $xml,
				'dom' => $dom,
				'libxml' => $libxml,
				'pdo' => $pdo,
				'zip' => $zip,
				'curl' => $curl,
				'apache_status' => $apache_status,
				'theme_info' => $theme_info,
				'plugins_info' => $plugins_info,
				
				'basic_user_info' => $basic_user_info,
			);
		}



		/**
		 * Add error page.
		 */
		public function add_error_page() {
			// Check if easy_error_log_debug_mode_enabled option is empty or 0.
			$debug_error_mode_enabled = get_option('easy_error_log_debug_mode_enabled', 0);
			if ( empty($debug_error_mode_enabled) ) {
				$config_path = ABSPATH . 'wp-config.php';
				if ( file_exists($config_path) ) {
					$config_contents = file_get_contents($config_path);

					// Check if both WP_DEBUG and WP_DEBUG_LOG are defined, if not, add them.
					if ( ! preg_match('/define\s*\(\s*\'WP_DEBUG\'\s*,\s*([^\)]+)\);/s', $config_contents) ||
						! preg_match('/define\s*\(\s*\'WP_DEBUG_LOG\'\s*,\s*([^\)]+)\);/s', $config_contents) ) {

						// Define constants.
						$constants_to_add = "define('WP_DEBUG', false);\n" .
											"define('WP_DEBUG_LOG', false);\n";

						// Find the position to insert constants.
						$position_to_insert = strpos($config_contents, '/* That\'s all, stop editing! Happy publishing. */');

						if ( false !== $position_to_insert ) {
							// Insert constants above the line.
							$config_contents = substr_replace($config_contents, $constants_to_add . "\n", $position_to_insert, 0);

							// Write the updated content back to wp-config.php.
							file_put_contents($config_path, $config_contents);

							// Update the option value.
							update_option('easy_error_log_debug_mode_enabled', 1);
						}
					}
				}
			}

			// Add WP Error Dashboard in Tools.
			add_management_page( 'WP Errors', 'WP Errors', 'manage_options', 'errors', array( $this, 'display_errors' ) );
		}

		/**
		 * Show status
		 */
		public function check_debug_constants_status_callback() {
			$status = array(
				'WP_DEBUG' => defined('WP_DEBUG') ? WP_DEBUG : 'Not Found',
				'WP_DEBUG_LOG' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : 'Not Found',
			);
			wp_send_json_success($status);
			wp_die();
		}


		/**
		 * Display errors files.
		 */
		public function display_errors() {
			$mode = '';
			$status = '';
			$widgets_mode = '';

			?>
			<br>

			<div class="nav-tab-wrapper">
				<a href="#debugger" class="nav-tab nav-tab-active"><?php echo esc_html__( 'Debugger', 'easy-error-log' ); ?></a>
				<a href="#system-info" class="nav-tab"><?php echo esc_html__( 'System Info', 'easy-error-log' ); ?></a>
				<a href="#theme-plugins" class="nav-tab"><?php echo esc_html__( 'Theme & Plugins', 'easy-error-log' ); ?></a>
				<a href="#user-info" class="nav-tab"><?php echo esc_html__( 'User Info', 'easy-error-log' ); ?></a>
			</div>

			<div class="tab-content">
				<!-- Debugger Tab -->
				<div id="debugger" class="tab-pane active">
					<div class="wpel-buttons" style="display: flex; gap: 16px;">

						<button id="clean-debug-log" class="button"><?php echo esc_html__( 'Clean Debug Log', 'easy-error-log' ); ?></button>

						<button id="refresh-debug-log" class="button"><?php echo esc_html__( 'Refresh Debug Log', 'easy-error-log' ); ?></button>

						<form id="download-debug-log" method="post" action="">
							<?php wp_nonce_field( 'download_debug_log_nonce', 'download_debug_log_nonce' ); ?>
							<input type="hidden" name="action" value="download_debug_log">
							<button type="submit" class="button"><?php echo esc_html__( 'Download Debug Log', 'easy-error-log' ); ?></button>
						</form>
						<button id="reset-constant" class="button"><?php echo esc_html__( 'Reset Debug Constant', 'easy-error-log' ); ?></button>

						
						<button id="toggle-fe-mode" class="button">
							<?php echo esc_html__( 'FE widgets:', 'easy-error-log' ); ?>
							<span id="debug-fe-status" style="color: <?php echo esc_html( 'active' === $widgets_mode ? 'red' : 'green' ); ?>">
								<?php echo esc_html( $status ); ?>
							</span>
						</button>


						<button id="toggle-debug-mode" class="button">
							<?php echo esc_html__( 'Toggle Debug Mode:', 'easy-error-log' ); ?>
							<span id="debug-mode-status" style="color: <?php echo esc_html( 'active' === $mode ? 'red' : 'green' ); ?>">
								<?php echo esc_html( $status ); ?>
							</span>
						</button>

						


						</div>
						<div class="status">
						<h4>WP_DEBUG: <span class="constant-status wp-debug" style="color: green;">Found</span></h4>
						<h4>WP_DEBUG_LOG: <span class="constant-status wp-debug-log" style="color: green;">Found</span></h4>
					</div>
					<div class="debug-constant">
						<div class="code-wrapper">
							<code contenteditable="true" id="code1">error_log( 'Data Received: ' . print_r( $, true ) );</code>
							<button class="copy-btn" data-target="#code1" title="Copy to Clipboard">
								&#x1F4CB; <!-- Copy icon, can be replaced with an image or font icon -->
							</button>
						</div>
						<div class="code-wrapper">
							<code contenteditable="true" id="code2">error_log( 'Data Received:-  ' );</code>
							<button class="copy-btn" data-target="#code2" title="Copy to Clipboard">
								&#x1F4CB; <!-- Copy icon, can be replaced with an image or font icon -->
							</button>
						</div>
					</div>


						<!-- Display error and other status  -->
					<table class="wp-list-table widefat fixed striped">
						<thead class="wp-error-head">
							<tr class="wp-error-row">
								<th class="wp-error-table-header"><?php echo esc_html__( 'Error Message', 'easy-error-log' ); ?></th>
							</tr>
						</thead>
						<tbody class="wp-error-body">
							<tr class="wp-error-body-row">
								<td class="wp-error-body-data"><p id="error-log-container" class="error-log-scrollable"></p></td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- System Info Tab -->

				<?php
					// Get the system information
					$system_info = $this->system_info();
				?>

				<div id="system-info" class="tab-pane" style="display: none;">
					<h3><?php echo esc_html__( 'System Information', 'easy-error-log' ); ?></h3>
					
					<h4 class="eel-title"><?php echo esc_html__( 'WordPress Environment Information', 'easy-error-log' ); ?></h4>
					<!-- General System Information -->
					<table class="wp-list-table widefat fixed striped">
						<tbody>
							<tr>
								<th><?php echo esc_html__( 'Home URL:', 'easy-error-log' ); ?></th>
								<td><?php echo esc_html( $system_info['home_url'] ); ?></td>
							</tr>
							<tr>
								<th><?php echo esc_html__( 'Site URL:', 'easy-error-log' ); ?></th>
								<td><?php echo esc_html( $system_info['site_url'] ); ?></td>
							</tr>
							<tr>
								<th><?php echo esc_html__( 'WP Content Path:', 'easy-error-log' ); ?></th>
								<td><?php echo esc_html( $system_info['wp_content_path'] ); ?></td>
							</tr>
							<tr>
								<th><?php echo esc_html__( 'WP Path:', 'easy-error-log' ); ?></th>
								<td><?php echo esc_html( $system_info['wp_path'] ); ?></td>
							</tr>
							<tr>
								<th><?php echo esc_html__( 'WP Version:', 'easy-error-log' ); ?></th>
								<td><?php echo esc_html( $system_info['wp_version'] ); ?></td>
							</tr>
							<tr>
								<th><?php echo esc_html__( 'Multisite:', 'easy-error-log' ); ?></th>
								<td><?php echo esc_html( $system_info['multisite'] ); ?></td>
							</tr>
							<tr>
								<th><?php echo esc_html__( 'Memory Limit:', 'easy-error-log' ); ?></th>
								<td><?php echo esc_html( $system_info['memory_limit'] ); ?></td>
							</tr>
							<tr>
								<th><?php echo esc_html__( 'WP Debug:', 'easy-error-log' ); ?></th>
								<td><?php echo esc_html( $system_info['wp_debug'] ); ?></td>
							</tr>
							<tr>
								<th><?php echo esc_html__( 'Language:', 'easy-error-log' ); ?></th>
								<td><?php echo esc_html( $system_info['language'] ); ?></td>
							</tr>
						</tbody>
					</table>
					
					<!-- Server Information -->
					<h4 class="eel-title"><?php echo esc_html__( 'Server Information', 'easy-error-log' ); ?></h4>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Parameter', 'easy-error-log' ); ?></th>
								<th><?php echo esc_html__( 'Value', 'easy-error-log' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?php echo esc_html__( 'Operating System:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['os'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'Server Info:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['server_info'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'PHP Version:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['php_version'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'Post Max Size:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['post_max_size'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'Time Limit:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['time_limit'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'MySQL Version:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['mysql_version'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'Max Upload Size:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['max_upload_size'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'MBString:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['mbstring'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'XML:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['xml'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'DOM:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['dom'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'LibXML:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['libxml'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'PDO:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['pdo'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'Zip:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['zip'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'cURL:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['curl'] ); ?></td>
							</tr>
							<tr>
								<td><?php echo esc_html__( 'Apache Status:', 'easy-error-log' ); ?></td>
								<td><?php echo esc_html( $system_info['apache_status'] ); ?></td>
							</tr>

						</tbody>
					</table>

					
				</div>


				<!-- Theme & Plugins Tab -->
				<div id="theme-plugins" class="tab-pane" style="display: none;">
					<h3><?php echo esc_html__( 'Theme & Plugins Information', 'easy-error-log' ); ?></h3>

					<h4 class="eel-title"><?php echo esc_html__( 'Active Theme Information', 'easy-error-log' ); ?></h4>
					<table class="wp-list-table widefat fixed striped theme-info-table">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Name', 'easy-error-log' ); ?></th>
								<th><?php echo esc_html__( 'Version', 'easy-error-log' ); ?></th>
								<th><?php echo esc_html__( 'Author', 'easy-error-log' ); ?></th>
								<th><?php echo esc_html__( 'Author URI', 'easy-error-log' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?php echo esc_html( $system_info['theme_info']['Name'] ); ?></td>
								<td><?php echo esc_html( $system_info['theme_info']['Version'] ); ?></td>
								<td><?php echo esc_html( $system_info['theme_info']['Author'] ); ?></td>
								<td><?php echo esc_html( $system_info['theme_info']['AuthorURI'] ); ?></td>
							</tr>
						</tbody>
					</table>

					<h4 class="eel-title"><?php echo esc_html__( 'Active Plugins Information', 'easy-error-log' ); ?></h4>
					<table class="wp-list-table widefat fixed striped plugins-info-table">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Name', 'easy-error-log' ); ?></th>
								<th><?php echo esc_html__( 'Version', 'easy-error-log' ); ?></th>
								<th><?php echo esc_html__( 'Author', 'easy-error-log' ); ?></th>
								<th><?php echo esc_html__( 'Description', 'easy-error-log' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php 
							foreach ( $system_info['plugins_info'] as $plugin ) : ?>
							<tr>
								<td><?php echo $plugin['Name']; ?></td>
								<td><?php echo esc_html( $plugin['Version'] ); ?></td>
								<td><?php echo esc_html( $plugin['Author'] ); ?></td>
								<td><?php echo wp_kses_post( html_entity_decode( $plugin['Description'] ) ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>


				<!-- User info Tab -->
				<div id="user-info" class="tab-pane" style="display: none;">
					<h3><?php echo esc_html__( 'User Basic Information', 'easy-error-log' ); ?></h3>

					<h4 class="eel-title"><?php echo esc_html__( 'Active user', 'easy-error-log' ); ?></h4>
					<table class="wp-list-table widefat fixed striped theme-info-table">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Name', 'easy-error-log' ); ?></th>
								<th><?php echo esc_html__( 'Value', 'easy-error-log' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $system_info['basic_user_info'] as $key => $value ) : ?>
							<tr>
								<td><?php echo esc_html( $key ); ?></td>
								<td><?php echo esc_html( $value ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>



			</div>
			
			
			
			
			<?php
		}

		/**
		 * AJAX callback function to get the current debug mode status.
		 */
		public function get_debug_mode_status_callback() {
			$debug_mode_status = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'ON' : 'OFF';
			echo esc_html($debug_mode_status);
			wp_die();
		}


		/**
		 * AJAX callback function to display error log.
		 */
		public function display_error_log_callback() {
			$debug_log_paths = array(
				WP_CONTENT_DIR . '/debug.log',
				ABSPATH . 'debug.log',
			);

			$debug_log_path = '';
			foreach ( $debug_log_paths as $path ) {
				if ( file_exists($path) ) {
					$debug_log_path = $path;
					break;
				}
			}

			if ( file_exists($debug_log_path) ) {
				$debug_log_entries = file($debug_log_path, FILE_IGNORE_NEW_LINES);
				if ( empty($debug_log_entries) ) {
					echo '<div>' . esc_html__('Debug log empty. No errors found.', 'easy-error-log') . '</div>';
				} else {
					foreach ( $debug_log_entries as $entry ) {
						echo "<div class='debug-log-errors'>" . esc_html($entry) . '</div>';
					}
				}
			} else {
				echo '<div>' . esc_html__('Debug log file not found.', 'easy-error-log') . '</div>';
			}
			die();
		}


		/**
		 * Callback function for toggling debug mode.
		 */
		public function toggle_debug_mode_callback() {
			$config_path = ABSPATH . 'wp-config.php';
			if ( file_exists( $config_path ) ) {
				$config_contents = file_get_contents( $config_path );

				// Check if WP_DEBUG is defined.
				if ( preg_match( '/define\s*\(\s*\'WP_DEBUG\'\s*,\s*([^\)]+)\);/s', $config_contents, $matches ) ) {
					// Toggle WP_DEBUG value.
					$new_debug_value = ( 'true' === $matches[1] ) ? 'false' : 'true';
					$config_contents = preg_replace( '/define\s*\(\s*\'WP_DEBUG\'\s*,\s*([^\)]+)\);/s', "define('WP_DEBUG', $new_debug_value);", $config_contents );

					// Toggle WP_DEBUG_LOG value.
					if ( 'false' === $new_debug_value ) {
						$config_contents = preg_replace('/define\s*\(\s*\'WP_DEBUG_LOG\'\s*,\s*([^\)]+)\);/s', "define('WP_DEBUG_LOG', false);", $config_contents);
					} else {
						$config_contents = preg_replace('/define\s*\(\s*\'WP_DEBUG_LOG\'\s*,\s*([^\)]+)\);/s', "define('WP_DEBUG_LOG', true);", $config_contents);
					}

					// Update wp-config.php with the new values.
					file_put_contents( $config_path, $config_contents );
					$debug_status = ( 'true' === $new_debug_value ) ? 'ON' : 'OFF';
					echo esc_html__( $debug_status, 'easy-error-log' );

				} else {
					echo esc_html__( 'WP_DEBUG constant not found', 'easy-error-log' );
				}
			} else {
				echo esc_html__( 'wp-config not found.', 'easy-error-log' );
			}
			die();
		}

		/**
		 * AJAX callback function to clean debug log.
		 */
		public function clean_debug_log_callback() {
			// $debug_log_path = WP_CONTENT_DIR . '/debug.log';
			$debug_log_paths = array(
				WP_CONTENT_DIR . '/debug.log',
				ABSPATH . 'debug.log',
			);

			$debug_log_path = '';
			foreach ( $debug_log_paths as $path ) {
				if ( file_exists($path) ) {
					$debug_log_path = $path;
					break;
				}
			}

			if ( file_exists( $debug_log_path ) ) {
				file_put_contents( $debug_log_path, '' );
				echo '<p>' . esc_html__( 'Debug log cleaned successfully.', 'easy-error-log' ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'Debug log file not found.', 'easy-error-log' ) . '</p>';
			}
			die();
		}

		/**
		 * Reset debug constant callback.
		 */
		public function reset_debug_constant_callback() {
			update_option('easy_error_log_debug_mode_enabled', ''); // Reset the option value
			echo esc_html__('Debug constant reset successfully.', 'easy-error-log'); // Return success message
			wp_die();
		}


		/**
		 * AJAX callback function to download debug log.
		 */
		public function download_debug_log_callback() {
			// $debug_log_path = WP_CONTENT_DIR . '/debug.log';.
			$debug_log_paths = array(
				WP_CONTENT_DIR . '/debug.log',
				ABSPATH . 'debug.log',
			);

			$debug_log_path = '';
			foreach ( $debug_log_paths as $path ) {
				if ( file_exists($path) ) {
					$debug_log_path = $path;
					break;
				}
			}

			if ( file_exists( $debug_log_path ) ) {
				// Return the URL to the debug log file.
				echo esc_url( content_url( '/debug.log' ) );
			} else {
				echo esc_html__( 'Debug log file not found.', 'easy-error-log' );
			}
			wp_die();
		}

		/**
		 * Count erros.
		 */
		public function get_error_count_callback() {

			$debug_log_paths = array(
				WP_CONTENT_DIR . '/debug.log',
				ABSPATH . 'debug.log',
			);

			$debug_log_path = '';
			foreach ( $debug_log_paths as $path ) {
				if ( file_exists($path) ) {
					$debug_log_path = $path;
					break;
				}
			}

			$error_count = 0;
			if ( file_exists($debug_log_path) ) {
				$debug_log_entries = file( $debug_log_path, FILE_IGNORE_NEW_LINES );
				$error_count = count($debug_log_entries);
			}

			echo esc_html($error_count);
			wp_die();
		}



		/**
		 * Function to add error page in the admin bar.
		 *
		 * @param string $wp_admin_bar   use to add error page in the admin bar.
		 */
		public function add_my_page_to_admin_bar( $wp_admin_bar ) {
			// $debug_log_path = WP_CONTENT_DIR . '/debug.log';
			$debug_log_paths = array(
				WP_CONTENT_DIR . '/debug.log',
				ABSPATH . 'debug.log',
			);

			$debug_log_path = '';
			foreach ( $debug_log_paths as $path ) {
				if ( file_exists($path) ) {
					$debug_log_path = $path;
					break;
				}
			}

			$error_count = 0;
			if ( file_exists($debug_log_path) ) {
				$debug_log_entries = file( $debug_log_path, FILE_IGNORE_NEW_LINES );
				$error_count = count($debug_log_entries);
			}

			$wp_admin_bar->add_node(array(
				'id'    => 'my-errors-page',
				'title' => "WP Errors-<span style='color:red;font-weight:bold;' class='update-plugins count-$error_count'><span class='update-count'>$error_count</span></span>",
				'href'  => admin_url('tools.php?page=errors'),
			));
		}


		public function get_widgets_mode_status_callback() {
			// Get the current mode value.
			$widgets_mode = get_option( 'fe_widgets_mode', 'false' );
			wp_send_json_success( array( 'widgets_mode' => $widgets_mode ) );
		}



		public function toggle_widgets_mode_callback(){
			 // Check the current value of the option.
			 $widgets_mode = get_option( 'fe_widgets_mode', 'false' ); 

			 // Toggle the value.
			 if ( 'true' === $widgets_mode ) {
				 $widgets_mode = 'false';
			 } else {
				 $widgets_mode = 'true';
			 }
		 
			 // Update the option with the new value.
			 update_option( 'fe_widgets_mode', $widgets_mode );
		 
			 // Send the new mode back as the response.
			 wp_send_json_success( array( 'widgets_mode' => $widgets_mode ) );
		}

		/**
		 * Function to add error page in the admin bar.
		 *
		 * @param string $wp_admin_bar   use to add error page in the admin bar.
		 */
		
		public function display_error_floating_widget() {
			$mode = get_option( 'fe_widgets_mode', 'false' );
			if ( 'true' === $mode ) {
				?>
				<div id="error-log-container" class="error-log-container">
					<div class="error-log-header">
						<span><?php echo esc_html__( 'Error Log', 'easy-error-log' ); ?></span>
						<button id="error-log-toggle" class="error-log-toggle"><?php echo esc_html__( '+', 'easy-error-log' ); ?></button>
					</div>
					<div id="error-log-content" class="error-log-content"></div>
				</div>
				<?php
			}
		}

		/**
		 * Activation Hook
		 */
		public function eel_activate() {
			EEL_Activate::eel_activate();
		}
		/**
		 * Deactivation Hook
		 */
		public function eel_deactivate() {
			EEL_Deactivate::eel_deactivate();
		}
	}
	/**
	 * Instantiate an Object Class
	 */
	$err = new EEL_Error();

	register_activation_hook (__FILE__, array( $err, 'eel_activate' ) );
	register_deactivation_hook (__FILE__, array( $err, 'eel_deactivate' ) );
}
