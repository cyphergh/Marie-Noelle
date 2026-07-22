<?php
include_once __DIR__ . '/includes/layout.php';
include_once __DIR__ . '/../panel/includes/audit_helper.php';
staff_require_login();

$staff = staff_fetch_current($con);
$staffId = (int) ($staff['id'] ?? 0);
$staffName = $staff['name'] ?? 'Unknown Staff';

function staff_render_customer_row($row, $index)
{
    ob_start();
    ?>
    <tr id="customer-row-<?php echo (int) $row['ID']; ?>">
        <td><?php echo (int) $index; ?></td>
        <td><strong><?php echo staff_escape($row['Name']); ?></strong></td>
        <td><?php echo staff_escape($row['Email']); ?></td>
        <td><?php echo staff_escape($row['MobileNumber']); ?></td>
        <td><span class="staff-badge"><?php echo staff_escape($row['Gender'] ?: 'N/A'); ?></span></td>
        <td><?php echo date('d M Y', strtotime($row['CreationDate'])); ?></td>
        <td>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="staff-button js-edit-customer" data-id="<?php echo (int) $row['ID']; ?>" style="padding: 8px 12px;">
                    <i class="fa fa-edit"></i> Edit
                </button>
                <button type="button" class="staff-button staff-button-danger js-delete-customer" data-id="<?php echo (int) $row['ID']; ?>" style="padding: 8px 12px;">
                    <i class="fa fa-trash"></i> Delete
                </button>
            </div>
        </td>
    </tr>
    <?php
    return trim(ob_get_clean());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $action = isset($_POST['ajax_action']) ? $_POST['ajax_action'] : '';

    if ($action === 'create_customer') {
        $name = mysqli_real_escape_string($con, trim($_POST['name'] ?? ''));
        $emailRaw = trim($_POST['email'] ?? '');
        $email = mysqli_real_escape_string($con, $emailRaw);
        $mobile = mysqli_real_escape_string($con, trim($_POST['mobilenum'] ?? ''));
        $gender = mysqli_real_escape_string($con, trim($_POST['gender'] ?? ''));

        if ($name === '' || $mobile === '') {
            staff_json_response(false, 'Please complete the required customer fields.');
        }

        if ($emailRaw !== '' && !staff_validate_email($emailRaw)) {
            staff_json_response(false, 'Please provide a valid email address.');
        }

        if (!staff_validate_phone($mobile)) {
            staff_json_response(false, 'Please provide a valid mobile number.');
        }

        $duplicateSql = "SELECT ID FROM tblcustomers WHERE MobileNumber = '{$mobile}'";
        if ($emailRaw !== '') {
            $duplicateSql .= " OR Email = '{$email}'";
        }
        $duplicateSql .= " LIMIT 1";
        $duplicateResult = mysqli_query($con, $duplicateSql);
        if ($duplicateResult && mysqli_num_rows($duplicateResult) > 0) {
            staff_json_response(false, 'A customer with this phone or email already exists.');
        }

        $genderSql = $gender !== '' ? "'{$gender}'" : 'NULL';
        $emailSql = $emailRaw !== '' ? "'{$email}'" : 'NULL';
        $insertQuery = mysqli_query(
            $con,
            "INSERT INTO tblcustomers (Name, Email, MobileNumber, Gender, dob, marriage_date)
             VALUES ('{$name}', {$emailSql}, '{$mobile}', {$genderSql}, '2025-01-01', '2025-01-01')"
        );

        if (!$insertQuery) {
            staff_json_response(false, 'Unable to add the customer.');
        }

        $newId = mysqli_insert_id($con);
        $customerResult = mysqli_query($con, "SELECT * FROM tblcustomers WHERE ID = '{$newId}'");
        $customerRow = $customerResult ? mysqli_fetch_assoc($customerResult) : null;

        log_audit_action($con, [
            'user_type' => 'staff',
            'user_id' => $staffId,
            'user_name' => $staffName,
            'action' => 'create',
            'entity_type' => 'customer',
            'entity_id' => $newId,
            'new_values' => [
                'Name' => $name,
                'Email' => $emailRaw,
                'MobileNumber' => $mobile,
                'Gender' => $gender,
            ],
            'description' => "Staff {$staffName} created customer {$name}",
        ]);

        staff_json_response(true, 'Customer added successfully.', [
            'record_id' => $newId,
            'row_html' => $customerRow ? staff_render_customer_row($customerRow, 1) : '',
        ]);
    }
    
    if ($action === 'get_customer') {
        $id = (int) $_POST['id'];
        $customerResult = mysqli_query($con, "SELECT * FROM tblcustomers WHERE ID = '{$id}'");
        $customerRow = mysqli_fetch_assoc($customerResult);
        if (!$customerRow) {
            staff_json_response(false, 'Customer not found.');
        }
        staff_json_response(true, 'Customer loaded.', ['record' => $customerRow]);
    }
    
    if ($action === 'update_customer') {
        $id = (int) $_POST['id'];
        $name = mysqli_real_escape_string($con, trim($_POST['name']));
        $email = mysqli_real_escape_string($con, trim($_POST['email']));
        $mobile = mysqli_real_escape_string($con, trim($_POST['mobilenum']));
        $gender = mysqli_real_escape_string($con, trim($_POST['gender']));
        
        if ($name === '' || $email === '' || $mobile === '') {
            staff_json_response(false, 'Please complete the customer form.');
        }
        
        $updateQuery = mysqli_query($con, "UPDATE tblcustomers SET Name = '{$name}', Email = '{$email}', MobileNumber = '{$mobile}', Gender = '{$gender}' WHERE ID = '{$id}'");
        if (!$updateQuery) {
            staff_json_response(false, 'Unable to update the customer.');
        }
        
        staff_json_response(true, 'Customer updated successfully.', ['record_id' => $id]);
    }
    
    if ($action === 'delete_customer') {
        $id = (int) $_POST['id'];
        $deleteQuery = mysqli_query($con, "DELETE FROM tblcustomers WHERE ID = '{$id}'");
        
        if (!$deleteQuery) {
            staff_json_response(false, 'Unable to delete the customer.');
        }
        
        staff_json_response(true, 'Customer deleted.', ['record_id' => $id]);
    }
    
    if ($action === 'load_customers') {
        $customersResult = mysqli_query($con, "SELECT * FROM tblcustomers ORDER BY ID DESC");
        $customers = array();
        while ($row = mysqli_fetch_assoc($customersResult)) {
            $customers[] = $row;
        }
        staff_json_response(true, 'Customers loaded.', ['customers' => $customers]);
    }
}

