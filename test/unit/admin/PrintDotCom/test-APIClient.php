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
				[ 'sku' => 'test-posters', 'title' => [ 'en' => 'Poster B1' ], 'id' => '1' ],
				[ 'sku' => 'test-posters', 'title' => [ 'en' => 'Poster A1' ], 'id' => '2' ],
				[ 'sku' => 'test-posters', 'title' => [ 'en' => 'Poster A0' ], 'id' => '3' ],
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
		$this->assertEquals( 'Poster B1', $presets[2]->title );

		putenv( 'PDC_POD_API_BASE_URL' );
		putenv( 'PDC_POD_API_KEY' );
	}
}
