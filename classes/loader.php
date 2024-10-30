<?php
/**
 * loader.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once JLT_PACKAGE_PLUGIN_DIR . 'classes/class-wc-product-employer-package.php';
require_once JLT_PACKAGE_PLUGIN_DIR . 'classes/class-wc-product-resume-package.php';

require_once JLT_PACKAGE_PLUGIN_DIR . 'classes/class-employer-package.php';
require_once JLT_PACKAGE_PLUGIN_DIR . 'classes/class-candidate-package.php';