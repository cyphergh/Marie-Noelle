<?php
include_once __DIR__ . '/includes/layout.php';
staff_require_login();

$staffId = staff_current_id();
$staff = staff_fetch_current($con);

$spaInfo = @parse_ini_file(__DIR__ . '/../info.ini') ?: array();
$branchName = isset($spaInfo['branch']) ? trim($spaInfo['branch']) : '';
$spaAddress = isset($spaInfo['address']) ? trim($spaInfo['address']) : '';
$spaContact = isset($spaInfo['contact']) ? trim($spaInfo['contact']) : '';
$spaEmail = isset($spaInfo['email']) ? trim($spaInfo['email']) : '';
$logoUrl = '../panel/images/logo.png';

$fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : date('Y-m-01');
$toDate = isset($_GET['to_date']) ? trim($_GET['to_date']) : date('Y-m-d');

$summary = array('total_rows' => 0, 'gross_total' => 0, 'total_with_tax' => 0);
$summaryResult = mysqli_query($con, "
    SELECT 
        COUNT(*) AS total_rows, 
        COALESCE(SUM(total), 0) AS gross_total 
    FROM tblinvoice 
    WHERE ServiceId = '0' AND staff = '{$staffId}' 
    AND PostingDate BETWEEN '{$fromDate}' AND '{$toDate}'
");
if ($summaryResult) {
    $summary = mysqli_fetch_assoc($summaryResult);
}

$taxCalcResult = mysqli_query($con, "
    SELECT COALESCE(SUM(total * (tax / 100)), 0) AS total_tax_amount
    FROM tblinvoice 
    WHERE ServiceId = '0' AND staff = '{$staffId}' 
    AND PostingDate BETWEEN '{$fromDate}' AND '{$toDate}'
");
if ($taxCalcResult) {
    $taxRow = mysqli_fetch_assoc($taxCalcResult);
    $summary['total_with_tax'] = $summary['gross_total'] + $taxRow['total_tax_amount'];
}

$taxResult = mysqli_query($con, "SELECT name, value FROM tbl_tax WHERE delete_status = '0' ORDER BY id");
$taxList = [];
$totalTaxRate = 0;
if ($taxResult) {
    while ($taxRow = mysqli_fetch_assoc($taxResult)) {
        $taxList[] = $taxRow;
        $totalTaxRate += (float) $taxRow['value'];
    }
}

$paymentRows = mysqli_query($con, "
    SELECT MAX(i.id) AS latest_invoice_row_id,
           i.BillingId, i.PostingDate, i.tax, i.total, i.payment_method, i.qty, i.momo_transaction_id,
           i.discount_type, i.discount_value, i.discount_amount,
           c.Name AS customer_name, c.Email AS customer_email,
           MAX(a.Remark) AS appointment_remark,
           (SELECT GROUP_CONCAT(s2.ServiceName SEPARATOR '|') 
            FROM tblinvoice i2 
            LEFT JOIN tblservices s2 ON s2.ID = i2.ServiceId 
            WHERE i2.BillingId = i.BillingId AND i2.ServiceId != '0') AS service_names,
           (SELECT GROUP_CONCAT(CAST(i2.ServiceId AS CHAR) SEPARATOR '|')
            FROM tblinvoice i2
            WHERE i2.BillingId = i.BillingId AND i2.ServiceId != '0') AS service_ids,
           (SELECT GROUP_CONCAT(CAST(s2.Cost AS CHAR) SEPARATOR '|') 
            FROM tblinvoice i2 
            LEFT JOIN tblservices s2 ON s2.ID = i2.ServiceId 
            WHERE i2.BillingId = i.BillingId AND i2.ServiceId != '0') AS service_costs
    FROM tblinvoice i
    LEFT JOIN tblcustomers c ON c.ID = i.Userid
    LEFT JOIN tblappointment a ON a.payment_id = i.BillingId
    WHERE i.ServiceId = '0' AND i.staff = '{$staffId}'
    AND i.PostingDate BETWEEN '{$fromDate}' AND '{$toDate}'
    GROUP BY i.BillingId, i.PostingDate, i.tax, i.total, i.payment_method, i.qty, i.momo_transaction_id, i.discount_type, i.discount_value, i.discount_amount, c.Name, c.Email
    ORDER BY latest_invoice_row_id DESC, i.PostingDate DESC, i.BillingId DESC
");

staff_layout_start('Payments', 'payments', 'Track your sales and payment history');
?>
<div class="staff-section-head mb-4">
    <div class="staff-section-head-left">
        <h2>Sales & Payments</h2>
        <p class="staff-muted mb-0">Track your sales and payment history with tax included.</p>
    </div>
</div>

<section class="staff-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">From Date</label>
            <input type="date" class="form-control" name="from_date" value="<?php echo staff_escape($fromDate); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">To Date</label>
            <input type="date" class="form-control" name="to_date" value="<?php echo staff_escape($toDate); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="staff-button staff-button-primary" style="width: 100%;">
                <i class="fa fa-filter"></i> Filter
            </button>
        </div>
        <div class="col-md-2">
            <a href="payments.php" class="staff-button" style="width: 100%;">
                <i class="fa fa-refresh"></i> Reset
            </a>
        </div>
        <div class="col-md-2">
            <span class="staff-pill" style="background: var(--staff-green-soft); color: var(--staff-green);">
                <i class="fa fa-calendar"></i>
                <?php echo staff_format_date($fromDate); ?> - <?php echo staff_format_date($toDate); ?>
            </span>
        </div>
    </form>
</section>

<div class="staff-grid cards-3">
    <div class="staff-card">
        <div class="staff-stat-icon green">
            <i class="fa fa-file-invoice"></i>
        </div>
        <div class="staff-stat-label">Total Invoice Lines</div>
        <p class="staff-stat-value"><?php echo (int) $summary['total_rows']; ?></p>
        <p class="staff-stat-help">Invoices in selected period.</p>
    </div>

    <div class="staff-card">
        <div class="staff-stat-icon accent">
            <i class="fa fa-coins"></i>
        </div>
        <div class="staff-stat-label">Subtotal</div>
        <p class="staff-stat-value" style="font-size: 1.8rem;">GH₵ <?php echo staff_format_money($summary['gross_total']); ?></p>
        <p class="staff-stat-help">Before tax.</p>
    </div>

    <div class="staff-card">
        <div class="staff-stat-icon" style="background: var(--staff-green-soft); color: var(--staff-green);">
            <i class="fa fa-wallet"></i>
        </div>
        <div class="staff-stat-label">Total with Tax</div>
        <p class="staff-stat-value" style="font-size: 1.8rem; color: var(--staff-green);">GH₵ <?php echo staff_format_money($summary['total_with_tax']); ?></p>
        <p class="staff-stat-help">Including all taxes.</p>
    </div>
</div>

<section class="staff-table-card mt-4">
    <div class="staff-section-head">
        <div class="staff-section-head-left">
            <h2>Payment History</h2>
            <p class="staff-muted mb-0">Detailed breakdown of all invoice lines.</p>
        </div>
        <span class="staff-badge is-success">
            <i class="fa fa-check-circle"></i>
            <?php echo mysqli_num_rows($paymentRows); ?> Records
        </span>
    </div>

    <?php if ($paymentRows && mysqli_num_rows($paymentRows) > 0): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Method</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th>Tax (%)</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($paymentRows)): 
                        $taxPercent = (float) ($row['tax'] ?: 0);
                        $subtotal = (float) ($row['total'] ?: 0);
                        $taxAmount = $subtotal * ($taxPercent / 100);
                        $totalWithTax = $subtotal + $taxAmount;
                        
                        $invoiceData = [
                            'BillingId' => $row['BillingId'],
                            'PostingDate' => $row['PostingDate'],
                            'total' => $row['total'],
                            'tax' => $row['tax'],
                            'payment_method' => $row['payment_method'],
                            'qty' => $row['qty'],
                            'momo_transaction_id' => $row['momo_transaction_id'] ?? '',
                            'customer_name' => $row['customer_name'],
                            'service_names' => $row['service_names'] ?? '',
                            'service_ids' => $row['service_ids'] ?? '',
                            'service_costs' => $row['service_costs'] ?? '',
                            'appointment_remark' => $row['appointment_remark'] ?? '',
                            'tax_list' => $taxList,
                            'discount_type' => $row['discount_type'] ?? '',
                            'discount_value' => $row['discount_value'] ?? 0,
                            'discount_amount' => $row['discount_amount'] ?? 0
                        ];
                    ?>
                        <tr data-invoice='<?php echo htmlspecialchars(json_encode($invoiceData), ENT_QUOTES, 'UTF-8'); ?>'>
                            <td>
                                <strong><?php echo staff_escape($row['BillingId'] ?: '#' . $row['id']); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo staff_escape($row['customer_name'] ?: 'Unknown customer'); ?></strong><br>
                                <small class="staff-muted"><?php echo staff_escape($row['customer_email'] ?: '--'); ?></small>
                            </td>
                            <td class="staff-muted">
                                <?php echo staff_escape($row['service_names'] ?: 'Not available'); ?>
                            </td>
                            <td>
                                <span class="staff-badge">
                                    <?php echo staff_escape($row['payment_method'] ?: 'N/A'); ?>
                                </span>
                            </td>
                            <td class="staff-muted">
                                <?php echo (int) ($row['qty'] ?: 1); ?>
                            </td>
                            <td class="staff-muted">
                                GH₵ <?php echo staff_format_money($subtotal); ?>
                            </td>
                            <td class="staff-muted">
                                <?php echo $taxPercent; ?>%
                            </td>
                            <td style="font-weight: 700; color: var(--staff-green);">
                                GH₵ <?php echo staff_format_money($totalWithTax); ?>
                            </td>
                            <td class="staff-muted">
                                <?php echo staff_format_date($row['PostingDate']); ?>
                            </td>
                            <td>
                                <button type="button" class="staff-button btn-sm" onclick="printReceipt(this)" style="padding: 4px 10px; font-size: 12px;">
                                    <i class="fa fa-print"></i> Print
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="staff-empty-state">
            <i class="fa fa-file-invoice-dollar"></i>
            <p>No payment records found for the selected date range.</p>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                        <i class="fa fa-receipt" style="color: var(--staff-accent); margin-right: 10px;"></i>
                        Receipt Preview
                    </h3>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="receiptPreviewContainer">
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="staff-button" data-bs-dismiss="modal">Close</button>
                <button type="button" class="staff-button staff-button-primary" onclick="window.printReceiptFinal()">
                    <i class="fa fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var receiptModalInstance = null;
    var currentReceiptData = null;

    function initReceiptModal() {
        var modalEl = document.getElementById('receiptModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            receiptModalInstance = new bootstrap.Modal(modalEl);
        }
    }

    window.printReceipt = function(btn) {
        var row = btn.closest('tr');
        var invoiceData = JSON.parse(row.dataset.invoice || '{}');
        currentReceiptData = invoiceData;

        var preview = document.getElementById('receiptPreviewContainer');
        preview.innerHTML = generateReceiptHTML(invoiceData, true);

        if (receiptModalInstance) {
            receiptModalInstance.show();
        }
    };

    window.printReceiptFinal = function() {
        if (!currentReceiptData) return;

        if (receiptModalInstance) {
            receiptModalInstance.hide();
        }

        var win = window.open('', '_blank', 'width=300,height=600,menubar=no,toolbar=no,location=no,status=no,scrollbars=yes');
        if (!win) { window.StaffPortal.showToast('Please allow popups to print the receipt.', 'error'); return; }
        win.document.write('<!DOCTYPE html><html><head><title>Receipt</title>' +
            '<style>@page{size:58mm auto;margin:0;}body{margin:0;padding:0;width:58mm;background:#fff;color:#000;font-weight:bold;font-family:monospace,Courier New;font-size:11px;line-height:1.35;}</style>' +
            '</head><body>' + generateReceiptHTML(currentReceiptData, false) + '</body></html>');
        win.document.close();
        win.focus();
        setTimeout(function() { win.print(); }, 200);
    };

    function resolveServiceCosts(data) {
        var serviceIds = (data.service_ids || '').split('|').filter(Boolean);
        var serviceCosts = (data.service_costs || '').split('|');
        var remark = data.appointment_remark || '';
        var customPriceMatch = remark.match(/custom_prices=(\{.*\})/);

        if (customPriceMatch && serviceIds.length) {
            try {
                var customPrices = JSON.parse(customPriceMatch[1]);
                for (var i = 0; i < serviceIds.length; i++) {
                    var serviceId = serviceIds[i];
                    if (Object.prototype.hasOwnProperty.call(customPrices, serviceId)) {
                        serviceCosts[i] = customPrices[serviceId];
                    }
                }
            } catch (error) {
                console.warn('Unable to parse custom receipt pricing.', error);
            }
        }

        return serviceCosts;
    }

    function generateReceiptHTML(data, isPreview) {
        var staffName = <?php echo json_encode($staff['name'] ?? 'Staff'); ?>;
        var spaName = <?php echo json_encode($branchName ? 'MARIE NOELLE ' . $branchName : 'MARIE NOELLE SPA & SALON'); ?>;
        var address = <?php echo json_encode($spaAddress ?: 'Accra, Ghana'); ?>;
        var phone = <?php echo json_encode($spaContact ? 'Tel: ' . $spaContact : ''); ?>;
        var email = <?php echo json_encode($spaEmail ?: ''); ?>;
        var logoUrl = <?php echo json_encode($logoUrl); ?>;

        var subtotal = parseFloat(data.total) || 0;
        var taxPercent = parseFloat(data.tax) || 0;
        var taxAmount = subtotal * (taxPercent / 100);
        var preDiscountTotal = subtotal + taxAmount;
        var discountType = data.discount_type || '';
        var discountValue = parseFloat(data.discount_value) || 0;
        var discountAmount = parseFloat(data.discount_amount) || 0;
        if (discountAmount > 0) {
            var discCalc = discountType === 'percentage' ? preDiscountTotal * Math.min(discountValue, 100) / 100 : Math.min(discountValue, preDiscountTotal);
            discountAmount = Math.min(discountAmount, discCalc);
        }
        var total = preDiscountTotal - discountAmount;
        var invoiceId = data.BillingId || data.id || 'N/A';
        var date = new Date(data.PostingDate).toLocaleString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
        var customer = data.customer_name || 'Customer';
        var paymentMethod = data.payment_method || 'Cash';
        var momoTx = data.momo_transaction_id || '';

        if (isPreview) {
            var html = '<div style="max-height:60vh;overflow-y:auto;border:1px dashed #ccc;padding:15px;border-radius:8px;width:58mm;padding:3mm 15% 3mm 3mm;color:#000;background:#fff;font-weight:bold;font-family:monospace,Courier New;font-size:11px;line-height:1.35;">';
        } else {
            var html = '<div id="printReceipt" style="width:58mm;padding:3mm 15% 3mm 3mm;color:#000;background:#fff;font-weight:bold;font-family:monospace,Courier New;font-size:11px;line-height:1.35;">';
        }

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
        html += '<div style="color:#000;font-weight:bold;font-size:10px;border-bottom:1px solid #000;padding:2px 0;">Served by: ' + staffName + '</div>';
        html += '</div>';

        var serviceNames = (data.service_names || data.ServiceName || '');
        var serviceCosts = resolveServiceCosts(data);

        if (serviceNames && serviceCosts.length) {
            var services = serviceNames.split('|');
            var costs = serviceCosts;
            for (var i = 0; i < services.length; i++) {
                var cost = (costs[i] && parseFloat(costs[i]) > 0) ? formatMoney(parseFloat(costs[i])) : '-';
                html += '<div style="border-top:1px solid #000;padding:2px 0;">';
                html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + services[i].trim() + '</div>';
                html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + cost + '</div>';
                html += '</div>';
            }
        } else {
            html += '<div style="border-top:1px solid #000;padding:2px 0;">';
            html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + (data.service_names || data.ServiceName || 'Service') + '</div>';
            html += '<div style="color:#000;font-weight:bold;font-size:10px;">' + formatMoney(subtotal) + '</div>';
            html += '</div>';
        }

        html += '<div style="border-top:1px solid #000;margin-top:3px;padding-top:3px;">';
        html += '<div style="color:#000;font-weight:bold;font-size:10px;display:flex;justify-content:space-between;"><span>Subtotal</span><span>' + formatMoney(subtotal) + '</span></div>';

        var taxList = data.tax_list || [];
        if (taxList.length > 0) {
            for (var ti = 0; ti < taxList.length; ti++) {
                var taxName = taxList[ti].name || 'Tax';
                var taxRate = parseFloat(taxList[ti].value) || 0;
                var taxAmt = subtotal * (taxRate / 100);
                html += '<div style="color:#000;font-weight:bold;font-size:10px;display:flex;justify-content:space-between;"><span>' + taxName + ' (' + taxRate + '%)</span><span>' + formatMoney(taxAmt) + '</span></div>';
            }
        } else {
            html += '<div style="color:#000;font-weight:bold;font-size:10px;display:flex;justify-content:space-between;"><span>Tax (' + taxPercent + '%)</span><span>' + formatMoney(taxAmount) + '</span></div>';
        }

        if (discountAmount > 0) {
            var discLabel = discountType === 'percentage' ? discountValue + '% OFF' : 'GH₵ ' + discountValue.toFixed(2) + ' OFF';
            html += '<div style="color:#a63c3c;font-weight:bold;font-size:10px;display:flex;justify-content:space-between;"><span>Discount (' + discLabel + ')</span><span>-' + formatMoney(discountAmount) + '</span></div>';
        }
        html += '<div style="color:#000;font-weight:bold;font-size:11px;display:flex;justify-content:space-between;border-top:1px dashed #000;padding-top:3px;margin-top:3px;"><span>TOTAL</span><span>' + formatMoney(total) + '</span></div>';
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

    function formatMoney(amount) {
        return 'GHS ' + parseFloat(amount).toFixed(2);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReceiptModal);
    } else {
        initReceiptModal();
    }
})();
</script>
<?php staff_layout_end(); ?>
