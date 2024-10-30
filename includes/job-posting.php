<?php
/**
 * job-posting.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function jlt_package_post_job_step( $steps ) {
	if ( jlt_use_package_job() ) {
		$steps = array(
			'login'            => jlt_get_page_post_job_login_step(),
			'employer_package' => jlt_get_page_post_job_package_step(),
			'post_job'         => jlt_get_page_post_job_post_step(),
			'preview_job'      => jlt_get_page_post_job_preview_step(),
		);
	}

	return $steps;
}

add_filter( 'jlt_page_post_job_steps_list', 'jlt_package_post_job_step' );
function jlt_package_job_posting_info( $posting_info, $user_id ) {
	if ( jlt_use_package_job() ) {
		$posting_info = get_user_meta( $user_id, '_employer_package', true );
	}

	return $posting_info;
}

add_filter( 'jlt_job_posting_info', 'jlt_package_job_posting_info', 10, 2 );

/**
 * Posting job form action
 */

function jlt_posting_job_check_package() {
	if ( jlt_use_package_job() ) {
		if ( jlt_get_job_posting_remain() <= 0 ) {
			jlt_posting_job_redirect_next_step( 'employer_package' );
		}
	}
}

function jlt_posting_job_post_job_action( $action, $next_step, $job_id ) {
	jlt_posting_job_check_package();
}

add_action( 'jlt_posting_job_action_post_job', 'jlt_posting_job_post_job_action', 10, 3 );

function jlt_posting_job_employer_package_action( $action, $next_step, $job_id ) {

	if ( jlt_use_package_job() ) {
		if ( jlt_get_job_posting_remain() > 0 ) {

			jlt_posting_job_redirect_next_step( $next_step );
		}
		if ( jlt_get_job_posting_remain() == 0 ) {
			$package_info = jlt_get_job_posting_info();
			if ( isset( $package_info[ 'product_id' ] ) && ! empty( $package_info[ 'product_id' ] ) ) {
				jlt_message_add( __( 'You can not add job, Please check your plan.', 'job-listings-package' ), 'error' );
				jlt_force_redirect( JLT_Member::get_endpoint_url( 'manage-plan' ) );
			}
		}
	} else {
		jlt_posting_job_redirect_next_step( $next_step );
	}

	jlt_employer_package_list( $job_id );
}

add_action( 'jlt_posting_job_action_employer_package', 'jlt_posting_job_employer_package_action', 10, 3 );

function jlt_job_publish_form_action( $job_id, $job_need_approve ) {

	if ( jlt_use_package_job() ) {
		if ( jlt_get_job_posting_remain() > 0 ) {
			jlt_increase_job_posting_count( get_current_user_id() );
			if ( ! $job_need_approve ) {
				wp_update_post( array(
					'ID'          => $job_id,
					'post_status' => 'publish',
				) );
				jlt_set_job_expired( $job_id );
			} else {
				wp_update_post( array(
					'ID'          => $job_id,
					'post_status' => 'pending',
				) );
				update_post_meta( $job_id, '_in_review', 1 );
			}

			jlt_message_add( __( 'Job successfully added', 'job-listings-package' ) );

			jlt_job_send_notification( $job_id );

			wp_safe_redirect( JLT_Member::get_endpoint_url( 'manage-job' ) );
			exit;
		} else {

			global $woocommerce;

			wp_update_post( array(
				'ID'          => $job_id,
				'post_status' => 'pending_payment',
			) );

			if ( isset( $_POST[ 'package_id' ] ) ) {
				jlt_increase_job_posting_count( get_current_user_id() );

				$employer_package  = wc_get_product( absint( $_POST[ 'package_id' ] ) );
				$quantity          = empty( $_REQUEST[ 'quantity' ] ) ? 1 : wc_stock_amount( $_REQUEST[ 'quantity' ] );
				$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $employer_package->get_id(), $quantity );

				if ( $employer_package->is_type( 'employer_package' ) && $passed_validation ) {

					// Add the product to the cart

					$woocommerce->cart->empty_cart();
					if ( $woocommerce->cart->add_to_cart( $employer_package->get_id(), $quantity, '', '', array( '_job_id' => $job_id ) ) ) {

						wp_safe_redirect( $woocommerce->cart->get_checkout_url() );
						die;
					}
				}
			} else {
				wp_update_post( array(
					'ID'          => $job_id,
					'post_status' => 'trashed',
				) );
			}
		}
	}
}

add_action( 'jlt_job_publish_form_action', 'jlt_job_publish_form_action', 10, 2 );