<?php
/**
 * Tailor Flow Shortcode & Standalone Dashboard Handler Class.
 *
 * Designed and Developed by Hammad Memon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tailor_Flow_Shortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'tailor_flow_app', array( $this, 'render_app_shell' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'template_redirect', array( $this, 'render_standalone_dashboard' ) );
	}

	/**
	 * Enqueue plugin assets and pass AJAX data.
	 */
	public function enqueue_assets() {
		// Enqueue Dashicons if not loaded
		wp_enqueue_style( 'dashicons' );

		// Enqueue Custom CSS
		wp_enqueue_style(
			'tailor-flow-css',
			TAILOR_FLOW_URL . 'assets/css/style.css',
			array(),
			TAILOR_FLOW_VERSION
		);

		// Enqueue Custom JS
		wp_enqueue_script(
			'tailor-flow-js',
			TAILOR_FLOW_URL . 'assets/js/script.js',
			array( 'jquery' ),
			TAILOR_FLOW_VERSION,
			true
		);

		// Localize script for AJAX URL & Nonce
		wp_localize_script(
			'tailor-flow-js',
			'tf_vars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'tf_nonce' ),
			)
		);
	}

	/**
	 * Template Redirect to load Tailor Flow in 100% Fullscreen Standalone App Mode.
	 */
	public function render_standalone_dashboard() {
		$dashboard_id = get_option( 'tf_dashboard_page_id' );

		if ( ( $dashboard_id && is_page( $dashboard_id ) ) || is_page( 'tailor-flow-dashboard' ) ) {
			$this->enqueue_assets();
			?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title>Tailor Flow PK - Management System</title>
				<?php wp_head(); ?>
				<style>
					html, body {
						margin: 0 !important;
						padding: 0 !important;
						width: 100% !important;
						height: 100% !important;
						overflow-x: hidden !important;
						background-color: #0f121d !important;
					}
					#wpadminbar {
						display: none !important;
					}
					html {
						margin-top: 0 !important;
					}
					body.tf-standalone-body {
						background: #0f121d !important;
					}
				</style>
			</head>
			<body <?php body_class( 'tf-standalone-body' ); ?>>
				<?php echo $this->render_app_shell(); ?>
				<?php wp_footer(); ?>
			</body>
			</html>
			<?php
			exit;
		}
	}

	/**
	 * Render the App Shell template for [tailor_flow_app] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output of app-shell.php
	 */
	public function render_app_shell( $atts = array() ) {
		$this->enqueue_assets();

		ob_start();

		$template_file = TAILOR_FLOW_PATH . 'templates/app-shell.php';
		if ( file_exists( $template_file ) ) {
			include $template_file;
		}

		return ob_get_clean();
	}
}

// Initialize shortcode instance.
new Tailor_Flow_Shortcode();
