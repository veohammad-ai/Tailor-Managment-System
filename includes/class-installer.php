<?php
/**
 * Tailor Flow Installer Class.
 *
 * Designed and Developed by Hammad Memon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tailor_Flow_Installer {

	/**
	 * Register activation hook & one-time recovery listener.
	 */
	public static function init() {
		$plugin_file = dirname( __FILE__, 2 ) . '/tailor-flow.php';
		register_activation_hook( $plugin_file, array( __CLASS__, 'install' ) );

		// Record last login timestamp for staff
		add_action( 'wp_login', array( __CLASS__, 'record_user_last_login' ), 10, 2 );

		// Record logout activity for audit log
		add_action( 'wp_logout', array( __CLASS__, 'record_user_logout' ) );

		// Ensure role capabilities are dynamically in sync (e.g. Receptionist measurements access)
		add_action( 'init', array( __CLASS__, 'sync_role_capabilities' ) );

		// One-time check for User ID = 1 administrator recovery
		if ( ! get_option( 'tf_admin_recovery_completed' ) ) {
			add_action( 'init', array( __CLASS__, 'one_time_admin_recovery' ) );
		}
	}

	/**
	 * Record last login timestamp and audit log for users.
	 */
	public static function record_user_last_login( $user_login, $user ) {
		if ( $user && isset( $user->ID ) ) {
			update_user_meta( $user->ID, 'last_login', current_time( 'timestamp', true ) );

			if ( function_exists( 'tf_log_activity' ) ) {
				$roles      = (array) $user->roles;
				$role_label = ! empty( $roles ) ? implode( ', ', array_map( 'ucfirst', $roles ) ) : 'Staff';
				tf_log_activity( $user->ID, $user->ID, 'user_login', '-', 'Logged In (' . $role_label . ')' );
			}
		}
	}

	/**
	 * Record logout activity for audit log.
	 */
	public static function record_user_logout( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user && function_exists( 'tf_log_activity' ) ) {
				$roles      = (array) $user->roles;
				$role_label = ! empty( $roles ) ? implode( ', ', array_map( 'ucfirst', $roles ) ) : 'Staff';
				tf_log_activity( $user_id, $user_id, 'user_logout', 'Logged In (' . $role_label . ')', 'Logged Out' );
			}
		}
	}

	/**
	 * Run installation procedures on plugin activation.
	 */
	public static function install() {
		// Run DB creation if DB class exists
		if ( class_exists( 'Tailor_Flow_DB' ) ) {
			Tailor_Flow_DB::create_tables();
		}

		// Register Custom Roles and Capabilities
		self::create_roles();

		// One-time Admin Recovery
		self::one_time_admin_recovery();

		// Create Dashboard Page
		self::create_dashboard_page();
	}

	/**
	 * One-Time Self-Disabling Recovery Function for User ID = 1 Administrator.
	 */
	public static function one_time_admin_recovery() {
		if ( get_option( 'tf_admin_recovery_completed' ) ) {
			return;
		}

		$admin_user = get_userdata( 1 );
		if ( $admin_user ) {
			$roles = (array) $admin_user->roles;
			if ( ! in_array( 'administrator', $roles, true ) ) {
				$admin_user->add_role( 'administrator' );
			}
		}

		// Mark as completed so this function self-disables and never executes again
		update_option( 'tf_admin_recovery_completed', true );
	}

	/**
	 * Register Custom WP Roles and Capabilities for Tailor Flow.
	 */
	public static function create_roles() {
		// 1. Owner - Full Access
		add_role(
			'tf_owner',
			'Tailor Flow Owner',
			array(
				'read'                   => true,
				'tf_manage_all'          => true,
				'tf_manage_customers'    => true,
				'tf_manage_measurements' => true,
				'tf_manage_orders'       => true,
				'tf_manage_karigar'      => true,
				'tf_view_reports'        => true,
				'tf_manage_roles'        => true,
				'tf_manage_settings'     => true,
				'tf_update_order_stage'  => true,
				'tf_view_orders'         => true,
			)
		);

		// 2. Manager - Customers, Measurements, Orders, Reports, Karigar
		add_role(
			'tf_manager',
			'Tailor Flow Manager',
			array(
				'read'                   => true,
				'tf_manage_customers'    => true,
				'tf_manage_measurements' => true,
				'tf_manage_orders'       => true,
				'tf_manage_karigar'      => true,
				'tf_view_reports'        => true,
				'tf_update_order_stage'  => true,
				'tf_view_orders'         => true,
			)
		);

		// 3. Receptionist - Customers, Measurements, New Orders
		add_role(
			'tf_receptionist',
			'Tailor Flow Receptionist',
			array(
				'read'                   => true,
				'tf_manage_customers'    => true,
				'tf_manage_measurements' => true,
				'tf_manage_orders'       => true,
				'tf_update_order_stage'  => true,
				'tf_view_orders'         => true,
			)
		);

		// 4. Karigar - View Assigned Orders, Update Order Stage, No Financial Access
		add_role(
			'tf_karigar',
			'Tailor Flow Karigar',
			array(
				'read'                  => true,
				'tf_view_orders'        => true,
				'tf_update_order_stage' => true,
			)
		);

		// 5. Cashier - View Orders, Receive Payments, Print Receipts, View Financial Reports
		add_role(
			'tf_cashier',
			'Tailor Flow Cashier',
			array(
				'read'                   => true,
				'tf_view_orders'         => true,
				'tf_manage_orders'       => true,
				'tf_view_reports'        => true,
				'tf_update_order_stage'  => true,
			)
		);

		// Grant all capabilities to Administrator
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$caps = array(
				'tf_manage_all',
				'tf_manage_customers',
				'tf_manage_measurements',
				'tf_manage_orders',
				'tf_manage_karigar',
				'tf_view_reports',
				'tf_manage_roles',
				'tf_manage_settings',
				'tf_update_order_stage',
				'tf_view_orders',
			);
			foreach ( $caps as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * Create Tailor Flow Dashboard page and store page ID in option.
	 */
	private static function create_dashboard_page() {
		$page_id = get_option( 'tf_dashboard_page_id' );

		// Check if saved page ID exists and is valid (not in trash)
		if ( $page_id && get_post_status( $page_id ) && get_post_status( $page_id ) !== 'trash' ) {
			return;
		}

		// Check by slug in case option was cleared but page exists
		$existing_page = get_page_by_path( 'tailor-flow-dashboard' );
		if ( $existing_page ) {
			update_option( 'tf_dashboard_page_id', $existing_page->ID );
			return;
		}

		// Create new page
		$page_data = array(
			'post_title'     => 'Tailor Flow Dashboard',
			'post_name'      => 'tailor-flow-dashboard',
			'post_content'   => '[tailor_flow_app]',
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);

		$new_page_id = wp_insert_post( $page_data );

		if ( $new_page_id && ! is_wp_error( $new_page_id ) ) {
			update_option( 'tf_dashboard_page_id', $new_page_id );
		}
	}

	/**
	 * Dynamically synchronize capabilities for Receptionist.
	 */
	public static function sync_role_capabilities() {
		$rec = get_role( 'tf_receptionist' );
		if ( $rec ) {
			$caps = array(
				'tf_manage_customers',
				'tf_manage_measurements',
				'tf_manage_orders',
				'tf_view_orders',
				'tf_update_order_stage',
			);
			foreach ( $caps as $cap ) {
				if ( ! $rec->has_cap( $cap ) ) {
					$rec->add_cap( $cap );
				}
			}
		}
	}
}

// Automatically bind activation hook when class file is loaded.
Tailor_Flow_Installer::init();
