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

require_once JLT_PACKAGE_PLUGIN_DIR . 'includes/functions.php';
require_once JLT_PACKAGE_PLUGIN_DIR . 'includes/setting-hooks.php';
require_once JLT_PACKAGE_PLUGIN_DIR . 'includes/job-posting.php';
require_once JLT_PACKAGE_PLUGIN_DIR . 'includes/shortcode.php';

require_once JLT_PACKAGE_PLUGIN_DIR . 'includes/job-viewable-candidate-package.php';
require_once JLT_PACKAGE_PLUGIN_DIR . 'includes/resume-viewable.php';
require_once JLT_PACKAGE_PLUGIN_DIR . 'includes/candidate-viewable.php';

require_once JLT_PACKAGE_PLUGIN_DIR . 'includes/template-functions.php';
require_once JLT_PACKAGE_PLUGIN_DIR . 'includes/template-hooks.php';