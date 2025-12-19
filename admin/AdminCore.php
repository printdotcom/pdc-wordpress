<?php
/**
 * Admin core
 *
 * Provides admin-specific hooks, pages, and integrations for the plugin.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/admin
 * @since 1.0.0
 */

namespace PdcPod\Admin;

use PdcPod\Admin\PrintDotCom\APIClient;
use PdcPod\Includes\Core;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://print.com
 * @since      1.0.0
 *
 * @package    Pdc_Pod
 * @subpackage Pdc_Pod/admin
 */

/**
 * Admin-specific functionality of the plugin applied to hooks.
 *
 * @package    PdcPodAdmin
 * @subpackage Pdc_Pod/admin
 * @author     Tijmen <tijmen@print.com>
 */
class AdminCore {
	/**
	 * Print.com API client instance.
	 *
	 * @since 1.0.0
	 * @var APIClient
	 */
	private APIClient $pdc_client;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->pdc_client = new APIClient();
	}

	/**
	 * Retrieves the meta key for the given key.
	 * Plug-in meta keys should not be shown to the public so are always prefixed
	 * with an underscore. They are also namespaced by using the plug-in name.
	 *
	 * @since 1.0.1
	 * @param string $key The meta key, ex. 'pdf_url'.
	 */
	private function get_meta_key( $key ) {
		return Core::get_meta_key( $key );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( PDC_POD_NAME . '-admin', plugin_dir_url( __FILE__ ) . 'css/pdc-pod-admin.css', array(), PDC_POD_VERSION, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// Make sure we can use the media file uploader.
		wp_enqueue_media();

		// Register admin JS scripts.
		wp_enqueue_script( PDC_POD_NAME . '-admin', plugin_dir_url( __FILE__ ) . 'js/pdc-pod-admin.js', array( 'jquery' ), PDC_POD_VERSION, false );
		wp_localize_script(
			PDC_POD_NAME . '-admin',
			'PDC_POD_ADMIN',
			array(
				'root'        => esc_url_raw( rest_url() ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'plugin_name' => PDC_POD_NAME,
				'pdc_url'     => $this->pdc_client->get_api_base_url(),
			)
		);
	}

	/**
	 * Register the Admin Menu pages for Print.com settings
	 *
	 * @since    1.0.0
	 */
	public function add_menu_pages() {
		add_menu_page( 'General Settings', 'Print.com', 'manage_options', PDC_POD_NAME, array( $this, 'page_general_settings' ), 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJMYWFnXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeD0iMCIgeT0iMCIgdmlld0JveD0iMCAwIDY5IDY5IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA2OSA2OSIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CiAgPHN0eWxlPgogICAgLnN0MXtmaWxsOiNmZmZ9CiAgPC9zdHlsZT4KICA8cGF0aCBpZD0iUGF0aF82MDQiIGQ9Ik01MC4zIDY1LjVjLTIzLjIgOS4zLTQxIC4yLTQ4LjUtMjcuMS01LjUtMjAgMi0yNS4xIDIyLjctMzQuNEM0OC43LTYuOSA2Mi44IDUuNyA2Ny43IDI4LjJjMy44IDE3LjQtLjYgMzAuNS0xNy40IDM3LjN6IiBzdHlsZT0iZmlsbDojZmYwMDQ4Ii8+CiAgPGcgaWQ9Ikdyb3VwXzgxMzQiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDE2LjM3MiAyNC43MjgpIj4KICAgIDxnIGlkPSJHcm91cF84MTMyIj4KICAgICAgPHBhdGggaWQ9IlBhdGhfNjA1IiBjbGFzcz0ic3QxIiBkPSJNNC4xIDcuNVYxLjRDNC4yLjIgMy43LTEgMi44LTEuOCAxLjctMi42LjQtMy0uOS0yLjloLTVWMTVjMCAuNS4zLjguOS44aDIuN1YxMWMuNi42IDEuNC45IDIuMy44IDEuMSAwIDIuMi0uNCAzLTEuMS43LS45IDEuMS0yIDEuMS0zLjJ6TS41IDYuN2MwIC42LS4xIDEuMi0uMyAxLjctLjIuNC0uNy42LTEuMS42LS41IDAtMS0uMi0xLjQtLjZWMGgxLjRDMCAwIC41LjUuNSAxLjV2NS4yeiIvPgogICAgICA8cGF0aCBpZD0iUGF0aF82MDYiIGNsYXNzPSJzdDEiIGQ9Ik0xMi44LTMuMmMtMS4yLS4xLTIuMy43LTIuNiAxLjh2LS43YzAtLjUtLjMtLjgtLjktLjhINi41djEzLjdjMCAuNS4zLjguOS44aDIuN1YzLjFjLjEtMS4zIDEtMi41IDIuMy0yLjcuMiAwIC40LS4yLjUtLjR2LTMuMWMwLS4xIDAtLjEtLjEtLjF6Ii8+CiAgICAgIDxwYXRoIGlkPSJQYXRoXzYwNyIgY2xhc3M9InN0MSIgZD0iTTIzLjUgMTEuNWgyLjdWLjVjLjItLjYuOC0xIDEuNC0uOS44IDAgMS4yLjUgMS4yIDEuNHY5LjdjMCAuNS4zLjcuOC43aDIuOFYuOGMuMS0xLjEtLjMtMi4xLTEtMi45LS41LS44LTEuNC0xLjItMi40LTEuMS0xLjEtLjEtMi4yLjUtMi43IDEuNXYtLjRjMC0uNS0uMy0uOC0uOS0uOGgtMy44djIuM2MwIC4zLjMuNi42LjZoLjR2MTAuOGMuMS40LjMuNy45Ljd6Ii8+CiAgICAgIDxwYXRoIGlkPSJQYXRoXzYwOCIgY2xhc3M9InN0MSIgZD0iTTIwLjIgMTEuNVY5LjJjMC0uMy0uMy0uNi0uNi0uNkgxOVYtMi4yYzAtLjUtLjMtLjgtLjktLjhoLTIuN3YxMy43YzAgLjUuMy43LjkuN2gzLjl6Ii8+CiAgICAgIDxwYXRoIGlkPSJQYXRoXzYwOSIgY2xhc3M9InN0MSIgZD0iTTQwLjIgOC43aC0uNGMtLjggMC0xLjMtLjQtMS4zLTEuM1YwaDIuMXYtMi4xYzAtLjUtLjMtLjctLjgtLjdoLTEuNHYtMS4xYzAtLjUtLjMtLjgtLjktLjhIMzVWNi45YzAgMS42LjMgMi44IDEgMy41LjcuNyAxLjcgMS4xIDMuMiAxLjFoMS45VjkuNGMwLS41LS4zLS43LS45LS43eiIvPgogICAgICA8cGF0aCBpZD0iUGF0aF82MTAiIGNsYXNzPSJzdDEiIGQ9Ik0xOC4xLTQuOWMtMS40LjYtMi41IDAtMy0xLjctLjMtMS4yLjEtMS41IDEuNC0yLjEgMS41LS43IDIuNC4xIDIuNyAxLjUuMyAxIDAgMS44LTEuMSAyLjN6Ii8+CiAgICA8L2c+CiAgICA8ZyBpZD0iR3JvdXBfODEzMyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTguODI0IDM4LjQwNikiPgogICAgICA8cGF0aCBpZD0iUGF0aF82MTEiIGNsYXNzPSJzdDEiIGQ9Ik0tMS42LTIyYy0xLjctMS4xLTMuOS0xLjEtNS41IDAtLjcuNi0xIDEuNS0xIDIuNHY0LjVjLS4xLjkuMyAxLjggMSAyLjQgMS43IDEuMSAzLjkgMS4xIDUuNSAwIC43LS42IDEtMS41IDEtMi40di0uNmMwLS40LS4yLS42LS43LS42aC0xLjRjLS40IDAtLjcuMi0uNy42di42YzAgLjctLjMgMS4xLTEgMS4xcy0xLS40LTEtMS4xdi00LjZjMC0uNy4zLTEuMSAxLTEuMXMxIC40IDEgMS4xdi42YzAgLjQuMi42LjcuNmgxLjRjLjQgMCAuNy0uMi43LS42di0uNmMuMS0uOC0uMy0xLjctMS0yLjN6Ii8+CiAgICAgIDxwYXRoIGlkPSJQYXRoXzYxMiIgY2xhc3M9InN0MSIgZD0iTTcuNS0yMmMtMS43LTEuMS0zLjktMS4xLTUuNSAwLS43LjYtMSAxLjUtMSAyLjR2NC41Yy0uMS45LjMgMS44IDEgMi40IDEuNyAxLjEgMy45IDEuMSA1LjUgMCAuNy0uNiAxLTEuNSAxLTIuNHYtNC41YzAtLjktLjQtMS44LTEtMi40em0tMS44IDYuOWMwIC43LS4zIDEuMS0xIDEuMXMtMS0uNC0xLTEuMXYtNC42YzAtLjcuMy0xLjEgMS0xLjFzMSAuNCAxIDEuMXY0LjZ6Ii8+CiAgICAgIDxwYXRoIGlkPSJQYXRoXzYxMyIgY2xhc3M9InN0MSIgZD0iTS0xMC4zLTEyYy0xLjEuNS0yIDAtMi40LTEuMy0uMy0xIC4xLTEuMiAxLjEtMS43IDEuMi0uNSAxLjkuMSAyLjEgMS4yLjQuNyAwIDEuNS0uOCAxLjguMS0uMS4xLS4xIDAgMHoiLz4KICAgICAgPHBhdGggaWQ9IlBhdGhfNjE0IiBjbGFzcz0ic3QxIiBkPSJNMjIuNi0xNC4zaC0uNHYtNS42YzAtLjgtLjItMS42LS43LTIuMi0uNS0uNS0xLjItLjgtMi0uOC0xIDAtMS45LjUtMi40IDEuMy0uNC0uOC0xLjMtMS4zLTIuMy0xLjItLjgtLjEtMS42LjMtMiAxLjF2LS4zYzAtLjQtLjItLjYtLjctLjZIOS40djEuOGMwIC4yLjIuNC40LjRoLjN2Ny44YzAgLjQuMi41LjcuNWgydi04Yy4xLS40LjYtLjcgMS0uNy42IDAgLjkuNC45IDEuMXY3LjFjMCAuNC4yLjUuNi41aDIuMXYtOGMuMi0uNC42LS43IDEtLjcuNiAwIC45LjQuOSAxLjF2Ny4xYzAgLjQuMi41LjYuNWgyLjl2LTEuN2MuMy0uMy4xLS41LS4yLS41eiIvPgogICAgPC9nPgogIDwvZz4KPC9zdmc+' );
	}

	/**
	 * Registers settings sections for the plugin admin page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_sections() {
		add_settings_section(
			PDC_POD_NAME . '-credentials',
			'Credentials',
			array( $this, 'section_credentials' ),
			PDC_POD_NAME,
		);
		add_settings_section(
			PDC_POD_NAME . '-product',
			'Product',
			array( $this, 'section_product' ),
			PDC_POD_NAME,
		);
	}

	/**
	 * Registers plugin settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		// API key setting: simple string sanitized via sanitize_text_field.
		register_setting(
			PDC_POD_NAME . '-options',
			PDC_POD_NAME . '-api_key',
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_api_key' ),
			)
		);
		// Environment setting: only allow 'stg' or 'prod'.
		register_setting(
			PDC_POD_NAME . '-options',
			PDC_POD_NAME . '-env',
			array(
				'type'              => 'string',
				'default'           => 'stg',
				'sanitize_callback' => array( $this, 'sanitize_env' ),
			)
		);
		// Product configuration: array of options; currently supports a boolean flag.
		register_setting(
			PDC_POD_NAME . '-options',
			PDC_POD_NAME . '-product',
			array(
				'type'              => 'array',
				'default'           => array( 'use_preset_copies' => false ),
				'sanitize_callback' => array( $this, 'sanitize_product' ),
			)
		);
	}

	/**
	 * Adds a Print.com tab to the product data tabs.
	 *
	 * @since 1.0.0
	 * @param array $tabs Existing product tabs.
	 * @return array Modified tabs.
	 */
	public function add_product_data_tab( $tabs ) {
		$tabs['pdc_printtab'] = array(
			'label'    => 'Print.com',
			'priority' => 60,
			'target'   => 'pdc_product_data_tab',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
		);

		return $tabs;
	}



	/**
	 * Saves the product settings.
	 *
	 * @since 1.0.0
	 * @param int $post_id The product post ID.
	 * @return void
	 */
	public function save_product_data_fields( $post_id ) {
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( sanitize_key( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		$key_product_sku = $this->get_meta_key( 'product_sku' );
		if ( isset( $_POST[ $key_product_sku ] ) ) {
			$raw_value = sanitize_key( wp_unslash( $_POST[ $key_product_sku ] ) );
			$sanitized = is_array( $raw_value ) ? array_map( 'sanitize_text_field', $raw_value ) : sanitize_text_field( $raw_value );
			update_post_meta( $post_id, $key_product_sku, $sanitized );
		}

		$key_preset_id = $this->get_meta_key( 'preset_id' );
		if ( isset( $_POST[ $key_preset_id ] ) ) {
			$raw_value = sanitize_key( wp_unslash( $_POST[ $key_preset_id ] ) );
			$sanitized = is_array( $raw_value ) ? array_map( 'sanitize_text_field', $raw_value ) : sanitize_text_field( $raw_value );
			update_post_meta( $post_id, $key_preset_id, $sanitized );
		}

		$key_pdf_url = $this->get_meta_key( 'pdf_url' );
		if ( isset( $_POST[ $key_pdf_url ] ) ) {
			$raw_value = sanitize_url( wp_unslash( $_POST[ $key_pdf_url ] ) );
			$sanitized = is_array( $raw_value ) ? array_map( 'sanitize_url', $raw_value ) : sanitize_text_field( $raw_value );
			update_post_meta( $post_id, $key_pdf_url, $sanitized );
		}
	}

	/**
	 * Renders metabox for legacy WooCommerce order screen.
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function pdc_meta_box_shop_order( $post ) {
		$order = wc_get_order( $post->ID );
		include plugin_dir_path( __FILE__ ) . 'partials/' . PDC_POD_NAME . '-html-order-metabox.php';
	}

	/**
	 * Renders metabox for WooCommerce 7.8+ orders page.
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function pdc_meta_box_page_wc_orders( $post ) {
		$order = wc_get_order( $post->get_ID() );
		include plugin_dir_path( __FILE__ ) . 'partials/' . PDC_POD_NAME . '-html-order-metabox.php';
	}

	/**
	 * Registers order metaboxes for various WooCommerce screens.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function pdc_order_meta_box() {
		// WooCommerce 7.7 and lower.
		add_meta_box(
			'pdc_order_meta_box',
			'Print.com',
			array( $this, 'pdc_meta_box_shop_order' ),
			'shop_order',
			'normal',
			'core'
		);

		// WooCommerce 7.8+.
		add_meta_box(
			'pdc_order_meta_box',
			'Print.com',
			array( $this, 'pdc_meta_box_page_wc_orders' ),
			'woocommerce_page_wc-orders',
			'normal',
			'core'
		);
	}

	/**
	 * Renders the product data tab content.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_product_data_tab() {
		global $post, $thepostid, $product_object;

		$pdc_pod_sku          = get_post_meta( $post->ID, $this->get_meta_key( 'product_sku' ), true );
		$pdc_pod_sku_title    = get_post_meta( $post->ID, $this->get_meta_key( 'product_title' ), true );
		$pdc_pod_preset_id    = get_post_meta( $post->ID, $this->get_meta_key( 'preset_id' ), true );
		$pdc_pod_preset_title = get_post_meta( $post->ID, $this->get_meta_key( 'preset_title' ), true );
		$preset_input_name    = $this->get_meta_key( 'preset_id' );

		$pdc_pod_presets_for_sku = array();
		if ( ! empty( $pdc_pod_sku ) ) {
			$pdc_pod_presets_for_sku = $this->pdc_client->get_presets( $pdc_pod_sku );
		}

		$pdc_products = $this->pdc_client->search_products();
		include plugin_dir_path( __FILE__ ) . 'partials/' . PDC_POD_NAME . '-admin-producttab.php';
	}

	/**
	 * Sanitizes the API key option value.
	 *
	 * Ensures a trimmed string without unsafe characters is stored.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw option value.
	 * @return string Sanitized API key string.
	 */
	public function sanitize_api_key( $value ) {
		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}
		return '';
	}

	/**
	 * Sanitizes the environment option value.
	 *
	 * Only 'stg' (test) and 'prod' (live) are accepted. Falls back to 'stg'.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw option value.
	 * @return string 'stg' or 'prod'.
	 */
	public function sanitize_env( $value ) {
		$val = is_string( $value ) ? strtolower( sanitize_text_field( $value ) ) : '';
		return in_array( $val, array( 'stg', 'prod' ), true ) ? $val : 'stg';
	}

	/**
	 * Sanitizes the product configuration option value.
	 *
	 * Currently supports:
	 * - use_preset_copies: bool. Checkbox style input.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw option value.
	 * @return array Sanitized configuration array.
	 */
	public function sanitize_product( $value ) {
		$sanitized = array( 'use_preset_copies' => false );
		if ( is_array( $value ) ) {
			$sanitized['use_preset_copies'] = ! empty( $value['use_preset_copies'] ) ? (bool) intval( $value['use_preset_copies'] ) : false;
		}
		return $sanitized;
	}

	/**
	 * Creates the settings page
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function page_general_settings() {
		include plugin_dir_path( __FILE__ ) . 'partials/' . PDC_POD_NAME . '-admin-general.php';
	}

	/**
	 * Creates the credentials section
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function section_credentials() {
		include plugin_dir_path( __FILE__ ) . 'partials/' . PDC_POD_NAME . '-admin-section-credentials.php';
	}

	/**
	 * Creates the product configuration section
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function section_product() {
		include plugin_dir_path( __FILE__ ) . 'partials/' . PDC_POD_NAME . '-admin-section-product.php';
	}

	/**
	 * Will save the order item meta data
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	/**
	 * Saves order item metadata on order save.
	 *
	 * @since 1.0.0
	 * @param int $order_item_id Order item ID.
	 * @return void
	 */
	public function on_order_save( int $order_item_id ) {
		// Check the nonce.
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {
			return;
		}

		$meta_pdf_url = $this->get_meta_key( 'pdf_url' );
		if ( isset( $_POST[ $meta_pdf_url ] ) ) {
			// URLs should be sanitized with esc_url_raw; always unslash first.
			$raw_pdf = sanitize_url( wp_unslash( $_POST[ $meta_pdf_url ] ) );
			$val_pdf = is_array( $raw_pdf ) ? array_map( 'esc_url_raw', $raw_pdf ) : esc_url_raw( $raw_pdf );
			update_post_meta( $order_item_id, $meta_pdf_url, $val_pdf );
		}
	}


	/**
	 * Registers the PDC REST API endpoints.
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function register_pdc_endpoints() {
		register_rest_route(
			'pdc/v1',
			'/products/(?P<sku>[^/]+)/presets',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'pdc_render_preset_select' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			'pdc/v1',
			'/verify',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'pdc_pod_verify_key' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			'pdc/v1',
			'/orders/(?P<id>\d+)/attach-pdf',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'pdc_attach_pdf' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
		register_rest_route(
			'pdc/v1',
			'/orders/(?P<id>\d+)/purchase',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'pdc_place_order' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			'pdc/v1',
			'/orders/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'pdc_order_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handles verification
	 * Hooked to endoint /verify
	 *
	 * @since 1.0.0
	 * @return bool|WP_Error
	 */
	public function pdc_pod_verify_key() {
		$is_authenticated = $this->pdc_client->is_authenticated();
		if ( ! $is_authenticated ) {
			return new \WP_Error(
				'pdc_pod_not_authenticated',
				__( 'Invalid credentials.', 'pdc-pod' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Handles incoming webhooks from Print.com.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request The REST request.
	 * @return void
	 */
	public function pdc_order_webhook( \WP_REST_Request $request ) {
		$body       = json_decode( $request->get_body() );
		$event_type = $body->event_type;
		$payload    = $body->payload;

		if ( 'ORDER_STATUS_CHANGED' === $event_type ) {
			$order_id      = $request->get_param( 'order_id' );
			$order_item_id = $request->get_param( 'order_item_id' );

			if ( 'ACCEPTEDBYSUPPLIER' === $payload->status ) {
				$this->on_webhook_in_production( $order_id, $order_item_id );
			}
		}

		if ( 'SHIPMENT_CREATED' === $event_type ) {
			$this->on_webhook_shipped( $payload->order_item_number, $payload->tracking_code );
		}
	}

	/**
	 * Sets an order item to 'production' when the webhook event is received.
	 *
	 * @since 1.0.0
	 * @param string $order_id      The WooCommerce order ID.
	 * @param string $order_item_id The WooCommerce order item ID.
	 * @return void
	 */
	private function on_webhook_in_production( string $order_id, string $order_item_id ) {
		$order_item = new \WC_Order_Item_Product( $order_item_id );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_status' ), 'production' );
		$order_item->save();

		$order = wc_get_order( $order_id );
		$note  = __( 'Item is being produced at Print.com.', 'pdc-pod' );
		$order->add_order_note( $note );
		$order->save();
	}

	/**
	 * Will attempt to retrieve a WC_Order_item by a Print.com Order Number
	 *
	 * @param [type] $pdc_order_item_number ex. 6000012345-1
	 * @return WC_Order_Item_Product
	 */
	/**
	 * Retrieves a WC_Order_Item_Product by Print.com order item number.
	 * We have to do this by direct query as WooCommerce does not expose
	 * a possiblity to get an order item by a meta key.
	 *
	 * @since 1.0.0
	 * @param string $pdc_order_item_number ex. 6000012345-1.
	 * @return integer|null
	 */
	private function get_order_item_id_by_order_item_number( $pdc_order_item_number ) {
		global $wpdb;

		$results = wp_cache_get( $this->get_meta_key( 'order_item_number' ), $pdc_order_item_number );
		if ( empty( $results ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT im.order_item_id 
					FROM {$wpdb->prefix}woocommerce_order_items AS i
					JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
					WHERE im.meta_key = %s AND im.meta_value = %s
					",
					$this->get_meta_key( 'order_item_number' ),
					$pdc_order_item_number
				)
			);
			wp_cache_set( $results, $results );
		}

		if ( empty( $results ) ) {
			return null;
		}

		$result = $results[0];
		return $result->order_item_id;
	}

	/**
	 * Marks an order item as shipped and stores the tracking URL when the webhook event is received.
	 *
	 * @since 1.0.0
	 * @param string $order_item_number Print.com order item number.
	 * @param string $tracking_url      Tracking URL provided by Print.com.
	 * @return void
	 */
	private function on_webhook_shipped( string $order_item_number, string $tracking_url ) {
		$order_item_id = $this->get_order_item_id_by_order_item_number( $order_item_number );
		$order_item    = new \WC_Order_Item_Product( $order_item_id );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_tnt_url' ), $tracking_url );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_status' ), 'shipped' );
		$order_item->save();

		$order = wc_get_order( $order_item->wp_order_id );
		$note  = sprintf(
			// translators: placeholder is a URL to the track & trace page.
			__( 'Item has been shipped by Print.com. Track & Trace code: <a href="%1$s">%2$s</a>.', 'pdc-pod' ),
			$tracking_url,
			$tracking_url,
		);
		$order->add_order_note( $note );
		$order->save();
	}

	/**
	 * REST callback to attach a PDF URL to an order item.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request The REST request.
	 * @return string The stored PDF URL.
	 */
	public function pdc_attach_pdf( \WP_REST_Request $request ) {
		$order_item_id = $request->get_param( 'orderItemId' );
		$pdf_url       = $request->get_param( 'pdfUrl' );

		$meta_key_pdf_url = $this->get_meta_key( 'pdf_url' );
		$order_item       = new \WC_Order_Item_Product( $order_item_id );
		$order_item->update_meta_data( $meta_key_pdf_url, $pdf_url );
		$order_item->save_meta_data();
		return $pdf_url;
	}

	/**
	 * Implementation of API method attached to GET /products/:sku/presets
	 * Will list the presets for a given product for each selection.
	 *
	 * @param       \WP_REST_Request $request the request.
	 * @since      1.0.0
	 */
	public function pdc_render_preset_select( \WP_REST_Request $request ) {
		$sku = $request->get_param( 'sku' );
		$sku = is_string( $sku ) ? sanitize_text_field( $sku ) : '';
		if ( empty( $sku ) ) {
			return new \WP_Error(
				'pdc_missing_sku',
				__( 'Product SKU is required.', 'pdc-pod' ),
				array( 'status' => 400 )
			);
		}

		$response = $this->pdc_client->get_presets( $sku );
		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'pdc_presets_fetch_failed',
				sprintf(
					/* translators: %s is the error message returned by the Print.com API. */
					__( 'Could not retrieve presets: %s', 'pdc-pod' ),
					$response->get_error_message()
				),
				array( 'status' => 500 )
			);
		}

		$pdc_pod_presets_for_sku = $response;
		$pdc_pod_preset_id       = '';
		ob_start();
		include plugin_dir_path( __FILE__ ) . 'partials/' . PDC_POD_NAME . '-admin-preset-select.php';
		$preset_select_html = ob_get_contents();
		ob_end_clean();
		return rest_ensure_response(
			array(
				'html' => $preset_select_html,
			)
		);
	}

	/**
	 * Initiates a purchase at Print.com for an order item.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request instance.
	 * @return \WP_REST_Response|\WP_Error REST response or error.
	 */
	public function pdc_place_order( \WP_REST_Request $request ) {
		$order_item_id = absint( $request->get_param( 'id' ) );
		if ( empty( $order_item_id ) ) {
			return new \WP_Error(
				'pdc_missing_order_item',
				__( 'Order item ID is required.', 'pdc-pod' ),
				array( 'status' => 400 )
			);
		}

		$pdc_product_config = get_option( PDC_POD_NAME . '-product' );

		$result = $this->pdc_client->purchase_order_item( $order_item_id, $pdc_product_config );
		if ( is_wp_error( $result ) ) {
			$status = absint( $result->get_error_code() );
			if ( 0 === $status ) {
				$status = 500;
			}
			return new \WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				array_merge(
					array(
						'status' => $status,
					),
					(array) $result->get_error_data()
				)
			);
		}
		$pdc_order               = $result->order;
		$pdc_order_item          = $pdc_order->items[0];
		$pdc_order_item_shipment = $pdc_order_item->shipments[0];
		$order_item              = new \WC_Order_Item_Product( $order_item_id );

		$order_item->update_meta_data( $this->get_meta_key( 'order' ), $pdc_order );
		$order_item->update_meta_data( $this->get_meta_key( 'purchase_date' ), gmdate( 'c' ) );
		// Map external API fields to local snake_case variables for linting compliance.
		$order_number = $pdc_order->orderNumber; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$grand_total  = $pdc_order->grandTotal; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$order_item->update_meta_data( $this->get_meta_key( 'order_number' ), $order_number );
		$order_item->update_meta_data( $this->get_meta_key( 'grand_total' ), $grand_total );
		$order_item->update_meta_data( $this->get_meta_key( 'order_status' ), $pdc_order->status );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item' ), $pdc_order_item );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_shipment' ), $pdc_order_item_shipment );
		$order_item_number = $pdc_order_item->orderItemNumber; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$order_item_status = $pdc_order_item->status; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$order_item_total  = $pdc_order_item->grandTotal; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_number' ), $order_item_number );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_status' ), $order_item_status );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_grand_total' ), $order_item_total );
		$order_item->save();

		$order_id = wc_get_order_id_by_order_item_id( $order_item_id );
		$order    = wc_get_order( $order_id );

		$note = sprintf(
			// translators: placeholder is the order number.
			__( 'Item purchased at Print.com with order number: %s.', 'pdc-pod' ),
			$order_number
		);
		$order->add_order_note( $note );

		return rest_ensure_response(
			array(
				'order' => $pdc_order,
			)
		);
	}

	/**
	 * Renders variation data fields partial in the product editor.
	 *
	 * @since 1.0.0
	 * @param int      $index          Variation index.
	 * @param array    $variation_data Variation data.
	 * @param \WP_Post $variation      Variation post object.
	 * @return void
	 */
	public function render_variation_data_fields( int $index, array $variation_data, \WP_Post $variation ) {
		global $post;

		$pdc_pod_variation_id = isset( $variation->ID ) ? intval( $variation->ID ) : 0;
		$pdc_pod_parent_id    = isset( $variation->post_parent ) ? intval( $variation->post_parent ) : 0;

		$pdc_pod_meta_key_pdf_url   = $this->get_meta_key( 'pdf_url' );
		$pdc_pod_meta_key_sku       = $this->get_meta_key( 'product_sku' );
		$pdc_pod_meta_key_preset_id = $this->get_meta_key( 'preset_id' );

		$pdc_pod_index = isset( $index ) ? intval( $index ) : 0;

		$pdc_pod_sku       = get_post_meta( $pdc_pod_parent_id, $pdc_pod_meta_key_sku, true );
		$pdc_pod_preset_id = get_post_meta( $pdc_pod_variation_id, $pdc_pod_meta_key_preset_id, true );

		$pdc_pod_presets_for_sku = array();
		if ( ! empty( $pdc_pod_sku ) ) {
			$pdc_pod_presets_for_sku = $this->pdc_client->get_presets( $pdc_pod_sku );
		}

		include plugin_dir_path( __FILE__ ) . 'partials/' . PDC_POD_NAME . '-admin-variation-data.php';
	}

	/**
	 * Saves variation data fields from the product editor.
	 *
	 * @since 1.0.0
	 * @param int $variation_id Variation ID.
	 * @param int $i            Index in submitted arrays.
	 * @return void
	 */
	public function save_variation_data_fields( $variation_id, $i ) {

		$nonce = isset( $_POST[ PDC_POD_NAME . '_variations_nonce' . $i ] )
			? sanitize_text_field( wp_unslash( $_POST[ PDC_POD_NAME . '_variations_nonce' . $i ] ) )
			: '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, PDC_POD_NAME . '_save_variations' . $i ) ) {
			return;
		}

		$fields = array(
			'pdf_url'   => $this->get_meta_key( 'pdf_url' ),
			'preset_id' => $this->get_meta_key( 'preset_id' ),
		);

		foreach ( $fields as $meta_key ) {
			if ( isset( $_POST[ $meta_key ] ) && isset( $_POST[ $meta_key ][ $i ] ) ) {

				// We capture the raw data to check its type.
				if ( is_array( $_POST[ $meta_key ][ $i ] ) ) {
					$val = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $meta_key ][ $i ] ) );
				} else {
					$val = sanitize_text_field( wp_unslash( $_POST[ $meta_key ][ $i ] ) );
				}

				update_post_meta( $variation_id, $meta_key, $val );
			}
		}
	}
}
