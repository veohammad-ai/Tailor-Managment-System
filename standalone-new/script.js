/**
 * Tailor Flow PK - Standalone Frontend Controller
 * Powered by window.localStorage mock database logic.
 * Designed and Developed by Hammad Memon
 */

(function($) {
    'use strict';

    // ==========================================================================
    // 1. DATABASE SCHEMA & INITIALIZATION
    // ==========================================================================

    const DEFAULT_SETTINGS = {
        company_name: 'Tailor Flow PK',
        logo_url: '',
        phone: '+92 300 1234567',
        currency: 'PKR',
        address: 'Shop #12, Commercial Market, Lahore',
        receipt_footer: 'Thank you for choosing Tailor Flow PK!',
        timezone: 'Asia/Karachi'
    };

    const DEFAULT_USERS = [
        { id: 1, name: 'Hammad Memon', username: 'owner', email: 'owner@tailorflow.com', phone: '03001111111', role: 'tf_owner', status: 'active', last_login: '' },
        { id: 2, name: 'Sikandar Baba', username: 'manager', email: 'manager@tailorflow.com', phone: '03002222222', role: 'tf_manager', status: 'active', last_login: '' },
        { id: 3, name: 'Ayesha Khan', username: 'receptionist', email: 'ayesha@tailorflow.com', phone: '03003333333', role: 'tf_receptionist', status: 'active', last_login: '' },
        { id: 4, name: 'Ali Raza', username: 'cashier', email: 'ali@tailorflow.com', phone: '03004444444', role: 'tf_cashier', status: 'active', last_login: '' },
        { id: 5, name: 'Umer Tailor', username: 'karigar', email: 'umer@tailorflow.com', phone: '03005555555', role: 'tf_karigar', status: 'active', last_login: '' }
    ];

    const DEFAULT_CUSTOMERS = [
        { id: 1, customer_code: 'CUST-0001', name: 'Husnain', phone: '03348589959', city: 'Lahore' },
        { id: 2, customer_code: 'CUST-0002', name: 'Talha', phone: '03214567890', city: 'Karachi' },
        { id: 3, customer_code: 'CUST-0003', name: 'Sajawal', phone: '03129876543', city: 'Rawalpindi' }
    ];

    const DEFAULT_MEASUREMENTS = {
        "1": { length: '40"', chest: '38"', waist: '34"', hip: '40"', shoulder: '18"', sleeves: '24"', neck: '15.5"', shalwar_length: '38"', paucha: '8.5"' },
        "2": { length: '42"', chest: '40"', waist: '36"', hip: '42"', shoulder: '19"', sleeves: '25"', neck: '16"', shalwar_length: '39"', paucha: '9"' },
        "3": { length: '38"', chest: '36"', waist: '32"', hip: '38"', shoulder: '17.5"', sleeves: '23.5"', neck: '15"', shalwar_length: '37"', paucha: '8"' }
    };

    const DEFAULT_ORDERS = [
        { id: 1, order_number: 'TF-2026-0001', customer_id: 1, garment_type: 'Kameez Shalwar', quantity: 1, booking_date: '2026-07-20', trial_date: '2026-07-28', delivery_date: '2026-07-30', status: 'received', total_amount: 3500, advance_amount: 1000, balance_amount: 2500, cloth_details: 'Blue Cotton', special_notes: 'Collar shape round' },
        { id: 2, order_number: 'TF-2026-0002', customer_id: 2, garment_type: '2-Piece Suit', quantity: 1, booking_date: '2026-07-22', trial_date: '2026-07-29', delivery_date: '2026-07-31', status: 'cutting', total_amount: 12000, advance_amount: 5000, balance_amount: 7000, cloth_details: 'Grey Wool', special_notes: 'Double breasted' },
        { id: 3, order_number: 'TF-2026-0003', customer_id: 3, garment_type: 'Kurta Pajama', quantity: 2, booking_date: '2026-07-25', trial_date: '2026-07-29', delivery_date: '2026-07-30', status: 'ready', total_amount: 5000, advance_amount: 5000, balance_amount: 0, cloth_details: 'White Silk', special_notes: 'Simple stitching' }
    ];

    function initDb() {
        if (!localStorage.getItem('tf_settings')) localStorage.setItem('tf_settings', JSON.stringify(DEFAULT_SETTINGS));
        if (!localStorage.getItem('tf_users')) localStorage.setItem('tf_users', JSON.stringify(DEFAULT_USERS));
        if (!localStorage.getItem('tf_customers')) localStorage.setItem('tf_customers', JSON.stringify(DEFAULT_CUSTOMERS));
        if (!localStorage.getItem('tf_measurements')) localStorage.setItem('tf_measurements', JSON.stringify(DEFAULT_MEASUREMENTS));
        if (!localStorage.getItem('tf_orders')) localStorage.setItem('tf_orders', JSON.stringify(DEFAULT_ORDERS));
        if (!localStorage.getItem('tf_logs')) localStorage.setItem('tf_logs', JSON.stringify([]));
    }

    initDb();

    // Defensive getters to prevent JSON parse errors
    function getSettings() {
        try { return JSON.parse(localStorage.getItem('tf_settings')) || DEFAULT_SETTINGS; }
        catch(e) { return DEFAULT_SETTINGS; }
    }
    function setSettings(settings) { localStorage.setItem('tf_settings', JSON.stringify(settings)); }
    
    function getUsers() {
        try { return JSON.parse(localStorage.getItem('tf_users')) || DEFAULT_USERS; }
        catch(e) { return DEFAULT_USERS; }
    }
    function setUsers(users) { localStorage.setItem('tf_users', JSON.stringify(users)); }
    
    function getCustomers() {
        try { return JSON.parse(localStorage.getItem('tf_customers')) || DEFAULT_CUSTOMERS; }
        catch(e) { return DEFAULT_CUSTOMERS; }
    }
    function setCustomers(custs) { localStorage.setItem('tf_customers', JSON.stringify(custs)); }
    
    function getMeasurements() {
        try { return JSON.parse(localStorage.getItem('tf_measurements')) || DEFAULT_MEASUREMENTS; }
        catch(e) { return DEFAULT_MEASUREMENTS; }
    }
    function setMeasurements(meas) { localStorage.setItem('tf_measurements', JSON.stringify(meas)); }
    
    function getOrders() {
        try { return JSON.parse(localStorage.getItem('tf_orders')) || DEFAULT_ORDERS; }
        catch(e) { return DEFAULT_ORDERS; }
    }
    function setOrders(orders) { localStorage.setItem('tf_orders', JSON.stringify(orders)); }
    
    function getLogs() {
        try { return JSON.parse(localStorage.getItem('tf_logs')) || []; }
        catch(e) { return []; }
    }
    function setLogs(logs) { localStorage.setItem('tf_logs', JSON.stringify(logs)); }

    // Safe date formatter utility to prevent RangeError: Invalid time value
    function formatDate(dateVal, includeTime) {
        if (!dateVal) return 'N/A';
        try {
            var d = new Date(dateVal);
            if (isNaN(d.getTime())) return 'N/A';
            return includeTime ? d.toLocaleString() : d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
        } catch(e) {
            return 'N/A';
        }
    }

    function logActivity(userId, targetUserId, actionType, prevRole, newRole) {
        var logs = getLogs();
        var users = getUsers();
        var u = users.find(x => x.id == userId) || { name: 'System' };
        var t = users.find(x => x.id == targetUserId) || { name: '-' };
        
        var newLog = {
            id: logs.length + 1,
            user_id: userId,
            user_name: u.name,
            target_user_id: targetUserId,
            target_user_name: t.name,
            action_type: actionType,
            prev_role: prevRole || '-',
            new_role: newRole || '-',
            ip_address: '127.0.0.1',
            created_at: new Date().toISOString()
        };
        logs.unshift(newLog);
        setLogs(logs);
    }

    // ==========================================================================
    // 2. AUTHENTICATION & LOGIN FLOW
    // ==========================================================================

    var currentUser = null;
    try {
        currentUser = JSON.parse(localStorage.getItem('tf_current_user')) || null;
    } catch(e) {
        currentUser = null;
    }

    function checkAuth() {
        if (currentUser && currentUser.name) {
            $('#tf-login-wrapper').hide();
            $('#tf-dashboard-wrapper').show();
            initDashboardUI();
        } else {
            $('#tf-dashboard-wrapper').hide();
            $('#tf-login-wrapper').show();
            initLoginUI();
        }
    }

    function initLoginUI() {
        var settings = getSettings();
        $('.tf-dynamic-shop-name-upper').text((settings.company_name || 'TAILOR FLOW PK').toUpperCase());
        $('#tf-app-login-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            var username = $('#tf-app-login-username').val().trim().toLowerCase();
            var users = getUsers();

            var matchedUser = users.find(u => (u.username === username || u.email === username) && u.status === 'active');
            if (matchedUser) {
                matchedUser.last_login = new Date().toISOString();
                setUsers(users);
                
                localStorage.setItem('tf_current_user', JSON.stringify(matchedUser));
                currentUser = matchedUser;
                
                logActivity(matchedUser.id, matchedUser.id, 'user_login', '-', matchedUser.role);

                $('#tf-login-notice').removeClass('notice-error').addClass('notice-success').html('Logged in successfully! Loading portal...').slideDown(150);
                setTimeout(function() {
                    $('#tf-login-notice').hide();
                    checkAuth();
                }, 800);
            } else {
                $('#tf-login-notice').removeClass('notice-success').addClass('notice-error').html('Invalid username or account inactive.').slideDown(150);
            }
        });
    }

    $('#tf-app-logout-link').on('click', function(e) {
        e.preventDefault();
        if (currentUser) {
            logActivity(currentUser.id, currentUser.id, 'user_logout', currentUser.role, '-');
        }
        localStorage.removeItem('tf_current_user');
        currentUser = null;
        checkAuth();
    });

    // ==========================================================================
    // 3. UI GENERATION & PERMISSION ROUTING
    // ==========================================================================

    const ROLE_LABELS = {
        tf_owner: 'Owner',
        tf_manager: 'Manager',
        tf_receptionist: 'Receptionist',
        tf_cashier: 'Cashier',
        tf_karigar: 'Karigar'
    };

    function initDashboardUI() {
        var settings = getSettings();
        
        // 1. Dynamic branding updates
        var compName = settings.company_name || 'Tailor Flow PK';
        $('.tf-dynamic-shop-name').text(compName);
        var split = compName.split(' ', 2);
        $('.tf-dynamic-split-title').html($('<div>').text(split[0]).html() + (split[1] ? ' <span>' + $('<div>').text(split[1]).html() + '</span>' : ''));
        
        $('.tf-dynamic-user-name').text(currentUser.name);
        $('.tf-dynamic-user-initial').text(currentUser.name.charAt(0).toUpperCase());
        $('.tf-dynamic-user-role').text(ROLE_LABELS[currentUser.role] || 'Staff');
        $('.tf-inv-currency').text(settings.currency);
        
        if (settings.logo_url) {
            $('#tf-inv-logo-img').attr('src', settings.logo_url);
            $('#tf-inv-logo-wrapper').show();
        } else {
            $('#tf-inv-logo-wrapper').hide();
        }
        $('#tf-inv-shop-phone').text(settings.phone ? settings.phone : 'Custom Bespoke Tailoring');
        $('#tf-inv-shop-address').text(settings.address);
        $('#tf-inv-shop-addr-sep').text(settings.address ? ' | ' : '');
        $('#tf-inv-receipt-footer').text(settings.receipt_footer);

        // 2. Role based tab visibility filter
        $('.tf-nav-item').hide();
        $('.tf-nav-item[data-tab="overview"]').show();
        $('.tf-nav-item.tf-nav-logout').show();

        if (currentUser.role === 'tf_owner') {
            $('.tf-nav-item[data-tab="new-order"]').show();
            $('.tf-nav-item[data-tab="customers"]').show();
            $('.tf-nav-item[data-tab="karigar-ledger"]').show();
            $('.tf-nav-item[data-tab="reports"]').show();
            $('.tf-nav-item[data-tab="user-roles"]').show();
            $('.tf-nav-item[data-tab="settings"]').show();
        } else if (currentUser.role === 'tf_manager') {
            $('.tf-nav-item[data-tab="new-order"]').show();
            $('.tf-nav-item[data-tab="customers"]').show();
            $('.tf-nav-item[data-tab="karigar-ledger"]').show();
            $('.tf-nav-item[data-tab="reports"]').show();
        } else if (currentUser.role === 'tf_receptionist') {
            $('.tf-nav-item[data-tab="new-order"]').show();
            $('.tf-nav-item[data-tab="customers"]').show();
        } else if (currentUser.role === 'tf_cashier') {
            $('.tf-nav-item[data-tab="reports"]').show();
        }

        // Render sections data
        renderOverviewStats();
        renderOrdersTable();
        renderCustomersTable();
        renderStaffUsersTable();
        renderActivityLogsTable();
        renderKarigarLedger();
        renderReportsTab();

        // Populate settings form
        $('#tf-set-company-name').val(settings.company_name);
        $('#tf-set-logo-url').val(settings.logo_url);
        $('#tf-set-phone').val(settings.phone);
        $('#tf-set-currency').val(settings.currency);
        $('#tf-set-address').val(settings.address);
        $('#tf-set-receipt-footer').val(settings.receipt_footer);
        $('#tf-set-timezone').val(settings.timezone);

        // Load customers into order dropdown selection
        var customers = getCustomers();
        var $dropdown = $('#tf-order-customer-id');
        $dropdown.html('<option value="">-- Choose Existing Customer --</option>');
        $.each(customers, function(i, c) {
            $dropdown.append('<option value="' + c.id + '">' + c.name + ' (' + c.phone + ')</option>');
        });
    }

    // Tab Switching
    function switchTab(tabId) {
        $('.tf-nav-item').removeClass('active');
        $('.tf-nav-item[data-tab="' + tabId + '"]').addClass('active');
        $('.tf-tab-content').removeClass('active');
        $('#tf-tab-' + tabId).addClass('active');
        $('#tf-sidebar').removeClass('open');
    }

    $(document).on('click', '.tf-nav-item a[href^="#"]', function(e) {
        e.preventDefault();
        var tab = $(this).parent().attr('data-tab');
        if (tab) switchTab(tab);
    });

    $(document).on('click', '[data-switch-tab]', function(e) {
        e.preventDefault();
        var target = $(this).attr('data-switch-tab');
        if (target) switchTab(target);
    });

    // Mobile menu toggles
    $('#tf-mobile-toggle').on('click', function() {
        $('#tf-sidebar').addClass('open');
    });
    $('#tf-sidebar-close').on('click', function() {
        $('#tf-sidebar').removeClass('open');
    });

    // ==========================================================================
    // 4. OVERVIEW STATS & DIRECTORY RENDERING
    // ==========================================================================

    function renderOverviewStats() {
        var orders = getOrders();
        var customers = getCustomers();
        var settings = getSettings();
        var todayStr = new Date().toISOString().split('T')[0];

        var delivToday = orders.filter(o => o.delivery_date === todayStr && o.status !== 'delivered').length;
        var trialToday = orders.filter(o => o.trial_date === todayStr).length;
        var readyCount = orders.filter(o => o.status === 'ready').length;
        
        var pendingWages = orders.reduce((sum, o) => sum + (o.status !== 'delivered' ? (parseFloat(o.balance_amount) || 0) : 0), 0);
        var totalOrders = orders.length;
        var activeCustomers = customers.length;
        var totalRevenue = orders.reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0);

        $('.tf-stat-card').each(function() {
            var label = $(this).find('.tf-stat-label').text().trim();
            if (label === 'Delivery Today') {
                $(this).find('.tf-stat-value').text(delivToday);
            } else if (label === 'Trial Today') {
                $(this).find('.tf-stat-value').text(trialToday);
            } else if (label === 'Ready for Delivery') {
                $(this).find('.tf-stat-value').text(readyCount);
            } else if (label === 'Pending Payments') {
                $(this).find('.tf-stat-value').text(settings.currency + ' ' + pendingWages.toLocaleString());
            } else if (label === 'Total Orders') {
                $(this).find('.tf-stat-value').text(totalOrders);
            } else if (label === 'Active Customers') {
                $(this).find('.tf-stat-value').text(activeCustomers);
            } else if (label === 'Total Revenue') {
                $(this).find('.tf-stat-value').text(settings.currency + ' ' + totalRevenue.toLocaleString());
            }
        });
    }

    function renderOrdersTable() {
        var orders = getOrders();
        var customers = getCustomers();
        var settings = getSettings();
        var $tbody = $('#tf-overview-orders-tbody');

        $tbody.empty();
        if (orders.length === 0) {
            $tbody.append('<tr><td colspan="7" style="text-align: center; color: var(--tf-text-dim);">No customer orders found.</td></tr>');
            return;
        }

        $.each(orders, function(i, o) {
            var c = customers.find(x => x.id == o.customer_id) || { name: 'Unknown' };
            var trialDate = formatDate(o.trial_date, false);
            
            var row = '<tr id="tf-order-row-' + o.id + '">' +
                '<td><strong>' + o.order_number + '</strong></td>' +
                '<td>' + c.name + '</td>' +
                '<td>' + o.garment_type + ' (' + o.quantity + ')</td>' +
                '<td>' + trialDate + '</td>' +
                '<td>' +
                    '<select class="tf-stage-select tf-stage-' + o.status + '" data-order-id="' + o.id + '">' +
                        '<option value="received"' + (o.status === 'received' ? ' selected' : '') + '>Received</option>' +
                        '<option value="cutting"' + (o.status === 'cutting' ? ' selected' : '') + '>Cutting</option>' +
                        '<option value="stitching"' + (o.status === 'stitching' ? ' selected' : '') + '>Stitching</option>' +
                        '<option value="pressing"' + (o.status === 'pressing' ? ' selected' : '') + '>Pressing</option>' +
                        '<option value="ready"' + (o.status === 'ready' ? ' selected' : '') + '>Ready</option>' +
                        '<option value="delivered"' + (o.status === 'delivered' ? ' selected' : '') + '>Delivered</option>' +
                    '</select>' +
                '</td>' +
                '<td>' + settings.currency + ' ' + parseFloat(o.total_amount).toFixed(2) + '</td>' +
                '<td>' +
                    '<div style="display: flex; gap: 6px; align-items: center;">' +
                        '<button class="tf-btn-sm tf-btn-view-order" data-order-id="' + o.id + '" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">' +
                            '<span class="dashicons dashicons-visibility"></span> View' +
                        '</button>' +
                        '<button class="tf-btn-sm tf-btn-edit-order" data-order-id="' + o.id + '" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">' +
                            '<span class="dashicons dashicons-edit"></span> Edit' +
                        '</button>' +
                    '</div>' +
                '</td>' +
            '</tr>';
            $tbody.append(row);
        });
    }

    // Change stage handler
    $(document).on('change', '.tf-stage-select', function() {
        var $select = $(this);
        var orderId = $select.attr('data-order-id');
        var newStage = $select.val();
        var orders = getOrders();
        var o = orders.find(x => x.id == orderId);
        
        if (o) {
            var oldStage = o.status;
            o.status = newStage;
            setOrders(orders);
            
            $select.removeClass().addClass('tf-stage-select tf-stage-' + newStage);
            logActivity(currentUser.id, currentUser.id, 'status_change', oldStage, newStage);
            
            showNotice('Order stage updated to ' + newStage.toUpperCase() + '!', 'success');
            renderOverviewStats();
        }
    });

    function showNotice(msg, type) {
        var $toast = $('<div class="tf-notice notice-' + type + '" style="position: fixed; bottom: 20px; right: 20px; z-index: 999999; min-width: 250px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: none;">' + msg + '</div>');
        $('body').append($toast);
        $toast.fadeIn(250).delay(2000).fadeOut(300, function() { $(this).remove(); });
    }

    // ==========================================================================
    // 5. ORDER CREATION / INVOICING / DETAILS MODALS
    // ==========================================================================

    $('#tf-order-customer-id').off('change').on('change', function() {
        var val = $(this).val();
        if (val) {
            var meas = getMeasurements();
            var m = meas[val] || {};
            $('#tf-order-m-length').val(m.length || '');
            $('#tf-order-m-chest').val(m.chest || '');
            $('#tf-order-m-waist').val(m.waist || '');
            $('#tf-order-m-hip').val(m.hip || '');
            $('#tf-order-m-shoulder').val(m.shoulder || '');
            $('#tf-order-m-sleeves').val(m.sleeves || '');
            $('#tf-order-m-neck').val(m.neck || '');
            $('#tf-order-m-shalwar-length').val(m.shalwar_length || '');
            $('#tf-order-m-paucha').val(m.paucha || '');

            $('#tf-order-measurements-section').slideDown(200);
        } else {
            $('#tf-order-measurements-section').slideUp(200);
        }
    });

    $('#tf-create-order-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        var customerId = parseInt($('#tf-order-customer-id').val());
        var garmentType = $('#tf-order-garment-type').val();
        var qty = parseInt($('#tf-order-quantity').val()) || 1;
        var trialDate = $('#tf-order-trial-date').val();
        var delivDate = $('#tf-order-delivery-date').val();
        var totalAmt = parseFloat($('#tf-order-total-amount').val()) || 0;
        var advAmt = parseFloat($('#tf-order-advance-amount').val()) || 0;
        var stage = $('#tf-order-stage').val();
        var cloth = $('#tf-order-cloth-details').val();
        var notes = $('#tf-order-special-notes').val();

        if (!customerId || totalAmt <= 0) {
            alert('Please select a customer and enter a valid total amount.');
            return;
        }

        var orders = getOrders();
        var orderNum = 'TF-' + new Date().getFullYear() + '-' + String(orders.length + 1).padStart(4, '0');
        
        var meas = getMeasurements();
        meas[customerId] = {
            length: $('#tf-order-m-length').val(),
            chest: $('#tf-order-m-chest').val(),
            waist: $('#tf-order-m-waist').val(),
            hip: $('#tf-order-m-hip').val(),
            shoulder: $('#tf-order-m-shoulder').val(),
            sleeves: $('#tf-order-m-sleeves').val(),
            neck: $('#tf-order-m-neck').val(),
            shalwar_length: $('#tf-order-m-shalwar-length').val(),
            paucha: $('#tf-order-m-paucha').val()
        };
        setMeasurements(meas);

        var newOrder = {
            id: orders.length + 1,
            order_number: orderNum,
            customer_id: customerId,
            garment_type: garmentType,
            quantity: qty,
            booking_date: new Date().toISOString().split('T')[0],
            trial_date: trialDate,
            delivery_date: delivDate,
            status: stage,
            total_amount: totalAmt,
            advance_amount: advAmt,
            balance_amount: Math.max(0, totalAmt - advAmt),
            cloth_details: cloth,
            special_notes: notes
        };
        orders.unshift(newOrder);
        setOrders(orders);

        logActivity(currentUser.id, currentUser.id, 'order_created', '-', orderNum);

        var c = getCustomers().find(x => x.id == customerId) || { name: 'Anonymous', phone: '-' };
        
        $('#tf-inv-order-no').text(orderNum);
        $('#tf-inv-booking-date').text(formatDate(newOrder.booking_date, false));
        $('#tf-inv-trial-date').text(formatDate(trialDate, false));
        $('#tf-inv-delivery-date').text(formatDate(delivDate, false));
        $('#tf-inv-cust-name').text(c.name);
        $('#tf-inv-cust-phone').text(c.phone);
        $('#tf-inv-garment').text(garmentType);
        $('#tf-inv-qty').text(qty);
        $('#tf-inv-total').text(totalAmt.toFixed(2));
        $('#tf-inv-advance').text(advAmt.toFixed(2));
        $('#tf-inv-balance').text((totalAmt - advAmt).toFixed(2));
        
        if (notes) {
            $('#tf-inv-notes').text(notes);
            $('#tf-inv-notes-wrapper').show();
        } else {
            $('#tf-inv-notes-wrapper').hide();
        }

        $('#tf-create-order-form')[0].reset();
        $('#tf-order-measurements-section').hide();
        
        initDashboardUI();
        $('#tf-invoice-modal').fadeIn(250);
    });

    $('#tf-close-invoice-modal, #tf-done-invoice-btn').on('click', function() {
        $('#tf-invoice-modal').fadeOut(200);
        switchTab('overview');
    });

    $('#tf-print-invoice-btn').on('click', function() {
        window.print();
    });

    $(document).on('click', '.tf-btn-view-order', function(e) {
        e.preventDefault();
        var id = $(this).attr('data-order-id');
        var o = getOrders().find(x => x.id == id);
        if (!o) return;

        var c = getCustomers().find(x => x.id == o.customer_id) || { name: 'Anonymous', phone: '-' };
        var m = getMeasurements()[o.customer_id] || {};

        $('#tf-det-order-no').text(o.order_number);
        $('#tf-det-cust-name').text(c.name);
        $('#tf-det-cust-phone').text(c.phone);
        $('#tf-det-garment').text(o.garment_type + ' (Qty: ' + o.quantity + ')');
        $('#tf-det-stage').text(o.status.toUpperCase()).removeClass().addClass('tf-badge badge-' + o.status);
        $('#tf-det-trial-date').text(formatDate(o.trial_date, false));
        $('#tf-det-delivery-date').text(formatDate(o.delivery_date, false));
        $('#tf-det-total').text(o.total_amount.toFixed(2));
        $('#tf-det-advance').text(o.advance_amount.toFixed(2));
        $('#tf-det-balance').text(o.balance_amount.toFixed(2));

        if (o.cloth_details || o.special_notes) {
            $('#tf-det-notes').text((o.cloth_details ? 'Cloth: ' + o.cloth_details + ' | ' : '') + (o.special_notes || ''));
            $('#tf-det-notes-wrapper').show();
        } else {
            $('#tf-det-notes-wrapper').hide();
        }

        var mGridHtml = '';
        var mKeys = ['length', 'chest', 'waist', 'hip', 'shoulder', 'sleeves', 'neck', 'shalwar_length', 'paucha'];
        $.each(mKeys, function(i, k) {
            var val = m[k] || '-';
            var label = k.replace('_', ' ').toUpperCase();
            mGridHtml += '<div style="background: rgba(255,255,255,0.05); padding: 6px 10px; border-radius: 4px;">' +
                '<span style="color:#9ca3af;">' + label + ':</span> <strong style="color:#ffffff;">' + val + '</strong>' +
                '</div>';
        });
        $('#tf-det-measurements-grid').html(mGridHtml);

        $('#tf-order-details-modal').fadeIn(250);
    });

    $('#tf-close-details-modal').on('click', function() {
        $('#tf-order-details-modal').fadeOut(200);
    });

    $(document).on('click', '.tf-btn-edit-order', function(e) {
        e.preventDefault();
        var id = $(this).attr('data-order-id');
        var o = getOrders().find(x => x.id == id);
        if (!o) return;

        var m = getMeasurements()[o.customer_id] || {};

        $('#tf-edit-order-id').val(o.id);
        $('#tf-edit-order-no-title').text(o.order_number);
        
        $('#tf-edit-order-garment-type').val(o.garment_type);
        $('#tf-edit-order-quantity').val(o.quantity);
        $('#tf-edit-order-trial-date').val(o.trial_date);
        $('#tf-edit-order-delivery-date').val(o.delivery_date);
        $('#tf-edit-order-total-amount').val(o.total_amount);
        $('#tf-edit-order-advance-amount').val(o.advance_amount);
        $('#tf-edit-order-stage').val(o.status);
        $('#tf-edit-order-cloth-details').val(o.cloth_details);
        $('#tf-edit-order-special-notes').val(o.special_notes);

        $('#tf-edit-order-m-length').val(m.length || '');
        $('#tf-edit-order-m-chest').val(m.chest || '');
        $('#tf-edit-order-m-waist').val(m.waist || '');
        $('#tf-edit-order-m-hip').val(m.hip || '');
        $('#tf-edit-order-m-shoulder').val(m.shoulder || '');
        $('#tf-edit-order-m-sleeves').val(m.sleeves || '');
        $('#tf-edit-order-m-neck').val(m.neck || '');
        $('#tf-edit-order-m-shalwar-length').val(m.shalwar_length || '');
        $('#tf-edit-order-m-paucha').val(m.paucha || '');

        $('#tf-order-edit-modal').fadeIn(250);
    });

    $('#tf-close-order-edit-modal').on('click', function() {
        $('#tf-order-edit-modal').fadeOut(200);
    });

    $('#tf-edit-order-form').on('submit', function(e) {
        e.preventDefault();
        var id = $('#tf-edit-order-id').val();
        var orders = getOrders();
        var o = orders.find(x => x.id == id);
        if (!o) return;

        o.garment_type = $('#tf-edit-order-garment-type').val();
        o.quantity = parseInt($('#tf-edit-order-quantity').val()) || 1;
        o.trial_date = $('#tf-edit-order-trial-date').val();
        o.delivery_date = $('#tf-edit-order-delivery-date').val();
        o.total_amount = parseFloat($('#tf-edit-order-total-amount').val()) || 0;
        o.advance_amount = parseFloat($('#tf-edit-order-advance-amount').val()) || 0;
        o.balance_amount = Math.max(0, o.total_amount - o.advance_amount);
        o.status = $('#tf-edit-order-stage').val();
        o.cloth_details = $('#tf-edit-order-cloth-details').val();
        o.special_notes = $('#tf-edit-order-special-notes').val();

        setOrders(orders);

        var meas = getMeasurements();
        meas[o.customer_id] = {
            length: $('#tf-edit-order-m-length').val(),
            chest: $('#tf-edit-order-m-chest').val(),
            waist: $('#tf-edit-order-m-waist').val(),
            hip: $('#tf-edit-order-m-hip').val(),
            shoulder: $('#tf-edit-order-m-shoulder').val(),
            sleeves: $('#tf-edit-order-m-sleeves').val(),
            neck: $('#tf-edit-order-m-neck').val(),
            shalwar_length: $('#tf-edit-order-m-shalwar-length').val(),
            paucha: $('#tf-edit-order-m-paucha').val()
        };
        setMeasurements(meas);

        logActivity(currentUser.id, currentUser.id, 'order_updated', '-', o.order_number);

        $('#tf-order-edit-modal').fadeOut(200);
        showNotice('Order updated successfully!', 'success');
        initDashboardUI();
    });

    // ==========================================================================
    // 6. CUSTOMER MANAGEMENT & MEASUREMENTS MODALS
    // ==========================================================================

    function renderCustomersTable() {
        var custs = getCustomers();
        var $tbody = $('#tf-customers-tbody');
        $tbody.empty();

        if (custs.length === 0) {
            $tbody.append('<tr class="tf-no-records"><td colspan="5" style="text-align: center; color: var(--tf-text-dim);">No customer records found.</td></tr>');
            return;
        }

        $.each(custs, function(i, c) {
            var row = '<tr>' +
                '<td><strong>' + c.customer_code + '</strong></td>' +
                '<td>' + c.name + '</td>' +
                '<td>' + c.phone + '</td>' +
                '<td>' + (c.city ? c.city : '-') + '</td>' +
                '<td>' +
                    '<div style="display: flex; gap: 6px; align-items: center;">' +
                        '<button class="tf-btn-sm tf-btn-measurements" data-customer-id="' + c.id + '" data-customer-name="' + c.name + '">' +
                            '<span class="dashicons dashicons-edit"></span> Measurements' +
                        '</button>' +
                        '<button class="tf-btn-sm tf-btn-customer-history" data-customer-id="' + c.id + '" data-customer-name="' + c.name + '" style="background: rgba(139, 92, 246, 0.15); color: #c084fc; border: 1px solid rgba(139, 92, 246, 0.3);">' +
                            '<span class="dashicons dashicons-backup"></span> View History' +
                        '</button>' +
                    '</div>' +
                '</td>' +
            '</tr>';
            $tbody.append(row);
        });
    }

    $('#tf-toggle-customer-form').on('click', function() {
        $('#tf-customer-form-card').slideToggle(200);
    });
    $('#tf-close-customer-form, #tf-cancel-customer-form').on('click', function() {
        $('#tf-customer-form-card').slideUp(200);
    });

    $('#tf-add-customer-form').on('submit', function(e) {
        e.preventDefault();
        var name = $('#tf-cust-name').val().trim();
        var phone = $('#tf-cust-phone').val().trim();
        var city = $('#tf-cust-city').val().trim();
        var notes = $('#tf-cust-notes').val().trim();

        var custs = getCustomers();
        var code = 'CUST-' + String(custs.length + 1).padStart(4, '0');

        var newC = {
            id: custs.length + 1,
            customer_code: code,
            name: name,
            phone: phone,
            city: city,
            notes: notes
        };
        custs.unshift(newC);
        setCustomers(custs);

        logActivity(currentUser.id, currentUser.id, 'customer_created', '-', name);

        $('#tf-add-customer-form')[0].reset();
        $('#tf-customer-form-card').slideUp(200);

        showNotice('Customer profile created successfully!', 'success');
        initDashboardUI();
    });

    $(document).on('click', '.tf-btn-measurements', function() {
        var id = $(this).attr('data-customer-id');
        var name = $(this).attr('data-customer-name');
        var m = getMeasurements()[id] || {};

        $('#tf-m-customer-id').val(id);
        $('#tf-modal-customer-name').text(name);

        $('#tf-m-length').val(m.length || '');
        $('#tf-m-chest').val(m.chest || '');
        $('#tf-m-waist').val(m.waist || '');
        $('#tf-m-hip').val(m.hip || '');
        $('#tf-m-shoulder').val(m.shoulder || '');
        $('#tf-m-sleeves').val(m.sleeves || '');
        $('#tf-m-neck').val(m.neck || '');
        $('#tf-m-shalwar-length').val(m.shalwar_length || '');
        $('#tf-m-paucha').val(m.paucha || '');

        $('#tf-measurement-modal').fadeIn(250);
    });

    $('#tf-close-measurement-modal, #tf-cancel-measurement-modal').on('click', function() {
        $('#tf-measurement-modal').fadeOut(200);
    });

    $('#tf-measurement-form').on('submit', function(e) {
        e.preventDefault();
        var id = $('#tf-m-customer-id').val();
        var meas = getMeasurements();

        meas[id] = {
            length: $('#tf-m-length').val(),
            chest: $('#tf-m-chest').val(),
            waist: $('#tf-m-waist').val(),
            hip: $('#tf-m-hip').val(),
            shoulder: $('#tf-m-shoulder').val(),
            sleeves: $('#tf-m-sleeves').val(),
            neck: $('#tf-m-neck').val(),
            shalwar_length: $('#tf-m-shalwar-length').val(),
            paucha: $('#tf-m-paucha').val()
        };
        setMeasurements(meas);
        
        logActivity(currentUser.id, currentUser.id, 'measurement_updated', '-', 'Customer ID: ' + id);

        $('#tf-measurement-modal').fadeOut(200);
        showNotice('Measurements updated successfully!', 'success');
    });

    $(document).on('click', '.tf-btn-customer-history', function() {
        var id = $(this).attr('data-customer-id');
        var name = $(this).attr('data-customer-name');
        var orders = getOrders().filter(x => x.customer_id == id);
        var settings = getSettings();
        
        $('#tf-hist-cust-name').text(name);
        var $tbody = $('#tf-customer-history-tbody');
        $tbody.empty();

        if (orders.length === 0) {
            $tbody.append('<tr><td colspan="6" style="text-align: center; color: var(--tf-text-dim);">No booking history found.</td></tr>');
        } else {
            $.each(orders, function(i, o) {
                var delivDate = formatDate(o.delivery_date, false);
                $tbody.append('<tr>' +
                    '<td><strong>' + o.order_number + '</strong></td>' +
                    '<td>' + o.garment_type + ' (' + o.quantity + ')</td>' +
                    '<td>' + delivDate + '</td>' +
                    '<td><span class="tf-badge badge-' + o.status + '">' + o.status.toUpperCase() + '</span></td>' +
                    '<td>' + settings.currency + ' ' + o.total_amount + '</td>' +
                    '<td>' + settings.currency + ' ' + o.balance_amount + '</td>' +
                '</tr>');
            });
        }
        $('#tf-customer-history-modal').fadeIn(250);
    });

    $('#tf-close-history-modal, #tf-done-history-modal').on('click', function() {
        $('#tf-customer-history-modal').fadeOut(200);
    });

    // ==========================================================================
    // 7. KARIGAR LEDGER, REPORTS & SETTINGS
    // ==========================================================================

    function renderKarigarLedger() {
        var orders = getOrders();
        var settings = getSettings();
        
        var $tbody = $('#tf-karigar-wages-tbody');
        if ($tbody.length === 0) return;
        $tbody.empty();

        var karigarOrders = orders.filter(o => o.status === 'stitching' || o.status === 'ready' || o.status === 'delivered');
        if (karigarOrders.length === 0) {
            $tbody.append('<tr><td colspan="6" style="text-align: center; color: var(--tf-text-dim);">No karigar sewing records found.</td></tr>');
        } else {
            $.each(karigarOrders, function(i, o) {
                var wage = o.garment_type.includes('Suit') ? 1200 : 450;
                var paymentStatus = o.status === 'delivered' ? 'Paid' : 'Unpaid';
                var badgeClass = paymentStatus === 'Paid' ? 'badge-active' : 'badge-inactive';
                
                $tbody.append('<tr>' +
                    '<td><strong>' + o.order_number + '</strong></td>' +
                    '<td>Umer Tailor</td>' +
                    '<td>' + o.garment_type + '</td>' +
                    '<td>' + settings.currency + ' ' + wage + '</td>' +
                    '<td><span class="tf-badge ' + badgeClass + '">' + paymentStatus + '</span></td>' +
                    '<td>' +
                        (paymentStatus === 'Unpaid' ? '<button class="tf-btn-sm tf-pay-karigar-btn" data-order-id="' + o.id + '" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">Pay Wage</button>' : '-') +
                    '</td>' +
                '</tr>');
            });
        }
    }

    $(document).on('click', '.tf-pay-karigar-btn', function() {
        var id = $(this).attr('data-order-id');
        var orders = getOrders();
        var o = orders.find(x => x.id == id);
        if (o) {
            o.status = 'delivered';
            setOrders(orders);
            showNotice('Stitching wage paid to Umer Tailor!', 'success');
            initDashboardUI();
        }
    });

    function renderReportsTab() {
        var orders = getOrders();
        
        var totalRevenue = orders.reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0);
        var advancePaid = orders.reduce((sum, o) => sum + (parseFloat(o.advance_amount) || 0), 0);
        var balanceDue = orders.reduce((sum, o) => sum + (parseFloat(o.balance_amount) || 0), 0);

        $('#tf-rep-revenue').text(totalRevenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#tf-rep-advance').text(advancePaid.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#tf-rep-pending-balance').text(balanceDue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        
        var total = orders.length || 1;
        var stitchVal = Math.round((orders.filter(o => o.status === 'stitching').length / total) * 100);
        var readyVal = Math.round((orders.filter(o => o.status === 'ready').length / total) * 100);
        var delivVal = Math.round((orders.filter(o => o.status === 'delivered').length / total) * 100);

        $('.tf-progress-fill').each(function() {
            if ($(this).hasClass('purple')) {
                $(this).css('width', stitchVal + '%').parent().prev().find('.tf-progress-percent').text(stitchVal + '%');
            } else if ($(this).hasClass('amber')) {
                $(this).css('width', readyVal + '%').parent().prev().find('.tf-progress-percent').text(readyVal + '%');
            } else {
                $(this).css('width', delivVal + '%').parent().prev().find('.tf-progress-percent').text(delivVal + '%');
            }
        });
    }

    $('#tf-settings-form').on('submit', function(e) {
        e.preventDefault();
        var newSet = {
            company_name: $('#tf-set-company-name').val().trim(),
            logo_url: $('#tf-set-logo-url').val().trim(),
            phone: $('#tf-set-phone').val().trim(),
            currency: $('#tf-set-currency').val().trim(),
            address: $('#tf-set-address').val().trim(),
            receipt_footer: $('#tf-set-receipt-footer').val().trim(),
            timezone: $('#tf-set-timezone').val()
        };
        setSettings(newSet);
        
        logActivity(currentUser.id, currentUser.id, 'settings_updated', '-', newSet.company_name);

        showNotice('Settings saved successfully!', 'success');
        initDashboardUI();
    });

    $('#tf-upload-logo-btn').on('click', function(e) {
        e.preventDefault();
        var url = prompt('Enter Logo Image URL:', $('#tf-set-logo-url').val());
        if (url !== null) {
            $('#tf-set-logo-url').val(url);
        }
    });

    // ==========================================================================
    // 8. STAFF USER MANAGEMENT (ROLE CHANGES / ACTIVE STATUS)
    // ==========================================================================

    function renderStaffUsersTable() {
        var users = getUsers();
        var $tbody = $('#tf-staff-tbody');
        $tbody.empty();

        $.each(users, function(i, u) {
            var roleLabel = ROLE_LABELS[u.role] || 'Staff';
            var isSelf = (currentUser.id == u.id);
            var isSelfBadge = isSelf ? ' <span style="background:rgba(255,255,255,0.1); font-size:10px; padding:2px 6px; border-radius:10px;">(You)</span>' : '';

            var statusToggleBtn = isSelf ? '-' : '<button type="button" class="tf-status-toggle-badge ' + (u.status === 'active' ? 'badge-active' : 'badge-inactive') + '" data-user-id="' + u.id + '" data-current-status="' + u.status + '" style="cursor: pointer; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); font-weight:600; font-size:0.75rem;">' + (u.status === 'active' ? 'Active' : 'Inactive') + '</button>';

            var actionButtons = isSelf ? '-' : 
                '<button class="tf-btn-sm tf-delete-user-btn" data-user-id="' + u.id + '" data-user-name="' + u.name + '" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">' +
                    '<span class="dashicons dashicons-trash"></span> Delete' +
                '</button>';

            var roleSelect = isSelf ? roleLabel :
                '<select class="tf-input tf-user-role-select" style="width: 130px; height: 32px; padding: 0 6px; font-size: 12px;" data-user-id="' + u.id + '" data-user-name="' + u.name + '">' +
                    '<option value="tf_owner"' + (u.role === 'tf_owner' ? ' selected' : '') + '>Owner</option>' +
                    '<option value="tf_manager"' + (u.role === 'tf_manager' ? ' selected' : '') + '>Manager</option>' +
                    '<option value="tf_receptionist"' + (u.role === 'tf_receptionist' ? ' selected' : '') + '>Receptionist</option>' +
                    '<option value="tf_karigar"' + (u.role === 'tf_karigar' ? ' selected' : '') + '>Karigar</option>' +
                    '<option value="tf_cashier"' + (u.role === 'tf_cashier' ? ' selected' : '') + '>Cashier</option>' +
                '</select>';

            var lastLoginFmt = formatDate(u.last_login, true);

            var row = '<tr data-user-id="' + u.id + '">' +
                '<td><strong>' + u.name + '</strong>' + isSelfBadge + '</td>' +
                '<td><code>@' + u.username + '</code></td>' +
                '<td>' + u.phone + '</td>' +
                '<td>' + roleSelect + '</td>' +
                '<td>' + statusToggleBtn + '</td>' +
                '<td><small>' + lastLoginFmt + '</small></td>' +
                '<td>' + actionButtons + '</td>' +
            '</tr>';
            $tbody.append(row);
        });
    }

    $(document).on('click', '.tf-status-toggle-badge', function(e) {
        e.preventDefault();
        var id = $(this).attr('data-user-id');
        var cur = $(this).attr('data-current-status');
        var newStatus = cur === 'active' ? 'inactive' : 'active';
        
        var users = getUsers();
        var u = users.find(x => x.id == id);
        if (u) {
            u.status = newStatus;
            setUsers(users);
            logActivity(currentUser.id, u.id, 'status_change', cur, newStatus);
            showNotice('Status for ' + u.name + ' updated to ' + newStatus.toUpperCase() + '!', 'success');
            initDashboardUI();
        }
    });

    $(document).on('change', '.tf-user-role-select', function() {
        var id = $(this).attr('data-user-id');
        var name = $(this).attr('data-user-name');
        var newRole = $(this).val();

        var users = getUsers();
        var u = users.find(x => x.id == id);
        if (u) {
            var oldRole = u.role;
            u.role = newRole;
            setUsers(users);
            logActivity(currentUser.id, u.id, 'role_change', oldRole, newRole);
            showNotice('Role for ' + name + ' updated to ' + newRole.replace('tf_', '').toUpperCase() + '!', 'success');
            initDashboardUI();
        }
    });

    $(document).on('click', '.tf-delete-user-btn', function() {
        var id = $(this).attr('data-user-id');
        var name = $(this).attr('data-user-name');
        if (confirm('Are you sure you want to delete staff member ' + name + '?')) {
            var users = getUsers();
            var filtered = users.filter(x => x.id != id);
            setUsers(filtered);
            
            logActivity(currentUser.id, id, 'user_deleted', '-', 'Deleted ' + name);
            showNotice('Staff user deleted successfully.', 'success');
            initDashboardUI();
        }
    });

    $('#tf-open-add-user-btn').on('click', function() {
        $('#tf-add-user-modal').fadeIn(200);
    });
    $('#tf-close-add-user-modal, #tf-cancel-add-user').on('click', function() {
        $('#tf-add-user-modal').fadeOut(200);
    });

    $('#tf-add-user-form').on('submit', function(e) {
        e.preventDefault();
        var name = $('#tf-user-fullname').val().trim();
        var username = $('#tf-user-username').val().trim().toLowerCase();
        var email = $('#tf-user-email').val().trim();
        var phone = $('#tf-user-phone').val().trim();
        var role = $('#tf-user-role').val();

        var users = getUsers();
        var duplicate = users.find(u => u.username === username || u.email === email);
        if (duplicate) {
            alert('Duplicate username or email found! Staff member cannot be created.');
            return;
        }

        var newUser = {
            id: users.length + 1,
            name: name,
            username: username,
            email: email,
            phone: phone,
            role: role,
            status: 'active',
            last_login: ''
        };
        users.push(newUser);
        setUsers(users);

        logActivity(currentUser.id, newUser.id, 'new_staff', '-', role);

        $('#tf-add-user-modal').fadeOut(200);
        $('#tf-add-user-form')[0].reset();
        showNotice('New staff member added successfully!', 'success');
        initDashboardUI();
    });

    // ==========================================================================
    // 9. SECURITY AUDIT LOGS & SEARCH LOGIC
    // ==========================================================================

    function renderActivityLogsTable() {
        var logs = getLogs();
        var $tbody = $('#tf-activity-logs-tbody');
        if ($tbody.length === 0) return;
        $tbody.empty();

        if (logs.length === 0) {
            $tbody.append('<tr><td colspan="7" style="text-align: center; color: var(--tf-text-dim);">No activity found</td></tr>');
            return;
        }

        var recentLogs = logs.slice(0, 20);

        $.each(recentLogs, function(i, l) {
            var dateStr = formatDate(l.created_at, true);
            var actionLabel = l.action_type.replace('_', ' ').toUpperCase();
            var badgeClass = 'badge-ready';
            
            if (l.action_type === 'user_login' || l.action_type === 'new_staff') badgeClass = 'badge-active';
            else if (l.action_type === 'user_logout' || l.action_type === 'status_change') badgeClass = 'badge-warning';
            else if (l.action_type === 'user_deleted') badgeClass = 'badge-inactive';

            var row = '<tr>' +
                '<td><span class="tf-badge ' + badgeClass + '">' + actionLabel + '</span></td>' +
                '<td><strong>' + l.user_name + '</strong></td>' +
                '<td>' + l.target_user_name + '</td>' +
                '<td><span class="tf-badge badge-warning">' + l.prev_role + '</span></td>' +
                '<td><span class="tf-badge badge-ready">' + l.new_role + '</span></td>' +
                '<td>' + dateStr + '</td>' +
                '<td><code>' + l.ip_address + '</code></td>' +
            '</tr>';
            $tbody.append(row);
        });
    }

    $('#tf-global-search-input').on('input', function() {
        var query = $(this).val().trim().toLowerCase();
        var $dropdown = $('#tf-global-search-dropdown');
        
        if (query.length < 2) {
            $dropdown.hide().empty();
            return;
        }

        $dropdown.empty();

        var customers = getCustomers();
        var orders = getOrders();
        var users = getUsers();

        var foundC = customers.filter(c => c.name.toLowerCase().includes(query) || c.phone.includes(query));
        if (foundC.length) {
            $dropdown.append('<div style="padding: 6px 12px; background: rgba(255,255,255,0.05); font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Customers</div>');
            $.each(foundC, function(i, c) {
                $dropdown.append('<a href="#" class="tf-search-result-item" data-tab="customers" data-customer-id="' + c.id + '" data-customer-name="' + c.name + '">' +
                    '<span class="dashicons dashicons-admin-users"></span> ' + c.name + ' (' + c.phone + ')' +
                    '</a>');
            });
        }

        var foundO = orders.filter(o => o.order_number.toLowerCase().includes(query) || o.garment_type.toLowerCase().includes(query));
        if (foundO.length) {
            $dropdown.append('<div style="padding: 6px 12px; background: rgba(255,255,255,0.05); font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Orders</div>');
            $.each(foundO, function(i, o) {
                $dropdown.append('<a href="#" class="tf-search-result-item" data-tab="overview" data-order-id="' + o.id + '">' +
                    '<span class="dashicons dashicons-clipboard"></span> Order ' + o.order_number + ' - ' + o.garment_type +
                    '</a>');
            });
        }

        var foundS = users.filter(u => u.name.toLowerCase().includes(query) || u.username.toLowerCase().includes(query));
        if (foundS.length && currentUser.role === 'tf_owner') {
            $dropdown.append('<div style="padding: 6px 12px; background: rgba(255,255,255,0.05); font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Staff</div>');
            $.each(foundS, function(i, u) {
                $dropdown.append('<a href="#" class="tf-search-result-item" data-tab="user-roles">' +
                    '<span class="dashicons dashicons-shield"></span> ' + u.name + ' (@' + u.username + ')' +
                    '</a>');
            });
        }

        if ($dropdown.children().length) {
            $dropdown.show();
        } else {
            $dropdown.append('<div style="padding: 12px; text-align: center; color: #64748b; font-size: 0.85rem;">No results match query.</div>').show();
        }
    });

    $(document).on('click', '.tf-search-result-item', function(e) {
        e.preventDefault();
        var tab = $(this).attr('data-tab');
        var orderId = $(this).attr('data-order-id');
        var customerId = $(this).attr('data-customer-id');

        switchTab(tab);
        $('#tf-global-search-dropdown').hide().empty();
        $('#tf-global-search-input').val('');

        if (orderId) {
            setTimeout(function() {
                $('.tf-btn-view-order[data-order-id="' + orderId + '"]').trigger('click');
            }, 100);
        } else if (customerId) {
            setTimeout(function() {
                $('.tf-btn-measurements[data-customer-id="' + customerId + '"]').trigger('click');
            }, 100);
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.tf-header-search').length) {
            $('#tf-global-search-dropdown').hide();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.tf-modal-overlay').fadeOut(200);
        }
    });

    $(document).on('click', '.tf-modal-overlay', function(e) {
        if ($(e.target).hasClass('tf-modal-overlay')) {
            $(this).fadeOut(200);
        }
    });

    checkAuth();

})(jQuery);
