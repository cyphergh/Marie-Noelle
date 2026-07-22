<?php
include_once __DIR__ . '/includes/layout.php';
staff_require_login();

$activeKey = 'plan';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $action = isset($_POST['ajax_action']) ? $_POST['ajax_action'] : '';
    
    if ($action === 'create_plan') {
        $planName = mysqli_real_escape_string($con, trim($_POST['plan_name']));
        $description = mysqli_real_escape_string($con, trim($_POST['description']));
        $durationDays = (int) $_POST['duration_days'];
        $price = (float) $_POST['price'];
        
        if ($planName === '' || $description === '' || $durationDays <= 0) {
            staff_json_response(false, 'Please complete the membership plan form.');
        }
        
        $insertQuery = mysqli_query(
            $con,
            "INSERT INTO membership_plans (plan_name, description, duration_days, price) VALUES ('{$planName}', '{$description}', '{$durationDays}', '{$price}')"
        );
        
        if (!$insertQuery) {
            staff_json_response(false, 'Unable to create the plan.');
        }
        
        $newId = mysqli_insert_id($con);
        staff_json_response(true, 'Plan created successfully.', ['record_id' => $newId]);
    }
    
    if ($action === 'get_plan') {
        $id = (int) $_POST['id'];
        $planResult = mysqli_query($con, "SELECT * FROM membership_plans WHERE id = '{$id}'");
        $planRow = mysqli_fetch_assoc($planResult);
        if (!$planRow) {
            staff_json_response(false, 'Plan not found.');
        }
        staff_json_response(true, 'Plan loaded.', ['record' => $planRow]);
    }
    
    if ($action === 'update_plan') {
        $id = (int) $_POST['id'];
        $planName = mysqli_real_escape_string($con, trim($_POST['plan_name']));
        $description = mysqli_real_escape_string($con, trim($_POST['description']));
        $durationDays = (int) $_POST['duration_days'];
        $price = (float) $_POST['price'];
        
        if ($planName === '' || $description === '' || $durationDays <= 0) {
            staff_json_response(false, 'Please complete the membership plan form.');
        }
        
        $updateQuery = mysqli_query($con, "UPDATE membership_plans SET plan_name = '{$planName}', duration_days = '{$durationDays}', description = '{$description}', price = '{$price}' WHERE id = '{$id}'");
        if (!$updateQuery) {
            staff_json_response(false, 'Unable to update the plan.');
        }
        
        staff_json_response(true, 'Plan updated successfully.', ['record_id' => $id]);
    }
    
    if ($action === 'delete_plan') {
        $id = (int) $_POST['id'];
        $deleteQuery = mysqli_query($con, "DELETE FROM membership_plans WHERE id = '{$id}'");
        
        if (!$deleteQuery) {
            staff_json_response(false, 'Unable to delete the plan.');
        }
        
        staff_json_response(true, 'Plan deleted.', ['record_id' => $id]);
    }
}

$planResult = mysqli_query($con, "SELECT * FROM membership_plans ORDER BY id DESC");

staff_layout_start('Membership Plans', $activeKey, 'Create and manage membership plans for your clients');
?>

<div class="staff-section-head">
    <div class="staff-section-head-left">
        <p style="margin: 6px 0 0; color: var(--staff-muted); font-size: 14px;">
            Manage membership plans with pricing, duration, and benefits.
        </p>
    </div>
    <button type="button" class="staff-button staff-button-primary" data-bs-toggle="modal" data-bs-target="#planCreateModal">
        <i class="fa fa-plus"></i>
        Add Plan
    </button>
</div>

