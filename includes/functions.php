<?php
/**
 * functions.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function jlt_package_info() {
	if ( JLT_Member::is_employer() ) {
		$package = jlt_get_job_posting_info();
	} elseif ( JLT_Member::is_candidate() ) {
		$package = jlt_get_resume_posting_info();
	}

	return $package;
}

function jlt_package_page_id() {
	$package_page_id = '';
	if ( jlt_is_employer() ) {
		$package_page_id = JLT_Employer_Package::get_setting( 'package_page_id' );
	} elseif ( jlt_is_candidate() ) {
		$package_page_id = JLT_Candidate_Package::get_setting( 'candidate_package_page_id' );
	}

	return $package_page_id;
}

function jlt_package_page_url() {
	$page_id = jlt_package_page_id();
	$url     = esc_url( get_permalink( $page_id ) );

	return apply_filters( 'jlt_package_page_url', $url, $page_id );
}

function jlt_use_package_job() {
	if ( jlt_check_woocommerce_active() ) {
		$employer_package_actions = array(
			jlt_get_action_control( 'post_job' ),
		);

		return in_array( 'package', $employer_package_actions );
	} else {
		return false;
	}
}

function jlt_get_job_posting_remain( $user_id = null ) {
	if ( $user_id === null ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) ) {
		return 0;
	}

	$package   = jlt_get_job_posting_info( $user_id );
	$job_limit = empty( $package ) || ! is_array( $package ) || ! isset( $package[ 'job_limit' ] ) ? 0 : $package[ 'job_limit' ];
	$job_added = jlt_get_job_posting_added( $user_id );

	return absint( $job_limit ) - absint( $job_added );
}

function jlt_package_can_post_job( $result, $user_id ) {
	return jlt_get_job_posting_remain( $user_id ) > 0;
}

add_filter( 'jlt_can_post_job', 'jlt_package_can_post_job', 10, 2 );

function jlt_package_quick_setup_page( $list_pages ) {
	$list_pages[] = array(
		'title'         => __( 'Packages', 'job-listings-package' ),
		'content'       => '[employer_package_list',
		'shortcode'     => '[employer_package_list]',
		'page_template' => '',
		'help'          => __( 'The page for Employer to choose Employer Package', 'job-listings-package' ),
		'setting'       => array(
			'group' => 'employer_package',
			'key'   => 'package_page_id',
			'url'   => jlt_admin_setting_page_url( 'employer_package' ),
		),
	);

	return $list_pages;
}

add_filter( 'jlt_setup_page', 'jlt_package_quick_setup_page' );

function jlt_package_is_enabled_view_resume() {
	if ( ! class_exists( 'Job_Listings_Resume' ) ) {
		return false;
	} else {
		return 'package' == jlt_get_action_control( 'view_resume' );
	}
}