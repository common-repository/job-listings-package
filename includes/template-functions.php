<?php
/**
 * template-functions.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'jlt_employer_package_list' ) ) :

	function jlt_employer_package_list() {
		jlt_get_template( 'package/package-employer-list.php', '', '', JLT_PACKAGE_PLUGIN_TEMPLATE_DIR );
	}

endif;

if ( ! function_exists( 'jlt_package_checkout_add_endpoint' ) ) :

	function jlt_package_checkout_add_endpoint( $list_endpoint ) {
		$list_endpoint[ 'package-checkout' ] = 'package-checkout';

		return $list_endpoint;
	}

	add_filter( 'jlt_member_list_endpoint', 'jlt_package_checkout_add_endpoint' );

endif;

if ( ! function_exists( 'jlt_member_package_checkout' ) ) :

	function jlt_member_package_checkout() {
		if ( ! jlt_is_logged_in() ) {
			jlt_message_add( __( 'Please login before buying a Package', 'job-listings-package' ), 'error' );
			wp_safe_redirect( JLT_Member::get_member_page_url() );
			exit();
		}

		$product_id = isset( $_GET[ 'product_id' ] ) ? intval($_GET[ 'product_id' ]) : '';
		if ( empty( $product_id ) ) {
			jlt_message_add( __( 'Missing Package ID.', 'job-listings-package' ), 'error' );
			wp_safe_redirect( JLT_Member::get_member_page_url() );
			exit();
		}

		$product      = wc_get_product( $product_id );

		if ( ! $product ) {
			jlt_message_add( __( 'The Package you selected is removed or unavailable. Please choose another package.', 'job-listings-package' ), 'error' );
			wp_safe_redirect( JLT_Member::get_member_page_url() );
			exit();
		} else{

			$package_type = $product->product_type;

			if (jlt_is_employer() && 'candidate_package' == $package_type){
				jlt_message_add( __( 'Employer can\'t buy a candidate package ' , 'job-listings-package' ), 'error' );
				wp_safe_redirect( JLT_Member::get_member_page_url() );
				exit();
			}

			if (jlt_is_candidate() && 'employer_package' == $package_type){
				jlt_message_add( __( 'Candidate can\'t buy a employer package ' , 'job-listings-package' ), 'error' );
				wp_safe_redirect( JLT_Member::get_member_page_url() );
				exit();
			}

		}
		wp_safe_redirect( $product->add_to_cart_url() );
		exit();
	}

	add_action( 'jlt_account_package-checkout_endpoint', 'jlt_member_package_checkout' );
endif;

if ( ! function_exists( 'jlt_package_employer_endpoint_define' ) ) :

	function jlt_package_employer_endpoint_define() {
		$endpoints   = [ ];
		$endpoints[] = array(
			'key'          => 'manage-plan',
			'value'        => jlt_get_endpoints_setting( 'manage-plan', 'manage-plan' ),
			'text'         => __( 'Manage Plan', 'job-listings-package' ),
			'order'        => 20,
			'show_in_menu' => true,
		);

		return $endpoints;
	}

endif;

if ( ! function_exists( 'jlt_package_employer_endpoint' ) ) :

	function jlt_package_employer_endpoint( $endpoints ) {

		$endpoints = array_merge( $endpoints, jlt_package_employer_endpoint_define() );

		return $endpoints;
	}

	add_filter( 'jlt_list_endpoints_employer', 'jlt_package_employer_endpoint' );
	add_filter( 'jlt_list_endpoints_candidate', 'jlt_package_employer_endpoint' );

endif;

if ( ! function_exists( 'jlt_package_add_endpoints' ) ) :
	function jlt_package_add_endpoints() {
		foreach ( jlt_package_employer_endpoint_define() as $endpoint ) {
			add_rewrite_endpoint( $endpoint[ 'value' ], EP_ROOT | EP_PAGES );
		}
	}

	add_action( 'init', 'jlt_package_add_endpoints' );
endif;

if ( ! function_exists( 'jlt_member_manage_plan' ) ) :

	function jlt_member_manage_plan() {
		jlt_get_template( 'member/manage-plan.php', array(), '', JLT_PACKAGE_PLUGIN_TEMPLATE_DIR );
	}

	add_action( 'jlt_account_manage-plan_endpoint', 'jlt_member_manage_plan' );
endif;


if ( ! function_exists( 'jlt_candidate_package_list' ) ) :

	function jlt_candidate_package_list() {
		jlt_get_template( 'package/package-candidate-list.php', '', '', JLT_PACKAGE_PLUGIN_TEMPLATE_DIR );
	}

endif;

