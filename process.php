<?php
ob_start();
// Better debug version
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log what we received
file_put_contents('debug.log', "=== NEW SUBMISSION ===\n", FILE_APPEND);
file_put_contents('debug.log', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', "Time: " . date('Y-m-d H:i:s') . "\n\n", FILE_APPEND);

// Capture form fields
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';

// Check what's missing
$missing = [];
if (empty($full_name)) $missing[] = 'full_name';
if (empty($email)) $missing[] = 'email';
if (empty($phone)) $missing[] = 'phone';

if (!empty($missing)) {
    echo "<h3 style='color: red;'>Missing Required Fields:</h3>";
    echo implode(', ', $missing);
    file_put_contents('debug.log', "ERROR: Missing fields: " . implode(', ', $missing) . "\n", FILE_APPEND);
    exit;
}

// If we get here, all required fields are present
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

file_put_contents('debug.log', "Validation passed. Name: $full_name, Email: $email\n", FILE_APPEND);

// Build column values for Monday.com
$columnValues = [
    "text" => $full_name,
    "email" => [
        "email" => $email,
        "text" => $email
    ],
    "phone" => $phone,
    "date" => $dob,
    "text4" => $current_address,
    "text7" => $property_address,
    "numbers" => $purchase_price,
    "numbers1" => $loan_amount,
    "dropdown" => $transaction_type,
    "dropdown8" => $occupancy,
    "text8" => $employer,
    "text9" => $job_title,
    "numbers6" => $time_at_job,
    "numbers3" => $monthly_income,
    "numbers4" => $other_income,
    "text0" => $bank_name,
    "numbers7" => $savings,
    "numbers5" => $monthly_debt,
    "status" => $consent
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

file_put_contents('debug.log', "Sending to Monday.com...\n", FILE_APPEND);

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

file_put_contents('debug.log', "Monday response code: $httpCode\n", FILE_APPEND);
file_put_contents('debug.log', "Monday response: $response\n", FILE_APPEND);

$responseData = json_decode($response, true);

// Check for errors
if ($httpCode !== 200 || isset($responseData['errors'])) {
    file_put_contents('debug.log', "ERROR from Monday.com\n", FILE_APPEND);
     "<h3 style='color: red;'>ERROR: Failed to submit to Monday.com</h3>";
     "HTTP Code: $httpCode<br>";
     "Response: <pre>" . print_r($responseData, true) . "</pre>";
    exit;
}

header('Location: https://mplacemortgage.com/thank-you.html', true, 303);
exit;


