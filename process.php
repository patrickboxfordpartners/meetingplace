<?php
// Capture form fields
$volume = $_POST['volume'] ?? '';
$loanamt = $_POST['loanamt'] ?? '';
$loans = $_POST['loans'] ?? '';
$jumboamt = $_POST['jumboamt'] ?? '';
$jumboloans = $_POST['jumboloans'] ?? '';
$convamt = $_POST['convamt'] ?? '';
$convloans = $_POST['convloans'] ?? '';
$governmentamt = $_POST['governmentamt'] ?? '';
$governmentloans = $_POST['governmentloans'] ?? '';
$purchaseamt = $_POST['purchaseamt'] ?? '';
$purchaseloans = $_POST['purchaseloans'] ?? '';
$refiamt = $_POST['refiamt'] ?? '';
$refiloans = $_POST['refiloans'] ?? '';
$category = $_POST['category'] ?? '';
$supercategory = $_POST['supercategory'] ?? '';
$subcategory = $_POST['subcategory'] ?? '';
$supercategory2 = $_POST['supercategory2'] ?? '';
$subcategory2 = $_POST['subcategory2'] ?? '';

// Build column values for Monday
$columnValues = [
    "volume" => $volume,
    "loanamt" => $loanamt,
    "loans" => $loans,
    "jumboamt" => $jumboamt,
    "jumboloans" => $jumboloans,
    "convamt" => $convamt,
    "convloans" => $convloans,
    "governmentamt" => $governmentamt,
    "governmentloans" => $governmentloans,
    "purchaseamt" => $purchaseamt,
    "purchaseloans" => $purchaseloans,
    "refiamt" => $refiamt,
    "refiloans" => $refiloans,
    "category" => $category,
    "supercategory" => $supercategory,
    "subcategory" => $subcategory,
    "supercategory2" => $supercategory2,
    "subcategory2" => $subcategory2
];

// GraphQL mutation
$query = 'mutation ($boardId: ID!, $itemName: String!, $columnValues: JSON!) {
  create_item(board_id: $boardId, item_name: $itemName, column_values: $columnValues) {
    id
  }
}';

// Variables — boardId must be a string
$variables = [
    "boardId" => "18323185491",
    "itemName" => "New Submission",
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

// Send to Monday
$ch = curl_init('https://api.monday.com/v2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

echo json_encode(['query' => $query, 'variables' => $variables], JSON_UNESCAPED_SLASHES);
exit;

$response = curl_exec($ch);
curl_close($ch);

// Show response for debugging
echo $response;
