<?php
/**
 * class-wc-product-employer-package.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( class_exists( 'Woocommerce' ) ) :
	class WC_Product_Employer_Package extends WC_Product {

		public function __construct( $product ) {
			$this->product_type = 'employer_package';
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

		public function is_unlimited_job_posting() {
			return (bool) $this->job_posting_unlimit;
		}

		public function get_post_job_limit() {
			if ( $this->is_unlimited_job_posting() ) {
				return 99999999;
			}

			if ( $this->job_posting_limit ) {
				return absint( get_post_meta( $this->get_id(), 'job_posting_limit', true ) );
			}

			return 0;
		}

		public function get_job_feature_limit() {
			if ( $this->job_feature_limit ) {
				return absint( $this->job_feature_limit );
			}

			return 0;
		}

		public function get_can_view_resume() {
			return $this->can_view_resume;
		}

		public function get_job_display_duration() {
			if ( $this->job_display_duration ) {
				return $this->job_display_duration;
			}

			return 1;
		}

		public function get_package_interval() {

			$interval = get_post_meta( $this->get_id(), '_employer_package_interval', true );
			$interval = ! empty( $interval ) ? $interval : '';

			return $interval;
		}

		public function get_package_interval_unit() {
			$unit = get_post_meta( $this->get_id(), '_employer_package_interval_unit', true );
			$unit = ! empty( $unit ) ? $unit : 'day';

			return $unit;
		}

		public function get_company_featured() {
			if ( $this->company_featured ) {
				return (bool) $this->company_featured;
			}

			return false;
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