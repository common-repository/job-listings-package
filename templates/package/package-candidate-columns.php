<?php
/**
 * Display list candidate package
 *
 * This template can be overridden by copying it to yourtheme/job-listings/package/package-candidate-list.php.
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

global $jlt_view_candidate_package;
$jlt_view_candidate_package = true;

$product_args = array(
	'post_type'        => 'product',
	'posts_per_page'   => - 1,
	'suppress_filters' => false,
	'tax_query'        => array(
		array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => array( 'candidate_package' ),
		),
	),
	'orderby'          => 'menu_order title',
	'order'            => 'ASC',
	'suppress_filters' => false,
);

$packages                   = new WP_Query( $product_args );
$jlt_view_candidate_package = false;

?>
<?php do_action( 'jlt_before_candidate_package' ); ?>
<?php if ( $packages->have_posts() ) : ?>
	<ul class="package-list package-list-grid package-list-grid-<?php echo esc_attr( $columns ); ?>">
		<?php while ( $packages->have_posts() ) : $packages->the_post(); ?>
			<?php

			global $product;
			$checkout_url = JLT_Member::get_checkout_url( $product->get_id() );

			?>
			<li class="package-product-item">
				<div class="package-item">
					<div class="package-meta">
						<div class="package-title"><?php echo esc_html( $product->get_title() ) ?></div>
						<div class="package-price">
							<?php echo $product->get_price_html(); ?>
							<span class="package-interval">
								<?php echo $product->get_package_interval(); ?>
								<?php echo $product->get_package_interval_unit();
								_e( '(s)', 'job-listings' ); ?>
							</span>
						</div>
					</div>
					<div class="package-info">
						<?php the_excerpt(); ?>
						<?php do_action( 'jlt_candidate_package_item', $product ); ?>
					</div>
					<a class="package-btn"
					   href="<?php echo esc_url( $checkout_url ); ?>"><?php _e( 'Select', 'job-listings-package' ); ?></a>
				</div>
			</li>
		<?php endwhile; ?>
	</ul>
	<?php wp_reset_postdata();
endif;
?>
<?php do_action( 'jlt_after_candidate_package' ); ?>
