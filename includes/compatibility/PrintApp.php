<?php
/**
 * PrintApp compatibility layer
 *
 * Provides integration and compatibility with the Print.app plugin.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/includes/compatibility
 * @since 1.2.0
 */

namespace PdcPod\Includes\Compatibility;

/**
 * Compatibility class for Print.app.
 *
 * @link       https://print.com
 * @since      1.2.0
 *
 * @package    Pdc_Pod
 * @subpackage Pdc_Pod/includes/compatibility
 */
class PrintApp {

	/**
	 * The base URL for Print.app PDF generation.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $print_app_pdf_base_url = 'https://pdf.print.app/';

	/**
	 * The meta key used by Print.app to store customization data.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $print_app_meta_key = 'print_app_customization';

	/**
	 * Initialize the class and register hooks.
	 *
	 * @since    1.2.0
	 */
	public function __construct() {}

	/**
	 * Register hooks for Print.app compatibility.
	 *
	 * @since    1.2.0
	 * @return   void
	 */
	public function init() {
		add_filter( 'pdc_pod_order_item_pdf_url', array( $this, 'get_print_app_pdf_url' ), 10, 2 );
	}

	/**
	 * Retrieves the PDF URL from Print.app if available.
	 *
	 * @since 1.2.0
	 * @param string|bool $pdf_url      The current PDF URL.
	 * @param int         $order_item_id The WooCommerce order item ID.
	 * @return string|bool The Print.app PDF URL if found, otherwise the original URL.
	 */
	public function get_print_app_pdf_url( $pdf_url, $order_item_id ) {
		$print_app_data = wc_get_order_item_meta( $order_item_id, $this->print_app_meta_key, true );
		if ( empty( $print_app_data ) ) {
			return $pdf_url;
		}

		if ( ! isset( $print_app_data['projectId'] ) ) {
			return $pdf_url;
		}

		return $this->print_app_pdf_base_url . $print_app_data['projectId'];
	}
}
