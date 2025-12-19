<?php
/**
 * Print.com API client (admin)
 *
 * Provides a client for communicating with the Print.com API from the admin area.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/admin/PrintDotCom
 * @since 1.0.0
 */

namespace PdcPod\Admin\PrintDotCom;

use PdcPod\Includes\Core;

/**
 * Client to connect to the Print.com API
 *
 * @link       https://print.com
 * @since      1.0.0
 *
 * @package    Pdc_Pod
 * @subpackage Pdc_Pod/admin
 */
class APIClient {


	/**
	 * Base URL of the Print.com API.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $pdc_pod_api_base_url;

	/**
	 * API key for the Print.com API.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $pdc_pod_api_key;

	/**
	 * Initializes the API client.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$env = get_option( PDC_POD_NAME . '-env' );

		// Allow environment variable override for testing.
		if ( getenv( 'PDC_POD_API_BASE_URL' ) ) {
			$this->pdc_pod_api_base_url = getenv( 'PDC_POD_API_BASE_URL' );
		} else {
			$this->pdc_pod_api_base_url = ( 'prod' === $env ) ? 'https://api.print.com' : 'https://api.stg.print.com';
		}

		// Allow environment variable override for testing.
		if ( getenv( 'PDC_POD_API_KEY' ) ) {
			$this->pdc_pod_api_key = getenv( 'PDC_POD_API_KEY' );
		} else {
			$api_key               = get_option( PDC_POD_NAME . '-api_key' );
			$this->pdc_pod_api_key = $api_key;
		}
	}

	/**
	 * Retrieves the API base URL based on the current environment.
	 *
	 * @since 1.0.0
	 * @return string API base URL.
	 */
	public function get_api_base_url() {
		return $this->pdc_pod_api_base_url;
	}

	/**
	 * Returns the API key used for authenticated requests.
	 *
	 * @since 1.0.0
	 * @return string API key.
	 */
	private function get_token() {
		return $this->pdc_pod_api_key;
	}

	/**
	 * Performs an authenticated request to the Print.com API.
	 * A more convenient wrapper around performHttpRequest.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $method  The HTTP method to use.
	 * @param string     $path    The path to request.
	 * @param array|null $data    Optional data to send in the request.
	 * @param array      $headers Optional headers to send with the request.
	 * @return string|WP_Error The unparsed response from the API.
	 */
	private function perform_authenticated_request( $method, $path, $data = null, $headers = array() ) {
		$url   = $this->pdc_pod_api_base_url . $path;
		$token = $this->get_token();
		return $this->perform_http_request( $method, $url, $data, $token, $headers );
	}

