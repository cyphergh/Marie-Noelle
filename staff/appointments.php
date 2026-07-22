<?php
include_once __DIR__ . '/includes/layout.php';
include_once __DIR__ . '/../panel/includes/audit_helper.php';
staff_require_login();

$staff = staff_fetch_current($con);
$staffId = (int) $staff['id'];
$staffName = $staff['name'] ?? 'Unknown Staff';
$spaInfo = @parse_ini_file(__DIR__ . '/../info.ini') ?: array();
$branchName = isset($spaInfo['branch']) ? trim($spaInfo['branch']) : '';
$spaAddress = isset($spaInfo['address']) ? trim($spaInfo['address']) : '';
$spaContact = isset($spaInfo['contact']) ? trim($spaInfo['contact']) : '';
$spaEmail = isset($spaInfo['email']) ? trim($spaInfo['email']) : '';
$logoUrl = '../panel/images/logo.png';

function staff_render_appointment_row($row, $customerMap, $serviceMap)
{
    $customer = staff_resolve_customer($row['Name'], $customerMap);
    $statusInfo = staff_get_appointment_status($row['Status']);
    $total = !empty($row['grand_total']) ? $row['grand_total'] : ($row['total'] ?: 0);
    $paymentStatus = !empty($row['payment_status']) ? $row['payment_status'] : 'Unpaid';
    
    ob_start();
    ?>
    <tr data-appointment-id="<?php echo (int) $row['ID']; ?>">
        <td>
            <strong style="font-weight: 700;"><?php echo staff_escape($row['AptNumber'] ?: '#' . $row['ID']); ?></strong>
        </td>
        <td>
            <strong><?php echo staff_escape($customer['Name']); ?></strong><br>
            <small class="staff-muted"><?php echo staff_escape($customer['Email'] ?: ($row['Email'] ?: '--')); ?></small>
        </td>
        <td>
            <span class="staff-muted"><?php echo staff_escape(staff_service_names($row['Services'], $serviceMap)); ?></span>
        </td>
        <td>
            <strong><?php echo staff_format_date($row['AptDate']); ?></strong><br>
            <small class="staff-muted"><?php echo staff_format_date($row['AptTime'], 'g:i A'); ?></small>
        </td>
        <td style="font-weight: 700; color: var(--staff-green);">
            GH₵ <?php echo staff_format_money($total); ?>
        </td>
        <td>
            <span class="staff-badge <?php echo $statusInfo['class']; ?>">
                <?php echo $statusInfo['label']; ?>
            </span>
        </td>
        <td>
            <?php if ($paymentStatus === 'Paid'): ?>
                <span class="staff-badge is-success">
                    <i class="fa fa-check-circle"></i> Paid
                </span>
            <?php else: ?>
                <span class="staff-badge" style="background: var(--staff-danger-soft); color: var(--staff-danger);">
                    <i class="fa fa-clock"></i> Unpaid
                </span>
            <?php endif; ?>
        </td>

    </tr>
    <?php
    return trim(ob_get_clean());
}

