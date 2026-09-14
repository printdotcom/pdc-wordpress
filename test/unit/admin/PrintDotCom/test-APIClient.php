<?php
/**
 * Test APIClient functionality
 *
 * Tests for the Print.com API client, specifically focusing on the header
 * merging functionality that was simplified in version 1.0.1.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/tests
 * @since 1.0.1
 */

namespace PdcPod\Tests;

use PdcPod\Admin\PrintDotCom\APIClient;
use PdcPod\Admin\PrintDotCom\Preset;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * APIClient test case.
 *
 * Tests the APIClient class, focusing on HTTP request handling and
 * header merging behavior.
 *
 * @since 1.0.1
 */
class Test_APIClient extends TestCase {

    public static function setUpBeforeClass() : void
    {
		if (!defined('PDC_POD_NAME')) {
			define('PDC_POD_NAME', 'pdc-pod');
		}
    }

	/**
	 * Tests that constructor sets base URL from PDC_POD_API_BASE_URL environment variable.
	 *
	 * @since 1.0.1
	 */
	public function test_constructor_sets_base_url_using_env() {
        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args'  => ['pdc-pod-api_key'],
            'return' => 'fake-api-key',
        ] );

		putenv( 'PDC_POD_API_BASE_URL=https://testapi.print.com' );

		$client = new APIClient();

		$this->assertEquals( 'https://testapi.print.com', $client->get_api_base_url() );

		putenv( 'PDC_POD_API_BASE_URL' );
	}

    /**
	 * Tests that constructor sets base URL to api.print.com when stored environment is prod
	 *
	 * @since 1.0.1
	 */
	public function test_constructor_sets_printcom_baseurl_when_env_option_is_prod() {
        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args'  => ['pdc-pod-env'],
            'return' => 'prod',
        ] );

        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args'  => ['pdc-pod-api_key'],
            'return' => 'fake-api-key',
        ] );

		$client = new APIClient();

		$this->assertEquals( 'https://api.print.com', $client->get_api_base_url() );
	}

    /**
	 * Tests that constructor sets base URL to api.print.com when stored environment is prod
	 *
	 * @since 1.0.1
	 */
	public function test_constructor_sets_printcom_baseurl_when_env_option_is_not_set() {
        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args'  => ['pdc-pod-env'],
        ] );

        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args'  => ['pdc-pod-api_key'],
            'return' => 'fake-api-key',
        ] );

		$client = new APIClient();

		$this->assertEquals( 'https://api.stg.print.com', $client->get_api_base_url() );
	}


    /**
	 * Ensures that presets are sorted by title
	 *
	 * @since 1.0.1
	 */
	public function test_sorts_presets_by_title() {
		putenv( 'PDC_POD_API_BASE_URL=https://testapi.print.com' );
		putenv( 'PDC_POD_API_KEY=fake-key' );

		$body = json_encode( [
			'items' => [
				[ 'sku' => 'test-posters', 'title' => [ 'en' => 'Poster B1' ], 'id' => '1', 'configuration' => [ 'copies' => 1 ] ],
				[ 'sku' => 'test-posters', 'title' => [ 'en' => 'Poster A2' ], 'id' => '2', 'configuration' => [ 'copies' => 1 ] ],
				[ 'sku' => 'test-posters', 'title' => [ 'en' => 'Poster A10' ], 'id' => '3', 'configuration' => [ 'copies' => 1 ] ],
				[ 'sku' => 'test-posters', 'title' => [ 'en' => 'Poster A1' ], 'id' => '4', 'configuration' => [ 'copies' => 1 ] ],
				[ 'sku' => 'test-posters', 'title' => [ 'en' => 'Poster A0' ], 'id' => '5', 'configuration' => [ 'copies' => 1 ] ],
			],
		] );

		WP_Mock::userFunction( 'wp_remote_request', [ 'return' => [] ] );
		WP_Mock::userFunction( 'is_wp_error', [ 'return' => false ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [ 'return' => 200 ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_body', [ 'return' => $body ] );

		$client  = new APIClient();
		$presets = $client->get_presets( 'test-posters' );

		$this->assertEquals( 'Poster A0', $presets[0]->title );
		$this->assertEquals( 'Poster A1', $presets[1]->title );
		$this->assertEquals( 'Poster A2', $presets[2]->title );
		$this->assertEquals( 'Poster A10', $presets[3]->title );
		$this->assertEquals( 'Poster B1', $presets[4]->title );

		putenv( 'PDC_POD_API_BASE_URL' );
		putenv( 'PDC_POD_API_KEY' );
	}

	/**
	 * Tests that get_preset_by_id returns no 'accessories' key when the preset
	 * configuration contains no _accessories.
	 *
	 * @since 1.4.0
	 */
	public function test_get_preset_by_id_returns_no_accessories_key_when_none_configured() {
		putenv( 'PDC_POD_API_BASE_URL=https://testapi.print.com' );
		putenv( 'PDC_POD_API_KEY=fake-key' );

		$preset_body = '{"id":"preset-id-123","sku":"poster-a4","title":{"en":"A4 Poster"},"configuration":{"copies":1}}';

		WP_Mock::userFunction( 'wp_remote_request', [ 'return' => [] ] );
		WP_Mock::userFunction( 'is_wp_error', [ 'return' => false ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [ 'return' => 200 ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_body', [ 'return' => $preset_body ] );

		$client     = new APIClient();
		$reflection = new \ReflectionMethod( APIClient::class, 'get_preset_by_id' );
		$result     = $reflection->invoke( $client, 'preset-id-123' );

		$this->assertInstanceOf( Preset::class, $result );
		$this->assertEquals( 'poster-a4', $result->sku );
		$this->assertNotEmpty( $result->configuration );
		$this->assertEmpty( $result->accessories );

		putenv( 'PDC_POD_API_BASE_URL' );
		putenv( 'PDC_POD_API_KEY' );
	}

	/**
	 * Tests that get_preset_by_id resolves accessories via a second API call,
	 * adds them to the result, and strips _accessories from the options object.
	 *
	 * @since 1.4.0
	 */
	public function test_get_preset_by_id_resolves_accessories_and_strips_them_from_options() {
		putenv( 'PDC_POD_API_BASE_URL=https://testapi.print.com' );
		putenv( 'PDC_POD_API_KEY=fake-key' );

		$preset_body      = '{"id":"preset-id-123","sku":"poster-a4","title":{"en":"A4 Poster"},"configuration":{"copies":1,"_accessories":{"acc-001":2}}}';
		$accessories_body = '[{"id":"acc-001","sku":"envelope","configuration":{"size":"A4"}}]';

		WP_Mock::userFunction( 'wp_remote_request', [ 'return' => [] ] );
		WP_Mock::userFunction( 'is_wp_error', [ 'return' => false ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [ 'return' => 200 ] );
		WP_Mock::userFunction(
			'wp_remote_retrieve_body',
			[ 'return_in_order' => [ $preset_body, $accessories_body ] ]
		);
		WP_Mock::userFunction( 'get_transient', [ 'return' => false ] );
		WP_Mock::userFunction( 'set_transient', [ 'times' => 1 ] );

		$client     = new APIClient();
		$reflection = new \ReflectionMethod( APIClient::class, 'get_preset_by_id' );
		$result     = $reflection->invoke( $client, 'preset-id-123' );

		$this->assertInstanceOf( Preset::class, $result );
		$this->assertNotEmpty( $result->accessories );
		$this->assertCount( 1, $result->accessories );

		$resolved = $result->accessories[0];
		$this->assertEquals( 'acc-001', $resolved->accessory_id );
		$this->assertEquals( 'envelope', $resolved->sku );
		$this->assertEquals( 2, $resolved->copies );

		$this->assertArrayNotHasKey( '_accessories', $result->configuration );

		putenv( 'PDC_POD_API_BASE_URL' );
		putenv( 'PDC_POD_API_KEY' );
	}

	/**
	 * Tests that get_preset_by_id skips an accessory whose ID is absent
	 * from the product accessories list, and omits the 'accessories' key.
	 *
	 * @since 1.4.0
	 */
	public function test_get_preset_by_id_skips_accessory_not_found_in_product_list() {
		putenv( 'PDC_POD_API_BASE_URL=https://testapi.print.com' );
		putenv( 'PDC_POD_API_KEY=fake-key' );

		$preset_body      = '{"id":"preset-id-123","sku":"poster-a4","title":{"en":"A4 Poster"},"configuration":{"copies":1,"_accessories":{"missing-acc":1}}}';
		$accessories_body = '[{"id":"other-acc","sku":"tube","configuration":{}}]';

		// Suppress error-level logging to avoid Logger singleton instantiation.
		WP_Mock::userFunction(
			'get_option',
			[
				'args'   => [ 'pdc-pod-loglevel', 'error' ],
				'return' => 'none',
			]
		);

		WP_Mock::userFunction( 'wp_remote_request', [ 'return' => [] ] );
		WP_Mock::userFunction( 'is_wp_error', [ 'return' => false ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [ 'return' => 200 ] );
		WP_Mock::userFunction(
			'wp_remote_retrieve_body',
			[ 'return_in_order' => [ $preset_body, $accessories_body ] ]
		);
		WP_Mock::userFunction( 'get_transient', [ 'return' => false ] );
		WP_Mock::userFunction( 'set_transient', [ 'times' => 1 ] );

		$client     = new APIClient();
		$reflection = new \ReflectionMethod( APIClient::class, 'get_preset_by_id' );
		$result     = $reflection->invoke( $client, 'preset-id-123' );

		$this->assertInstanceOf( Preset::class, $result );
		$this->assertEmpty( $result->accessories );

		putenv( 'PDC_POD_API_BASE_URL' );
		putenv( 'PDC_POD_API_KEY' );
	}

	/**
	 * Tests that prepare_order_item includes a correctly structured
	 * 'accessories' key when the resolved preset has accessories.
	 *
	 * @since 1.4.0
	 */
	public function test_prepare_order_item_includes_accessories_with_correct_structure() {
		putenv( 'PDC_POD_API_BASE_URL=https://testapi.print.com' );
		putenv( 'PDC_POD_API_KEY=fake-key' );

		$preset_body      = '{"id":"preset-id-123","sku":"poster-a4","title":{"en":"A4 Poster"},"configuration":{"copies":1,"_accessories":{"acc-001":2}}}';
		$accessories_body = '[{"id":"acc-001","sku":"envelope","configuration":{"size":"A4"}}]';

		WP_Mock::userFunction( 'wp_remote_request', [ 'return' => [] ] );
		WP_Mock::userFunction( 'is_wp_error', [ 'return' => false ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [ 'return' => 200 ] );
		WP_Mock::userFunction(
			'wp_remote_retrieve_body',
			[ 'return_in_order' => [ $preset_body, $accessories_body ] ]
		);
		WP_Mock::userFunction( 'get_transient', [ 'return' => false ] );
		WP_Mock::userFunction( 'set_transient', [ 'times' => 1 ] );

		$order = \Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'get_billing_email' )->andReturn( 'buyer@example.com' );

		$order_item = \Mockery::mock( 'WC_Order_Item_Product' );
		$order_item->shouldReceive( 'get_quantity' )->andReturn( 3 );
		$order_item->shouldReceive( 'get_id' )->andReturn( 99 );

		$shipping_address = [
			'city'       => 'Amsterdam',
			'country'    => 'NL',
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'company'    => '',
			'postcode'   => '1000 AA',
			'address_1'  => 'Keizersgracht 1',
			'phone'      => '+31612345678',
		];

		$client     = new APIClient();
		$reflection = new \ReflectionMethod( APIClient::class, 'prepare_order_item' );
		$result     = $reflection->invoke(
			$client,
			$order,
			$order_item,
			'preset-id-123',
			'https://example.com/file.pdf',
			$shipping_address,
			[]
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'accessories', $result );
		$this->assertCount( 1, $result['accessories'] );

		$accessory = $result['accessories'][0];
		$this->assertEquals( 'envelope', $accessory['sku'] );
		$this->assertEquals( 'acc-001', $accessory['accessoryId'] );
		$this->assertEquals( 2, $accessory['options']->copies );
		$this->assertCount( 1, $accessory['shipments'] );
		$this->assertEquals( 2, $accessory['shipments'][0]['copies'] );
		$this->assertEquals( 'buyer@example.com', $accessory['shipments'][0]['address']['email'] );
		$this->assertEquals( 'Amsterdam', $accessory['shipments'][0]['address']['city'] );

		putenv( 'PDC_POD_API_BASE_URL' );
		putenv( 'PDC_POD_API_KEY' );
	}

	/**
	 * Tests that get_preset_by_id calls the accessories endpoint only once
	 * even when the preset references multiple accessories (N+1 prevention).
	 *
	 * @since 1.4.1
	 */
	public function test_get_preset_by_id_calls_accessories_api_only_once_for_multiple_accessories() {
		putenv( 'PDC_POD_API_BASE_URL=https://testapi.print.com' );
		putenv( 'PDC_POD_API_KEY=fake-key' );

		$preset_body      = '{"id":"preset-id-123","sku":"poster-a4","title":{"en":"A4 Poster"},"configuration":{"copies":1,"_accessories":{"acc-001":2,"acc-002":1}}}';
		$accessories_body = '[{"id":"acc-001","sku":"envelope","configuration":{"size":"A4"}},{"id":"acc-002","sku":"tube","configuration":{"size":"A4"}}]';

		// Exactly 2 HTTP calls expected: one for the preset, one for the accessories list.
		WP_Mock::userFunction( 'wp_remote_request', [ 'times' => 2, 'return' => [] ] );
		WP_Mock::userFunction( 'is_wp_error', [ 'return' => false ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [ 'return' => 200 ] );
		WP_Mock::userFunction(
			'wp_remote_retrieve_body',
			[ 'return_in_order' => [ $preset_body, $accessories_body ] ]
		);
		// First accessory lookup: cache miss; second: cache hit (no further HTTP call).
		WP_Mock::userFunction(
			'get_transient',
			[ 'return_in_order' => [ false, $accessories_body ] ]
		);
		WP_Mock::userFunction( 'set_transient', [ 'times' => 1 ] );

		$client     = new APIClient();
		$reflection = new \ReflectionMethod( APIClient::class, 'get_preset_by_id' );
		$result     = $reflection->invoke( $client, 'preset-id-123' );

		$this->assertInstanceOf( Preset::class, $result );
		$this->assertCount( 2, $result->accessories );
		$this->assertEquals( 'acc-001', $result->accessories[0]->accessory_id );
		$this->assertEquals( 'acc-002', $result->accessories[1]->accessory_id );

		putenv( 'PDC_POD_API_BASE_URL' );
		putenv( 'PDC_POD_API_KEY' );
	}

	/**
	 * Tests that prepare_order_item excludes the 'accessories' key when
	 * the preset has no _accessories configured.
	 *
	 * @since 1.4.0
	 */
	public function test_prepare_order_item_excludes_accessories_key_when_preset_has_none() {
		putenv( 'PDC_POD_API_BASE_URL=https://testapi.print.com' );
		putenv( 'PDC_POD_API_KEY=fake-key' );

		$preset_body = '{"id":"preset-id-123","sku":"poster-a4","title":{"en":"A4 Poster"},"configuration":{"copies":1}}';

		WP_Mock::userFunction( 'wp_remote_request', [ 'return' => [] ] );
		WP_Mock::userFunction( 'is_wp_error', [ 'return' => false ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [ 'return' => 200 ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_body', [ 'return' => $preset_body ] );

		$order = \Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'get_billing_email' )->andReturn( 'buyer@example.com' );

		$order_item = \Mockery::mock( 'WC_Order_Item_Product' );
		$order_item->shouldReceive( 'get_quantity' )->andReturn( 1 );
		$order_item->shouldReceive( 'get_id' )->andReturn( 99 );

		$shipping_address = [
			'city'       => 'Amsterdam',
			'country'    => 'NL',
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'company'    => '',
			'postcode'   => '1000 AA',
			'address_1'  => 'Keizersgracht 1',
			'phone'      => '+31612345678',
		];

		$client     = new APIClient();
		$reflection = new \ReflectionMethod( APIClient::class, 'prepare_order_item' );
		$result     = $reflection->invoke(
			$client,
			$order,
			$order_item,
			'preset-id-123',
			'https://example.com/file.pdf',
			$shipping_address,
			[]
		);

		$this->assertIsArray( $result );
		$this->assertArrayNotHasKey( 'accessories', $result );

		putenv( 'PDC_POD_API_BASE_URL' );
		putenv( 'PDC_POD_API_KEY' );
	}

	/**
	 * A timed out order POST must not be reported as a failure: Print.com may
	 * have accepted the order, and calling it failed invites a second purchase.
	 *
	 * @since 1.5.0
	 */
	public function test_purchase_order_items_reports_a_timeout_distinctly() {
		putenv( 'PDC_POD_API_BASE_URL=https://testapi.print.com' );
		putenv( 'PDC_POD_API_KEY=fake-key' );

		$timeout_error = new \WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out after 120001 milliseconds' );

		WP_Mock::userFunction( 'wp_remote_request', [ 'return' => $timeout_error ] );
		WP_Mock::userFunction( 'is_wp_error', [ 'return' => true ] );
		WP_Mock::userFunction( 'get_option', [ 'return' => 'none' ] );
		WP_Mock::userFunction( 'wp_json_encode', [
			'return' => function ( $data ) {
				return json_encode( $data );
			},
		] );

		$order = \Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'get_address' )->andReturn( [ 'city' => 'Deventer' ] );
		$order->shouldReceive( 'get_id' )->andReturn( 42 );
		$order->shouldReceive( 'get_billing_email' )->andReturn( 'test@example.com' );

		WP_Mock::userFunction( 'add_query_arg', [ 'return' => 'https://example.com/webhook' ] );
		WP_Mock::userFunction( 'rest_url', [ 'return' => 'https://example.com/wp-json/pdc/v1/orders/webhook' ] );
		WP_Mock::userFunction( 'esc_url_raw', [ 'return' => 'https://example.com/webhook' ] );

		$client = new APIClient();

		// A preset lookup is attempted first and times out too, which is enough
		// to prove the timeout is propagated rather than flattened to a failure.
		$result = $client->purchase_order_items( $order, [], [] );

		$this->assertTrue( in_array( $result->get_error_code(), [ 'pdc_request_timeout', 'pdc_purchase_timeout' ], true ) );

		putenv( 'PDC_POD_API_BASE_URL' );
		putenv( 'PDC_POD_API_KEY' );
	}
}
