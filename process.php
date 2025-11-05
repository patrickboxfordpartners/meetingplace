<?php
// Replace with your actual Monday API token
$apiToken = "eyJhbGciOiJIUzI1NiJ9.eyJ0aWQiOjU4MjkzNTYyNSwiYWFpIjoxMSwidWlkIjo5NTQxMTU0NiwiaWFkIjoiMjAyNS0xMS0wNVQxODo0ODowNy40OTZaIiwicGVyIjoibWU6d3JpdGUiLCJhY3RpZCI6MzIyNzkzMTcsInJnbiI6InVzZTEifQ.0wdsVkQOlNx2zUJKFsyuvs0IdouCdHUWKl1fPoCSLRI";
$boardId  = "18323185491";

// Collect form data safely
$fullName        = $_POST['full_name'] ?? '';
$email           = $_POST['email'] ?? '';
$phone           = $_POST['phone'] ?? '';
$dob             = $_POST['dob'] ?? '';
$currentAddress  = $_POST['current_address'] ?? '';
$propertyAddress = $_POST['property_address'] ?? '';
$purchasePrice   = $_POST['purchase_price'] ?? '';
$loanAmount      = $_POST['loan_amount'] ?? '';
$transactionType = $_POST['transaction_type'] ?? '';
$occupancy       = $_POST['occupancy'] ?? '';
$employer        = $_POST['employer'] ?? '';
$jobTitle        = $_POST['job_title'] ?? '';
$timeAtJob       = $_POST['time_at_job'] ?? '';
$monthlyIncome   = $_POST['monthly_income'] ?? '';
$otherIncome     = $_POST['other_income'] ?? '';
$bankName        = $_POST['bank_name'] ?? '';
$savings         = $_POST['savings'] ?? '';
$monthlyDebt     = $_POST['monthly_debt'] ?? '';
$consent         = isset($_POST['consent']) ? true : false;

// Build column values JSON
$columnValues = [
  "lead_email"         => ["email" => $email, "text" => $email],
  "lead_phone"         => ["phone" => $phone, "countryShortName" => "US"],
  "date_mkxd4yv6"      => $dob,
  "text_mkxdrgsj"      => $currentAddress,
  "text_mkxdvt4v"      => $propertyAddress,
  "numeric_mkxdn0rw"   => $purchasePrice,
  "numeric_mkxbgt39"   => $loanAmount,
  "dropdown_mkxbjpf6"  => ["labels" => [$transactionType]],
  "dropdown_mkxdw2t5"  => ["labels" => [$occupancy]],
  "lead_company"       => $employer,
  "text"               => $jobTitle,
  "numeric_mkxdw8b6"   => $timeAtJob,
  "numeric_mkxd3yr5"   => $monthlyIncome,
  "numeric_mkxdhe8q"   => $otherIncome,
  "text_mkxd44hz"      => $bankName,
  "numeric_mkxddesj"   => $savings,
  "numeric_mkxdmkaa"   => $monthlyDebt,
  "boolean_mkxdqnft"   => ["checked" => $consent]
];

// Build GraphQL mutation
$query = 'mutation ($boardId: Int!, $itemName: String!, $columnVals: JSON!) {
  create_item (
    board_id: $boardId,
    item_name: $itemName,
    column_values: $columnVals
  ) {
    id
  }
}';

$variables = [
  "boardId"   => $boardId,
  "itemName"  => "Lead - " . $fullName,
  "columnVals"=> json_encode($columnValues)
];

// Send request to Monday API
$ch = curl_init("https://api.monday.com/v2");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Content-Type: application/json",
  "Authorization: $apiToken"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
  "query"     => $query,
  "variables" => $variables
]));

$response = curl_exec($ch);
curl_close($ch);

// Handle response
$data = json_decode($response, true);
if (isset($data['data']['create_item']['id'])) {
  echo "Application submitted successfully!";
} else {
  echo "Error submitting application: " . $response;
}
?>
