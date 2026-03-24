<?php

/**
 * Test AdminCore functionality
 *
 * Tests for the AdminCore class, specifically focusing on variation data
 * field rendering and SKU prioritization.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/tests
 * @since 1.0.1
 */

namespace PdcPod\Tests;

use PdcPod\Admin\AdminCore;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * AdminCore test case.
 *
 * Tests the AdminCore class
 *
 * @since 1.0.1
 */
class Test_AdminCore extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        if (!defined('PDC_POD_NAME')) {
            define('PDC_POD_NAME', 'pdc-pod');
        }
    }

    /**
     * Tests render_variation_data_fields.
     * SKU can be saved on product level and variation level.
     * It should prioritize the variation level to retrieve presets.
     *
     * @since 1.0.1
     */
    function test_uses_variation_sku_when_saved()
    {
        $parent_id = 123;
        $variation_id = 456;
        $parent_sku = 'parent-sku-123';
        $variation_sku = 'variation-sku-456';
        $meta_key_sku = '_pdc-pod_product_sku';
        $meta_key_preset_id = '_pdc-pod_preset_id';

        // Create a mock WP_Post object.
        $variation_post = $this->getMockBuilder('WP_Post')
            ->disableOriginalConstructor()
            ->getMock();
        $variation_post->ID = $variation_id;
        $variation_post->post_parent = $parent_id;

        WP_Mock::userFunction('get_post_meta', array(
            'times' => 1,
            'args' => array($parent_id, $meta_key_sku, true),
            'return' => $parent_sku,
        ));

        WP_Mock::userFunction('get_post_meta', array(
            'times' => 1,
            'args' => array($variation_id, $meta_key_sku, true),
            'return' => $variation_sku,
        ));

        WP_Mock::userFunction('get_post_meta', array(
            'times' => 1,
            'args' => array($variation_id, $meta_key_preset_id, true),
            'return' => '',
        ));
        
        $meta_key_pdf_url = '_pdc-pod_pdf_url';
        WP_Mock::userFunction('get_post_meta', array(
            'times' => 1,
            'args' => array($variation_id, $meta_key_pdf_url, true),
            'return' => '',
        ));
        
        WP_Mock::userFunction('wp_nonce_field', array(
            'times' => 1,
        ));

        $admin_dir = dirname(__DIR__, 3) . '/admin/';
        $partials_dir = $admin_dir . 'partials/';
        
        WP_Mock::userFunction('plugin_dir_path')
            ->times(2)
            ->andReturn($admin_dir, $partials_dir);

        WP_Mock::userFunction('get_option', array(
            'return' => 'fake-api-key',
        ));

        $mock_client = $this->getMockBuilder('PdcPod\\Admin\\PrintDotCom\\APIClient')
            ->disableOriginalConstructor()
            ->onlyMethods(array('search_products', 'get_presets'))
            ->getMock();

        $mock_client->expects($this->once())
            ->method('search_products')
            ->willReturn(array());

        // Assert that get_presets is called with the variation SKU, not the parent SKU.
        $mock_client->expects($this->once())
            ->method('get_presets')
            ->with($this->equalTo($variation_sku))
            ->willReturn(array());

        $admin_core = new AdminCore($mock_client);

        ob_start();
        $admin_core->render_variation_data_fields(0, array(), $variation_post);
        ob_end_clean();
    }

    /**
     * @testdox get_pdf_url_by_order_item_id()
     */
    public function test_get_pdf_url_by_order_item_id_applies_filter()
    {
        $order_item_id = 999;
        $meta_key_pdf_url = '_pdc-pod_pdf_url';
        $stored_pdf_url = 'https://example.com/original.pdf';
        $filtered_pdf_url = 'https://example.com/filtered.pdf';

        WP_Mock::userFunction('wc_get_order_item_meta', array(
            'times' => 1,
            'args' => array($order_item_id, $meta_key_pdf_url, true),
            'return' => $stored_pdf_url,
        ));

        WP_Mock::onFilter('pdc_pod_order_item_pdf_url')
            ->with($stored_pdf_url, $order_item_id)
            ->reply($filtered_pdf_url);

        $mock_client = $this->getMockBuilder('PdcPod\Admin\PrintDotCom\APIClient')
            ->disableOriginalConstructor()
            ->getMock();

        $admin_core = new AdminCore($mock_client);

        // Access the private get_pdf_url_by_order_item_id method
        $reflection = new \ReflectionMethod(AdminCore::class, 'get_pdf_url_by_order_item_id');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($admin_core, $order_item_id);

        $this->assertEquals($filtered_pdf_url, $result);
    }

    /**
     * @testdox get_pdf_url_by_order_item_id() when the filter doesn't change the value
     */
    public function test_get_pdf_url_by_order_item_id_no_filter_change()
    {
        $order_item_id = 1000;
        $meta_key_pdf_url = '_pdc-pod_pdf_url';
        $stored_pdf_url = 'https://example.com/unfiltered.pdf';

        WP_Mock::userFunction('wc_get_order_item_meta', array(
            'times' => 1,
            'args' => array($order_item_id, $meta_key_pdf_url, true),
            'return' => $stored_pdf_url,
        ));

        // Let the filter pass the value through unchanged (which is the default WP behavior when no filter is attached)
        WP_Mock::onFilter('pdc_pod_order_item_pdf_url')
            ->with($stored_pdf_url, $order_item_id)
            ->reply($stored_pdf_url);

        $mock_client = $this->getMockBuilder('PdcPod\Admin\PrintDotCom\APIClient')
            ->disableOriginalConstructor()
            ->getMock();

        $admin_core = new AdminCore($mock_client);

        // Access the private get_pdf_url_by_order_item_id method
        $reflection = new \ReflectionMethod(AdminCore::class, 'get_pdf_url_by_order_item_id');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($admin_core, $order_item_id);

        $this->assertEquals($stored_pdf_url, $result);
    }
}
