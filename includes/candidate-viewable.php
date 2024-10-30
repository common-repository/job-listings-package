<?php
/**
 * candidate-viewable.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function jlt_package_is_enabled_candidate_contact() {
	return 'package' == jlt_get_action_control( 'view_candidate_contact', 'public' );
}

function jlt_package_view_candidate_contact_info( $package ) {
	if ( is_array( $package ) && isset( $package[ 'product_id' ] ) && ! empty( $package[ 'product_id' ] ) ) {
		$product = wc_get_product( absint( $package[ 'product_id' ] ) );

		if ( $product && $product->product_type === 'employer_package' ) {
			if ( jlt_is_enabled_employer_package_view_candidate_contact() && $product->can_view_candidate_contact == '1' ) : ?>
				<li>
					<strong><?php _e( 'View Candidate Contact', 'job-listings-package' ) ?></strong><?php _e( 'Yes', 'job-listings-package' ) ?>
				</li>
			<?php endif;
		}
	}
}

add_action( 'jlt_manage_plan_features_list', 'jlt_package_view_candidate_contact_info' );

function jlt_package_can_view_candidate_contact( $can_view, $resume_id ) {
	if ( jlt_package_is_enabled_candidate_contact() ) {
		$package = jlt_get_job_posting_info();

		if ( empty( $package ) ) {
			return false;
		} else {
			return isset( $package[ 'can_view_candidate_contact' ] ) && $package[ 'can_view_candidate_contact' ] = 1;
		}
	} else {
		return $can_view;
	}
}

add_filter( 'jlt_can_view_candidate_contact', 'jlt_package_can_view_candidate_contact', 10, 2 );