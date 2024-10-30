<?php
/**
 * Display list employer package
 *
 * This template can be overridden by copying it to yourtheme/job-listings/package/package-employer-list.php.
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

global $jlt_view_employer_package;
$jlt_view_employer_package = true;

$product_args = array(
	'post_type'        => 'product',
	'posts_per_page'   => - 1,
	'suppress_filters' => false,
	'tax_query'        => array(
		array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => array( 'employer_package' ),
		),
	),
	'orderby'          => 'menu_order title',
	'order'            => 'ASC',
	'suppress_filters' => false,
);

$packages                 = new WP_Query( $product_args );
$jlt_view_employer_package = false;

?>
<?php do_action( 'jlt_before_employer_package' ); ?>
<?php if ( $packages->have_posts() ) : ?>
	<ul class="package-list package-list-row">
		<?php while ( $packages->have_posts() ) : $packages->the_post(); ?>
			<?php

			global $product;

			$checkout_url = JLT_Member::get_checkout_url( $product->get_id() );

			?>
			<li <?php post_class(); ?>>
				<div class="package-item">
					<div class="package-meta">
						<div class="jlt-col-40">
							<span class="package-title"><?php echo esc_html( $product->get_title() ) ?></span>
						</div>
						<div class="jlt-col-30">
							<span class="package-price"><?php echo $product->get_price_html(); ?></span>
						</div>
						<div class="jlt-col-30 package-btn">
							<a class="jlt-btn"
							   href="<?php echo esc_url( $checkout_url ); ?>"><?php _e( 'Select', 'job-listings-package' ); ?></a>
						</div>
					</div>
					<div class="package-info">
						<?php the_excerpt(); ?>
						<?php do_action( 'jlt_employer_package_item', $product ); ?>
					</div>
				</div>
			</li>
		<?php endwhile; ?>
	</ul>
	<?php wp_reset_postdata();
endif;
?>
<?php do_action( 'jlt_after_employer_package' ); ?>
