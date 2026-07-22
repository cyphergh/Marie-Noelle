<?php
include_once __DIR__ . '/includes/layout.php';
staff_require_login();

$activeKey = 'subscribe';

function fetch_subscription_row($con, $id)
{
    $query = mysqli_query(
        $con,
        "SELECT um.*, c.Name AS customer_name, mp.plan_name
         FROM user_memberships um
         LEFT JOIN tblcustomers c ON c.ID = um.user_id
         LEFT JOIN membership_plans mp ON mp.id = um.plan_id
         WHERE um.id = '{$id}'"
    );
    return mysqli_fetch_assoc($query);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $action = isset($_POST['ajax_action']) ? $_POST['ajax_action'] : '';
    
    if ($action === 'create_subscription') {
        $userId = (int) $_POST['user_id'];
        $planId = (int) $_POST['plan_id'];
        
        if (!$userId || !$planId) {
            staff_json_response(false, 'Please choose a customer and a plan.');
        }
        
        $planResult = mysqli_query($con, "SELECT duration_days FROM membership_plans WHERE id = '{$planId}'");
        $plan = mysqli_fetch_assoc($planResult);
        
        if (!$plan) {
            staff_json_response(false, 'Selected plan could not be found.');
        }
        
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+' . (int) $plan['duration_days'] . ' days'));
        
        $insertQuery = mysqli_query(
            $con,
            "INSERT INTO user_memberships (user_id, plan_id, start_date, end_date, status) VALUES ('{$userId}', '{$planId}', '{$start}', '{$end}', 'active')"
        );
        
        if (!$insertQuery) {
            staff_json_response(false, 'Unable to create the subscription.');
        }
        
        $newId = mysqli_insert_id($con);
        staff_json_response(true, 'Subscription added successfully.', ['record_id' => $newId]);
    }
    
    if ($action === 'get_subscription') {
        $id = (int) $_POST['id'];
        $row = fetch_subscription_row($con, $id);
        if (!$row) {
            staff_json_response(false, 'Subscription not found.');
        }
        staff_json_response(true, 'Subscription loaded.', ['record' => $row]);
    }
    
    if ($action === 'update_subscription') {
        $id = (int) $_POST['id'];
        $userId = (int) $_POST['user_id'];
        $planId = (int) $_POST['plan_id'];
        
        if (!$userId || !$planId) {
            staff_json_response(false, 'Please choose a customer and a plan.');
        }
        
        $planResult = mysqli_query($con, "SELECT duration_days FROM membership_plans WHERE id = '{$planId}'");
        $plan = mysqli_fetch_assoc($planResult);
        
        if (!$plan) {
            staff_json_response(false, 'Selected plan could not be found.');
        }
        
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+' . (int) $plan['duration_days'] . ' days'));
        
        $updateQuery = mysqli_query($con, "UPDATE user_memberships SET user_id = '{$userId}', plan_id = '{$planId}', start_date = '{$start}', end_date = '{$end}' WHERE id = '{$id}'");
        if (!$updateQuery) {
            staff_json_response(false, 'Unable to update the subscription.');
        }
        
        staff_json_response(true, 'Subscription updated successfully.', ['record_id' => $id]);
    }
    
    if ($action === 'delete_subscription') {
        $id = (int) $_POST['id'];
        $deleteQuery = mysqli_query($con, "DELETE FROM user_memberships WHERE id = '{$id}'");
        
        if (!$deleteQuery) {
            staff_json_response(false, 'Unable to delete the subscription.');
        }
        
        staff_json_response(true, 'Subscription deleted.', ['record_id' => $id]);
    }
}

$customers = mysqli_query($con, "SELECT ID, Name FROM tblcustomers ORDER BY Name ASC");
$plans = mysqli_query($con, "SELECT id, plan_name, price FROM membership_plans ORDER BY plan_name ASC");
$subscriptionResult = mysqli_query(
    $con,
    "SELECT um.*, c.Name AS customer_name, mp.plan_name
     FROM user_memberships um
     LEFT JOIN tblcustomers c ON c.ID = um.user_id
     LEFT JOIN membership_plans mp ON mp.id = um.plan_id
     ORDER BY um.id DESC"
);

