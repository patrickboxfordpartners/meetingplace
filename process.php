<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Capture form fields
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$dob = $_POST['dob'] ?? '';
$current_address = $_POST['current_address'] ?? '';
$property_address = $_POST['property_address'] ?? '';
$purchase_price = $_POST['purchase_price'] ?? '';
$loan_amount = $_POST['loan_amount'] ?? '';
$transaction_type = $_POST['transaction_type'] ?? '';
$occupancy = $_POST['occupancy'] ?? '';
$employer = $_POST['employer'] ?? '';
$job_title = $_POST['job_title'] ?? '';
$time_at_job = $_POST['time_at_job'] ?? '';
$monthly_income = $_POST['monthly_income'] ?? '';
$other_income = $_POST['other_income'] ?? '';
$bank_name = $_POST['bank_name'] ?? '';
$savings = $_POST['savings'] ?? '';
$monthly_debt = $_POST['monthly_debt'] ?? '';
$consent = isset($_POST['consent']) ? 'Yes' : 'No';

// Validate required fields
if (empty($full_name) || empty($email) || empty($phone)) {
    die("ERROR: Please fill in all required fields (Name, Email, Phone)");
}

// Clean phone number - remove all non-digits
$phone_clean = preg_replace('/[^0-9]/', '', $phone);

// Build column values for Monday.com
$columnValues = [
    'lead_email' => ['email' => $email, 'text' => $email],
    'lead_phone' => ['phone' => $phone_clean, 'countryShortName' => 'US'],
    'text' => $job_title,
    'lead_company' => $employer,
    'text_mkxdrgsj' => $current_address,
    'text_mkxdvt4v' => $property_address,
    'text_mkxd44hz' => $bank_name,
    'numeric_mkxdn0rw' => $purchase_price,
    'numeric_mkxbgt39' => $loan_amount,
    'numeric_mkxdw8b6' => $time_at_job,
    'numeric_mkxd3yr5' => $monthly_income,
    'numeric_mkxdhe8q' => $other_income,
    'numeric_mkxddesj' => $savings,
    'numeric_mkxdmkaa' => $monthly_debt,
    'date_mkxd4yv6' => ['date' => $dob],
    'dropdown_mkxbjpf6' => ['labels' => [$transaction_type]],
    'dropdown_mkxdw2t5' => ['labels' => [$occupancy]],
    'boolean_mkxdqnft' => ['checked' => ($consent === 'Yes' ? 'true' : 'false')]
];

// Remove empty values
$columnValues = array_filter($columnValues, function($value) {
    return $value !== '' && $value !== null;
});

// GraphQL mutation
$query = 'mutation ($boardId: ID!, $itemName: String!, $columnValues: JSON!) {
  create_item(board_id: $boardId, item_name: $itemName, column_values: $columnValues) {
    id
  }
}';

$variables = [
    "boardId" => "18323185491",
    "itemName" => $full_name,
    "columnValues" => json_encode($columnValues)
];

$data = json_encode([
    'query' => $query,
    'variables' => $variables
]);

$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJ0aWQiOjU4MjkzNTYyNSwiYWFpIjoxMSwidWlkIjo5NTQxMTU0NiwiaWFkIjoiMjAyNS0xMS0wNVQxODo0ODowNy40OTZaIiwicGVyIjoibWU6d3JpdGUiLCJhY3RpZCI6MzIyNzkzMTcsInJnbiI6InVzZTEifQ.0wdsVkQOlNx2zUJKFsyuvs0IdouCdHUWKl1fPoCSLRI'
];

// Send to Monday.com
$ch = curl_init('https://api.monday.com/v2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$responseData = json_decode($response, true);

// Check for errors
if ($httpCode !== 200 || isset($responseData['errors'])) {
    echo "ERROR submitting to Monday.com<br>";
    echo "HTTP Code: $httpCode<br>";
    echo "<pre>" . print_r($responseData, true) . "</pre>";
    exit;
}
// Extract the Monday item ID from response
$monday_item_id = $responseData['data']['create_item']['id'] ?? '';

// ==================== URLA GENERATION ====================
$lead_data = array(
    'Name' => $full_name,
    'Email' => $email,
    'Phone' => $phone,
    'Date of Birth' => $dob,
    'Current Address' => $current_address,
    'Property Address' => $property_address,
    'Purchase Price' => $purchase_price,
    'Loan Amount Requested' => $loan_amount,
    'Transaction Type' => $transaction_type,
    'Occupancy' => $occupancy,
    'Employer Name' => $employer,
    'Job Title' => $job_title,
    'Time At Job' => $time_at_job,
    'Monthly Gross Income' => $monthly_income,
    'Other Income' => $other_income,
    'Bank Name' => $bank_name,
    'Approximate Savings' => $savings,
    'Monthly Debt Obligations' => $monthly_debt,
    'monday_item_id' => $monday_item_id
);

$json_data = json_encode($lead_data);
$python_script = '/home/site/wwwroot/monday_integration.py';  // UPDATE THIS PATH
$command = "python3 " . escapeshellarg($python_script) . " --data " . escapeshellarg($json_data) . " 2>&1";
exec($command, $output, $return_var);

// Add debugging right here:
error_log("URLA Generation Debug:");
error_log("Command: " . $command);
error_log("Return code: " . $return_var);
error_log("Output: " . print_r($output, true));

if ($return_var === 0) {
    // PDF generated successfully - extract the file path from output
    $pdf_path = '';
    foreach ($output as $line) {
        if (strpos($line, 'Path:') !== false) {
            $pdf_path = trim(str_replace('Path:', '', $line));
            break;
        }
    }
    
    if ($pdf_path && file_exists($pdf_path)) {
        // Upload PDF to Monday Files column
        $file_column_id = 'file_mkxfbe9g';  // Or your actual column ID like 'files_mkxd1234'
        
        $upload_query = 'mutation ($file: File!, $itemId: ID!, $columnId: String!) {
            add_file_to_column (item_id: $itemId, column_id: $columnId, file: $file) {
                id
            }
        }';
        
        $curl_file = new CURLFile($pdf_path, 'application/pdf', basename($pdf_path));
        
        $upload_data = [
            'query' => $upload_query,
            'variables' => json_encode([
                'itemId' => $monday_item_id,
                'columnId' => $file_column_id
            ]),
            'map' => json_encode(['file' => ['variables.file']]),
            'file' => $curl_file
        ];
        
        $ch_upload = curl_init('https://api.monday.com/v2/file');
        curl_setopt($ch_upload, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_upload, CURLOPT_POST, true);
        curl_setopt($ch_upload, CURLOPT_POSTFIELDS, $upload_data);
        curl_setopt($ch_upload, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJ0aWQiOjU4MjkzNTYyNSwiYWFpIjoxMSwidWlkIjo5NTQxMTU0NiwiaWFkIjoiMjAyNS0xMS0wNVQxODo0ODowNy40OTZaIiwicGVyIjoibWU6d3JpdGUiLCJhY3RpZCI6MzIyNzkzMTcsInJnbiI6InVzZTEifQ.0wdsVkQOlNx2zUJKFsyuvs0IdouCdHUWKl1fPoCSLRI'
        ]);
        
        curl_exec($ch_upload);
        curl_close($ch_upload);
        
        // Optional: Delete local PDF after upload
        // unlink($pdf_path);
    }
} else {
    error_log("URLA generation failed for $full_name: " . implode("\n", $output));
}
// ==================== END URLA GENERATION ====================

// Success - redirect
header('Location: https://mplacemortgage.com/thank-you.html');
exit;
?>
