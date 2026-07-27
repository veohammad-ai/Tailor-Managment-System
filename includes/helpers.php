<?php
/**
 * Helper functions for Tailor Flow PK.
 *
 * Designed and Developed by Hammad Memon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log security and management activities in database table wp_tf_activity_logs.
 *
 * @param int    $user_id        ID of user performing action.
 * @param int    $target_user_id ID of target user.
 * @param string $action_type    Type of action (e.g. role_change, user_created, user_deleted, user_login, user_logout, status_change).
 * @param string $prev_role      Previous state/role description.
 * @param string $new_role       New state/role description.
 * @return bool True on success, false on failure.
 */
function tf_log_activity( $user_id, $target_user_id, $action_type, $prev_role = '', $new_role = '' ) {
	global $wpdb;
	$table_logs = $wpdb->prefix . 'tf_activity_logs';

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_logs ) ) !== $table_logs ) {
		return false;
	}

	$ip_address = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';

	return (bool) $wpdb->insert(
		$table_logs,
		array(
			'user_id'        => (int) $user_id,
			'target_user_id' => (int) $target_user_id,
			'action_type'    => sanitize_text_field( $action_type ),
			'prev_role'      => sanitize_text_field( $prev_role ),
			'new_role'       => sanitize_text_field( $new_role ),
			'ip_address'     => $ip_address,
			'created_at'     => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
	);
}

/**
 * Get plugin setting option with default fallback.
 *
 * @param string $key Setting key name.
 * @param mixed  $default Default value if setting is missing or empty.
 * @return mixed Setting value.
 */
function tf_get_setting( $key, $default = '' ) {
	$defaults = array(
		'company_name'   => 'Tailor Flow PK',
		'logo_url'       => '',
		'phone'          => '+92 300 1234567',
		'address'        => 'Shop #12, Commercial Market, Lahore',
		'receipt_footer' => 'Thank you for choosing Tailor Flow PK!',
		'currency'       => 'PKR',
		'timezone'       => 'Asia/Karachi',
	);

	$fallback = isset( $defaults[ $key ] ) ? $defaults[ $key ] : $default;
	$val      = get_option( 'tf_setting_' . $key, null );

	if ( is_null( $val ) || '' === $val ) {
		return $fallback;
	}

	return $val;
}

/**
 * Update plugin setting option.
 *
 * @param string $key Setting key name.
 * @param mixed  $value Setting value.
 * @return bool True on update success.
 */
function tf_update_setting( $key, $value ) {
	return update_option( 'tf_setting_' . sanitize_key( $key ), $value );
}

/**
 * Get staff user status (active/inactive).
 *
 * @param int $user_id User ID.
 * @return string 'active' or 'inactive'.
 */
function tf_get_user_status( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return 'active';
	}
	$status = get_user_meta( $user_id, 'tf_user_status', true );
	return ( 'inactive' === $status ) ? 'inactive' : 'active';
}

/**
 * Update staff user status in user meta database table.
 *
 * @param int    $user_id User ID.
 * @param string $status 'active' or 'inactive'.
 * @return bool
 */
function tf_update_user_status_meta( $user_id, $status ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return false;
	}
	$status = ( 'inactive' === $status ) ? 'inactive' : 'active';
	update_user_meta( $user_id, 'tf_user_status', $status );
	return true;
}
