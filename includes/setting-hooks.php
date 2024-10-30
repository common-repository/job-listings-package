<?php
/**
 * setting-hooks.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
function jlt_package_post_job_action( $actions ) {
	$actions = array_merge( array( 'package' => __( 'Employers with Paid Packages', 'job-listings-package' ) ), $actions );

	return $actions;
}

add_filter( 'jlt_post_job_action_options', 'jlt_package_post_job_action' );

function jlt_package_view_job_detail_action( $actions ) {
	$actions = array_merge( array( 'package' => __( 'Candidates with Paid Packages', 'job-listings-package' ) ), $actions );

	return $actions;
}

add_filter( 'jlt_view_job_detail_action_options', 'jlt_package_view_job_detail_action' );

function jlt_package_apply_job_action( $actions ) {
	$actions = array_merge( array( 'package' => __( 'Candidates with Paid Packages', 'job-listings-package' ) ), $actions );

	return $actions;
}

add_filter( 'jlt_apply_job_action_options', 'jlt_package_apply_job_action' );

function jlt_package_view_resume_action( $actions ) {
	$actions = array_merge( array( 'package' => __( 'Employers with Paid Packages', 'job-listings-package' ) ), $actions );

	return $actions;
}

add_filter( 'jlt_view_resume_action_options', 'jlt_package_view_resume_action' );

function jlt_package_view_candidate_contact_action( $actions ) {
	$actions = array_merge( array( 'package' => __( 'Employers with Paid Packages', 'job-listings-package' ) ), $actions );

	return $actions;
}

add_filter( 'jlt_view_candidate_contact_action_options', 'jlt_package_view_candidate_contact_action' );