<?php

//Check Plugin Job Listings Resume

if ( ! class_exists( 'Job_Listings_Resume' ) ) {
	return;
}
if ( ! class_exists( 'JLT_Candidate_Package' ) ) :
	class JLT_Candidate_Package {

		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'woocommerce_add_to_cart_handler_candidate_package', array(
				$this,
				'woocommerce_add_to_cart_handler',
			), 100 );

			add_action( 'woocommerce_order_status_completed', array( $this, 'order_paid' ) );
			add_action( 'woocommerce_order_status_changed', array( $this, 'order_changed' ), 10, 3 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_fields_resume_meta' ) );

			// Expired package
			add_action( 'jlt-resume-package-expired', array( 'JLT_Candidate_Package', 'reset_candidate_package' ) );

			if ( is_admin() ) {
				add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_data_tabs' ) );

				add_action( 'admin_init', array( $this, 'admin_init' ) );
				add_action( 'jlt_admin_setting_employer_package', array( $this, 'setting_page' ) );
			} else {
				add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 100 );
			}
		}

		public function init() {

			if ( is_admin() ) {
				add_filter( 'product_type_selector', array( $this, 'product_type_selector' ) );
				add_action( 'woocommerce_product_options_general_product_data', array(
					$this,
					'candidate_package_product_data',
				) );
				add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ) );
			}
		}

		public function pre_get_posts( $q ) {
			global $jlt_view_candidate_package;

			if ( ! jlt_check_woocommerce_active() ) {
				return;
			}
			if ( empty( $jlt_view_candidate_package ) && $this->is_woo_product_query( $q ) ) {
				$tax_query                    = array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'candidate_package' ),
					'operator' => 'NOT IN',
				);
				$q->tax_query->queries[]      = $tax_query;
				$q->query_vars[ 'tax_query' ] = $q->tax_query->queries;
			}
			$jlt_view_candidate_package = false;
		}

		protected function is_woo_product_query( $query = null ) {
			if ( empty( $query ) ) {
				return false;
			}
			if ( isset( $query->query_vars[ 'post_type' ] ) && $query->query_vars[ 'post_type' ] === 'product' ) {
				return true;
			}
			if ( is_post_type_archive( 'product' ) || is_product_taxonomy() ) {
				return true;
			}

			return false;
		}

		public function checkout_fields_resume_meta( $order_id ) {
			global $woocommerce;

			/* -------------------------------------------------------
			 * Create order create fields _resume_id for storing resume that need to activate
			 * ------------------------------------------------------- */
			foreach ( $woocommerce->cart->cart_contents as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item[ '_resume_id' ] ) && is_numeric( $cart_item[ '_resume_id' ] ) ) :

					update_post_meta( $order_id, '_resume_id', sanitize_text_field( $cart_item[ '_resume_id' ] ) );

				endif;
			}
		}

		public function order_paid( $order_id ) {
			$order = new WC_Order( $order_id );
			if ( get_post_meta( $order_id, 'candidate_package_processed', true ) ) {
				return;
			}
			foreach ( $order->get_items() as $item ) {
				$product = wc_get_product( $item[ 'product_id' ] );

				if ( $product->is_type( 'candidate_package' ) && $order->customer_user ) {
					$user_id = $order->customer_user;

					$package_interval      = absint( $product->get_package_interval() );
					$package_interval_unit = $product->get_package_interval_unit();
					$package_data          = array(
						'product_id'            => $product->get_id(),
						'order_id'              => $order_id,
						'created'               => current_time( 'mysql' ),
						'package_interval'      => $package_interval,
						'package_interval_unit' => $package_interval_unit,
						'resume_limit'          => absint( $product->get_post_resume_limit() ),
					);

					$package_data = apply_filters( 'jlt_candidate_package_user_data', $package_data, $product );

					if ( ! self::is_purchased_free_package( $user_id ) || $product->get_price() > 0 ) {
						if ( ! empty( $package_interval ) ) {
							$expired                   = strtotime( "+{$package_interval} {$package_interval_unit}" );
							$package_data[ 'expired' ] = $expired;
							JLT_Candidate_Package::set_expired_package_schedule( $user_id, $package_data );
						}
						update_user_meta( $user_id, '_candidate_package', $package_data );
						update_user_meta( $user_id, '_resume_added', '0' );

						$resume_id = jlt_get_post_meta( $order_id, '_resume_id', '' );
						if ( ! empty( $resume_id ) && is_numeric( $resume_id ) ) {
							$resume = get_post( $resume_id );
							if ( $resume->post_type == 'jlt_resume' ) {
								jlt_increase_resume_posting_count( $user_id );
								$resume_need_approve = (bool) jlt_get_resume_setting( 'resume_approve', '' );
								if ( ! $resume_need_approve ) {
									wp_update_post( array(
										'ID'            => $resume_id,
										'post_status'   => 'publish',
										'post_date'     => current_time( 'mysql' ),
										'post_date_gmt' => current_time( 'mysql', 1 ),
									) );
								} else {
									wp_update_post( array(
										'ID'          => $resume_id,
										'post_status' => 'pending',
									) );
									update_post_meta( $resume_id, '_in_review', 1 );
								}

								JLT_Resume::notify_candidate( $resume_id, $user_id );
							}
						}

						if ( $product->get_price() <= 0 ) {
							update_user_meta( $user_id, '_free_package_bought', 1 );
						}

						if ( $product->is_unlimited_resume_posting() ) {
						}

						do_action( 'jlt_candidate_package_order_completed', $product, $user_id );
					}

					break;
				}
			}
			update_post_meta( $order_id, 'candidate_package_processed', true );
		}

		public function order_changed( $order_id, $old_status, $new_status ) {
			if ( get_post_meta( $order_id, 'candidate_package_processed', true ) ) {

				// Check if order is changing from completed to not completed
				if ( $old_status == 'completed' && $new_status != 'completed' ) {
					$order = new WC_Order( $order_id );
					foreach ( $order->get_items() as $item ) {
						$product = wc_get_product( $item[ 'product_id' ] );

						// Check if there's resume package in this order
						if ( $product->is_type( 'candidate_package' ) && $order->customer_user ) {
							$user_id = $order->customer_user;

							$user_package = jlt_get_job_posting_info( $user_id );

							// Check if user is currently active with this order
							if ( ! empty( $user_package ) && isset( $user_package[ 'order_id' ] ) && absint( $order_id ) == absint( $user_package[ 'order_id' ] ) ) {

								self::reset_candidate_package( $user_id );

								// Reset the processed status so that it can update if the order is reseted.
								update_post_meta( $order_id, 'candidate_package_processed', false );
							}

							break;
						}
					}
				}
			}
		}

		public function woocommerce_add_to_cart_handler() {
			global $woocommerce;
			$product_id        = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST[ 'add-to-cart' ] ) );
			$product           = wc_get_product( absint( $product_id ) );
			$quantity          = empty( $_REQUEST[ 'quantity' ] ) ? 1 : wc_stock_amount( $_REQUEST[ 'quantity' ] );
			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
			if ( $product->is_type( 'candidate_package' ) && $passed_validation ) {
				// Add the product to the cart
				$woocommerce->cart->empty_cart();
				if ( $woocommerce->cart->add_to_cart( $product_id, $quantity ) ) {

					wp_safe_redirect( $woocommerce->cart->get_checkout_url() );
					die;
				}
			}
		}

		public function admin_init() {
		}

		public static function get_setting( $id = null, $default = null ) {
			global $candidate_package_setting;
			if ( ! isset( $candidate_package_setting ) || empty( $candidate_package_setting ) ) {
				$candidate_package_setting = get_option( 'employer_package' );
			}
			if ( isset( $candidate_package_setting[ $id ] ) ) {
				return $candidate_package_setting[ $id ];
			}

			return $default;
		}

		public function product_type_selector( $types ) {
			$types[ 'candidate_package' ] = __( 'Candidate Package', 'job-listings-package' );

			return $types;
		}

		public function candidate_package_product_data() {
			global $post;
			?>
			<div class="options_group show_if_candidate_package">
				<?php

				jlt_wc_wp_time_interval( array(
					'id'                => '_candidate_package_interval',
					'label'             => __( 'Expired After', 'job-listings-package' ),
					'description'       => __( 'The time that buyer can use this package. Use zero for unlimited time.', 'job-listings-package' ),
					'value'             => get_post_meta( $post->ID, '_candidate_package_interval', true ),
					'std'               => 30,
					'placeholder'       => '',
					'desc_tip'          => true,
					'custom_attributes' => array( 'min' => '', 'step' => '1' ),
				) );
				$custom_attributes = get_post_meta( $post->ID, '_resume_posting_unlimit', true ) ? 'disabled' : '';
				woocommerce_wp_text_input( array(
					'id'                => '_resume_posting_limit',
					'label'             => __( 'Resume posting limit', 'job-listings-package' ),
					'description'       => __( 'The number of resumes a user can post with this package.', 'job-listings-package' ),
					'value'             => max( get_post_meta( $post->ID, '_resume_posting_limit', true ), 1 ),
					'placeholder'       => 1,
					'type'              => 'number',
					'desc_tip'          => true,
					'custom_attributes' => array(
						'min'              => '',
						'step'             => '1',
						$custom_attributes => $custom_attributes,
					),
				) );
				woocommerce_wp_checkbox( array(
					'id'          => '_resume_posting_unlimit',
					'label'       => '',
					'value'       => get_post_meta( $post->ID, '_resume_posting_unlimit', true ),
					'description' => __( 'Unlimited posting?', 'job-listings-package' ),
				) );
				?>

				<script type="text/javascript">
					jQuery('.pricing').addClass('show_if_candidate_package');
					jQuery(document).ready(function ($) {
						$("#_resume_posting_unlimit").change(function () {
							if (this.checked) {
								$('#_resume_posting_limit').prop('disabled', true);
							} else {
								$('#_resume_posting_limit').prop('disabled', false);
							}
						});
					});
				</script>
				<?php
				do_action( 'jlt_candidate_package_data' )
				?>
			</div>
			<?php
		}

		public function save_product_data( $post_id ) {
			// Save meta
			$fields = array(
				'_candidate_package_interval'      => '',
				'_candidate_package_interval_unit' => '',
				'_resume_posting_limit'         => 'int',
				'_resume_posting_unlimit'       => '',
			);
			foreach ( $fields as $key => $type ) {
				$value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : '';
				switch ( $type ) {
					case 'int' :
						$value = absint( $value );
						break;
					case 'float' :
						$value = floatval( $value );
						break;
					default :
						$value = sanitize_text_field( $value );
				}
				update_post_meta( $post_id, $key, $value );
			}

			do_action( 'jlt_candidate_package_save_data', $post_id );
		}

		public function product_data_tabs( $product_data_tabs = array() ) {

			if ( empty( $product_data_tabs ) ) {
				return;
			}

			if ( isset( $product_data_tabs[ 'shipping' ] ) && isset( $product_data_tabs[ 'shipping' ][ 'class' ] ) ) {
				$product_data_tabs[ 'shipping' ][ 'class' ][] = 'hide_if_candidate_package';
			}
			if ( isset( $product_data_tabs[ 'linked_product' ] ) && isset( $product_data_tabs[ 'linked_product' ][ 'class' ] ) ) {
				$product_data_tabs[ 'linked_product' ][ 'class' ][] = 'hide_if_candidate_package';
			}
			if ( isset( $product_data_tabs[ 'attribute' ] ) && isset( $product_data_tabs[ 'attribute' ][ 'class' ] ) ) {
				$product_data_tabs[ 'attribute' ][ 'class' ][] = 'hide_if_candidate_package';
			}

			return $product_data_tabs;
		}

		public function add_seting_candidate_package_tab( $tabs ) {

			$tabs[ 'candidate_package' ] = __( 'Candidate Packages', 'job-listings-package' );

			return $tabs;
		}

		public function setting_page() {
			if ( ! jlt_check_woocommerce_active() ) {
				return;
			}
			?>
			<br/>
			<h3><?php echo __( 'Candidate Package Options', 'job-listings-package' ) ?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
				<tr>
					<th>
						<?php esc_html_e( 'Candidate Package Page', 'job-listings-package' ) ?>
					</th>
					<td>
						<?php
						$args = array(
							'name'             => 'employer_package[candidate_package_page_id]',
							'id'               => 'candidate_package_page_id',
							'sort_column'      => 'menu_order',
							'sort_order'       => 'ASC',
							'show_option_none' => ' ',
							'class'            => 'jlt-admin-chosen',
							'echo'             => false,
							'selected'         => self::get_setting( 'candidate_package_page_id' ),
						);
						?>
						<?php echo str_replace( ' id=', " data-placeholder='" . __( 'Select a page&hellip;', 'job-listings-package' ) . "' id=", wp_dropdown_pages( $args ) ); ?>
						<p>
							<small><?php _e( 'Select a page with shortcode [jlt_candidate_package_list]', 'job-listings-package' ); ?></small>
						</p>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e( 'Allow re-purchase free package', 'job-listings-package' ) ?>
					</th>
					<td>
						<?php $resume_repurchase_free = self::get_setting( 'resume_repurchase_free', '' ); ?>
						<input type="hidden" name="employer_package[resume_repurchase_free]" value="">
						<input type="checkbox" <?php checked( $resume_repurchase_free, '1' ); ?>
						       name="employer_package[resume_repurchase_free]" value="1">
						<p>
							<small><?php echo __( 'Enable this option if you allow candidate to purchase the free package more than one time.', 'job-listings-package' ) ?></small>
						</p>
					</td>
				</tr>
				<?php do_action( 'jlt_setting_candidate_package_fields' ); ?>
				</tbody>
			</table>
			<?php
		}

		public static function is_purchased_free_package( $user_id = '' ) {
			if ( empty( $user_id ) ) {
				return false;
			}

			if ( self::get_setting( 'resume_repurchase_free', '' ) ) {
				return false;
			}

			return (bool) get_user_meta( $user_id, '_free_candidate_package_bought', true );
		}

		public static function reset_candidate_package( $user_id = '' ) {
			if ( empty( $user_id ) ) {
				return;
			}

			update_user_meta( $user_id, '_candidate_package', false );
		}

		public static function set_expired_package_schedule( $user_id = '', $package_data = array() ) {
			if ( empty( $user_id ) ) {
				return;
			}
			if ( empty( $package_data ) ) {
				$package_data = jlt_get_resume_posting_info( $user_id );
			}

			wp_clear_scheduled_hook( 'jlt-resume-package-expired', array( $user_id ) );

			if ( isset( $package_data[ 'expired' ] ) && ! empty( $package_data[ 'expired' ] ) ) {
				wp_schedule_single_event( $package_data[ 'expired' ], 'jlt-resume-package-expired', array( $user_id ) );
			}
		}

	}

	new JLT_Candidate_Package();
endif;