$customersResult = mysqli_query($con, "SELECT * FROM tblcustomers ORDER BY ID DESC");
$activeKey = 'customers';

staff_layout_start('Customers', $activeKey, 'Manage your clients and their information');
?>

<div class="staff-section-head">
    <div class="staff-section-head-left">
        <div>
            <p style="margin: 6px 0 0; color: var(--staff-muted); font-size: 14px;">
                Add and manage customer profiles for appointments and services.
            </p>
        </div>
    </div>
    <button type="button" class="staff-button staff-button-primary" data-bs-toggle="modal" data-bs-target="#customerCreateModal">
        <i class="fa fa-plus"></i>
        Add Customer
    </button>
</div>

<div class="staff-table-card">
    <div class="table-responsive">
        <table class="table" id="customersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Gender</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="customersTableBody">
                <?php $cnt = 1; while ($row = mysqli_fetch_assoc($customersResult)) { ?>
                    <?php echo staff_render_customer_row($row, $cnt++); ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="customerCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <form id="customerCreateForm">
                <div class="modal-header border-0">
                    <div>
                        <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                            <i class="fa fa-user-plus" style="color: var(--staff-accent); margin-right: 10px;"></i>
                            Add Customer
                        </h3>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="ajax_action" value="create_customer">
                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile Number *</label>
                        <input type="text" class="form-control" name="mobilenum" maxlength="15" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Transgender">Transgender</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa fa-save"></i> Save Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="customerEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <form id="customerEditForm">
                <div class="modal-header border-0">
                    <div>
                        <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                            <i class="fa fa-edit" style="color: var(--staff-accent); margin-right: 10px;"></i>
                            Edit Customer
                        </h3>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="ajax_action" value="update_customer">
                    <input type="hidden" name="id" id="customerEditId">
                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name" id="customerEditName" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="customerEditEmail" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile Number *</label>
                        <input type="text" class="form-control" name="mobilenum" id="customerEditMobile" maxlength="10" pattern="[0-9]+" required>
                    </div>
                    <!--<div class="col-md-6">
                        <label class="form-label">Date of Birth *</label>
                        <input type="date" class="form-control" name="dob" id="customerEditDob" required>
                    </div>-->
                    <!--<div class="col-md-6">
                        <label class="form-label">Anniversary Date</label>
                        <input type="date" class="form-control" name="marriage_date" id="customerEditMarriageDate">
                    </div>-->
                    <div class="col-md-6">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender" id="customerEditGender">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Transgender">Transgender</option>
                        </select>
                    </div>
                    <!--<div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="details" id="customerEditDetails" rows="3"></textarea>
                    </div>-->
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa fa-save"></i> Update Customer
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
                <p style="margin-bottom: 0;">Are you sure you want to delete this customer? This action cannot be undone.</p>
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
    var customersTableBody = document.getElementById('customersTableBody');
    
    function renumberRows() {
        if (!customersTableBody) return;
        var rows = customersTableBody.querySelectorAll('tr');
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
            if (modal) {
                modal.hide();
            }
        }
    }

    function prependCustomerRow(rowHtml) {
        if (!customersTableBody || !rowHtml) return;
        customersTableBody.insertAdjacentHTML('afterbegin', rowHtml);
        renumberRows();
    }
    
    document.getElementById('customerEditForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var form = e.currentTarget;
        var formData = new FormData(form);
        
        try {
            var response = await fetch('customers.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var payload = await response.json();
            
            if (payload.success) {
                StaffPortal.showToast(payload.message, 'success');
                form.reset();
                closeModal('customerEditModal');
                location.reload();
            } else {
                StaffPortal.showToast(payload.message || 'Failed to update customer.', 'error');
            }
        } catch (error) {
            StaffPortal.showToast('Failed to update customer.', 'error');
        }
    });

    document.getElementById('customerCreateForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var form = e.currentTarget;
        var formData = new FormData(form);

        try {
            var response = await fetch('customers.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var payload = await response.json();

            if (payload.success) {
                StaffPortal.showToast(payload.message, 'success');
                form.reset();
                closeModal('customerCreateModal');
                prependCustomerRow(payload.row_html);
            } else {
                StaffPortal.showToast(payload.message || 'Failed to add customer.', 'error');
            }
        } catch (error) {
            StaffPortal.showToast('Failed to add customer.', 'error');
        }
    });
    
    document.addEventListener('click', function(e) {
        var editBtn = e.target.closest('.js-edit-customer');
        if (editBtn) {
            var customerId = editBtn.getAttribute('data-id');
            var row = document.getElementById('customer-row-' + customerId);
            if (row) {
                document.getElementById('customerEditId').value = customerId;
                document.getElementById('customerEditName').value = row.children[1].textContent.trim() || '';
                document.getElementById('customerEditEmail').value = row.children[2].textContent.trim() || '';
                document.getElementById('customerEditMobile').value = row.children[3].textContent.trim() || '';
                
                var genderCell = row.children[4];
                var gender = genderCell.textContent.trim();
                document.getElementById('customerEditGender').value = gender === 'N/A' ? '' : gender;
                
                var modal = new bootstrap.Modal(document.getElementById('customerEditModal'));
                modal.show();
            }
            return;
        }
        
        var deleteBtn = e.target.closest('.js-delete-customer');
        if (deleteBtn) {
            pendingDeleteId = deleteBtn.getAttribute('data-id');
            var modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
            return;
        }
    });
    
    document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
        if (!pendingDeleteId) return;
        
        var formData = new FormData();
        formData.append('ajax_action', 'delete_customer');
        formData.append('id', pendingDeleteId);
        
        try {
            var response = await fetch('customers.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var payload = await response.json();
            
            if (payload.success) {
                StaffPortal.showToast(payload.message, 'success');
                var row = document.getElementById('customer-row-' + pendingDeleteId);
                if (row) {
                    row.remove();
                }
                renumberRows();
            } else {
                StaffPortal.showToast(payload.message || 'Failed to delete customer.', 'error');
            }
        } catch (error) {
            StaffPortal.showToast('Failed to delete customer.', 'error');
        }
        
        pendingDeleteId = null;
        closeModal('deleteConfirmModal');
    });
})();
</script>

<?php staff_layout_end(); ?>
