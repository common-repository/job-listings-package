<?php

function jlt_is_enabled_candidate_package_view_job() {
	return 'package' == jlt_get_action_control( 'view_job', 'public' );
}

function jlt_candidate_package_view_job_data() {
	global $post;
	if ( jlt_is_enabled_candidate_package_view_job() ) {
		woocommerce_wp_checkbox( array(
			'id'          => '_can_view_job',
			'label'       => __( 'Can view Job', 'job-listings-package' ),
			'description' => __( 'Allow buyers to view jobs detail.', 'job-listings-package' ),
			'cbvalue'     => 1,
			'desc_tip'    => false,
		) );

		$disable_field = get_post_meta( $post->ID, '_can_view_job', true ) === '1' ? '' : 'disabled';
		woocommerce_wp_text_input( array(
			'id'                => '_job_view_limit',
			'label'             => __( 'Job view limit', 'job-listings-package' ),
			'description'       => __( 'The maximum number of jobs this package allows candidates to view, input -1 for unlimited.', 'job-listings-package' ),
			'placeholder'       => '',
			'type'              => 'number',
			'value'             => get_post_meta( $post->ID, '_job_view_limit', true ),
			'desc_tip'          => true,
			'custom_attributes' => array( 'min' => '', 'step' => '1', $disable_field => $disable_field ),
		) );
		?>
		<script type="text/javascript">
			jQuery('.pricing').addClass('show_if_candidate_package');
			jQuery(document).ready(function ($) {
				$("#_can_view_job").change(function () {
					if (this.checked) {
						$('#_job_view_limit').prop('disabled', false);
					} else {
						$('#_job_view_limit').prop('disabled', true);
					}
				});
			});
		</script>
		<?php
	}
}

add_action( 'jlt_candidate_package_data', 'jlt_candidate_package_view_job_data' );

function jlt_candidate_package_save_view_job_data( $post_id ) {
	if ( jlt_is_enabled_candidate_package_view_job() ) {
		// Save meta
		$fields = array(
			'_can_view_job'   => '',
			'_job_view_limit' => 'int',
		);
		foreach ( $fields as $key => $value ) {
			$value = ! empty( $_POST[ $key ] ) ? $_POST[ $key ] : '';
			switch ( $value ) {
				case 'int' :
					$value = intval( $value );
					break;
				case 'float' :
					$value = floatval( $value );
					break;
				default :
					$value = sanitize_text_field( $value );
			}
			update_post_meta( $post_id, $key, $value );
		}
	}
}

add_action( 'jlt_candidate_package_save_data', 'jlt_candidate_package_save_view_job_data' );

function jlt_candidate_package_view_job_user_data( $data, $product ) {
	if ( jlt_is_enabled_candidate_package_view_job() && is_object( $product ) ) {
		$data[ 'can_view_job' ]   = $product->can_view_job;
		$data[ 'job_view_limit' ] = $product->job_view_limit;
	}

	return $data;
}

add_filter( 'jlt_candidate_package_user_data', 'jlt_candidate_package_view_job_user_data', 10, 2 );

function jlt_candidate_package_view_job_order_completed( $product, $user_id ) {
	if ( jlt_is_enabled_candidate_package_view_job() && $product->can_view_job == '1' ) {
		update_user_meta( $user_id, '_job_view_count', '0' );
		$package = get_user_meta( $user_id, '_candidate_package', true );
	}
}

add_action( 'jlt_candidate_package_order_completed', 'jlt_candidate_package_view_job_order_completed', 10, 2 );

function jlt_candidate_package_features_view_job( $product ) {
	if ( jlt_is_enabled_candidate_package_view_job() && $product->can_view_job == '1' ) :
		$job_view_limit = $product->job_view_limit;
		?>
		<?php if ( $product->job_view_limit == - 1 ) : ?>
		<li class="jlt-li-icon"><i
				class="fa fa-check-circle"></i> <?php _e( 'View Unlimited Resumes', 'job-listings-package' ); ?>
		</li>
	<?php elseif ( $job_view_limit > 0 ) : ?>
		<li class="jlt-li-icon"><i
				class="fa fa-check-circle"></i> <?php echo sprintf( _n( 'View %d job', 'View %d jobs', $job_view_limit, 'job-listings-package' ), $job_view_limit ); ?>
		</li>
	<?php endif; ?>
	<?php endif;
}

add_action( 'jlt_candidate_package_features_list', 'jlt_candidate_package_features_view_job' );

