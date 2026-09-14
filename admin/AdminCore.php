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
use PdcPod\Includes\Logger;

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
	 * Action Scheduler hook used to run a queued automatic purchase.
	 *
	 * @since 1.5.0
	 * @var string
	 */
	const AUTO_PURCHASE_HOOK = 'pdc_pod_auto_purchase_order';

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
	 * @param APIClient $pdc_api_client The api client.
	 * @since    1.0.0
	 */
	public function __construct( $pdc_api_client ) {
		$this->pdc_client = $pdc_api_client;
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
				'root'                   => esc_url_raw( rest_url() ),
				'nonce'                  => wp_create_nonce( 'wp_rest' ),
				'plugin_name'            => PDC_POD_NAME,
				'pdc_url'                => $this->pdc_client->get_api_base_url(),
				'confirm_force_purchase' => __( 'This item may already have been purchased at Print.com. Check there first. Purchase again anyway?', 'pdc-pod' ),
			)
		);
	}

	/**
	 * Register the Admin Menu pages for Print.com settings
	 *
	 * @since    1.0.0
	 */
	public function add_menu_pages() {
		add_menu_page( 'Print.com', 'Print.com', 'manage_options', PDC_POD_NAME, array( $this, 'page_general_settings' ), 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJMYWFnXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeD0iMCIgeT0iMCIgdmlld0JveD0iMCAwIDY5IDY5IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA2OSA2OSIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CiAgPHN0eWxlPgogICAgLnN0MXtmaWxsOiNmZmZ9CiAgPC9zdHlsZT4KICA8cGF0aCBpZD0iUGF0aF82MDQiIGQ9Ik01MC4zIDY1LjVjLTIzLjIgOS4zLTQxIC4yLTQ4LjUtMjcuMS01LjUtMjAgMi0yNS4xIDIyLjctMzQuNEM0OC43LTYuOSA2Mi44IDUuNyA2Ny43IDI4LjJjMy44IDE3LjQtLjYgMzAuNS0xNy40IDM3LjN6IiBzdHlsZT0iZmlsbDojZmYwMDQ4Ii8+CiAgPGcgaWQ9Ikdyb3VwXzgxMzQiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDE2LjM3MiAyNC43MjgpIj4KICAgIDxnIGlkPSJHcm91cF84MTMyIj4KICAgICAgPHBhdGggaWQ9IlBhdGhfNjA1IiBjbGFzcz0ic3QxIiBkPSJNNC4xIDcuNVYxLjRDNC4yLjIgMy43LTEgMi44LTEuOCAxLjctMi42LjQtMy0uOS0yLjloLTVWMTVjMCAuNS4zLjguOS44aDIuN1YxMWMuNi42IDEuNC45IDIuMy44IDEuMSAwIDIuMi0uNCAzLTEuMS43LS45IDEuMS0yIDEuMS0zLjJ6TS41IDYuN2MwIC42LS4xIDEuMi0uMyAxLjctLjIuNC0uNy42LTEuMS42LS41IDAtMS0uMi0xLjQtLjZWMGgxLjRDMCAwIC41LjUuNSAxLjV2NS4yeiIvPgogICAgICA8cGF0aCBpZD0iUGF0aF82MDYiIGNsYXNzPSJzdDEiIGQ9Ik0xMi44LTMuMmMtMS4yLS4xLTIuMy43LTIuNiAxLjh2LS43YzAtLjUtLjMtLjgtLjktLjhINi41djEzLjdjMCAuNS4zLjguOS44aDIuN1YzLjFjLjEtMS4zIDEtMi41IDIuMy0yLjcuMiAwIC40LS4yLjUtLjR2LTMuMWMwLS4xIDAtLjEtLjEtLjF6Ii8+CiAgICAgIDxwYXRoIGlkPSJQYXRoXzYwNyIgY2xhc3M9InN0MSIgZD0iTTIzLjUgMTEuNWgyLjdWLjVjLjItLjYuOC0xIDEuNC0uOS44IDAgMS4yLjUgMS4yIDEuNHY5LjdjMCAuNS4zLjcuOC43aDIuOFYuOGMuMS0xLjEtLjMtMi4xLTEtMi45LS41LS44LTEuNC0xLjItMi40LTEuMS0xLjEtLjEtMi4yLjUtMi43IDEuNXYtLjRjMC0uNS0uMy0uOC0uOS0uOGgtMy44djIuM2MwIC4zLjMuNi42LjZoLjR2MTAuOGMuMS40LjMuNy45Ljd6Ii8+CiAgICAgIDxwYXRoIGlkPSJQYXRoXzYwOCIgY2xhc3M9InN0MSIgZD0iTTIwLjIgMTEuNVY5LjJjMC0uMy0uMy0uNi0uNi0uNkgxOVYtMi4yYzAtLjUtLjMtLjgtLjktLjhoLTIuN3YxMy43YzAgLjUuMy43LjkuN2gzLjl6Ii8+CiAgICAgIDxwYXRoIGlkPSJQYXRoXzYwOSIgY2xhc3M9InN0MSIgZD0iTTQwLjIgOC43aC0uNGMtLjggMC0xLjMtLjQtMS4zLTEuM1YwaDIuMXYtMi4xYzAtLjUtLjMtLjctLjgtLjdoLTEuNHYtMS4xYzAtLjUtLjMtLjgtLjktLjhIMzVWNi45YzAgMS42LjMgMi44IDEgMy41LjcuNyAxLjcgMS4xIDMuMiAxLjFoMS45VjkuNGMwLS41LS4zLS43LS45LS43eiIvPgogICAgICA8cGF0aCBpZD0iUGF0aF82MTAiIGNsYXNzPSJzdDEiIGQ9Ik0xOC4xLTQuOWMtMS40LjYtMi41IDAtMy0xLjctLjMtMS4yLjEtMS41IDEuNC0yLjEgMS41LS43IDIuNC4xIDIuNyAxLjUuMyAxIDAgMS44LTEuMSAyLjN6Ii8+CiAgICA8L2c+CiAgICA8ZyBpZD0iR3JvdXBfODEzMyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTguODI0IDM4LjQwNikiPgogICAgICA8cGF0aCBpZD0iUGF0aF82MTEiIGNsYXNzPSJzdDEiIGQ9Ik0tMS42LTIyYy0xLjctMS4xLTMuOS0xLjEtNS41IDAtLjcuNi0xIDEuNS0xIDIuNHY0LjVjLS4xLjkuMyAxLjggMSAyLjQgMS43IDEuMSAzLjkgMS4xIDUuNSAwIC43LS42IDEtMS41IDEtMi40di0uNmMwLS40LS4yLS42LS43LS42aC0xLjRjLS40IDAtLjcuMi0uNy42di42YzAgLjctLjMgMS4xLTEgMS4xcy0xLS40LTEtMS4xdi00LjZjMC0uNy4zLTEuMSAxLTEuMXMxIC40IDEgMS4xdi42YzAgLjQuMi42LjcuNmgxLjRjLjQgMCAuNy0uMi43LS42di0uNmMuMS0uOC0uMy0xLjctMS0yLjN6Ii8+CiAgICAgIDxwYXRoIGlkPSJQYXRoXzYxMiIgY2xhc3M9InN0MSIgZD0iTTcuNS0yMmMtMS43LTEuMS0zLjktMS4xLTUuNSAwLS43LjYtMSAxLjUtMSAyLjR2NC41Yy0uMS45LjMgMS44IDEgMi40IDEuNyAxLjEgMy45IDEuMSA1LjUgMCAuNy0uNiAxLTEuNSAxLTIuNHYtNC41YzAtLjktLjQtMS44LTEtMi40em0tMS44IDYuOWMwIC43LS4zIDEuMS0xIDEuMXMtMS0uNC0xLTEuMXYtNC42YzAtLjcuMy0xLjEgMS0xLjFzMSAuNCAxIDEuMXY0LjZ6Ii8+CiAgICAgIDxwYXRoIGlkPSJQYXRoXzYxMyIgY2xhc3M9InN0MSIgZD0iTS0xMC4zLTEyYy0xLjEuNS0yIDAtMi40LTEuMy0uMy0xIC4xLTEuMiAxLjEtMS43IDEuMi0uNSAxLjkuMSAyLjEgMS4yLjQuNyAwIDEuNS0uOCAxLjguMS0uMS4xLS4xIDAgMHoiLz4KICAgICAgPHBhdGggaWQ9IlBhdGhfNjE0IiBjbGFzcz0ic3QxIiBkPSJNMjIuNi0xNC4zaC0uNHYtNS42YzAtLjgtLjItMS42LS43LTIuMi0uNS0uNS0xLjItLjgtMi0uOC0xIDAtMS45LjUtMi40IDEuMy0uNC0uOC0xLjMtMS4zLTIuMy0xLjItLjgtLjEtMS42LjMtMiAxLjF2LS4zYzAtLjQtLjItLjYtLjctLjZIOS40djEuOGMwIC4yLjIuNC40LjRoLjN2Ny44YzAgLjQuMi41LjcuNWgydi04Yy4xLS40LjYtLjcgMS0uNy42IDAgLjkuNC45IDEuMXY3LjFjMCAuNC4yLjUuNi41aDIuMXYtOGMuMi0uNC42LS43IDEtLjcuNiAwIC45LjQuOSAxLjF2Ny4xYzAgLjQuMi41LjYuNWgyLjl2LTEuN2MuMy0uMy4xLS41LS4yLS41eiIvPgogICAgPC9nPgogIDwvZz4KPC9zdmc+' );
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
			PDC_POD_NAME . '-general',
		);
		add_settings_section(
			PDC_POD_NAME . '-product',
			'Product',
			array( $this, 'section_product' ),
			PDC_POD_NAME . '-product',
		);
		add_settings_section(
			PDC_POD_NAME . '-orders',
			'Orders',
			array( $this, 'section_orders' ),
			PDC_POD_NAME . '-orders',
		);
		add_settings_section(
			PDC_POD_NAME . '-support',
			'Support',
			array( $this, 'section_support' ),
			PDC_POD_NAME . '-support',
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
			PDC_POD_NAME . '-general-options',
			PDC_POD_NAME . '-api_key',
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_api_key' ),
			)
		);
		// Environment setting: only allow 'stg' or 'prod'.
		register_setting(
			PDC_POD_NAME . '-general-options',
			PDC_POD_NAME . '-env',
			array(
				'type'              => 'string',
				'default'           => 'stg',
				'sanitize_callback' => array( $this, 'sanitize_env' ),
			)
		);
		// Product configuration: array of options; currently supports a boolean flag.
		register_setting(
			PDC_POD_NAME . '-product-options',
			PDC_POD_NAME . '-product',
			array(
				'type'              => 'array',
				'default'           => array( 'use_preset_copies' => false ),
				'sanitize_callback' => array( $this, 'sanitize_product' ),
			)
		);
		// Order configuration: array of options controlling automatic purchasing.
		register_setting(
			PDC_POD_NAME . '-orders-options',
			PDC_POD_NAME . '-orders',
			array(
				'type'              => 'array',
				'default'           => array(
					'auto_purchase'  => false,
					'trigger_status' => 'processing',
				),
				'sanitize_callback' => array( $this, 'sanitize_orders' ),
			)
		);
		// Log level setting: controls which messages are written to the log.
		register_setting(
			PDC_POD_NAME . '-support-options',
			PDC_POD_NAME . '-loglevel',
			array(
				'type'              => 'string',
				'default'           => 'error',
				'sanitize_callback' => array( $this, 'sanitize_loglevel' ),
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
	 * Retrieves the PDF URL for a specific order item.
	 *
	 * This method checks for a PDF URL in the order item metadata.
	 * It also provides a filter to allow external overrides of the URL.
	 *
	 * @since 1.2.0
	 * @param int $pdc_pod_order_item_id The WooCommerce order item ID.
	 * @return string|bool The PDF URL if found, or false.
	 */
	public function get_pdf_url_by_order_item_id( $pdc_pod_order_item_id ) {
		$pdf_url = wc_get_order_item_meta( $pdc_pod_order_item_id, Core::get_meta_key( 'pdf_url' ), true );

		/**
		 * Filter the PDF URL for an order item.
		 *
		 * This allows developers to override the PDF URL retrieval logic
		 * for specific order items.
		 *
		 * @since 1.2.0
		 * @param string|bool $pdf_url      The PDF URL, or false if not found.
		 * @param int         $order_item_id The WooCommerce order item ID.
		 */
		return apply_filters( 'pdc_pod_order_item_pdf_url', $pdf_url, $pdc_pod_order_item_id );
	}

	/**
	 * Retrieves the preset ID for a given order item.
	 *
	 * Falls back to the variation or product preset if the order item has no
	 * preset metadata directly set.
	 *
	 * @since 1.0.0
	 *
	 * @param int $pdc_pod_order_item_id The WooCommerce order item ID.
	 * @return string The preset ID, or an empty string if not found.
	 */
	public function get_preset_id_by_order_item_id( $pdc_pod_order_item_id ) {
		$pdc_pod_preset_id = wc_get_order_item_meta( $pdc_pod_order_item_id, Core::get_meta_key( 'preset_id' ), true );
		if ( empty( $pdc_pod_preset_id ) ) {
			$pdc_pod_order_item_product = new \WC_Order_Item_Product( $pdc_pod_order_item_id );
			$pdc_pod_variation_id       = $pdc_pod_order_item_product->get_variation_id();
			if ( $pdc_pod_variation_id ) {
				$pdc_pod_preset_id = get_post_meta( $pdc_pod_variation_id, Core::get_meta_key( 'preset_id' ), true );
			}

			if ( empty( $pdc_pod_preset_id ) ) {
				$pdc_pod_product_id = $pdc_pod_order_item_product->get_product_id();
				if ( $pdc_pod_product_id ) {
					$pdc_pod_preset_id = get_post_meta( $pdc_pod_product_id, Core::get_meta_key( 'preset_id' ), true );
				}
			}
		}
		return $pdc_pod_preset_id;
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

		$pdc_products    = array();
		$search_response = $this->pdc_client->search_products();
		if ( ! is_wp_error( $search_response ) ) {
			$pdc_products = $search_response;
		}

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
	 * Sanitizes the log level option value.
	 *
	 * Only 'none', 'error', and 'debug' are accepted. Falls back to 'error'.
	 *
	 * @since 1.2.0
	 *
	 * @param mixed $value Raw option value.
	 * @return string 'none', 'error', or 'debug'.
	 */
	public function sanitize_loglevel( $value ) {
		$val = is_string( $value ) ? strtolower( sanitize_text_field( $value ) ) : '';
		return in_array( $val, array( 'none', 'error', 'debug' ), true ) ? $val : 'error';
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
	 * Sanitizes the order configuration option value.
	 *
	 * Supports:
	 * - auto_purchase: bool. Checkbox style input.
	 * - trigger_status: string. An order status key without the 'wc-' prefix.
	 *
	 * An unknown or disallowed status falls back to 'processing' so that a
	 * tampered or stale value can never arm the purchase on, for example, a
	 * cancelled order.
	 *
	 * @since 1.5.0
	 *
	 * @param mixed $value Raw option value.
	 * @return array Sanitized configuration array.
	 */
	public function sanitize_orders( $value ) {
		$sanitized = array(
			'auto_purchase'  => false,
			'trigger_status' => 'processing',
		);

		if ( ! is_array( $value ) ) {
			return $sanitized;
		}

		$sanitized['auto_purchase'] = ! empty( $value['auto_purchase'] );

		$trigger_status = isset( $value['trigger_status'] ) ? sanitize_key( $value['trigger_status'] ) : '';
		if ( array_key_exists( $trigger_status, $this->get_auto_purchase_statuses() ) ) {
			$sanitized['trigger_status'] = $trigger_status;
		}

		return $sanitized;
	}

	/**
	 * Lists the order statuses that may trigger an automatic purchase.
	 *
	 * Every registered status is offered, including statuses added by other
	 * plugins, except the ones where purchasing print would always be wrong.
	 * Keys are returned without the 'wc-' prefix so they can be compared
	 * directly to the status passed by woocommerce_order_status_changed.
	 *
	 * @since 1.5.0
	 * @return array Map of status key to translated label.
	 */
	public function get_auto_purchase_statuses() {
		if ( ! function_exists( 'wc_get_order_statuses' ) ) {
			return array();
		}

		$denied  = array( 'wc-pending', 'wc-cancelled', 'wc-refunded', 'wc-failed', 'wc-checkout-draft' );
		$allowed = array();
		foreach ( wc_get_order_statuses() as $status_key => $status_label ) {
			if ( in_array( $status_key, $denied, true ) ) {
				continue;
			}
			$allowed[ preg_replace( '/^wc-/', '', $status_key ) ] = $status_label;
		}

		return $allowed;
	}

	/**
	 * Reads the stored order configuration.
	 *
	 * Normalizes types only. The trigger status is deliberately not validated
	 * against the currently registered statuses here: deactivating the plugin
	 * that provides a custom status must not silently move the trigger onto
	 * another status.
	 *
	 * @since 1.5.0
	 * @return array Configuration array with auto_purchase and trigger_status.
	 */
	public function get_orders_config() {
		$config = get_option( PDC_POD_NAME . '-orders' );
		if ( ! is_array( $config ) ) {
			$config = array();
		}

		return array(
			'auto_purchase'  => ! empty( $config['auto_purchase'] ),
			'trigger_status' => ! empty( $config['trigger_status'] ) ? sanitize_key( $config['trigger_status'] ) : 'processing',
		);
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
	 * Creates the product configuration section.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function section_product() {
		include plugin_dir_path( __FILE__ ) . 'partials/' . PDC_POD_NAME . '-admin-section-product.php';
	}

	/**
	 * Creates the order configuration section.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function section_orders() {
		include plugin_dir_path( __FILE__ ) . 'partials/' . PDC_POD_NAME . '-admin-section-orders.php';
	}

	/**
	 * Creates the support section.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function section_support() {
		include __DIR__ . '/partials/' . PDC_POD_NAME . '-admin-section-support.php';
	}

	/**
	 * Suppresses the default settings error display for this plugin's settings page.
	 *
	 * Removes the settings_errors() callback from the admin_notices hook so that
	 * notifications are rendered inside the settings form sections instead of the
	 * page head.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function suppress_settings_notices() {
		$screen = get_current_screen();
		if ( $screen && 'toplevel_page_' . PDC_POD_NAME === $screen->id ) {
			remove_action( 'admin_notices', 'settings_errors' );
		}
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
			'/download-logs',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'download_logs' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
		register_rest_route(
			'pdc/v1',
			'/order-items/(?P<id>\d+)/attach-pdf',
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
			'/order-items/(?P<id>\d+)/purchase',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'pdc_place_order_item' ),
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
		register_rest_route(
			'pdc/v1',
			'/orders/(?P<id>\d+)/purchase',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'pdc_purchase_order' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * REST callback to purchase all purchasable items in a WooCommerce order.
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response|\WP_Error REST response or error.
	 */
	public function pdc_purchase_order( \WP_REST_Request $request ) {
		$order_id = $request->get_param( 'id' );

		Logger::log(
			'purchasing order',
			'debug',
			array(
				'order_id' => $order_id,
			)
		);

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new \WP_Error(
				'pdc_order_not_found',
				__( 'Order not found.', 'pdc-pod' ),
				array( 'status' => 404 )
			);
		}

		$purchasable = $this->get_purchasable_items( $order );

		if ( empty( $purchasable['items'] ) ) {
			return new \WP_Error(
				'pdc_no_items_to_purchase',
				__( 'No valid items found to purchase. Ensure items have both a PDF and a preset assigned.', 'pdc-pod' ),
				array( 'status' => 400 )
			);
		}

		$pdc_order = $this->purchase_prepared_items( $order, $purchasable['items'] );

		if ( is_wp_error( $pdc_order ) ) {
			return $pdc_order;
		}

		return rest_ensure_response(
			array(
				'order' => $pdc_order,
			)
		);
	}

	/**
	 * Splits the items of an order into the ones that can be purchased and the
	 * ones that cannot.
	 *
	 * An item is purchasable when it has a connected preset, an attached PDF and
	 * has not been purchased before.
	 *
	 * Only items that look like they were meant to be Print.com items end up in
	 * the skipped list: an item with a preset but no PDF (or the other way
	 * around) is a misconfiguration worth reporting, while an item with neither
	 * is simply an ordinary WooCommerce product and is ignored silently.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Order $order The WooCommerce order.
	 * @return array {
	 *     @type array $items   Purchasable items, shaped for purchase_order_items().
	 *     @type array $skipped Map of order item ID to the reason it was skipped.
	 * }
	 */
	public function get_purchasable_items( $order ) {
		$items   = array();
		$skipped = array();

		foreach ( $order->get_items() as $order_item ) {
			$order_item_id     = $order_item->get_id();
			$pdc_pod_preset_id = $this->get_preset_id_by_order_item_id( $order_item_id );
			$pdc_pod_pdf_url   = $this->get_pdf_url_by_order_item_id( $order_item_id );
			$purchase_date     = wc_get_order_item_meta( $order_item_id, $this->get_meta_key( 'purchase_date' ), true );

			if ( ! empty( $purchase_date ) ) {
				continue;
			}

			$purchase_state = $this->get_item_purchase_state( $order_item_id );
			if ( 'in_progress' === $purchase_state ) {
				$skipped[ $order_item_id ] = __( 'a purchase is already in progress', 'pdc-pod' );
				continue;
			}
			if ( 'unknown' === $purchase_state ) {
				$skipped[ $order_item_id ] = __( 'the last purchase timed out, check Print.com before retrying', 'pdc-pod' );
				continue;
			}

			if ( empty( $pdc_pod_preset_id ) && empty( $pdc_pod_pdf_url ) ) {
				// Not a Print.com item at all.
				continue;
			}

			if ( empty( $pdc_pod_preset_id ) ) {
				$skipped[ $order_item_id ] = __( 'no preset connected', 'pdc-pod' );
				continue;
			}

			if ( empty( $pdc_pod_pdf_url ) ) {
				$skipped[ $order_item_id ] = __( 'no PDF attached', 'pdc-pod' );
				continue;
			}

			$items[] = array(
				'order_item'        => $order_item,
				'pdc_pod_preset_id' => $pdc_pod_preset_id,
				'pdc_pod_pdf_url'   => $pdc_pod_pdf_url,
			);
		}

		return array(
			'items'   => $items,
			'skipped' => $skipped,
		);
	}

	/**
	 * Purchases prepared items at Print.com and writes the result back onto the
	 * WooCommerce order.
	 *
	 * Shared by the REST endpoint behind the 'Purchase all' button and by the
	 * automatic purchase job, so both behave identically.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Order $order The WooCommerce order.
	 * @param array     $items Items as returned by get_purchasable_items().
	 * @return object|\WP_Error The Print.com order on success.
	 */
	public function purchase_prepared_items( $order, array $items ) {
		$pdc_product_config = get_option( PDC_POD_NAME . '-product' );

		// Claim the items before the call. Placing an order can take over a
		// minute, and for that whole minute nothing else may buy them again.
		foreach ( $items as $item ) {
			$this->set_item_purchase_state( $item['order_item'], 'in_progress' );
		}

		$result = $this->pdc_client->purchase_order_items( $order, $items, $pdc_product_config );

		if ( is_wp_error( $result ) ) {
			$unknown = self::is_unknown_outcome( $result );

			// Print.com rejects the order as a whole, so every item in this
			// batch carries the reason it was not bought. A definite failure
			// releases the claim so the item can be retried; a timeout keeps it
			// held, because Print.com may have accepted the order after all.
			foreach ( $items as $item ) {
				$this->set_order_item_error( $item['order_item'], $result );
				$this->set_item_purchase_state( $item['order_item'], $unknown ? 'unknown' : '' );
			}
			return $result;
		}

		$pdc_order = $result->order;
		foreach ( $pdc_order->items as $pdc_order_item ) {
			$order_item_id = (int) $pdc_order_item->customerReference; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$order_item    = $order->get_item( $order_item_id );

			if ( $order_item ) {
				$this->update_order_item( $order_item, $pdc_order );
			} else {
				Logger::log( 'unable to update order item after purchase', 'error', array( 'order_item_id' => $order_item_id ) );
			}
		}

		$note = sprintf(
			/* translators: %s: Print.com order number */
			__( 'Order purchased at Print.com with order number: %s.', 'pdc-pod' ),
			$pdc_order->orderNumber // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		);
		$order->add_order_note( $note );

		return $pdc_order;
	}

	/**
	 * Queues an automatic purchase when an order reaches the configured status.
	 *
	 * Hooked to woocommerce_order_status_changed. The purchase itself is never
	 * performed here: this hook runs inside the checkout request or a payment
	 * gateway webhook, and a gateway that times out will retry, which would
	 * purchase the same print twice.
	 *
	 * @since 1.5.0
	 *
	 * @param int       $order_id    The WooCommerce order ID.
	 * @param string    $status_from Status the order moved away from.
	 * @param string    $status_to   Status the order moved to.
	 * @param \WC_Order $order       The WooCommerce order.
	 * @return void
	 */
	public function maybe_schedule_auto_purchase( $order_id, $status_from, $status_to, $order = null ) {
		// Required by the WooCommerce hook signature but not used here.
		unset( $status_from, $order );

		$config = $this->get_orders_config();
		if ( empty( $config['auto_purchase'] ) || $config['trigger_status'] !== $status_to ) {
			return;
		}

		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			Logger::log(
				'cannot schedule automatic purchase, action scheduler is unavailable',
				'error',
				array( 'order_id' => $order_id )
			);
			return;
		}

		$args = array( 'order_id' => (int) $order_id );
		if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::AUTO_PURCHASE_HOOK, $args, PDC_POD_NAME ) ) {
			return;
		}

		as_schedule_single_action( time(), self::AUTO_PURCHASE_HOOK, $args, PDC_POD_NAME );

		Logger::log(
			'scheduled automatic purchase',
			'debug',
			array(
				'order_id' => $order_id,
				'status'   => $status_to,
			)
		);
	}

	/**
	 * Performs a queued automatic purchase.
	 *
	 * Hooked to the Action Scheduler hook queued by maybe_schedule_auto_purchase.
	 * A failure is never retried: the order is marked failed and the shop owner
	 * purchases the items manually from the Print.com order panel.
	 *
	 * @since 1.5.0
	 *
	 * @param int $order_id The WooCommerce order ID.
	 * @return void
	 */
	public function run_auto_purchase( $order_id ) {
		Logger::log(
			'running automatic purchase',
			'debug',
			array(
				'order_id' => $order_id,
			)
		);
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			Logger::log( 'automatic purchase: order not found', 'error', array( 'order_id' => $order_id ) );
			return;
		}

		$order_status = $order->get_status();
		if ( in_array( $order_status, array( 'cancelled', 'refunded', 'trash' ), true ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: current WooCommerce order status */
					__( 'Automatic purchase skipped, the order is %s.', 'pdc-pod' ),
					$order_status
				)
			);
			return;
		}

		$purchasable = $this->get_purchasable_items( $order );

		if ( ! empty( $purchasable['skipped'] ) ) {
			$order->add_order_note( $this->get_skipped_items_note( $order, $purchasable['skipped'] ) );
		}

		if ( empty( $purchasable['items'] ) ) {
			Logger::log( 'automatic purchase: nothing to purchase', 'debug', array( 'order_id' => $order_id ) );
			return;
		}

		$pdc_order = $this->purchase_prepared_items( $order, $purchasable['items'] );

		if ( is_wp_error( $pdc_order ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: error message returned by the Print.com API */
					__( 'Automatic purchase did not complete: %s', 'pdc-pod' ),
					$pdc_order->get_error_message()
				)
			);

			Logger::log(
				'automatic purchase did not complete',
				'error',
				array(
					'order_id' => $order_id,
					'error'    => $pdc_order->get_error_message(),
				)
			);
		}
	}

	/**
	 * Builds the order note listing the items an automatic purchase skipped.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Order $order   The WooCommerce order.
	 * @param array     $skipped Map of order item ID to the reason it was skipped.
	 * @return string The order note.
	 */
	private function get_skipped_items_note( $order, array $skipped ) {
		$lines = array();
		foreach ( $skipped as $skipped_item_id => $reason ) {
			$skipped_item = $order->get_item( $skipped_item_id );
			$lines[]      = sprintf( '%1$s (%2$s)', $skipped_item ? $skipped_item->get_name() : $skipped_item_id, $reason );
		}

		return sprintf(
			/* translators: %s: comma separated list of order items with the reason they were skipped */
			__( 'Automatic purchase skipped these items: %s. Purchase them manually once they are complete.', 'pdc-pod' ),
			implode( ', ', $lines )
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
		$body = json_decode( $request->get_body() );

		Logger::log(
			'webhook received',
			'debug',
			array(
				'body' => $body,
			)
		);

		$event_type = $body->event_type;
		$payload    = $body->payload;

		if ( 'ORDER_STATUS_CHANGED' === $event_type ) {
			if ( isset( $payload->status ) && 'ACCEPTEDBYSUPPLIER' === $payload->status ) {
				$this->on_webhook_in_production( $payload );
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
	 * @param object $payload   The body of the webhook.
	 * @return void
	 */
	private function on_webhook_in_production( $payload ) {
		if ( ! isset( $payload->order_item_number ) ) {
			Logger::log( 'expected order item number in webhook payload', 'error', array( 'payload' => $payload ) );
			return;
		}

		$pdc_order_item_number = $payload->order_item_number;

		$order_item_id = $this->get_order_item_id_by_order_item_number( $pdc_order_item_number );

		$order_item = new \WC_Order_Item_Product( $order_item_id );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_status' ), 'production' );
		$order_item->save();

		$order = $order_item->get_order();
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
	private function on_webhook_shipped( $order_item_number, $tracking_url ) {
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
	public function pdc_place_order_item( \WP_REST_Request $request ) {
		$order_item_id = absint( $request->get_param( 'id' ) );
		if ( empty( $order_item_id ) ) {
			return new \WP_Error(
				'pdc_missing_order_item',
				__( 'Order item ID is required.', 'pdc-pod' ),
				array( 'status' => 400 )
			);
		}

		$purchase_date = wc_get_order_item_meta( $order_item_id, $this->get_meta_key( 'purchase_date' ), true );
		if ( ! empty( $purchase_date ) ) {
			return new \WP_Error(
				'pdc_item_already_purchased',
				__( 'This item has already been purchased at Print.com.', 'pdc-pod' ),
				array( 'status' => 409 )
			);
		}

		$purchase_state = $this->get_item_purchase_state( $order_item_id );
		if ( '' !== $purchase_state && ! $request->get_param( 'force' ) ) {
			return new \WP_Error(
				'pdc_item_purchase_held',
				'in_progress' === $purchase_state
					? __( 'A purchase for this item is already in progress.', 'pdc-pod' )
					: __( 'The last purchase for this item timed out. Check Print.com before purchasing again.', 'pdc-pod' ),
				array(
					'status'         => 409,
					'purchase_state' => $purchase_state,
				)
			);
		}

		$order_item = new \WC_Order_Item_Product( $order_item_id );
		$order_id   = wc_get_order_id_by_order_item_id( $order_item_id );
		$order      = wc_get_order( $order_id );

		$this->set_item_purchase_state( $order_item, 'in_progress' );

		$pdc_pod_preset_id = $this->get_preset_id_by_order_item_id( $order_item_id );
		$pdc_pod_pdf_url   = $this->get_pdf_url_by_order_item_id( $order_item_id );

		$pdc_product_config = get_option( PDC_POD_NAME . '-product' );
		$item               = array(
			'order_item'        => $order_item,
			'pdc_pod_preset_id' => $pdc_pod_preset_id,
			'pdc_pod_pdf_url'   => $pdc_pod_pdf_url,
		);

		$result = $this->pdc_client->purchase_order_items( $order, array( $item ), $pdc_product_config );
		if ( is_wp_error( $result ) ) {
			$this->set_order_item_error( $order_item, $result );
			$this->set_item_purchase_state( $order_item, self::is_unknown_outcome( $result ) ? 'unknown' : '' );

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
		$pdc_order = $result->order;
		$this->update_order_item( $order_item, $pdc_order );

		$note = sprintf(
			// translators: placeholder is the order number.
			__( 'Item purchased at Print.com with order number: %s.', 'pdc-pod' ),
			$pdc_order->orderNumber // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		);
		$order->add_order_note( $note );

		return rest_ensure_response(
			array(
				'order' => $pdc_order,
			)
		);
	}

	/**
	 * Updates WooCommerce order item metadata after a successful Print.com purchase.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order_Item $order_item The WooCommerce order item.
	 * @param object         $pdc_order  The Print.com order response object.
	 * @return void
	 */
	private function update_order_item( $order_item, $pdc_order ) {
		// The item is bought, so whatever went wrong before no longer applies.
		$order_item->delete_meta_data( $this->get_meta_key( 'last_error' ) );
		$order_item->delete_meta_data( $this->get_meta_key( 'purchase_state' ) );
		$order_item->update_meta_data( $this->get_meta_key( 'order' ), $pdc_order );
		$order_item->update_meta_data( $this->get_meta_key( 'purchase_date' ), gmdate( 'c' ) );
		$order_number = $pdc_order->orderNumber; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$grand_total  = $pdc_order->grandTotal; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$order_item->update_meta_data( $this->get_meta_key( 'order_number' ), $order_number );
		$order_item->update_meta_data( $this->get_meta_key( 'grand_total' ), $grand_total );
		$order_item->update_meta_data( $this->get_meta_key( 'order_status' ), $pdc_order->status );

		$order_item_id  = (string) $order_item->get_id();
		$pdc_order_item = null;
		foreach ( $pdc_order->items as $item ) {
			$item_reference = isset( $item->customerReference ) ? $item->customerReference : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( $item_reference === $order_item_id ) {
				$pdc_order_item = $item;
				break;
			}
		}

		if ( null === $pdc_order_item ) {
			Logger::log(
				'unable to update order item',
				'error',
				array(
					'order_item_id' => $order_item_id,
				)
			);
			return;
		}

		$pdc_order_item_shipment = $pdc_order_item->shipments[0];

		$order_item_number = $pdc_order_item->orderItemNumber; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$order_item_status = $pdc_order_item->status; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$order_item_total  = $pdc_order_item->grandTotal; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_number' ), $order_item_number );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_status' ), $order_item_status );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_grand_total' ), $order_item_total );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item' ), $pdc_order_item );
		$order_item->update_meta_data( $this->get_meta_key( 'order_item_shipment' ), $pdc_order_item_shipment );
		$order_item->save();
	}

	/**
	 * Stores the error Print.com last returned for an order item.
	 *
	 * Kept on the item itself so the shop owner can see why a purchase did not
	 * happen, whether it was attempted automatically or by hand.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Order_Item $order_item The WooCommerce order item.
	 * @param \WP_Error      $error      The error returned by the API client.
	 * @return void
	 */
	private function set_order_item_error( $order_item, $error ) {
		$message = wp_strip_all_tags( (string) $error->get_error_message() );

		$order_item->update_meta_data(
			$this->get_meta_key( 'last_error' ),
			array(
				'code'    => (string) $error->get_error_code(),
				'message' => mb_substr( $message, 0, 500 ),
				'date'    => gmdate( 'c' ),
			)
		);
		$order_item->save_meta_data();
	}

	/**
	 * Determines whether an error leaves the purchase outcome unknown.
	 *
	 * A timed out request may still have been carried out by Print.com, so it
	 * must never be presented as a failure: that is what invites a second, real
	 * purchase of the same print.
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_Error $error The error returned by the API client.
	 * @return bool True when we cannot know whether the order was placed.
	 */
	private static function is_unknown_outcome( $error ) {
		return in_array(
			$error->get_error_code(),
			array( 'pdc_request_timeout', 'pdc_purchase_timeout' ),
			true
		);
	}

	/**
	 * Records whether a purchase is in flight for an order item.
	 *
	 * This is the guard that stops the same print being bought twice. It is
	 * written before the API call, because the call itself can take over a
	 * minute and 'purchase_date' is only written once it succeeds.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Order_Item $order_item The WooCommerce order item.
	 * @param string         $state      'in_progress', 'unknown', or '' to release.
	 * @return void
	 */
	private function set_item_purchase_state( $order_item, $state ) {
		if ( '' === $state ) {
			$order_item->delete_meta_data( $this->get_meta_key( 'purchase_state' ) );
		} else {
			$order_item->update_meta_data( $this->get_meta_key( 'purchase_state' ), $state );
		}
		$order_item->save_meta_data();
	}

	/**
	 * Reads the purchase state of an order item.
	 *
	 * @since 1.5.0
	 *
	 * @param int $order_item_id The WooCommerce order item ID.
	 * @return string 'in_progress', 'unknown', or '' when nothing is in flight.
	 */
	public function get_item_purchase_state( $order_item_id ) {
		$state = wc_get_order_item_meta( $order_item_id, $this->get_meta_key( 'purchase_state' ), true );

		return in_array( $state, array( 'in_progress', 'unknown' ), true ) ? $state : '';
	}

	/**
	 * Reads the error Print.com last returned for an order item.
	 *
	 * @since 1.5.0
	 *
	 * @param int $order_item_id The WooCommerce order item ID.
	 * @return array|null Error with code, message and date, or null when there is none.
	 */
	public function get_order_item_error( $order_item_id ) {
		$error = wc_get_order_item_meta( $order_item_id, $this->get_meta_key( 'last_error' ), true );

		if ( ! is_array( $error ) || empty( $error['message'] ) ) {
			return null;
		}

		return $error;
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

		$pdc_pod_parent_sku     = get_post_meta( $pdc_pod_parent_id, $pdc_pod_meta_key_sku, true );
		$pdc_pod_variantion_sku = get_post_meta( $pdc_pod_variation_id, $pdc_pod_meta_key_sku, true );
		$pdc_pod_sku            = ! empty( $pdc_pod_variantion_sku ) ? $pdc_pod_variantion_sku : $pdc_pod_parent_sku;
		$pdc_pod_preset_id      = get_post_meta( $pdc_pod_variation_id, $pdc_pod_meta_key_preset_id, true );

		$pdc_pod_products = $this->pdc_client->search_products();

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
			'pdf_url'     => $this->get_meta_key( 'pdf_url' ),
			'product_sku' => $this->get_meta_key( 'product_sku' ),
			'preset_id'   => $this->get_meta_key( 'preset_id' ),
		);

		foreach ( $fields as $meta_key ) {
			if ( isset( $_POST[ $meta_key ] ) && isset( $_POST[ $meta_key ][ $i ] ) ) {
				if ( is_array( $_POST[ $meta_key ][ $i ] ) ) {
					$val = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $meta_key ][ $i ] ) );
				} else {
					$val = sanitize_text_field( wp_unslash( $_POST[ $meta_key ][ $i ] ) );
				}

				update_post_meta( $variation_id, $meta_key, $val );
			}
		}
	}

	/**
	 * REST callback to trigger a download of the plugin log file.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function download_logs() {
		$logger = Logger::get_instance();
		$logger->download_log();
	}
}