function staff_create_invoice($con, $appointmentId, $customerId, $serviceIds, $serviceTotal, $taxPercent, $staffId, $paymentMethod = 'Cash', $momoTransactionId = '', $discountType = '', $discountValue = 0, $discountAmount = 0) {
    $invoiceId = mt_rand(100000000, 999999999);
    $postingDate = date('Y-m-d H:i:s');
    $momoTxId = mysqli_real_escape_string($con, trim($momoTransactionId));
    $serviceTotalFormatted = number_format((float)$serviceTotal, 2, '.', '');
    $discountTypeDb = !empty($discountType) ? "'" . mysqli_real_escape_string($con, $discountType) . "'" : 'NULL';
    $discountValueDb = $discountValue > 0 ? "'" . number_format((float)$discountValue, 2, '.', '') . "'" : 'NULL';
    $discountAmountDb = $discountAmount > 0 ? "'" . number_format((float)$discountAmount, 2, '.', '') . "'" : 'NULL';
    
    mysqli_query($con, "
        INSERT INTO tblinvoice (Userid, ServiceId, BillingId, staff, tax, total, discount_type, discount_value, discount_amount, PostingDate, payment_method, qty, momo_transaction_id, commision) 
        VALUES ('{$customerId}', '0', '{$invoiceId}', '{$staffId}', '{$taxPercent}', '{$serviceTotalFormatted}', {$discountTypeDb}, {$discountValueDb}, {$discountAmountDb}, '{$postingDate}', '{$paymentMethod}', '1', '{$momoTxId}', '0')
    ");
    
    foreach ($serviceIds as $serviceId) {
        $serviceId = (int) $serviceId;
        if ($serviceId > 0) {
            mysqli_query($con, "
                INSERT INTO tblinvoice (Userid, ServiceId, BillingId, staff, tax, total, PostingDate, payment_method, qty, momo_transaction_id, commision) 
                VALUES ('{$customerId}', '{$serviceId}', '{$invoiceId}', '{$staffId}', '0', '0', '{$postingDate}', '{$paymentMethod}', '1', '{$momoTxId}', '0')
            ");
        }
    }
    
    $paymentStatus = in_array($paymentMethod, array('Cash', 'MoMo', 'POS'), true) ? 'Paid' : 'Unpaid';
    mysqli_query($con, "UPDATE tblappointment SET payment_id = '{$invoiceId}', payment_status = '{$paymentStatus}', payment_method = '{$paymentMethod}', momo_transaction_id = '{$momoTxId}' WHERE ID = '{$appointmentId}'");
    
    return $invoiceId;
}

$customerMap = staff_fetch_customer_map($con);
$serviceMap = staff_fetch_service_map($con);
$customerOptions = mysqli_query($con, "SELECT ID, Name, Email, MobileNumber, Gender FROM tblcustomers ORDER BY Name ASC");
$taxOptions = mysqli_query($con, "SELECT * FROM tbl_tax");
$taxList = array();
if ($taxOptions) {
    while ($taxRow = mysqli_fetch_assoc($taxOptions)) {
        $taxList[] = array(
            'name' => $taxRow['name'] ?? 'Tax',
            'value' => $taxRow['value'] ?? 0,
        );
    }
    mysqli_data_seek($taxOptions, 0);
}

$appointments = mysqli_query($con, "SELECT * FROM tblappointment WHERE Status != '3' AND Status != '2' ORDER BY ID DESC, AptDate DESC, AptTime DESC");
$serviceOptions = mysqli_query($con, "SELECT ID, ServiceName, Cost, type FROM tblservices ORDER BY type ASC, ServiceName ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && staff_is_ajax_request()) {
    $action = isset($_POST['ajax_action']) ? $_POST['ajax_action'] : '';

    if ($action === 'create_walkin_appointment') {
        $customerMode = trim($_POST['customer_mode'] ?? 'existing');
        $selectedCustomerId = (int) ($_POST['customer_id'] ?? 0);
        $customerNameRaw = trim($_POST['customer_name'] ?? '');
        $customerName = mysqli_real_escape_string($con, $customerNameRaw);
        $emailRaw = trim($_POST['email'] ?? '');
        $email = mysqli_real_escape_string($con, $emailRaw);
        $phoneRaw = trim($_POST['phone'] ?? '');
        $phone = mysqli_real_escape_string($con, $phoneRaw);
        $aptDate = date('Y-m-d');
        $aptTime = date('H:i');
        $genderRaw = trim($_POST['gender'] ?? '');
        $serviceIds = isset($_POST['service_ids']) && is_array($_POST['service_ids']) ? $_POST['service_ids'] : array();
        $submittedServicePrices = isset($_POST['service_prices']) && is_array($_POST['service_prices']) ? $_POST['service_prices'] : array();
        $paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Cash';

        if (empty($serviceIds)) {
            staff_json_response(false, 'Please complete all required fields.');
        }

        if (!empty($emailRaw) && !staff_validate_email($emailRaw)) {
            staff_json_response(false, 'Please provide a valid email address.');
        }

        if ($customerMode === 'existing') {
            if ($selectedCustomerId <= 0) {
                staff_json_response(false, 'Please choose a customer from the list.');
            }
        } else {
            if ($customerNameRaw === '' || $phoneRaw === '') {
                staff_json_response(false, 'Please provide the new customer name and phone number.');
            }

            if (!staff_validate_phone($phoneRaw)) {
                staff_json_response(false, 'Please provide a valid phone number.');
            }
        }

        $validServiceIds = array();
        $validServicePrices = array();
        $serviceTotal = 0.00;
        foreach ($serviceIds as $serviceId) {
            $serviceId = (int) $serviceId;
            if ($serviceId > 0 && isset($serviceMap[$serviceId])) {
                $defaultCost = (float) $serviceMap[$serviceId]['Cost'];
                $submittedPrice = isset($submittedServicePrices[$serviceId]) ? trim((string) $submittedServicePrices[$serviceId]) : '';
                $serviceCost = $defaultCost;
                if ($submittedPrice !== '' && is_numeric($submittedPrice)) {
                    $serviceCost = max(0, (float) $submittedPrice);
                }
                $validServiceIds[] = $serviceId;
                $validServicePrices[$serviceId] = number_format($serviceCost, 2, '.', '');
                $serviceTotal += $serviceCost;
            }
        }

        if (empty($validServiceIds)) {
            staff_json_response(false, 'Please select at least one valid service.');
        }

        $customerId = 0;
        $customerRow = null;

        if ($customerMode === 'existing') {
            $customerResult = mysqli_query($con, "SELECT * FROM tblcustomers WHERE ID = '{$selectedCustomerId}' LIMIT 1");
            $customerRow = $customerResult ? mysqli_fetch_assoc($customerResult) : null;

            if (!$customerRow) {
                staff_json_response(false, 'Selected customer was not found.');
            }

            $customerId = (int) $customerRow['ID'];
            $customerNameRaw = $customerRow['Name'];
            $emailRaw = (string) ($customerRow['Email'] ?? '');
            $email = mysqli_real_escape_string($con, $emailRaw);
            $phoneRaw = (string) ($customerRow['MobileNumber'] ?? '');
            $phone = mysqli_real_escape_string($con, $phoneRaw);
        } else {
            $customerLookupSql = !empty($emailRaw)
                ? "SELECT * FROM tblcustomers WHERE Email = '" . mysqli_real_escape_string($con, $emailRaw) . "' LIMIT 1"
                : "SELECT * FROM tblcustomers WHERE MobileNumber = '" . mysqli_real_escape_string($con, $phoneRaw) . "' LIMIT 1";

            $customerResult = mysqli_query($con, $customerLookupSql);
            $customerRow = $customerResult ? mysqli_fetch_assoc($customerResult) : null;

            if ($customerRow) {
                $customerId = (int) $customerRow['ID'];
            } else {
            $gender = in_array($genderRaw, array('Female', 'Male', 'Transgender'), true) 
                ? "'" . mysqli_real_escape_string($con, $genderRaw) . "'" 
                : 'NULL';

            $emailValue = !empty($emailRaw) ? "'" . mysqli_real_escape_string($con, $emailRaw) . "'" : 'NULL';
            
            $insertCustomer = mysqli_query(
                $con,
                "INSERT INTO tblcustomers (Name, Email, MobileNumber, Gender, dob, marriage_date) 
                 VALUES ('{$customerName}', {$emailValue}, '{$phone}', {$gender}, '2025-01-01', '2025-01-01')"
            );

            if (!$insertCustomer) {
                staff_json_response(false, 'Unable to create the customer record.');
            }

            $customerId = mysqli_insert_id($con);
            $customerReload = mysqli_query($con, "SELECT * FROM tblcustomers WHERE ID = '{$customerId}'");
            $customerRow = $customerReload ? mysqli_fetch_assoc($customerReload) : null;
            }
        }

        $appointmentNumber = mt_rand(100000000, 999999999);
        $serviceList = implode(',', $validServiceIds);
        
        $taxPercent = 0;
        foreach ($taxList as $taxRow) {
            $taxPercent += (float) ($taxRow['value'] ?? 0);
        }
        
        $discountType = isset($_POST['discount_type']) ? trim($_POST['discount_type']) : '';
        $discountValue = isset($_POST['discount_value']) ? max(0, (float) $_POST['discount_value']) : 0;
        $preDiscountGrandTotal = $serviceTotal + ($serviceTotal * $taxPercent / 100);
        if ($discountType === 'percentage' && $discountValue > 0) {
            $discountAmount = $preDiscountGrandTotal * min($discountValue, 100) / 100;
        } elseif ($discountType === 'fixed' && $discountValue > 0) {
            $discountAmount = min($discountValue, $preDiscountGrandTotal);
        } else {
            $discountType = '';
            $discountValue = 0;
            $discountAmount = 0;
        }
        $grandTotal = $preDiscountGrandTotal - $discountAmount;
        $grandTotalFormatted = number_format($grandTotal, 2, '.', '');
        $serviceTotalFormatted = number_format($serviceTotal, 2, '.', '');
        $discountAmountFormatted = number_format($discountAmount, 2, '.', '');
        $remarkText = 'Walk-in appointment';
        if (!empty($validServicePrices)) {
            $remarkText .= ' | custom_prices=' . json_encode($validServicePrices);
        }
        $remark = mysqli_real_escape_string($con, $remarkText);
        $emailValue = !empty($emailRaw) ? "'" . mysqli_real_escape_string($con, $emailRaw) . "'" : 'NULL';
        $momoTransactionId = isset($_POST['momo_transaction_id']) ? trim($_POST['momo_transaction_id']) : '';

        $discountTypeDb = !empty($discountType) ? "'" . mysqli_real_escape_string($con, $discountType) . "'" : 'NULL';
        $discountValueDb = $discountValue > 0 ? "'{$discountValue}'" : 'NULL';
        $discountAmountDb = $discountAmount > 0 ? "'{$discountAmountFormatted}'" : 'NULL';

        $insertAppointment = mysqli_query(
            $con,
            "INSERT INTO tblappointment (AptNumber, Name, Email, PhoneNumber, AptDate, AptTime, Services, Remark, Status, total, grand_total, discount_type, discount_value, discount_amount, payment_id, order_id, payment_status, payment_method, momo_transaction_id) 
             VALUES ('{$appointmentNumber}', '{$customerId}', {$emailValue}, '{$phone}', '{$aptDate}', '{$aptTime}', '{$serviceList}', '{$remark}', '1', '{$serviceTotalFormatted}', '{$grandTotalFormatted}', {$discountTypeDb}, {$discountValueDb}, {$discountAmountDb}, '', '', 'Unpaid', '{$paymentMethod}', '{$momoTransactionId}')"
        );

        if (!$insertAppointment) {
            staff_json_response(false, 'Unable to create the appointment.');
        }

        $newId = mysqli_insert_id($con);
        
        $invoiceId = staff_create_invoice($con, $newId, $customerId, $validServiceIds, $serviceTotalFormatted, $taxPercent, $staffId, $paymentMethod, $momoTransactionId, $discountType, $discountValue, $discountAmount);

        $allAppointments = mysqli_query($con, "SELECT * FROM tblappointment WHERE Status != '3' AND Status != '2' ORDER BY ID DESC, AptDate DESC, AptTime DESC");
        $tableHtml = '';
        while ($row = mysqli_fetch_assoc($allAppointments)) {
            $tableHtml .= staff_render_appointment_row($row, $customerMap, $serviceMap);
        }

        $message = in_array($paymentMethod, array('Cash', 'MoMo', 'POS'), true)
            ? 'Walk-in appointment created and invoice generated!' 
            : 'Walk-in appointment created. Invoice generated - pending payment.';

        $serviceNames = array();
        $serviceCosts = array();
        foreach ($validServiceIds as $serviceId) {
            if (isset($serviceMap[$serviceId])) {
                $serviceNames[] = $serviceMap[$serviceId]['ServiceName'];
                $serviceCosts[] = isset($validServicePrices[$serviceId]) ? $validServicePrices[$serviceId] : number_format((float) $serviceMap[$serviceId]['Cost'], 2, '.', '');
            }
        }
        
        log_audit_action($con, [
            'user_type' => 'staff',
            'user_id' => $staffId,
            'user_name' => $staffName,
            'action' => 'create',
            'entity_type' => 'appointment',
            'entity_id' => $newId,
            'new_values' => [
                'AptNumber' => $appointmentNumber,
                'customer_id' => $customerId,
                'services' => $serviceList,
                'total' => $grandTotal
            ],
            'description' => "Staff {$staffName} created walk-in appointment #{$appointmentNumber}"
        ]);

        include_once __DIR__ . '/../panel/includes/sms_helper.php';
        send_sms($phone);

        staff_json_response(true, $message, array(
            'table_html' => $tableHtml,
            'record_id' => $newId,
            'appointment_number' => $appointmentNumber,
            'payment_method' => $paymentMethod,
            'grand_total' => $grandTotal,
            'receipt_data' => array(
                'BillingId' => $invoiceId ?? '',
                'PostingDate' => date('Y-m-d H:i:s'),
                'total' => $serviceTotalFormatted,
                'tax' => $taxPercent,
                'payment_method' => $paymentMethod,
                'qty' => count($validServiceIds),
                'momo_transaction_id' => $momoTransactionId,
                'customer_name' => $customerNameRaw,
                'service_names' => implode('|', $serviceNames),
                'service_costs' => implode('|', $serviceCosts),
                'service_ids' => implode('|', $validServiceIds),
                'tax_list' => $taxList,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
            ),
        ));
    }
    
    if ($action === 'delete_appointment') {
        $id = (int) $_POST['id'];
        
        if ($id <= 0) {
            staff_json_response(false, 'Invalid appointment ID.');
        }
        
        $check = mysqli_query($con, "SELECT payment_id FROM tblappointment WHERE ID = '{$id}'");
        if (!$check || mysqli_num_rows($check) === 0) {
            staff_json_response(false, 'Appointment not found.');
        }
        
        $aptRow = mysqli_fetch_assoc($check);
        $invoiceId = $aptRow['payment_id'];
        
        if (!empty($invoiceId)) {
            $invoiceDelete = mysqli_query($con, "DELETE FROM tblinvoice WHERE BillingId = '{$invoiceId}'");
        }
        
        $deleteQuery = mysqli_query($con, "DELETE FROM tblappointment WHERE ID = '{$id}'");
        
        if (!$deleteQuery) {
            $error = mysqli_error($con);
            staff_json_response(false, 'Failed to delete appointment: ' . $error);
        }
        
        $affected = mysqli_affected_rows($con);
        if ($affected <= 0) {
            staff_json_response(false, 'Appointment not found or already deleted.');
        }
        
        log_audit_action($con, [
            'user_type' => 'staff',
            'user_id' => $staffId,
            'user_name' => $staffName,
            'action' => 'delete',
            'entity_type' => 'appointment',
            'entity_id' => $id,
            'old_values' => ['payment_id' => $invoiceId],
            'description' => "Staff {$staffName} deleted appointment #{$id}"
        ]);
        
        staff_json_response(true, 'Appointment and related payments deleted.', ['record_id' => $id]);
    }
}

staff_layout_start('Appointments', 'appointments', 'Manage bookings and create walk-in visits');
?>
<div class="staff-section-head mb-4">
    <div class="staff-section-head-left">
        <h2>Appointment Queue</h2>
        <p>Review all bookings and create new walk-in visits for in-store services.</p>
    </div>
    <button type="button" class="staff-button staff-button-primary" id="openWalkInModal">
        <i class="fa fa-plus"></i>
        New Walk-In Appointment
    </button>
</div>

<div class="staff-note mb-4">
    <i class="fa fa-info-circle"></i>
    Walk-in bookings create an accepted appointment immediately. Select payment method and process accordingly.
</div>

<section class="staff-table-card">
    <?php if ($appointments && mysqli_num_rows($appointments) > 0): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="staffAppointmentsTable">
                <thead>
                    <tr>
                        <th>Appointment #</th>
                        <th>Customer</th>
                        <th>Services</th>
                        <th>Schedule</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($appointments)): ?>
                        <?php echo staff_render_appointment_row($row, $customerMap, $serviceMap); ?>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="staff-empty-state">
            <i class="fa fa-calendar-times-o"></i>
            <p>No appointments found.</p>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="deleteErrorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <div class="modal-header border-0 pb-0">
                <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                    <i class="fa fa-exclamation-circle" style="color: var(--staff-danger); margin-right: 8px;"></i>
                    Error
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p id="deleteErrorMessage">An error occurred.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="staff-button" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteAppointmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <div class="modal-header border-0 pb-0">
                <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                    <i class="fa fa-exclamation-triangle" style="color: var(--staff-danger); margin-right: 8px;"></i>
                    Delete Appointment
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p>Are you sure you want to delete this appointment? This action cannot be undone and will also delete any associated payments.</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="staff-button" id="confirmDeleteBtn" style="background: var(--staff-danger);">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="walkInAppointmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0; flex: 1; display: flex; flex-direction: column; overflow: hidden;">
            <form id="walkInAppointmentForm" style="display: contents;">
                <div class="modal-header border-0 pb-0" style="padding: 24px 24px 0; flex-shrink: 0;">
                    <div>
                        <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif; font-size: 1.5rem;">
                            <i class="fa fa-calendar-plus-o" style="color: var(--staff-accent); margin-right: 10px;"></i>
                            New Walk-In Appointment
                        </h3>
                        <p class="staff-muted mb-0" style="margin-top: 6px;">Capture an in-store visit for services or products.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 24px; overflow-y: auto; flex: 1; max-height: calc(90vh - 160px);">
                    <input type="hidden" name="ajax_action" value="create_walkin_appointment">
                    <div class="walkin-stepper">
                        <div class="walkin-step-panel is-active" data-step-panel="1">
                            <div class="row g-4">
                                <div class="col-12">
                                    <label class="form-label">Customer Source *</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <label class="payment-method-option" style="flex: 1; min-width: 220px;">
                                            <input type="radio" name="customer_mode" value="existing" checked style="display: none;">
                                            <div class="staff-card customer-mode-card" data-value="existing" style="padding: 16px; cursor: pointer; border: 2px solid var(--staff-green); background: var(--staff-green-soft);">
                                                <div style="font-weight: 700; color: var(--staff-green);">Choose Existing Customer</div>
                                                <small class="staff-muted">Search and select from your customer list.</small>
                                            </div>
                                        </label>
                                        <label class="payment-method-option" style="flex: 1; min-width: 220px;">
                                            <input type="radio" name="customer_mode" value="new" style="display: none;">
                                            <div class="staff-card customer-mode-card" data-value="new" style="padding: 16px; cursor: pointer; border: 2px solid var(--staff-border);">
                                                <div style="font-weight: 700; color: var(--staff-muted);">Add New Customer</div>
                                                <small class="staff-muted">Create a customer and book them immediately.</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12" id="existingCustomerSection">
                                    <input type="hidden" name="customer_id" id="walkInCustomerId" value="">
                                    <div class="staff-card" style="padding: 18px;">
                                        <label class="form-label" style="margin-bottom: 6px;">Search Customer *</label>
                                        <p class="staff-muted mb-0" style="font-size: 13px; margin-bottom: 10px;">Search by name, phone number, or email, then click to select.</p>
                                        <input type="text" class="form-control" id="walkInCustomerSearch" placeholder="Search customers..." style="margin-bottom: 12px;">
                                        <div id="inlineCustomerList" class="inline-customer-list">
                                            <?php if ($customerOptions && mysqli_num_rows($customerOptions) > 0): ?>
                                                <?php while ($customer = mysqli_fetch_assoc($customerOptions)): ?>
                                                    <div class="customer-inline-option"
                                                         data-id="<?php echo (int) $customer['ID']; ?>"
                                                         data-name="<?php echo staff_escape($customer['Name']); ?>"
                                                         data-email="<?php echo staff_escape($customer['Email']); ?>"
                                                         data-phone="<?php echo staff_escape($customer['MobileNumber']); ?>"
                                                         data-gender="<?php echo staff_escape($customer['Gender']); ?>"
                                                         onclick="selectInlineCustomer(this)"
                                                         style="padding: 10px 14px; cursor: pointer; border-bottom: 1px solid var(--staff-border); transition: background 0.15s; display: flex; align-items: center; gap: 10px;">
                                                        <i class="fa fa-circle-o customer-option-icon" style="color: var(--staff-muted); font-size: 14px; width: 16px; flex-shrink: 0;"></i>
                                                        <div style="flex: 1; min-width: 0;">
                                                            <strong class="customer-option-name" style="display: block; font-size: 14px;"><?php echo staff_escape($customer['Name']); ?></strong>
                                                            <small class="staff-muted customer-option-meta" style="font-size: 12px;">
                                                                <?php
                                                                $meta = [];
                                                                if (!empty($customer['MobileNumber'])) $meta[] = staff_escape($customer['MobileNumber']);
                                                                if (!empty($customer['Email'])) $meta[] = staff_escape($customer['Email']);
                                                                echo implode(' | ', $meta);
                                                                ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <div class="staff-muted" style="padding: 20px; text-align: center; font-size: 13px;">No customers found.</div>
                                            <?php endif; ?>
                                        </div>
                                        <div id="selectedCustomerSummary" style="margin-top: 12px; padding: 10px 14px; background: var(--staff-green-soft); border-radius: var(--staff-radius); display: none;">
                                            <strong id="selectedCustomerName" style="display: block; margin-bottom: 2px; color: var(--staff-green);">Customer selected</strong>
                                            <span id="selectedCustomerMeta" class="staff-muted" style="font-size: 13px;"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12" id="newCustomerSection" style="display: none;">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Customer Name *</label>
                                            <input type="text" class="form-control" name="customer_name" required placeholder="Enter customer name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" placeholder="Enter email address">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number *</label>
                                            <input type="tel" class="form-control" name="phone" required placeholder="Enter phone number">
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
                                </div>
                            </div>
                        </div>

                        <div class="walkin-step-panel" data-step-panel="2">
                            <label class="form-label">Services / Products *</label>
                            <select class="form-control" name="service_ids[]" id="walkInServiceIds" multiple required style="display: none;">
                                <?php
                                mysqli_data_seek($serviceOptions, 0);
                                while ($service = mysqli_fetch_assoc($serviceOptions)):
                                    $typeLabel = (int) $service['type'] === 1 ? 'Product' : 'Service';
                                ?>
                                    <option
                                        value="<?php echo (int) $service['ID']; ?>"
                                        data-cost="<?php echo number_format((float) $service['Cost'], 2, '.', ''); ?>"
                                        data-default-cost="<?php echo number_format((float) $service['Cost'], 2, '.', ''); ?>"
                                        data-name="<?php echo staff_escape($service['ServiceName']); ?>"
                                        data-type="<?php echo $typeLabel; ?>"
                                    >
                                        <?php echo staff_escape($service['ServiceName']); ?> - GH₵ <?php echo staff_format_money($service['Cost']); ?> (<?php echo $typeLabel; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div id="walkInServicePriceInputs"></div>
                            <div class="staff-card" style="padding: 18px;">
                                <p class="staff-muted mb-0" style="font-size: 13px; margin-bottom: 10px;">Search, select multiple items, and adjust prices when needed.</p>
                                <input type="text" class="form-control" id="walkInServiceSearch" placeholder="Search services or products" style="margin-bottom: 12px;">
                                <div id="walkInServicePickerList" style="display: grid; gap: 10px; overflow-y: auto;">
                                    <?php
                                    mysqli_data_seek($serviceOptions, 0);
                                    while ($service = mysqli_fetch_assoc($serviceOptions)):
                                        $typeLabel = (int) $service['type'] === 1 ? 'Product' : 'Service';
                                    ?>
                                        <label
                                            class="service-picker-option staff-card"
                                            data-search="<?php echo staff_escape(strtolower($service['ServiceName'] . ' ' . $typeLabel)); ?>"
                                            data-default-cost="<?php echo number_format((float) $service['Cost'], 2, '.', ''); ?>"
                                            style="padding: 14px 16px; cursor: pointer; box-shadow: none;"
                                        >
                                            <span style="display: flex; align-items: flex-start; gap: 12px;">
                                                <input
                                                    type="checkbox"
                                                    class="service-picker-checkbox"
                                                    value="<?php echo (int) $service['ID']; ?>"
                                                    data-default-cost="<?php echo number_format((float) $service['Cost'], 2, '.', ''); ?>"
                                                    style="margin-top: 4px;"
                                                >
                                                <span style="display: block; flex: 1; min-width: 0;">
                                                    <span style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">
                                                        <span style="display: block;">
                                                            <strong style="display: block;"><?php echo staff_escape($service['ServiceName']); ?></strong>
                                                            <small class="staff-muted"><?php echo $typeLabel; ?> • Default GH₵ <?php echo staff_format_money($service['Cost']); ?></small>
                                                        </span>
                                                        <span style="display: block; min-width: 150px;">
                                                            <small class="staff-muted" style="display: block; margin-bottom: 4px;">Custom Price</small>
                                                            <input
                                                                type="number"
                                                                class="form-control service-price-input"
                                                                value="<?php echo number_format((float) $service['Cost'], 2, '.', ''); ?>"
                                                                min="0"
                                                                step="0.01"
                                                                inputmode="decimal"
                                                                data-service-id="<?php echo (int) $service['ID']; ?>"
                                                            >
                                                        </span>
                                                    </span>
                                                </span>
                                            </span>
                                        </label>
                                    <?php endwhile; ?>
                                </div>
                                <div id="selectedServicesSummary" class="staff-card" style="margin-top: 14px; padding: 14px; background: rgba(255,255,255,0.72); box-shadow: none; border: 1px dashed var(--staff-border); display: none;">
                                    <strong id="selectedServicesTitle" style="display: block; margin-bottom: 4px;">No services selected</strong>
                                    <span id="selectedServicesMeta" class="staff-muted" style="font-size: 13px;">Choose one or more services/products to continue.</span>
                                </div>
                            </div>
                        </div>

                        <div class="walkin-step-panel" data-step-panel="3">
                            <div class="row g-4">
                                <div class="col-12">
                                    <div class="staff-card" style="padding: 14px 18px; margin-bottom: 16px; background: linear-gradient(135deg, rgba(177, 132, 88, 0.08), rgba(43, 91, 85, 0.08));">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span style="font-weight: 600; font-size: 14px;">
                                                <span id="step3ItemCount">0</span> item(s) selected
                                            </span>
                                            <span style="font-weight: 700; font-size: 16px; color: var(--staff-green);">
                                                Total: GH₵ <span id="step3TotalAmount">0.00</span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="staff-card" style="padding: 18px; margin-bottom: 16px;">
                                        <label class="form-label">Discount (optional)</label>
                                        <div class="d-flex gap-3 align-items-start flex-wrap">
                                            <label class="payment-method-option" style="flex: 1; min-width: 140px;">
                                                <input type="radio" name="discount_type" value="" checked style="display: none;">
                                                <div class="staff-card discount-type-card" data-value="" style="text-align: center; cursor: pointer; border: 2px solid var(--staff-border); padding: 10px;">
                                                    <div style="font-weight: 700; font-size: 13px; color: var(--staff-muted);">No Discount</div>
                                                </div>
                                            </label>
                                            <label class="payment-method-option" style="flex: 1; min-width: 140px;">
                                                <input type="radio" name="discount_type" value="percentage" style="display: none;">
                                                <div class="staff-card discount-type-card" data-value="percentage" style="text-align: center; cursor: pointer; border: 2px solid var(--staff-border); padding: 10px;">
                                                    <div style="font-weight: 700; font-size: 13px; color: var(--staff-muted);">Percentage (%)</div>
                                                </div>
                                            </label>
                                            <label class="payment-method-option" style="flex: 1; min-width: 140px;">
                                                <input type="radio" name="discount_type" value="fixed" style="display: none;">
                                                <div class="staff-card discount-type-card" data-value="fixed" style="text-align: center; cursor: pointer; border: 2px solid var(--staff-border); padding: 10px;">
                                                    <div style="font-weight: 700; font-size: 13px; color: var(--staff-muted);">Fixed (GH₵)</div>
                                                </div>
                                            </label>
                                        </div>
                                        <div id="discountValueWrapper" style="display: none; margin-top: 12px;">
                                            <label class="form-label" id="discountValueLabel">Discount Value</label>
                                            <input type="number" class="form-control" name="discount_value" id="discountValueInput" min="0" step="0.01" placeholder="Enter discount value" style="max-width: 220px;">
                                        </div>
                                    </div>
                                    <label class="form-label">Payment Method *</label>
                                    <div class="d-flex gap-3 flex-wrap">
                                        <label class="payment-method-option" style="flex: 1; min-width: 160px;">
                                            <input type="radio" name="payment_method" value="Cash" checked style="display: none;">
                                            <div class="staff-card payment-method-card" id="cashPaymentCard" data-value="Cash" style="text-align: center; cursor: pointer; border: 2px solid var(--staff-green); background: var(--staff-green-soft);">
                                                <i class="fa fa-money-bill-wave" style="color: var(--staff-green);"></i>
                                                <div style="font-weight: 700; color: var(--staff-green);">Cash</div>
                                                <small class="staff-muted">Pay with cash</small>
                                            </div>
                                        </label>
                                        <label class="payment-method-option" style="flex: 1; min-width: 160px;">
                                            <input type="radio" name="payment_method" value="MoMo" style="display: none;">
                                            <div class="staff-card payment-method-card" id="momoPaymentCard" data-value="MoMo" style="text-align: center; cursor: pointer; border: 2px solid var(--staff-border);">
                                                <i class="fa fa-mobile-alt" style="color: var(--staff-muted);"></i>
                                                <div style="font-weight: 700; color: var(--staff-muted);">MoMo</div>
                                                <small class="staff-muted">Pay with Mobile Money</small>
                                            </div>
                                        </label>
                                        <label class="payment-method-option" style="flex: 1; min-width: 160px;">
                                            <input type="radio" name="payment_method" value="POS" style="display: none;">
                                            <div class="staff-card payment-method-card" id="posPaymentCard" data-value="POS" style="text-align: center; cursor: pointer; border: 2px solid var(--staff-border);">
                                                <i class="fa fa-credit-card" style="color: var(--staff-muted);"></i>
                                                <div style="font-weight: 700; color: var(--staff-muted);">POS</div>
                                                <small class="staff-muted">Pay with card or terminal</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12" id="momoTransactionIdWrapper" style="display: none;">
                                    <label class="form-label">MoMo Transaction ID *</label>
                                    <input type="text" class="form-control" name="momo_transaction_id" id="momoTransactionId" placeholder="Enter MoMo transaction ID">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0" style="padding: 0 24px 24px; flex-shrink: 0;">
                    <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                    <div style="display: flex; gap: 10px; margin-left: auto;">
                        <button type="button" class="staff-button" id="walkInPrevStepBtn" style="display: none;">
                            <i class="fa fa-arrow-left"></i>
                            Previous
                        </button>
                        <button type="button" class="staff-button staff-button-primary" id="walkInNextStepBtn">
                            Next
                            <i class="fa fa-arrow-right"></i>
                        </button>
                        <button type="submit" class="staff-button staff-button-primary" id="createAppointmentBtn" style="display: none;">
                        <i class="fa fa-check"></i>
                        <span id="createBtnText">Create & Collect Payment</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="walkInConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-height: 88vh;">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0; overflow: hidden;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                        <i class="fa fa-check-circle" style="color: var(--staff-green); margin-right: 10px;"></i>
                        Confirm Walk-In Booking
                    </h3>
                    <p class="staff-muted mb-0" style="margin-top: 6px;">Review selected items and payment summary before completing the booking.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 24px; overflow-y: auto;">
                <div class="staff-card" style="padding: 18px; margin-bottom: 16px;">
                    <h4 style="margin: 0 0 12px; font-family: 'Libre Baskerville', serif; font-size: 1.05rem;">Selected Items</h4>
                    <div id="confirmSelectedItemsEmpty" class="staff-muted" style="font-size: 13px;">No items selected.</div>
                    <div id="confirmSelectedItemsList" style="display: grid; gap: 10px;"></div>
                </div>
                <div class="staff-card" style="background: linear-gradient(135deg, rgba(177, 132, 88, 0.08), rgba(43, 91, 85, 0.08)); padding: 18px;">
                    <h4 style="margin: 0 0 14px; font-family: 'Libre Baskerville', serif; font-size: 1.05rem;">Pricing Summary</h4>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="color: var(--staff-muted); font-size: 13px;">Subtotal</span>
                        <span id="confirmSubtotal" style="font-weight: 700;">GH₵ 0.00</span>
                    </div>
                    <?php
                    mysqli_data_seek($taxOptions, 0);
                    while ($tax = mysqli_fetch_assoc($taxOptions)):
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span style="color: var(--staff-muted); font-size: 13px;"><?php echo staff_escape($tax['name']); ?> (<?php echo (float) $tax['value']; ?>%)</span>
                            <span class="confirm-tax-amount" data-tax-percent="<?php echo (float) $tax['value']; ?>">GH₵ 0.00</span>
                        </div>
                    <?php endwhile; ?>
                    <div class="d-flex justify-content-between align-items-center mb-2" id="confirmDiscountRow" style="display: none;">
                        <span style="color: var(--staff-danger); font-size: 13px; font-weight: 700;">Discount</span>
                        <span id="confirmDiscountAmount" style="font-weight: 700; color: var(--staff-danger);">GH₵ 0.00</span>
                    </div>
                    <hr style="border-color: var(--staff-border); margin: 12px 0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: 0.08em;">Grand Total</span>
                        <strong id="confirmGrandTotal" style="font-size: 1.35rem; color: var(--staff-green);">GH₵ 0.00</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="staff-button" data-bs-dismiss="modal">Back</button>
                <button type="button" class="staff-button staff-button-primary" id="confirmCreateAppointmentBtn">
                    <i class="fa fa-check"></i>
                    Confirm Booking
                </button>
            </div>
        </div>
    </div>
</div>
<style>
        display: flex;
        flex-direction: column;
        gap: 24px;
    }
    .walkin-step-panel {
        display: none;
    }
    .walkin-step-panel.is-active {
        display: block;
    }
    .payment-method-card {
        padding: 12px 10px;
        min-height: 108px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        transition: all 0.2s ease;
    }
    .payment-method-card i {
        font-size: 20px;
        margin-bottom: 4px;
    }
    .payment-method-card small {
        font-size: 11px;
        line-height: 1.35;
    }
    .payment-method-option input:checked + .payment-method-card {
        border-color: var(--staff-green) !important;
        background: var(--staff-green-soft) !important;
    }
    .payment-method-option input:checked + .payment-method-card i {
        color: var(--staff-green) !important;
    }
    .payment-method-option input:checked + .payment-method-card div {
        color: var(--staff-green) !important;
    }
    .payment-method-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--staff-shadow-soft);
    }
    .inline-customer-list {
        max-height: 220px;
        overflow-y: auto;
        border: 1px solid var(--staff-border);
        border-radius: var(--staff-radius);
        background: #fff;
    }
    .customer-inline-option:hover {
        background: rgba(43, 91, 85, 0.06);
    }
    .customer-inline-option.is-selected {
        background: rgba(43, 91, 85, 0.1);
        border-color: var(--staff-green) !important;
    }
    .customer-inline-option.is-selected .customer-option-name {
        color: var(--staff-green);
    }
    .customer-inline-option:last-child {
        border-bottom: none !important;
    }
    .service-picker-option {
        transition: border-color 0.2s ease, background 0.2s ease, transform 0.2s ease;
        border: 1px solid var(--staff-border);
    }
    .service-picker-option.is-selected {
        border-color: rgba(43, 91, 85, 0.28);
        background: rgba(43, 91, 85, 0.08);
    }
    .confirm-item-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid var(--staff-border);
    }
