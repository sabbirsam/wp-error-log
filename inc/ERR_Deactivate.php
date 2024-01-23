<?php
/**
 * Easy Error Log Deactivator
 *
 * This class is used to builds all of the tables when the plugin is Deactivated
 *
 * @package ERROR\Inc
 */
namespace ERROR\Inc;

defined('ABSPATH') || die('Hey, what are you doing here? You silly human!');

/**
 * Deactivated plugin
 */
class ERR_Deactivate {

	/**
	 * This function use to deactivate the plugin and run after deactivate
	 */
	public static function err_deactivate() {
		flush_rewrite_rules();
	}
}
