<?php
/**
 * Plugin entry
 *
 * File that bootstraps the plugin.
 *
 * @package Pdc_Pod
 * @since 1.0.0
 */

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://print.com
 * @since             1.0.0
 * @package           Pdc_Pod
 *
 * @wordpress-plugin
 * Plugin Name:       Print.com Print on Demand
 * Plugin URI:        https://github.com/printdotcom/pdc-pod
 * Description:       Allows customers to configure, edit and purchase products via the Print.com API.
 * Version:           1.0.0
 * Author:            Print.com
 * Author URI:        https://print.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pdc-pod
 * Domain Path:       /languages
 */

require_once __DIR__ . '/vendor/autoload.php';

use PdcPod\Includes\Core;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Current plugin version.
 *
 * @since 1.0.0
 * @var string PDC_POD_VERSION Plugin version.
 */
define( 'PDC_POD_VERSION', '1.0.0' );

/**
 * Plugin name
 *
 * @since 1.0.0
 * @var string PDC_POD_NAME Plugin name
 */
define( 'PDC_POD_NAME', 'pdc-pod' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function pdc_pod_run() {
	$plugin = new Core();
	$plugin->run();
}
pdc_pod_run();
