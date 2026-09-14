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

use PdcPod\Includes\Logger;

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
	 * Timeout in seconds for ordinary API requests.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	const REQUEST_TIMEOUT = 30;

	/**
	 * Timeout in seconds for placing an order.
	 *
	 * Placing an order can take well over a minute, and aborting early is worse
	 * than waiting: Print.com may have accepted the order while we gave up on it.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	const PURCHASE_TIMEOUT = 120;

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
		if ( getenv( 'PDC_POD_API_BASE_URL' ) ) {
			$this->pdc_pod_api_base_url = getenv( 'PDC_POD_API_BASE_URL' );
		} else {
			$env                        = get_option( PDC_POD_NAME . '-env' );
			$this->pdc_pod_api_base_url = ( 'prod' === $env ) ? 'https://api.print.com' : 'https://api.stg.print.com';
		}

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
	 * @param int        $timeout Request timeout in seconds.
	 * @return string|WP_Error The unparsed response from the API.
	 */
	private function perform_authenticated_request( $method, $path, $data = null, $headers = array(), $timeout = self::REQUEST_TIMEOUT ) {
		$url   = $this->pdc_pod_api_base_url . $path;
		$token = $this->get_token();
		return $this->perform_http_request( $method, $url, $data, $token, $headers, $timeout );
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
	/**
	 * Determines whether a transport error was caused by a timeout.
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_Error $error The error returned by the HTTP transport.
	 * @return bool True when the request timed out.
	 */
	private static function is_timeout( $error ) {
		if ( 'http_request_failed' !== $error->get_error_code() ) {
			return false;
		}

		return false !== stripos( $error->get_error_message(), 'timed out' )
			|| false !== stripos( $error->get_error_message(), 'timeout' );
	}

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
	 * @param int         $timeout Request timeout in seconds.
	 * @return string|WP_Error The unparsed response from the API.
	 */
	private function perform_http_request( $method, $url, $data = null, $token = null, $headers = array(), $timeout = self::REQUEST_TIMEOUT ) {
		$method = strtoupper( $method );

		$args = array(
			'timeout' => $timeout,
			'headers' => array(
				'Accept' => 'application/json',
			),
		);

		if ( null !== $token ) {
			$args['headers']['Authorization'] = 'PrintApiKey ' . $token;
		}

		if ( ! empty( $headers ) ) {
			$args['headers'] = array_merge( $args['headers'], $headers );
		}

		if ( 'GET' === $method && ! empty( $data ) && is_array( $data ) ) {
			$query = http_build_query( $data );
			$url   = $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $query;
		} elseif ( ! empty( $data ) ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = function_exists( 'wp_json_encode' ) ? wp_json_encode( $data ) : json_encode( $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		}

		Logger::log(
			'Print.com API request',
			'debug',
			array(
				'method' => $method,
				'url'    => $url,
				'body'   => isset( $args['body'] ) ? $args['body'] : null,
			)
		);

		$response = wp_remote_request( $url, array_merge( $args, array( 'method' => $method ) ) );
		if ( is_wp_error( $response ) ) {
			if ( self::is_timeout( $response ) ) {
				// A timeout is not a failure: the request may well have been
				// carried out. Callers must be able to tell the two apart.
				return new \WP_Error(
					'pdc_request_timeout',
					$response->get_error_message(),
					array(
						'url'     => $url,
						'timeout' => $timeout,
					)
				);
			}
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			Logger::log(
				'Print.com API request failed.',
				'error',
				array(
					'method' => $method,
					'url'    => $url,
					'status' => $code,
					'body'   => $body,
				)
			);
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
			Logger::log(
				'failed to retrieve customer presets.',
				'error',
				array(
					'sku' => $sku,
				)
			);
			return $result;
		}
		$decoded_result = json_decode( $result );

		$presets = array_map(
			function ( $preset ) {
				return new Preset( $preset );
			},
			$decoded_result->items
		);

		$filtered_by_sku = array_filter(
			$presets,
			function ( $preset ) use ( $sku ) {
				return $preset->sku === $sku;
			}
		);

		usort(
			$filtered_by_sku,
			fn( $a, $b ) => strnatcasecmp( $a->title, $b->title )
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
			Logger::log(
				'failed to retrieve products.',
				'error',
				array()
			);
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
	 * Retrieves a specific preset by its ID from the Print.com API.
	 *
	 * Fetches the preset details, strips internal API-specific fields from the
	 * configuration, and resolves any attached accessories into a structured list.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pdc_pod_preset_id The unique identifier for the Print.com preset.
	 * @return Preset|\WP_Error The preset or WP_Error on failure.
	 */
	private function get_preset_by_id( $pdc_pod_preset_id ) {
		$result = $this->perform_authenticated_request( 'GET', '/customerpresets/' . rawurlencode( $pdc_pod_preset_id ), null );
		if ( is_wp_error( $result ) ) {
			Logger::log(
				'failed to get preset.',
				'error',
				array(
					'preset_id'   => $pdc_pod_preset_id,
					'environment' => $this->pdc_pod_api_base_url,
				)
			);
			if ( $result->get_error_message() === '[404] Preset not found.' ) {
				return new \WP_Error(
					404,
					'Preset does not exist.',
					array(
						'preset_id'   => $pdc_pod_preset_id,
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
					'preset_id'   => $pdc_pod_preset_id,
					'environment' => $this->pdc_pod_api_base_url,
				)
			);
		}
		$preset = json_decode( $result );

		$pdc_preset = new Preset( $preset );

		$accessories = array();
		foreach ( $pdc_preset->accessory_ids as $accessory_id => $quantity ) {
			$retrieved_accessory = $this->get_accessory_by_id( $pdc_preset->sku, $accessory_id, $quantity );
			if ( $retrieved_accessory ) {
				$accessories[] = $retrieved_accessory;
			}
		}

		$pdc_preset->set_accessories( $accessories );

		return $pdc_preset;
	}

	/**
	 * Retrieves an accessory by its ID for a specific product SKU.
	 *
	 * @since 1.4.0
	 *
	 * @param string $sku          The product SKU.
	 * @param string $accessory_id The accessory ID to find.
	 * @param int    $quantity     The quantity of the accessory.
	 * @return Accessory|null The accessory object, or null if not found.
	 */
	private function get_accessory_by_id( $sku, $accessory_id, $quantity ) {
		$sku_accessories = $this->get_product_accessories( $sku );
		if ( is_wp_error( $sku_accessories ) || ! is_array( $sku_accessories ) ) {
			return null;
		}

		$pdc_accessory = null;
		foreach ( $sku_accessories as $sku_accessory ) {
			if ( $sku_accessory->id === $accessory_id ) {
				$pdc_accessory = new Accessory( $accessory_id, $sku_accessory->sku, $sku_accessory->configuration, $quantity );
				break;
			}
		}

		if ( null === $pdc_accessory ) {
			Logger::log(
				'accessory not found in product accessories list.',
				'error',
				array(
					'sku'          => $sku,
					'accessory_id' => $accessory_id,
				)
			);
		}

		return $pdc_accessory;
	}

	/**
	 * Retrieves all available accessories for a product SKU.
	 *
	 * Results are cached as a transient for one hour to avoid redundant API
	 * calls when multiple accessories on the same preset are resolved.
	 *
	 * @since 1.4.0
	 * @since 1.4.1 Results are cached using a transient to prevent N+1 API calls.
	 *
	 * @param string $sku The product SKU.
	 * @return array|\WP_Error List of accessory objects on success, WP_Error on failure.
	 */
	private function get_product_accessories( $sku ) {
		$transient_key = PDC_POD_NAME . '-accessories-' . $sku;
		$cached        = get_transient( $transient_key );
		if ( $cached ) {
			return json_decode( $cached );
		}

		$result = $this->perform_authenticated_request( 'GET', '/accessories/' . rawurlencode( $sku ) );
		if ( is_wp_error( $result ) ) {
			Logger::log(
				'failed to get accessories for product.',
				'error',
				array(
					'sku'         => $sku,
					'environment' => $this->pdc_pod_api_base_url,
				)
			);
			return new \WP_Error( 500, $result->get_error_message() );
		}

		set_transient( $transient_key, $result, 60 * 60 ); // 1 hour
		$product_accessories = json_decode( $result );
		return $product_accessories;
	}

	/**
	 * Prepares a single order item for the Print.com API request.
	 *
	 * Fetches the preset configuration, merges it with the order item data,
	 * and formats the shipping address to match the Print.com API structure.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order              $order              The WooCommerce order.
	 * @param \WC_Order_Item_Product $order_item         The WooCommerce order item.
	 * @param string                 $pdc_pod_preset_id  The Print.com preset ID.
	 * @param string                 $pdc_pod_pdf_url    The PDF URL for the print item.
	 * @param array                  $shipping_address   The WooCommerce shipping address array.
	 * @param array                  $purchase_args      Configuration arguments (e.g., use_preset_copies).
	 * @return array|\WP_Error Prepared item array or WP_Error on failure.
	 */
	private function prepare_order_item( $order, $order_item, $pdc_pod_preset_id, $pdc_pod_pdf_url, $shipping_address, $purchase_args ) {
		$preset = $this->get_preset_by_id( $pdc_pod_preset_id );
		if ( is_wp_error( $preset ) ) {
			return $preset;
		}

		if ( empty( $purchase_args['use_preset_copies'] ) ) {
			$preset->set_copies( $order_item->get_quantity() );
		}

		$shipping_address_payload = array(
			'email'       => $order->get_billing_email(),
			'city'        => $shipping_address['city'],
			'country'     => $shipping_address['country'],
			'firstName'   => $shipping_address['first_name'],
			'lastName'    => $shipping_address['last_name'],
			'companyName' => $shipping_address['company'],
			'postcode'    => $shipping_address['postcode'],
			'fullstreet'  => $shipping_address['address_1'],
			'telephone'   => $shipping_address['phone'],
		);

		$order_item_shipment = array(
			array(
				'address' => $shipping_address_payload,
				'copies'  => $preset->configuration['copies'],
			),
		);

		$prepared_item = array(
			'sku'               => $preset->sku,
			'fileUrl'           => $pdc_pod_pdf_url,
			'options'           => $preset->configuration,
			'approveDesign'     => true,
			'customerReference' => $order_item->get_id(),
			'shipments'         => $order_item_shipment,
		);

		if ( ! empty( $preset->accessories ) ) {
			$prepared_item['accessories'] = array();
			foreach ( $preset->accessories as $accessory ) {
				$preset_accessory                    = array(
					'sku'         => $accessory->sku,
					'options'     => $accessory->configuration,
					'accessoryId' => $accessory->accessory_id,
					'shipments'   => array(
						array(
							'address' => $shipping_address_payload,
							'copies'  => $accessory->copies,
						),
					),
				);
				$preset_accessory['options']->copies = $accessory->copies;
				$prepared_item['accessories'][]      = $preset_accessory;
			}
		}

		return $prepared_item;
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
	 * @param \WC_Order $order            The WooCommerce order.
	 * @param array     $items            The items to purchase
	 *      \WC_Order_Item_Product $order_item         The WooCommerce order item.
	 *      string                 $pdc_pod_pdf_url The PDF URL for the print item.
	 *      string                 $pdc_pod_preset_id  The Print.com preset ID.
	 * @param array     $purchase_args {
	 *     Optional. Arguments for customizing the purchase behavior.
	 *
	 *     @type bool $use_preset_copies Whether to use preset-defined copy count.
	 *                                   If false, uses order item quantity. Default true.
	 * }
	 *
	 * @return object|\WP_Error Returns the Print.com order response object on success,
	 *                         or \WP_Error on failure with error details.
	 *
	 * @phpcsSuppress WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 */
	public function purchase_order_items( $order, $items, $purchase_args = array() ) {
		$shipping_address = $order->get_address( 'shipping' );

		if ( empty( $shipping_address ) ) {
			return new \WP_Error( 400, 'No shipping address found', array( 'order' => $order ) );
		}

		$order_id = $order->get_id();

		$webhook_url = add_query_arg(
			array(
				'order_id' => $order_id,
			),
			rest_url( 'pdc/v1/orders/webhook' )
		);

		$order_request_items = array();

		foreach ( $items as $item ) {
			$prepare_result = $this->prepare_order_item(
				$order,
				$item['order_item'],
				$item['pdc_pod_preset_id'],
				$item['pdc_pod_pdf_url'],
				$shipping_address,
				$purchase_args
			);

			if ( is_wp_error( $prepare_result ) ) {
				return $prepare_result;
			}

			$order_request_items[] = $prepare_result;
		}

		$order_request = array(
			'customerReference' => (string) $order_id,
			'webhookUrl'        => esc_url_raw( $webhook_url ),
			'items'             => $order_request_items,
		);

		$order_body = apply_filters( PDC_POD_NAME . '_before_purchase_order_item', $order_request );
		$result     = $this->perform_authenticated_request(
			'POST',
			'/orders',
			$order_body,
			array(
				'pdc-request-source' => 'pdc-woocommerce',
			),
			self::PURCHASE_TIMEOUT
		);

		if ( is_wp_error( $result ) ) {
			Logger::log(
				'failed to purchase order.',
				'error',
				array(
					'requestbody' => $order_body,
					'environment' => $this->pdc_pod_api_base_url,
				)
			);

			if ( 'pdc_request_timeout' === $result->get_error_code() ) {
				// We do not know whether the order was placed. Saying it failed
				// would invite a second, real purchase.
				return new \WP_Error(
					'pdc_purchase_timeout',
					'The purchase timed out. Print.com may have received this order, check before purchasing again.',
					array( 'result' => $result )
				);
			}

			return new \WP_Error( 500, 'failed placing the order', array( 'result' => $result ) );
		}

		if ( empty( $result ) ) {
			return new \WP_Error( 500, 'unable to place order', array( 'order' => $order_request ) );
		}

		return json_decode( $result );
	}
}