	/**
	 * Performs an HTTP request to the Print.com API.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $method  The HTTP method to use.
	 * @param string     $url     The URL to request.
	 * @param array|null $data    The data to send in the request.
	 * @param string|null $token  The access token to use.
	 * @param array      $headers Additional headers to send with the request.
	 * @return string|WP_Error The unparsed response from the API.
	 */
	/**
	 * Performs an HTTP request to the Print.com API using WordPress HTTP API.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $method  The HTTP method to use.
	 * @param string      $url     The URL to request.
	 * @param array|null  $data    The data to send in the request.
	 * @param string|null $token   The access token to use.
	 * @param array       $headers Additional headers to send with the request.
	 * @return string|WP_Error The unparsed response from the API.
	 */
	private function perform_http_request( $method, $url, $data = null, $token = null, $headers = array() ) {
		$method = strtoupper( $method );

		$args = array(
			'timeout' => 30,
			'headers' => array(
				'Accept' => 'application/json',
			),
		);

		if ( null !== $token ) {
			$args['headers']['Authorization'] = 'PrintApiKey ' . $token;
		}

		if ( ! empty( $headers ) ) {
			// Merge string header formats like 'key: value' or associative arrays.
			foreach ( $headers as $h ) {
				if ( is_string( $h ) && false !== strpos( $h, ':' ) ) {
					list($k, $v) = array_map( 'trim', explode( ':', $h, 2 ) );
					if ( $k ) {
						$args['headers'][ $k ] = $v;
					}
				} elseif ( is_array( $h ) ) {
					$args['headers'] = array_merge( $args['headers'], $h );
				}
			}
		}

		if ( 'GET' === $method && ! empty( $data ) && is_array( $data ) ) {
			$query = http_build_query( $data );
			$url   = $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $query;
		} elseif ( ! empty( $data ) ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = function_exists( 'wp_json_encode' ) ? wp_json_encode( $data ) : json_encode( $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		}

		$response = wp_remote_request( $url, array_merge( $args, array( 'method' => $method ) ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( $code, $body );
		}

		return $body;
	}


	/**
	 * Retrieves a list of Print.com Presets
	 *
	 * @param string $sku The SKU of the product to retrieve the Presets for.
	 * @return Pdc_Preset[] | WP_Error A list of Print.com Presets
	 *
	 * @phpcsSuppress WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 */
	public function get_presets( $sku ) {
		$result = $this->perform_authenticated_request( 'GET', '/customerpresets' );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$decoded_result = json_decode( $result );

		$presets = array_map(
			function ( $preset ) {
				return new Preset( $preset->sku, $preset->title->en, $preset->id );
			},
			$decoded_result->items
		);

		$filtered_by_sku = array_filter(
			$presets,
			function ( $preset ) use ( $sku ) {
				return $preset->sku === $sku;
			}
		);
		return array_values( $filtered_by_sku );
	}

	/**
	 * Does a products request to the Print.com
	 * to verify if the environemnt and API key is working.
	 *
	 * @since 1.0.0
	 *
	 * @return bool returns true when authenticated
	 */
	public function is_authenticated() {
		$result = $this->perform_authenticated_request( 'GET', '/products' );
		if ( is_wp_error( $result ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Searches products from the Print.com API.
	 *
	 * @since 1.0.0
	 *
	 * @return Product[]|WP_Error A list of products or WP_Error on failure.
	 */
	public function search_products() {
		$result = null;
		$cached = get_transient( PDC_POD_NAME . '-products' );
		if ( $cached ) {
			$result = json_decode( $cached );
		} else {
			$response = $this->perform_authenticated_request( 'GET', '/products', null );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			if ( empty( $response ) ) {
				return new \WP_Error( 'no result', 'No products found' );
			}
			set_transient( PDC_POD_NAME . '-products', $response, 60 * 60 * 24 ); // 1 day
			$result = json_decode( $response );
		}

		$result = array_values(
			array_filter(
				$result,
				fn( $item ) => ! empty( $item->sku ) && ! empty( $item->titlePlural ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			)
		);

		usort(
			$result,
			fn( $a, $b ) => strcasecmp( $a->titlePlural ?? '', $b->titlePlural ?? '' ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		);

		$products = array_map(
			fn( $item ) => new Product( $item->sku, $item->titlePlural ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$result
		);

		return $products;
	}

	/**
	 * Purchases an order item through the Print.com API.
	 *
	 * This function creates a print order by retrieving preset configuration,
	 * combining it with WooCommerce order data, and submitting it to Print.com.
	 * It handles preset retrieval, file URLs, shipping addresses, and quantity
	 * management based on the provided arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $order_item_id The WooCommerce order item ID to purchase.
	 * @param array $args {
	 *     Optional. Arguments for customizing the purchase behavior.
	 *
	 *     @type bool $use_preset_copies Whether to use preset-defined copy count.
	 *                                   If false, uses order item quantity. Default true.
	 * }
	 *
	 * @return object|WP_Error Returns the Print.com order response object on success,
	 *                         or WP_Error on failure with error details.
	 *
	 * @phpcsSuppress WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 */
	public function purchase_order_item( $order_item_id, $args ) {
		$order_item       = new \WC_Order_Item_Product( $order_item_id );
		$order_id         = wc_get_order_id_by_order_item_id( $order_item_id );
		$order            = wc_get_order( $order_id );
		$shipping_address = $order->get_address( 'shipping' );

		if ( empty( $shipping_address ) ) {
			return new \WP_Error( 400, 'No shipping address found', array( 'order' => $order ) );
		}

		$pdc_preset_id = wc_get_order_item_meta( $order_item_id, Core::get_meta_key( 'preset_id' ), true );
		$pdc_pdf_url   = wc_get_order_item_meta( $order_item_id, Core::get_meta_key( 'pdf_url' ), true );

		$result = $this->perform_authenticated_request( 'GET', '/customerpresets/' . rawurlencode( $pdc_preset_id ), null );
		if ( is_wp_error( $result ) ) {
			if ( $result->get_error_message() === '[404] Preset not found.' ) {
				return new \WP_Error(
					404,
					'Preset does not exist.',
					array(
						'preset_id'   => $pdc_preset_id,
						'environment' => $this->pdc_pod_api_base_url,
					)
				);
			}
			return new \WP_Error( 500, $result->get_error_message() );
		}
		if ( empty( $result ) ) {
			return new \WP_Error(
				404,
				'Preset does not exist.',
				array(
					'preset_id'   => $pdc_preset_id,
					'environment' => $this->pdc_pod_api_base_url,
				)
			);
		}

		$preset = json_decode( $result );

		$item_options = $preset->configuration;
		if ( empty( $args['use_preset_copies'] ) ) {
			$item_options->copies = $order_item->get_quantity();
		}

		// Remove unwanted options.
		unset( $item_options->_accessories );
		unset( $item_options->variants );
		unset( $item_options->deliveryPromise ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$restapi_url   = esc_url_raw( rest_url() );
		$order_request = array(
			'customerReference' => $order->get_order_number() . '-' . $order_item_id,
			'webhookUrl'        => $restapi_url . 'pdc/v1/orders/webhook?order_item_id=' . $order_item_id . '&order_id=' . $order_id,
			'items'             => array(
				array(
					'sku'           => $preset->sku,
					'fileUrl'       => $pdc_pdf_url,
					'options'       => $item_options,
					'approveDesign' => true,
					'shipments'     => array(
						array(
							'address' => array(
								'city'        => $shipping_address['city'],
								'country'     => $shipping_address['country'],
								'firstName'   => $shipping_address['first_name'],
								'lastName'    => $shipping_address['last_name'],
								'companyName' => $shipping_address['company'],
								'postcode'    => $shipping_address['postcode'],
								'fullstreet'  => $shipping_address['address_1'],
								'telephone'   => $shipping_address['phone'],
							),
							'copies'  => $item_options->copies,
						),
					),
				),
			),
		);

		$order_body = apply_filters( PDC_POD_NAME . '_before_purchase_order_item', $order_request, $order_item_id );
		$result     = $this->perform_authenticated_request(
			'POST',
			'/orders',
			$order_body,
			array(
				'pdc-request-source' => 'pdc-woocommerce',
			)
		);

		if ( is_wp_error( $result ) ) {
			return new \WP_Error( 500, 'failed placing the order', array( 'result' => $result ) );
		}

		if ( empty( $result ) ) {
			return new \WP_Error( 500, 'unable to place order', array( 'order' => $order_request ) );
		}

		return json_decode( $result );
	}
}
