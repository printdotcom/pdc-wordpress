<?php
/**
 * Core
 *
 * Defines the core plugin class and bootstraps both admin and public hooks.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/includes
 * @since 1.0.0
 */

namespace PdcPod\Includes;

use PdcPod\Front\FrontCore;


/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://print.com
 * @since      1.0.0
 *
 * @package    Pdc_Pod
 * @subpackage Pdc_Pod/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Pdc_Pod
 * @subpackage Pdc_Pod/includes
 * @author     Tijmen <tijmen@print.com>
 */
class Core {


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Retrieves a namespaced meta key for this plugin.
	 *
	 * Prepends the plugin name and, unless $public is true, an underscore to hide the
	 * meta key from public display in WordPress admin UI.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name   Base meta key name, e.g. 'pdf_url'.
	 * @param bool   $public_meta_key Optional. Whether the meta key should be publicly visible (no leading underscore). Default false.
	 * @return string Fully qualified meta key for storage.
	 */
	public static function get_meta_key( $name, $public_meta_key = false ) {
		$meta_key_name = PDC_POD_NAME . '_' . $name;
		if ( $public_meta_key ) {
			return $meta_key_name;
		}
		return '_' . $meta_key_name;
	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Pdc_Pod_Loader. Orchestrates the hooks of the plugin.
	 * - Pdc_Pod_I18n. Defines internationalization functionality.
	 * - Pdc_Pod_Admin. Defines all hooks for the admin area.
	 * - Pdc_Pod_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		$this->loader = new Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Pdc_Pod_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new \PdcPod\Admin\AdminCore();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_pages' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_sections' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_filter( 'woocommerce_product_data_tabs', $plugin_admin, 'add_product_data_tab' );
		$this->loader->add_action( 'woocommerce_product_data_panels', $plugin_admin, 'render_product_data_tab' );
		$this->loader->add_action( 'woocommerce_variation_options', $plugin_admin, 'render_variation_data_fields', 10, 3 );
		$this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'save_variation_data_fields', 10, 2 );
		$this->loader->add_action( 'woocommerce_process_product_meta_simple', $plugin_admin, 'save_product_data_fields', 10 );
		$this->loader->add_action( 'woocommerce_process_product_meta_variable', $plugin_admin, 'save_product_data_fields', 10 );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'pdc_order_meta_box' );
		$this->loader->add_action( 'woocommerce_process_shop_order_meta', $plugin_admin, 'on_order_save' );
		$this->loader->add_action( 'rest_api_init', $plugin_admin, 'register_pdc_endpoints' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new FrontCore();

		$this->loader->add_filter( 'woocommerce_add_cart_item_data', $plugin_public, 'capture_cart_item_data', 10, 2 );
		$this->loader->add_filter( 'woocommerce_before_add_to_cart_button', $plugin_public, 'add_cart_item_data_nonce', 10, 2 );
		$this->loader->add_filter( 'woocommerce_checkout_create_order_line_item', $plugin_public, 'save_pdc_values_order_meta', 80, 4 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Pdc_Pod_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
}
