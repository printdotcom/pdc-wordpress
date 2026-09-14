<?php
/**
 * Admin general settings page
 *
 * Renders the Print.com general settings admin page with tabbed navigation.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/admin/partials
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$pdc_pod_current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';

$pdc_pod_tabs = array(
	'general' => __( 'General', 'pdc-pod' ),
	'product' => __( 'Product', 'pdc-pod' ),
	'orders'  => __( 'Orders', 'pdc-pod' ),
	'support' => __( 'Support', 'pdc-pod' ),
);

if ( ! array_key_exists( $pdc_pod_current_tab, $pdc_pod_tabs ) ) {
	$pdc_pod_current_tab = 'general';
}

$pdc_pod_active_tab_name = $pdc_pod_tabs[ $pdc_pod_current_tab ];
?>
<div class="wrap pdc-pod-settings">
	<div class="pdc-pod-settings-head">
		<strong><?php echo esc_html( get_admin_page_title() ); ?></strong>
	</div>
	<nav class="nav-tab-wrapper">
		<?php foreach ( $pdc_pod_tabs as $pdc_pod_tab_id => $pdc_pod_tab_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . PDC_POD_NAME . '&tab=' . $pdc_pod_tab_id ) ); ?>"
				class="nav-tab <?php echo $pdc_pod_current_tab === $pdc_pod_tab_id ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $pdc_pod_tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<form method="post" action="options.php" id="js-<?php echo esc_attr( PDC_POD_NAME ); ?>-general-form">
		<h2 class="screen-reader-text"><?php echo esc_html( $pdc_pod_active_tab_name ); ?></h2>
		<?php
		if ( 'general' === $pdc_pod_current_tab ) {
			settings_fields( PDC_POD_NAME . '-general-options' );
			do_settings_sections( PDC_POD_NAME . '-general' );
		} elseif ( 'product' === $pdc_pod_current_tab ) {
			settings_fields( PDC_POD_NAME . '-product-options' );
			do_settings_sections( PDC_POD_NAME . '-product' );
		} elseif ( 'orders' === $pdc_pod_current_tab ) {
			settings_fields( PDC_POD_NAME . '-orders-options' );
			do_settings_sections( PDC_POD_NAME . '-orders' );
		} elseif ( 'support' === $pdc_pod_current_tab ) {
			settings_fields( PDC_POD_NAME . '-support-options' );
			do_settings_sections( PDC_POD_NAME . '-support' );
		}
		submit_button(
			'Save Settings',
			'primary',
			'submit',
			true,
			array(
				'test-id' => 'pdc-save-settings',
			)
		);
		?>
	</form>
</div>
