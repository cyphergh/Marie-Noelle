<?php
include('panel/includes/dbconnection.php');

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    echo "Invalid receipt link.";
    exit;
}

$stmt = mysqli_prepare($con, "SELECT * FROM tblappointment WHERE ereceipt_token = ?");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appt = mysqli_fetch_assoc($result);

if (!$appt) {
    echo "Receipt not found.";
    exit;
}

$spaInfo = @parse_ini_file(__DIR__ . '/info.ini') ?: array();
$branchName = isset($spaInfo['branch']) ? trim($spaInfo['branch']) : '';
$spaAddress = isset($spaInfo['address']) ? trim($spaInfo['address']) : '';
$spaContact = isset($spaInfo['contact']) ? trim($spaInfo['contact']) : '';
$spaEmail = isset($spaInfo['email']) ? trim($spaInfo['email']) : '';
$spaName = $branchName ? 'MARIE NOELLE ' . $branchName : 'MARIE NOELLE SPA & SALON';

$paymentId = $appt['payment_id'] ?? '';
$invoiceId = $paymentId;

$customerId = (int) ($appt['Name'] ?? 0);
$customerQuery = mysqli_query($con, "SELECT Name, Email, MobileNumber FROM tblcustomers WHERE ID = '{$customerId}'");
$customerRow = mysqli_fetch_assoc($customerQuery);
$customerName = $customerRow ? htmlspecialchars($customerRow['Name']) : htmlspecialchars($appt['Name'] ?? 'Customer');
$customerPhone = $customerRow ? htmlspecialchars($customerRow['MobileNumber']) : htmlspecialchars($appt['PhoneNumber'] ?? '');
$customerEmail = $customerRow ? htmlspecialchars($customerRow['Email']) : '';

$services = $appt['Services'] ?? '';
$serviceIds = $services ? explode(',', $services) : array();
$serviceNames = array();
$serviceCosts = array();
$subtotal = 0;

if (!empty($serviceIds)) {
    $ids = implode(',', array_map('intval', $serviceIds));
    $svcQuery = mysqli_query($con, "SELECT ID, ServiceName, Cost FROM tblservices WHERE ID IN ({$ids})");
    $serviceMap = array();
    while ($svc = mysqli_fetch_assoc($svcQuery)) {
        $serviceMap[(int) $svc['ID']] = $svc;
    }
    foreach ($serviceIds as $sid) {
        $sid = (int) $sid;
        if (isset($serviceMap[$sid])) {
            $serviceNames[] = $serviceMap[$sid]['ServiceName'];
            $cost = (float) $serviceMap[$sid]['Cost'];
            $serviceCosts[] = $cost;
            $subtotal += $cost;
        }
    }
}

$invoiceRow = null;
if ($invoiceId) {
    $invQuery = mysqli_query($con, "SELECT * FROM tblinvoice WHERE BillingId = '{$invoiceId}' AND ServiceId = '0' LIMIT 1");
    $invoiceRow = mysqli_fetch_assoc($invQuery);
}

$taxPercent = $invoiceRow ? (float) ($invoiceRow['tax'] ?? 0) : 0;
$taxAmount = $subtotal * ($taxPercent / 100);

$discountType = $appt['discount_type'] ?? '';
$discountValue = (float) ($appt['discount_value'] ?? 0);
$discountAmount = (float) ($appt['discount_amount'] ?? 0);
$preDiscountTotal = $subtotal + $taxAmount;
$grandTotal = $preDiscountTotal - $discountAmount;

$paymentMethod = !empty($appt['payment_method']) ? $appt['payment_method'] : ($invoiceRow['payment_method'] ?? 'Cash');
$momoTx = $appt['momo_transaction_id'] ?? '';

$postingDate = $invoiceRow ? $invoiceRow['PostingDate'] : ($appt['PostingDate'] ?? '');
$formattedDate = !empty($postingDate) ? date('d M Y, h:i A', strtotime($postingDate)) : '';

$staffName = '';
if ($invoiceRow && !empty($invoiceRow['staff'])) {
    $staffQuery = mysqli_query($con, "SELECT name FROM tbl_staff WHERE id = '" . (int) $invoiceRow['staff'] . "'");
    $staffRow = mysqli_fetch_assoc($staffQuery);
    $staffName = $staffRow ? htmlspecialchars($staffRow['name']) : '';
}

