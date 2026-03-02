<?php
/**
 * PHPUnit bootstrap file
 *
 * Initializes the testing environment for WordPress plugin tests.
 *
 * @package Pdc_Pod
 * @subpackage Pdc_Pod/tests
 * @since 1.0.1
 */

require_once dirname( __DIR__ ) . '/../vendor/autoload.php';

WP_Mock::bootstrap();