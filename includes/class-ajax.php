<?php
/**
 * Tailor Flow AJAX Handler Class.
 *
 * Designed and Developed by Hammad Memon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tailor_Flow_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Customer AJAX endpoints
		add_action( 'wp_ajax_tf_add_customer', array( $this, 'add_customer' ) );
		add_action( 'wp_ajax_nopriv_tf_add_customer', array( $this, 'add_customer' ) );

		add_action( 'wp_ajax_tf_get_customers', array( $this, 'get_customers' ) );
		add_action( 'wp_ajax_nopriv_tf_get_customers', array( $this, 'get_customers' ) );

		// Measurement AJAX endpoints
		add_action( 'wp_ajax_tf_save_measurement', array( $this, 'save_measurement' ) );
		add_action( 'wp_ajax_nopriv_tf_save_measurement', array( $this, 'save_measurement' ) );

		add_action( 'wp_ajax_tf_get_measurement', array( $this, 'get_measurement' ) );
		add_action( 'wp_ajax_nopriv_tf_get_measurement', array( $this, 'get_measurement' ) );

		// Order AJAX endpoints
		add_action( 'wp_ajax_tf_create_order', array( $this, 'create_order' ) );
		add_action( 'wp_ajax_nopriv_tf_create_order', array( $this, 'create_order' ) );

		add_action( 'wp_ajax_tf_get_orders', array( $this, 'get_orders' ) );
		add_action( 'wp_ajax_nopriv_tf_get_orders', array( $this, 'get_orders' ) );

		// Order Stage Update Endpoint
		add_action( 'wp_ajax_tf_update_order_stage', array( $this, 'update_order_stage' ) );
		add_action( 'wp_ajax_nopriv_tf_update_order_stage', array( $this, 'update_order_stage' ) );

		// Reports Data Endpoint
		add_action( 'wp_ajax_tf_get_reports_data', array( $this, 'get_reports_data' ) );
		add_action( 'wp_ajax_nopriv_tf_get_reports_data', array( $this, 'get_reports_data' ) );

		// Role Management AJAX Endpoint (Owner only)
		add_action( 'wp_ajax_tf_update_user_role', array( $this, 'update_user_role' ) );
		add_action( 'wp_ajax_nopriv_tf_update_user_role', array( $this, 'update_user_role' ) );

		// Add User Endpoint (Owner/Admin)
		add_action( 'wp_ajax_tf_create_user', array( $this, 'create_user' ) );
		add_action( 'wp_ajax_nopriv_tf_create_user', array( $this, 'create_user' ) );

		// Global Header Search Endpoint
		add_action( 'wp_ajax_tf_global_search', array( $this, 'global_search' ) );
		add_action( 'wp_ajax_nopriv_tf_global_search', array( $this, 'global_search' ) );

		// Custom App Login Endpoint
		add_action( 'wp_ajax_tf_custom_login', array( $this, 'custom_login' ) );
		add_action( 'wp_ajax_nopriv_tf_custom_login', array( $this, 'custom_login' ) );

		// New Phase Endpoints: Details, Edit, History, Duplicate Check
		add_action( 'wp_ajax_tf_get_order_details', array( $this, 'get_order_details' ) );
		add_action( 'wp_ajax_nopriv_tf_get_order_details', array( $this, 'get_order_details' ) );

		add_action( 'wp_ajax_tf_update_order', array( $this, 'update_order' ) );
		add_action( 'wp_ajax_nopriv_tf_update_order', array( $this, 'update_order' ) );

		add_action( 'wp_ajax_tf_get_customer_history', array( $this, 'get_customer_history' ) );
		add_action( 'wp_ajax_nopriv_tf_get_customer_history', array( $this, 'get_customer_history' ) );

		add_action( 'wp_ajax_tf_check_duplicate_phone', array( $this, 'check_duplicate_phone' ) );
		add_action( 'wp_ajax_nopriv_tf_check_duplicate_phone', array( $this, 'check_duplicate_phone' ) );

		// User Deletion & Status Endpoints
		add_action( 'wp_ajax_tf_delete_user', array( $this, 'delete_user' ) );
		add_action( 'wp_ajax_nopriv_tf_delete_user', array( $this, 'delete_user' ) );

		add_action( 'wp_ajax_tf_update_user_status', array( $this, 'update_user_status' ) );
		add_action( 'wp_ajax_nopriv_tf_update_user_status', array( $this, 'update_user_status' ) );

		// Shop Settings Endpoint
		add_action( 'wp_ajax_tf_save_settings', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_nopriv_tf_save_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Add customer AJAX Handler.
	 */
	public function add_customer() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid nonce token.' ) );
		}

		if ( ! current_user_can( 'tf_manage_customers' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied. Managing customers requires appropriate permissions.' ) );
		}

		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$city  = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';

		if ( empty( $name ) || empty( $phone ) ) {
			wp_send_json_error( array( 'message' => 'Name and Phone number are required fields.' ) );
		}

		global $wpdb;
		$table_customers = $wpdb->prefix . 'tf_customers';

		$existing_cust = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE phone = %s", $phone ), ARRAY_A );
		if ( $existing_cust ) {
			if ( ! empty( $_POST['use_existing'] ) ) {
				wp_send_json_success(
					array(
						'message'     => 'Using existing customer: ' . esc_html( $existing_cust['name'] ),
						'customer'    => $existing_cust,
						'is_existing' => true,
					)
				);
			} else {
				wp_send_json_error(
					array(
						'duplicate' => true,
						'message'   => 'Customer already exists.',
						'customer'  => $existing_cust,
					)
				);
			}
		}

		$count         = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_customers}" );
		$customer_code = 'CUST-' . str_pad( (string) ( $count + 1 ), 4, '0', STR_PAD_LEFT );

		$inserted = $wpdb->insert(
			$table_customers,
			array(
				'customer_code' => $customer_code,
				'name'          => $name,
				'phone'         => $phone,
				'city'          => $city,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			wp_send_json_error( array( 'message' => 'Failed to save customer into database.' ) );
		}

		$customer_id = $wpdb->insert_id;

		wp_send_json_success(
			array(
				'message'  => 'Customer registered successfully!',
				'customer' => array(
					'id'            => $customer_id,
					'customer_code' => $customer_code,
					'name'          => $name,
					'phone'         => $phone,
					'city'          => $city,
				),
			)
		);
	}

	/**
	 * Get Customers list handler.
	 */
	public function get_customers() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'tf_manage_customers' ) && ! current_user_can( 'tf_view_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		global $wpdb;
		$table_customers = $wpdb->prefix . 'tf_customers';
		$customers       = $wpdb->get_results( "SELECT * FROM {$table_customers} ORDER BY name ASC", ARRAY_A );

		wp_send_json_success( array( 'customers' => $customers ) );
	}

	/**
	 * Save Customer Measurement AJAX Handler.
	 */
	public function save_measurement() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid nonce token.' ) );
		}

		if ( ! current_user_can( 'tf_manage_measurements' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied. Managing measurements requires appropriate permissions.' ) );
		}

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		if ( ! $customer_id ) {
			wp_send_json_error( array( 'message' => 'Invalid customer ID provided.' ) );
		}

		$fields = array( 'length', 'chest', 'waist', 'hip', 'shoulder', 'sleeves', 'neck', 'shalwar_length', 'paucha' );
		$meas   = array();
		foreach ( $fields as $f ) {
			if ( isset( $_POST[ $f ] ) ) {
				$meas[ $f ] = sanitize_text_field( wp_unslash( $_POST[ $f ] ) );
			}
		}

		$special_instructions = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';
		$meas_json            = wp_json_encode( $meas );

		global $wpdb;
		$table_measurements = $wpdb->prefix . 'tf_measurements';

		$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_measurements} WHERE customer_id = %d LIMIT 1", $customer_id ) );

		if ( $existing_id ) {
			$updated = $wpdb->update(
				$table_measurements,
				array(
					'measurements_data'    => $meas_json,
					'special_instructions' => $special_instructions,
					'updated_at'           => current_time( 'mysql' ),
				),
				array( 'id' => $existing_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $updated && $wpdb->last_error ) {
				wp_send_json_error( array( 'message' => 'Database error updating measurements.' ) );
			}

			wp_send_json_success(
				array(
					'message'        => 'Measurements updated successfully!',
					'measurement_id' => $existing_id,
				)
			);
		} else {
			$inserted = $wpdb->insert(
				$table_measurements,
				array(
					'customer_id'          => $customer_id,
					'garment_type'         => 'Kameez Shalwar',
					'title'                => 'Standard Profile',
					'measurements_data'    => $meas_json,
					'special_instructions' => $special_instructions,
					'created_at'           => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false === $inserted ) {
				wp_send_json_error( array( 'message' => 'Database error inserting measurements.' ) );
			}

			wp_send_json_success(
				array(
					'message'        => 'Measurements created successfully!',
					'measurement_id' => $wpdb->insert_id,
				)
			);
		}
	}

	/**
	 * Get Measurement AJAX Handler.
	 */
	public function get_measurement() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid nonce token.' ) );
		}

		if ( ! current_user_can( 'tf_manage_measurements' ) && ! current_user_can( 'tf_view_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		if ( ! $customer_id ) {
			wp_send_json_error( array( 'message' => 'Invalid customer ID.' ) );
		}

		global $wpdb;
		$table_measurements = $wpdb->prefix . 'tf_measurements';

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_measurements} WHERE customer_id = %d LIMIT 1", $customer_id ), ARRAY_A );

		if ( $row ) {
			$meas_data = ! empty( $row['measurements_data'] ) ? json_decode( $row['measurements_data'], true ) : array();
			wp_send_json_success(
				array(
					'exists'       => true,
					'measurements' => $meas_data,
					'notes'        => $row['special_instructions'],
				)
			);
		} else {
			wp_send_json_success(
				array(
					'exists'       => false,
					'measurements' => array(),
					'notes'        => '',
				)
			);
		}
	}

	/**
	 * Create Order AJAX Handler.
	 */
	public function create_order() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid nonce token.' ) );
		}

		if ( ! current_user_can( 'tf_manage_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied. Creating orders requires appropriate permissions.' ) );
		}

		$customer_id   = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		$garment_type  = isset( $_POST['garment_type'] ) ? sanitize_text_field( wp_unslash( $_POST['garment_type'] ) ) : 'Kameez Shalwar';
		$quantity      = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;
		$trial_date    = isset( $_POST['trial_date'] ) ? sanitize_text_field( wp_unslash( $_POST['trial_date'] ) ) : null;
		$delivery_date = isset( $_POST['delivery_date'] ) ? sanitize_text_field( wp_unslash( $_POST['delivery_date'] ) ) : null;
		$total_amount  = isset( $_POST['total_amount'] ) ? floatval( $_POST['total_amount'] ) : 0.0;
		$advance_amount = isset( $_POST['advance_amount'] ) ? floatval( $_POST['advance_amount'] ) : 0.0;
		$stage         = isset( $_POST['stage'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['stage'] ) ) ) : 'received';
		$cloth_details = isset( $_POST['cloth_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cloth_details'] ) ) : '';
		$special_notes = isset( $_POST['special_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['special_notes'] ) ) : '';

		if ( ! $customer_id || $total_amount <= 0 ) {
			wp_send_json_error( array( 'message' => 'Customer and valid Total Amount are required.' ) );
		}

		$balance_amount = max( 0.0, $total_amount - $advance_amount );

		global $wpdb;
		$table_customers    = $wpdb->prefix . 'tf_customers';
		$table_orders       = $wpdb->prefix . 'tf_orders';
		$table_measurements = $wpdb->prefix . 'tf_measurements';

		$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $customer_id ), ARRAY_A );
		if ( ! $customer ) {
			wp_send_json_error( array( 'message' => 'Selected customer does not exist.' ) );
		}

		$measurement_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_measurements} WHERE customer_id = %d LIMIT 1", $customer_id ) );

		// Parse and save customer measurements if passed
		$length         = isset( $_POST['length'] ) ? sanitize_text_field( wp_unslash( $_POST['length'] ) ) : '';
		$chest          = isset( $_POST['chest'] ) ? sanitize_text_field( wp_unslash( $_POST['chest'] ) ) : '';
		$waist          = isset( $_POST['waist'] ) ? sanitize_text_field( wp_unslash( $_POST['waist'] ) ) : '';
		$hip            = isset( $_POST['hip'] ) ? sanitize_text_field( wp_unslash( $_POST['hip'] ) ) : '';
		$shoulder       = isset( $_POST['shoulder'] ) ? sanitize_text_field( wp_unslash( $_POST['shoulder'] ) ) : '';
		$sleeves        = isset( $_POST['sleeves'] ) ? sanitize_text_field( wp_unslash( $_POST['sleeves'] ) ) : '';
		$neck           = isset( $_POST['neck'] ) ? sanitize_text_field( wp_unslash( $_POST['neck'] ) ) : '';
		$shalwar_length = isset( $_POST['shalwar_length'] ) ? sanitize_text_field( wp_unslash( $_POST['shalwar_length'] ) ) : '';
		$paucha         = isset( $_POST['paucha'] ) ? sanitize_text_field( wp_unslash( $_POST['paucha'] ) ) : '';

		if ( $length || $chest || $waist || $shoulder || $sleeves ) {
			$m_data = array(
				'length'         => $length,
				'chest'          => $chest,
				'waist'          => $waist,
				'hip'            => $hip,
				'shoulder'       => $shoulder,
				'sleeves'        => $sleeves,
				'neck'           => $neck,
				'shalwar_length' => $shalwar_length,
				'paucha'         => $paucha,
			);
			$encoded = wp_json_encode( $m_data );

			if ( $measurement_id ) {
				$wpdb->update(
					$table_measurements,
					array( 'measurements_data' => $encoded, 'updated_at' => current_time( 'mysql' ) ),
					array( 'id' => $measurement_id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
			} else {
				$wpdb->insert(
					$table_measurements,
					array(
						'customer_id'       => $customer_id,
						'garment_type'      => $garment_type,
						'measurements_data' => $encoded,
						'created_at'        => current_time( 'mysql' ),
						'updated_at'        => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s', '%s' )
				);
				$measurement_id = $wpdb->insert_id;
			}
		}

		$year_prefix  = date( 'Y' );
		$count        = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_orders}" );
		$order_number = 'TF-' . $year_prefix . '-' . str_pad( (string) ( $count + 1 ), 4, '0', STR_PAD_LEFT );
		$booking_date = current_time( 'Y-m-d' );

		$inserted = $wpdb->insert(
			$table_orders,
			array(
				'order_number'   => $order_number,
				'customer_id'    => $customer_id,
				'measurement_id' => $measurement_id,
				'garment_type'   => $garment_type,
				'quantity'       => $quantity,
				'booking_date'   => $booking_date,
				'trial_date'     => $trial_date ? $trial_date : null,
				'delivery_date'  => $delivery_date ? $delivery_date : null,
				'status'         => $stage,
				'total_amount'   => $total_amount,
				'advance_amount' => $advance_amount,
				'balance_amount' => $balance_amount,
				'cloth_details'  => $cloth_details,
				'special_notes'  => $special_notes,
				'created_at'     => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			wp_send_json_error( array( 'message' => 'Database error creating order.' ) );
		}

		$order_id = $wpdb->insert_id;

		if ( 'ready' === $stage ) {
			$order_obj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_orders} WHERE id = %d", $order_id ) );
			do_action( 'tf_order_ready', $order_id, $order_obj );
		}

		$meas_data = array();
		if ( $measurement_id ) {
			$raw_m = $wpdb->get_var( $wpdb->prepare( "SELECT measurements_data FROM {$table_measurements} WHERE id = %d", $measurement_id ) );
			if ( $raw_m ) {
				$meas_data = json_decode( $raw_m, true );
			}
		}

		$invoice = array(
			'order_id'       => $order_id,
			'order_number'   => $order_number,
			'booking_date'   => date( 'd M, Y', strtotime( $booking_date ) ),
			'trial_date'     => $trial_date ? date( 'd M, Y', strtotime( $trial_date ) ) : 'N/A',
			'delivery_date'  => $delivery_date ? date( 'd M, Y', strtotime( $delivery_date ) ) : 'N/A',
			'status'         => ucfirst( $stage ),
			'customer'       => array(
				'name'  => $customer['name'],
				'code'  => $customer['customer_code'],
				'phone' => $customer['phone'],
				'city'  => $customer['city'],
			),
			'garment_type'   => $garment_type,
			'quantity'       => $quantity,
			'total_amount'   => number_format( $total_amount, 2 ),
			'advance_amount' => number_format( $advance_amount, 2 ),
			'balance_amount' => number_format( $balance_amount, 2 ),
			'cloth_details'  => $cloth_details,
			'special_notes'  => $special_notes,
			'measurements'   => $meas_data,
			'developer'      => 'Designed and Developed by Hammad Memon',
		);

		wp_send_json_success(
			array(
				'message'  => 'Order ' . $order_number . ' created successfully!',
				'order_id' => $order_id,
				'stage'    => $stage,
				'invoice'  => $invoice,
			)
		);
	}

	/**
	 * Update Order Stage AJAX Handler.
	 */
	public function update_order_stage() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid nonce token.' ) );
		}

		if ( ! current_user_can( 'tf_update_order_stage' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied. Updating order stage requires appropriate permissions.' ) );
		}

		$order_id  = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$new_stage = isset( $_POST['stage'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['stage'] ) ) ) : '';

		$allowed_stages = array( 'received', 'cutting', 'stitching', 'pressing', 'ready', 'delivered' );
		if ( ! $order_id || ! in_array( $new_stage, $allowed_stages, true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid order ID or stage provided.' ) );
		}

		global $wpdb;
		$table_orders = $wpdb->prefix . 'tf_orders';

		$updated = $wpdb->update(
			$table_orders,
			array(
				'status'     => $new_stage,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $order_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated && $wpdb->last_error ) {
			wp_send_json_error( array( 'message' => 'Database error updating order stage.' ) );
		}

		$order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_orders} WHERE id = %d", $order_id ) );

		if ( 'ready' === $new_stage ) {
			do_action( 'tf_order_ready', $order_id, $order );
		}

		wp_send_json_success(
			array(
				'message'  => 'Order stage updated to ' . ucfirst( $new_stage ) . '!',
				'order_id' => $order_id,
				'stage'    => $new_stage,
			)
		);
	}

	/**
	 * Get Reports Data AJAX Handler.
	 */
	public function get_reports_data() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid nonce token.' ) );
		}

		if ( ! current_user_can( 'tf_view_reports' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied. Financial reports access is restricted.' ) );
		}

		$report_type = isset( $_POST['report_type'] ) ? sanitize_text_field( wp_unslash( $_POST['report_type'] ) ) : 'all_time';
		$month       = isset( $_POST['month'] ) ? sanitize_text_field( wp_unslash( $_POST['month'] ) ) : date( 'Y-m' );
		$year        = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : (int) date( 'Y' );
		$start_date  = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date    = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';

		global $wpdb;
		$table_orders = $wpdb->prefix . 'tf_orders';
		$table_wages  = $wpdb->prefix . 'tf_karigar_wages';

		$order_where = 'WHERE 1=1';
		$wage_where  = 'WHERE 1=1';

		$period_label = 'All Time Summary';

		if ( 'monthly' === $report_type && ! empty( $month ) ) {
			$order_where  = $wpdb->prepare( "WHERE DATE_FORMAT(booking_date, '%Y-%m') = %s OR (booking_date IS NULL AND DATE_FORMAT(created_at, '%Y-%m') = %s)", $month, $month );
			$wage_where   = $wpdb->prepare( "WHERE DATE_FORMAT(created_at, '%Y-%m') = %s", $month );
			$period_label = date( 'F Y', strtotime( $month . '-01' ) ) . ' Report';
		} elseif ( 'yearly' === $report_type && $year > 0 ) {
			$order_where  = $wpdb->prepare( "WHERE YEAR(booking_date) = %d OR (booking_date IS NULL AND YEAR(created_at) = %d)", $year, $year );
			$wage_where   = $wpdb->prepare( "WHERE YEAR(created_at) = %d", $year );
			$period_label = 'Year ' . $year . ' Report';
		} elseif ( 'custom' === $report_type && ! empty( $start_date ) && ! empty( $end_date ) ) {
			$order_where  = $wpdb->prepare( 'WHERE DATE(booking_date) BETWEEN %s AND %s', $start_date, $end_date );
			$wage_where   = $wpdb->prepare( 'WHERE DATE(created_at) BETWEEN %s AND %s', $start_date, $end_date );
			$period_label = date( 'd M Y', strtotime( $start_date ) ) . ' - ' . date( 'd M Y', strtotime( $end_date ) );
		}

		// 1. Single aggregate query for Order Metrics
		$order_stats = $wpdb->get_row( "SELECT COALESCE(SUM(total_amount), 0) AS total_revenue, COALESCE(SUM(advance_amount), 0) AS total_advance, COALESCE(SUM(balance_amount), 0) AS pending_balance FROM {$table_orders} {$order_where}", ARRAY_A );

		// 2. Single aggregate query for Karigar Wage Metrics
		$wage_stats = $wpdb->get_row( "SELECT COALESCE(SUM(paid_amount), 0) AS karigar_paid, COALESCE(SUM(GREATEST(0, amount - paid_amount)), 0) AS karigar_pending FROM {$table_wages} {$wage_where}", ARRAY_A );

		$total_revenue   = (float) ( $order_stats['total_revenue'] ?? 0.0 );
		$total_advance   = (float) ( $order_stats['total_advance'] ?? 0.0 );
		$pending_balance = (float) ( $order_stats['pending_balance'] ?? 0.0 );
		$karigar_paid     = (float) ( $wage_stats['karigar_paid'] ?? 0.0 );
		$karigar_pending  = (float) ( $wage_stats['karigar_pending'] ?? 0.0 );

		wp_send_json_success(
			array(
				'period_label'    => $period_label,
				'revenue'         => number_format( $total_revenue, 2 ),
				'advance'         => number_format( $total_advance, 2 ),
				'pending_balance' => number_format( $pending_balance, 2 ),
				'karigar_paid'    => number_format( $karigar_paid, 2 ),
				'karigar_pending' => number_format( $karigar_pending, 2 ),
			)
		);
	}

	/**
	 * Get Orders list handler.
	 */
	public function get_orders() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'tf_view_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		global $wpdb;
		$table_orders    = $wpdb->prefix . 'tf_orders';
		$table_customers = $wpdb->prefix . 'tf_customers';

		$sql    = "SELECT o.*, c.name as customer_name, c.phone as customer_phone 
		        FROM {$table_orders} o 
		        LEFT JOIN {$table_customers} c ON o.customer_id = c.id 
		        ORDER BY o.id DESC";
		$orders = $wpdb->get_results( $sql, ARRAY_A );

		wp_send_json_success( array( 'orders' => $orders ) );
	}

	/**
	 * Update User Role AJAX Handler (Owner capability required).
	 */
	public function update_user_role() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid nonce token.' ) );
		}

		if ( ! current_user_can( 'tf_manage_roles' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied. Only Owner can manage user roles.' ) );
		}

		$user_id  = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$new_role = isset( $_POST['role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['role_name'] ) ) : '';

		$allowed_roles = array( 'tf_owner', 'tf_manager', 'tf_receptionist', 'tf_karigar', 'tf_cashier', 'remove' );
		if ( ! $user_id || ! in_array( $new_role, $allowed_roles, true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid User ID or Role specified.' ) );
		}

		$current_user_id = get_current_user_id();

		// Security Check 1: User cannot change their own role
		if ( $current_user_id === $user_id ) {
			wp_send_json_error( array( 'message' => 'Security Violation: A logged-in user cannot modify their own role.' ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => 'Specified user does not exist.' ) );
		}

		$target_roles = (array) $user->roles;

		// Security Check 2: Prevent changing or removing the last remaining Tailor Flow Owner
		if ( in_array( 'tf_owner', $target_roles, true ) && 'tf_owner' !== $new_role ) {
			$owners = get_users( array( 'role' => 'tf_owner', 'fields' => 'ID' ) );
			if ( count( $owners ) <= 1 ) {
				wp_send_json_error( array( 'message' => 'Security Error: Cannot change or remove the last remaining Tailor Flow Owner.' ) );
			}
		}

		$prev_role_label = ! empty( $target_roles ) ? implode( ', ', array_map( 'ucfirst', $target_roles ) ) : 'None';
		$roles_map       = array(
			'tf_owner'        => 'Owner',
			'tf_manager'      => 'Manager',
			'tf_receptionist' => 'Receptionist',
			'tf_karigar'      => 'Karigar',
			'tf_cashier'      => 'Cashier',
			'remove'          => 'Role Removed',
		);
		$new_role_label  = $roles_map[ $new_role ] ?? $new_role;

		// TAILOR FLOW SECONDARY ROLE REWRITE SYSTEM
		// 1. Strip only previous Tailor Flow roles using remove_role()
		$tf_roles = array( 'tf_owner', 'tf_manager', 'tf_receptionist', 'tf_karigar', 'tf_cashier' );
		foreach ( $tf_roles as $r ) {
			$user->remove_role( $r );
		}

		// 2. Add new Tailor Flow role as a secondary application role using add_role()
		// Never call set_role(), preserving all core WordPress roles (administrator, editor, subscriber) forever.
		if ( 'remove' !== $new_role ) {
			$user->add_role( $new_role );
		}

		// Log Role Change Activity into wp_tf_activity_logs
		global $wpdb;
		$table_activity = $wpdb->prefix . 'tf_activity_logs';
		$ip_address     = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';

		$wpdb->insert(
			$table_activity,
			array(
				'user_id'        => $current_user_id,
				'target_user_id' => $user_id,
				'action_type'    => 'role_change',
				'prev_role'      => $prev_role_label,
				'new_role'       => $new_role_label,
				'ip_address'     => $ip_address,
				'created_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		wp_send_json_success( array( 'message' => 'Assigned ' . esc_html( $new_role_label ) . ' role to ' . esc_html( $user->display_name ) . ' successfully.' ) );
	}

	/**
	 * Create WP User & assign Tailor Flow Role.
	 */
	public function create_user() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid nonce token.' ) );
		}

		if ( ! current_user_can( 'tf_manage_roles' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied. Only Owner can add new staff users.' ) );
		}

		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : '';
		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$role     = isset( $_POST['role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['role_name'] ) ) : 'tf_receptionist';

		if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => 'Username, Email, and Password are required fields.' ) );
		}

		if ( username_exists( $username ) ) {
			wp_send_json_error( array( 'message' => 'Username already exists. Please choose a different username.' ) );
		}

		if ( email_exists( $email ) ) {
			wp_send_json_error( array( 'message' => 'Email address is already registered in WordPress.' ) );
		}

		$user_id = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
		}

		if ( ! empty( $name ) ) {
			wp_update_user( array( 'ID' => $user_id, 'display_name' => $name, 'first_name' => $name ) );
		}

		if ( ! empty( $phone ) ) {
			update_user_meta( $user_id, 'phone', $phone );
		}

		$allowed_roles = array( 'tf_owner', 'tf_manager', 'tf_receptionist', 'tf_karigar', 'tf_cashier' );
		if ( in_array( $role, $allowed_roles, true ) ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$user->add_role( $role );
			}
		}

		// Log activity
		global $wpdb;
		$table_activity  = $wpdb->prefix . 'tf_activity_logs';
		$current_user_id = get_current_user_id();
		$ip_address      = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';

		$wpdb->insert(
			$table_activity,
			array(
				'user_id'        => $current_user_id,
				'target_user_id' => $user_id,
				'action_type'    => 'user_created',
				'prev_role'      => 'New User Created',
				'new_role'       => ucfirst( str_replace( 'tf_', '', $role ) ),
				'ip_address'     => $ip_address,
				'created_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		wp_send_json_success( array( 'message' => 'Staff user @' . $username . ' created successfully!' ) );
	}

	/**
	 * Global Search AJAX Handler.
	 */
	public function global_search() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		$query = isset( $_POST['query'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['query'] ) ) ) : '';
		if ( strlen( $query ) < 2 ) {
			wp_send_json_success( array( 'results' => array() ) );
		}

		global $wpdb;
		$results = array();

		// Search Staff Users (Name, Username, Phone Meta)
		$users = get_users( array( 'number' => 20 ) );
		foreach ( $users as $u ) {
			$mobile = get_user_meta( $u->ID, 'phone', true ) ?: ( get_user_meta( $u->ID, 'billing_phone', true ) ?: '-' );
			if (
				false !== stripos( $u->display_name, $query ) ||
				false !== stripos( $u->user_login, $query ) ||
				( '-' !== $mobile && false !== stripos( $mobile, $query ) )
			) {
				$roles = (array) $u->roles;
				$results[] = array(
					'type'     => 'Staff User',
					'title'    => $u->display_name . ' (@' . $u->user_login . ')',
					'subtitle' => 'Phone: ' . $mobile . ' | Role: ' . implode( ', ', array_map( 'ucfirst', $roles ) ),
					'tab'      => 'user-roles',
				);
			}
		}

		// Search Customers
		$table_cust = $wpdb->prefix . 'tf_customers';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_cust ) ) === $table_cust ) {
			$like  = '%' . $wpdb->esc_like( $query ) . '%';
			$custs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_cust} WHERE name LIKE %s OR phone LIKE %s OR customer_code LIKE %s LIMIT 5", $like, $like, $like ), ARRAY_A );
			foreach ( $custs as $c ) {
				$results[] = array(
					'type'        => 'Customer',
					'title'       => $c['name'] . ' (' . $c['customer_code'] . ')',
					'subtitle'    => 'Phone: ' . $c['phone'] . ' | City: ' . ( $c['city'] ? $c['city'] : 'N/A' ),
					'tab'         => 'customers',
					'customer_id' => $c['id'],
				);
			}
		}

		// Search Orders
		$table_ord = $wpdb->prefix . 'tf_orders';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_ord ) ) === $table_ord ) {
			$like = '%' . $wpdb->esc_like( $query ) . '%';
			$ords = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_ord} WHERE order_number LIKE %s OR garment_type LIKE %s LIMIT 5", $like, $like ), ARRAY_A );
			foreach ( $ords as $o ) {
				$results[] = array(
					'type'     => 'Order',
					'title'    => $o['order_number'] . ' - ' . $o['garment_type'],
					'subtitle' => 'Status: ' . ucfirst( $o['status'] ) . ' | Total: PKR ' . number_format( $o['total_amount'], 2 ),
					'tab'      => 'overview',
					'order_id' => $o['id'],
				);
			}
		}

		// Search Measurements
		$table_meas = $wpdb->prefix . 'tf_measurements';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_meas ) ) === $table_meas ) {
			$like  = '%' . $wpdb->esc_like( $query ) . '%';
			$sql_m = "SELECT m.*, c.name as customer_name, c.customer_code 
					  FROM {$table_meas} m 
					  LEFT JOIN {$table_cust} c ON m.customer_id = c.id 
					  WHERE m.garment_type LIKE %s OR m.title LIKE %s OR m.special_instructions LIKE %s OR c.name LIKE %s 
					  LIMIT 5";
			$meass = $wpdb->get_results( $wpdb->prepare( $sql_m, $like, $like, $like, $like ), ARRAY_A );
			foreach ( $meass as $m ) {
				$cust_name = $m['customer_name'] ? $m['customer_name'] : 'Customer #' . $m['customer_id'];
				$results[] = array(
					'type'        => 'Measurement',
					'title'       => 'Measurement: ' . $cust_name . ' (' . ucfirst( str_replace( '_', ' ', $m['garment_type'] ) ) . ')',
					'subtitle'    => 'Instructions: ' . ( $m['special_instructions'] ? wp_trim_words( $m['special_instructions'], 6 ) : 'Standard' ),
					'tab'         => 'customers',
					'customer_id' => $m['customer_id'],
					'customer_name' => $cust_name,
				);
			}
		}

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Custom Tailor Flow Application Login AJAX Handler.
	 */
	public function custom_login() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid token.' ) );
		}

		$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : '';
		$remember = isset( $_POST['remember'] ) ? (bool) $_POST['remember'] : true;

		if ( empty( $username ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => 'Please enter both Username and Password.' ) );
		}

		$creds = array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => $remember,
		);

		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( array( 'message' => 'Invalid Username or Password. Please try again.' ) );
		}

		// Record last login timestamp
		update_user_meta( $user->ID, 'last_login', current_time( 'timestamp', true ) );

		$redirect_url = get_permalink( get_option( 'tf_dashboard_page_id' ) );
		if ( ! $redirect_url ) {
			$redirect_url = home_url( '/tailor-flow-dashboard/' );
		}

		wp_send_json_success( array(
			'message'  => 'Authentication successful! Loading Tailor Flow PK...',
			'redirect' => $redirect_url,
		) );
	}

	/**
	 * Get Order Details AJAX Handler (For View Order Modal).
	 */
	public function get_order_details() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => 'Invalid Order ID.' ) );
		}

		global $wpdb;
		$table_orders       = $wpdb->prefix . 'tf_orders';
		$table_customers    = $wpdb->prefix . 'tf_customers';
		$table_measurements = $wpdb->prefix . 'tf_measurements';

		$order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_orders} WHERE id = %d", $order_id ), ARRAY_A );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => 'Order not found.' ) );
		}

		$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $order['customer_id'] ), ARRAY_A );
		if ( ! $customer ) {
			$customer = array( 'name' => 'Unknown Customer', 'phone' => '-', 'city' => '-' );
		}

		$measurement = array();
		if ( ! empty( $order['measurement_id'] ) ) {
			$m_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_measurements} WHERE id = %d", $order['measurement_id'] ), ARRAY_A );
		} else {
			$m_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_measurements} WHERE customer_id = %d ORDER BY id DESC LIMIT 1", $order['customer_id'] ), ARRAY_A );
		}

		if ( ! empty( $m_row['measurements_data'] ) ) {
			$measurement = json_decode( $m_row['measurements_data'], true );
		}

		wp_send_json_success(
			array(
				'order'       => $order,
				'customer'    => $customer,
				'measurement' => $measurement,
			)
		);
	}

	/**
	 * Update Order AJAX Handler (For Order Edit Modal).
	 */
	public function update_order() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'tf_manage_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => 'Invalid Order ID.' ) );
		}

		global $wpdb;
		$table_orders       = $wpdb->prefix . 'tf_orders';
		$table_measurements = $wpdb->prefix . 'tf_measurements';

		$existing_order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_orders} WHERE id = %d", $order_id ), ARRAY_A );
		if ( ! $existing_order ) {
			wp_send_json_error( array( 'message' => 'Order not found.' ) );
		}

		$garment_type   = isset( $_POST['garment_type'] ) ? sanitize_text_field( wp_unslash( $_POST['garment_type'] ) ) : $existing_order['garment_type'];
		$quantity       = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : $existing_order['quantity'];
		$trial_date     = ! empty( $_POST['trial_date'] ) ? sanitize_text_field( wp_unslash( $_POST['trial_date'] ) ) : $existing_order['trial_date'];
		$delivery_date  = ! empty( $_POST['delivery_date'] ) ? sanitize_text_field( wp_unslash( $_POST['delivery_date'] ) ) : $existing_order['delivery_date'];
		$total_amount   = isset( $_POST['total_amount'] ) ? floatval( $_POST['total_amount'] ) : floatval( $existing_order['total_amount'] );
		$advance_amount = isset( $_POST['advance_amount'] ) ? floatval( $_POST['advance_amount'] ) : floatval( $existing_order['advance_amount'] );
		$balance_amount = max( 0, $total_amount - $advance_amount );
		$new_stage      = isset( $_POST['stage'] ) ? sanitize_text_field( wp_unslash( $_POST['stage'] ) ) : $existing_order['status'];
		$cloth_details  = isset( $_POST['cloth_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cloth_details'] ) ) : $existing_order['cloth_details'];
		$special_notes  = isset( $_POST['special_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['special_notes'] ) ) : $existing_order['special_notes'];

		$updated = $wpdb->update(
			$table_orders,
			array(
				'garment_type'   => $garment_type,
				'quantity'       => $quantity,
				'trial_date'     => $trial_date,
				'delivery_date'  => $delivery_date,
				'total_amount'   => $total_amount,
				'advance_amount' => $advance_amount,
				'balance_amount' => $balance_amount,
				'status'         => $new_stage,
				'cloth_details'  => $cloth_details,
				'special_notes'  => $special_notes,
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $order_id ),
			array( '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		// Save updated measurements if passed
		$length         = isset( $_POST['length'] ) ? sanitize_text_field( wp_unslash( $_POST['length'] ) ) : '';
		$chest          = isset( $_POST['chest'] ) ? sanitize_text_field( wp_unslash( $_POST['chest'] ) ) : '';
		$waist          = isset( $_POST['waist'] ) ? sanitize_text_field( wp_unslash( $_POST['waist'] ) ) : '';
		$hip            = isset( $_POST['hip'] ) ? sanitize_text_field( wp_unslash( $_POST['hip'] ) ) : '';
		$shoulder       = isset( $_POST['shoulder'] ) ? sanitize_text_field( wp_unslash( $_POST['shoulder'] ) ) : '';
		$sleeves        = isset( $_POST['sleeves'] ) ? sanitize_text_field( wp_unslash( $_POST['sleeves'] ) ) : '';
		$neck           = isset( $_POST['neck'] ) ? sanitize_text_field( wp_unslash( $_POST['neck'] ) ) : '';
		$shalwar_length = isset( $_POST['shalwar_length'] ) ? sanitize_text_field( wp_unslash( $_POST['shalwar_length'] ) ) : '';
		$paucha         = isset( $_POST['paucha'] ) ? sanitize_text_field( wp_unslash( $_POST['paucha'] ) ) : '';

		if ( $length || $chest || $waist || $shoulder || $sleeves ) {
			$m_data = array(
				'length'         => $length,
				'chest'          => $chest,
				'waist'          => $waist,
				'hip'            => $hip,
				'shoulder'       => $shoulder,
				'sleeves'        => $sleeves,
				'neck'           => $neck,
				'shalwar_length' => $shalwar_length,
				'paucha'         => $paucha,
			);
			$encoded = json_encode( $m_data );

			$customer_id = $existing_order['customer_id'];
			$m_exists    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_measurements} WHERE customer_id = %d", $customer_id ) );

			if ( $m_exists ) {
				$wpdb->update(
					$table_measurements,
					array( 'measurements_data' => $encoded, 'updated_at' => current_time( 'mysql' ) ),
					array( 'id' => $m_exists ),
					array( '%s', '%s' ),
					array( '%d' )
				);
			} else {
				$wpdb->insert(
					$table_measurements,
					array(
						'customer_id'       => $customer_id,
						'garment_type'      => $garment_type,
						'measurements_data' => $encoded,
						'created_at'        => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s' )
				);
			}
		}

		// Trigger hook if stage changed to ready
		if ( 'ready' === $new_stage && 'ready' !== $existing_order['status'] ) {
			do_action( 'tf_order_ready', $order_id, $existing_order );
		}

		wp_send_json_success( array( 'message' => 'Order ' . esc_html( $existing_order['order_number'] ) . ' updated successfully!' ) );
	}

	/**
	 * Get Customer History AJAX Handler.
	 */
	public function get_customer_history() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		if ( ! $customer_id ) {
			wp_send_json_error( array( 'message' => 'Invalid Customer ID.' ) );
		}

		global $wpdb;
		$table_customers = $wpdb->prefix . 'tf_customers';
		$table_orders    = $wpdb->prefix . 'tf_orders';

		$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $customer_id ), ARRAY_A );
		if ( ! $customer ) {
			wp_send_json_error( array( 'message' => 'Customer not found.' ) );
		}

		$orders = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_orders} WHERE customer_id = %d ORDER BY id DESC", $customer_id ), ARRAY_A );

		wp_send_json_success(
			array(
				'customer' => $customer,
				'orders'   => $orders,
			)
		);
	}

	/**
	 * Check Duplicate Phone AJAX Handler.
	 */
	public function check_duplicate_phone() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		if ( empty( $phone ) ) {
			wp_send_json_success( array( 'exists' => false ) );
		}

		global $wpdb;
		$table_customers = $wpdb->prefix . 'tf_customers';
		$existing        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE phone = %s", $phone ), ARRAY_A );

		if ( $existing ) {
			wp_send_json_success(
				array(
					'exists'   => true,
					'message'  => 'Customer with phone ' . esc_html( $phone ) . ' already exists.',
					'customer' => $existing,
				)
			);
		} else {
			wp_send_json_success( array( 'exists' => false ) );
		}
	}

	/**
	 * Delete User AJAX Handler (Owner / Admin only).
	 */
	public function delete_user() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid nonce.' ) );
		}

		if ( ! current_user_can( 'tf_manage_roles' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied. Only Owner can delete staff users.' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => 'Invalid User ID specified.' ) );
		}

		$current_user_id = get_current_user_id();
		if ( $current_user_id === $user_id ) {
			wp_send_json_error( array( 'message' => 'Security Violation: You cannot delete your own logged-in account.' ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => 'Specified user does not exist.' ) );
		}

		$target_roles = (array) $user->roles;
		if ( in_array( 'administrator', $target_roles, true ) ) {
			wp_send_json_error( array( 'message' => 'Security Protection: Core WordPress Administrator accounts cannot be deleted from Tailor Flow.' ) );
		}

		if ( in_array( 'tf_owner', $target_roles, true ) ) {
			$owners = get_users( array( 'role' => 'tf_owner', 'fields' => 'ID' ) );
			if ( count( $owners ) <= 1 ) {
				wp_send_json_error( array( 'message' => 'Security Error: Cannot delete the last remaining Tailor Flow Owner.' ) );
			}
		}

		$user_display_name = $user->display_name;
		$user_role_label   = ! empty( $target_roles ) ? implode( ', ', array_map( 'ucfirst', $target_roles ) ) : 'Staff';

		require_once ABSPATH . 'wp-admin/includes/user.php';
		$deleted = wp_delete_user( $user_id, $current_user_id );

		if ( ! $deleted ) {
			wp_send_json_error( array( 'message' => 'Failed to delete user from WordPress.' ) );
		}

		if ( function_exists( 'tf_log_activity' ) ) {
			tf_log_activity( $current_user_id, $user_id, 'user_deleted', $user_role_label, 'Account Deleted' );
		}

		wp_send_json_success( array( 'message' => 'Staff user ' . esc_html( $user_display_name ) . ' deleted successfully!' ) );
	}

	/**
	 * Update User Status (Active / Inactive) AJAX Handler.
	 */
	public function update_user_status() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Invalid nonce token.' ) );
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Authentication required. Please log in.' ) );
		}

		$current_user = wp_get_current_user();
		$is_allowed   = current_user_can( 'tf_manage_roles' ) || current_user_can( 'tf_manage_all' ) || current_user_can( 'manage_options' ) || in_array( 'administrator', (array) $current_user->roles, true ) || in_array( 'tf_owner', (array) $current_user->roles, true );

		if ( ! $is_allowed ) {
			wp_send_json_error( array( 'message' => 'Permission denied. Only Owner or Administrator can update staff status.' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$status  = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active';
		$status  = ( 'inactive' === $status ) ? 'inactive' : 'active';

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => 'Invalid User ID.' ) );
		}

		if ( $current_user->ID === $user_id && 'inactive' === $status ) {
			wp_send_json_error( array( 'message' => 'Security Warning: You cannot set your own account to Inactive.' ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => 'Specified user does not exist.' ) );
		}

		$prev_status = function_exists( 'tf_get_user_status' ) ? tf_get_user_status( $user_id ) : 'active';

		if ( function_exists( 'tf_update_user_status_meta' ) ) {
			tf_update_user_status_meta( $user_id, $status );
		} else {
			update_user_meta( $user_id, 'tf_user_status', $status );
		}

		if ( function_exists( 'tf_log_activity' ) ) {
			tf_log_activity( $current_user->ID, $user_id, 'status_change', ucfirst( $prev_status ), ucfirst( $status ) );
		}

		wp_send_json_success(
			array(
				'message' => 'Status for ' . esc_html( $user->display_name ) . ' updated to ' . ucfirst( $status ) . '!',
				'user_id' => $user_id,
				'status'  => $status,
			)
		);
	}

	/**
	 * Save Shop Settings AJAX Handler.
	 */
	public function save_settings() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tf_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'tf_manage_settings' ) && ! current_user_can( 'tf_manage_roles' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied. Only Owner/Admin can modify shop settings.' ) );
		}

		$fields = array(
			'company_name'   => 'sanitize_text_field',
			'logo_url'       => 'esc_url_raw',
			'phone'          => 'sanitize_text_field',
			'address'        => 'sanitize_text_field',
			'receipt_footer' => 'sanitize_text_field',
			'currency'       => 'sanitize_text_field',
			'timezone'       => 'sanitize_text_field',
		);

		foreach ( $fields as $key => $sanitizer ) {
			if ( isset( $_POST[ $key ] ) ) {
				$raw_val = wp_unslash( $_POST[ $key ] );
				$val     = call_user_func( $sanitizer, $raw_val );
				if ( function_exists( 'tf_update_setting' ) ) {
					tf_update_setting( $key, $val );
				} else {
					update_option( 'tf_setting_' . $key, $val );
				}
			}
		}

		wp_send_json_success( array( 'message' => 'Tailor Flow shop settings updated successfully!' ) );
	}
}

// Instantiate AJAX handler.
new Tailor_Flow_Ajax();