$taxList = array();
$taxRes = mysqli_query($con, "SELECT name, value FROM tbl_tax WHERE delete_status = '0' ORDER BY id");
while ($tx = mysqli_fetch_assoc($taxRes)) {
    $taxList[] = $tx;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt - <?php echo htmlspecialchars($spaName); ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    background: #f5f5f5;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    font-family: 'Courier New', monospace;
    padding: 20px;
}
.receipt {
    width: 58mm;
    max-width: 100%;
    background: #fff;
    color: #000;
    font-weight: bold;
    font-size: 11px;
    line-height: 1.35;
    padding: 3mm 3mm 3mm 3mm;
    box-shadow: 0 2px 12px rgba(0,0,0,0.12);
    border-radius: 0;
}
.receipt .center { text-align: center; }
.receipt .spa-name { font-size: 13px; margin-bottom: 2px; }
.receipt .spa-detail { font-size: 10px; }
.receipt .border-bottom { border-bottom: 1px solid #000; }
.receipt .border-top { border-top: 1px solid #000; }
.receipt .border-dashed-top { border-top: 1px dashed #000; }
.receipt .section { margin-bottom: 6px; }
.receipt .section:last-child { margin-bottom: 0; }
.receipt .receipt-title { font-size: 12px; margin-bottom: 2px; }
.receipt .receipt-meta { font-size: 10px; }
.receipt .row-flex {
    display: flex;
    justify-content: space-between;
    padding: 2px 0;
}
.receipt .service-row {
    border-top: 1px solid #000;
    padding: 2px 0;
}
.receipt .total-row {
    font-size: 11px;
    padding-top: 3px;
    margin-top: 3px;
}
.receipt .payment-section {
    text-align: center;
    margin: 6px 0;
    padding: 4px 0;
}
.receipt .thanks {
    text-align: center;
    font-size: 10px;
}
.download-btn {
    display: block;
    width: 58mm;
    max-width: 100%;
    margin: 16px auto 0;
    padding: 10px 0;
    background: #2b5b55;
    color: #fff;
    border: none;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
}
.download-btn:hover { background: #1f443f; }
@media print {
    body { background: #fff; padding: 0; }
    .receipt { box-shadow: none; }
    .download-btn { display: none; }
}
</style>
</head>
<body>
<div>
    <div class="receipt" id="receiptContent">
        <div class="section center border-bottom" style="padding-bottom:4px;">
            <div class="spa-name"><?php echo htmlspecialchars($spaName); ?></div>
            <?php if ($spaAddress): ?>
            <div class="spa-detail"><?php echo htmlspecialchars($spaAddress); ?></div>
            <?php endif; ?>
            <?php if ($spaContact): ?>
            <div class="spa-detail"><?php echo htmlspecialchars($spaContact); ?></div>
            <?php endif; ?>
            <?php if ($spaEmail): ?>
            <div class="spa-detail"><?php echo htmlspecialchars($spaEmail); ?></div>
            <?php endif; ?>
        </div>

        <div class="section center" style="margin-bottom:6px;">
            <div class="receipt-title">RECEIPT</div>
            <?php if ($invoiceId): ?>
            <div class="receipt-meta"><?php echo htmlspecialchars($invoiceId); ?></div>
            <?php endif; ?>
            <?php if ($formattedDate): ?>
            <div class="receipt-meta"><?php echo htmlspecialchars($formattedDate); ?></div>
            <?php endif; ?>
        </div>

        <div class="section" style="margin-bottom:6px;">
            <div class="spa-detail border-bottom" style="padding:2px 0;">Customer: <?php echo htmlspecialchars($customerName); ?></div>
            <?php if ($staffName): ?>
            <div class="spa-detail border-bottom" style="padding:2px 0;">Served by: <?php echo htmlspecialchars($staffName); ?></div>
            <?php endif; ?>
        </div>

        <div class="section">
            <?php for ($i = 0; $i < count($serviceNames); $i++): ?>
            <div class="service-row">
                <div class="spa-detail"><?php echo htmlspecialchars($serviceNames[$i]); ?></div>
                <div class="spa-detail">GHS <?php echo number_format($serviceCosts[$i], 2); ?></div>
            </div>
            <?php endfor; ?>
        </div>

        <div class="section border-top" style="margin-top:3px;padding-top:3px;">
            <div class="row-flex">
                <span>Subtotal</span>
                <span>GHS <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php if (!empty($taxList)): ?>
                <?php foreach ($taxList as $tx): ?>
                <?php $txAmt = $subtotal * ((float) $tx['value'] / 100); ?>
                <div class="row-flex">
                    <span><?php echo htmlspecialchars($tx['name']); ?> (<?php echo (float) $tx['value']; ?>%)</span>
                    <span>GHS <?php echo number_format($txAmt, 2); ?></span>
                </div>
                <?php endforeach; ?>
            <?php elseif ($taxPercent > 0): ?>
            <div class="row-flex">
                <span>Tax (<?php echo $taxPercent; ?>%)</span>
                <span>GHS <?php echo number_format($taxAmount, 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($discountAmount > 0): ?>
            <div class="row-flex">
                <span style="color:#a63c3c;">Discount (<?php echo $discountType === 'percentage' ? $discountValue . '% OFF' : 'GHS ' . number_format($discountValue, 2) . ' OFF'; ?>)</span>
                <span style="color:#a63c3c;">-GHS <?php echo number_format($discountAmount, 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="row-flex total-row border-dashed-top">
                <span>TOTAL</span>
                <span>GHS <?php echo number_format($grandTotal, 2); ?></span>
            </div>
        </div>

        <div class="section payment-section border-top border-bottom">
            <div class="spa-detail">Payment: <?php echo htmlspecialchars($paymentMethod); ?></div>
            <?php if ($momoTx): ?>
            <div class="spa-detail">MoMo Ref: <?php echo htmlspecialchars($momoTx); ?></div>
            <?php endif; ?>
        </div>

        <div class="section thanks">
            <div>THANK YOU</div>
            <div>PLEASE COME AGAIN</div>
        </div>
    </div>

    <button class="download-btn" onclick="window.print()">Print / Save as PDF</button>
</div>
</body>
</html>
