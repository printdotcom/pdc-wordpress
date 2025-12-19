<?php
/**
 * Admin preset list.
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
 * @var array<PdcPod\Admin\PrintDotCom\Preset> $pdc_pod_presets_for_sku
 * @var string $pdc_pod_preset_id
 */
?>

<option value><?php esc_html_e( 'Select a preset', 'pdc-pod' ); ?></option>
<?php foreach ( $pdc_pod_presets_for_sku as $pdc_pod_preset ) { ?>
	<option value="<?php echo esc_attr( $pdc_pod_preset->id ); ?>" <?php selected( $pdc_pod_preset->id, $pdc_pod_preset_id ); ?>><?php echo esc_html( $pdc_pod_preset->title ); ?></option>
<?php } ?>