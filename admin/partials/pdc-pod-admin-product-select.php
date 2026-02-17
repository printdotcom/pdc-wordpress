<?php
$current_value = isset( $pdc_pod_sku ) ? $pdc_pod_sku : '';
?>
<p class="form-field">
	<label for="js-pdc-product-selector"><?php esc_html_e( 'Print.com SKU', 'pdc-pod' ); ?></label>
	<select
		id="js-pdc-product-selector"
		data-testid="pdc-product-sku"
		name="<?php echo esc_attr( $this->get_meta_key( 'product_sku' ) ); ?>"
		value="<?php echo esc_attr( (string) $current_value ); ?>">
		<option disabled selected value><?php esc_html_e( 'Choose a product', 'pdc-pod' ); ?></option>
		<?php foreach ( $pdc_products as $pdc_pod ) { ?>
			<option value="<?php echo esc_attr( $pdc_pod->sku ); ?>" <?php selected( $pdc_pod->sku, $current_value ); ?>><?php echo esc_attr( $pdc_pod->title ); ?></option>
		<?php } ?>
	</select>
</p>