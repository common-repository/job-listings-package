<?php
/**
 * template-hooks.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'jlt_before_employer_package', 'jlt_message_print', 10 );