function jlt_manage_plan_features_view_job( $package ) {
	if ( jlt_is_candidate() && jlt_is_enabled_candidate_package_view_job() ) :
		$job_view_limit = isset( $package[ 'job_view_limit' ] ) && ! empty( $package[ 'job_view_limit' ] ) ? intval( $package[ 'job_view_limit' ] ) : 0;
		$job_view_remain = jlt_get_job_view_remain();

		if ( isset( $package[ 'can_view_job' ] ) && $package[ 'can_view_job' ] == '1' ) : ?>
			<?php if ( $job_view_limit ) : ?>
				<li>
				<strong><?php _e( 'Job View Limit', 'job-listings-package' ) ?></strong>
				<?php if ( $job_view_limit == - 1 ) : ?>
					<?php _e( 'Unlimited', 'job-listings-package' ); ?>
				<?php elseif ( $job_view_limit > 0 ) : ?>
					<?php echo sprintf( _n( '%d job', '%d jobs', $job_view_limit, 'job-listings-package' ), $job_view_limit ); ?>
					<?php if ( $job_view_remain < $job_view_limit ) {
						echo '&nbsp;' . sprintf( __( '( %d remain )', 'job-listings-package' ), $job_view_remain );
					} ?>
					</li>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif;
	endif;
}

add_action( 'jlt_manage_plan_features_list', 'jlt_manage_plan_features_view_job' );

function jlt_get_job_view_remain( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$package        = jlt_get_resume_posting_info( $user_id );
	$job_view_limit = empty( $package ) || ! is_array( $package ) || ! isset( $package[ 'job_view_limit' ] ) ? 0 : $package[ 'job_view_limit' ];
	if ( $job_view_limit == - 1 ) {
		return - 1;
	}

	$job_viewed = jlt_get_job_viewed_count( $user_id );

	return max( absint( $job_view_limit ) - absint( $job_viewed ), 0 );
}

function jlt_get_job_viewed_count( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$job_viewed = get_user_meta( $user_id, '_job_view_count', true );

	return empty( $job_viewed ) ? 0 : absint( $job_viewed );
}

function jlt_get_viewed_jobs( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$viewed_jobs = get_user_meta( $user_id, '_jobs_saved', true );
	$viewed_jobs = ! is_array( $viewed_jobs ) || empty( $viewed_jobs ) ? array() : $viewed_jobs;

	return $viewed_jobs;
}

function jlt_candidate_package_view_job( $can_view_job, $job_id ) {

	if ( jlt_is_enabled_candidate_package_view_job() ) {

		if ( ! jlt_is_candidate() ) {
			$can_view_job = false;
		} else {
			$viewed_jobs = jlt_get_viewed_jobs();

			if ( in_array( $job_id, $viewed_jobs ) ) {
				$can_view_job = true;
			} else {

				$package = jlt_get_resume_posting_info();

				if ( empty( $package ) ) {
					$can_view_job = false;
				} else {
					$can_view_job = ( isset( $package[ 'can_view_job' ] ) && $package[ 'can_view_job' ] === '1' ) && ( jlt_get_job_view_remain() != 0 );

					if ( $can_view_job && ! in_array( $job_id, $viewed_jobs ) ) {
						$viewed_jobs[] = $job_id;
						$user_id       = get_current_user_id();

						$job_view_count = jlt_get_job_viewed_count( $user_id );
						update_user_meta( $user_id, '_job_view_count', $job_view_count + 1 );
						update_user_meta( $user_id, '_jobs_saved', $viewed_jobs );
					}
				}
			}
		}
	}

	return $can_view_job;
}

add_filter( 'jlt_can_view_job', 'jlt_candidate_package_view_job', 10, 2 );

function jlt_package_not_view_job_html( $result, $view_job_setting, $job_id ) {
	if ( 'package' == $view_job_setting ) {

		$title = __( 'Only paid candidate can view job details.', 'job-listings-package' );
		$link  = JLT_Member::get_endpoint_url( 'manage-plan' );

		if ( ! jlt_is_logged_in() ) {
			$link = JLT_Member::get_login_url();
			$link = '<a href="' . esc_url( $link ) . '" class="jlt-btn">' . __( 'Login as Candidate', 'job-listings-package' ) . '</a>';
		} elseif ( ! jlt_is_candidate() ) {
			$link = JLT_Member::get_logout_url();
			$link = '<a href="' . esc_url( $link ) . '" class="jlt-btn">' . __( 'Logout', 'job-listings-package' ) . '</a>';
		} else {
			$title = __( 'Your membership doesn\'t allow you to view job details.', 'job-listings-package' );
			$link  = '<a href="' . esc_url( $link ) . '" class="jlt-btn">' . __( 'Upgrade your membership', 'job-listings-package' ) . '</a>';
		}

		return array( 'title' => $title, 'link' => $link );
	} else {
		return $result;
	}
}

add_filter( 'jlt_job_not_view_html', 'jlt_package_not_view_job_html', 10, 3 );
