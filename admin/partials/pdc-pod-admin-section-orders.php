<?php
/**
 * Admin section: Order configuration
 *
 * Renders the automatic purchasing settings on the Print.com settings page.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/admin/partials
 * @since 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$pdc_pod_orders_config  = $this->get_orders_config();
$pdc_pod_auto_purchase  = $pdc_pod_orders_config['auto_purchase'];
$pdc_pod_trigger_status = $pdc_pod_orders_config['trigger_status'];
$pdc_pod_order_statuses = $this->get_auto_purchase_statuses();
?>

<?php esc_html_e( 'Configure what should happen automatically when an order comes in.', 'pdc-pod' ); ?>

<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><label for="pdc_auto_purchase"><?php esc_html_e( 'Automatic purchasing', 'pdc-pod' ); ?></label></th>
			<td>
				<label for="pdc_auto_purchase">
					<input type="checkbox" id="pdc_auto_purchase" data-testid="pdc-pod-auto_purchase" name="<?php echo esc_attr( PDC_POD_NAME ); ?>-orders[auto_purchase]" value="1" <?php checked( $pdc_pod_auto_purchase, true ); ?> />
					<?php esc_html_e( 'Purchase orders at Print.com automatically', 'pdc-pod' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'When enabled, order items that have both a preset and a PDF are purchased at Print.com without further confirmation.', 'pdc-pod' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pdc_trigger_status"><?php esc_html_e( 'Purchase when order is', 'pdc-pod' ); ?></label></th>
			<td>
				<select id="pdc_trigger_status" data-testid="pdc-pod-trigger_status" name="<?php echo esc_attr( PDC_POD_NAME ); ?>-orders[trigger_status]">
					<?php foreach ( $pdc_pod_order_statuses as $pdc_pod_status_key => $pdc_pod_status_label ) : ?>
						<option value="<?php echo esc_attr( $pdc_pod_status_key ); ?>" <?php selected( $pdc_pod_trigger_status, $pdc_pod_status_key ); ?>>
							<?php echo esc_html( $pdc_pod_status_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Decides on which order status the order will be purchased.', 'pdc-pod' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>
