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
<div class="wp-list-table pdc-table widefat fixed striped posts" id="js-pdc-order-metabox">
	<fieldset id="js-pdc-order-fieldset">
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
			$pdc_pod_items_count        = 0;
			$pdc_pod_items_ready        = 0;
			foreach ( $order->get_items() as $pdc_pod_order_item_product ) {
				$pdc_pod_order_item_id          = $pdc_pod_order_item_product->get_id();
				$pdc_pod_order_item             = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'order_item' ), true );
				$pdc_pod_order_item_number      = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'order_item_number' ), true );
				$pdc_pod_order_item_grand_total = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'order_item_grand_total' ), true );
				$pdc_pod_purchase_date          = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'purchase_date' ), true );
				$pdc_pod_image_url              = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'image_url' ), true );
				$pdc_pod_order_item_status      = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'order_item_status' ), true );
				$pdc_pod_tnt_url                = wc_get_order_item_meta( $pdc_pod_order_item_id, $this->get_meta_key( 'order_item_tnt_url' ), true );
				$pdc_pod_pdf_url                = $this->get_pdf_url_by_order_item_id( $pdc_pod_order_item_id );
				$pdc_pod_preset_id              = $this->get_preset_id_by_order_item_id( $pdc_pod_order_item_id );

				$pdc_pod_purchase_state  = $this->get_item_purchase_state( $pdc_pod_order_item_id );
				$pdc_pod_last_error      = $this->get_order_item_error( $pdc_pod_order_item_id );
				$pdc_pod_last_error_date = '';
				if ( $pdc_pod_last_error && ! empty( $pdc_pod_last_error['date'] ) ) {
					$pdc_pod_last_error_date = wp_date(
						get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
						strtotime( $pdc_pod_last_error['date'] )
					);
				}

				$pdc_pod_has_file     = ! empty( $pdc_pod_pdf_url );
				$pdc_pod_has_preset   = ! empty( $pdc_pod_preset_id );
				$pdc_pod_filename     = basename( $pdc_pod_pdf_url );
				$pdc_pod_can_purchase = $pdc_pod_has_file && $pdc_pod_has_preset;

				++$pdc_pod_items_count;
				if ( $pdc_pod_can_purchase && empty( $pdc_pod_purchase_date ) ) {
					++$pdc_pod_items_ready;
				}
				?>
				<div class="table-row" id="pdc_order_item_<?php echo esc_attr( $pdc_pod_order_item_id ); ?>">
					<div class="table-row-contents" id="pdc_order_item_<?php echo esc_attr( $pdc_pod_order_item_id ); ?>_inner">
						<div class="table-cell">
							<?php if ( $pdc_pod_order_item_number ) { ?>
								<span><strong><?php esc_html_e( 'Order item number', 'pdc-pod' ); ?></strong> #<?php echo esc_html( $pdc_pod_order_item_number ); ?></span><br>
								<span data-testid="pdc-ordered-copies-<?php echo esc_attr( $pdc_pod_items_count ); ?>"><strong><?php esc_html_e( 'Copies', 'pdc-pod' ); ?></strong> <?php echo esc_html( $pdc_pod_order_item->options->copies ); ?></span><br>
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
								<?php if ( 'in_progress' === $pdc_pod_purchase_state ) { ?>
									<p class="pdc-item-error is-unknown" data-testid="pdc-item-inprogress-<?php echo esc_attr( $pdc_pod_items_count ); ?>">
										<span class="pdc-item-error-message"><?php esc_html_e( 'A purchase is in progress. Wait for it to finish before purchasing again.', 'pdc-pod' ); ?></span>
									</p>
								<?php } ?>
								<?php if ( $pdc_pod_last_error ) { ?>
									<p class="pdc-item-error <?php echo 'unknown' === $pdc_pod_purchase_state ? 'is-unknown' : ''; ?>" data-testid="pdc-item-error-<?php echo esc_attr( $pdc_pod_items_count ); ?>">
										<span class="pdc-item-error-message"><?php echo esc_html( $pdc_pod_last_error['message'] ); ?></span>
										<?php if ( $pdc_pod_last_error_date ) { ?>
											<span class="pdc-item-error-date"><?php echo esc_html( $pdc_pod_last_error_date ); ?></span>
										<?php } ?>
									</p>
								<?php } ?>
							</div>
						</div>
						<div class="table-cell">
							<div class="actions">
								<?php if ( null === $pdc_pod_order_item_number || '' === $pdc_pod_order_item_number ) { ?>
									<input type="text" class="hidden" id="js-pdc-order-pdf-<?php echo esc_attr( $pdc_pod_order_item_id ); ?>" placeholder="<?php esc_attr_e( 'http://', 'pdc-pod' ); ?>" name="<?php echo esc_attr( $pdc_pod_meta_key_pdf_url ); ?>" value="<?php echo esc_attr( $pdc_pod_pdf_url ); ?>" />
									<button
										type="button"
										id="pdc-file-upload-<?php echo esc_attr( $pdc_pod_items_count ); ?>"
										data-order-item-id="<?php echo esc_attr( $pdc_pod_order_item_id ); ?>"
										class="button button-secondary js-pdc-file-upload">
										<?php
										if ( $pdc_pod_pdf_url ) {
											esc_html_e( 'Replace PDF', 'pdc-pod' );
										} else {
											esc_html_e( 'Upload PDF', 'pdc-pod' );
										}
										?>
									</button>

									<button
										type="button"
										id="pdc-order-<?php echo esc_attr( $pdc_pod_items_count ); ?>"
										data-testid="pdc-purchase-orderitem-<?php echo esc_attr( $pdc_pod_items_count ); ?>"
										data-order-item-id="<?php echo esc_attr( $pdc_pod_order_item_id ); ?>"
										data-purchase-state="<?php echo esc_attr( $pdc_pod_purchase_state ); ?>"
										class="button button-primary js-pdc-purchase-orderitem"
										<?php
										if ( ! $pdc_pod_can_purchase ) {
											echo 'disabled';
										}
										?>
										>
										<?php
										if ( '' !== $pdc_pod_purchase_state ) {
											esc_html_e( 'Purchase anyway', 'pdc-pod' );
										} else {
											esc_html_e( 'Purchase', 'pdc-pod' );
										}
										?>
									</button>

									<span class="spinner"></span>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
				<?php

			}
			?>
		</div>
		<div class="table-foot">
			<div class="table-cell table-cell-auto">
				<mark class="pdc-errormark" id="js-pdc-purchase-error">
					<span class="dashicons dashicons-warning"></span>
					<div>
						<strong id="js-pdc-purchase-error-title"></strong>
						<span id="js-pdc-purchase-error-descr"></span>
					</div>
				</mark>
			</div>
			<div class="table-cell">
				<span class="spinner"></span>
				<button
					type="button"
					class="button button-primary"
					data-order-id="<?php echo esc_attr( $order->get_id() ); ?>"
					data-testid="pdc-pod-purchase-all"
					id="js-pdc-purchase-all"
					<?php
					if ( 0 === $pdc_pod_items_ready ) {
						echo 'disabled';
					}
					?>
					>
					<?php
					if ( $pdc_pod_items_ready > 0 ) {
						echo esc_html(
							sprintf(
								/* translators: %d: number of items ready for purchase */
								_n( 'Purchase %d item', 'Purchase all %d items', $pdc_pod_items_ready, 'pdc-pod' ),
								number_format_i18n( $pdc_pod_items_ready )
							)
						);
					} else {
						esc_html_e( 'No items to purchase', 'pdc-pod' );
					}
					?>
				</button>
			</div>
		</div>
	</fieldset>
</div>