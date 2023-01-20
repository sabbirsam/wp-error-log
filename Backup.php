<?php
/**
 * Plugin Name: WP Error Log
 *
 * @author            Sabbir Sam, devsabbirahmed
 * @copyright         2022- devsabbirahmed
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: WP Error Log
 * Plugin URI: https://github.com/sabbirsam/Admin-Chat-Box/tree/free
 * Description: Logs JavaScript and PHP errors to a file and displays them on a page
 * Version:           1.0.0
 * Requires at least: 5.9 or higher
 * Requires PHP:      5.4 or higher
 * Author:            SABBIRSAM
 * Author URI:        https://github.com/sabbirsam/
 * Text Domain:       acb
 * Domain Path: /languages/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * 
 */

defined('ABSPATH') or die('Hey, what are you doing here? You silly human!');

if (file_exists(dirname(__FILE__).'/vendor/autoload.php')) {
    require_once dirname(__FILE__).'/vendor/autoload.php';
}

use ERROR\Inc\ERR_Activate;
use ERROR\Inc\ERR_Deactivate;

/**
 * Main Class
 */
if(!class_exists('ERR_Error')){
    class ERR_Error{
        public function __construct(){
            $this->includes();
            // add_action( 'wp_head', array( $this, 'log_javascript_errors' ) );
            // set_error_handler( array( $this, 'log_php_errors' ) );
            add_action( 'admin_menu', array( $this, 'add_error_page' ) );
        }
        /**
         * Register
         */
        function register(){
            add_action("plugins_loaded", array( $this, 'err_load' )); 
        }
        /**
         * Language load
         */
        function err_load(){
            load_plugin_textdomain('err', false,dirname(__FILE__)."languages");
        }
        /**
         * Classes 
         */
        public function includes() {

        }
        /**
         * log_javascript_errors : Javascript Error
         */
        /* public function log_javascript_errors() {
            ?>
            <script>
                window.onerror = function( message, url, lineNumber, columnNumber, error ) {
                    var data = {
                        'message': message,
                        'url': url,
                        'line': lineNumber,
                        'column': columnNumber,
                        'error': error
                    };
                    var xhr = new XMLHttpRequest();
                    xhr.open( 'POST', '<?php echo admin_url( 'admin-ajax.php' ); ?>' );
                    xhr.setRequestHeader( 'Content-Type', 'application/json' );
                    xhr.send( JSON.stringify( data ) );
                };
            </script>
            <?php
        } */

        /**
         * Log php errors
         */
        /* public function log_php_errors( $errno, $errstr, $errfile, $errline ) {
            $error_data = array(
                'type'    => $errno,
                'message' => $errstr,
                'file'    => $errfile,
                'line'    => $errline
            );
            error_log( print_r( $error_data, true ) );
        } */
        
        /**
         * Add error page
         */
        public function add_error_page() {
            // add_menu_page( 'Errors', 'Errors', 'manage_options', 'errors', array( $this, 'display_errors' ), 'dashicons-warning', 90 );
            add_management_page( 'WP Errors', 'WP Errors', 'manage_options', 'errors', array( $this, 'display_errors' ) );
        }

        public function display_errors() {
            $debug_log = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($debug_log)) {
                $debug_log_entries = file( $debug_log, FILE_IGNORE_NEW_LINES );
                $debug_log_data = array();
                foreach ( $debug_log_entries as $entry ) {
                    preg_match( '/^\[(.+)\] (\S+) (\S+): (.*)/', $entry, $matches );
                        $date = $matches[1];
                        $type = $matches[2];
                        $file = $matches[3];
                        $line = $matches[4];
                        $error_message = $matches[5];
                        $debug_log_data[] = array(
                            'date' => $date,
                            'type' => $type,
                            'file' => $file,
                            'line' => $line,
                            'error_message' => $error_message
                        );
                }
            }
            
            ?>
                <div class="wrap">
                    <h1>Errors</h1>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>File</th>
                                <th>Line</th>
                                <th>Error Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ( $debug_log_data as $data ) {
                                ?>
                                    <tr>
                                        <td><?php echo $data['date'] ?></td>
                                        <td><?php echo  $data['type'] ?></td>
                                        <td><?php echo  $data['file'] ?></td>
                                        <td><?php echo  $data['line'] ?></td>
                                        <td><?php echo $data['error_message'] ?></td>
                                    </tr>
                                <?php
                            }
                        ?>
                        </tbody>
                    </table>
                </div>
            <?php
            
        }
    
        /**
         * Activation Hook
         */
        function err_activate(){   
            ERR_Activate::err_activate();
        }
        /**
         * Deactivation Hook
         */
        function err_deactivate(){ 
            ERR_Deactivate::err_deactivate(); 
        }
    }
    /**
     * Instantiate an Object Class 
     */
    $err = new ERR_Error;
    register_activation_hook (__FILE__, array( $err, 'err_activate' ) );
    register_deactivation_hook (__FILE__, array( $err, 'err_deactivate' ) );
}

