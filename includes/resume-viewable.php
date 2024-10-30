<?php
/**
 * resume-viewable.php
 *
 * @package:
 * @since  : 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function jlt_employer_package_features_view_resume( $product ) {
	if ( jlt_package_is_enabled_view_resume() && $product->can_view_resume == '1' ) :
		$resume_view_limit = $product->resume_view_limit;
		?>
		<?php if ( $product->resume_view_limit == - 1 ) : ?>
		<li class="jlt-li-icon"><i
				class="fa fa-check-circle"></i> <?php _e( 'View Unlimited Resumes', 'job-listings-package' ); ?>
		</li>
	<?php elseif ( $resume_view_limit > 0 ) : ?>
		<li class="jlt-li-icon"><i
				class="fa fa-check-circle"></i> <?php echo sprintf( _n( 'View %d resume', 'View %d resumes', $resume_view_limit, 'job-listings-package' ), $resume_view_limit ); ?>
		</li>
	<?php endif; ?>
	<?php endif;
}

add_action( 'jlt_employer_package_features_list', 'jlt_employer_package_features_view_resume' );

function jlt_manage_plan_features_view_resume( $package ) {
	if ( JLT_Member::is_employer() && jlt_package_is_enabled_view_resume() ) :
		$resume_view_limit = isset( $package[ 'resume_view_limit' ] ) && ! empty( $package[ 'resume_view_limit' ] ) ? intval( $package[ 'resume_view_limit' ] ) : 0;
		$resume_view_remain = jlt_get_resume_view_remain();
		$resume_view_until = jlt_get_resume_view_expire();
		if ( $resume_view_until == - 1 ) {
			$resume_view_until = __( 'Forever', 'job-listings-package' );
		} elseif ( is_numeric( $resume_view_until ) ) {
			$resume_view_until = $resume_view_until > time() ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $resume_view_until ) : '<strong>' . __( 'Expired', 'job-listings-package' ) . '</strong>';
		}
		if ( isset( $package[ 'can_view_resume' ] ) && $package[ 'can_view_resume' ] == '1' ) : ?>
			<?php if ( $resume_view_limit ) : ?>
				<li><strong><?php _e( 'Resume View Limit', 'job-listings-package' ) ?></strong>
				<?php if ( $resume_view_limit == - 1 ) : ?>
					<?php _e( 'Unlimited', 'job-listings-package' ); ?>
				<?php elseif ( $resume_view_limit > 0 ) : ?>
					<?php echo sprintf( _n( '%d resume', '%d resumes', $resume_view_limit, 'job-listings-package' ), $resume_view_limit ); ?>
					<?php if ( $resume_view_remain < $resume_view_limit ) {
						echo '&nbsp;' . sprintf( __( '( %d remain )', 'job-listings-package' ), $resume_view_remain );
					} ?></li>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( $resume_view_until ) : ?>
				<li><strong><?php _e( 'View Resume Until', 'job-listings-package' ) ?></strong>
					<?php echo $resume_view_until; ?></li>
			<?php endif; ?>
		<?php endif;
	endif;
}

add_action( 'jlt_manage_plan_features_list', 'jlt_manage_plan_features_view_resume' );

function jlt_get_resume_view_remain( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( jlt_is_resume_view_expired( $user_id ) ) {
		return 0;
	}

	$package           = jlt_get_job_posting_info( $user_id );
	$resume_view_limit = empty( $package ) || ! is_array( $package ) || ! isset( $package[ 'resume_view_limit' ] ) ? 0 : $package[ 'resume_view_limit' ];
	if ( $resume_view_limit == - 1 ) {
		return - 1;
	}

	$resume_viewed = jlt_get_resume_viewed_count( $user_id );

	return max( absint( $resume_view_limit ) - absint( $resume_viewed ), 0 );
}

function jlt_get_resume_viewed_count( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$resume_viewed = get_user_meta( $user_id, '_resume_view_count', true );

	return empty( $resume_viewed ) ? 0 : absint( $resume_viewed );
}

function jlt_get_resume_view_expire( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$resume_view_expire = get_user_meta( $user_id, '_resume_view_expire', true );

	if ( $resume_view_expire == '-1' ) {
		return - 1;
	}

	return empty( $resume_view_expire ) ? 0 : absint( $resume_view_expire );
}

function jlt_is_resume_view_expired( $user_id = '' ) {
	$resume_view_expire = jlt_get_resume_view_expire( $user_id );

	return $resume_view_expire != - 1 && $resume_view_expire <= time();
}

function jlt_get_viewed_resumes( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$viewed_resumes = get_user_meta( $user_id, '_resumes_saved', true );
	$viewed_resumes = ! is_array( $viewed_resumes ) || empty( $viewed_resumes ) ? array() : $viewed_resumes;

	return $viewed_resumes;
}

function jlt_employer_package_view_resume_user_data( $data, $product ) {
	if ( jlt_package_is_enabled_view_resume() && is_object( $product ) ) {
		$data[ 'can_view_resume' ]   = $product->can_view_resume;
		$data[ 'resume_view_limit' ] = $product->resume_view_limit;
	}

	return $data;
}

add_filter( 'jlt_employer_package_user_data', 'jlt_employer_package_view_resume_user_data', 10, 2 );

function jlt_employer_package_view_resume_order_completed( $product, $user_id ) {
	if ( jlt_package_is_enabled_view_resume() && $product->can_view_resume == '1' ) {
		update_user_meta( $user_id, '_resume_view_count', '0' );
		$package            = get_user_meta( $user_id, '_employer_package', true );
		$resume_view_expire = isset( $package[ 'expired' ] ) ? absint( $package[ 'expired' ] ) : '-1';
		// $resume_view_expire = ( $product->resume_view_duration > 0 ) ? strtotime('+'.absint($product->resume_view_duration).' day') : '-1';
		update_user_meta( $user_id, '_resume_view_expire', $resume_view_expire );
	}
}

add_action( 'jlt_employer_package_order_completed', 'jlt_employer_package_view_resume_order_completed', 10, 2 );

function jlt_package_get_can_view_resume( $user_id = null ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}
	$package = get_user_meta( $user_id, '_employer_package', true );

	$package_id = isset( $package[ 'product_id' ] ) ? $package[ 'product_id' ] : '';
	if ( ! empty( $package_id ) ) {
		return false;
	} else {
		$package_resume_view = isset( $package[ 'can_view_resume' ] ) ? $package[ 'can_view_resume' ] : '';

		if ( empty( $package_resume_view ) ) {

			$package_product     = get_post_meta( $package_id, '', true );
			$package_resume_view = isset( $package_product[ '_can_view_resume' ] ) ? array_shift( $package_product[ '_can_view_resume' ] ) : '';
		}

		return $package_resume_view;
	}
}

function jlt_package_can_view_resumes_list( $can_view_resume ) {
	$can_view_resume_setting = jlt_get_action_control( 'view_resume', 'public' );

	if ( 'package' == $can_view_resume_setting ) {
		$package_can_view = jlt_package_get_can_view_resume();
	} else {
		$package_can_view = $can_view_resume;
	}

	return $package_can_view;
}

add_filter( 'jlt_can_view_resumes_list', 'jlt_package_can_view_resumes_list' );

function jlt_package_view_single_resume( $can_view_resume, $resume_id ) {

	$viewed_resumes = jlt_get_viewed_resumes();
	$user_id        = get_current_user_id();

	if ( jlt_package_get_can_view_resume( $user_id ) ) {
		$can_view_resume = true;

		$viewed_resumes[] = $resume_id;

		$resume_view_count = jlt_get_resume_viewed_count( $user_id );
		update_user_meta( $user_id, '_resume_view_count', $resume_view_count + 1 );
		update_user_meta( $user_id, '_resumes_saved', $viewed_resumes );
	} else {
		if ( ! $can_view_resume ) {
			if ( in_array( $resume_id, $viewed_resumes ) ) {
				$can_view_resume = true;
			}
		} else {
			if ( ! in_array( $resume_id, $viewed_resumes ) ) {
				$viewed_resumes[] = $resume_id;

				$resume_view_count = jlt_get_resume_viewed_count( $user_id );
				update_user_meta( $user_id, '_resume_view_count', $resume_view_count + 1 );
				update_user_meta( $user_id, '_resumes_saved', $viewed_resumes );
			}
		}
	}

	return $can_view_resume;
}

add_filter( 'jlt_can_view_single_resume', 'jlt_package_view_single_resume', 10, 2 );

function jlt_package_resume_not_view_html( $result, $can_view_resume_setting, $resume_id ) {
	if ( 'package' == $can_view_resume_setting ) {

		$title = __( 'Only paid employers can view resumes.', 'job-listings-package' );
		$link  = JLT_Member::get_endpoint_url( 'manage-plan' );

		if ( ! jlt_is_logged_in() ) {
			$link = JLT_Member::get_login_url();
			$link = '<a href="' . esc_url( $link ) . '" class="jlt-btn">' . __( 'Login as Employer', 'job-listings-package' ) . '</a>';
		} elseif ( ! jlt_is_employer() ) {
			$link = JLT_Member::get_logout_url();
			$link = '<a href="' . esc_url( $link ) . '" class="jlt-btn">' . __( 'Logout', 'job-listings-package' ) . '</a>';
		} else {
			$title = __( 'Your membership doesn\'t allow you to view resumes.', 'job-listings-package' );
			$link  = '<a href="' . esc_url( $link ) . '" class="jlt-btn">' . __( 'Upgrade your membership', 'job-listings-package' ) . '</a>';
		}

		return array( 'title' => $title, 'link' => $link );
	} else {
		return $result;
	}
}

add_filter( 'jlt_resume_not_view_html', 'jlt_package_resume_not_view_html', 10, 3 );