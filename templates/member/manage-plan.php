<?php
/**
 * Manage Plan Page.
 *
 * This template can be overridden by copying it to yourtheme/job-listings/member/manage-plan.php.
 *
 * HOWEVER, on occasion NooTheme will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author      NooTheme
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<?php do_action( 'jlt_member_manage_plan_before' ); ?>

<?php
$package = jlt_package_info();
?>
<div class="member-plan">
	<?php if ( empty( $package ) ) : ?>
		<p><?php _e( 'You have no active packages', 'job-listings-package' ) ?></p>
	<?php else : ?>
		<ul class="jlt-list-plan">
			<?php if ( isset( $package[ 'product_id' ] ) && ! empty( $package[ 'product_id' ] ) ) : ?>

				<li><?php echo esc_html( get_the_title( absint( $package[ 'product_id' ] ) ) ) ?></li>

			<?php endif; ?>
			<?php if ( jlt_is_employer() ) : ?>
				<?php
				$is_unlimited       = $package[ 'job_limit' ] >= 99999999;
				$job_limit_text     = $is_unlimited ? __( 'Unlimited', 'job-listings-package' ) : sprintf( _n( '%d job', '%d jobs', $package[ 'job_limit' ], 'job-listings-package' ), number_format_i18n( $package[ 'job_limit' ] ) );
				$job_added          = jlt_get_job_posting_added();
				$feature_job_remain = jlt_get_feature_job_remain();
				?>

				<?php if ( $is_unlimited || $package[ 'job_limit' ] > 0 ) : ?>
					<li><strong><?php _e( 'Job Limit', 'job-listings-package' ) ?></strong><?php echo $job_limit_text; ?></li>
					<li>
						<strong><?php _e( 'Job Added', 'job-listings-package' ) ?></strong><?php echo $job_added > 0 ? sprintf( _n( '%d job', '%d jobs', $job_added, 'job-listings-package' ), number_format_i18n( $job_added ) ) : __( '0 job', 'job-listings-package' ); ?>
					</li>
					<li>
						<strong><?php _e( 'Job Duration', 'job-listings-package' ) ?></strong><?php echo sprintf( _n( '%s day', '%s days', $package[ 'job_duration' ], 'job-listings-package' ), number_format_i18n( $package[ 'job_duration' ] ) ); ?>
					</li>
				<?php endif; ?>
				<?php if ( isset( $package[ 'job_featured' ] ) && ! empty( $package[ 'job_featured' ] ) ) : ?>
					<li>
						<strong><?php _e( 'Featured Job limit', 'job-listings-package' ) ?></strong><?php echo sprintf( _n( '%d job', '%d jobs', $package[ 'job_featured' ], 'job-listings-package' ), number_format_i18n( $package[ 'job_featured' ] ) ); ?>
						<?php if ( $feature_job_remain < $package[ 'job_featured' ] ) {
							echo '&nbsp;' . sprintf( __( '( %d remain )', 'job-listings-package' ), $feature_job_remain );
						} ?>
					</li>
				<?php endif; ?>
				<?php if ( isset( $package[ 'company_featured' ] ) && $package[ 'company_featured' ] ) : ?>
					<li><strong><?php _e( 'Featured Company', 'job-listings-package' ) ?></strong><?php _e( 'Yes', 'job-listings-package' ); ?></li>
				<?php endif; ?>
			<?php else : ?>
				<?php
				$is_unlimited      = $package[ 'resume_limit' ] >= 99999999;
				$resume_limit_text = $is_unlimited ? __( 'Unlimited', 'job-listings-package' ) : sprintf( _n( '%d resume', '%d resumes', $package[ 'resume_limit' ], 'job-listings-package' ), number_format_i18n( $package[ 'resume_limit' ] ) );
				$resume_added      = jlt_get_resume_posting_added();
				?>
				<?php if ( $is_unlimited || $package[ 'resume_limit' ] > 0 ) : ?>
					<li><strong><?php _e( 'Resume Limit', 'job-listings-package' ) ?></strong><?php echo $resume_limit_text; ?></li>
					<li>
						<strong><?php _e( 'Resume Added', 'job-listings-package' ) ?></strong><?php echo $resume_added > 0 ? sprintf( _n( '%s resume', '%s resumes', $resume_added, 'job-listings-package' ), $resume_added ) : __( '0 resume', 'job-listings-package' ); ?>
					</li>
				<?php endif; ?>
			<?php endif; ?>

			<?php do_action( 'jlt_manage_plan_features_list', $package ); ?>

			<?php if ( isset( $package[ 'created' ] ) && ! empty( $package[ 'created' ] ) ): ?>
				<li>
					<strong><?php _e( 'Date Activated', 'job-listings-package' ) ?></strong><?php echo mysql2date( get_option( 'date_format' ), $package[ 'created' ] ) ?>
				</li>
			<?php elseif ( isset( $package[ 'counter_reset' ] ) && ! empty( $package[ 'counter_reset' ] ) ) : ?>
				<li><?php echo sprintf( _n( 'Your counter will be reset every %d month', 'Your counter will be reset every %d months', $package[ 'counter_reset' ], 'job-listings-package' ), absint( $package[ 'counter_reset' ] ) ); ?></li>
			<?php endif; ?>
			<?php if ( isset( $package[ 'expired' ] ) && ! empty( $package[ 'expired' ] ) ): ?>
				<li>
					<strong><?php _e( 'Expired On', 'job-listings-package' ) ?></strong><?php echo date_i18n( get_option( 'date_format' ), $package[ 'expired' ] ); ?>
				</li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>
	<a class="jlt-btn btn-package-page"
	   href="<?php echo jlt_package_page_url(); ?>"><?php _e( 'Choose or upgrade a Package', 'job-listings-package' ) ?></a>
</div>