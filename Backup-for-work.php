<?php


defined('ABSPATH') or die('Hey, what are you doing here? You silly human!');

if (file_exists(dirname(__FILE__).'/vendor/autoload.php')) {
    require_once dirname(__FILE__).'/vendor/autoload.php';
}


/**
 * Main Class
 */
if(!class_exists('ERR_Error')){
    class ERR_Error{
        public function __construct(){
            $this->includes();
            add_action( 'admin_menu', array( $this, 'add_error_page' ) );
            // Register the Ajax handler
            add_action( 'wp_ajax_my_errors_count', array( $this, 'my_errors_count_ajax_handler' ) );
            add_action( 'admin_bar_menu', array( $this, 'add_my_page_to_admin_bar' ), 100 );
       
        }

        /**
         * Classes 
         */
        public function includes() {
            add_action("plugins_loaded", array( $this, 'err_load' ));  
        }

        /**
         * Language load
         */
        function err_load(){
            load_plugin_textdomain('err', false,dirname(__FILE__)."languages");
        }

        /**
         * Add error page
         */
        public function add_error_page() {
            add_management_page( 'WP Errors', 'WP Errors', 'manage_options', 'errors', array( $this, 'display_errors' ) );
        }

        public function display_errors() {
            
            ?>
                <br>
                <div class="wpel-buttons" style="display: flex; gap: 16px;">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="clean_debug_log">
                        <button type="submit" class="button">Clean Debug Log</button>
                    </form> 
                    <form method="post" action="">
                        <input type="hidden" name="action" value="download_debug_log">
                        <button type="submit" class="button">Download Debug Log</button>
                    </form>
                </div>

                <br>
                <code>error_log( 'Data Received: ' . print_r( $your_data, true ) );</code>    
            <?php

            /**
             * Clean Log
             */

            if ( isset( $_POST['action'] ) && $_POST['action'] === 'clean_debug_log' ) {
                $debug_log = WP_CONTENT_DIR . '/debug.log';
                if ( file_exists( $debug_log ) ) {
                    file_put_contents( $debug_log, '' );
                }
            }

            /**
             * Download
             */

             if ( isset( $_POST['action'] ) && $_POST['action'] === 'download_debug_log' ) {
                $debug_log = WP_CONTENT_DIR . '/debug.log';
                if (file_exists($debug_log)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . basename($debug_log));
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($debug_log));
                    ob_clean();
                    flush();
                    readfile($debug_log);
                    exit;
                } else {
                    echo 'Debug log file not found';
                }
            }

            /**
            * Show log in page
            */

            $debug_log = WP_CONTENT_DIR . '/debug.log';

            $output = '';
            if (file_exists($debug_log)) {
                $debug_log_entries = file( $debug_log, FILE_IGNORE_NEW_LINES );
                if(empty($debug_log_entries)){
                    ?>
                        <div class="wrap">
                            <h1>Errors</h1> 
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Error Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Debug log empty. No error found</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php
                }else{
                    ?>
                        <div class="wrap">
                            <h1>Errors</h1>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Error Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                    foreach ( $debug_log_entries as $data ) {
                                    ?>
                                        <tr>
                                            <td><?php echo $data?></td>
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
                $output .= '<h3>Debug log file not found. Hense no error found yet</h3>';
            }
            echo $output;
        }

        private function get_error_count() {
            // Get the path to the debug log file
            $debug_log = WP_CONTENT_DIR . '/debug.log';
        
            // Get the number of error lines in the debug log file
            $error_count = 0;
            if (file_exists($debug_log)) {
                $debug_log_entries = file( $debug_log, FILE_IGNORE_NEW_LINES );
                foreach ( $debug_log_entries as $entry ) {
                    if ( strpos( $entry, 'PHP Error:' ) !== false ) {
                        $error_count++;
                    }
                }
            }
        
            return $error_count;
        }

        public function my_errors_count_ajax_handler() {
            // Get the current error count
            $error_count = $this->get_error_count();
        
            // Return the error count as JSON
            wp_send_json_success( array(
                'count' => $error_count,
            ) );
        }

        

        public function add_my_page_to_admin_bar($wp_admin_bar) {
            // error_log('add_my_page_to_admin_bar called!');

            /* $debug_log = WP_CONTENT_DIR . '/debug.log';
            $error_count = 0;
            if (file_exists($debug_log)) {
                $debug_log_entries = file( $debug_log, FILE_IGNORE_NEW_LINES );
                $error_count = count($debug_log_entries);
            }

            $wp_admin_bar->add_node( array(
                'id'    => 'my-errors-page',
                'title' => 'WP Errors-'. '<span style="color:red;font-weight:bold;" class="update-plugins count-' . $error_count . '"><span class="update-count">' . $error_count . '</span></span>',
                'href'  => admin_url( 'tools.php?page=errors' ),
            ) ); */

            $error_count = $this->get_error_count();

            // Add the admin bar node
            $wp_admin_bar->add_node( array(
                'id'    => 'my-errors-page',
                'title' => 'WP Errors-'. '<span style="color:red;font-weight:bold;" id="my-errors-page" class="update-plugins count-' . $error_count . '"><span class="update-count">' . $error_count . '</span></span>',
                'href'  => admin_url( 'tools.php?page=errors' ),
            ) );

            // Enqueue the Ajax script
            wp_enqueue_script( 'my-errors-ajax', plugin_dir_url( __FILE__ ) . 'assets/my-errors-ajax.js', array( 'jquery' ), '1.0', true );

            // Add the Ajax endpoint to the script
            wp_localize_script( 'my-errors-ajax', 'myErrorsAjax', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'action' => 'my_errors_count',
                'interval' => 5000, // Check for updates every 5 seconds
            ) );

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

