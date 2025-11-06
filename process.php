<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

// Capture form fields from the mortgage application
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
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: full_name, email, or phone'
    ]);
    exit;
}

// Build column values for Monday.com
// Note: These keys must match your Monday.com board column IDs
$columnValues = [
    "text" => $full_name,           // Name column
    "email" => [                     // Email column
        "email" => $email,
        "text" => $email
    ],
    "phone" => $phone,               // Phone column
    "date" => $dob,                  // Date of Birth column
    "text4" => $current_address,     // Current Address column
    "text7" => $property_address,    // Property Address column
    "numbers" => $purchase_price,    // Purchase Price column
    "numbers1" => $loan_amount,      // Loan Amount Requested column
    "dropdown" => $transaction_type, // Transaction Type column
    "dropdown8" => $occupancy,       // Occupancy column
    "text8" => $employer,            // Employer Name column
    "text9" => $job_title,           // Job Title column
    "numbers6" => $time_at_job,      // Time At Job column
    "numbers3" => $monthly_income,   // Monthly Gross Income column
    "numbers4" => $other_income,     // Other Income column
    "text0" => $bank_name,           // Bank Name column
    "numbers7" => $savings,          // Approximate Savings column
    "numbers5" => $monthly_debt,     // Monthly Debt Obligations column
    "status" => $consent             // Consent Checkbox column
];

// Remove empty values to avoid Monday.com errors
$columnValues = array_filter($columnValues, function($value) {
    return $value !== '' && $value !== null;
});

// GraphQL mutation to create item in Monday.com
$query = 'mutation ($boardId: ID!, $itemName: String!, $columnValues: JSON!) {
  create_item(board_id: $boardId, item_name: $itemName, column_values: $columnValues) {
    id
  }
}';

// Variables - Update boardId to your actual board ID
$variables = [
    "boardId" => "1762389923",  // Your Monday.com board ID
    "itemName" => $full_name,   // Use applicant name as item name
    "columnValues" => json_encode($columnValues)
];

// Prepare request
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

// Parse response
$responseData = json_decode($response, true);

// Check for errors
if ($httpCode !== 200 || isset($responseData['errors'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit to Monday.com',
        'details' => $responseData,
        'http_code' => $httpCode
    ]);
    exit;
}

// Success! Redirect to thank you page
if (isset($_POST['full_name'])) {
    // If this is a form submission (not API call), redirect to success page
    header('Location: https://mplacemortgage.com/thank-you.html');
    exit;
} else {
    // If this is an API call, return JSON
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully',
        'monday_response' => $responseData
    ]);
}
?>
