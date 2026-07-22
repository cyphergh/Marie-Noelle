<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

header('Content-Type: application/json');

if (strlen($_SESSION['bpmsaid'] == 0)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$appointment_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

// Start transaction
mysqli_begin_transaction($con);

try {
    // Get the BillingId(s) for this appointment before deleting
    // First check if appointment has invoice through tblinvoice (using Userid and services)
    $aptQuery = mysqli_query($con, "SELECT * FROM tblappointment WHERE ID = $appointment_id");
    
    if (!$aptQuery || mysqli_num_rows($aptQuery) === 0) {
        throw new Exception('Appointment not found');
    }
    
    $aptRow = mysqli_fetch_assoc($aptQuery);
    
    // Delete invoices related to this appointment's customer and matching service IDs
    // The appointment stores customer NAME, so we need to look up the customer ID
    $services = $aptRow['Services'];
    $customerName = mysqli_real_escape_string($con, $aptRow['Name']);
    
    // Look up customer ID from tblcustomers using the appointment's Name field
    $customerQuery = mysqli_query($con, "SELECT ID FROM tblcustomers WHERE Name = '$customerName' LIMIT 1");
    
    if ($customerQuery && mysqli_num_rows($customerQuery) > 0) {
        $customerRow = mysqli_fetch_assoc($customerQuery);
        $customerId = (int) $customerRow['ID'];
        
        if (!empty($services) && $customerId > 0) {
            $serviceIds = array_filter(array_map('intval', explode(',', $services)));
            
            if (!empty($serviceIds)) {
                $serviceIdList = implode(',', $serviceIds);
                
                $invoiceQuery = mysqli_query($con, "
                    SELECT BillingId FROM tblinvoice 
                    WHERE Userid = $customerId 
                    AND ServiceId IN ($serviceIdList)
                    ORDER BY ID DESC LIMIT 1
                ");
                
                if ($invoiceQuery && mysqli_num_rows($invoiceQuery) > 0) {
                    $invoiceRow = mysqli_fetch_assoc($invoiceQuery);
                    $billingId = (int) $invoiceRow['BillingId'];
                    
                    mysqli_query($con, "DELETE FROM tblinvoice WHERE BillingId = $billingId");
                }
            }
        }
    } else {
        // Customer not found in tblcustomers - try to find invoices by service only
        // This handles edge cases where invoices were created without proper customer link
        if (!empty($services)) {
            $serviceIds = array_filter(array_map('intval', explode(',', $services)));
            
            if (!empty($serviceIds)) {
                $serviceIdList = implode(',', $serviceIds);
                
                // Delete all invoices matching these services (clean up orphaned invoices)
                mysqli_query($con, "DELETE FROM tblinvoice WHERE ServiceId IN ($serviceIdList)");
            }
        }
    }
    
    // Delete the appointment
    $deleteResult = mysqli_query($con, "DELETE FROM tblappointment WHERE ID = $appointment_id");
    
    if (!$deleteResult) {
        throw new Exception('Failed to delete appointment');
    }
    
    mysqli_commit($con);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Appointment and related invoices deleted successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($con);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
