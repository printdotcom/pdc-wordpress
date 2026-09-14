<?php
/**
 * WooCommerce class stubs for unit tests.
 *
 * Only the surface the plugin actually touches is stubbed. Classes are guarded
 * so a real WooCommerce, when present, always wins.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/tests
 * @since 1.5.0
 */

if ( ! class_exists( 'WC_Order_Item_Product' ) ) {
	/**
	 * Minimal stand-in for the WooCommerce order item.
	 */
	class WC_Order_Item_Product {

		/**
		 * Order item ID.
		 *
		 * @var int
		 */
		private $id;

		/**
		 * Order item name.
		 *
		 * @var string
		 */
		private $name;

		/**
		 * In-memory meta store.
		 *
		 * @var array
		 */
		private $meta = array();

		/**
		 * Constructor.
		 *
		 * @param int    $id   Order item ID.
		 * @param string $name Order item name.
		 */
		public function __construct( $id = 0, $name = '' ) {
			$this->id   = (int) $id;
			$this->name = '' !== $name ? $name : 'Item ' . $id;
		}

		/**
		 * Order item ID.
		 *
		 * @return int
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Order item name.
		 *
		 * @return string
		 */
		public function get_name() {
			return $this->name;
		}

		/**
		 * Stores a meta value.
		 *
		 * @param string $key   Meta key.
		 * @param mixed  $value Meta value.
		 * @return void
		 */
		public function update_meta_data( $key, $value ) {
			$this->meta[ $key ] = $value;
		}

		/**
		 * Reads a meta value.
		 *
		 * @param string $key Meta key.
		 * @return mixed Stored value, or an empty string when absent.
		 */
		public function get_meta( $key ) {
			return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
		}

		/**
		 * Removes a meta value.
		 *
		 * @param string $key Meta key.
		 * @return void
		 */
		public function delete_meta_data( $key ) {
			unset( $this->meta[ $key ] );
		}

		/**
		 * Persists meta. A no-op in tests.
		 *
		 * @return void
		 */
		public function save_meta_data() {
		}

		/**
		 * Persists the item. A no-op in tests.
		 *
		 * @return void
		 */
		public function save() {
		}

		/**
		 * Ordered quantity.
		 *
		 * @return int
		 */
		public function get_quantity() {
			return 1;
		}

		/**
		 * Variation ID. Always 0 so preset lookups do not fall back to post meta.
		 *
		 * @return int
		 */
		public function get_variation_id() {
			return 0;
		}

		/**
		 * Product ID. Always 0 so preset lookups do not fall back to post meta.
		 *
		 * @return int
		 */
		public function get_product_id() {
			return 0;
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal stand-in for the WordPress error object.
	 */
	class WP_Error {

		/**
		 * Error code.
		 *
		 * @var string|int
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Error data.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Constructor.
		 *
		 * @param string|int $code    Error code.
		 * @param string     $message Error message.
		 * @param mixed      $data    Error data.
		 */
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * Error code.
		 *
		 * @return string|int
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * Error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}

		/**
		 * Error data.
		 *
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}
