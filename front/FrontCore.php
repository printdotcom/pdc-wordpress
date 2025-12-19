<?php
/**
 * Front core
 *
 * Provides public-facing hooks and behavior for the plugin.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/front
 * @since 1.0.0
 */

namespace PdcPod\Front;

use PdcPod\Includes\Core;

/**
 * The user-facing functionality of the plugin.
 *
 * @link       https://print.com
 * @since      1.0.0
 *
 * @package    Pdc_Pod
 * @subpackage Pdc_Pod/front
 */

/**
 * The user-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the user-facing stylesheet and JavaScript.
 *
 * @package    Pdc_Pod
 * @subpackage Pdc_Pod/front
 * @author     Tijmen <tijmen@print.com>
 */
class FrontCore {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Because we are reading request values in the cart item data filter,
	 * we need nonce verification.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_cart_item_data_nonce() {
		wp_nonce_field( 'pdc_addtocart', 'pdc-nonce' );
	}


	/**
	 * Adds additional values to the cart item.
	 *
	 * Filter cart item data for add to cart requests. Hooks into woocommerce_add_cart_item_data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $cart_item_data The existing cart item data.
	 * @param int   $product_id     The product ID being added to the cart.
	 * @return array Modified cart item data.
	 */
	public function capture_cart_item_data( $cart_item_data, $product_id ) {

		// $product_id is required by the WooCommerce hook signature but is not used here.
		unset( $product_id );

		$cart_item_data[ Core::get_meta_key( 'pdf_url' ) ] = $this->capture_cart_item_pdf_url();
		return $cart_item_data;
	}

	/**
	 * Captures the PDF URL for a cart item from the current request context.
	 *
	 * This checks for a direct PDF URL in the request, or falls back to a PitchPrint
	 * project reference if present.
	 *
	 * @since 1.0.0
	 *
	 * @return string The detected PDF URL or empty string when none is available.
	 */
	private function capture_cart_item_pdf_url() {
		if ( ! isset( $_POST['pdc-nonce'] ) ) {
			return '';
		}

		$nonce_verification = wp_verify_nonce( wp_unslash( sanitize_key( $_POST['pdc-nonce'] ) ), 'pdc_addtocart' );
		if ( ! $nonce_verification ) {
			return '';
		}

		$pdc_pdf_url_metakey = Core::get_meta_key( 'pdf_url' );
		if ( isset( $_POST[ $pdc_pdf_url_metakey ] ) && ! empty( $_POST[ $pdc_pdf_url_metakey ] ) ) {
			// Request contains a pdf_url, so we use that.
			return esc_url_raw( wp_unslash( $_POST[ $pdc_pdf_url_metakey ] ) );
		}

		if ( isset( $_POST['_w2p_set_option'] ) && ! empty( $_POST['_w2p_set_option'] ) ) {
			// PitchPrint has a PDF URL, so we use that.
			$raw_option       = wp_unslash( sanitize_key( $_POST['_w2p_set_option'] ) );
			$pitch_print_data = json_decode( urldecode( $raw_option ) );
			if ( $pitch_print_data && isset( $pitch_print_data->projectId ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				return esc_url_raw( 'https://pdf.pitchprint.com/' . $pitch_print_data->projectId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}

		return '';
	}

	/**
	 * Saves the PDC values on the order item.
	 *
	 * Hooks into woocommerce_checkout_create_order_line_item.
	 *
	 * @since 1.0.0
	 *
	 * @param \\WC_Order_Item_Product $order_item    The WooCommerce order item object.
	 * @param string                  $cart_item_key The cart item key.
	 * @param array                   $values        The cart item values.
	 * @return void
	 */
	public function save_pdc_values_order_meta( \WC_Order_Item_Product $order_item, $cart_item_key, $values ) {
		$product_id   = $values['product_id'];
		$variation_id = $order_item->get_variation_id();

		$pdc_pdf_url   = isset( $values[ Core::get_meta_key( 'pdf_url' ) ] ) ? $values[ Core::get_meta_key( 'pdf_url' ) ] : null;
		$pdc_preset_id = isset( $values[ Core::get_meta_key( 'preset_id' ) ] ) ? $values[ Core::get_meta_key( 'preset_id' ) ] : null;

		if ( empty( $pdc_pdf_url ) ) {
			// There is no preconfigured PDF on the cart item.
			if ( $variation_id ) {
				$pdc_pdf_url = get_post_meta( $variation_id, Core::get_meta_key( 'pdf_url' ), true );
			}

			// If the variation did not set the PDF URL, get it from the product.
			if ( empty( $pdc_pdf_url ) ) {
				$pdc_pdf_url = get_post_meta( $product_id, Core::get_meta_key( 'pdf_url' ), true );
			}
		}

		if ( empty( $pdc_preset_id ) ) {
			// There is no preconfigured preset on the cart item.
			if ( $variation_id ) {
				$pdc_preset_id = get_post_meta( $variation_id, Core::get_meta_key( 'preset_id' ), true );
			}

			// Variation did not set the preset ID, so get it from the product.
			if ( empty( $pdc_preset_id ) ) {
				$pdc_preset_id = get_post_meta( $product_id, Core::get_meta_key( 'preset_id' ), true );
			}
		}

		$pitchprint_data = isset( $values['_pda_w2p_set_option'] ) ? $values['_pda_w2p_set_option'] : '';
		if ( ! empty( $pitchprint_data ) ) {
			$decoded_data = json_decode( urldecode( $pitchprint_data ) );

			if ( json_last_error() === JSON_ERROR_NONE && isset( $decoded_data->projectId ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				// Valid PitchPrint project found; override pdf url.
				$pdc_pdf_url = 'https://pdf.print.app/' . $decoded_data->projectId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}

		$order_item->add_meta_data( Core::get_meta_key( 'pdf_url' ), $pdc_pdf_url );
		$order_item->add_meta_data( Core::get_meta_key( 'preset_id' ), $pdc_preset_id );
	}
}
