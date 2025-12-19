<?php
/**
 * Admin section: Product configuration
 *
 * Renders the product configuration section on the Print.com settings page.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/admin/partials
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$pdc_pod_product_config = get_option( PDC_POD_NAME . '-product' );

// Handle case where option doesn't exist yet or use_preset_copies is not set.
$pdc_pod_use_preset_copies = isset( $pdc_pod_product_config['use_preset_copies'] ) ? $pdc_pod_product_config['use_preset_copies'] : false;
?>

<?php esc_html_e( 'Configure how to set up the product connection with Print.com.', 'pdc-pod' ); ?>

<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><label for="pdc_use_preset_copies"><?php esc_html_e( 'Presets', 'pdc-pod' ); ?></label></th>
			<td>
				<label for="pdc_use_preset_copies">
					<input type="checkbox" id="pdc_use_preset_copies" data-testid="pdc-pod-use_preset_copies" name="<?php echo esc_attr( PDC_POD_NAME ); ?>-product[use_preset_copies]" value="1" <?php checked( $pdc_pod_use_preset_copies, true ); ?> />
					<?php esc_html_e( 'Use preset copies', 'pdc-pod' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'When enabled, the number of copies from the preset will be used instead of allowing customers to choose their own quantity.', 'pdc-pod' ); ?></p>
			</td>
		</tr>
	</tbody>
</table>