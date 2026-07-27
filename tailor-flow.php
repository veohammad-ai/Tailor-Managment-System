<?php
/**
 * Plugin Name: Tailor Flow PK
 * Plugin URI: https://example.com/tailor-flow
 * Description: A professional custom tailoring management system plugin for WordPress.
 * Version: 1.1.0
 * Author: Hammad Memon
 * Text Domain: tailor-flow
 *
 * Designed and Developed by Hammad Memon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'TAILOR_FLOW_VERSION', '1.1.0' );
define( 'TAILOR_FLOW_PATH', plugin_dir_path( __FILE__ ) );
define( 'TAILOR_FLOW_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Tailor Flow PK Plugin Class
 */
final class Tailor_Flow {

	/**
	 * Single instance of the class.
	 *
	 * @var Tailor_Flow
	 */
	private static $instance = null;

	/**
	 * Main Instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once TAILOR_FLOW_PATH . 'includes/helpers.php';
		require_once TAILOR_FLOW_PATH . 'includes/class-installer.php';
		require_once TAILOR_FLOW_PATH . 'includes/class-db.php';
		require_once TAILOR_FLOW_PATH . 'includes/class-admin.php';
		require_once TAILOR_FLOW_PATH . 'includes/class-ajax.php';
		require_once TAILOR_FLOW_PATH . 'includes/class-shortcode.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Register activation procedure
		register_activation_hook( __FILE__, array( 'Tailor_Flow_Installer', 'install' ) );
	}
}

/**
 * Returns the main instance of Tailor_Flow.
 */
function tailor_flow() {
	return Tailor_Flow::instance();
}

// Kick off the plugin.
tailor_flow();
