/**
 * Tailor Flow PK JavaScript - Production-Grade Role Security & Activity Logging
 * Designed and Developed by Sikandar Hayat Baba
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Helper: Get localized variables safely
         */
        function getAjaxUrl() {
            return (typeof tf_vars !== 'undefined' && tf_vars.ajax_url) ? tf_vars.ajax_url : '/wp-admin/admin-ajax.php';
        }

        function getNonce() {
            return (typeof tf_vars !== 'undefined' && tf_vars.nonce) ? tf_vars.nonce : '';
        }

        /**
         * 1. Sidebar Tab Switcher
         */
        function switchTab(tabId) {
            if (!tabId) return;

            $('.tf-nav-item').removeClass('active');
            $('.tf-nav-item[data-tab="' + tabId + '"]').addClass('active');

            $('.tf-tab-content').removeClass('active');
            $('#tf-tab-' + tabId).addClass('active');

            $('#tf-sidebar').removeClass('tf-sidebar-open');
        }

        $(document).off('click', '.tf-nav-item').on('click', '.tf-nav-item', function(e) {
            e.preventDefault();
            switchTab($(this).attr('data-tab'));
        });

        $(document).off('click', '[data-switch-tab]').on('click', '[data-switch-tab]', function(e) {
            e.preventDefault();
            switchTab($(this).attr('data-switch-tab'));
        });

        /**
         * 2. Mobile Sidebar Drawer Toggle
         */
        $('#tf-mobile-toggle').off('click').on('click', function(e) {
            e.stopPropagation();
            $('#tf-sidebar').toggleClass('tf-sidebar-open');
        });

        $('#tf-sidebar-close').off('click').on('click', function() {
            $('#tf-sidebar').removeClass('tf-sidebar-open');
        });

        $(document).off('click.tfMobile').on('click.tfMobile', function(e) {
            if ($(window).width() <= 992) {
                if (!$(e.target).closest('#tf-sidebar, #tf-mobile-toggle').length) {
                    $('#tf-sidebar').removeClass('tf-sidebar-open');
                }
            }
        });

        /**
         * 3. Customer Module: Toggle & Form Submit with Security & Double-Submission Prevention
         */
        $('#tf-toggle-customer-form').off('click').on('click', function() {
            $('#tf-customer-form-card').slideToggle(200);
            $('#tf-customer-notice').hide().empty();
        });

        $('#tf-close-customer-form, #tf-cancel-customer-form').off('click').on('click', function() {
            $('#tf-customer-form-card').slideUp(200);
            $('#tf-add-customer-form')[0].reset();
            $('#tf-customer-notice').hide().empty();
        });

        $('#tf-add-customer-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            clearTimeout(phoneCheckTimer);
            if (window.phoneCheckXhr) {
                try { window.phoneCheckXhr.abort(); } catch (err) {}
            }

            var $form = $(this);
            var $btn = $('#tf-save-customer-btn');
            var $notice = $('#tf-customer-notice');

            if ($form.data('submitting')) return;

            var name = $('#tf-cust-name').val().trim();
            var phone = $('#tf-cust-phone').val().trim();
            var city = $('#tf-cust-city').val().trim();

            if (!name || !phone) {
                $notice.removeClass('notice-success').addClass('notice-error')
                       .html('Name and Phone number are required fields.').slideDown(150);
                return;
            }

            $form.data('submitting', true);
            $btn.prop('disabled', true);
            $notice.hide().empty();

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_add_customer',
                    nonce: getNonce(),
                    name: name,
                    phone: phone,
                    city: city
                },
                dataType: 'json',
                success: function(response) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);

                    if (response.success) {
                        $notice.removeClass('notice-error').addClass('notice-success')
                               .html(response.data.message).slideDown(150);

                        $form[0].reset();

                        var c = response.data.customer;
                        var newRow = '<tr>' +
                            '<td><strong>' + $('<div>').text(c.customer_code).html() + '</strong></td>' +
                            '<td>' + $('<div>').text(c.name).html() + '</td>' +
                            '<td>' + $('<div>').text(c.phone).html() + '</td>' +
                            '<td>' + $('<div>').text(c.city ? c.city : '-').html() + '</td>' +
                            '<td>' +
                            '<button class="tf-btn-sm tf-btn-measurements" data-customer-id="' + c.id + '" data-customer-name="' + $('<div>').text(c.name).html() + '">' +
                            '<span class="dashicons dashicons-edit"></span> Measurements' +
                            '</button>' +
                            '</td>' +
                            '</tr>';

                        $('.tf-no-records').remove();
                        $('#tf-customers-tbody').prepend(newRow);
                        $('#tf-order-customer-id').append('<option value="' + c.id + '">' + $('<div>').text(c.name + ' (' + c.phone + ')').html() + '</option>');

                        setTimeout(function() {
                            $('#tf-customer-form-card').slideUp(300);
                            $notice.hide();
                        }, 1800);
                    } else {
                        $notice.removeClass('notice-success').addClass('notice-error')
                               .html(response.data.message || 'An error occurred.').slideDown(150);
                    }
                },
                error: function(xhr, status, error) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);
                    $notice.removeClass('notice-success').addClass('notice-error')
                           .html('AJAX request failed: ' + error).slideDown(150);
                }
            });
        });

        /**
         * 4. Measurement Module: Open Modal & Save Data
         */
        $(document).off('click', '.tf-btn-measurements').on('click', '.tf-btn-measurements', function(e) {
            e.preventDefault();

            var customerId = $(this).attr('data-customer-id');
            var customerName = $(this).attr('data-customer-name');

            $('#tf-m-customer-id').val(customerId);
            $('#tf-modal-customer-name').text(customerName);
            $('#tf-measurement-form')[0].reset();
            $('#tf-measurement-notice').hide().empty();

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_get_measurement',
                    nonce: getNonce(),
                    customer_id: customerId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.exists) {
                        var m = response.data.measurements || {};
                        $('#tf-m-length').val(m.length || '');
                        $('#tf-m-chest').val(m.chest || '');
                        $('#tf-m-waist').val(m.waist || '');
                        $('#tf-m-hip').val(m.hip || '');
                        $('#tf-m-shoulder').val(m.shoulder || '');
                        $('#tf-m-sleeves').val(m.sleeves || '');
                        $('#tf-m-neck').val(m.neck || '');
                        $('#tf-m-shalwar-length').val(m.shalwar_length || '');
                        $('#tf-m-paucha').val(m.paucha || '');
                        $('#tf-m-notes').val(response.data.notes || '');
                    }
                }
            });

            $('#tf-measurement-modal').fadeIn(200);
        });

        $('#tf-close-measurement-modal, #tf-cancel-measurement-modal').off('click').on('click', function() {
            $('#tf-measurement-modal').fadeOut(200);
            $('#tf-measurement-notice').hide().empty();
        });

        $('#tf-measurement-modal').off('click').on('click', function(e) {
            if ($(e.target).is('#tf-measurement-modal')) {
                $('#tf-measurement-modal').fadeOut(200);
                $('#tf-measurement-notice').hide().empty();
            }
        });

        $('#tf-measurement-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $('#tf-save-measurement-btn');
            var $notice = $('#tf-measurement-notice');
            var customerId = $('#tf-m-customer-id').val();

            if ($form.data('submitting')) return;
            if (!customerId || customerId === '0') {
                $notice.removeClass('notice-success').addClass('notice-error')
                       .html('Invalid Customer selected.').slideDown(150);
                return;
            }

            $form.data('submitting', true);
            $btn.prop('disabled', true);
            $notice.hide().empty();

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_save_measurement',
                    nonce: getNonce(),
                    customer_id: customerId,
                    length: $('#tf-m-length').val().trim(),
                    chest: $('#tf-m-chest').val().trim(),
                    waist: $('#tf-m-waist').val().trim(),
                    hip: $('#tf-m-hip').val().trim(),
                    shoulder: $('#tf-m-shoulder').val().trim(),
                    sleeves: $('#tf-m-sleeves').val().trim(),
                    neck: $('#tf-m-neck').val().trim(),
                    shalwar_length: $('#tf-m-shalwar-length').val().trim(),
                    paucha: $('#tf-m-paucha').val().trim(),
                    notes: $('#tf-m-notes').val().trim()
                },
                dataType: 'json',
                success: function(response) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);

                    if (response.success) {
                        $notice.removeClass('notice-error').addClass('notice-success')
                               .html(response.data.message).slideDown(150);

                        setTimeout(function() {
                            $('#tf-measurement-modal').fadeOut(300);
                            $notice.hide();
                        }, 1500);
                    } else {
                        $notice.removeClass('notice-success').addClass('notice-error')
                               .html(response.data.message || 'Failed to save measurements.').slideDown(150);
                    }
                },
                error: function(xhr, status, error) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);
                    $notice.removeClass('notice-success').addClass('notice-error')
                           .html('AJAX request failed: ' + error).slideDown(150);
                }
            });
        });

        /**
         * 5. Order Module: Create Order via AJAX & Render Printable Invoice
         */
        $('#tf-create-order-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $('#tf-create-order-btn');
            var $notice = $('#tf-order-notice');

            if ($form.data('submitting')) return;

            var customerId = $('#tf-order-customer-id').val();
            var totalAmount = $('#tf-order-total-amount').val();

            if (!customerId) {
                $notice.removeClass('notice-success').addClass('notice-error')
                       .html('Please select a customer.').slideDown(150);
                return;
            }

            if (!totalAmount || parseFloat(totalAmount) <= 0) {
                $notice.removeClass('notice-success').addClass('notice-error')
                       .html('Total amount must be greater than zero.').slideDown(150);
                return;
            }

            $form.data('submitting', true);
            $btn.prop('disabled', true);
            $notice.hide().empty();

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_create_order',
                    nonce: getNonce(),
                    customer_id: customerId,
                    garment_type: $('#tf-order-garment-type').val(),
                    quantity: $('#tf-order-quantity').val(),
                    trial_date: $('#tf-order-trial-date').val(),
                    delivery_date: $('#tf-order-delivery-date').val(),
                    total_amount: totalAmount,
                    advance_amount: $('#tf-order-advance-amount').val(),
                    stage: $('#tf-order-stage').val(),
                    cloth_details: $('#tf-order-cloth-details').val(),
                    special_notes: $('#tf-order-special-notes').val(),
                    length: $('#tf-order-m-length').val(),
                    chest: $('#tf-order-m-chest').val(),
                    waist: $('#tf-order-m-waist').val(),
                    hip: $('#tf-order-m-hip').val(),
                    shoulder: $('#tf-order-m-shoulder').val(),
                    sleeves: $('#tf-order-m-sleeves').val(),
                    neck: $('#tf-order-m-neck').val(),
                    shalwar_length: $('#tf-order-m-shalwar-length').val(),
                    paucha: $('#tf-order-m-paucha').val()
                },
                dataType: 'json',
                success: function(response) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);

                    if (response.success) {
                        var inv = response.data.invoice;
                        var stageKey = (response.data.stage || 'received').toLowerCase();

                        $('#tf-inv-order-no').text(inv.order_number);
                        $('#tf-inv-booking-date').text(inv.booking_date);
                        $('#tf-inv-trial-date').text(inv.trial_date);
                        $('#tf-inv-delivery-date').text(inv.delivery_date);
                        $('#tf-inv-cust-name').text(inv.customer.name);
                        $('#tf-inv-cust-phone').text(inv.customer.phone);
                        $('#tf-inv-garment').text(inv.garment_type);
                        $('#tf-inv-qty').text(inv.quantity);
                        $('#tf-inv-total').text(inv.total_amount);
                        $('#tf-inv-advance').text(inv.advance_amount);
                        $('#tf-inv-balance').text(inv.balance_amount);

                        if (inv.special_notes) {
                            $('#tf-inv-notes').text(inv.special_notes);
                            $('#tf-inv-notes-wrapper').show();
                        } else {
                            $('#tf-inv-notes-wrapper').hide();
                        }

                        var stageOptionsHtml = '<select class="tf-stage-select tf-stage-' + stageKey + '" data-order-id="' + response.data.order_id + '">' +
                            '<option value="received"' + (stageKey === 'received' ? ' selected' : '') + '>Received</option>' +
                            '<option value="cutting"' + (stageKey === 'cutting' ? ' selected' : '') + '>Cutting</option>' +
                            '<option value="stitching"' + (stageKey === 'stitching' ? ' selected' : '') + '>Stitching</option>' +
                            '<option value="pressing"' + (stageKey === 'pressing' ? ' selected' : '') + '>Pressing</option>' +
                            '<option value="ready"' + (stageKey === 'ready' ? ' selected' : '') + '>Ready</option>' +
                            '<option value="delivered"' + (stageKey === 'delivered' ? ' selected' : '') + '>Delivered</option>' +
                            '</select>';

                        var newOrderRow = '<tr id="tf-order-row-' + response.data.order_id + '">' +
                            '<td><strong>' + $('<div>').text(inv.order_number).html() + '</strong></td>' +
                            '<td>' + $('<div>').text(inv.customer.name).html() + '</td>' +
                            '<td>' + $('<div>').text(inv.garment_type + ' (' + inv.quantity + ')').html() + '</td>' +
                            '<td>' + $('<div>').text(inv.delivery_date).html() + '</td>' +
                            '<td>' + stageOptionsHtml + '</td>' +
                            '<td>PKR ' + inv.total_amount + '</td>' +
                            '</tr>';
                        $('.tf-no-orders-row').remove();
                        $('#tf-overview-orders-tbody').prepend(newOrderRow);

                        var $totCount = $('#tf-overview-total-orders');
                        var currentCount = parseInt($totCount.text()) || 0;
                        $totCount.text(currentCount + 1);

                        $form[0].reset();
                        $('#tf-order-measurements-section').hide();

                        $('#tf-invoice-modal').fadeIn(250);

                    } else {
                        $notice.removeClass('notice-success').addClass('notice-error')
                               .html(response.data.message || 'Error creating order.').slideDown(150);
                    }
                },
                error: function(xhr, status, error) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);
                    $notice.removeClass('notice-success').addClass('notice-error')
                           .html('AJAX request failed: ' + error).slideDown(150);
                }
            });
        });

        /**
         * 6. Order Stage Change via AJAX
         */
        $(document).off('change', '.tf-stage-select').on('change', '.tf-stage-select', function() {
            var $select = $(this);
            var orderId = $select.attr('data-order-id');
            var newStage = $select.val();

            $select.removeClass('tf-stage-received tf-stage-cutting tf-stage-stitching tf-stage-pressing tf-stage-ready tf-stage-delivered')
                   .addClass('tf-stage-' + newStage);

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_update_order_stage',
                    nonce: getNonce(),
                    order_id: orderId,
                    stage: newStage
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $select.css('outline', '2px solid #10b981');
                        setTimeout(function() {
                            $select.css('outline', 'none');
                        }, 1000);
                    } else {
                        alert(response.data.message || 'Error updating order stage.');
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX request failed: ' + error);
                }
            });
        });

        /**
         * 7. Reports Module: Filter Type Switcher & AJAX Fetch
         */
        $('#tf-report-type').off('change').on('change', function() {
            var type = $(this).val();

            $('#tf-filter-monthly-wrap, #tf-filter-yearly-wrap, #tf-filter-custom-start-wrap, #tf-filter-custom-end-wrap').hide();

            if (type === 'monthly') {
                $('#tf-filter-monthly-wrap').show();
            } else if (type === 'yearly') {
                $('#tf-filter-yearly-wrap').show();
            } else if (type === 'custom') {
                $('#tf-filter-custom-start-wrap, #tf-filter-custom-end-wrap').show();
            }
        });

        $('#tf-reports-filter-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $('#tf-apply-report-btn');

            if ($form.data('submitting')) return;

            $form.data('submitting', true);
            $btn.prop('disabled', true);

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_get_reports_data',
                    nonce: getNonce(),
                    report_type: $('#tf-report-type').val(),
                    month: $('#tf-report-month').val(),
                    year: $('#tf-report-year').val(),
                    start_date: $('#tf-report-start-date').val(),
                    end_date: $('#tf-report-end-date').val()
                },
                dataType: 'json',
                success: function(response) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);

                    if (response.success) {
                        var d = response.data;
                        $('#tf-rep-revenue').text(d.revenue);
                        $('#tf-rep-advance').text(d.advance);
                        $('#tf-rep-pending-balance').text(d.pending_balance);
                        $('#tf-rep-karigar-paid').text(d.karigar_paid);
                        $('#tf-rep-karigar-pending').text(d.karigar_pending);

                        if (d.period_label) {
                            $('#tf-report-period-subtitle').text(d.period_label);
                        }
                    } else {
                        alert(response.data.message || 'Error fetching report data.');
                    }
                },
                error: function(xhr, status, error) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);
                    alert('AJAX request failed: ' + error);
                }
            });
        });

        /**
         * 8. Staff Table Instant Search & Role Filtering
         */
        function filterStaffTable() {
            var searchVal = ($('#tf-staff-search').val() || '').trim().toLowerCase();
            var roleVal = $('#tf-staff-role-filter').val();
            var statusVal = $('#tf-staff-status-filter').val();

            $('#tf-staff-tbody tr').each(function() {
                var $row = $(this);
                var name = ($row.attr('data-name') || '').toLowerCase();
                var username = ($row.attr('data-username') || '').toLowerCase();
                var mobile = ($row.attr('data-mobile') || '').toLowerCase();
                var role = $row.attr('data-tf-role') || '';
                var status = $row.attr('data-tf-status') || 'active';

                var matchesSearch = (!searchVal) || (name.indexOf(searchVal) !== -1 || username.indexOf(searchVal) !== -1 || mobile.indexOf(searchVal) !== -1);
                var matchesRole = (roleVal === 'all') || (role === roleVal);
                var matchesStatus = (statusVal === 'all') || (status === statusVal);

                if (matchesSearch && matchesRole && matchesStatus) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        }

        $(document).off('keyup input', '#tf-staff-search').on('keyup input', '#tf-staff-search', filterStaffTable);
        $(document).off('change', '#tf-staff-role-filter').on('change', '#tf-staff-role-filter', filterStaffTable);
        $(document).off('change', '#tf-staff-status-filter').on('change', '#tf-staff-status-filter', filterStaffTable);

        function showStaffNotice(message, isError) {
            var $notice = $('#tf-staff-notice');
            if ($notice.length === 0) {
                $notice = $('<div id="tf-staff-notice" class="tf-notice" style="display: none; margin-bottom: 16px;"></div>');
                $('#tf-staff-roles-table').before($notice);
            }

            if (isError) {
                $notice.removeClass('notice-success').addClass('notice-error').html(message).slideDown(150);
            } else {
                $notice.removeClass('notice-error').addClass('notice-success').html(message).slideDown(150);
            }

            setTimeout(function() {
                $notice.slideUp(300);
            }, 3500);
        }

        /**
         * User Status Toggle Handler (QA Fix - Instant UI update without page reload)
         */
        $(document).off('click', '.tf-toggle-user-status-btn, .tf-status-toggle-badge').on('click', '.tf-toggle-user-status-btn, .tf-status-toggle-badge', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $trigger = $(e.target).closest('.tf-toggle-user-status-btn, .tf-status-toggle-badge');
            if (!$trigger.length) {
                $trigger = $(this);
            }

            var userId = $trigger.attr('data-user-id') || $trigger.closest('tr').attr('data-user-id');
            if (!userId) {
                console.error('Tailor Flow Status Toggle Error: Unable to determine target user_id.');
                return;
            }

            var $row = $trigger.closest('tr');
            if (!$row.length) {
                $row = $('tr[data-user-id="' + userId + '"]');
            }

            var currStatus = $trigger.attr('data-current-status') || $row.attr('data-tf-status') || 'active';
            var newStatus = (currStatus === 'active') ? 'inactive' : 'active';

            $trigger.prop('disabled', true);

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_update_user_status',
                    nonce: getNonce(),
                    user_id: userId,
                    status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    $trigger.prop('disabled', false);
                    if (response && response.success) {
                        var updatedStatus = (response.data && response.data.status) ? response.data.status : newStatus;

                        // 1. Update row attribute for filters
                        $row.attr('data-tf-status', updatedStatus);

                        // 2. Update Status badge element in column instantly
                        var $badge = $row.find('.tf-status-toggle-badge');
                        if ($badge.length) {
                            if (updatedStatus === 'active') {
                                $badge.removeClass('badge-inactive')
                                      .addClass('badge-active')
                                      .css({
                                          'background': 'rgba(16, 185, 129, 0.15)',
                                          'color': '#34d399',
                                          'border': '1px solid rgba(16, 185, 129, 0.3)'
                                      })
                                      .attr('data-current-status', 'active');
                                $badge.find('.tf-status-label').text('Active');
                            } else {
                                $badge.removeClass('badge-active')
                                      .addClass('badge-inactive')
                                      .css({
                                          'background': 'rgba(239, 68, 68, 0.15)',
                                          'color': '#f87171',
                                          'border': '1px solid rgba(239, 68, 68, 0.3)'
                                      })
                                      .attr('data-current-status', 'inactive');
                                $badge.find('.tf-status-label').text('Inactive');
                            }
                        }

                        // 3. Update action button attribute if present
                        $row.find('.tf-toggle-user-status-btn').attr('data-current-status', updatedStatus);

                        // 4. Re-evaluate table row filter if status filter is active
                        if (typeof filterStaffTable === 'function') {
                            filterStaffTable();
                        }

                        // 5. Display success notification toast without page reload
                        showStaffNotice(response.data.message || 'Status updated successfully!', false);
                    } else {
                        var errMsg = (response && response.data && response.data.message) ? response.data.message : 'Failed to update user status.';
                        showStaffNotice(errMsg, true);
                    }
                },
                error: function(xhr, status, error) {
                    $trigger.prop('disabled', false);
                    showStaffNotice('AJAX request failed: ' + error, true);
                }
            });
        });

        /**
         * Delete Staff User Handler
         */
        $(document).off('click', '.tf-delete-user-btn').on('click', '.tf-delete-user-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var userId = $btn.attr('data-user-id');
            var userName = $btn.attr('data-user-name') || 'Staff User';

            if (!confirm('WARNING: Are you sure you want to PERMANENTLY DELETE staff user "' + userName + '"?\nThis action cannot be undone.')) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_delete_user',
                    nonce: getNonce(),
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to delete staff user.');
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    alert('AJAX error: ' + error);
                }
            });
        });

        /**
         * 9. Production-Grade User Role Confirmation & Security Workflow
         */
        var pendingUserId = 0;
        var pendingNewRole = '';

        $(document).off('click', '.tf-save-user-role-btn').on('click', '.tf-save-user-role-btn', function(e) {
            e.preventDefault();

            var $btn = $(this);
            pendingUserId = $btn.attr('data-user-id');
            var $select = $('.tf-user-role-select[data-user-id="' + pendingUserId + '"]');

            var targetName = $select.attr('data-user-name') || 'User #' + pendingUserId;
            var currentRole = $select.attr('data-current-role') || 'None';
            pendingNewRole = $select.val();

            if (!pendingNewRole) {
                alert('Please select a role from the dropdown menu first.');
                return;
            }

            var newRoleText = $select.find('option:selected').text();

            $('#tf-confirm-target-user').text(targetName);
            $('#tf-confirm-current-role').text(currentRole);
            $('#tf-confirm-new-role').text(newRoleText);

            $('#tf-role-confirm-input').val('');
            $('#tf-execute-role-change-btn').prop('disabled', true).css('opacity', '0.5');

            $('#tf-role-confirm-modal').fadeIn(200);
        });

        $('#tf-role-confirm-input').off('input').on('input', function() {
            var val = $(this).val().trim().toUpperCase();
            if (val === 'CONFIRM') {
                $('#tf-execute-role-change-btn').prop('disabled', false).css('opacity', '1');
            } else {
                $('#tf-execute-role-change-btn').prop('disabled', true).css('opacity', '0.5');
            }
        });

        $('#tf-execute-role-change-btn').off('click').on('click', function(e) {
            e.preventDefault();

            if (!pendingUserId || !pendingNewRole) return;

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_update_user_role',
                    nonce: getNonce(),
                    user_id: pendingUserId,
                    role_name: pendingNewRole
                },
                dataType: 'json',
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $('#tf-role-confirm-modal').fadeOut(200);
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error updating user role.');
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    alert('AJAX request failed: ' + error);
                }
            });
        });

        $('#tf-close-role-confirm-modal, #tf-cancel-role-confirm-modal').off('click').on('click', function() {
            $('#tf-role-confirm-modal').fadeOut(200);
        });

        $('#tf-role-confirm-modal').off('click').on('click', function(e) {
            if ($(e.target).is('#tf-role-confirm-modal')) {
                $('#tf-role-confirm-modal').fadeOut(200);
            }
        });

        /**
         * 9. Printable Invoice Modal Actions & Print Trigger
         */
        $('#tf-print-invoice-btn').off('click').on('click', function(e) {
            e.preventDefault();
            window.print();
        });

        $('#tf-close-invoice-modal, #tf-done-invoice-btn').off('click').on('click', function() {
            $('#tf-invoice-modal').fadeOut(200);
        });

        $('#tf-invoice-modal').off('click').on('click', function(e) {
            if ($(e.target).is('#tf-invoice-modal')) {
                $('#tf-invoice-modal').fadeOut(200);
            }
        });

        /**
         * 10. Global Top Bar Live AJAX Search
         */
        var searchTimeout = null;
        $('#tf-global-search-input').off('keyup input').on('keyup input', function() {
            var query = $(this).val().trim();
            var $dropdown = $('#tf-global-search-dropdown');

            if (query.length < 2) {
                $dropdown.hide().empty();
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: getAjaxUrl(),
                    type: 'POST',
                    data: {
                        action: 'tf_global_search',
                        nonce: getNonce(),
                        query: query
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.results && response.data.results.length > 0) {
                            var html = '<div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--tf-text-muted); margin-bottom: 8px;">Search Results (' + response.data.results.length + ')</div>';
                            $.each(response.data.results, function(i, res) {
                                var badgeClass = 'badge-ready';
                                if (res.type === 'Staff User') badgeClass = 'badge-stitching';
                                else if (res.type === 'Customer') badgeClass = 'badge-active';
                                else if (res.type === 'Order') badgeClass = 'badge-warning';
                                else if (res.type === 'Measurement') badgeClass = 'badge-ready';

                                html += '<div class="tf-search-result-item" data-tab="' + res.tab + '" data-order-id="' + (res.order_id || 0) + '" data-customer-id="' + (res.customer_id || 0) + '" style="padding: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; border-radius: 4px; transition: background 0.15s ease;">' +
                                    '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                                    '<strong style="color: #ffffff; font-size: 13px;">' + $('<div>').text(res.title).html() + '</strong>' +
                                    '<span class="tf-badge ' + badgeClass + '" style="font-size: 10px;">' + $('<div>').text(res.type).html() + '</span>' +
                                    '</div>' +
                                    '<div style="color: #9ca3af; font-size: 11px; margin-top: 2px;">' + $('<div>').text(res.subtitle).html() + '</div>' +
                                    '</div>';
                            });
                            $dropdown.html(html).show();
                        } else {
                            $dropdown.html('<div style="padding: 12px; color: #9ca3af; text-align: center; font-size: 12px;">No matching staff, customers, orders, or measurements found.</div>').show();
                        }
                    }
                });
            }, 300);
        });

        $(document).off('click', '.tf-search-result-item').on('click', '.tf-search-result-item', function() {
            var $item = $(this);
            var tab = $item.attr('data-tab');
            var orderId = $item.attr('data-order-id');
            var customerId = $item.attr('data-customer-id');

            if (tab) {
                switchTab(tab);
                $('#tf-global-search-dropdown').hide().empty();
                $('#tf-global-search-input').val('');

                if (orderId && orderId !== '0') {
                    setTimeout(function() {
                        $('.tf-btn-view-order[data-order-id="' + orderId + '"]').trigger('click');
                    }, 100);
                } else if (customerId && customerId !== '0') {
                    setTimeout(function() {
                        if ($('.tf-btn-measurements[data-customer-id="' + customerId + '"]').length) {
                            $('.tf-btn-measurements[data-customer-id="' + customerId + '"]').trigger('click');
                        }
                    }, 100);
                }
            }
        });

        $(document).off('click.tfSearch').on('click.tfSearch', function(e) {
            if (!$(e.target).closest('.tf-header-search').length) {
                $('#tf-global-search-dropdown').hide();
            }
        });

        /**
         * 11. Add Staff User Modal & Form Submit (QA Fixes)
         */
        $(document).off('click', '#tf-open-add-user-btn').on('click', '#tf-open-add-user-btn', function(e) {
            e.preventDefault();
            $('#tf-add-user-form')[0].reset();
            $('#tf-add-user-notice').hide().empty();
            $('#tf-save-new-user-btn').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Create Staff User');
            $('#tf-add-user-modal').fadeIn(200);
        });

        $(document).off('click', '#tf-close-add-user-modal, #tf-cancel-add-user-modal').on('click', '#tf-close-add-user-modal, #tf-cancel-add-user-modal', function(e) {
            e.preventDefault();
            $('#tf-add-user-modal').fadeOut(200);
        });

        $(document).off('click', '#tf-add-user-modal').on('click', '#tf-add-user-modal', function(e) {
            if ($(e.target).is('#tf-add-user-modal')) {
                $('#tf-add-user-modal').fadeOut(200);
            }
        });

        $(document).on('keydown.tfAddUser', function(e) {
            if ((e.key === 'Escape' || e.keyCode === 27) && $('#tf-add-user-modal').is(':visible')) {
                $('#tf-add-user-modal').fadeOut(200);
            }
        });

        $(document).off('click', '#tf-save-new-user-btn').on('click', '#tf-save-new-user-btn', function(e) {
            e.preventDefault();
            $('#tf-add-user-form').trigger('submit');
        });

        $(document).off('submit', '#tf-add-user-form').on('submit', '#tf-add-user-form', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $('#tf-save-new-user-btn');
            var $notice = $('#tf-add-user-notice');

            if ($form.data('submitting')) return;

            var name = $('#tf-new-user-name').val().trim();
            var username = $('#tf-new-user-username').val().trim();
            var email = $('#tf-new-user-email').val().trim();
            var password = $('#tf-new-user-password').val();
            var role = $('#tf-new-user-role').val();
            var phone = $('#tf-new-user-phone').val().trim();

            if (!name || !username || !email || !password) {
                $notice.removeClass('notice-success').addClass('notice-error')
                       .html('Full Name, Username, Email, and Password are required.').slideDown(150);
                return;
            }

            $form.data('submitting', true);
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Creating Staff...');
            $notice.hide().empty();

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_create_user',
                    nonce: getNonce(),
                    name: name,
                    username: username,
                    phone: phone,
                    email: email,
                    password: password,
                    role_name: role
                },
                dataType: 'json',
                success: function(response) {
                    $form.data('submitting', false);

                    if (response.success) {
                        $notice.removeClass('notice-error').addClass('notice-success')
                               .html(response.data.message).slideDown(150);

                        setTimeout(function() {
                            $('#tf-add-user-modal').fadeOut(200);
                            location.reload();
                        }, 1200);
                    } else {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Create Staff User');
                        $notice.removeClass('notice-success').addClass('notice-error')
                               .html(response.data.message || 'Error creating user.').slideDown(150);
                    }
                },
                error: function(xhr, status, error) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Create Staff User');
                    $notice.removeClass('notice-success').addClass('notice-error')
                           .html('AJAX request failed: ' + error).slideDown(150);
                }
            });
        });

        /**
         * 12. Custom App Login Form Handler
         */
        $('#tf-app-login-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $('#tf-app-login-btn');
            var $notice = $('#tf-login-notice');

            if ($form.data('submitting')) return;

            var username = $('#tf-app-login-username').val().trim();
            var password = $('#tf-app-login-password').val();
            var remember = $('#tf-app-login-remember').is(':checked') ? 1 : 0;

            if (!username || !password) {
                $notice.removeClass('notice-success').addClass('notice-error')
                       .html('Please enter both Username and Password.').slideDown(150);
                return;
            }

            $form.data('submitting', true);
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Signing In...');
            $notice.hide().empty();

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_custom_login',
                    nonce: getNonce(),
                    username: username,
                    password: password,
                    remember: remember
                },
                dataType: 'json',
                success: function(response) {
                    $form.data('submitting', false);

                    if (response.success) {
                        $notice.removeClass('notice-error').addClass('notice-success')
                               .html(response.data.message).slideDown(150);

                        setTimeout(function() {
                            window.location.href = response.data.redirect || window.location.href;
                        }, 800);
                    } else {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-lock"></span> Sign In to Tailor Flow');
                        $notice.removeClass('notice-success').addClass('notice-error')
                               .html(response.data.message || 'Invalid Username or Password.').slideDown(150);
                    }
                },
                error: function(xhr, status, error) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-lock"></span> Sign In to Tailor Flow');
                    $notice.removeClass('notice-success').addClass('notice-error')
                           .html('Login failed. Please check your credentials.').slideDown(150);
                }
            });
        });

        /**
         * 13. View Order Details Modal Handler (Module 1)
         */
        $(document).off('click', '.tf-btn-view-order').on('click', '.tf-btn-view-order', function(e) {
            e.preventDefault();
            var orderId = $(this).attr('data-order-id');

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_get_order_details',
                    nonce: getNonce(),
                    order_id: orderId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var ord = response.data.order;
                        var cust = response.data.customer;
                        var m = response.data.measurement || {};

                        $('#tf-det-order-no').text(ord.order_number);
                        $('#tf-det-cust-name').text(cust.name || 'Unknown');
                        $('#tf-det-cust-phone').text(cust.phone || '-');
                        $('#tf-det-garment').text((ord.garment_type || 'kameez_shalwar').replace('_', ' ').toUpperCase() + ' (Qty: ' + ord.quantity + ')');
                        $('#tf-det-stage').text((ord.status || 'received').toUpperCase());
                        $('#tf-det-trial-date').text(ord.trial_date || 'N/A');
                        $('#tf-det-delivery-date').text(ord.delivery_date || 'N/A');

                        $('#tf-det-total').text(parseFloat(ord.total_amount).toFixed(2));
                        $('#tf-det-advance').text(parseFloat(ord.advance_amount).toFixed(2));
                        $('#tf-det-balance').text(parseFloat(ord.balance_amount).toFixed(2));

                        if (ord.special_notes || ord.cloth_details) {
                            $('#tf-det-notes').text((ord.cloth_details ? 'Cloth: ' + ord.cloth_details + ' | ' : '') + (ord.special_notes || ''));
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
                                '<span style="color:#9ca3af;">' + label + ':</span> <strong style="color:#ffffff;">' + $('<div>').text(val).html() + '</strong>' +
                                '</div>';
                        });
                        $('#tf-det-measurements-grid').html(mGridHtml);

                        $('#tf-order-details-modal').fadeIn(200);
                    } else {
                        alert(response.data.message || 'Error fetching order details.');
                    }
                }
            });
        });

        $('#tf-close-details-modal, #tf-done-details-modal').off('click').on('click', function() {
            $('#tf-order-details-modal').fadeOut(200);
        });

        $('#tf-print-details-btn').off('click').on('click', function() {
            window.print();
        });

        /**
         * 14. Edit Order Modal Handler (Module 2)
         */
        $(document).off('click', '.tf-btn-edit-order').on('click', '.tf-btn-edit-order', function(e) {
            e.preventDefault();
            var orderId = $(this).attr('data-order-id');

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_get_order_details',
                    nonce: getNonce(),
                    order_id: orderId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var ord = response.data.order;
                        var m = response.data.measurement || {};

                        $('#tf-edit-order-id').val(ord.id);
                        $('#tf-edit-order-no-title').text(ord.order_number);
                        $('#tf-edit-garment').val(ord.garment_type);
                        $('#tf-edit-stage').val(ord.status);
                        $('#tf-edit-trial-date').val(ord.trial_date);
                        $('#tf-edit-delivery-date').val(ord.delivery_date);
                        $('#tf-edit-total-amount').val(ord.total_amount);
                        $('#tf-edit-advance-amount').val(ord.advance_amount);
                        $('#tf-edit-special-notes').val(ord.special_notes);

                        $('#tf-edit-m-length').val(m.length || '');
                        $('#tf-edit-m-chest').val(m.chest || '');
                        $('#tf-edit-m-waist').val(m.waist || '');
                        $('#tf-edit-m-hip').val(m.hip || '');
                        $('#tf-edit-m-shoulder').val(m.shoulder || '');
                        $('#tf-edit-m-sleeves').val(m.sleeves || '');
                        $('#tf-edit-m-neck').val(m.neck || '');
                        $('#tf-edit-m-shalwar').val(m.shalwar_length || '');
                        $('#tf-edit-m-paucha').val(m.paucha || '');

                        $('#tf-edit-order-notice').hide().empty();
                        $('#tf-order-edit-modal').fadeIn(200);
                    } else {
                        alert(response.data.message || 'Error fetching order for edit.');
                    }
                }
            });
        });

        $('#tf-close-order-edit-modal, #tf-cancel-order-edit-modal').off('click').on('click', function() {
            $('#tf-order-edit-modal').fadeOut(200);
        });

        $('#tf-edit-order-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $('#tf-save-order-edit-btn');
            var $notice = $('#tf-edit-order-notice');

            if ($form.data('submitting')) return;

            $form.data('submitting', true);
            $btn.prop('disabled', true);
            $notice.hide().empty();

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_update_order',
                    nonce: getNonce(),
                    order_id: $('#tf-edit-order-id').val(),
                    garment_type: $('#tf-edit-garment').val(),
                    stage: $('#tf-edit-stage').val(),
                    trial_date: $('#tf-edit-trial-date').val(),
                    delivery_date: $('#tf-edit-delivery-date').val(),
                    total_amount: $('#tf-edit-total-amount').val(),
                    advance_amount: $('#tf-edit-advance-amount').val(),
                    special_notes: $('#tf-edit-special-notes').val(),
                    length: $('#tf-edit-m-length').val().trim(),
                    chest: $('#tf-edit-m-chest').val().trim(),
                    waist: $('#tf-edit-m-waist').val().trim(),
                    hip: $('#tf-edit-m-hip').val().trim(),
                    shoulder: $('#tf-edit-m-shoulder').val().trim(),
                    sleeves: $('#tf-edit-m-sleeves').val().trim(),
                    neck: $('#tf-edit-m-neck').val().trim(),
                    shalwar_length: $('#tf-edit-m-shalwar').val().trim(),
                    paucha: $('#tf-edit-m-paucha').val().trim()
                },
                dataType: 'json',
                success: function(response) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);

                    if (response.success) {
                        $notice.removeClass('notice-error').addClass('notice-success')
                               .html(response.data.message).slideDown(150);

                        setTimeout(function() {
                            $('#tf-order-edit-modal').fadeOut(200);
                            location.reload();
                        }, 1200);
                    } else {
                        $notice.removeClass('notice-success').addClass('notice-error')
                               .html(response.data.message || 'Error updating order.').slideDown(150);
                    }
                },
                error: function(xhr, status, error) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);
                    alert('AJAX error: ' + error);
                }
            });
        });

        /**
         * 15. Customer History Modal Handler (Module 3)
         */
        $(document).off('click', '.tf-btn-customer-history').on('click', '.tf-btn-customer-history', function(e) {
            e.preventDefault();

            var custId = $(this).attr('data-customer-id');
            var custName = $(this).attr('data-customer-name');

            $('#tf-hist-cust-name').text(custName);

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_get_customer_history',
                    nonce: getNonce(),
                    customer_id: custId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var orders = response.data.orders || [];
                        var rowsHtml = '';

                        if (orders.length > 0) {
                            $.each(orders, function(i, o) {
                                var stageKey = (o.status || 'received').toLowerCase();
                                rowsHtml += '<tr>' +
                                    '<td><strong>' + $('<div>').text(o.order_number).html() + '</strong></td>' +
                                    '<td>' + $('<div>').text(o.garment_type + ' (' + o.quantity + ')').html() + '</td>' +
                                    '<td>' + $('<div>').text(o.delivery_date || '-').html() + '</td>' +
                                    '<td><span class="tf-badge badge-ready">' + $('<div>').text(stageKey.toUpperCase()).html() + '</span></td>' +
                                    '<td>PKR ' + parseFloat(o.total_amount).toFixed(2) + '</td>' +
                                    '<td>PKR ' + parseFloat(o.balance_amount).toFixed(2) + '</td>' +
                                    '</tr>';
                            });
                        } else {
                            rowsHtml = '<tr><td colspan="6" style="text-align:center; color:#9ca3af;">No order history found for this customer.</td></tr>';
                        }

                        $('#tf-customer-history-tbody').html(rowsHtml);
                        $('#tf-customer-history-modal').fadeIn(200);
                    } else {
                        alert(response.data.message || 'Error fetching customer history.');
                    }
                }
            });
        });

        $('#tf-close-history-modal, #tf-done-history-modal').off('click').on('click', function() {
            $('#tf-customer-history-modal').fadeOut(200);
        });

        /**
         * 16. Duplicate Customer Check Handling (Module 4) - Performance Optimized
         */
        var pendingDuplicateCustomer = null;
        var phoneCheckTimer = null;
        window.phoneCheckXhr = null;

        $(document).off('input', '#tf-cust-phone').on('input', '#tf-cust-phone', function() {
            var phone = $(this).val().trim();
            clearTimeout(phoneCheckTimer);
            if (window.phoneCheckXhr) {
                try { window.phoneCheckXhr.abort(); } catch (e) {}
            }
            if (phone.length >= 7) {
                phoneCheckTimer = setTimeout(function() {
                    window.phoneCheckXhr = $.ajax({
                        url: getAjaxUrl(),
                        type: 'POST',
                        data: {
                            action: 'tf_check_duplicate_phone',
                            nonce: getNonce(),
                            phone: phone
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.data.exists) {
                                pendingDuplicateCustomer = response.data.customer;
                                $('#tf-customer-notice').removeClass('notice-success').addClass('notice-error')
                                       .html('Customer already exists with phone ' + $('<div>').text(phone).html() + ' (' + $('<div>').text(pendingDuplicateCustomer.name).html() + '). <button type="button" id="tf-use-existing-cust-btn" class="tf-btn-sm" style="margin-left:8px; background:#10b981; color:#ffffff;">Use Existing Customer</button>')
                                       .slideDown(150);
                            }
                        }
                    });
                }, 350);
            }
        });

        $(document).off('click', '#tf-use-existing-cust-btn').on('click', '#tf-use-existing-cust-btn', function(e) {
            e.preventDefault();
            if (pendingDuplicateCustomer) {
                var c = pendingDuplicateCustomer;
                if ($('#tf-order-customer-id').length) {
                    if (!$('#tf-order-customer-id option[value="' + c.id + '"]').length) {
                        $('#tf-order-customer-id').append('<option value="' + c.id + '">' + $('<div>').text(c.name + ' (' + c.phone + ')').html() + '</option>');
                    }
                    $('#tf-order-customer-id').val(c.id);
                }
                switchTab('new-order');
                $('#tf-customer-form-card').slideUp(200);
                $('#tf-customer-notice').hide().empty();
            }
        });

        /**
         * 17. Shop Settings Form Submit & Logo Upload
         */
        $('#tf-settings-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $('#tf-save-settings-btn');
            var $notice = $('#tf-settings-notice');

            if ($form.data('submitting')) return;

            $form.data('submitting', true);
            $btn.prop('disabled', true);
            $notice.hide().empty();

            $.ajax({
                url: getAjaxUrl(),
                type: 'POST',
                data: {
                    action: 'tf_save_settings',
                    nonce: getNonce(),
                    company_name: $('#tf-set-company-name').val().trim(),
                    logo_url: $('#tf-set-logo-url').val().trim(),
                    phone: $('#tf-set-phone').val().trim(),
                    currency: $('#tf-set-currency').val().trim(),
                    address: $('#tf-set-address').val().trim(),
                    receipt_footer: $('#tf-set-receipt-footer').val().trim(),
                    timezone: $('#tf-set-timezone').val()
                },
                dataType: 'json',
                success: function(response) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);

                    if (response.success) {
                        $notice.removeClass('notice-error').addClass('notice-success')
                               .html(response.data.message).slideDown(150);

                        // Extract updated settings values
                        var companyName = $('#tf-set-company-name').val().trim();
                        var logoUrl = $('#tf-set-logo-url').val().trim();
                        var phone = $('#tf-set-phone').val().trim();
                        var currency = $('#tf-set-currency').val().trim();
                        var address = $('#tf-set-address').val().trim();
                        var receiptFooter = $('#tf-set-receipt-footer').val().trim();

                        // 1. Update dynamic slip header elements in DOM
                        $('#tf-inv-shop-name').text(companyName.toUpperCase());
                        $('#tf-inv-shop-phone').text(phone ? phone : 'Custom Bespoke Tailoring');
                        $('#tf-inv-receipt-footer').text(receiptFooter ? receiptFooter : 'Thank you for choosing Tailor Flow PK!');
                        $('.tf-inv-currency').text(currency);

                        if (address) {
                            $('#tf-inv-shop-addr-sep').text(' | ');
                            $('#tf-inv-shop-address').text(address);
                        } else {
                            $('#tf-inv-shop-addr-sep').text('');
                            $('#tf-inv-shop-address').text('');
                        }

                        if (logoUrl) {
                            $('#tf-inv-logo-img').attr('src', logoUrl);
                            $('#tf-inv-logo-wrapper').show();
                        } else {
                            $('#tf-inv-logo-wrapper').hide();
                        }

                        // 2. Update dashboard application branding elements dynamically
                        $('.tf-brand-name').text(companyName);
                        var splitParts = companyName.split(' ', 2);
                        var restPart = splitParts[1] ? ' <span>' + $('<div>').text(splitParts[1]).html() + '</span>' : '';
                        $('.tf-brand-text h2').html($('<div>').text(splitParts[0]).html() + restPart);

                        setTimeout(function() {
                            $notice.slideUp(300);
                        }, 2500);
                    } else {
                        $notice.removeClass('notice-success').addClass('notice-error')
                               .html(response.data.message || 'Error saving settings.').slideDown(150);
                    }
                },
                error: function(xhr, status, error) {
                    $form.data('submitting', false);
                    $btn.prop('disabled', false);
                    $notice.removeClass('notice-success').addClass('notice-error')
                           .html('AJAX request failed: ' + error).slideDown(150);
                }
            });
        });

        $('#tf-upload-logo-btn').off('click').on('click', function(e) {
            e.preventDefault();
            if (typeof wp !== 'undefined' && wp.media) {
                var mediaUploader = wp.media({
                    title: 'Select Shop Logo Image',
                    button: { text: 'Use as Shop Logo' },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#tf-set-logo-url').val(attachment.url);
                });

                mediaUploader.open();
            } else {
                var promptUrl = prompt('Enter Image URL for Shop Logo:', $('#tf-set-logo-url').val());
                if (promptUrl !== null) {
                    $('#tf-set-logo-url').val(promptUrl);
                }
            }
        });

        // Load customer measurements inline when selecting a customer on the New Order page
        $('#tf-order-customer-id').off('change').on('change', function() {
            var val = $(this).val();
            if (val) {
                // Clear any previous values first
                $('#tf-order-measurements-section').find('.tf-input').val('');
                
                $.ajax({
                    url: getAjaxUrl(),
                    type: 'POST',
                    data: {
                        action: 'tf_get_measurement',
                        nonce: getNonce(),
                        customer_id: val
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.measurements) {
                            var m = response.data.measurements;
                            // Populate inline fields
                            $('#tf-order-m-length').val(m.length || '');
                            $('#tf-order-m-chest').val(m.chest || '');
                            $('#tf-order-m-waist').val(m.waist || '');
                            $('#tf-order-m-hip').val(m.hip || '');
                            $('#tf-order-m-shoulder').val(m.shoulder || '');
                            $('#tf-order-m-sleeves').val(m.sleeves || '');
                            $('#tf-order-m-neck').val(m.neck || '');
                            $('#tf-order-m-shalwar-length').val(m.shalwar_length || '');
                            $('#tf-order-m-paucha').val(m.paucha || '');
                        }
                        $('#tf-order-measurements-section').slideDown(200);
                    }
                });
            } else {
                $('#tf-order-measurements-section').slideUp(200);
            }
        });

    });

})(jQuery);