staff_layout_start('Subscriptions', $activeKey, 'Manage client memberships and subscriptions');
?>

<div class="staff-section-head">
    <div class="staff-section-head-left">
        <p style="margin: 6px 0 0; color: var(--staff-muted); font-size: 14px;">
            Assign membership plans to customers and track their subscription status.
        </p>
    </div>
    <button type="button" class="staff-button staff-button-primary" data-bs-toggle="modal" data-bs-target="#subscriptionCreateModal">
        <i class="fa fa-plus"></i>
        Add Subscription
    </button>
</div>

<div class="staff-table-card">
    <div class="table-responsive">
        <table class="table" id="subscriptionTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Plan</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="subscriptionTableBody">
                <?php $cnt = 1; while ($row = mysqli_fetch_assoc($subscriptionResult)) { ?>
                <tr id="subscription-row-<?php echo $row['id']; ?>">
                    <td><?php echo $cnt++; ?></td>
                    <td><strong><?php echo staff_escape($row['customer_name'] ?: 'Unknown'); ?></strong></td>
                    <td><?php echo staff_escape($row['plan_name'] ?: 'Unknown'); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['start_date'])); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['end_date'])); ?></td>
                    <td>
                        <span class="staff-badge <?php echo $row['status'] === 'active' ? 'is-success' : ''; ?>">
                            <?php echo staff_escape($row['status']); ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="staff-button js-edit-subscription" data-id="<?php echo $row['id']; ?>" title="Edit">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button type="button" class="staff-button staff-button-danger js-delete-subscription" data-id="<?php echo $row['id']; ?>" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="subscriptionCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <form id="subscriptionCreateForm">
                <div class="modal-header border-0">
                    <div>
                        <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                            <i class="fa fa-file-invoice" style="color: var(--staff-accent); margin-right: 10px;"></i>
                            Add Subscription
                        </h3>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="ajax_action" value="create_subscription">
                    <div class="col-md-6">
                        <label class="form-label">Customer *</label>
                        <select class="form-select" name="user_id" required>
                            <option value="">Select Customer</option>
                            <?php mysqli_data_seek($customers, 0); while ($customer = mysqli_fetch_assoc($customers)) { ?>
                                <option value="<?php echo $customer['ID']; ?>"><?php echo staff_escape($customer['Name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Plan *</label>
                        <select class="form-select" name="plan_id" required>
                            <option value="">Choose a Plan</option>
                            <?php mysqli_data_seek($plans, 0); while ($plan = mysqli_fetch_assoc($plans)) { ?>
                                <option value="<?php echo $plan['id']; ?>">
                                    <?php echo staff_escape($plan['plan_name']); ?> - <?php echo number_format((float) $plan['price'], 2); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa fa-save"></i> Save Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="subscriptionEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <form id="subscriptionEditForm">
                <div class="modal-header border-0">
                    <div>
                        <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                            <i class="fa fa-edit" style="color: var(--staff-accent); margin-right: 10px;"></i>
                            Edit Subscription
                        </h3>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="ajax_action" value="update_subscription">
                    <input type="hidden" name="id" id="subscriptionEditId">
                    <div class="col-md-6">
                        <label class="form-label">Customer *</label>
                        <select class="form-select" name="user_id" id="subscriptionEditUser" required>
                            <option value="">Select Customer</option>
                            <?php mysqli_data_seek($customers, 0); while ($customer = mysqli_fetch_assoc($customers)) { ?>
                                <option value="<?php echo $customer['ID']; ?>"><?php echo staff_escape($customer['Name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Plan *</label>
                        <select class="form-select" name="plan_id" id="subscriptionEditPlan" required>
                            <option value="">Choose a Plan</option>
                            <?php mysqli_data_seek($plans, 0); while ($plan = mysqli_fetch_assoc($plans)) { ?>
                                <option value="<?php echo $plan['id']; ?>">
                                    <?php echo staff_escape($plan['plan_name']); ?> - <?php echo number_format((float) $plan['price'], 2); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa fa-save"></i> Update Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif; color: var(--staff-danger);">
                        <i class="fa fa-exclamation-triangle" style="margin-right: 8px;"></i>
                        Confirm Delete
                    </h3>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p style="margin-bottom: 0;">Are you sure you want to delete this subscription? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="staff-button staff-button-danger" id="confirmDeleteBtn">
                    <i class="fa fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    var pendingDeleteId = null;
    var subscriptionTableBody = document.getElementById('subscriptionTableBody');
    
    function renumberRows() {
        if (!subscriptionTableBody) return;
        var rows = subscriptionTableBody.querySelectorAll('tr');
        rows.forEach(function(row, index) {
            var firstCell = row.querySelector('td:first-child');
            if (firstCell) {
                firstCell.textContent = index + 1;
            }
        });
    }
    
    function closeModal(modalId) {
        var modalEl = document.getElementById(modalId);
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    }
    
    document.getElementById('subscriptionCreateForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var form = e.currentTarget;
        var formData = new FormData(form);
        formData.append('csrf_token', document.getElementById('staffCsrfToken').value);
        
        try {
            var response = await fetch('manage_subscribe.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            var payload = await response.json();
            
            if (payload.success) {
                StaffPortal.showToast(payload.message, 'success');
                form.reset();
                closeModal('subscriptionCreateModal');
                location.reload();
            } else {
                StaffPortal.showToast(payload.message || 'Failed to create subscription.', 'error');
            }
        } catch (error) {
            StaffPortal.showToast('Failed to create subscription.', 'error');
        }
    });
    
    document.getElementById('subscriptionEditForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var form = e.currentTarget;
        var formData = new FormData(form);
        formData.append('csrf_token', document.getElementById('staffCsrfToken').value);
        
        try {
            var response = await fetch('manage_subscribe.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            var payload = await response.json();
            
            if (payload.success) {
                StaffPortal.showToast(payload.message, 'success');
                form.reset();
                closeModal('subscriptionEditModal');
                location.reload();
            } else {
                StaffPortal.showToast(payload.message || 'Failed to update subscription.', 'error');
            }
        } catch (error) {
            StaffPortal.showToast('Failed to update subscription.', 'error');
        }
    });
    
    document.addEventListener('click', function(e) {
        var editBtn = e.target.closest('.js-edit-subscription');
        if (editBtn) {
            var subId = editBtn.getAttribute('data-id');
            var formData = new FormData();
            formData.append('ajax_action', 'get_subscription');
            formData.append('id', subId);
            
            fetch('manage_subscribe.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(payload) {
                if (payload.success && payload.record) {
                    document.getElementById('subscriptionEditId').value = payload.record.id;
                    document.getElementById('subscriptionEditUser').value = payload.record.user_id || '';
                    document.getElementById('subscriptionEditPlan').value = payload.record.plan_id || '';
                    var modal = new bootstrap.Modal(document.getElementById('subscriptionEditModal'));
                    modal.show();
                }
            });
            return;
        }
        
        var deleteBtn = e.target.closest('.js-delete-subscription');
        if (deleteBtn) {
            pendingDeleteId = deleteBtn.getAttribute('data-id');
            var modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }
    });
    
    document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
        if (!pendingDeleteId) return;
        
        var formData = new FormData();
        formData.append('ajax_action', 'delete_subscription');
        formData.append('id', pendingDeleteId);
        formData.append('csrf_token', document.getElementById('staffCsrfToken').value);
        
        try {
            var response = await fetch('manage_subscribe.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            var payload = await response.json();
            
            if (payload.success) {
                StaffPortal.showToast(payload.message, 'success');
                var row = document.getElementById('subscription-row-' + pendingDeleteId);
                if (row) row.remove();
                renumberRows();
            } else {
                StaffPortal.showToast(payload.message || 'Failed to delete subscription.', 'error');
            }
        } catch (error) {
            StaffPortal.showToast('Failed to delete subscription.', 'error');
        }
        
        pendingDeleteId = null;
        closeModal('deleteConfirmModal');
    });
})();
</script>

<?php staff_layout_end(); ?>
