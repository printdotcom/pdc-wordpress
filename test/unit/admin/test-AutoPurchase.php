<?php

/**
 * Test automatic purchasing
 *
 * Covers the settings that arm automatic purchasing, the split between
 * purchasable and skipped order items, and the claim flag that stops the same
 * print being bought twice.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/tests
 * @since 1.5.0
 */

namespace PdcPod\Tests;

use PdcPod\Admin\AdminCore;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * Automatic purchasing test case.
 *
 * @since 1.5.0
 */
class Test_AutoPurchase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('PDC_POD_NAME')) {
            define('PDC_POD_NAME', 'pdc-pod');
        }
    }

    public function setUp(): void
    {
        parent::setUp();

        WP_Mock::userFunction('sanitize_key', array(
            'return' => function ($key) {
                return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $key));
            },
        ));
    }

    /**
     * Builds an AdminCore with a mocked API client.
     */
    private function admin_core($client = null)
    {
        return new AdminCore($client ?: \Mockery::mock('PdcPod\Admin\PrintDotCom\APIClient'));
    }

    /**
     * Silences the logger by configuring the 'none' log level, and answers the
     * orders option with the given configuration.
     */
    private function mock_options($orders_config)
    {
        WP_Mock::userFunction('get_option', array(
            'return' => function ($name, $default = false) use ($orders_config) {
                if (PDC_POD_NAME . '-loglevel' === $name) {
                    return 'none';
                }
                if (PDC_POD_NAME . '-orders' === $name) {
                    return $orders_config;
                }
                return $default;
            },
        ));
    }

    private function mock_order_statuses()
    {
        WP_Mock::userFunction('wc_get_order_statuses', array(
            'return' => array(
                'wc-pending' => 'Pending payment',
                'wc-processing' => 'Processing',
                'wc-on-hold' => 'On hold',
                'wc-completed' => 'Completed',
                'wc-cancelled' => 'Cancelled',
                'wc-refunded' => 'Refunded',
                'wc-failed' => 'Failed',
                'wc-checkout-draft' => 'Draft',
                'wc-ready-to-print' => 'Ready to print',
            ),
        ));
    }

    /**
     * @testdox get_auto_purchase_statuses() drops the statuses where buying print is always wrong
     */
    public function test_get_auto_purchase_statuses_applies_denylist()
    {
        $this->mock_order_statuses();

        $statuses = $this->admin_core()->get_auto_purchase_statuses();

        $this->assertSame(
            array('processing', 'on-hold', 'completed', 'ready-to-print'),
            array_keys($statuses)
        );
        $this->assertSame('Ready to print', $statuses['ready-to-print']);
    }

    /**
     * @testdox sanitize_orders() defaults to disabled on processing for a non-array value
     */
    public function test_sanitize_orders_defaults_for_invalid_value()
    {
        $sanitized = $this->admin_core()->sanitize_orders('nonsense');

        $this->assertSame(
            array('auto_purchase' => false, 'trigger_status' => 'processing'),
            $sanitized
        );
    }

    /**
     * @testdox sanitize_orders() keeps an allowed status
     */
    public function test_sanitize_orders_accepts_allowed_status()
    {
        $this->mock_order_statuses();

        $sanitized = $this->admin_core()->sanitize_orders(array(
            'auto_purchase' => '1',
            'trigger_status' => 'ready-to-print',
        ));

        $this->assertTrue($sanitized['auto_purchase']);
        $this->assertSame('ready-to-print', $sanitized['trigger_status']);
    }

    /**
     * @testdox sanitize_orders() refuses a denied status and falls back to processing
     */
    public function test_sanitize_orders_rejects_denied_status()
    {
        $this->mock_order_statuses();

        $sanitized = $this->admin_core()->sanitize_orders(array(
            'auto_purchase' => '1',
            'trigger_status' => 'cancelled',
        ));

        $this->assertSame('processing', $sanitized['trigger_status']);
    }

    /**
     * @testdox get_orders_config() is disabled when the option was never saved
     */
    public function test_get_orders_config_defaults_to_disabled()
    {
        $this->mock_options(false);

        $config = $this->admin_core()->get_orders_config();

        $this->assertFalse($config['auto_purchase']);
        $this->assertSame('processing', $config['trigger_status']);
    }

    /**
     * Mocks the three order item meta reads get_purchasable_items() performs.
     *
     * @param array $meta Map of order item ID to preset_id, pdf_url and purchase_date.
     */
    private function mock_order_item_meta(array $meta)
    {
        WP_Mock::userFunction('wc_get_order_item_meta', array(
            'return' => function ($item_id, $key, $single = true) use ($meta) {
                $keys = array(
                    '_pdc-pod_preset_id' => 'preset_id',
                    '_pdc-pod_pdf_url' => 'pdf_url',
                    '_pdc-pod_purchase_date' => 'purchase_date',
                    '_pdc-pod_purchase_state' => 'purchase_state',
                );
                if (!isset($keys[$key], $meta[$item_id])) {
                    return '';
                }
                $field = $keys[$key];
                return isset($meta[$item_id][$field]) ? $meta[$item_id][$field] : '';
            },
        ));

        foreach ($meta as $item_id => $values) {
            $pdf_url = isset($values['pdf_url']) ? $values['pdf_url'] : '';
            WP_Mock::onFilter('pdc_pod_order_item_pdf_url')
                ->with($pdf_url, $item_id)
                ->reply($pdf_url);
        }
    }

    /**
     * @testdox get_purchasable_items() returns items that have a preset and a PDF
     */
    public function test_get_purchasable_items_returns_complete_items()
    {
        $item = new \WC_Order_Item_Product(11, 'Flyers A5');
        $this->mock_order_item_meta(array(
            11 => array('preset_id' => 'flyers_a5', 'pdf_url' => 'https://example.com/a.pdf'),
        ));

        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('get_items')->andReturn(array($item));

        $result = $this->admin_core()->get_purchasable_items($order);

        $this->assertCount(1, $result['items']);
        $this->assertSame('flyers_a5', $result['items'][0]['pdc_pod_preset_id']);
        $this->assertSame('https://example.com/a.pdf', $result['items'][0]['pdc_pod_pdf_url']);
        $this->assertEmpty($result['skipped']);
    }

    /**
     * @testdox get_purchasable_items() reports an item that has a preset but no PDF
     */
    public function test_get_purchasable_items_reports_item_without_pdf()
    {
        $item = new \WC_Order_Item_Product(12, 'Flyers A5');
        $this->mock_order_item_meta(array(
            12 => array('preset_id' => 'flyers_a5', 'pdf_url' => ''),
        ));

        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('get_items')->andReturn(array($item));

        $result = $this->admin_core()->get_purchasable_items($order);

        $this->assertEmpty($result['items']);
        $this->assertArrayHasKey(12, $result['skipped']);
    }

    /**
     * @testdox get_purchasable_items() ignores an ordinary WooCommerce product without reporting it
     */
    public function test_get_purchasable_items_ignores_non_print_item()
    {
        $item = new \WC_Order_Item_Product(13, 'A mug');
        $this->mock_order_item_meta(array(
            13 => array('preset_id' => '', 'pdf_url' => ''),
        ));
        WP_Mock::userFunction('get_post_meta', array('return' => ''));

        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('get_items')->andReturn(array($item));

        $result = $this->admin_core()->get_purchasable_items($order);

        $this->assertEmpty($result['items']);
        $this->assertEmpty($result['skipped']);
    }

    /**
     * @testdox get_purchasable_items() never returns an item that was already purchased
     */
    public function test_get_purchasable_items_skips_purchased_item()
    {
        $item = new \WC_Order_Item_Product(14, 'Flyers A5');
        $this->mock_order_item_meta(array(
            14 => array(
                'preset_id' => 'flyers_a5',
                'pdf_url' => 'https://example.com/a.pdf',
                'purchase_date' => '2026-09-14T10:00:00+00:00',
            ),
        ));

        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('get_items')->andReturn(array($item));

        $result = $this->admin_core()->get_purchasable_items($order);

        $this->assertEmpty($result['items']);
        $this->assertEmpty($result['skipped']);
    }

    /**
     * @testdox maybe_schedule_auto_purchase() does nothing while the setting is off
     */
    public function test_maybe_schedule_does_nothing_when_disabled()
    {
        $this->mock_options(array('auto_purchase' => false, 'trigger_status' => 'processing'));
        WP_Mock::userFunction('as_schedule_single_action', array('times' => 0));

        $this->admin_core()->maybe_schedule_auto_purchase(77, 'pending', 'processing', null);

        $this->assertConditionsMet();
    }

    /**
     * @testdox maybe_schedule_auto_purchase() ignores a status other than the configured one
     */
    public function test_maybe_schedule_ignores_other_status()
    {
        $this->mock_options(array('auto_purchase' => true, 'trigger_status' => 'completed'));
        WP_Mock::userFunction('as_schedule_single_action', array('times' => 0));

        $this->admin_core()->maybe_schedule_auto_purchase(77, 'pending', 'processing', null);

        $this->assertConditionsMet();
    }

    /**
     * @testdox maybe_schedule_auto_purchase() queues the job on the configured status
     */
    public function test_maybe_schedule_queues_the_job()
    {
        $this->mock_options(array('auto_purchase' => true, 'trigger_status' => 'processing'));
        WP_Mock::userFunction('as_has_scheduled_action', array('return' => false));
        WP_Mock::userFunction('as_schedule_single_action', array(
            'times' => 1,
            'return' => 1,
        ));

        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('get_meta')->with('_pdc-pod_auto_purchase_state')->andReturn('');

        $this->admin_core()->maybe_schedule_auto_purchase(77, 'pending', 'processing', $order);

        $this->assertConditionsMet();
    }

    /**
     * @testdox run_auto_purchase() refuses to buy print for a cancelled order
     */
    public function test_run_auto_purchase_skips_cancelled_order()
    {
        $this->mock_options(array('auto_purchase' => true, 'trigger_status' => 'processing'));

        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('get_status')->andReturn('cancelled');
        $order->shouldReceive('add_order_note')->once();
        $order->shouldReceive('get_items')->never();

        WP_Mock::userFunction('wc_get_order', array('return' => $order));

        $this->admin_core()->run_auto_purchase(77);

        $this->assertConditionsMet();
    }

    /**
     * @testdox purchase_prepared_items() stores the API error on every item of the batch
     */
    public function test_purchase_prepared_items_stores_the_error_on_each_item()
    {
        $this->mock_options(array());
        WP_Mock::userFunction('is_wp_error', array('return' => true));
        WP_Mock::userFunction('wp_strip_all_tags', array(
            'return' => function ($text) {
                return $text;
            },
        ));

        $error = \Mockery::mock('WP_Error');
        $error->shouldReceive('get_error_message')->andReturn('Preset does not exist.');
        $error->shouldReceive('get_error_code')->andReturn(404);

        $client = \Mockery::mock('PdcPod\Admin\PrintDotCom\APIClient');
        $client->shouldReceive('purchase_order_items')->once()->andReturn($error);

        $first = new \WC_Order_Item_Product(21, 'Flyers A5');
        $second = new \WC_Order_Item_Product(22, 'Posters A2');
        $order = \Mockery::mock('WC_Order');

        $result = $this->admin_core($client)->purchase_prepared_items($order, array(
            array('order_item' => $first),
            array('order_item' => $second),
        ));

        $this->assertSame($error, $result);
        foreach (array($first, $second) as $item) {
            $stored = $item->get_meta('_pdc-pod_last_error');
            $this->assertSame('Preset does not exist.', $stored['message']);
            $this->assertSame('404', $stored['code']);
            $this->assertNotEmpty($stored['date']);
        }
    }

    /**
     * @testdox get_purchasable_items() never hands out an item whose purchase is in flight
     */
    public function test_get_purchasable_items_skips_an_item_being_purchased()
    {
        $item = new \WC_Order_Item_Product(51, 'Flyers A5');
        $this->mock_order_item_meta(array(
            51 => array(
                'preset_id' => 'flyers_a5',
                'pdf_url' => 'https://example.com/a.pdf',
                'purchase_state' => 'in_progress',
            ),
        ));

        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('get_items')->andReturn(array($item));

        $result = $this->admin_core()->get_purchasable_items($order);

        $this->assertEmpty($result['items']);
        $this->assertArrayHasKey(51, $result['skipped']);
    }

    /**
     * @testdox get_purchasable_items() never hands out an item whose outcome is unknown
     */
    public function test_get_purchasable_items_skips_an_item_with_unknown_outcome()
    {
        $item = new \WC_Order_Item_Product(52, 'Flyers A5');
        $this->mock_order_item_meta(array(
            52 => array(
                'preset_id' => 'flyers_a5',
                'pdf_url' => 'https://example.com/a.pdf',
                'purchase_state' => 'unknown',
            ),
        ));

        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('get_items')->andReturn(array($item));

        $result = $this->admin_core()->get_purchasable_items($order);

        $this->assertEmpty($result['items']);
        $this->assertArrayHasKey(52, $result['skipped']);
    }

    /**
     * @testdox purchase_prepared_items() claims the item before calling Print.com
     */
    public function test_purchase_prepared_items_claims_before_the_call()
    {
        $this->mock_options(array());
        WP_Mock::userFunction('is_wp_error', array('return' => false));

        $item = new \WC_Order_Item_Product(61, 'Flyers A5');

        $client = \Mockery::mock('PdcPod\Admin\PrintDotCom\APIClient');
        $client->shouldReceive('purchase_order_items')->once()->andReturnUsing(function () use ($item) {
            // The claim must already be in place while the call is running.
            $this->assertSame('in_progress', $item->get_meta('_pdc-pod_purchase_state'));
            return (object) array(
                'order' => (object) array(
                    'orderNumber' => '60001234',
                    'grandTotal' => 10,
                    'status' => 'ACCEPTED',
                    'items' => array(),
                ),
            );
        });

        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('add_order_note');

        $this->admin_core($client)->purchase_prepared_items($order, array(
            array('order_item' => $item),
        ));

        $this->assertConditionsMet();
    }

    /**
     * @testdox a definite failure releases the claim so the item can be retried
     */
    public function test_definite_failure_releases_the_claim()
    {
        $this->mock_options(array());
        WP_Mock::userFunction('is_wp_error', array('return' => true));
        WP_Mock::userFunction('wp_strip_all_tags', array(
            'return' => function ($text) {
                return $text;
            },
        ));

        $error = new \WP_Error(404, 'Preset does not exist.');

        $client = \Mockery::mock('PdcPod\Admin\PrintDotCom\APIClient');
        $client->shouldReceive('purchase_order_items')->once()->andReturn($error);

        $item = new \WC_Order_Item_Product(62, 'Flyers A5');
        $order = \Mockery::mock('WC_Order');

        $this->admin_core($client)->purchase_prepared_items($order, array(
            array('order_item' => $item),
        ));

        $this->assertSame('', $item->get_meta('_pdc-pod_purchase_state'));
    }

    /**
     * @testdox a timed out purchase keeps the claim so nothing buys it again
     */
    public function test_timeout_keeps_the_claim()
    {
        $this->mock_options(array());
        WP_Mock::userFunction('is_wp_error', array('return' => true));
        WP_Mock::userFunction('wp_strip_all_tags', array(
            'return' => function ($text) {
                return $text;
            },
        ));

        $timeout = new \WP_Error('pdc_purchase_timeout', 'The purchase timed out.');

        $client = \Mockery::mock('PdcPod\Admin\PrintDotCom\APIClient');
        $client->shouldReceive('purchase_order_items')->once()->andReturn($timeout);

        $item = new \WC_Order_Item_Product(63, 'Flyers A5');
        $order = \Mockery::mock('WC_Order');

        $this->admin_core($client)->purchase_prepared_items($order, array(
            array('order_item' => $item),
        ));

        $this->assertSame('unknown', $item->get_meta('_pdc-pod_purchase_state'));
    }

    /**
     * @testdox update_order_item() clears the stored error once the item is bought
     */
    public function test_update_order_item_clears_the_stored_error()
    {
        $item = new \WC_Order_Item_Product(21, 'Flyers A5');
        $item->update_meta_data('_pdc-pod_last_error', array('message' => 'Preset does not exist.'));

        $pdc_order = json_decode(json_encode(array(
            'orderNumber' => '60001234',
            'grandTotal' => 12.5,
            'status' => 'ACCEPTED',
            'items' => array(array(
                'customerReference' => '21',
                'orderItemNumber' => '60001234-1',
                'status' => 'ACCEPTED',
                'grandTotal' => 12.5,
                'shipments' => array(array('id' => 1)),
            )),
        )));

        $method = new \ReflectionMethod(AdminCore::class, 'update_order_item');
        $method->setAccessible(true);
        $method->invoke($this->admin_core(), $item, $pdc_order);

        $this->assertSame('', $item->get_meta('_pdc-pod_last_error'));
        $this->assertSame('60001234-1', $item->get_meta('_pdc-pod_order_item_number'));
    }

    /**
     * @testdox get_order_item_error() returns null when nothing was stored
     */
    public function test_get_order_item_error_returns_null_without_an_error()
    {
        WP_Mock::userFunction('wc_get_order_item_meta', array('return' => ''));

        $this->assertNull($this->admin_core()->get_order_item_error(21));
    }

    /**
     * @testdox get_order_item_error() returns the stored error
     */
    public function test_get_order_item_error_returns_the_stored_error()
    {
        WP_Mock::userFunction('wc_get_order_item_meta', array(
            'return' => array(
                'code' => '404',
                'message' => 'Preset does not exist.',
                'date' => '2026-09-14T10:00:00+00:00',
            ),
        ));

        $error = $this->admin_core()->get_order_item_error(21);

        $this->assertSame('Preset does not exist.', $error['message']);
    }

}
