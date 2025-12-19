<?php
/**
 * Print.com Preset model
 *
 * Provides a data structure for representing a Print.com Preset within the admin context.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/admin/PrintDotCom
 * @since 1.0.0
 */

namespace PdcPod\Admin\PrintDotCom;

/**
 * A class representing a Print.com Preset
 *
 * @link       https://print.com
 * @since      1.0.0
 *
 * @package    Pdc_Pod
 * @subpackage Pdc_Pod/admin
 */
class Preset {

	/**
	 * The preset identifier.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public string $id;

	/**
	 * The product SKU associated with this preset.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public string $sku;

	/**
	 * The preset title.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public string $title;

	/**
	 * Constructs a new Preset instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sku   The product SKU.
	 * @param string $title The preset title.
	 * @param string $id    The preset identifier.
	 */
	public function __construct( $sku, $title, $id ) {
		$this->id    = $id;
		$this->sku   = $sku ?? '';
		$this->title = $title ?? '';
	}
}
