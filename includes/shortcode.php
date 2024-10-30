<?php
/**
 * shortcode.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function jlt_package_employer_list_shortcode( $atts, $content = null ) {

	$atts = shortcode_atts( array(
		'type'    => '',
		'columns' => 3,
	), $atts );

	if ( 'list' == $atts[ 'type' ] ) {
		ob_start();
		jlt_employer_package_list();

		return ob_get_clean();
	} else {
		ob_start();
		jlt_get_template( 'package/package-employer-columns.php', $atts, '', JLT_PACKAGE_PLUGIN_TEMPLATE_DIR );

		return ob_get_clean();
	}
}

add_shortcode( 'employer_package_list', 'jlt_package_employer_list_shortcode' );

function jlt_package_candidate_list_shortcode( $atts, $content = null ) {

	$atts = shortcode_atts( array(
		'type'    => '',
		'columns' => 3,
	), $atts );

	if ( 'list' == $atts[ 'type' ] ) {
		ob_start();
		jlt_employer_package_list();

		return ob_get_clean();
	} else {
		ob_start();
		jlt_get_template( 'package/package-candidate-columns.php', $atts, '', JLT_PACKAGE_PLUGIN_TEMPLATE_DIR );

		return ob_get_clean();
	}
}

add_shortcode( 'candidate_package_list', 'jlt_package_candidate_list_shortcode' );