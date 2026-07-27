<?php
/**
 * Tailor Flow Database Handler Class.
 *
 * Designed and Developed by Hammad Memon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tailor_Flow_DB {

	/**
	 * Database schema version.
	 */
	const DB_VERSION = '1.1.0';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Database initialization.
	}

	/**
	 * Register activation hook for database table creation.
	 */
	public static function init() {
		$plugin_file = dirname( __FILE__, 2 ) . '/tailor-flow.php';
		register_activation_hook( $plugin_file, array( __CLASS__, 'create_tables' ) );

		// Check if DB tables need update during initialization
		add_action( 'plugins_loaded', array( __CLASS__, 'check_db_update' ) );
	}

	/**
	 * Perform database schema creation/update check on plugins_loaded.
	 */
	public static function check_db_update() {
		if ( get_option( 'tf_db_version' ) !== self::DB_VERSION ) {
			self::create_tables();
		}
	}

	/**
	 * Create or update plugin database tables using dbDelta().
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// 1. Customers Table
		$table_customers = $wpdb->prefix . 'tf_customers';
		$sql_customers   = "CREATE TABLE {$table_customers} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			customer_code varchar(50) DEFAULT '',
			name varchar(191) NOT NULL,
			phone varchar(50) DEFAULT '',
			alt_phone varchar(50) DEFAULT '',
			email varchar(100) DEFAULT '',
			address text DEFAULT NULL,
			city varchar(100) DEFAULT '',
			notes text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY customer_code (customer_code),
			KEY phone (phone)
		) {$charset_collate};";

		// 2. Measurements Table
		$table_measurements = $wpdb->prefix . 'tf_measurements';
		$sql_measurements   = "CREATE TABLE {$table_measurements} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			customer_id bigint(20) NOT NULL,
			garment_type varchar(100) NOT NULL DEFAULT 'kameez_shalwar',
			title varchar(100) DEFAULT '',
			measurements_data longtext DEFAULT NULL,
			special_instructions text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY customer_id (customer_id)
		) {$charset_collate};";

		// 3. Orders Table
		$table_orders = $wpdb->prefix . 'tf_orders';
		$sql_orders   = "CREATE TABLE {$table_orders} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			order_number varchar(50) NOT NULL,
			customer_id bigint(20) NOT NULL,
			measurement_id bigint(20) DEFAULT 0,
			garment_type varchar(100) DEFAULT 'kameez_shalwar',
			quantity int(11) DEFAULT 1,
			booking_date date DEFAULT NULL,
			trial_date date DEFAULT NULL,
			delivery_date date DEFAULT NULL,
			status varchar(50) DEFAULT 'pending',
			priority varchar(20) DEFAULT 'normal',
			total_amount decimal(10,2) DEFAULT '0.00',
			advance_amount decimal(10,2) DEFAULT '0.00',
			discount_amount decimal(10,2) DEFAULT '0.00',
			balance_amount decimal(10,2) DEFAULT '0.00',
			karigar_id bigint(20) DEFAULT 0,
			stitching_wage decimal(10,2) DEFAULT '0.00',
			cloth_details text DEFAULT NULL,
			special_notes text DEFAULT NULL,
			created_by bigint(20) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY order_number (order_number),
			KEY customer_id (customer_id),
			KEY status (status),
			KEY delivery_date (delivery_date),
			KEY trial_date (trial_date),
			KEY karigar_id (karigar_id)
		) {$charset_collate};";

		// 4. Karigar Wages Table
		$table_karigar_wages = $wpdb->prefix . 'tf_karigar_wages';
		$sql_karigar_wages   = "CREATE TABLE {$table_karigar_wages} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			karigar_id bigint(20) NOT NULL,
			order_id bigint(20) DEFAULT 0,
			work_type varchar(100) DEFAULT 'stitching',
			amount decimal(10,2) DEFAULT '0.00',
			payment_status varchar(50) DEFAULT 'unpaid',
			paid_amount decimal(10,2) DEFAULT '0.00',
			paid_date datetime DEFAULT NULL,
			notes text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY karigar_id (karigar_id),
			KEY order_id (order_id),
			KEY payment_status (payment_status)
		) {$charset_collate};";

		// 5. Activity Logs Table
		$table_activity_logs = $wpdb->prefix . 'tf_activity_logs';
		$sql_activity_logs   = "CREATE TABLE {$table_activity_logs} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			target_user_id bigint(20) NOT NULL,
			action_type varchar(50) DEFAULT 'role_change',
			prev_role varchar(50) DEFAULT '',
			new_role varchar(50) DEFAULT '',
			ip_address varchar(100) DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY target_user_id (target_user_id)
		) {$charset_collate};";

		dbDelta( $sql_customers );
		dbDelta( $sql_measurements );
		dbDelta( $sql_orders );
		dbDelta( $sql_karigar_wages );
		dbDelta( $sql_activity_logs );

		update_option( 'tf_db_version', self::DB_VERSION );
	}
}

// Automatically bind the activation hook.
Tailor_Flow_DB::init();
