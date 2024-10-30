<?php

if ( ! class_exists( 'JLT_Employer_Package' ) ) :
	class JLT_Employer_Package {

		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );

			add_action( 'woocommerce_add_to_cart_handler_employer_package', array(
				$this,
				'woocommerce_add_to_cart_handler',
			), 100 );
			add_action( 'woocommerce_order_status_completed', array( $this, 'order_paid' ) );
			add_action( 'woocommerce_order_status_changed', array( $this, 'order_changed' ), 10, 3 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_fields_job_meta' ) );

			// Expired package
			add_action( 'jlt_employer_package_expired', array( 'JLT_Employer_Package', 'reset_employer_package' ) );

			if ( is_admin() ) {
				add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_data_tabs' ) );

				add_action( 'admin_init', array( $this, 'admin_init' ) );
				add_filter( 'jlt_admin_settings_tabs_array', array( $this, 'add_seting_employer_package_tab' ) );
				add_action( 'jlt_admin_setting_employer_package', array( $this, 'setting_page' ) );
				add_filter( 'admin_enqueue_scripts', array( $this, 'wc_admin_scripts' ) );
			} else {
				add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 100 );
			}
		}

		public function init() {

			if ( is_admin() ) {
				add_filter( 'product_type_selector', array( $this, 'product_type_selector' ) );
				add_action( 'woocommerce_product_options_general_product_data', array(
					$this,
					'employer_package_product_data',
				) );
				add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ) );
			}
		}

		public function pre_get_posts( $q ) {
			global $jlt_view_employer_package;

			if ( ! jlt_check_woocommerce_active() ) {
				return;
			}
			if ( empty( $jlt_view_employer_package ) && $this->is_woo_product_query( $q ) ) {
				$tax_query                    = array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'employer_package' ),
					'operator' => 'NOT IN',
				);
				$q->tax_query->queries[]      = $tax_query;
				$q->query_vars[ 'tax_query' ] = $q->tax_query->queries;
			}
			$jlt_view_employer_package = false;
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

		public function checkout_fields_job_meta( $order_id ) {
			global $woocommerce;

			foreach ( $woocommerce->cart->cart_contents as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item[ '_job_id' ] ) && is_numeric( $cart_item[ '_job_id' ] ) ) :

					update_post_meta( $order_id, '_job_id', sanitize_text_field( $cart_item[ '_job_id' ] ) );

				endif;
			}
		}

		public function order_paid( $order_id ) {
			$order = new WC_Order( $order_id );
			if ( get_post_meta( $order_id, 'employer_package_processed', true ) ) {
				return;
			}
			foreach ( $order->get_items() as $item ) {
				$product = wc_get_product( $item[ 'product_id' ] );

				if ( $product->is_type( 'employer_package' ) && $order->customer_user ) {
					$user_id = $order->customer_user;

					$package_interval      = absint( $product->get_package_interval() );
					$package_interval_unit = $product->get_package_interval_unit();
					$package_data          = array(
						'product_id'            => $product->get_id(),
						'order_id'              => $order_id,
						'created'               => current_time( 'mysql' ),
						'package_interval'      => $package_interval,
						'package_interval_unit' => $package_interval_unit,
						'job_duration'          => absint( $product->get_job_display_duration() ),
						'job_limit'             => absint( $product->get_post_job_limit() ),
						'job_featured'          => absint( $product->get_job_feature_limit() ),
						'company_featured'      => $product->get_company_featured(),
					);

					$package_data = apply_filters( 'jlt_employer_package_user_data', $package_data, $product );

					if ( ! self::is_purchased_free_package( $user_id ) || $product->get_price() > 0 ) {
						if ( $product->get_company_featured() ) {
							$company_id = jlt_get_employer_company( $user_id );
							if ( $company_id ) {
								update_post_meta( $company_id, '_company_featured', 'yes' );
							}
						}
						if ( ! empty( $package_interval ) ) {
							$expired                   = strtotime( "+{$package_interval} {$package_interval_unit}" );
							$package_data[ 'expired' ] = $expired;
							JLT_Employer_Package::set_expired_package_schedule( $user_id, $package_data );
						}
						update_user_meta( $user_id, '_employer_package', $package_data );
						update_user_meta( $user_id, '_job_added', '0' );
						update_user_meta( $user_id, '_job_featured', '0' );

						$job_id = jlt_get_post_meta( $order_id, '_job_id', '' );
						if ( ! empty( $job_id ) && is_numeric( $job_id ) ) {
							$job = get_post( $job_id );
							if ( $job->post_type == 'job' ) {
								jlt_increase_job_posting_count( $user_id );
								$job_need_approve = jlt_get_job_setting( 'job_approve', 'yes' ) == 'yes';
								if ( ! $job_need_approve ) {
									wp_update_post( array(
										'ID'            => $job_id,
										'post_status'   => 'publish',
										'post_date'     => current_time( 'mysql' ),
										'post_date_gmt' => current_time( 'mysql', 1 ),
									) );
									jlt_set_job_expired( $job_id );
								} else {
									wp_update_post( array(
										'ID'          => $job_id,
										'post_status' => 'pending',
									) );
									update_post_meta( $job_id, '_in_review', 1 );
								}

								jlt_job_send_notification( $job_id, $user_id );
							}
						}

						if ( $product->get_price() <= 0 ) {
							update_user_meta( $user_id, '_free_package_bought', 1 );
						}

						do_action( 'jlt_employer_package_order_completed', $product, $user_id );
					}

					break;
				}
			}
			update_post_meta( $order_id, 'employer_package_processed', true );
		}

		public function order_changed( $order_id, $old_status, $new_status ) {
			if ( get_post_meta( $order_id, 'employer_package_processed', true ) ) {

				// Check if order is changing from completed to not completed
				if ( $old_status == 'completed' && $new_status != 'completed' ) {
					$order = new WC_Order( $order_id );
					foreach ( $order->get_items() as $item ) {
						$product = wc_get_product( $item[ 'product_id' ] );

						// Check if there's job package in this order
						if ( $product->is_type( 'employer_package' ) && $order->customer_user ) {
							$user_id = $order->customer_user;

							$user_package = jlt_get_job_posting_info( $user_id );

							// Check if user is currently active with this order
							if ( ! empty( $user_package ) && isset( $user_package[ 'order_id' ] ) && absint( $order_id ) == absint( $user_package[ 'order_id' ] ) ) {

								self::reset_employer_package( $user_id );

								// Reset the processed status so that it can update if the order is reseted.
								update_post_meta( $order_id, 'employer_package_processed', false );
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
			if ( $product->is_type( 'employer_package' ) && $passed_validation ) {
				// Add the product to the cart
				$woocommerce->cart->empty_cart();
				if ( $woocommerce->cart->add_to_cart( $product_id, $quantity ) ) {
					//woocommerce_add_to_cart_message( $product_id );
					wp_safe_redirect( $woocommerce->cart->get_checkout_url() );
					die;
				}
			}
		}

		public function admin_init() {
			register_setting( 'employer_package', 'employer_package' );
		}

		public static function get_setting( $id = null, $default = null ) {
			global $employer_package_setting;
			if ( ! isset( $employer_package_setting ) || empty( $employer_package_setting ) ) {
				$employer_package_setting = get_option( 'employer_package' );
			}
			if ( isset( $employer_package_setting[ $id ] ) ) {
				return $employer_package_setting[ $id ];
			}

			return $default;
		}

		public function product_type_selector( $types ) {
			$types[ 'employer_package' ] = __( 'Employer Package', 'job-listings-package' );

			return $types;
		}

		public function wc_admin_scripts() {
			if ( get_post_type() === 'product' ) {
				wp_enqueue_style( 'package-admin-product', JLT_PACKAGE_PLUGIN_URL . 'assets/admin/css/package-product.css' );
			}
		}

		public function employer_package_product_data() {
			global $post;
			?>
			<div class="options_group show_if_employer_package">
				<?php

				jlt_wc_wp_time_interval( array(
					'id'                => '_employer_package_interval',
					'label'             => __( 'Expired After', 'job-listings-package' ),
					'description'       => __( 'The time that buyer can use this package. Use zero for unlimited time.', 'job-listings-package' ),
					'value'             => get_post_meta( $post->ID, '_employer_package_interval', true ),
					'std'               => 30,
					'placeholder'       => '',
					'desc_tip'          => true,
					'custom_attributes' => array( 'min' => '', 'step' => '1' ),
				) );
				$custom_attributes = get_post_meta( $post->ID, '_job_posting_unlimit', true ) ? 'disabled' : '';
				woocommerce_wp_text_input( array(
					'id'                => '_job_posting_limit',
					'label'             => __( 'Job posting limit', 'job-listings-package' ),
					'description'       => __( 'The number of jobs an user can post with this package.', 'job-listings-package' ),
					'value'             => max( get_post_meta( $post->ID, '_job_posting_limit', true ), 0 ),
					'placeholder'       => __( 'No job posting', 'job-listings-package' ),
					'type'              => 'number',
					'desc_tip'          => true,
					'custom_attributes' => array(
						'min'              => '',
						'step'             => '1',
						$custom_attributes => $custom_attributes,
					),
				) );
				woocommerce_wp_checkbox( array(
					'id'          => '_job_posting_unlimit',
					'label'       => '',
					'value'       => get_post_meta( $post->ID, '_job_posting_unlimit', true ),
					'description' => __( 'Unlimited posting?', 'job-listings-package' ),
				) );
				woocommerce_wp_text_input( array(
					'id'                => '_job_feature_limit',
					'label'             => __( 'Featured Job limit', 'job-listings-package' ),
					'description'       => __( 'The number of featured jobs an employer can set with this package.', 'job-listings-package' ),
					'value'             => max( get_post_meta( $post->ID, '_job_feature_limit', true ), 0 ),
					'placeholder'       => '',
					'desc_tip'          => true,
					'type'              => 'number',
					'custom_attributes' => array( 'min' => '', 'step' => '1' ),
				) );
				woocommerce_wp_text_input( array(
					'id'                => '_job_display_duration',
					'label'             => __( 'Job display duration', 'job-listings-package' ),
					'description'       => __( 'The number of days that the job listing will be displayed.', 'job-listings-package' ),
					'value'             => get_post_meta( $post->ID, '_job_display_duration', true ),
					'std'               => 30,
					'placeholder'       => '',
					'desc_tip'          => true,
					'type'              => 'number',
					'custom_attributes' => array( 'min' => '', 'step' => '1' ),
				) );
				woocommerce_wp_checkbox( array(
					'id'          => '_company_featured',
					'label'       => __( 'Featured Company', 'job-listings-package' ),
					'value'       => get_post_meta( $post->ID, '_company_featured', true ),
					'description' => __( 'Company ( Employer ) with this package will be featured.', 'job-listings-package' ),
				) );
				if ( jlt_package_is_enabled_view_resume() ) {
					woocommerce_wp_checkbox( array(
						'id'          => '_can_view_resume',
						'label'       => __( 'Can view Resume', 'job-listings-package' ),
						'description' => __( 'Allow buyers to access resumes.', 'job-listings-package' ),
						'cbvalue'     => 1,
						'desc_tip'    => false,
					) );

					$disable_field = get_post_meta( $post->ID, '_can_view_resume', true ) === '1' ? '' : 'disabled';
					woocommerce_wp_text_input( array(
						'id'                => '_resume_view_limit',
						'label'             => __( 'Resume view limit', 'job-listings-package' ),
						'description'       => __( 'The maximum number of resumes this package allows employers to view, input -1 for unlimited.', 'job-listings-package' ),
						'placeholder'       => '',
						'type'              => 'number',
						'value'             => get_post_meta( $post->ID, '_resume_view_limit', true ),
						'desc_tip'          => true,
						'custom_attributes' => array( 'min' => '', 'step' => '1', $disable_field => $disable_field ),
					) );
					?>
					<script type="text/javascript">
						jQuery('.pricing').addClass('show_if_employer_package');
						jQuery(document).ready(function ($) {
							$("#_can_view_resume").change(function () {
								if (this.checked) {
									$('#_resume_view_limit').prop('disabled', false);
								} else {
									$('#_resume_view_limit').prop('disabled', true);
								}
							});
						});
					</script>
					<?php
				}
				if ( jlt_package_is_enabled_candidate_contact() ) {
					woocommerce_wp_checkbox( array(
						'id'          => '_can_view_candidate_contact',
						'label'       => __( 'View Candidate Contact', 'job-listings-package' ),
						'description' => __( 'Allowing buyers to see Candidate Contact.', 'job-listings-package' ),
						'cbvalue'     => 1,
						'desc_tip'    => false,
					) );
				}
				?>

				<script type="text/javascript">
					jQuery('.pricing').addClass('show_if_employer_package');
					jQuery(document).ready(function ($) {
						$("#_job_posting_unlimit").change(function () {
							if (this.checked) {
								$('#_job_posting_limit').prop('disabled', true);
							} else {
								$('#_job_posting_limit').prop('disabled', false);
							}
						});
					});
				</script>
				<?php
				do_action( 'jlt_employer_package_data' )
				?>
			</div>
			<?php
		}

		public function save_product_data( $post_id ) {
			// Save meta
			$fields = array(
				'_employer_package_interval'      => '',
				'_employer_package_interval_unit' => '',
				'_job_posting_limit'              => 'int',
				'_job_feature_limit'              => 'int',
				'_job_posting_unlimit'            => '',
				'_job_display_duration'           => '',
				'_company_featured'               => '',
				'_can_view_resume'                => '',
				'_resume_view_limit'              => 'int',
				'_can_view_candidate_contact'     => '',
			);

			$fields = apply_filters( 'jlt_employer_package_fields_data', $fields );
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

			do_action( 'jlt_employer_package_save_data', $post_id );
		}

		public function product_data_tabs( $product_data_tabs = array() ) {
			if ( empty( $product_data_tabs ) ) {
				return;
			}

			if ( isset( $product_data_tabs[ 'shipping' ] ) && isset( $product_data_tabs[ 'shipping' ][ 'class' ] ) ) {
				$product_data_tabs[ 'shipping' ][ 'class' ][] = 'hide_if_employer_package';
			}
			if ( isset( $product_data_tabs[ 'linked_product' ] ) && isset( $product_data_tabs[ 'linked_product' ][ 'class' ] ) ) {
				$product_data_tabs[ 'linked_product' ][ 'class' ][] = 'hide_if_employer_package';
			}
			if ( isset( $product_data_tabs[ 'attribute' ] ) && isset( $product_data_tabs[ 'attribute' ][ 'class' ] ) ) {
				$product_data_tabs[ 'attribute' ][ 'class' ][] = 'hide_if_employer_package';
			}

			return $product_data_tabs;
		}

		public function add_seting_employer_package_tab( $tabs ) {
			$temp1 = array_slice( $tabs, 0, 2 );
			$temp2 = array_slice( $tabs, 2 );

			$employer_package_tab = array( 'employer_package' => __( 'Packages', 'job-listings-package' ) );

			return array_merge( $temp1, $employer_package_tab, $temp2 );
		}

		public function setting_page() {
			?>
			<?php settings_fields( 'employer_package' ); ?>
			<h3><?php echo __( 'Employer Package Options', 'job-listings-package' ) ?></h3>
			<table class="form-table" cellspacing="0">
				<tbody>
				<?php if ( jlt_check_woocommerce_active() ) : ?>
					<tr>
						<th>
							<?php esc_html_e( 'Employer Package Page', 'job-listings-package' ) ?>
						</th>
						<td>
							<?php
							$args = array(
								'name'             => 'employer_package[package_page_id]',
								'id'               => 'package_page_id',
								'show_option_none' => __( 'Select a page', 'job-listings-package' ),
								'class'            => 'jlt-admin-chosen',
								'echo'             => false,
								'selected'         => self::get_setting( 'package_page_id' ),
							);
							?>
							<?php echo str_replace( ' id=', " data-placeholder='" . __( 'Select a page&hellip;', 'job-listings-package' ) . "' id=", wp_dropdown_pages( $args ) ); ?>
							<p>
								<small><?php _e( 'Select a page with shortcode [employer_package_list]', 'job-listings-package' ); ?></small>
							</p>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e( 'Allow re-purchase free package', 'job-listings-package' ) ?>
						</th>
						<td>
							<?php $repurchase_free = self::get_setting( 'repurchase_free', '' ); ?>
							<input type="hidden" name="employer_package[repurchase_free]" value="">
							<input type="checkbox" <?php checked( $repurchase_free, '1' ); ?>
							       name="employer_package[repurchase_free]" value="1">
							<p>
								<small><?php echo __( 'Enable this option if you allow employer to purchase the free package more than one time.', 'job-listings-package' ) ?></small>
							</p>
						</td>
					</tr>
				<?php else : ?>
					<tr>
						<th>
							<?php esc_html_e( 'WooCommerce Missing', 'job-listings-package' ) ?>
						</th>
						<td>
							<p><?php echo sprintf( __( 'You need to install <a href="%s" target="_blank">WooCommerce</a> plugin to start creating and using Job Packages', 'job-listings-package' ), 'https://wordpress.org/plugins/woocommerce/' ); ?></p>
						</td>
					</tr>
				<?php endif; ?>
				<?php do_action( 'jlt_setting_employer_package_fields' ); ?>
				</tbody>
			</table>
			<?php
		}

		public static function is_purchased_free_package( $user_id = '' ) {
			if ( empty( $user_id ) ) {
				return false;
			}

			if ( self::get_setting( 'repurchase_free', '' ) ) {
				return false;
			}

			return (bool) get_user_meta( $user_id, '_free_package_bought', true );
		}

		public static function get_package_interval_text( $interval = '', $unit = 'day' ) {
			if ( $interval === '0' ) {
				return __( 'Unlimited', 'job-listings-package' );
			} elseif ( empty( $interval ) ) {
				return '';
			}

			switch ( $unit ) {
				case 'day':
					return sprintf( _n( '%d Day', '%d Days', $interval, 'job-listings-package' ), $interval );
				case 'week':
					return sprintf( _n( '%d Week', '%d Weeks', $interval, 'job-listings-package' ), $interval );
				case 'month':
					return sprintf( _n( '%d Month', '%d Months', $interval, 'job-listings-package' ), $interval );
				case 'year':
					return sprintf( _n( '%d Year', '%d Years', $interval, 'job-listings-package' ), $interval );
				case '0':
					return __( 'Unlimited', 'job-listings-package' );
			}

			return '';
		}

		public static function reset_employer_package( $user_id = '' ) {
			if ( empty( $user_id ) ) {
				return;
			}

			$package = get_user_meta( $user_id, '_employer_package', true );
			if ( isset( $package[ 'product_id' ] ) ) {
				$product = wc_get_product( $package[ 'product_id' ] );
				if ( $product && $product->get_company_featured() ) {
					// Reset company featured
					$company_id = jlt_get_employer_company( $user_id );
					update_post_meta( $company_id, '_company_featured', 'no' );
				}
			}

			update_user_meta( $user_id, '_employer_package', false );
		}

		public static function set_expired_package_schedule( $user_id = '', $package_data = array() ) {
			if ( empty( $user_id ) ) {
				return;
			}
			if ( empty( $package_data ) ) {
				$package_data = jlt_get_job_posting_info( $user_id );
			}

			wp_clear_scheduled_hook( 'jlt_employer_package_expired', array( $user_id ) );

			if ( isset( $package_data[ 'expired' ] ) && ! empty( $package_data[ 'expired' ] ) ) {
				wp_schedule_single_event( $package_data[ 'expired' ], 'jlt_employer_package_expired', array( $user_id ) );
			}
		}
	}

	new JLT_Employer_Package();
endif;