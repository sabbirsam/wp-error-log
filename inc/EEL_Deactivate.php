<?php
/**
 * Easy Error Log Deactivator
 *
 * This class is used to builds all of the tables when the plugin is Deactivated
 *
 * @package EEL\Inc
 */
namespace EEL\Inc;

defined('ABSPATH') || die('Hey, what are you doing here? You silly human!');

/**
 * Deactivated plugin
 */
class EEL_Deactivate {

	/**
	 * This function use to deactivate the plugin and run after deactivate
	 */
	public static function eel_deactivate() {
		flush_rewrite_rules();
	}
}
