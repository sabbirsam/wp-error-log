<?php
/**
 * Easy Error Log Activator
 *
 * This class is used to builds all of the tables when the plugin is activated
 *
 * @package EEL\Inc
 */
namespace EEL\Inc;

defined('ABSPATH') || die('Hey, what are you doing here? You silly human!');

/**
 * Activate class here
 */
class EEL_Activate {

	/**
	 * This function use to active the plugin and run after activated
	 */
	public static function eel_activate() {
		flush_rewrite_rules();
	}
}
