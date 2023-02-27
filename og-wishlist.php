<?php
/**
 * OG Wishlist.
 * Plugin Name:       OG Wishlist
 * Plugin URI:        https://onegreen.in
 * Description:       Wishlist functionality for your WooCommerce store.
 * Version:           1.0.0
 * Author:            Pankaj K.
 * Author URI:        https://onegreen.in/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       og-wishlist
 * Domain Path:       /languages
 * @package           OGWishlist
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Define default path.
if ( ! defined( 'OGPKWL_URL' ) ) {
	define( 'OGPKWL_URL', plugins_url( '/', __FILE__ ) );
}
if ( ! defined( 'OGPKWL_PATH' ) ) {
	define( 'OGPKWL_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'OGPKWL_PREFIX' ) ) {
	define( 'OGPKWL_PREFIX', 'ogpkwl' );
}

if ( ! defined( 'OGPKWL_DOMAIN' ) ) {
	define( 'OGPKWL_DOMAIN', 'og-wishlist' );
}

if ( ! defined( 'OGPKWL_FVERSION' ) ) {
	define( 'OGPKWL_FVERSION', '2.2.1' );
}

if ( ! defined( 'OGPKWL_LOAD_FREE' ) ) {
	define( 'OGPKWL_LOAD_FREE', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'OGPKWL_NAME' ) ) {
	define( 'OGPKWL_NAME', 'OG Wishlist' );
}

if ( ! function_exists( 'ogpk_array_merge' ) ) {

	/**
	 * Function to merge arrays with replacement options
	 *
	 * @param array $array1 Array.
	 * @param array $_ Array.
	 *
	 * @return array
	 */
	function ogpk_array_merge( $array1, $_ = null ) {
		if ( ! is_array( $array1 ) ) {
			return $array1;
		}
		$args = func_get_args();
		array_shift( $args );
		foreach ( $args as $array2 ) {
			if ( is_array( $array2 ) ) {
				foreach ( $array2 as $key => $value ) {
					$array1[ $key ] = $value;
				}
			}
		}

		return $array1;
	}
}


if ( ! function_exists( 'ogpk_get_option_defaults' ) ) {

	/**
	 * Extract default options from settings class
	 *
	 * @param string $category Name category settings.
	 *
	 * @return array
	 */
	function ogpk_get_option_defaults( $category ) {
		$dir = OGPKWL_PATH . 'admin/settings/';
		if ( ! file_exists( $dir ) || ! is_dir( $dir ) ) {
			return array();
		}
		$files = scandir( $dir );
		foreach ( $files as $key => $value ) {
			if ( preg_match( '/\.class\.php$/i', $value ) ) {
				$files[ $key ] = preg_replace( '/\.class\.php$/i', '', $value );
			} else {
				unset( $files[ $key ] );
			}
		}
		$defaults = array();
		foreach ( $files as $file ) {
			$class         = 'OGpkWL_Admin_Settings_' . ucfirst( $file );
			$class         = $class::instance( OGPKWL_PREFIX, OGPKWL_FVERSION );
			$class_methods = get_class_methods( $class );

			foreach ( $class_methods as $method ) {
				if ( preg_match( '/_data$/i', $method ) ) {
					$settings = $class->get_defaults( $class->$method() );
					$defaults = ogpk_array_merge( $defaults, $settings );
				}
			}
		}

		if ( 'all' === $category ) {
			return $defaults;
		}
		if ( array_key_exists( $category, $defaults ) ) {
			return $defaults[ $category ];
		}

		return array();
	}
} // End if().

if ( ! function_exists( 'activation_ogpk_wishlist' ) ) {

	/**
	 * Activation plugin
	 */
	function activation_ogpk_wishlist() {
		if ( dependency_ogpk_wishlist( false ) ) {
			OGpkWL_Activator::activate();
			flush_rewrite_rules();
		}
	}
}

if ( ! function_exists( 'deactivation_ogpk_wishlist' ) ) {

	/**
	 * Deactivation plugin
	 */
	function deactivation_ogpk_wishlist() {
		flush_rewrite_rules();
	}
}

