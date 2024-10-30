<?php
/**
 * Plugin Name: Job Listings Package
 * Plugin URI:        http://nootheme.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           0.1.1
 * Author:            NooTheme
 * Author URI:        http://nootheme.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       job-listings-package
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Job_Listings_Package' ) ):
	class Job_Listings_Package {
		protected static $instance;

		/**
		 * Job_Listings_Package constructor.
		 */
		public function __construct() {

			define( 'JLT_PACKAGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			define( 'JLT_PACKAGE_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
			define( 'JLT_PACKAGE_PLUGIN_TEMPLATE_DIR', JLT_PACKAGE_PLUGIN_DIR . 'templates/' );

			add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
			// Includes
			$this->includes();

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

		public static function init() {
			is_null( self::$instance ) AND self::$instance = new self;

			return self::$instance;
		}

		public function load_plugin_textdomain() {

			$locale = apply_filters( 'plugin_locale', get_locale(), 'job-listings-package' );

			load_textdomain( 'job-listings-package', WP_LANG_DIR . "/job-listings-package/job-listings-package-$locale.mo" );
			load_plugin_textdomain( 'job-listings-package', false, plugin_basename( dirname( __FILE__ ) . "/languages" ) );
		}

		public static function on_activation() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			if ( jlt_check_woocommerce_active() ) {
				if ( ! get_term_by( 'slug', sanitize_title( 'employer_package' ), 'product_type' ) ) {
					wp_insert_term( 'employer_package', 'product_type' );
				}
			}
		}

		public static function on_deactivation() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
		}

		public static function on_uninstall() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			check_admin_referer( 'bulk-plugins' );

			// Important: Check if the file is the one
			// that was registered during the uninstall hook.
			if ( __FILE__ != WP_UNINSTALL_PLUGIN ) {
				return;
			}
		}

		public function includes() {
			require JLT_PACKAGE_PLUGIN_DIR . 'classes/loader.php';
			require JLT_PACKAGE_PLUGIN_DIR . 'includes/loader.php';
		}

		public function enqueue_scripts() {
		}

		public function enqueue_style() {
			wp_enqueue_style( 'jlt-package', plugin_dir_url( __FILE__ ) . 'assets/frontend/css/package.css', array(), '1.0.0', 'all' );
		}

		public function admin_enqueue_scripts() {
		}
	}

	register_activation_hook( __FILE__, array( 'Job_Listings_Package', 'on_activation' ) );
	register_deactivation_hook( __FILE__, array( 'Job_Listings_Package', 'on_deactivation' ) );
	register_uninstall_hook( __FILE__, array( 'Job_Listings_Package', 'on_uninstall' ) );

endif;

function run_job_listings_package() {

	return Job_Listings_Package::init();
}

add_action( 'job_listings_loaded', 'run_job_listings_package' );
