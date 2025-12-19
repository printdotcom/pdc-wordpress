<?php
/**
 * Admin product data tab.
 *
 * Renders the WooCommerce product data tab for connecting to Print.com.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/admin/partials
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Variables available in this file
 *
 * @global WP_Post $post   Global post object.
 * @var string $pdc_pod_sku
 * @var string $pdc_pod_preset_id
 * @var string $preset_input_name
 * @var array<PdcPod\Admin\PrintDotCom\Product> $pdc_products
 * @var array<PdcPod\Admin\PrintDotCom\Preset> $pdc_pod_presets_for_sku
 */
?>
<div id="pdc_product_data_tab" class="panel woocommerce_options_panel">
	<?php wp_nonce_field( 'pdc_pod_save_product', 'pdc_pod_nonce' ); ?>
	<div class="options_group pdc_product_options">
		<p class="form-field">
			<label for="js-pdc-product-selector"><?php esc_html_e( 'Print.com SKU', 'pdc-pod' ); ?></label>
			<select
				id="js-pdc-product-selector"
				data-testid="pdc-product-sku"
				name="<?php echo esc_attr( $this->get_meta_key( 'product_sku' ) ); ?>"
				value="<?php echo esc_attr( (string) $pdc_pod_sku ); ?>">
				<option disabled selected value><?php esc_html_e( 'Choose a product', 'pdc-pod' ); ?></option>
				<?php foreach ( $pdc_products as $pdc_pod ) { ?>
					<option value="<?php echo esc_attr( $pdc_pod->sku ); ?>" <?php selected( $pdc_pod->sku, $pdc_pod_sku ); ?>><?php echo esc_attr( $pdc_pod->title ); ?></option>
				<?php } ?>
			</select>
		</p>
		<p class="form-field">
			<label for="pdc-presets-label"><?php esc_html_e( 'Print.com Preset', 'pdc-pod' ); ?></label>
			<span class="pdc-ac-preset-list">
				<select id="js-pdc-preset-list" class="pdc_preset_select" name="<?php echo esc_attr( $preset_input_name ); ?>" data-testid="pdc-preset-id"  data-current-value="<?php echo esc_attr( $pdc_pod_preset_id ); ?>" value="<?php echo esc_attr( (string) $pdc_pod_preset_id ); ?>">
					<?php require plugin_dir_path( __FILE__ ) . '/' . PDC_POD_NAME . '-admin-preset-select.php'; ?>
				</select>
			</span>
		</p>
		<?php
		/**
		 * Include the media upload input partial.
		 *
		 * @since 1.0.0
		 */
		require_once __DIR__ . '/html-input-mediaupload.php';
		?>
	</div>
</div>