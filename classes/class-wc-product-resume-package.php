<?php
/**
 * class-wc-product-resume-package.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( class_exists( 'Woocommerce' ) ) :
	class WC_Product_Candidate_Package extends WC_Product {

		public function __construct( $product ) {
			$this->product_type = 'candidate_package';
			parent::__construct( $product );
		}

		public function is_purchasable() {
			return true;
		}

		public function is_sold_individually() {
			return true;
		}

		public function is_virtual() {
			return true;
		}

		public function is_downloadable() {
			return true;
		}

		public function has_file( $download_id = '' ) {
			return false;
		}

		public function is_unlimited_resume_posting() {
			return (bool) $this->resume_posting_unlimit;
		}

		public function get_post_resume_limit() {

			if ( $this->is_unlimited_resume_posting() ) {
				return 99999999;
			}

			if ( $this->resume_posting_limit ) {
				if ( $this->resume_posting_limit == 1 ) {
					return 1;
				}

				return $this->resume_posting_limit;
			}

			return 1;
		}

		public function get_package_interval() {

			$interval = get_post_meta( $this->get_id(), '_candidate_package_interval', true );
			$interval = ! empty( $interval ) ? $interval : '';

			return $interval;
		}

		public function get_package_interval_unit() {
			$unit = get_post_meta( $this->get_id(), '_candidate_package_interval_unit', true );
			$unit = ! empty( $unit ) ? $unit : 'day';

			return $unit;
		}

		public function get_can_view_job() {
			return $this->can_view_job;
		}

		public function get_view_job_limit() {
			return $this->view_job_limit;
		}

		public function get_can_apply_job() {
			return $this->can_apply_job;
		}

		public function get_apply_job_limit() {
			return $this->apply_job_limit;
		}

		public function add_to_cart_url() {
			$url = $this->is_in_stock() ? esc_url( remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->id, home_url() ) ) ) : get_permalink( $this->id );

			return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
		}

		public function add_to_cart_text() {
			$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Select', 'job-listings-package' ) : __( 'Read More', 'job-listings-package' );

			return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
		}
	}
endif;