<div class="staff-table-card">
    <div class="table-responsive">
        <table class="table" id="planTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Plan Name</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="planTableBody">
                <?php $cnt = 1; while ($row = mysqli_fetch_assoc($planResult)) { ?>
                <tr id="plan-row-<?php echo $row['id']; ?>">
                    <td><?php echo $cnt++; ?></td>
                    <td><strong><?php echo staff_escape($row['plan_name']); ?></strong></td>
                    <td><?php echo number_format((float) $row['price'], 2); ?></td>
                    <td><?php echo (int) $row['duration_days']; ?> days</td>
                    <td><small><?php echo staff_escape($row['description']); ?></small></td>
                    <td>
                        <button type="button" class="staff-button js-edit-plan" data-id="<?php echo $row['id']; ?>" title="Edit">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button type="button" class="staff-button staff-button-danger js-delete-plan" data-id="<?php echo $row['id']; ?>" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="planCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <form id="planCreateForm">
                <div class="modal-header border-0">
                    <div>
                        <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                            <i class="fa fa-id-card" style="color: var(--staff-accent); margin-right: 10px;"></i>
                            Add Membership Plan
                        </h3>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="ajax_action" value="create_plan">
                    <div class="col-md-6">
                        <label class="form-label">Plan Name *</label>
                        <input type="text" class="form-control" name="plan_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Price *</label>
                        <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Duration (Days) *</label>
                        <input type="number" class="form-control" name="duration_days" min="1" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa fa-save"></i> Save Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="planEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <form id="planEditForm">
                <div class="modal-header border-0">
                    <div>
                        <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                            <i class="fa fa-edit" style="color: var(--staff-accent); margin-right: 10px;"></i>
                            Edit Membership Plan
                        </h3>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="ajax_action" value="update_plan">
                    <input type="hidden" name="id" id="planEditId">
                    <div class="col-md-6">
                        <label class="form-label">Plan Name *</label>
                        <input type="text" class="form-control" name="plan_name" id="planEditName" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Price *</label>
                        <input type="number" class="form-control" name="price" id="planEditPrice" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Duration (Days) *</label>
                        <input type="number" class="form-control" name="duration_days" id="planEditDuration" min="1" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" name="description" id="planEditDescription" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa fa-save"></i> Update Plan
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
                <p style="margin-bottom: 0;">Are you sure you want to delete this membership plan? This action cannot be undone.</p>
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
    var planTableBody = document.getElementById('planTableBody');
    
    function renumberRows() {
        if (!planTableBody) return;
        var rows = planTableBody.querySelectorAll('tr');
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
    
    document.getElementById('planCreateForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var form = e.currentTarget;
        var formData = new FormData(form);
        formData.append('csrf_token', document.getElementById('staffCsrfToken').value);
        
        try {
            var response = await fetch('manage_plan.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            var payload = await response.json();
            
            if (payload.success) {
                StaffPortal.showToast(payload.message, 'success');
                form.reset();
                closeModal('planCreateModal');
                location.reload();
            } else {
                StaffPortal.showToast(payload.message || 'Failed to create plan.', 'error');
            }
        } catch (error) {
            StaffPortal.showToast('Failed to create plan.', 'error');
        }
    });
    
    document.getElementById('planEditForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var form = e.currentTarget;
        var formData = new FormData(form);
        formData.append('csrf_token', document.getElementById('staffCsrfToken').value);
        
        try {
            var response = await fetch('manage_plan.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            var payload = await response.json();
            
            if (payload.success) {
                StaffPortal.showToast(payload.message, 'success');
                form.reset();
                closeModal('planEditModal');
                location.reload();
            } else {
                StaffPortal.showToast(payload.message || 'Failed to update plan.', 'error');
            }
        } catch (error) {
            StaffPortal.showToast('Failed to update plan.', 'error');
        }
    });
    
    document.addEventListener('click', function(e) {
        var editBtn = e.target.closest('.js-edit-plan');
        if (editBtn) {
            var planId = editBtn.getAttribute('data-id');
            var formData = new FormData();
            formData.append('ajax_action', 'get_plan');
            formData.append('id', planId);
            
            fetch('manage_plan.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(payload) {
                if (payload.success && payload.record) {
                    document.getElementById('planEditId').value = payload.record.id;
                    document.getElementById('planEditName').value = payload.record.plan_name || '';
                    document.getElementById('planEditPrice').value = payload.record.price || '';
                    document.getElementById('planEditDuration').value = payload.record.duration_days || '';
                    document.getElementById('planEditDescription').value = payload.record.description || '';
                    var modal = new bootstrap.Modal(document.getElementById('planEditModal'));
                    modal.show();
                }
            });
            return;
        }
        
        var deleteBtn = e.target.closest('.js-delete-plan');
        if (deleteBtn) {
            pendingDeleteId = deleteBtn.getAttribute('data-id');
            var modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }
    });
    
    document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
        if (!pendingDeleteId) return;
        
        var formData = new FormData();
        formData.append('ajax_action', 'delete_plan');
        formData.append('id', pendingDeleteId);
        formData.append('csrf_token', document.getElementById('staffCsrfToken').value);
        
        try {
            var response = await fetch('manage_plan.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            var payload = await response.json();
            
            if (payload.success) {
                StaffPortal.showToast(payload.message, 'success');
                var row = document.getElementById('plan-row-' + pendingDeleteId);
                if (row) row.remove();
                renumberRows();
            } else {
                StaffPortal.showToast(payload.message || 'Failed to delete plan.', 'error');
            }
        } catch (error) {
            StaffPortal.showToast('Failed to delete plan.', 'error');
        }
        
        pendingDeleteId = null;
        closeModal('deleteConfirmModal');
    });
})();
</script>

<?php staff_layout_end(); ?>
