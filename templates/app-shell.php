<?php
/**
 * Tailor Flow App Shell Template with Role & Permission Restrictions.
 *
 * Designed and Developed by Hammad Memon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_customers = $wpdb->prefix . 'tf_customers';
$table_orders    = $wpdb->prefix . 'tf_orders';
$table_wages     = $wpdb->prefix . 'tf_karigar_wages';

$current_user        = wp_get_current_user();
$can_manage_roles    = current_user_can( 'tf_manage_roles' ) || current_user_can( 'manage_options' );
$can_manage_settings = current_user_can( 'tf_manage_settings' ) || current_user_can( 'tf_manage_roles' ) || current_user_can( 'manage_options' );
$can_view_reports    = current_user_can( 'tf_view_reports' ) || current_user_can( 'manage_options' );
$can_manage_orders   = current_user_can( 'tf_manage_orders' ) || current_user_can( 'manage_options' );
$can_manage_cust     = current_user_can( 'tf_manage_customers' ) || current_user_can( 'manage_options' );
$can_manage_karigar  = current_user_can( 'tf_manage_karigar' ) || current_user_can( 'manage_options' );
$can_update_stage    = current_user_can( 'tf_update_order_stage' ) || current_user_can( 'manage_options' );
$can_manage_measurements = current_user_can( 'tf_manage_measurements' ) || current_user_can( 'manage_options' );

// User Role Display Label
$user_role_label = 'Staff';
if ( in_array( 'tf_owner', (array) $current_user->roles, true ) || current_user_can( 'manage_options' ) ) {
	$user_role_label = 'Owner';
} elseif ( in_array( 'tf_manager', (array) $current_user->roles, true ) ) {
	$user_role_label = 'Manager';
} elseif ( in_array( 'tf_receptionist', (array) $current_user->roles, true ) ) {
	$user_role_label = 'Receptionist';
} elseif ( in_array( 'tf_karigar', (array) $current_user->roles, true ) ) {
	$user_role_label = 'Karigar';
} elseif ( in_array( 'tf_cashier', (array) $current_user->roles, true ) ) {
	$user_role_label = 'Cashier';
}

$customers_list = array();
if ( $can_manage_cust && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_customers ) ) === $table_customers ) {
	$customers_list = $wpdb->get_results( "SELECT * FROM {$table_customers} ORDER BY name ASC", ARRAY_A );
}

$orders_list = array();
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_orders ) ) === $table_orders ) {
	$sql         = "SELECT o.*, c.name as customer_name, c.phone as customer_phone 
	        FROM {$table_orders} o 
	        LEFT JOIN {$table_customers} c ON o.customer_id = c.id 
	        ORDER BY o.id DESC";
	$orders_list = $wpdb->get_results( $sql, ARRAY_A );
}

// Initial Reports & Metrics Calculations
$today_str          = current_time( 'Y-m-d' );
$deliv_today_count  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_orders} WHERE delivery_date = %s AND status != 'delivered'", $today_str ) );
$trial_today_count  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_orders} WHERE trial_date = %s", $today_str ) );
$ready_count        = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_orders} WHERE status = 'ready'" );
$pending_pay_sum    = (float) $wpdb->get_var( "SELECT SUM(balance_amount) FROM {$table_orders} WHERE balance_amount > 0" );
$pending_suits      = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_orders} WHERE status NOT IN ('delivered', 'ready')" );

$init_revenue       = (float) $wpdb->get_var( "SELECT SUM(total_amount) FROM {$table_orders}" );
$init_advance       = (float) $wpdb->get_var( "SELECT SUM(advance_amount) FROM {$table_orders}" );
$init_pending       = (float) $wpdb->get_var( "SELECT SUM(balance_amount) FROM {$table_orders}" );

$table_wages_name   = $wpdb->prefix . 'tf_karigar_wages';
$init_k_paid        = 0.0;
$init_k_pend        = 0.0;
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_wages_name ) ) === $table_wages_name ) {
	$init_k_paid    = (float) $wpdb->get_var( "SELECT SUM(paid_amount) FROM {$table_wages_name}" );
	$init_k_pend    = (float) $wpdb->get_var( "SELECT SUM(GREATEST(0, amount - paid_amount)) FROM {$table_wages_name}" );
}

$stages_map = array(
	'received'  => 'Received',
	'cutting'   => 'Cutting',
	'stitching' => 'Stitching',
	'pressing'  => 'Pressing',
	'ready'     => 'Ready',
	'delivered' => 'Delivered',
);
?>

<?php if ( ! is_user_logged_in() ) : ?>
	<div id="tailor-flow-app" class="tf-app tf-app-container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #0f121d; padding: 20px;">
		<div class="tf-glass-card" style="width: 100%; max-width: 440px; padding: 36px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.08); background: rgba(31, 36, 51, 0.85); backdrop-filter: blur(16px); box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
			<div style="text-align: center; margin-bottom: 28px;">
				<div style="width: 64px; height: 64px; background: linear-gradient(135deg, #6366f1, #3b82f6); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);">
					<span class="dashicons dashicons-welcome-write-blog" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
				</div>
				<h2 style="color: #ffffff; margin: 0 0 6px 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;"><?php echo esc_html( strtoupper( function_exists( 'tf_get_setting' ) ? tf_get_setting( 'company_name' ) : 'TAILOR FLOW PK' ) ); ?></h2>
				<p style="color: #9ca3af; margin: 0; font-size: 13px;">Professional Custom Tailoring Management System</p>
			</div>

			<form id="tf-app-login-form">
				<div id="tf-login-notice" class="tf-notice" style="display: none; margin-bottom: 16px;"></div>

				<div class="tf-form-group" style="margin-bottom: 18px;">
					<label for="tf-app-login-username" style="color: #cbd5e1; font-weight: 500; font-size: 13px; margin-bottom: 6px; display: block;">Username or Email Address</label>
					<input type="text" id="tf-app-login-username" class="tf-input" placeholder="Enter your username or email" required style="width: 100%; height: 44px; padding: 0 14px; background: #1f2433; color: #ffffff; border: 1px solid #40465a; border-radius: 8px;">
				</div>

				<div class="tf-form-group" style="margin-bottom: 20px;">
					<label for="tf-app-login-password" style="color: #cbd5e1; font-weight: 500; font-size: 13px; margin-bottom: 6px; display: block;">Password</label>
					<input type="password" id="tf-app-login-password" class="tf-input" placeholder="Enter your password" required style="width: 100%; height: 44px; padding: 0 14px; background: #1f2433; color: #ffffff; border: 1px solid #40465a; border-radius: 8px;">
				</div>

				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
					<label style="color: #9ca3af; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
						<input type="checkbox" id="tf-app-login-remember" checked style="accent-color: #6366f1;"> Keep me logged in
					</label>
				</div>

				<button type="submit" id="tf-app-login-btn" class="tf-btn tf-btn-accent" style="width: 100%; height: 46px; border-radius: 8px; font-size: 15px; font-weight: 600; background: linear-gradient(135deg, #6366f1, #3b82f6); border: none; color: #ffffff; cursor: pointer; transition: all 0.2s ease;">
					<span class="dashicons dashicons-lock" style="font-size: 18px; width: 18px; height: 18px; vertical-align: middle; margin-right: 6px;"></span> Sign In to Tailor Flow
				</button>
			</form>

			<div style="text-align: center; margin-top: 28px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.06);">
				<small style="color: #64748b; font-size: 11px;">Designed and Developed by <strong style="color: #94a3b8;">Hammad Memon</strong></small>
			</div>
		</div>
	</div>
<?php else : ?>
<div id="tailor-flow-app" class="tf-app tf-app-container">
	<!-- Mobile Top Bar -->
	<header class="tf-mobile-header">
		<button id="tf-mobile-toggle" class="tf-btn-icon" aria-label="Toggle Navigation">
			<span class="dashicons dashicons-menu-alt3"></span>
		</button>
		<div class="tf-mobile-brand">
			<span class="dashicons dashicons-forms tf-brand-icon"></span>
			<span class="tf-brand-name"><?php echo esc_html( function_exists( 'tf_get_setting' ) ? tf_get_setting( 'company_name' ) : 'Tailor Flow PK' ); ?></span>
		</div>
	</header>

	<!-- Sidebar Navigation -->
	<aside id="tf-sidebar" class="tf-sidebar">
		<div class="tf-sidebar-header">
			<div class="tf-brand">
				<div class="tf-brand-logo">
					<span class="dashicons dashicons-forms"></span>
				</div>
				<div class="tf-brand-text">
					<?php
					$shop_name = function_exists( 'tf_get_setting' ) ? tf_get_setting( 'company_name' ) : 'Tailor Flow PK';
					$parts     = explode( ' ', $shop_name, 2 );
					$first_part = $parts[0];
					$rest_part  = isset( $parts[1] ) ? $parts[1] : '';
					?>
					<h2><?php echo esc_html( $first_part ); ?> <?php if ( ! empty( $rest_part ) ) : ?><span><?php echo esc_html( $rest_part ); ?></span><?php endif; ?></h2>
					<p><?php echo esc_html( $user_role_label ); ?> Portal</p>
				</div>
			</div>
			<button id="tf-sidebar-close" class="tf-btn-icon tf-mobile-only" aria-label="Close Sidebar">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<nav class="tf-sidebar-nav">
			<ul>
				<li class="tf-nav-item active" data-tab="overview">
					<a href="#overview">
						<span class="dashicons dashicons-dashboard"></span>
						<span class="tf-nav-label">Overview</span>
					</a>
				</li>

				<?php if ( $can_manage_orders ) : ?>
					<li class="tf-nav-item" data-tab="new-order">
						<a href="#new-order">
							<span class="dashicons dashicons-plus-alt2"></span>
							<span class="tf-nav-label">New Order</span>
						</a>
					</li>
				<?php endif; ?>

				<?php if ( $can_manage_cust ) : ?>
					<li class="tf-nav-item" data-tab="customers">
						<a href="#customers">
							<span class="dashicons dashicons-admin-users"></span>
							<span class="tf-nav-label">Customers</span>
						</a>
					</li>
				<?php endif; ?>

				<?php if ( $can_manage_karigar ) : ?>
					<li class="tf-nav-item" data-tab="karigar-ledger">
						<a href="#karigar-ledger">
							<span class="dashicons dashicons-groups"></span>
							<span class="tf-nav-label">Karigar Ledger</span>
						</a>
					</li>
				<?php endif; ?>

				<?php if ( $can_view_reports ) : ?>
					<li class="tf-nav-item" data-tab="reports">
						<a href="#reports">
							<span class="dashicons dashicons-chart-bar"></span>
							<span class="tf-nav-label">Reports</span>
						</a>
					</li>
				<?php endif; ?>

				<?php if ( $can_manage_roles ) : ?>
					<li class="tf-nav-item" data-tab="user-roles">
						<a href="#user-roles">
							<span class="dashicons dashicons-shield"></span>
							<span class="tf-nav-label">User Roles</span>
						</a>
					</li>
				<?php endif; ?>

				<?php if ( $can_manage_settings ) : ?>
					<li class="tf-nav-item" data-tab="settings">
						<a href="#settings">
							<span class="dashicons dashicons-admin-settings"></span>
							<span class="tf-nav-label">Settings</span>
						</a>
					</li>
				<?php endif; ?>

				<?php if ( is_user_logged_in() ) : ?>
					<li class="tf-nav-item tf-nav-logout" style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 10px;">
						<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>" style="color: #f87171;">
							<span class="dashicons dashicons-exit" style="color: #f87171;"></span>
							<span class="tf-nav-label">Logout</span>
						</a>
					</li>
				<?php else : ?>
					<li class="tf-nav-item tf-nav-login" style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 10px;">
						<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" style="color: #34d399;">
							<span class="dashicons dashicons-unlock" style="color: #34d399;"></span>
							<span class="tf-nav-label">Login</span>
						</a>
					</li>
				<?php endif; ?>
			</ul>
		</nav>

		<div class="tf-sidebar-footer">
			<div class="tf-developer-credit">
				<span class="dashicons dashicons-heart"></span>
				<p>Designed and Developed by <strong>Hammad Memon</strong></p>
			</div>
		</div>
	</aside>

	<!-- Main Content Area -->
	<main class="tf-main-content">
		<!-- Top Bar Header -->
		<header class="tf-header">
			<div class="tf-header-search" style="position: relative;">
				<span class="dashicons dashicons-search"></span>
				<input type="text" id="tf-global-search-input" placeholder="Search by Name, Username, Mobile..." autocomplete="off">
				<div id="tf-global-search-dropdown" class="tf-glass-card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; margin-top: 8px; max-height: 350px; overflow-y: auto; padding: 12px; border: 1px solid var(--tf-border-color); background: #151824;"></div>
			</div>
			<div class="tf-header-actions">
				<?php if ( $can_manage_orders ) : ?>
					<button class="tf-btn tf-btn-accent" data-switch-tab="new-order">
						<span class="dashicons dashicons-plus"></span>
						<span>New Order</span>
					</button>
				<?php endif; ?>
				<div class="tf-user-badge">
					<span class="tf-avatar"><?php echo esc_html( strtoupper( substr( $current_user->display_name, 0, 2 ) ) ); ?></span>
					<span class="tf-user-name"><?php echo esc_html( $current_user->display_name ); ?> (<?php echo esc_html( $user_role_label ); ?>)</span>
				</div>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>" class="tf-btn-sm" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-weight: 500; font-size: 13px;">
						<span class="dashicons dashicons-exit" style="font-size: 16px; width: 16px; height: 16px;"></span>
						<span>Logout</span>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="tf-btn-sm" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-weight: 500; font-size: 13px;">
						<span class="dashicons dashicons-unlock" style="font-size: 16px; width: 16px; height: 16px;"></span>
						<span>Login</span>
					</a>
				<?php endif; ?>
			</div>
		</header>

		<!-- Content Views -->
		<div class="tf-content-body">

			<!-- TAB 1: OVERVIEW -->
			<section id="tf-tab-overview" class="tf-tab-content active">
				<div class="tf-page-header">
					<div>
						<h1>Dashboard Overview</h1>
						<p>Real-time tailoring shop status — Logged in as <strong><?php echo esc_html( $user_role_label ); ?></strong></p>
					</div>
					<div class="tf-date-badge">
						<span class="dashicons dashicons-calendar-alt"></span>
						<span><?php echo esc_html( date( 'd M, Y' ) ); ?></span>
					</div>
				</div>

				<!-- Dashboard Alert Cards (Module 5) -->
				<div class="tf-stats-grid" style="grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px;">
					<div class="tf-glass-card tf-stat-card" style="border-left: 4px solid #ef4444; min-height: 100px; padding: 18px; margin-bottom: 0;">
						<div class="tf-stat-icon" style="background: rgba(239, 68, 68, 0.2); color: #f87171;">
							<span class="dashicons dashicons-calendar-alt"></span>
						</div>
						<div class="tf-stat-details">
							<span class="tf-stat-label">Delivery Today</span>
							<h3 class="tf-stat-value" style="color: #f87171; font-size: 1.35rem;"><?php echo esc_html( $deliv_today_count ); ?></h3>
							<span class="tf-stat-trend warning">Scheduled Deliveries</span>
						</div>
					</div>

					<div class="tf-glass-card tf-stat-card" style="border-left: 4px solid #f59e0b; min-height: 100px; padding: 18px; margin-bottom: 0;">
						<div class="tf-stat-icon" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24;">
							<span class="dashicons dashicons-id"></span>
						</div>
						<div class="tf-stat-details">
							<span class="tf-stat-label">Trial Today</span>
							<h3 class="tf-stat-value" style="color: #fbbf24; font-size: 1.35rem;"><?php echo esc_html( $trial_today_count ); ?></h3>
							<span class="tf-stat-trend warning">Fitting Trials Today</span>
						</div>
					</div>

					<div class="tf-glass-card tf-stat-card" style="border-left: 4px solid #10b981; min-height: 100px; padding: 18px; margin-bottom: 0;">
						<div class="tf-stat-icon" style="background: rgba(16, 185, 129, 0.2); color: #34d399;">
							<span class="dashicons dashicons-yes-alt"></span>
						</div>
						<div class="tf-stat-details">
							<span class="tf-stat-label">Ready for Delivery</span>
							<h3 class="tf-stat-value" style="color: #34d399; font-size: 1.35rem;"><?php echo esc_html( $ready_count ); ?></h3>
							<span class="tf-stat-trend positive">Ready for Pickup</span>
						</div>
					</div>

					<div class="tf-glass-card tf-stat-card" style="border-left: 4px solid #8b5cf6; min-height: 100px; padding: 18px; margin-bottom: 0;">
						<div class="tf-stat-icon" style="background: rgba(139, 92, 246, 0.2); color: #a78bfa;">
							<span class="dashicons dashicons-money-alt"></span>
						</div>
						<div class="tf-stat-details">
							<span class="tf-stat-label">Pending Payments</span>
							<h3 class="tf-stat-value" style="color: #a78bfa; font-size: 1.25rem;">PKR <?php echo esc_html( number_format( $pending_pay_sum, 2 ) ); ?></h3>
							<span class="tf-stat-trend warning">Uncollected Balance</span>
						</div>
					</div>
				</div>

				<!-- Stats Overview Cards (4 Equal Columns) -->
				<div class="tf-stats-grid" style="grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
					<div class="tf-glass-card tf-stat-card" style="min-height: 100px; padding: 18px; margin-bottom: 0;">
						<div class="tf-stat-icon icon-blue">
							<span class="dashicons dashicons-clipboard"></span>
						</div>
						<div class="tf-stat-details">
							<span class="tf-stat-label">Total Orders</span>
							<h3 class="tf-stat-value" id="tf-overview-total-orders" style="font-size: 1.35rem;"><?php echo esc_html( count( $orders_list ) ); ?></h3>
							<span class="tf-stat-trend positive">Active Shop Orders</span>
						</div>
					</div>

					<div class="tf-glass-card tf-stat-card" style="min-height: 100px; padding: 18px; margin-bottom: 0;">
						<div class="tf-stat-icon icon-amber">
							<span class="dashicons dashicons-clock"></span>
						</div>
						<div class="tf-stat-details">
							<span class="tf-stat-label">Pending Suits</span>
							<h3 class="tf-stat-value" style="font-size: 1.35rem;"><?php echo esc_html( $pending_suits ); ?></h3>
							<span class="tf-stat-trend warning">In Production Queue</span>
						</div>
					</div>

					<?php if ( $can_manage_cust ) : ?>
						<div class="tf-glass-card tf-stat-card" style="min-height: 100px; padding: 18px; margin-bottom: 0;">
							<div class="tf-stat-icon icon-green">
								<span class="dashicons dashicons-groups"></span>
							</div>
							<div class="tf-stat-details">
								<span class="tf-stat-label">Active Customers</span>
								<h3 class="tf-stat-value" style="font-size: 1.35rem;"><?php echo esc_html( count( $customers_list ) ); ?></h3>
								<span class="tf-stat-trend positive">Registered Profiles</span>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $can_view_reports ) : ?>
						<div class="tf-glass-card tf-stat-card" style="min-height: 100px; padding: 18px; margin-bottom: 0;">
							<div class="tf-stat-icon icon-purple">
								<span class="dashicons dashicons-chart-area"></span>
							</div>
							<div class="tf-stat-details">
								<span class="tf-stat-label">Total Revenue</span>
								<h3 class="tf-stat-value" style="font-size: 1.25rem;">PKR <?php echo esc_html( number_format( $init_revenue, 2 ) ); ?></h3>
								<span class="tf-stat-trend positive">Gross Shop Revenue</span>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<!-- Recent Orders Grid -->
				<div class="tf-glass-card" style="margin-top: 20px;">
					<div class="tf-card-header">
						<h3>Customer Orders Directory</h3>
						<?php if ( $can_manage_orders ) : ?>
							<button class="tf-btn-link" data-switch-tab="new-order">Add New</button>
						<?php endif; ?>
					</div>
					<div class="tf-table-responsive">
						<table class="tf-table">
							<thead>
								<tr>
									<th>Order #</th>
									<th>Customer</th>
									<th>Garment</th>
									<th>Delivery</th>
									<th>Stage</th>
									<?php if ( $can_view_reports ) : ?>
										<th>Amount</th>
									<?php endif; ?>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody id="tf-overview-orders-tbody">
								<?php if ( ! empty( $orders_list ) ) : ?>
									<?php foreach ( array_slice( $orders_list, 0, 15 ) as $ord ) : ?>
										<?php $curr_stage = strtolower( $ord['status'] ); ?>
										<tr id="tf-order-row-<?php echo esc_attr( $ord['id'] ); ?>">
											<td><strong><?php echo esc_html( $ord['order_number'] ); ?></strong></td>
											<td><?php echo esc_html( $ord['customer_name'] ? $ord['customer_name'] : 'Customer #' . $ord['customer_id'] ); ?></td>
											<td><?php echo esc_html( $ord['garment_type'] . ' (' . $ord['quantity'] . ')' ); ?></td>
											<td><?php echo esc_html( $ord['delivery_date'] ? date( 'd M, Y', strtotime( $ord['delivery_date'] ) ) : 'N/A' ); ?></td>
											<td>
												<?php if ( $can_update_stage ) : ?>
													<select class="tf-stage-select tf-stage-<?php echo esc_attr( $curr_stage ); ?>" data-order-id="<?php echo esc_attr( $ord['id'] ); ?>">
														<?php foreach ( $stages_map as $s_key => $s_label ) : ?>
															<option value="<?php echo esc_attr( $s_key ); ?>" <?php selected( $curr_stage, $s_key ); ?>><?php echo esc_html( $s_label ); ?></option>
														<?php endforeach; ?>
													</select>
												<?php else : ?>
													<span class="tf-badge badge-stitching"><?php echo esc_html( ucfirst( $curr_stage ) ); ?></span>
												<?php endif; ?>
											</td>
											<?php if ( $can_view_reports ) : ?>
												<td>PKR <?php echo esc_html( number_format( $ord['total_amount'], 2 ) ); ?></td>
											<?php endif; ?>
											<td>
												<div style="display: flex; gap: 6px; align-items: center;">
													<button class="tf-btn-sm tf-btn-view-order" data-order-id="<?php echo esc_attr( $ord['id'] ); ?>" title="View Order Details" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">
														<span class="dashicons dashicons-visibility"></span> View
													</button>
													<?php if ( $can_manage_orders ) : ?>
														<button class="tf-btn-sm tf-btn-edit-order" data-order-id="<?php echo esc_attr( $ord['id'] ); ?>" title="Edit Order" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
															<span class="dashicons dashicons-edit"></span> Edit
														</button>
													<?php endif; ?>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr class="tf-no-orders-row">
										<td colspan="7" style="text-align: center; color: var(--tf-text-dim);">No orders created yet.</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</section>

			<!-- TAB 2: NEW ORDER -->
			<?php if ( $can_manage_orders ) : ?>
				<section id="tf-tab-new-order" class="tf-tab-content">
					<div class="tf-page-header">
						<div>
							<h1>Create New Tailoring Order</h1>
							<p>Record customer measurements, cloth details and delivery terms</p>
						</div>
					</div>

					<div class="tf-glass-card">
						<form id="tf-create-order-form" class="tf-form-grid">
							<div class="tf-form-group col-span-2">
								<label for="tf-order-customer-id">Select Customer <span class="tf-required">*</span></label>
								<div style="display: flex; flex-direction: column; gap: 6px;">
									<select id="tf-order-customer-id" name="customer_id" class="tf-input" required>
										<option value="">-- Choose Existing Customer --</option>
										<?php foreach ( $customers_list as $c ) : ?>
											<option value="<?php echo esc_attr( $c['id'] ); ?>"><?php echo esc_html( $c['name'] . ' (' . $c['phone'] . ')' ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>

							<?php if ( $can_manage_measurements ) : ?>
								<div id="tf-order-measurements-section" class="tf-form-group col-span-2" style="display: none; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.02); padding: 16px; border-radius: 8px; margin-top: 8px;">
									<h4 style="color: #60a5fa; font-size: 14px; margin: 0 0 12px 0; display: flex; align-items: center; gap: 6px;">
										<span class="dashicons dashicons-edit"></span> Customer Measurements (Inches)
									</h4>
									<div class="tf-form-grid" style="grid-template-columns: repeat(3, 1fr); gap: 12px;">
										<div class="tf-form-group">
											<label for="tf-order-m-length">Length (Lambai)</label>
											<input type="text" id="tf-order-m-length" name="length" class="tf-input" placeholder='e.g. 40"'>
										</div>
										<div class="tf-form-group">
											<label for="tf-order-m-chest">Chest (Chati)</label>
											<input type="text" id="tf-order-m-chest" name="chest" class="tf-input" placeholder='e.g. 38"'>
										</div>
										<div class="tf-form-group">
											<label for="tf-order-m-waist">Waist (Kamar)</label>
											<input type="text" id="tf-order-m-waist" name="waist" class="tf-input" placeholder='e.g. 34"'>
										</div>
										<div class="tf-form-group">
											<label for="tf-order-m-hip">Hip</label>
											<input type="text" id="tf-order-m-hip" name="hip" class="tf-input" placeholder='e.g. 40"'>
										</div>
										<div class="tf-form-group">
											<label for="tf-order-m-shoulder">Shoulder (Teera)</label>
											<input type="text" id="tf-order-m-shoulder" name="shoulder" class="tf-input" placeholder='e.g. 18"'>
										</div>
										<div class="tf-form-group">
											<label for="tf-order-m-sleeves">Sleeves (Aasteen)</label>
											<input type="text" id="tf-order-m-sleeves" name="sleeves" class="tf-input" placeholder='e.g. 24"'>
										</div>
										<div class="tf-form-group">
											<label for="tf-order-m-neck">Neck (Gala)</label>
											<input type="text" id="tf-order-m-neck" name="neck" class="tf-input" placeholder='e.g. 15.5"'>
										</div>
										<div class="tf-form-group">
											<label for="tf-order-m-shalwar-length">Shalwar Length</label>
											<input type="text" id="tf-order-m-shalwar-length" name="shalwar_length" class="tf-input" placeholder='e.g. 38"'>
										</div>
										<div class="tf-form-group">
											<label for="tf-order-m-paucha">Paucha (Pancha)</label>
											<input type="text" id="tf-order-m-paucha" name="paucha" class="tf-input" placeholder='e.g. 8.5"'>
										</div>
									</div>
								</div>
							<?php endif; ?>

							<div class="tf-form-group">
								<label for="tf-order-garment-type">Garment Type</label>
								<select id="tf-order-garment-type" name="garment_type" class="tf-input">
									<option value="Kameez Shalwar">Kameez Shalwar</option>
									<option value="2-Piece Suit">2-Piece Suit</option>
									<option value="3-Piece Suit">3-Piece Suit</option>
									<option value="Sherwani">Sherwani</option>
									<option value="Waistcoat">Waistcoat</option>
									<option value="Kurta Pajama">Kurta Pajama</option>
								</select>
							</div>

							<div class="tf-form-group">
								<label for="tf-order-quantity">Quantity</label>
								<input type="number" id="tf-order-quantity" name="quantity" class="tf-input" value="1" min="1">
							</div>

							<div class="tf-form-group">
								<label for="tf-order-trial-date">Trial Date</label>
								<input type="date" id="tf-order-trial-date" name="trial_date" class="tf-input">
							</div>

							<div class="tf-form-group">
								<label for="tf-order-delivery-date">Delivery Date</label>
								<input type="date" id="tf-order-delivery-date" name="delivery_date" class="tf-input">
							</div>

							<div class="tf-form-group">
								<label for="tf-order-total-amount">Total Amount (PKR) <span class="tf-required">*</span></label>
								<input type="number" id="tf-order-total-amount" name="total_amount" class="tf-input" placeholder="e.g. 3500" step="0.01" required>
							</div>

							<div class="tf-form-group">
								<label for="tf-order-advance-amount">Advance Paid (PKR)</label>
								<input type="number" id="tf-order-advance-amount" name="advance_amount" class="tf-input" placeholder="e.g. 1000" step="0.01" value="0">
							</div>

							<div class="tf-form-group col-span-2">
								<label for="tf-order-stage">Initial Order Stage</label>
								<select id="tf-order-stage" name="stage" class="tf-input">
									<option value="received">Received</option>
									<option value="cutting">Cutting</option>
									<option value="stitching">Stitching</option>
									<option value="pressing">Pressing</option>
									<option value="ready">Ready</option>
									<option value="delivered">Delivered</option>
								</select>
							</div>

							<div class="tf-form-group col-span-2">
								<label for="tf-order-cloth-details">Cloth & Fabric Details</label>
								<textarea id="tf-order-cloth-details" name="cloth_details" class="tf-input" rows="2" placeholder="Fabric color, brand, cloth meter length..."></textarea>
							</div>

							<div class="tf-form-group col-span-2">
								<label for="tf-order-special-notes">Special Instructions</label>
								<textarea id="tf-order-special-notes" name="special_notes" class="tf-input" rows="2" placeholder="Urgent delivery, collar shape, special cuff..."></textarea>
							</div>

							<div id="tf-order-notice" class="tf-notice col-span-2" style="display: none;"></div>

							<div class="tf-form-actions col-span-2">
								<button type="submit" id="tf-create-order-btn" class="tf-btn tf-btn-primary">
									<span class="dashicons dashicons-printer"></span>
									<span>Save Order & Print Invoice</span>
								</button>
								<button type="button" class="tf-btn tf-btn-secondary" data-switch-tab="overview">Cancel</button>
							</div>
						</form>
					</div>
				</section>
			<?php endif; ?>

			<!-- TAB 3: CUSTOMERS -->
			<?php if ( $can_manage_cust ) : ?>
				<section id="tf-tab-customers" class="tf-tab-content">
					<div class="tf-page-header">
						<div>
							<h1>Customer Directory & Measurements</h1>
							<p>Manage customer profiles and custom measurement logs</p>
						</div>
						<button id="tf-toggle-customer-form" class="tf-btn tf-btn-accent">
							<span class="dashicons dashicons-plus-alt2"></span>
							<span>Add New Customer</span>
						</button>
					</div>

					<!-- Add Customer Form Card -->
					<div id="tf-customer-form-card" class="tf-glass-card" style="display: none;">
						<div class="tf-card-header">
							<h3>Add New Customer</h3>
							<button id="tf-close-customer-form" class="tf-btn-icon"><span class="dashicons dashicons-no-alt"></span></button>
						</div>
						<form id="tf-add-customer-form" class="tf-form-grid">
							<div class="tf-form-group">
								<label for="tf-cust-name">Full Name <span class="tf-required">*</span></label>
								<input type="text" id="tf-cust-name" name="name" class="tf-input" placeholder="e.g. Muhammad Ali" required>
							</div>

							<div class="tf-form-group">
								<label for="tf-cust-phone">Phone Number <span class="tf-required">*</span></label>
								<input type="text" id="tf-cust-phone" name="phone" class="tf-input" placeholder="e.g. 0300-1234567" required>
							</div>

							<div class="tf-form-group col-span-2">
								<label for="tf-cust-city">City</label>
								<input type="text" id="tf-cust-city" name="city" class="tf-input" placeholder="e.g. Lahore, Karachi, Islamabad">
							</div>

							<div class="tf-form-actions col-span-2">
								<button type="submit" id="tf-save-customer-btn" class="tf-btn tf-btn-primary">
									<span class="dashicons dashicons-saved"></span>
									<span>Save Customer</span>
								</button>
								<button type="button" id="tf-cancel-customer-form" class="tf-btn tf-btn-secondary">Cancel</button>
							</div>
						</form>
						<div id="tf-customer-notice" class="tf-notice" style="display: none; margin-top: 15px;"></div>
					</div>

					<!-- Customer Table -->
					<div class="tf-glass-card">
						<div class="tf-table-responsive">
							<table class="tf-table" id="tf-customers-table">
								<thead>
									<tr>
										<th>Code</th>
										<th>Name</th>
										<th>Phone</th>
										<th>City</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody id="tf-customers-tbody">
									<?php if ( ! empty( $customers_list ) ) : ?>
										<?php foreach ( $customers_list as $cust ) : ?>
											<tr>
												<td><strong><?php echo esc_html( $cust['customer_code'] ); ?></strong></td>
												<td><?php echo esc_html( $cust['name'] ); ?></td>
												<td><?php echo esc_html( $cust['phone'] ); ?></td>
												<td><?php echo esc_html( $cust['city'] ? $cust['city'] : '-' ); ?></td>
												<td>
													<div style="display: flex; gap: 6px; align-items: center;">
														<button class="tf-btn-sm tf-btn-measurements" data-customer-id="<?php echo esc_attr( $cust['id'] ); ?>" data-customer-name="<?php echo esc_attr( $cust['name'] ); ?>">
															<span class="dashicons dashicons-edit"></span> Measurements
														</button>
														<button class="tf-btn-sm tf-btn-customer-history" data-customer-id="<?php echo esc_attr( $cust['id'] ); ?>" data-customer-name="<?php echo esc_attr( $cust['name'] ); ?>" style="background: rgba(139, 92, 246, 0.15); color: #c084fc; border: 1px solid rgba(139, 92, 246, 0.3);">
															<span class="dashicons dashicons-backup"></span> View History
														</button>
													</div>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php else : ?>
										<tr class="tf-no-records">
											<td colspan="5" style="text-align: center; color: var(--tf-text-dim);">No customer records found. Click "Add New Customer" above to create one.</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<!-- TAB 4: KARIGAR LEDGER -->
			<?php if ( $can_manage_karigar ) : ?>
				<section id="tf-tab-karigar-ledger" class="tf-tab-content">
					<div class="tf-page-header">
						<div>
							<h1>Karigar Wage Ledger</h1>
							<p>Track stitching wages, advances, payouts and artisan balances</p>
						</div>
					</div>

					<div class="tf-glass-card">
						<div class="tf-table-responsive">
							<table class="tf-table">
								<thead>
									<tr>
										<th>Karigar Name</th>
										<th>Specialization</th>
										<th>Completed Suits</th>
										<th>Earned Wages</th>
										<th>Advance Taken</th>
										<th>Net Payable</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><strong>Ustad Rashid</strong></td>
										<td>Coat & Suit Master</td>
										<td>14 Suits</td>
										<td>PKR 28,000</td>
										<td>PKR 5,000</td>
										<td><strong class="tf-text-green">PKR 23,000</strong></td>
										<td><button class="tf-btn-sm">Pay Balance</button></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<!-- TAB 5: REPORTS -->
			<?php if ( $can_view_reports ) : ?>
				<section id="tf-tab-reports" class="tf-tab-content">
					<div class="tf-page-header">
						<div>
							<h1>Shop Reports & Financial Analytics</h1>
							<p id="tf-report-period-subtitle">Real-time revenue, customer balances, and Karigar wage analytics</p>
						</div>
					</div>

					<!-- Date Filters Card -->
					<div class="tf-glass-card">
						<div class="tf-card-header">
							<h3><span class="dashicons dashicons-filter"></span> Date & Period Filters</h3>
						</div>
						<form id="tf-reports-filter-form" class="tf-form-grid">
							<div class="tf-form-group">
								<label for="tf-report-type">Report Type</label>
								<select id="tf-report-type" name="report_type" class="tf-input">
									<option value="all_time">All-Time Summary</option>
									<option value="monthly" selected>Monthly Report</option>
									<option value="yearly">Yearly Report</option>
									<option value="custom">Custom Date Range</option>
								</select>
							</div>

							<!-- Monthly Input -->
							<div id="tf-filter-monthly-wrap" class="tf-form-group">
								<label for="tf-report-month">Select Month</label>
								<input type="month" id="tf-report-month" name="month" class="tf-input" value="<?php echo esc_attr( date( 'Y-m' ) ); ?>">
							</div>

							<!-- Yearly Input -->
							<div id="tf-filter-yearly-wrap" class="tf-form-group" style="display: none;">
								<label for="tf-report-year">Select Year</label>
								<select id="tf-report-year" name="year" class="tf-input">
									<?php $c_year = (int) date( 'Y' ); ?>
									<?php for ( $y = $c_year; $y >= $c_year - 5; $y-- ) : ?>
										<option value="<?php echo esc_attr( $y ); ?>"><?php echo esc_html( $y ); ?></option>
									<?php endfor; ?>
								</select>
							</div>

							<!-- Custom Date Range Inputs -->
							<div id="tf-filter-custom-start-wrap" class="tf-form-group" style="display: none;">
								<label for="tf-report-start-date">Start Date</label>
								<input type="date" id="tf-report-start-date" name="start_date" class="tf-input" value="<?php echo esc_attr( date( 'Y-m-01' ) ); ?>">
							</div>

							<div id="tf-filter-custom-end-wrap" class="tf-form-group" style="display: none;">
								<label for="tf-report-end-date">End Date</label>
								<input type="date" id="tf-report-end-date" name="end_date" class="tf-input" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
							</div>

							<div class="tf-form-actions col-span-2">
								<button type="submit" id="tf-apply-report-btn" class="tf-btn tf-btn-primary">
									<span class="dashicons dashicons-update"></span>
									<span>Apply Filter via AJAX</span>
								</button>
							</div>
						</form>
					</div>

					<!-- Reports Financial Stats Cards -->
					<div class="tf-stats-grid">
						<div class="tf-glass-card tf-stat-card">
							<div class="tf-stat-icon icon-blue">
								<span class="dashicons dashicons-money-alt"></span>
							</div>
							<div class="tf-stat-details">
								<span class="tf-stat-label">Total Revenue</span>
								<h3 class="tf-stat-value">PKR <span id="tf-rep-revenue"><?php echo esc_html( number_format( $init_revenue, 2 ) ); ?></span></h3>
								<span class="tf-stat-trend positive">Gross Sales Value</span>
							</div>
						</div>

						<div class="tf-glass-card tf-stat-card">
							<div class="tf-stat-icon icon-green">
								<span class="dashicons dashicons-yes-alt"></span>
							</div>
							<div class="tf-stat-details">
								<span class="tf-stat-label">Advance Received</span>
								<h3 class="tf-stat-value">PKR <span id="tf-rep-advance"><?php echo esc_html( number_format( $init_advance, 2 ) ); ?></span></h3>
								<span class="tf-stat-trend positive">Collected Cash</span>
							</div>
						</div>

						<div class="tf-glass-card tf-stat-card">
							<div class="tf-stat-icon icon-amber">
								<span class="dashicons dashicons-clock"></span>
							</div>
							<div class="tf-stat-details">
								<span class="tf-stat-label">Customer Pending Balance</span>
								<h3 class="tf-stat-value">PKR <span id="tf-rep-pending-balance"><?php echo esc_html( number_format( $init_pending, 2 ) ); ?></span></h3>
								<span class="tf-stat-trend warning">Receivable on Delivery</span>
							</div>
						</div>

						<div class="tf-glass-card tf-stat-card">
							<div class="tf-stat-icon icon-purple">
								<span class="dashicons dashicons-awards"></span>
							</div>
							<div class="tf-stat-details">
								<span class="tf-stat-label">Karigar Wages Paid</span>
								<h3 class="tf-stat-value">PKR <span id="tf-rep-karigar-paid"><?php echo esc_html( number_format( $init_k_paid, 2 ) ); ?></span></h3>
								<span class="tf-stat-trend positive">Disbursed Wages</span>
							</div>
						</div>

						<div class="tf-glass-card tf-stat-card">
							<div class="tf-stat-icon icon-blue" style="background: linear-gradient(135deg, #dc2626, #ef4444);">
								<span class="dashicons dashicons-warning"></span>
							</div>
							<div class="tf-stat-details">
								<span class="tf-stat-label">Karigar Pending Wages</span>
								<h3 class="tf-stat-value">PKR <span id="tf-rep-karigar-pending"><?php echo esc_html( number_format( $init_k_pend, 2 ) ); ?></span></h3>
								<span class="tf-stat-trend warning">Payable to Artisans</span>
							</div>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<!-- TAB 6: USER ROLES MANAGER & ACTIVITY LOGS (Owner Only) -->
			<?php if ( $can_manage_roles ) : ?>
				<section id="tf-tab-user-roles" class="tf-tab-content">
					<div class="tf-page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
						<div>
							<h1>User Role Manager & Audit Security</h1>
							<p>Manage staff permissions, active status and view immutable activity logs (Owner Access Only)</p>
						</div>
						<button class="tf-btn tf-btn-accent" id="tf-open-add-user-btn">
							<span class="dashicons dashicons-plus-alt"></span>
							<span>Add New Staff</span>
						</button>
					</div>

					<!-- User Role Assignment Table -->
					<div class="tf-glass-card">
						<div class="tf-card-header">
							<h3><span class="dashicons dashicons-shield"></span> Manage WordPress Staff Roles & Permissions</h3>
						</div>

						<!-- Search & Role / Status Filter Bar -->
						<div class="tf-form-grid" style="margin-bottom: 20px; grid-template-columns: 2fr 1fr 1fr; gap: 12px;">
							<div class="tf-form-group">
								<label for="tf-staff-search"><span class="dashicons dashicons-search"></span> Search Staff</label>
								<input type="text" id="tf-staff-search" class="tf-input" placeholder="Search by Name, Username, or Mobile...">
							</div>
							<div class="tf-form-group">
								<label for="tf-staff-role-filter"><span class="dashicons dashicons-filter"></span> Filter by Role</label>
								<select id="tf-staff-role-filter" class="tf-input">
									<option value="all">All Roles</option>
									<option value="tf_owner">Owner</option>
									<option value="tf_manager">Manager</option>
									<option value="tf_receptionist">Receptionist</option>
									<option value="tf_karigar">Karigar</option>
									<option value="tf_cashier">Cashier</option>
								</select>
							</div>
							<div class="tf-form-group">
								<label for="tf-staff-status-filter"><span class="dashicons dashicons-filter"></span> Filter by Status</label>
								<select id="tf-staff-status-filter" class="tf-input">
									<option value="all">All Statuses</option>
									<option value="active">Active</option>
									<option value="inactive">Inactive</option>
								</select>
							</div>
						</div>

						<div id="tf-staff-notice" class="tf-notice" style="display: none; margin-bottom: 16px;"></div>

						<div class="tf-table-responsive">
							<table class="tf-table" id="tf-staff-roles-table">
								<thead>
									<tr>
										<th>Name</th>
										<th>Username</th>
										<th>Mobile</th>
										<th>Role</th>
										<th>Status</th>
										<th>Last Login</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody id="tf-staff-tbody">
									<?php
									$all_users = get_users( array( 'number' => 100 ) );
									foreach ( $all_users as $u ) :
										$u_roles      = (array) $u->roles;
										$role_display = ! empty( $u_roles ) ? implode( ', ', array_map( 'ucfirst', $u_roles ) ) : 'None';
										$is_self      = ( $current_user->ID === $u->ID );

										$all_meta = get_user_meta( $u->ID );
										$mobile   = ! empty( $all_meta['phone'][0] ) ? $all_meta['phone'][0] : ( ! empty( $all_meta['billing_phone'][0] ) ? $all_meta['billing_phone'][0] : ( ! empty( $all_meta['mobile'][0] ) ? $all_meta['mobile'][0] : '-' ) );

										$last_login_meta = ! empty( $all_meta['last_login'][0] ) ? (int) $all_meta['last_login'][0] : 0;
										$last_login_fmt  = 'Never';
										if ( $last_login_meta ) {
											$tz_string = get_option( 'timezone_string' );
											$gmt_offset = get_option( 'gmt_offset' );
											if ( ! empty( $tz_string ) ) {
												$tz = new DateTimeZone( $tz_string );
											} elseif ( ! empty( $gmt_offset ) && 0 != $gmt_offset ) {
												$tz = wp_timezone();
											} else {
												$tz = new DateTimeZone( 'Asia/Karachi' );
											}
											$date_format    = get_option( 'date_format', 'd M Y' );
											$time_format    = get_option( 'time_format', 'h:i A' );
											$last_login_fmt = wp_date( $date_format . ', ' . $time_format, $last_login_meta, $tz );
										}

										$u_status = function_exists( 'tf_get_user_status' ) ? tf_get_user_status( $u->ID ) : 'active';

										// Role key for filtering
										$tf_role_key = 'none';
										if ( in_array( 'tf_owner', $u_roles, true ) ) {
											$tf_role_key = 'tf_owner';
										} elseif ( in_array( 'tf_manager', $u_roles, true ) ) {
											$tf_role_key = 'tf_manager';
										} elseif ( in_array( 'tf_receptionist', $u_roles, true ) ) {
											$tf_role_key = 'tf_receptionist';
										} elseif ( in_array( 'tf_karigar', $u_roles, true ) ) {
											$tf_role_key = 'tf_karigar';
										} elseif ( in_array( 'tf_cashier', $u_roles, true ) ) {
											$tf_role_key = 'tf_cashier';
										}
										?>
										<tr data-user-id="<?php echo esc_attr( $u->ID ); ?>" data-name="<?php echo esc_attr( strtolower( $u->display_name ) ); ?>" data-username="<?php echo esc_attr( strtolower( $u->user_login ) ); ?>" data-mobile="<?php echo esc_attr( strtolower( $mobile ) ); ?>" data-tf-role="<?php echo esc_attr( $tf_role_key ); ?>" data-tf-status="<?php echo esc_attr( $u_status ); ?>">
											<td>
												<strong><?php echo esc_html( $u->display_name ); ?></strong>
												<?php if ( $is_self ) : ?>
													<span class="tf-badge badge-warning" style="margin-left: 4px;">You</span>
												<?php endif; ?>
											</td>
											<td><code>@<?php echo esc_html( $u->user_login ); ?></code></td>
											<td><?php echo esc_html( $mobile ); ?></td>
											<td><span class="tf-badge badge-ready"><?php echo esc_html( $role_display ); ?></span></td>
											<td class="tf-status-cell">
												<?php if ( $is_self ) : ?>
													<span class="tf-badge badge-active" title="Logged in user status cannot be changed">Active</span>
												<?php else : ?>
													<button type="button" class="tf-status-toggle-badge <?php echo 'active' === $u_status ? 'badge-active' : 'badge-inactive'; ?>" data-user-id="<?php echo esc_attr( $u->ID ); ?>" data-current-status="<?php echo esc_attr( $u_status ); ?>" title="Click to toggle Active / Inactive status" style="background: <?php echo 'active' === $u_status ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)'; ?>; color: <?php echo 'active' === $u_status ? '#34d399' : '#f87171'; ?>; border: 1px solid <?php echo 'active' === $u_status ? 'rgba(16, 185, 129, 0.3)' : 'rgba(239, 68, 68, 0.3)'; ?>; cursor: pointer; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px;">
														<span class="dashicons dashicons-update" style="font-size: 12px; width: 12px; height: 12px;"></span>
														<span class="tf-status-label"><?php echo 'active' === $u_status ? 'Active' : 'Inactive'; ?></span>
													</button>
												<?php endif; ?>
											</td>
											<td><small><?php echo esc_html( $last_login_fmt ); ?></small></td>
											<td>
												<div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
													<?php if ( $is_self ) : ?>
														<button class="tf-btn-sm" disabled style="opacity:0.5; cursor:not-allowed;">
															<span class="dashicons dashicons-lock"></span> Protected
														</button>
													<?php else : ?>
														<select class="tf-input tf-user-role-select" style="width: 130px; height: 32px; padding: 0 6px; font-size: 12px;" data-user-id="<?php echo esc_attr( $u->ID ); ?>" data-user-name="<?php echo esc_attr( $u->display_name ); ?>" data-current-role="<?php echo esc_attr( $role_display ); ?>">
															<option value="">-- Role --</option>
															<option value="tf_owner" <?php selected( in_array( 'tf_owner', $u_roles, true ) ); ?>>Owner</option>
															<option value="tf_manager" <?php selected( in_array( 'tf_manager', $u_roles, true ) ); ?>>Manager</option>
															<option value="tf_receptionist" <?php selected( in_array( 'tf_receptionist', $u_roles, true ) ); ?>>Receptionist</option>
															<option value="tf_karigar" <?php selected( in_array( 'tf_karigar', $u_roles, true ) ); ?>>Karigar</option>
															<option value="tf_cashier" <?php selected( in_array( 'tf_cashier', $u_roles, true ) ); ?>>Cashier</option>
															<option value="remove">Remove Role</option>
														</select>
														<button class="tf-btn-sm tf-save-user-role-btn" data-user-id="<?php echo esc_attr( $u->ID ); ?>" title="Save Role">
															<span class="dashicons dashicons-saved"></span>
														</button>

														<button class="tf-btn-sm tf-toggle-user-status-btn" data-user-id="<?php echo esc_attr( $u->ID ); ?>" data-current-status="<?php echo esc_attr( $u_status ); ?>" title="Toggle Active Status" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3);">
															<span class="dashicons dashicons-admin-settings"></span>
														</button>

														<button class="tf-btn-sm tf-delete-user-btn" data-user-id="<?php echo esc_attr( $u->ID ); ?>" data-user-name="<?php echo esc_attr( $u->display_name ); ?>" title="Delete Staff User" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">
															<span class="dashicons dashicons-trash"></span>
														</button>
													<?php endif; ?>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>

					<!-- Activity Logs Table -->
					<div class="tf-glass-card" style="margin-top: 30px;">
						<div class="tf-card-header">
							<h3><span class="dashicons dashicons-list-view"></span> Security Audit Activity Logs</h3>
						</div>
						<div class="tf-table-responsive">
							<table class="tf-table">
								<thead>
									<tr>
										<th>Action Type</th>
										<th>Performed By</th>
										<th>Target User</th>
										<th>Previous State</th>
										<th>New State / Details</th>
										<th>Date & Time</th>
										<th>IP Address</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$table_logs = $wpdb->prefix . 'tf_activity_logs';
									$logs       = array();
									if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_logs ) ) === $table_logs ) {
										$logs = $wpdb->get_results( "SELECT * FROM {$table_logs} ORDER BY id DESC LIMIT 50", ARRAY_A );
									}
									if ( ! empty( $logs ) ) :
										foreach ( $logs as $lg ) :
											$performer = get_userdata( $lg['user_id'] );
											$target    = get_userdata( $lg['target_user_id'] );
											$atype     = strtolower( $lg['action_type'] );

											$badge_class = 'badge-ready';
											$action_label = ucfirst( str_replace( '_', ' ', $atype ) );

											if ( 'user_created' === $atype || 'new_staff' === $atype ) {
												$badge_class  = 'badge-active';
												$action_label = 'New Staff';
											} elseif ( 'role_change' === $atype ) {
												$badge_class  = 'badge-ready';
												$action_label = 'Role Change';
											} elseif ( 'user_login' === $atype ) {
												$badge_class  = 'badge-active';
												$action_label = 'Login';
											} elseif ( 'user_logout' === $atype ) {
												$badge_class  = 'badge-warning';
												$action_label = 'Logout';
											} elseif ( 'user_deleted' === $atype ) {
												$badge_class  = 'badge-inactive';
												$action_label = 'Delete User';
											} elseif ( 'status_change' === $atype ) {
												$badge_class  = 'badge-warning';
												$action_label = 'Status Change';
											}
											?>
											<tr>
												<td><span class="tf-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $action_label ); ?></span></td>
												<td><strong><?php echo esc_html( $performer ? $performer->display_name : 'User #' . $lg['user_id'] ); ?></strong></td>
												<td><?php echo esc_html( $target ? $target->display_name : 'User #' . $lg['target_user_id'] ); ?></td>
												<td><span class="tf-badge badge-warning"><?php echo esc_html( $lg['prev_role'] ? $lg['prev_role'] : '-' ); ?></span></td>
												<td><span class="tf-badge badge-ready"><?php echo esc_html( $lg['new_role'] ? $lg['new_role'] : '-' ); ?></span></td>
												<td>
													<?php
													$log_tz_string = get_option( 'timezone_string' );
													$log_gmt_offset = get_option( 'gmt_offset' );
													if ( ! empty( $log_tz_string ) ) {
														$log_tz = new DateTimeZone( $log_tz_string );
													} elseif ( ! empty( $log_gmt_offset ) && 0 != $log_gmt_offset ) {
														$log_tz = wp_timezone();
													} else {
														$log_tz = new DateTimeZone( 'Asia/Karachi' );
													}
													$log_dt = date_create( $lg['created_at'], wp_timezone() );
													if ( $log_dt ) {
														echo esc_html( wp_date( get_option( 'date_format', 'd M, Y' ) . ' ' . get_option( 'time_format', 'h:i A' ), $log_dt->getTimestamp(), $log_tz ) );
													} else {
														echo esc_html( $lg['created_at'] );
													}
													?>
												</td>
												<td><code><?php echo esc_html( $lg['ip_address'] ); ?></code></td>
											</tr>
										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="7" style="text-align: center; color: var(--tf-text-dim);">No activity found</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<!-- TAB 7: SETTINGS (Owner/Admin Only) -->
			<?php if ( $can_manage_settings ) : ?>
				<section id="tf-tab-settings" class="tf-tab-content">
					<div class="tf-page-header">
						<div>
							<h1>Shop & System Settings</h1>
							<p>Configure company profile, logo, receipt footer, currency, and timezone</p>
						</div>
					</div>

					<div class="tf-glass-card" style="max-width: 800px;">
						<form id="tf-settings-form">
							<div id="tf-settings-notice" class="tf-notice" style="display: none; margin-bottom: 20px;"></div>

							<div class="tf-form-grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
								<div class="tf-form-group col-span-2">
									<label for="tf-set-company-name">Company / Shop Name <span class="tf-required">*</span></label>
									<input type="text" id="tf-set-company-name" name="company_name" class="tf-input" value="<?php echo esc_attr( function_exists( 'tf_get_setting' ) ? tf_get_setting( 'company_name' ) : 'Tailor Flow PK' ); ?>" required>
								</div>

								<div class="tf-form-group col-span-2">
									<label for="tf-set-logo-url">Logo Image URL</label>
									<div style="display: flex; gap: 8px;">
										<input type="url" id="tf-set-logo-url" name="logo_url" class="tf-input" value="<?php echo esc_attr( function_exists( 'tf_get_setting' ) ? tf_get_setting( 'logo_url' ) : '' ); ?>" placeholder="https://example.com/logo.png">
										<button type="button" id="tf-upload-logo-btn" class="tf-btn tf-btn-secondary" style="white-space: nowrap;">
											<span class="dashicons dashicons-upload"></span> Upload Logo
										</button>
									</div>
									<small style="color: #9ca3af; margin-top: 4px; display: block;">Image will display at the top of printed invoices and receipts.</small>
								</div>

								<div class="tf-form-group">
									<label for="tf-set-phone">Shop Phone Number</label>
									<input type="text" id="tf-set-phone" name="phone" class="tf-input" value="<?php echo esc_attr( function_exists( 'tf_get_setting' ) ? tf_get_setting( 'phone' ) : '+92 300 1234567' ); ?>">
								</div>

								<div class="tf-form-group">
									<label for="tf-set-currency">Currency Code / Symbol</label>
									<input type="text" id="tf-set-currency" name="currency" class="tf-input" value="<?php echo esc_attr( function_exists( 'tf_get_setting' ) ? tf_get_setting( 'currency' ) : 'PKR' ); ?>">
								</div>

								<div class="tf-form-group col-span-2">
									<label for="tf-set-address">Shop Address</label>
									<textarea id="tf-set-address" name="address" class="tf-input" rows="2"><?php echo esc_textarea( function_exists( 'tf_get_setting' ) ? tf_get_setting( 'address' ) : 'Shop #12, Commercial Market, Lahore' ); ?></textarea>
								</div>

								<div class="tf-form-group col-span-2">
									<label for="tf-set-receipt-footer">Receipt Footer Message</label>
									<input type="text" id="tf-set-receipt-footer" name="receipt_footer" class="tf-input" value="<?php echo esc_attr( function_exists( 'tf_get_setting' ) ? tf_get_setting( 'receipt_footer' ) : 'Thank you for choosing Tailor Flow PK!' ); ?>">
								</div>

								<div class="tf-form-group col-span-2">
									<label for="tf-set-timezone">Timezone</label>
									<select id="tf-set-timezone" name="timezone" class="tf-input">
										<?php $c_tz = function_exists( 'tf_get_setting' ) ? tf_get_setting( 'timezone' ) : 'Asia/Karachi'; ?>
										<option value="Asia/Karachi" <?php selected( $c_tz, 'Asia/Karachi' ); ?>>Asia/Karachi (PKT +05:00)</option>
										<option value="UTC" <?php selected( $c_tz, 'UTC' ); ?>>UTC</option>
										<option value="Asia/Dubai" <?php selected( $c_tz, 'Asia/Dubai' ); ?>>Asia/Dubai (+04:00)</option>
										<option value="Europe/London" <?php selected( $c_tz, 'Europe/London' ); ?>>Europe/London</option>
										<option value="America/New_York" <?php selected( $c_tz, 'America/New_York' ); ?>>America/New_York</option>
									</select>
								</div>
							</div>

							<div class="tf-form-actions" style="margin-top: 24px;">
								<button type="submit" id="tf-save-settings-btn" class="tf-btn tf-btn-primary">
									<span class="dashicons dashicons-saved"></span> Save Shop Settings
								</button>
							</div>
						</form>
					</div>
				</section>
			<?php endif; ?>

		</div>

		<!-- Footer -->
		<footer class="tf-app-footer">
			<p>Designed and Developed by <strong>Hammad Memon</strong></p>
		</footer>
	</main>
</div>

<!-- ==========================================================================
     ROLE CONFIRMATION MODAL
     ========================================================================== -->
<div id="tf-role-confirm-modal" class="tf-app tf-modal-overlay" style="display: none;">
	<div class="tf-modal-container tf-glass-card" style="max-width: 500px;">
		<div class="tf-modal-header">
			<div>
				<h3 style="color: #f59e0b;"><span class="dashicons dashicons-warning" style="font-size: 24px; vertical-align: middle;"></span> Confirm Role Change</h3>
				<p class="tf-modal-subtitle">Security Authorization Required</p>
			</div>
			<button id="tf-close-role-confirm-modal" class="tf-btn-icon" aria-label="Close Modal">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<div style="padding: 10px 0;">
			<div style="background: rgba(255,255,255,0.04); padding: 14px; border-radius: 8px; border: 1px solid var(--tf-border); margin-bottom: 16px;">
				<p style="margin-bottom: 6px; font-size: 0.9rem;">Target User: <strong id="tf-confirm-target-user" style="color: #ffffff;">-</strong></p>
				<p style="margin-bottom: 6px; font-size: 0.9rem;">Current Role: <strong id="tf-confirm-current-role" style="color: #fbbf24;">-</strong></p>
				<p style="font-size: 0.9rem;">New Role: <strong id="tf-confirm-new-role" style="color: #34d399;">-</strong></p>
			</div>

			<p style="font-size: 0.85rem; color: var(--tf-text-muted); margin-bottom: 10px;">
				Please type <strong style="color: #ef4444; letter-spacing: 1px;">CONFIRM</strong> below to authorize this role modification:
			</p>
			<input type="text" id="tf-role-confirm-input" class="tf-input" placeholder="Type CONFIRM here..." autocomplete="off">
		</div>

		<div class="tf-modal-footer">
			<button type="button" id="tf-execute-role-change-btn" class="tf-btn tf-btn-primary" disabled style="opacity: 0.5;">
				<span class="dashicons dashicons-saved"></span>
				<span>Confirm & Save Role</span>
			</button>
			<button type="button" id="tf-cancel-role-confirm-modal" class="tf-btn tf-btn-secondary">Cancel</button>
		</div>
	</div>
</div>

<!-- ==========================================================================
     MEASUREMENT MODAL
     ========================================================================== -->
<div id="tf-measurement-modal" class="tf-app tf-modal-overlay" style="display: none;">
	<div class="tf-modal-container tf-glass-card">
		<div class="tf-modal-header">
			<div>
				<h3>Customer Measurements</h3>
				<p id="tf-modal-customer-subtitle" class="tf-modal-subtitle">Customer: <strong id="tf-modal-customer-name">Name</strong></p>
			</div>
			<button id="tf-close-measurement-modal" class="tf-btn-icon" aria-label="Close Modal">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<form id="tf-measurement-form">
			<input type="hidden" id="tf-m-customer-id" name="customer_id" value="0">

			<div class="tf-measurement-grid">
				<div class="tf-form-group">
					<label for="tf-m-length">Length (Lambai)</label>
					<input type="text" id="tf-m-length" name="length" class="tf-input" placeholder='e.g. 40"'>
				</div>

				<div class="tf-form-group">
					<label for="tf-m-chest">Chest (Chati)</label>
					<input type="text" id="tf-m-chest" name="chest" class="tf-input" placeholder='e.g. 38"'>
				</div>

				<div class="tf-form-group">
					<label for="tf-m-waist">Waist (Kamar)</label>
					<input type="text" id="tf-m-waist" name="waist" class="tf-input" placeholder='e.g. 34"'>
				</div>

				<div class="tf-form-group">
					<label for="tf-m-hip">Hip</label>
					<input type="text" id="tf-m-hip" name="hip" class="tf-input" placeholder='e.g. 40"'>
				</div>

				<div class="tf-form-group">
					<label for="tf-m-shoulder">Shoulder (Teera)</label>
					<input type="text" id="tf-m-shoulder" name="shoulder" class="tf-input" placeholder='e.g. 18"'>
				</div>

				<div class="tf-form-group">
					<label for="tf-m-sleeves">Sleeves (Aasteen)</label>
					<input type="text" id="tf-m-sleeves" name="sleeves" class="tf-input" placeholder='e.g. 24"'>
				</div>

				<div class="tf-form-group">
					<label for="tf-m-neck">Neck (Gala)</label>
					<input type="text" id="tf-m-neck" name="neck" class="tf-input" placeholder='e.g. 15.5"'>
				</div>

				<div class="tf-form-group">
					<label for="tf-m-shalwar-length">Shalwar Length</label>
					<input type="text" id="tf-m-shalwar-length" name="shalwar_length" class="tf-input" placeholder='e.g. 38"'>
				</div>

				<div class="tf-form-group">
					<label for="tf-m-paucha">Paucha (Pancha)</label>
					<input type="text" id="tf-m-paucha" name="paucha" class="tf-input" placeholder='e.g. 8.5"'>
				</div>

				<div class="tf-form-group col-span-3">
					<label for="tf-m-notes">Special Instructions & Notes</label>
					<textarea id="tf-m-notes" name="notes" class="tf-input" rows="3" placeholder="Double stitching, side pocket requirement, cuff design..."></textarea>
				</div>
			</div>

			<div id="tf-measurement-notice" class="tf-notice" style="display: none; margin-top: 15px;"></div>

			<div class="tf-modal-footer">
				<?php if ( current_user_can( 'tf_manage_measurements' ) || current_user_can( 'manage_options' ) ) : ?>
					<button type="submit" id="tf-save-measurement-btn" class="tf-btn tf-btn-primary">
						<span class="dashicons dashicons-saved"></span>
						<span>Save Measurements</span>
					</button>
				<?php endif; ?>
				<button type="button" id="tf-cancel-measurement-modal" class="tf-btn tf-btn-secondary">Close</button>
			</div>
		</form>
	</div>
</div>

<!-- ==========================================================================
     PRINTABLE INVOICE MODAL
     ========================================================================== -->
<div id="tf-invoice-modal" class="tf-app tf-modal-overlay" style="display: none;">
	<div class="tf-modal-container tf-glass-card tf-invoice-container">
		<div class="tf-modal-header no-print">
			<h3>Order Booking Receipt & Invoice</h3>
			<button id="tf-close-invoice-modal" class="tf-btn-icon" aria-label="Close Modal">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>

		<!-- Printable Receipt Card -->
		<div id="tf-invoice-printable-area" class="tf-receipt-card">
			<div class="tf-receipt-header">
				<?php
				$sys_logo  = function_exists( 'tf_get_setting' ) ? tf_get_setting( 'logo_url' ) : '';
				$sys_name  = function_exists( 'tf_get_setting' ) ? tf_get_setting( 'company_name' ) : 'TAILOR FLOW PK';
				$sys_phone = function_exists( 'tf_get_setting' ) ? tf_get_setting( 'phone' ) : '';
				$sys_addr  = function_exists( 'tf_get_setting' ) ? tf_get_setting( 'address' ) : '';
				$sys_foot  = function_exists( 'tf_get_setting' ) ? tf_get_setting( 'receipt_footer' ) : 'Thank you for choosing Tailor Flow PK!';
				$sys_curr  = function_exists( 'tf_get_setting' ) ? tf_get_setting( 'currency' ) : 'PKR';
				?>
				<div id="tf-inv-logo-wrapper" style="text-align: center; margin-bottom: 8px; <?php echo empty( $sys_logo ) ? 'display: none;' : ''; ?>">
					<img id="tf-inv-logo-img" src="<?php echo esc_url( $sys_logo ); ?>" alt="Logo" style="max-height: 50px; max-width: 180px; object-fit: contain;">
				</div>
				<h2 id="tf-inv-shop-name"><?php echo esc_html( strtoupper( $sys_name ) ); ?></h2>
				<p id="tf-inv-shop-info">
					<span id="tf-inv-shop-phone"><?php echo esc_html( $sys_phone ? $sys_phone : 'Custom Bespoke Tailoring' ); ?></span>
					<span id="tf-inv-shop-addr-sep"><?php echo $sys_addr ? ' | ' : ''; ?></span>
					<span id="tf-inv-shop-address"><?php echo esc_html( $sys_addr ); ?></span>
				</p>
				<div class="tf-receipt-order-no" id="tf-inv-order-no">TF-2026-0001</div>
			</div>

			<div class="tf-receipt-body">
				<div class="tf-receipt-row">
					<span>Booking Date:</span> <strong id="tf-inv-booking-date">-</strong>
				</div>
				<div class="tf-receipt-row">
					<span>Trial Date:</span> <strong id="tf-inv-trial-date">-</strong>
				</div>
				<div class="tf-receipt-row">
					<span>Delivery Date:</span> <strong id="tf-inv-delivery-date">-</strong>
				</div>
				<div class="tf-receipt-divider"></div>

				<div class="tf-receipt-row">
					<span>Customer Name:</span> <strong id="tf-inv-cust-name">-</strong>
				</div>
				<div class="tf-receipt-row">
					<span>Phone Number:</span> <strong id="tf-inv-cust-phone">-</strong>
				</div>
				<div class="tf-receipt-divider"></div>

				<div class="tf-receipt-row">
					<span>Garment Type:</span> <strong id="tf-inv-garment">-</strong>
				</div>
				<div class="tf-receipt-row">
					<span>Quantity:</span> <strong id="tf-inv-qty">-</strong>
				</div>
				<div class="tf-receipt-divider"></div>

				<div class="tf-receipt-row">
					<span>Total Amount:</span> <strong><span class="tf-inv-currency"><?php echo esc_html( $sys_curr ); ?></span> <span id="tf-inv-total">0.00</span></strong>
				</div>
				<div class="tf-receipt-row">
					<span>Advance Paid:</span> <strong class="tf-text-green"><span class="tf-inv-currency"><?php echo esc_html( $sys_curr ); ?></span> <span id="tf-inv-advance">0.00</span></strong>
				</div>
				<div class="tf-receipt-row tf-receipt-total">
					<span>Balance Due:</span> <strong><span class="tf-inv-currency"><?php echo esc_html( $sys_curr ); ?></span> <span id="tf-inv-balance">0.00</span></strong>
				</div>

				<div id="tf-inv-notes-wrapper" class="tf-receipt-notes" style="display: none; margin-top: 12px;">
					<small>Special Instructions:</small>
					<p id="tf-inv-notes"></p>
				</div>
			</div>

			<div class="tf-receipt-footer">
				<p id="tf-inv-receipt-footer"><?php echo esc_html( ! empty( $sys_foot ) ? $sys_foot : 'Thank you for choosing Tailor Flow PK!' ); ?></p>
				<small>Designed and Developed by <strong>Hammad Memon</strong></small>
			</div>
		</div>

		<div class="tf-modal-footer no-print">
			<button type="button" id="tf-print-invoice-btn" class="tf-btn tf-btn-primary">
				<span class="dashicons dashicons-printer"></span>
				<span>Print Receipt</span>
			</button>
			<button type="button" id="tf-done-invoice-btn" class="tf-btn tf-btn-secondary">Done</button>
		</div>
	</div>
</div>

<!-- Add Staff User Modal -->
<div id="tf-add-user-modal" class="tf-app tf-modal-overlay" style="display: none;">
	<div class="tf-modal-container tf-glass-card" style="max-width: 520px;">
		<div class="tf-modal-header">
			<div>
				<h3><span class="dashicons dashicons-admin-users" style="font-size: 22px; width: 22px; height: 22px; vertical-align: middle; margin-right: 6px;"></span> Create New Staff User</h3>
				<p class="tf-modal-subtitle">Add a new staff member to Tailor Flow PK</p>
			</div>
			<button type="button" id="tf-close-add-user-modal" class="tf-btn-icon" aria-label="Close Modal">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<form id="tf-add-user-form">
			<div class="tf-modal-body">
				<div id="tf-add-user-notice" class="tf-notice" style="display: none; margin-bottom: 16px;"></div>

				<div class="tf-form-group" style="margin-bottom: 14px;">
					<label for="tf-new-user-name">Full Name <span class="tf-required">*</span></label>
					<input type="text" id="tf-new-user-name" class="tf-input" placeholder="e.g. Ali Ahmed" required>
				</div>

				<div class="tf-form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 14px; gap: 12px;">
					<div class="tf-form-group">
						<label for="tf-new-user-username">Username <span class="tf-required">*</span></label>
						<input type="text" id="tf-new-user-username" class="tf-input" placeholder="e.g. aliahmed" required>
					</div>
					<div class="tf-form-group">
						<label for="tf-new-user-phone">Mobile / Phone</label>
						<input type="text" id="tf-new-user-phone" class="tf-input" placeholder="03001234567">
					</div>
				</div>

				<div class="tf-form-group" style="margin-bottom: 14px;">
					<label for="tf-new-user-email">Email Address <span class="tf-required">*</span></label>
					<input type="email" id="tf-new-user-email" class="tf-input" placeholder="ali@example.com" required>
				</div>

				<div class="tf-form-group" style="margin-bottom: 14px;">
					<label for="tf-new-user-password">Password <span class="tf-required">*</span></label>
					<input type="password" id="tf-new-user-password" class="tf-input" placeholder="Enter secure password" required>
				</div>

				<div class="tf-form-group" style="margin-bottom: 20px;">
					<label for="tf-new-user-role">Tailor Flow Role <span class="tf-required">*</span></label>
					<select id="tf-new-user-role" class="tf-input" required>
						<option value="tf_receptionist">Receptionist</option>
						<option value="tf_manager">Manager</option>
						<option value="tf_karigar">Karigar</option>
						<option value="tf_cashier">Cashier</option>
						<option value="tf_owner">Owner</option>
					</select>
				</div>
			</div>
			<div class="tf-modal-footer">
				<button type="submit" class="tf-btn tf-btn-accent" id="tf-save-new-user-btn">
					<span class="dashicons dashicons-saved"></span> Create Staff User
				</button>
				<button type="button" class="tf-btn tf-btn-secondary" id="tf-cancel-add-user-modal">Cancel</button>
			</div>
		</form>
	</div>
</div>

<!-- Order Details Modal (Module 1) -->
<div id="tf-order-details-modal" class="tf-app tf-modal-overlay" style="display: none;">
	<div class="tf-modal-container" style="max-width: 650px;">
		<div class="tf-modal-header">
			<h3><span class="dashicons dashicons-clipboard"></span> Order Details: <span id="tf-det-order-no">-</span></h3>
			<button class="tf-modal-close" id="tf-close-details-modal">&times;</button>
		</div>
		<div class="tf-modal-body" id="tf-details-modal-content">
			<div class="tf-form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 16px; gap: 12px; background: rgba(0,0,0,0.2); padding: 14px; border-radius: 8px;">
				<div>
					<small style="color: #9ca3af; display: block;">Customer Name:</small>
					<strong id="tf-det-cust-name" style="font-size: 15px; color: #ffffff;">-</strong>
				</div>
				<div>
					<small style="color: #9ca3af; display: block;">Phone Number:</small>
					<strong id="tf-det-cust-phone" style="font-size: 15px; color: #ffffff;">-</strong>
				</div>
				<div>
					<small style="color: #9ca3af; display: block;">Garment Type:</small>
					<strong id="tf-det-garment" style="color: #ffffff;">-</strong>
				</div>
				<div>
					<small style="color: #9ca3af; display: block;">Current Stage:</small>
					<span id="tf-det-stage" class="tf-badge badge-ready">-</span>
				</div>
				<div>
					<small style="color: #9ca3af; display: block;">Trial Date:</small>
					<span id="tf-det-trial-date" style="color: #ffffff;">-</span>
				</div>
				<div>
					<small style="color: #9ca3af; display: block;">Delivery Date:</small>
					<span id="tf-det-delivery-date" style="color: #ffffff;">-</span>
				</div>
			</div>

			<!-- Financial Summary -->
			<div class="tf-form-grid" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom: 16px; gap: 10px; text-align: center;">
				<div style="background: rgba(59, 130, 246, 0.15); padding: 10px; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.3);">
					<small style="color: #93c5fd; display: block;">Total Amount</small>
					<strong style="color: #60a5fa; font-size: 16px;">PKR <span id="tf-det-total">0.00</span></strong>
				</div>
				<div style="background: rgba(16, 185, 129, 0.15); padding: 10px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3);">
					<small style="color: #6ee7b7; display: block;">Advance Paid</small>
					<strong style="color: #34d399; font-size: 16px;">PKR <span id="tf-det-advance">0.00</span></strong>
				</div>
				<div style="background: rgba(239, 68, 68, 0.15); padding: 10px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">
					<small style="color: #fca5a5; display: block;">Remaining Balance</small>
					<strong style="color: #f87171; font-size: 16px;">PKR <span id="tf-det-balance">0.00</span></strong>
				</div>
			</div>

			<!-- Measurements Grid -->
			<div style="margin-bottom: 16px;">
				<h4 style="color: #cbd5e1; margin: 0 0 8px 0; font-size: 14px;"><span class="dashicons dashicons-edit"></span> Customer Measurements</h4>
				<div class="tf-form-grid" style="grid-template-columns: repeat(3, 1fr); gap: 8px; font-size: 12px;" id="tf-det-measurements-grid">
					<!-- Dynamically injected -->
				</div>
			</div>

			<!-- Notes & Cloth Details -->
			<div id="tf-det-notes-wrapper" style="margin-top: 12px; background: rgba(0,0,0,0.15); padding: 10px; border-radius: 6px;">
				<small style="color: #9ca3af; display: block;">Special Instructions & Cloth Details:</small>
				<p id="tf-det-notes" style="margin: 4px 0 0 0; color: #e2e8f0; font-size: 13px;"></p>
			</div>
		</div>
		<div class="tf-modal-footer">
			<button type="button" class="tf-btn tf-btn-secondary" id="tf-done-details-modal">Close</button>
			<button type="button" class="tf-btn tf-btn-primary" id="tf-print-details-btn">
				<span class="dashicons dashicons-printer"></span> Print Receipt
			</button>
		</div>
	</div>
</div>

<!-- Order Edit Modal (Module 2) -->
<div id="tf-order-edit-modal" class="tf-app tf-modal-overlay" style="display: none;">
	<div class="tf-modal-container" style="max-width: 650px;">
		<div class="tf-modal-header">
			<h3><span class="dashicons dashicons-edit"></span> Edit Order: <span id="tf-edit-order-no-title">-</span></h3>
			<button class="tf-modal-close" id="tf-close-order-edit-modal">&times;</button>
		</div>
		<form id="tf-edit-order-form">
			<input type="hidden" id="tf-edit-order-id">
			<div class="tf-modal-body">
				<div id="tf-edit-order-notice" class="tf-notice" style="display: none;"></div>

				<div class="tf-form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 12px; gap: 10px;">
					<div class="tf-form-group">
						<label for="tf-edit-garment">Garment Type</label>
						<select id="tf-edit-garment" class="tf-input">
							<option value="kameez_shalwar">Kameez Shalwar</option>
							<option value="kurta_pajama">Kurta Pajama</option>
							<option value="waistcoat">Waistcoat</option>
							<option value="prince_coat">Prince Coat</option>
							<option value="suit_2pc">Suit 2-Piece</option>
							<option value="suit_3pc">Suit 3-Piece</option>
							<option value="sherwani">Sherwani</option>
						</select>
					</div>
					<div class="tf-form-group">
						<label for="tf-edit-stage">Order Stage / Status</label>
						<select id="tf-edit-stage" class="tf-input">
							<option value="received">Received</option>
							<option value="cutting">Cutting</option>
							<option value="stitching">Stitching</option>
							<option value="pressing">Pressing</option>
							<option value="ready">Ready</option>
							<option value="delivered">Delivered</option>
						</select>
					</div>
				</div>

				<div class="tf-form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 12px; gap: 10px;">
					<div class="tf-form-group">
						<label for="tf-edit-trial-date">Trial Date</label>
						<input type="date" id="tf-edit-trial-date" class="tf-input">
					</div>
					<div class="tf-form-group">
						<label for="tf-edit-delivery-date">Delivery Date</label>
						<input type="date" id="tf-edit-delivery-date" class="tf-input">
					</div>
				</div>

				<div class="tf-form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 12px; gap: 10px;">
					<div class="tf-form-group">
						<label for="tf-edit-total-amount">Total Amount (PKR)</label>
						<input type="number" step="0.01" id="tf-edit-total-amount" class="tf-input" required>
					</div>
					<div class="tf-form-group">
						<label for="tf-edit-advance-amount">Advance Paid (PKR)</label>
						<input type="number" step="0.01" id="tf-edit-advance-amount" class="tf-input" required>
					</div>
				</div>

				<!-- Measurements Inputs -->
				<h4 style="color: #cbd5e1; margin: 12px 0 8px 0; font-size: 13px;">Edit Measurements (Inches)</h4>
				<div class="tf-form-grid" style="grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px;">
					<div class="tf-form-group"><label>Length</label><input type="text" id="tf-edit-m-length" class="tf-input"></div>
					<div class="tf-form-group"><label>Chest</label><input type="text" id="tf-edit-m-chest" class="tf-input"></div>
					<div class="tf-form-group"><label>Waist</label><input type="text" id="tf-edit-m-waist" class="tf-input"></div>
					<div class="tf-form-group"><label>Hip</label><input type="text" id="tf-edit-m-hip" class="tf-input"></div>
					<div class="tf-form-group"><label>Shoulder</label><input type="text" id="tf-edit-m-shoulder" class="tf-input"></div>
					<div class="tf-form-group"><label>Sleeves</label><input type="text" id="tf-edit-m-sleeves" class="tf-input"></div>
					<div class="tf-form-group"><label>Neck</label><input type="text" id="tf-edit-m-neck" class="tf-input"></div>
					<div class="tf-form-group"><label>Shalwar L.</label><input type="text" id="tf-edit-m-shalwar" class="tf-input"></div>
					<div class="tf-form-group"><label>Paucha</label><input type="text" id="tf-edit-m-paucha" class="tf-input"></div>
				</div>

				<div class="tf-form-group" style="margin-bottom: 12px;">
					<label for="tf-edit-special-notes">Notes & Instructions</label>
					<textarea id="tf-edit-special-notes" class="tf-input" rows="2"></textarea>
				</div>
			</div>
			<div class="tf-modal-footer">
				<button type="button" class="tf-btn tf-btn-secondary" id="tf-cancel-order-edit-modal">Cancel</button>
				<button type="submit" class="tf-btn tf-btn-accent" id="tf-save-order-edit-btn">
					<span class="dashicons dashicons-saved"></span> Save Order Changes
				</button>
			</div>
		</form>
	</div>
</div>

<!-- Customer History Modal (Module 3) -->
<div id="tf-customer-history-modal" class="tf-app tf-modal-overlay" style="display: none;">
	<div class="tf-modal-container" style="max-width: 700px;">
		<div class="tf-modal-header">
			<h3><span class="dashicons dashicons-backup"></span> Customer Order History: <span id="tf-hist-cust-name">-</span></h3>
			<button class="tf-modal-close" id="tf-close-history-modal">&times;</button>
		</div>
		<div class="tf-modal-body">
			<div class="tf-table-responsive">
				<table class="tf-table">
					<thead>
						<tr>
							<th>Order #</th>
							<th>Garment</th>
							<th>Delivery</th>
							<th>Stage</th>
							<th>Total</th>
							<th>Balance</th>
						</tr>
					</thead>
					<tbody id="tf-customer-history-tbody">
						<!-- Dynamically populated -->
					</tbody>
				</table>
			</div>
		</div>
		<div class="tf-modal-footer">
			<button type="button" class="tf-btn tf-btn-secondary" id="tf-done-history-modal">Close</button>
		</div>
	</div>
</div>
<?php endif; ?>
