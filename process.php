<?php
// ===== process.php — creates monday.com item + emails you a lead =====

// 1) Require POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Use POST";
  exit;
}

// 2) Read & sanitize the minimum required fields
$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');

// (Optional) honeypot: bots fill hidden "website" field; humans don't
if (!empty($_POST['website'] ?? '')) { http_response_code(204); exit; }

// 3) Validate requireds
$missing = [];
if ($full_name === '') $missing[] = 'full_name';
if ($email === '')     $missing[] = 'email';
if ($phone === '')     $missing[] = 'phone';

if ($missing) {
  http_response_code(400);
  $h = fn($s) => htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
  ?>
  <!doctype html><html lang="en"><head>
    <meta charset="utf-8"><title>Missing Required Fields</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial,sans-serif;margin:40px}
    code{background:#f3f6f5;padding:2px 6px;border-radius:6px}.box{max-width:720px;border:1px solid #e6eef0;border-radius:12px;padding:20px}</style>
  </head><body><div class="box">
    <h1>Missing Required Fields</h1>
    <p>We need: <strong><?= implode(', ', $missing) ?></strong></p>
    <pre>Full Name: '<?= $h($full_name) ?>'
Email:     '<?= $h($email) ?>'
Phone:     '<?= $h($phone) ?>'</pre>
    <p><a href="javascript:history.back()">Go back</a></p>
  </div></body></html><?php
  exit;
}

// 4) Create item in monday.com
$MONDAY_TOKEN = getenv('MONDAY_TOKEN'); // set in Azure → Configuration → Application settings
$BOARD_ID     = (int)(getenv('MONDAY_BOARD_ID') ?: 1234567890); // put real board id or set env
$GROUP_ID     = getenv('MONDAY_GROUP_ID') ?: 'topics';          // put real group id or set env

// Column IDs — adjust to match your board column IDs
$EMAIL_COL_ID = getenv('MONDAY_EMAIL_COL') ?: 'email';
$PHONE_COL_ID = getenv('MONDAY_PHONE_COL') ?: 'phone';
// $NOTE_COL_ID  = getenv('MONDAY_NOTE_COL')  ?: null; // optional text column

$createdMonday = false;
$mondayError   = null;

if ($MONDAY_TOKEN && $BOARD_ID && $GROUP_ID) {
  $columnValues = [
    $EMAIL_COL_ID => ['email' => $email, 'text' => $email],
    $PHONE_COL_ID => ['phone' => $phone, 'countryShortName' => 'US'],
    // if (!empty($NOTE_COL_ID)) $columnValues[$NOTE_COL_ID] = "Web lead";
  ];

  $mutation = <<<'GQL'
    mutation ($board: ID!, $group: String!, $item: String!, $cols: JSON!) {
      create_item(board_id: $board, group_id: $group, item_name: $item, column_values: $cols) { id }
    }
  GQL;

  $payload = [
    'query' => $mutation,
    'variables' => [
      'board' => $BOARD_ID,
      'group' => $GROUP_ID,
      'item'  => "Lead: " . $full_name,
      'cols'  => json_encode($columnValues, JSON_UNESCAPED_SLASHES),
    ],
  ];

  $ch = curl_init('https://api.monday.com/v2');
  curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
      'Content-Type: application/json',
      'Authorization: ' . $MONDAY_TOKEN, // monday expects raw token (no "Bearer ")
    ],
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 12,
  ]);
  $resp = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  $err  = curl_error($ch);
  curl_close($ch);

  if ($err)              { $mondayError = $err; }
  elseif ($http >= 400)  { $mondayError = "HTTP $http: $resp"; }
  else {
    $j = json_decode($resp, true);
    $createdMonday = isset($j['data']['create_item']['id']);
    if (!$createdMonday) { $mondayError = $resp; }
  }
}

// 5) Email notification (simple)
// Set LEAD_NOTIFY_TO in Azure (or hardcode your address here)
$TO_EMAIL = getenv('LEAD_NOTIFY_TO') ?: 'loans@mplacemortgage.com';
$SUBJ     = 'New Pre-Approval';
$BODY     = "Name: $full_name\nEmail: $email\nPhone: $phone\n"
          . "monday: " . ($createdMonday ? "created" : "not created") . ($mondayError ? " ($mondayError)" : "") . "\n";
$HEADERS  = "From: no-reply@mplacemortgage.com\r\nReply-To: $email\r\n";
@mail($TO_EMAIL, $SUBJ, $BODY, $HEADERS); // If not delivered, switch to SMTP (I can paste that too)

// 6) Success page (kept lightweight; use your own thank-you if you prefer)
$h = fn($s) => htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en"><head>
  <meta charset="utf-8" />
  <title>Application Received</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial,sans-serif;margin:40px}
    .card{max-width:720px;background:#fff;border:1px solid #e6eef0;border-radius:12px;padding:24px}
    h1{margin:0 0 10px}.muted{color:#566}
  </style>
</head><body>
  <div class="card">
    <h1>Thanks, we got it.</h1>
    <p class="muted">We’ll be in touch shortly.</p>
    <h3>Summary</h3>
    <ul>
      <li><strong>Name:</strong> <?= $h($full_name) ?></li>
      <li><strong>Email:</strong> <?= $h($email) ?></li>
      <li><strong>Phone:</strong> <?= $h($phone) ?></li>
    </ul>
    <?php if (!$createdMonday): ?>
      <p class="muted">Heads up: monday.com item wasn’t confirmed. We still emailed the lead. (<?= $h($mondayError ?? 'unknown') ?>)</p>
    <?php endif; ?>
  </div>
</body></html>
