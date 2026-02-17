<?php

/**
 * Product preset mapping page
 *
 * @package Pdc_Pod
 */

$preset_input_name = '_pdc_pod_preset_id';
$pdc_pod_preset_id = '';
?>

<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<p><?php esc_html_e( 'Map your WooCommerce products and variations to Print.com presets.', 'pdc-pod' ); ?></p>

	<form class="card pdc-preset-table-actions" id="pdc-pod-assign-preset">
		<?php require plugin_dir_path( __FILE__ ) . '/' . PDC_POD_NAME . '-admin-product-select.php'; ?>
		<p class="form-field">
			<label><?php esc_html_e( 'Select Preset' ); ?></label>
			<select id="js-pdc-preset-list" class="pdc_preset_select" name="<?php echo esc_attr( $preset_input_name ); ?>" data-testid="pdc-preset-id" data-current-value="<?php echo esc_attr( $pdc_pod_preset_id ); ?>" value="<?php echo esc_attr( (string) $pdc_pod_preset_id ); ?>">
				<?php require plugin_dir_path( __FILE__ ) . '/' . PDC_POD_NAME . '-admin-preset-select.php'; ?>
			</select>
		</p>
		<?php require plugin_dir_path( __FILE__ ) . PDC_POD_NAME . '-admin-input-media.php'; ?>
		<p class="form-field pdc-preset-table-primary">
			<label>Assign Presets</label>
			<button id="pdc-assign-presets-btn" class="button button-primary" disabled>Assign Presets</button>
		</p>
	</form>
	<div class="tablenav top">
		<div class="alignleft actions">
			<?php require plugin_dir_path( __FILE__ ) . PDC_POD_NAME . '-admin-product-search.php'; ?>
		</div>
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						esc_html( _n( '%s item', '%s items', $total_products, 'pdc-pod' ) ),
						number_format_i18n( $total_products )
					);
					?>
				</span>
				<?php
				echo paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
						'total'     => $total_pages,
						'current'   => $paged,
					)
				);
				?>
			</div>
		<?php else : ?>
			<div class="tablenav-pages one-page">
				<span class="displaying-num">
					<?php
					printf(
						esc_html( _n( '%s item', '%s items', $total_products, 'pdc-pod' ) ),
						number_format_i18n( $total_products )
					);
					?>
				</span>
			</div>
		<?php endif; ?>
	</div>

	<form method="post" id="pdc-preset-mapping-form">
		<?php wp_nonce_field( 'pdc_save_preset_mappings', 'pdc_preset_nonce' ); ?>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td class="manage-column check-column">
						<input type="checkbox" id="pdc-select-all-products">
					</td>
					<th class="manage-column">Product</th>
					<th class="manage-column">Type</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr>
						<td colspan="5" style="text-align: center; padding: 40px;">
							<em><?php esc_html_e( 'No products found.', 'pdc-pod' ); ?></em>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $items as $item ) : ?>
						<?php
						$is_variation = 'variation' === $item['type'];
						$is_ghost     = isset( $item['is_ghost'] ) && $item['is_ghost'];
						$row_style    = '';

						if ( $is_ghost ) {
							$row_style = 'background-color: #f9f9f9; opacity: 0.7;';
						}
						?>
						<tr style="<?php echo esc_attr( $row_style ); ?>">
							<th scope="row" class="check-column">
								<input type="checkbox" name="selected_products[]" value="<?php echo esc_attr( $item['id'] ); ?>" class="pdc-product-checkbox">
							</th>
							<td>
								<?php if ( $is_variation ) : ?>
									<span style="color: #666; margin-left: 20px; margin-right: 5px;">↳</span>
								<?php endif; ?>
								<?php
								// Determine edit link based on product type
								if ( $is_variation ) {
									$edit_url = admin_url( 'post.php?post=' . $item['parent_id'] . '&action=edit' );
								} else {
									$edit_url = admin_url( 'post.php?post=' . $item['id'] . '&action=edit' );
								}
								?>
								<strong>
									<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $item['name'] ); ?></a>
								</strong>
								<?php if ( $is_ghost ) : ?>
									<span style="color: #999; font-size: 11px; margin-left: 5px;">(parent)</span>
								<?php endif; ?>
								<br>
								<small style="color: #666; <?php echo $is_variation ? 'margin-left: 45px;' : ''; ?>">
									ID: <?php echo esc_html( $item['id'] ); ?>
									<?php if ( $item['parent_id'] > 0 ) : ?>
										| Parent ID: <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $item['parent_id'] . '&action=edit' ) ); ?>"><?php echo esc_html( $item['parent_id'] ); ?></a>
									<?php endif; ?>
								</small>
							</td>
							<td>
								<span class="<?php echo esc_attr( $is_variation ? 'dashicons dashicons-admin-generic' : 'dashicons dashicons-products' ); ?>"></span>
								<?php echo esc_html( ucfirst( $item['type'] ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<div class="tablenav bottom">
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php
						printf(
							esc_html( _n( '%s item', '%s items', $total_products, 'pdc-pod' ) ),
							number_format_i18n( $total_products )
						);
						?>
					</span>
					<?php
					echo paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'total'     => $total_pages,
							'current'   => $paged,
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $items ) ) : ?>
			<p class="submit">
				<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save Preset Mappings', 'pdc-pod' ); ?></button>
			</p>
		<?php endif; ?>
	</form>
</div>