</style>

<script>
    (function () {
        'use strict';

        var walkInModalInstance = null;
        var confirmModalInstance = null;
        var currentForm = null;
        var isConfirmingSubmit = false;
        var latestReceiptData = null;

        function initWalkInModal() {
            var modalElement = document.getElementById('walkInAppointmentModal');
            var form = document.getElementById('walkInAppointmentForm');
            var openButton = document.getElementById('openWalkInModal');
            var serviceSelect = document.getElementById('walkInServiceIds');
            var servicePriceInputs = document.getElementById('walkInServicePriceInputs');
            var tableBody = document.querySelector('#staffAppointmentsTable tbody');
            var cashCard = document.getElementById('cashPaymentCard');
            var createBtnText = document.getElementById('createBtnText');
            var createAppointmentBtn = document.getElementById('createAppointmentBtn');
            var confirmModal = document.getElementById('walkInConfirmModal');
            var customerSearch = document.getElementById('walkInCustomerSearch');
            var customerSelect = document.getElementById('walkInCustomerId');
            var inlineCustomerList = document.getElementById('inlineCustomerList');
            var serviceSearch = document.getElementById('walkInServiceSearch');
            var servicePickerList = document.getElementById('walkInServicePickerList');
            var selectedServicesTitle = document.getElementById('selectedServicesTitle');
            var selectedServicesMeta = document.getElementById('selectedServicesMeta');
            var existingCustomerSection = document.getElementById('existingCustomerSection');
            var newCustomerSection = document.getElementById('newCustomerSection');
            var selectedCustomerName = document.getElementById('selectedCustomerName');
            var selectedCustomerMeta = document.getElementById('selectedCustomerMeta');
            var confirmSelectedItemsEmpty = document.getElementById('confirmSelectedItemsEmpty');
            var confirmSelectedItemsList = document.getElementById('confirmSelectedItemsList');
            var confirmSubtotal = document.getElementById('confirmSubtotal');
            var confirmGrandTotal = document.getElementById('confirmGrandTotal');
            var confirmCreateAppointmentBtn = document.getElementById('confirmCreateAppointmentBtn');
            var stepPanels = form.querySelectorAll('.walkin-step-panel');
            var prevStepBtn = document.getElementById('walkInPrevStepBtn');
            var nextStepBtn = document.getElementById('walkInNextStepBtn');
            var currentStep = 1;
            var totalSteps = stepPanels.length || 3;

            if (!modalElement || !form || !openButton || !serviceSelect) {
                console.log('Modal elements not found');
                return;
            }

            currentForm = form;

            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                if (walkInModalInstance) {
                    modalElement.removeEventListener('hidden.bs.modal', handleModalHidden);
                }
                walkInModalInstance = new bootstrap.Modal(modalElement);
                modalElement.addEventListener('hidden.bs.modal', handleModalHidden);
                if (confirmModal) {
                    confirmModalInstance = new bootstrap.Modal(confirmModal);
                }
            } else {
                console.log('Bootstrap Modal not available');
                return;
            }

            function handleModalHidden() {
                form.reset();
                if (customerSearch) customerSearch.value = '';
                if (customerSelect) {
                    customerSelect.value = '';
                }
                clearInlineCustomerSelection();
                filterInlineCustomerOptions('');
                if (serviceSearch) serviceSearch.value = '';
                clearSelectedServices();
                clearInlineServiceSelection();
                filterServiceOptions('');
                updateSelectedServicesSummary();
                updateSelectedCustomerSummary();
                recalculateTotal();
                updateCustomerModeUI();
                updatePaymentMethodUI();
                updateDiscountTypeUI();
                goToStep(1);
                isConfirmingSubmit = false;
            }

            function validateStep(stepNumber) {
                if (stepNumber === 1) {
                    var customerMode = (form.querySelector('input[name="customer_mode"]:checked') || {}).value || 'existing';
                    if (customerMode === 'existing' && !customerSelect.value) {
                        window.StaffPortal.showToast('Please choose a customer first.', 'error');
                        return false;
                    }
                    if (customerMode === 'new') {
                        var nameInput = form.querySelector('input[name="customer_name"]');
                        var phoneInput = form.querySelector('input[name="phone"]');
                        if (!nameInput || !nameInput.value.trim() || !phoneInput || !phoneInput.value.trim()) {
                            window.StaffPortal.showToast('Please provide the new customer name and phone number.', 'error');
                            return false;
                        }
                    }
                }

                if (stepNumber === 2) {
                    if (!serviceSelect.selectedOptions.length) {
                        window.StaffPortal.showToast('Please choose at least one service or product.', 'error');
                        return false;
                    }
                }

                if (stepNumber === 3) {
                    var selectedPaymentMethod = (form.querySelector('input[name="payment_method"]:checked') || {}).value || 'Cash';
                    var momoInput = document.getElementById('momoTransactionId');
                    if (selectedPaymentMethod === 'MoMo' && momoInput && !momoInput.value.trim()) {
                        window.StaffPortal.showToast('Please enter the MoMo transaction ID.', 'error');
                        return false;
                    }
                }

                return true;
            }

            function updateStepperUI() {
                for (var i = 0; i < stepPanels.length; i++) {
                    var stepNumber = i + 1;
                    stepPanels[i].classList.toggle('is-active', stepNumber === currentStep);
                }

                if (prevStepBtn) {
                    prevStepBtn.style.display = currentStep === 1 ? 'none' : 'inline-flex';
                }
                if (nextStepBtn) {
                    nextStepBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
                }
                if (createAppointmentBtn) {
                    createAppointmentBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
                }
            }

            function goToStep(stepNumber) {
                if (stepNumber < 1 || stepNumber > totalSteps) {
                    return;
                }
                currentStep = stepNumber;
                updateStepperUI();
            }

            function attemptStepChange(targetStep) {
                if (targetStep > currentStep && !validateStep(currentStep)) {
                    return;
                }
                goToStep(targetStep);
            }

            function filterInlineCustomerOptions(searchTerm) {
                if (!inlineCustomerList) {
                    return;
                }

                var term = (searchTerm || '').toLowerCase().trim();
                var items = inlineCustomerList.querySelectorAll('.customer-inline-option');
                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    var haystack = [
                        item.getAttribute('data-name') || '',
                        item.getAttribute('data-email') || '',
                        item.getAttribute('data-phone') || ''
                    ].join(' ').toLowerCase();
                    item.style.display = term !== '' && haystack.indexOf(term) === -1 ? 'none' : 'flex';
                }
            }

            function clearInlineCustomerSelection() {
                if (!inlineCustomerList) return;
                var selected = inlineCustomerList.querySelectorAll('.customer-inline-option.is-selected');
                for (var i = 0; i < selected.length; i++) {
                    selected[i].classList.remove('is-selected');
                    var icon = selected[i].querySelector('.customer-option-icon');
                    if (icon) {
                        icon.className = 'fa fa-circle-o customer-option-icon';
                        icon.style.color = 'var(--staff-muted)';
                    }
                }
            }

            function updateSelectedCustomerSummary() {
                if (!selectedCustomerName || !selectedCustomerMeta || !customerSelect) {
                    return;
                }

                if (!customerSelect.value) {
                    selectedCustomerName.textContent = 'No customer selected';
                    selectedCustomerMeta.textContent = 'Pick an existing customer to continue.';
                    document.getElementById('selectedCustomerSummary').style.display = 'none';
                    return;
                }

                var selectedOption = inlineCustomerList ? inlineCustomerList.querySelector('.customer-inline-option[data-id="' + customerSelect.value + '"]') : null;
                if (!selectedOption) {
                    selectedCustomerName.textContent = 'No customer selected';
                    selectedCustomerMeta.textContent = 'Pick an existing customer to continue.';
                    document.getElementById('selectedCustomerSummary').style.display = 'none';
                    return;
                }

                selectedCustomerName.textContent = selectedOption.getAttribute('data-name') || 'Selected customer';
                var meta = [];
                var phone = selectedOption.getAttribute('data-phone') || '';
                var email = selectedOption.getAttribute('data-email') || '';
                if (phone) meta.push(phone);
                if (email) meta.push(email);
                selectedCustomerMeta.textContent = meta.length ? meta.join(' | ') : 'Customer selected';
                document.getElementById('selectedCustomerSummary').style.display = 'block';
            }

            function filterServiceOptions(searchTerm) {
                if (!servicePickerList) {
                    return;
                }

                var term = (searchTerm || '').toLowerCase().trim();
                var items = servicePickerList.querySelectorAll('.service-picker-option');
                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    var haystack = item.getAttribute('data-search') || '';
                    item.style.display = term !== '' && haystack.indexOf(term) === -1 ? 'none' : '';
                }
            }

            function normalizeServicePrice(value, fallback) {
                var parsed = parseFloat(value);
                if (isNaN(parsed) || parsed < 0) {
                    parsed = parseFloat(fallback);
                }
                if (isNaN(parsed) || parsed < 0) {
                    parsed = 0;
                }
                return parsed.toFixed(2);
            }

            function syncServicePriceInputs() {
                if (!servicePriceInputs || !serviceSelect) {
                    return;
                }

                servicePriceInputs.innerHTML = '';
                var selectedOptions = serviceSelect.selectedOptions;
                for (var i = 0; i < selectedOptions.length; i++) {
                    var option = selectedOptions[i];
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'service_prices[' + option.value + ']';
                    input.value = normalizeServicePrice(option.getAttribute('data-cost'), option.getAttribute('data-default-cost'));
                    servicePriceInputs.appendChild(input);
                }
            }

            function syncInlineServicesToSelect() {
                if (!servicePickerList || !serviceSelect) {
                    return;
                }

                clearSelectedServices();

                var checkedBoxes = servicePickerList.querySelectorAll('.service-picker-checkbox:checked');
                for (var i = 0; i < checkedBoxes.length; i++) {
                    var box = checkedBoxes[i];
                    var option = serviceSelect.querySelector('option[value="' + box.value + '"]');
                    if (option) {
                        option.selected = true;
                        var priceInput = box.closest('.service-picker-option').querySelector('.service-price-input');
                        var defaultCost = option.getAttribute('data-default-cost');
                        option.setAttribute('data-cost', normalizeServicePrice(priceInput ? priceInput.value : defaultCost, defaultCost));
                    }
                }

                syncServicePriceInputs();
                updateSelectedServicesSummary();
                recalculateTotal();
                updateConfirmModalSummary();
            }

            function clearInlineServiceSelection() {
                if (!servicePickerList) return;
                var checkboxes = servicePickerList.querySelectorAll('.service-picker-checkbox');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = false;
                    var card = checkboxes[i].closest('.service-picker-option');
                    if (card) {
                        card.classList.remove('is-selected');
                    }
                    var priceInput = checkboxes[i].closest('.service-picker-option').querySelector('.service-price-input');
                    if (priceInput) {
                        priceInput.value = normalizeServicePrice(checkboxes[i].getAttribute('data-default-cost'), 0);
                    }
                }
            }

            function clearSelectedServices() {
                if (!serviceSelect) {
                    return;
                }

                var options = serviceSelect.options;
                for (var i = 0; i < options.length; i++) {
                    options[i].selected = false;
                    options[i].setAttribute('data-cost', options[i].getAttribute('data-default-cost'));
                }

                if (servicePriceInputs) {
                    servicePriceInputs.innerHTML = '';
                }
            }

            function updateSelectedServicesSummary() {
                if (!serviceSelect || !selectedServicesTitle || !selectedServicesMeta) {
                    return;
                }

                var count = serviceSelect.selectedOptions.length;
                var summaryEl = document.getElementById('selectedServicesSummary');
                if (!count) {
                    selectedServicesTitle.textContent = 'No services selected';
                    selectedServicesMeta.textContent = 'Choose one or more services/products to continue.';
                    if (summaryEl) summaryEl.style.display = 'none';
                    return;
                }

                selectedServicesTitle.textContent = count + (count === 1 ? ' item selected' : ' items selected');
                selectedServicesMeta.textContent = 'Selection saved. You can review full item details before confirming the booking.';
                if (summaryEl) summaryEl.style.display = 'block';
            }

            function getSelectedServiceData() {
                var selected = [];
                var selectedOptions = serviceSelect.selectedOptions;
                for (var i = 0; i < selectedOptions.length; i++) {
                    var itemName = selectedOptions[i].getAttribute('data-name') || selectedOptions[i].textContent;
                    var itemType = selectedOptions[i].getAttribute('data-type') || '';
                    selected.push({
                        label: itemType ? itemName + ' (' + itemType + ')' : itemName,
                        cost: parseFloat(selectedOptions[i].getAttribute('data-cost')) || 0
                    });
                }
                return selected;
            }

            function updateConfirmModalSummary() {
                var selected = getSelectedServiceData();

                if (confirmSelectedItemsList) {
                    confirmSelectedItemsList.innerHTML = '';
                }

                if (confirmSelectedItemsEmpty) {
                    confirmSelectedItemsEmpty.style.display = selected.length ? 'none' : 'block';
                }

                for (var i = 0; i < selected.length; i++) {
                    var item = selected[i];
                    if (confirmSelectedItemsList) {
                        var row = document.createElement('div');
                        row.className = 'confirm-item-row';
                        row.innerHTML = '<span>' + item.label + '</span><strong>GH₵ ' + item.cost.toFixed(2) + '</strong>';
                        confirmSelectedItemsList.appendChild(row);
                    }
                }

                recalculateTotal();
            }

            function updateCustomerModeUI() {
                var radios = form.querySelectorAll('input[name="customer_mode"]');
                var selectedValue = 'existing';
                for (var i = 0; i < radios.length; i++) {
                    if (radios[i].checked) {
                        selectedValue = radios[i].value;
                        break;
                    }
                }

                var cards = form.querySelectorAll('.customer-mode-card');
                for (var j = 0; j < cards.length; j++) {
                    var card = cards[j];
                    var isActive = card.getAttribute('data-value') === selectedValue;
                    card.style.borderColor = isActive ? 'var(--staff-green)' : 'var(--staff-border)';
                    card.style.background = isActive ? 'var(--staff-green-soft)' : '';
                    var title = card.querySelector('div');
                    if (title) {
                        title.style.color = isActive ? 'var(--staff-green)' : 'var(--staff-muted)';
                    }
                }

                if (existingCustomerSection) {
                    existingCustomerSection.style.display = selectedValue === 'existing' ? 'block' : 'none';
                }
                if (newCustomerSection) {
                    newCustomerSection.style.display = selectedValue === 'new' ? 'block' : 'none';
                }

                var customerIdField = form.querySelector('[name="customer_id"]');
                var nameField = form.querySelector('[name="customer_name"]');
                var phoneField = form.querySelector('[name="phone"]');
                if (customerIdField) {
                    if (selectedValue !== 'existing') {
                        customerIdField.value = '';
                    }
                }
                if (nameField) {
                    nameField.required = selectedValue === 'new';
                }
                if (phoneField) {
                    phoneField.required = selectedValue === 'new';
                }
            }

            function recalculateTotal() {
                var subtotal = 0;
                var options = serviceSelect.selectedOptions;
                for (var i = 0; i < options.length; i++) {
                    var cost = parseFloat(options[i].getAttribute('data-cost')) || 0;
                    subtotal += cost;
                }

                if (confirmSubtotal) {
                    confirmSubtotal.textContent = 'GH₵ ' + subtotal.toFixed(2);
                }

                var confirmTaxAmounts = document.querySelectorAll('.confirm-tax-amount');
                var totalTaxAmount = 0;
                for (var k = 0; k < confirmTaxAmounts.length; k++) {
                    var confirmTaxEl = confirmTaxAmounts[k];
                    var confirmPercent = parseFloat(confirmTaxEl.getAttribute('data-tax-percent')) || 0;
                    var confirmTaxAmt = subtotal * (confirmPercent / 100);
                    confirmTaxEl.textContent = 'GH₵ ' + confirmTaxAmt.toFixed(2);
                    totalTaxAmount += confirmTaxAmt;
                }

                var preDiscountGrandTotal = subtotal + totalTaxAmount;

                var discountTypeEl = form.querySelector('input[name="discount_type"]:checked');
                var discountType = discountTypeEl ? discountTypeEl.value : '';
                var discountValue = parseFloat(document.getElementById('discountValueInput') ? document.getElementById('discountValueInput').value : 0) || 0;
                var discountAmount = 0;
                var discountValueLabel = document.getElementById('discountValueLabel');

                if (discountType === 'percentage' && discountValue > 0) {
                    discountAmount = preDiscountGrandTotal * Math.min(discountValue, 100) / 100;
                    if (discountValueLabel) discountValueLabel.textContent = 'Discount (%)';
                } else if (discountType === 'fixed' && discountValue > 0) {
                    discountAmount = Math.min(discountValue, preDiscountGrandTotal);
                    if (discountValueLabel) discountValueLabel.textContent = 'Discount (GH₵)';
                }

                var discountAmtDisplay = document.getElementById('confirmDiscountAmount');
                if (discountAmtDisplay && discountAmount > 0) {
                    discountAmtDisplay.textContent = 'GH₵ ' + discountAmount.toFixed(2);
                    discountAmtDisplay.closest('.d-flex').style.display = 'flex';
                } else if (discountAmtDisplay) {
                    discountAmtDisplay.textContent = 'GH₵ 0.00';
                    discountAmtDisplay.closest('.d-flex').style.display = 'none';
                }

                var grandTotal = preDiscountGrandTotal - discountAmount;
                if (confirmGrandTotal) {
                    confirmGrandTotal.textContent = 'GH₵ ' + grandTotal.toFixed(2);
                }

                var step3count = document.getElementById('step3ItemCount');
                var step3total = document.getElementById('step3TotalAmount');
                if (step3count) step3count.textContent = serviceSelect.selectedOptions.length;
                if (step3total) step3total.textContent = grandTotal.toFixed(2);
                
                return grandTotal;
            }

            function updatePaymentMethodUI() {
                var radios = form.querySelectorAll('input[name="payment_method"]');
                var selectedValue = 'Cash';
                for (var i = 0; i < radios.length; i++) {
                    if (radios[i].checked) {
                        selectedValue = radios[i].value;
                        break;
                    }
                }
                
                var momoWrapper = document.getElementById('momoTransactionIdWrapper');
                var momoInput = document.getElementById('momoTransactionId');
                var cashCardEl = document.getElementById('cashPaymentCard');
                var momoCardEl = document.getElementById('momoPaymentCard');
                var posCardEl = document.getElementById('posPaymentCard');
                
                if (selectedValue === 'MoMo') {
                    if (momoWrapper) momoWrapper.style.display = 'block';
                    if (momoCardEl) {
                        momoCardEl.style.borderColor = 'var(--staff-green)';
                        momoCardEl.style.background = 'var(--staff-green-soft)';
                        var icon = momoCardEl.querySelector('i');
                        var text = momoCardEl.querySelector('div');
                        if (icon) icon.style.color = 'var(--staff-green)';
                        if (text) text.style.color = 'var(--staff-green)';
                    }
                    if (cashCardEl) {
                        cashCardEl.style.borderColor = 'var(--staff-border)';
                        cashCardEl.style.background = '';
                        var icon = cashCardEl.querySelector('i');
                        var text = cashCardEl.querySelector('div');
                        if (icon) icon.style.color = 'var(--staff-muted)';
                        if (text) text.style.color = 'var(--staff-muted)';
                    }
                    if (posCardEl) {
                        posCardEl.style.borderColor = 'var(--staff-border)';
                        posCardEl.style.background = '';
                        var posIcon = posCardEl.querySelector('i');
                        var posText = posCardEl.querySelector('div');
                        if (posIcon) posIcon.style.color = 'var(--staff-muted)';
                        if (posText) posText.style.color = 'var(--staff-muted)';
                    }
                    if (createBtnText) createBtnText.textContent = 'Create & Mark as Paid';
                } else if (selectedValue === 'POS') {
                    if (momoWrapper) momoWrapper.style.display = 'none';
                    if (momoInput) momoInput.value = '';
                    if (posCardEl) {
                        posCardEl.style.borderColor = 'var(--staff-green)';
                        posCardEl.style.background = 'var(--staff-green-soft)';
                        var posIcon = posCardEl.querySelector('i');
                        var posText = posCardEl.querySelector('div');
                        if (posIcon) posIcon.style.color = 'var(--staff-green)';
                        if (posText) posText.style.color = 'var(--staff-green)';
                    }
                    if (cashCardEl) {
                        cashCardEl.style.borderColor = 'var(--staff-border)';
                        cashCardEl.style.background = '';
                        var cashIcon = cashCardEl.querySelector('i');
                        var cashText = cashCardEl.querySelector('div');
                        if (cashIcon) cashIcon.style.color = 'var(--staff-muted)';
                        if (cashText) cashText.style.color = 'var(--staff-muted)';
                    }
                    if (momoCardEl) {
                        momoCardEl.style.borderColor = 'var(--staff-border)';
                        momoCardEl.style.background = '';
                        var momoIcon = momoCardEl.querySelector('i');
                        var momoText = momoCardEl.querySelector('div');
                        if (momoIcon) momoIcon.style.color = 'var(--staff-muted)';
                        if (momoText) momoText.style.color = 'var(--staff-muted)';
                    }
                    if (createBtnText) createBtnText.textContent = 'Create & Mark as Paid';
                } else {
                    if (momoWrapper) momoWrapper.style.display = 'none';
                    if (momoInput) momoInput.value = '';
                    if (cashCardEl) {
                        cashCardEl.style.borderColor = 'var(--staff-green)';
                        cashCardEl.style.background = 'var(--staff-green-soft)';
                        var icon = cashCardEl.querySelector('i');
                        var text = cashCardEl.querySelector('div');
                        if (icon) icon.style.color = 'var(--staff-green)';
                        if (text) text.style.color = 'var(--staff-green)';
                    }
                    if (momoCardEl) {
                        momoCardEl.style.borderColor = 'var(--staff-border)';
                        momoCardEl.style.background = '';
                        var icon = momoCardEl.querySelector('i');
                        var text = momoCardEl.querySelector('div');
                        if (icon) icon.style.color = 'var(--staff-muted)';
                        if (text) text.style.color = 'var(--staff-muted)';
                    }
                    if (posCardEl) {
                        posCardEl.style.borderColor = 'var(--staff-border)';
                        posCardEl.style.background = '';
                        var posIcon = posCardEl.querySelector('i');
                        var posText = posCardEl.querySelector('div');
                        if (posIcon) posIcon.style.color = 'var(--staff-muted)';
                        if (posText) posText.style.color = 'var(--staff-muted)';
                    }
                    if (createBtnText) createBtnText.textContent = 'Create & Mark as Paid';
                }
            }

            function generateReceiptHTML(data) {
                var staffDisplayName = <?php echo json_encode($staff['name'] ?? 'Staff'); ?>;
                var spaName = <?php echo json_encode($branchName ? 'MARIE NOELLE ' . $branchName : 'MARIE NOELLE SPA & SALON'); ?>;
                var address = <?php echo json_encode($spaAddress ?: 'Accra, Ghana'); ?>;
                var phone = <?php echo json_encode($spaContact ? 'Tel: ' . $spaContact : ''); ?>;
                var email = <?php echo json_encode($spaEmail ?: ''); ?>;
                var logo = <?php echo json_encode($logoUrl); ?>;

                var subtotal = parseFloat(data.total) || 0;
                var invoiceId = data.BillingId || 'N/A';
                var date = new Date(data.PostingDate).toLocaleString('en-GB', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
                var customer = data.customer_name || 'Customer';
                var paymentMethod = data.payment_method || 'Cash';
                var momoTx = data.momo_transaction_id || '';
                var taxList = data.tax_list || [];
                var totalTax = 0;

                var html = '<div id="printReceipt" style="width:58mm;padding:3mm 15% 3mm 3mm;color:#000;background:#fff;font-weight:bold;font-family:monospace,Courier New;font-size:11px;line-height:1.35;">';

                html += '<div style="text-align:center;margin-bottom:6px;padding-bottom:4px;border-bottom:1px solid #000;">';
                html += '<div style="color:#000;font-weight:bold;font-size:13px;">' + spaName + '</div>';
                if (address) html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + address + '</div>';
                if (phone) html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + phone + '</div>';
                if (email) html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + email + '</div>';
                html += '</div>';

                html += '<div style="text-align:center;margin-bottom:6px;">';
                html += '<div style="color:#000;font-weight:bold;font-size:12px;">RECEIPT</div>';
                html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + invoiceId + '</div>';
                html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + date + '</div>';
                html += '</div>';

                html += '<div style="margin-bottom:6px;">';
                html += '<div style="color:#000;font-weight:bold;font-size:10px;border-bottom:1px solid #000;padding:2px 0;">Customer: ' + customer + '</div>';
                html += '<div style="color:#000;font-weight:bold;font-size:10px;border-bottom:1px solid #000;padding:2px 0;">Served by: ' + staffDisplayName + '</div>';
                html += '</div>';

                var services = (data.service_names || '').split('|').filter(Boolean);
                var costs = (data.service_costs || '').split('|');
                for (var i = 0; i < services.length; i++) {
                    var cost = (costs[i] && parseFloat(costs[i]) > 0) ? formatMoney(parseFloat(costs[i])) : '-';
                    html += '<div style="border-top:1px solid #000;padding:2px 0;">';
                    html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + services[i].trim() + '</div>';
                    html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + cost + '</div>';
                    html += '</div>';
                }

                html += '<div style="border-top:1px solid #000;margin-top:3px;padding-top:3px;">';
                html += '<div style="color:#000;font-weight:bold;font-size:10px;display:flex;justify-content:space-between;"><span>Subtotal</span><span>' + formatMoney(subtotal) + '</span></div>';
                for (var j = 0; j < taxList.length; j++) {
                    var taxName = taxList[j].name || 'Tax';
                    var taxRate = parseFloat(taxList[j].value) || 0;
                    var taxAmt = subtotal * (taxRate / 100);
                    totalTax += taxAmt;
                    html += '<div style="color:#000;font-weight:bold;font-size:10px;display:flex;justify-content:space-between;"><span>' + taxName + ' (' + taxRate + '%)</span><span>' + formatMoney(taxAmt) + '</span></div>';
                }
                var discountAmt = parseFloat(data.discount_amount) || 0;
                var discountTypeLabel = data.discount_type || '';
                var discountVal = parseFloat(data.discount_value) || 0;
                if (discountAmt > 0) {
                    var discLabel = discountTypeLabel === 'percentage' ? discountVal + '% OFF' : 'GH₵ ' + discountVal.toFixed(2) + ' OFF';
                    html += '<div style="color:#000;font-weight:bold;font-size:10px;display:flex;justify-content:space-between;"><span style="color:#a63c3c;">Discount (' + discLabel + ')</span><span style="color:#a63c3c;">-' + formatMoney(discountAmt) + '</span></div>';
                }
                html += '<div style="color:#000;font-weight:bold;font-size:11px;display:flex;justify-content:space-between;border-top:1px dashed #000;padding-top:3px;margin-top:3px;"><span>TOTAL</span><span>' + formatMoney(subtotal + totalTax - discountAmt) + '</span></div>';
                html += '</div>';

                html += '<div style="text-align:center;margin:6px 0;padding:4px 0;border-top:1px solid #000;border-bottom:1px solid #000;">';
                html += '<div style="color:#000;font-weight:bold;font-size:10px;">Payment: ' + paymentMethod + '</div>';
                if (momoTx) {
                    html += '<div style="color:#000;font-weight:bold;font-size:10px;">MoMo Ref: ' + momoTx + '</div>';
                }
                html += '</div>';

                html += '<div style="text-align:center;">';
                html += '<div style="color:#000;font-weight:bold;font-size:10px;">THANK YOU</div>';
                html += '<div style="color:#000;font-weight:bold;font-size:10px;">PLEASE COME AGAIN</div>';
                html += '</div>';

                html += '</div>';
                return html;
            }

            function printReceiptFromData(data) {
                if (!data) return;
                var win = window.open('', '_blank', 'width=300,height=600,menubar=no,toolbar=no,location=no,status=no,scrollbars=yes');
                if (!win) { window.StaffPortal.showToast('Please allow popups to print the receipt.', 'error'); return; }
                win.document.write('<!DOCTYPE html><html><head><title>Receipt</title>' +
                    '<style>@page{size:58mm auto;margin:0;}body{margin:0;padding:0;width:58mm;background:#fff;color:#000;font-weight:bold;font-family:monospace,Courier New;font-size:11px;line-height:1.35;}</style>' +
                    '</head><body>' + generateReceiptHTML(data) + '</body></html>');
                win.document.close();
                win.focus();
                setTimeout(function() { win.print(); }, 200);
            }

            function formatMoney(amount) {
                return 'GHS ' + parseFloat(amount).toFixed(2);
            }

            if (cashCard) {
                cashCard.onclick = function() {
                    var radio = form.querySelector('input[name="payment_method"][value="Cash"]');
                    if (radio) radio.checked = true;
                    updatePaymentMethodUI();
                };
            }

            var momoCard = document.getElementById('momoPaymentCard');
            if (momoCard) {
                momoCard.onclick = function() {
                    var radio = form.querySelector('input[name="payment_method"][value="MoMo"]');
                    if (radio) radio.checked = true;
                    updatePaymentMethodUI();
                };
            }

            var posCard = document.getElementById('posPaymentCard');
            if (posCard) {
                posCard.onclick = function() {
                    var radio = form.querySelector('input[name="payment_method"][value="POS"]');
                    if (radio) radio.checked = true;
                    updatePaymentMethodUI();
                };
            }

            var paymentRadios = form.querySelectorAll('input[name="payment_method"]');
            for (var i = 0; i < paymentRadios.length; i++) {
                paymentRadios[i].onchange = updatePaymentMethodUI;
            }

            function updateDiscountTypeUI() {
                var radios = form.querySelectorAll('input[name="discount_type"]');
                var selectedValue = '';
                for (var i = 0; i < radios.length; i++) {
                    if (radios[i].checked) { selectedValue = radios[i].value; break; }
                }
                var cards = form.querySelectorAll('.discount-type-card');
                for (var j = 0; j < cards.length; j++) {
                    var card = cards[j];
                    var isActive = card.getAttribute('data-value') === selectedValue;
                    card.style.borderColor = isActive ? 'var(--staff-green)' : 'var(--staff-border)';
                    card.style.background = isActive ? 'var(--staff-green-soft)' : '';
                    var title = card.querySelector('div');
                    if (title) title.style.color = isActive ? 'var(--staff-green)' : 'var(--staff-muted)';
                }
                var wrapper = document.getElementById('discountValueWrapper');
                var input = document.getElementById('discountValueInput');
                if (wrapper) wrapper.style.display = selectedValue ? 'block' : 'none';
                if (!selectedValue && input) input.value = '';
                recalculateTotal();
            }

            var discountCards = form.querySelectorAll('.discount-type-card');
            for (var i = 0; i < discountCards.length; i++) {
                discountCards[i].onclick = function() {
                    var value = this.getAttribute('data-value');
                    var radio = form.querySelector('input[name="discount_type"][value="' + value + '"]');
                    if (radio) { radio.checked = true; }
                    updateDiscountTypeUI();
                };
            }

            var discountRadios = form.querySelectorAll('input[name="discount_type"]');
            for (var i = 0; i < discountRadios.length; i++) {
                discountRadios[i].onchange = updateDiscountTypeUI;
            }

            var discountValueInput = document.getElementById('discountValueInput');
            if (discountValueInput) {
                discountValueInput.oninput = function() {
                    recalculateTotal();
                };
            }

            var customerModeRadios = form.querySelectorAll('input[name="customer_mode"]');
            for (var j = 0; j < customerModeRadios.length; j++) {
                customerModeRadios[j].onchange = updateCustomerModeUI;
            }

            var customerCards = form.querySelectorAll('.customer-mode-card');
            for (var k = 0; k < customerCards.length; k++) {
                customerCards[k].onclick = function() {
                    var value = this.getAttribute('data-value');
                    var radio = form.querySelector('input[name="customer_mode"][value="' + value + '"]');
                    if (radio) {
                        radio.checked = true;
                    }
                    updateCustomerModeUI();
                };
            }

            if (customerSearch) {
                customerSearch.oninput = function() {
                    filterInlineCustomerOptions(this.value);
                };
            }

            if (serviceSearch) {
                serviceSearch.oninput = function() {
                    filterServiceOptions(this.value);
                };
            }

            if (servicePickerList) {
                servicePickerList.addEventListener('change', function(event) {
                    var checkbox = event.target.closest('.service-picker-checkbox');
                    if (!checkbox) {
                        return;
                    }
                    var card = checkbox.closest('.service-picker-option');
                    if (card) {
                        card.classList.toggle('is-selected', checkbox.checked);
                    }
                    syncInlineServicesToSelect();
                });
                servicePickerList.addEventListener('input', function(event) {
                    var priceInput = event.target.closest('.service-price-input');
                    if (!priceInput) {
                        return;
                    }
                    var checkbox = servicePickerList.querySelector('.service-picker-checkbox[value="' + priceInput.getAttribute('data-service-id') + '"]');
                    if (checkbox) {
                        checkbox.checked = true;
                        var card = checkbox.closest('.service-picker-option');
                        if (card) {
                            card.classList.add('is-selected');
                        }
                    }
                    syncInlineServicesToSelect();
                });
            }

            openButton.onclick = function () {
                if (walkInModalInstance) {
                    goToStep(1);
                    walkInModalInstance.show();
                }
            };

            if (prevStepBtn) {
                prevStepBtn.onclick = function() {
                    goToStep(currentStep - 1);
                };
            }

            if (nextStepBtn) {
                nextStepBtn.onclick = function() {
                    attemptStepChange(currentStep + 1);
                };
            }

            serviceSelect.onchange = function() {
                syncServicePriceInputs();
                recalculateTotal();
                updateSelectedServicesSummary();
                updateConfirmModalSummary();
            };
            recalculateTotal();
            syncInlineServicesToSelect();
            syncServicePriceInputs();
            updateSelectedServicesSummary();
            updateConfirmModalSummary();
            updateSelectedCustomerSummary();
            updateCustomerModeUI();
            updatePaymentMethodUI();
            updateDiscountTypeUI();
            updateStepperUI();

            form.onsubmit = async function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (!isConfirmingSubmit) {
                    if (!validateStep(1) || !validateStep(2) || !validateStep(3)) {
                        return;
                    }

                    updateConfirmModalSummary();
                    recalculateTotal();
                    if (confirmModalInstance) {
                        confirmModalInstance.show();
                    }
                    return;
                }

                var formData = new FormData(form);
                var submitBtn = form.querySelector('button[type="submit"]');
                var originalBtnText = submitBtn ? submitBtn.innerHTML : '';
                var confirmBtnOriginal = confirmCreateAppointmentBtn ? confirmCreateAppointmentBtn.innerHTML : '';
                isConfirmingSubmit = false;

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
                }
                if (confirmCreateAppointmentBtn) {
                    confirmCreateAppointmentBtn.disabled = true;
                    confirmCreateAppointmentBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
                }

                try {
                    var payload = await window.StaffPortal.postForm('appointments.php', formData, {
                        loadingText: 'Creating appointment...',
                        successMessage: false
                    });

                    var section = document.querySelector('.staff-table-card');
                    var tableContainer = section.querySelector('.table-responsive');
                    
                    if (payload.table_html) {
                        if (!tableContainer) {
                            var tableHtml = '<div class="table-responsive">' +
                                '<table class="table align-middle mb-0" id="staffAppointmentsTable">' +
                                '<thead><tr>' +
                                '<th>Appointment #</th><th>Customer</th><th>Services</th><th>Schedule</th><th>Total</th><th>Status</th><th>Payment</th><th style="width: 80px;">Actions</th>' +
                                '</tr></thead>' +
                                '<tbody>' + payload.table_html + '</tbody>' +
                                '</table></div>';
                            section.innerHTML = tableHtml;
                        } else {
                            var tbody = tableContainer.querySelector('tbody');
                            if (tbody) {
                                tbody.innerHTML = payload.table_html;
                            }
                        }
                    } else {
                        if (tableContainer) {
                            section.innerHTML = '<div class="staff-empty-state"><i class="fa fa-calendar-times-o"></i><p>No appointments found.</p></div>';
                        }
                    }

                    if (confirmModalInstance) {
                        confirmModalInstance.hide();
                    }
                    if (walkInModalInstance) {
                        walkInModalInstance.hide();
                    }
                    latestReceiptData = payload.receipt_data || null;
                    if (latestReceiptData) {
                        printReceiptFromData(latestReceiptData);
                    }
                    
                    window.StaffPortal.showToast(payload.message || 'Walk-in appointment created successfully!', 'success');
                } catch (error) {
                    console.error('Error:', error);
                    window.StaffPortal.showToast('Failed to create appointment. Please try again.', 'error');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                    if (confirmCreateAppointmentBtn) {
                        confirmCreateAppointmentBtn.disabled = false;
                        confirmCreateAppointmentBtn.innerHTML = confirmBtnOriginal;
                    }
                }
            };

            if (confirmCreateAppointmentBtn) {
                confirmCreateAppointmentBtn.onclick = function() {
                    isConfirmingSubmit = true;
                    form.requestSubmit();
                };
            }
        }

        function initPage() {
            setTimeout(initWalkInModal, 50);
        }

        if (window.StaffPortal) {
            window.StaffPortal.onPageLoad = initPage;
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initWalkInModal);
        } else {
            initWalkInModal();
        }
    })();

    window.selectInlineCustomer = function(el) {
        var walkInForm = document.getElementById('walkInAppointmentForm');
        if (!walkInForm) return;

        var customerIdInput = walkInForm.querySelector('#walkInCustomerId');
        var inlineList = document.getElementById('inlineCustomerList');
        var summary = document.getElementById('selectedCustomerSummary');
        var nameEl = document.getElementById('selectedCustomerName');
        var metaEl = document.getElementById('selectedCustomerMeta');
        if (!customerIdInput || !inlineList) return;

        var wasSelected = el.classList.contains('is-selected');

        var allOptions = inlineList.querySelectorAll('.customer-inline-option');
        for (var i = 0; i < allOptions.length; i++) {
            allOptions[i].classList.remove('is-selected');
            var icon = allOptions[i].querySelector('.customer-option-icon');
            if (icon) {
                icon.className = 'fa fa-circle-o customer-option-icon';
                icon.style.color = 'var(--staff-muted)';
            }
        }

        if (wasSelected) {
            customerIdInput.value = '';
        } else {
            el.classList.add('is-selected');
            var checkIcon = el.querySelector('.customer-option-icon');
            if (checkIcon) {
                checkIcon.className = 'fa fa-check-circle customer-option-icon';
                checkIcon.style.color = 'var(--staff-green)';
            }
            customerIdInput.value = el.getAttribute('data-id');
        }

        if (customerIdInput.value && nameEl && metaEl && summary) {
            nameEl.textContent = el.getAttribute('data-name') || 'Selected customer';
            var meta = [];
            var phone = el.getAttribute('data-phone') || '';
            var email = el.getAttribute('data-email') || '';
            if (phone) meta.push(phone);
            if (email) meta.push(email);
            metaEl.textContent = meta.length ? meta.join(' | ') : 'Customer selected';
            summary.style.display = 'block';
        } else if (nameEl && metaEl && summary) {
            nameEl.textContent = 'No customer selected';
            metaEl.textContent = 'Pick an existing customer to continue.';
            summary.style.display = 'none';
        }
    };

    window.deleteAppointment = function(id) {
        var modalEl = document.getElementById('deleteAppointmentModal');
        var confirmBtn = document.getElementById('confirmDeleteBtn');
        
        if (!modalEl || typeof bootstrap === 'undefined') {
            if (!confirm('Are you sure you want to delete this appointment?')) {
                return;
            }
            performDelete(id);
            return;
        }
        
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
        
        var newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        newConfirmBtn.onclick = function() {
            modal.hide();
          //  performDelete(id);
        };
    };

    function performDelete(id) {
        var deleteBtn = document.getElementById('deleteBtn' + id);
        var originalBtnContent = '';
        if (deleteBtn) {
            originalBtnContent = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            deleteBtn.disabled = true;
        }
        
        var formData = new FormData();
        formData.append('ajax_action', 'delete_appointment');
        formData.append('id', id);
        
        fetch('', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'xmlhttprequest'
            }
        })
        .then(function(response) {
            console.log('Delete response status:', response.status, response.headers.get('content-type'));
            if (!response.ok) {
                return response.text().then(function(text) {
                    console.log('Error response:', text);
                    throw new Error('Server error: ' + response.status);
                });
            }
            return response.json();
        })
        .then(function(data) {
            console.log('Delete data:', data);
            if (deleteBtn) {
                deleteBtn.innerHTML = originalBtnContent;
                deleteBtn.disabled = false;
            }
            if (data.success) {
                var row = document.querySelector('tr[data-appointment-id="' + id + '"]');
                if (row) {
                    row.remove();
                }
                window.StaffPortal.showToast(data.message || 'Appointment deleted', 'success');
            } else {
                showErrorModal(data.message || 'Failed to delete appointment');
            }
        })
        .catch(function(error) {
            console.log('Delete error:', error);
            if (deleteBtn) {
                deleteBtn.innerHTML = originalBtnContent;
                deleteBtn.disabled = false;
            }
            var errorMsg = error.message || 'Error deleting appointment. Please try again.';
            showErrorModal(errorMsg);
        });
    }
    
    function showErrorModal(message) {
        var errorModalEl = document.getElementById('deleteErrorModal');
        var errorMsgEl = document.getElementById('deleteErrorMessage');
        if (errorModalEl && typeof bootstrap !== 'undefined') {
            if (errorMsgEl) {
                errorMsgEl.textContent = message;
            }
            setTimeout(function() {
                new bootstrap.Modal(errorModalEl).show();
            }, 300);
        } else {
            window.StaffPortal.showToast(message, 'error');
        }
    }
</script>
<?php staff_layout_end(); ?>