if ( ! function_exists( 'uninstall_ogpk_wishlist' ) ) {

	/**
	 * Uninstall plugin
	 */
	function uninstall_ogpk_wishlist() {
		if ( ! defined( 'OGPKWL_LOAD_PREMIUM' ) ) {
			OGpkWL_Activator::uninstall();
			flush_rewrite_rules();
			wp_clear_scheduled_hook( 'ogpkwl_remove_without_author_wishlist' );
		}
	}
}

if ( function_exists( 'spl_autoload_register' ) && ! function_exists( 'autoload_ogpk_wishlist' ) ) {

	/**
	 * Autoloader class. If no function spl_autoload_register, then all the files will be required
	 *
	 * @param string $_class Required class name.
	 *
	 * @return boolean
	 */
	function autoload_ogpk_wishlist( $_class ) {
		$preffix = 'OGpkWL';
		$ext     = '.php';
		$class   = explode( '_', $_class );
		$object  = array_shift( $class );
		if ( $preffix !== $object ) {
			return false;
		}
		if ( empty( $class ) ) {
			$class = array( $preffix );
		}
		$basicclass = $class;
		array_unshift( $class, 'includes' );
		$classes = array(
			OGPKWL_PATH . strtolower( implode( DIRECTORY_SEPARATOR, $basicclass ) ),
			OGPKWL_PATH . strtolower( implode( DIRECTORY_SEPARATOR, $class ) ),
		);

		foreach ( $classes as $class ) {
			foreach ( array( '.class', '.helper' ) as $suffix ) {
				$filename = $class . $suffix . $ext;
				if ( file_exists( $filename ) ) {
					require_once $filename;
				}
			}
		}

		require_once OGPKWL_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

		return false;
	}

	spl_autoload_register( 'autoload_ogpk_wishlist' );
} // End if().

if ( ! function_exists( 'dependency_ogpk_wishlist' ) ) {

	/**
	 * Dependency plugin
	 *
	 * @param boolean $run For run hooks dependency or return error message.
	 *
	 * @return boolean
	 */
	function dependency_ogpk_wishlist( $run = true ) {
		$ext = new OGpkWL_PluginExtend( null, __FILE__, OGPKWL_PREFIX );
		$ext->set_dependency( 'woocommerce/woocommerce.php', 'WooCommerce' )->need();
		if ( $run ) {
			$ext->run();
		}

		return $ext->status_dependency();
	}
}

if ( ! function_exists( 'run_ogpk_wishlist' ) ) {

	/**
	 * Run plugin
	 */
	function run_ogpk_wishlist() {
		global $ogpkwl_integrations;

		$ogpkwl_integrations = array();

		require_once OGPKWL_PATH . 'ogpk-wishlists-function.php';

		foreach ( glob( OGPKWL_PATH . 'integrations' . DIRECTORY_SEPARATOR . '*.php' ) as $file ) {
			require_once $file;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( defined( 'OGPKWL_LOAD_PREMIUM' ) && defined( 'OGPKWL_LOAD_FREE' ) || defined( 'OGPKWL_LOAD_PREMIUM' ) && is_plugin_active_for_network( OGPKWL_LOAD_PREMIUM ) || defined( 'OGPKWL_LOAD_FREE' ) && is_plugin_active_for_network( OGPKWL_LOAD_FREE ) ) {
			$redirect = ogpk_wishlist_status( plugin_basename( __FILE__ ) );
			if ( $redirect ) {
				header( 'Location: ' . $redirect );
				exit;
			}
		} elseif ( dependency_ogpk_wishlist() ) {
			$plugin = new OGpkWL();
			$plugin->run();
		}
	}
}

register_activation_hook( __FILE__, 'activation_ogpk_wishlist' );
register_deactivation_hook( __FILE__, 'deactivation_ogpk_wishlist' );
register_uninstall_hook( __FILE__, 'uninstall_ogpk_wishlist' );
add_action( 'plugins_loaded', 'run_ogpk_wishlist', 20 );
