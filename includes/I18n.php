<?php
/**
 * Internationalization bootstrap
 *
 * Defines and loads the plugin text domain.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/includes
 * @since 1.0.0
 */

namespace PdcPod\Includes;

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://print.com
 * @since      1.0.0
 *
 * @package    Pdc_Pod
 * @subpackage Pdc_Pod/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Pdc_Pod
 * @subpackage Pdc_Pod/includes
 * @author     Tijmen <tijmen@print.com>
 */
class I18n {



	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			PDC_POD_NAME,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
