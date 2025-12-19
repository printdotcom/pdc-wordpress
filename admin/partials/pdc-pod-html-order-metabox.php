<?php
/**
 * Admin HTML partial: order metabox
 *
 * Renders the order item details and actions in the WooCommerce order screen.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/admin/partials
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="wp-list-table pdc-table widefat fixed striped posts">
	<div class="table-head">
		<div class="table-head-col">
			<strong><?php esc_html_e( 'Information', 'pdc-pod' ); ?></strong>
		</div>
		<div class="table-head-col">
			<strong><?php esc_html_e( 'Actions', 'pdc-pod' ); ?></strong>
		</div>
	</div>
	<div class="table-body">
		<?php
		$pdc_pod_meta_key_pdf_url   = $this->get_meta_key( 'pdf_url' );
		$pdc_pod_meta_key_preset_id = $this->get_meta_key( 'preset_id' );

		foreach ( $order->get_items() as $pdc_pod_order_item_product ) {
			$pdc_pod_order_item_id          = $pdc_pod_order_item_product->get_id();
			$pdc_pod_order_item             = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'order_item' ), true );
			$pdc_pod_order_item_number      = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'order_item_number' ), true );
			$pdc_pod_order_item_grand_total = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'order_item_grand_total' ), true );
			$pdc_pod_purchase_date          = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'purchase_date' ), true );
			$pdc_pod_image_url              = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'image_url' ), true );
			$pdc_pod_pdf_url                = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'pdf_url' ), true );
			$pdc_pod_order_item_status      = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'order_item_status' ), true );
			$pdc_pod_tnt_url                = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'order_item_tnt_url' ), true );
			$pdc_pod_preset_id              = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'preset_id' ), true );

			if ( empty( $pdc_pod_preset_id ) ) {
				$pdc_pod_variation_id = $pdc_pod_order_item_product->get_variation_id();
				if ( $pdc_pod_variation_id ) {
					$pdc_pod_preset_id = get_post_meta( $pdc_pod_variation_id, $pdc_pod_meta_key_preset_id, true );
				}

				if ( empty( $pdc_pod_preset_id ) ) {
					$pdc_pod_product_id = $pdc_pod_order_item_product->get_product_id();
					if ( $pdc_pod_product_id ) {
						$pdc_pod_preset_id = get_post_meta( $pdc_pod_product_id, $pdc_pod_meta_key_preset_id, true );
					}
				}
			}

			$pdc_pod_has_file   = $pdc_pod_pdf_url ? true : false;
			$pdc_pod_has_preset = $pdc_pod_preset_id ? true : false;
			$pdc_pod_filename   = basename( $pdc_pod_pdf_url );
			?>
			<div class="table-row" id="pdc_order_item_<?php echo esc_attr( $pdc_pod_order_item_id ); ?>">
				<div class="table-row-contents" id="pdc_order_item_<?php echo esc_attr( $pdc_pod_order_item_id ); ?>_inner">
					<div class="table-cell">
						<?php if ( $pdc_pod_order_item_number ) { ?>
							<span><strong><?php esc_html_e( 'Order item number', 'pdc-pod' ); ?></strong> #<?php echo esc_html( $pdc_pod_order_item_number ); ?></span><br>
							<span data-testid="pdc-ordered-copies"><strong><?php esc_html_e( 'Copies', 'pdc-pod' ); ?></strong> <?php echo esc_html( $pdc_pod_order_item->options->copies ); ?></span><br>
							<span><strong><?php esc_html_e( 'Purchase Date', 'pdc-pod' ); ?></strong> <?php echo esc_html( $pdc_pod_purchase_date ); ?></span><br>
							<span><strong><?php esc_html_e( 'Item Status', 'pdc-pod' ); ?></strong> <?php echo esc_html( $pdc_pod_order_item_status ); ?></span><br>
							<span><strong><?php esc_html_e( 'Price', 'pdc-pod' ); ?></strong> <?php echo wp_kses_post( wc_price( $pdc_pod_order_item_grand_total ) ); ?></span><br>
							<span><strong><?php esc_html_e( 'Track & Trace', 'pdc-pod' ); ?></strong> <a href="<?php echo esc_url( $pdc_pod_tnt_url ); ?>"><?php echo esc_html( $pdc_pod_tnt_url ); ?></a></span><br>
						<?php } ?>

						<?php if ( $pdc_pod_pdf_url ) { ?>
							<span><strong><?php esc_html_e( 'File', 'pdc-pod' ); ?></strong> <a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $pdc_pod_pdf_url ); ?>"><?php echo esc_html( $pdc_pod_filename ); ?></a></span><br>
						<?php } ?>

						<div class="notifications">
							<?php
							if ( ! $pdc_pod_has_file ) {
								?>
								<p><?php esc_html_e( 'Missing file. Upload one to purchase.', 'pdc-pod' ); ?></p> <?php } ?>
							<?php
							if ( ! $pdc_pod_has_preset ) {
								?>
								<p><?php esc_html_e( 'Missing preset. You need a connected preset on the product to purchase.', 'pdc-pod' ); ?></p><?php } ?>
						</div>
					</div>
					<div class="table-cell">
						<div class="actions">
							<?php if ( null === $pdc_pod_order_item_number || '' === $pdc_pod_order_item_number ) { ?>
								<input type="text" class="hidden" id="js-pdc-order-pdf" placeholder="<?php esc_attr_e( 'http://', 'pdc-pod' ); ?>" name="<?php echo esc_attr( $pdc_pod_meta_key_pdf_url ); ?>" value="<?php echo esc_attr( $pdc_pod_pdf_url ); ?>" />
								<?php
								if ( empty( $pdc_pod_pdf_url ) ) {
									?>
								<button type="button" id="pdc-file-upload" data-order-item-id="<?php echo esc_attr( $pdc_pod_order_item_id ); ?>" class="button button-secondary"><?php esc_html_e( 'Upload PDF', 'pdc-pod' ); ?></button><?php } ?>
								<?php
								if ( $pdc_pod_pdf_url ) {
									?>
								<button type="button" id="pdc-file-upload" data-order-item-id="<?php echo esc_attr( $pdc_pod_order_item_id ); ?>" class="button button-secondary"><?php esc_html_e( 'Replace PDF', 'pdc-pod' ); ?></button><?php } ?>
								<?php if ( $pdc_pod_has_preset ) { ?>
									<button type="button" id="pdc-order" data-testid="pdc-purchase-orderitem" data-order-item-id="<?php echo esc_attr( $pdc_pod_order_item_id ); ?>" class="button button-primary"><?php esc_html_e( 'Purchase', 'pdc-pod' ); ?></button>
								<?php } ?>
								<span class="spinner" id="js-pdc-action-spinner"></span>
								<div class="notice-warning"><span id="js-pdc-request-response"></span></div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
