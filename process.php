<?php
// ===== Minimal, robust form handler =====

// 1) Require POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Use POST";
  exit;
}

// 2) Read & sanitize inputs (names must match the form)
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email     = isset($_POST['email'])     ? trim($_POST['email'])     : '';
$phone     = isset($_POST['phone'])     ? trim($_POST['phone'])     : '';

// 3) Validate requireds
$missing = [];
if ($full_name === '') $missing[] = 'full_name';
if ($email === '')     $missing[] = 'email';
if ($phone === '')     $missing[] = 'phone';

if (!empty($missing)) {
  // Return a simple HTML page with debug so you can see what reached the server
  http_response_code(400);
  $h = fn($s) => htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
  ?>
  <!doctype html>
  <html lang="en"><head>
    <meta charset="utf-8" /><title>Missing Required Fields</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <style>
      body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial,sans-serif;margin:40px}
      code{background:#f3f6f5;padding:2px 6px;border-radius:6px}
      .box{background:#fff;border:1px solid #e6eef0;border-radius:12px;padding:20px;max-width:720px}
      .miss{color:#a12b2b;font-weight:700}
    </style>
  </head><body>
    <div class="box">
      <h1>Missing Required Fields</h1>
      <p>We need: <span class="miss"><?= implode(', ', $missing) ?></span></p>
      <h3>Debug Info</h3>
      <pre>
Full Name: '<?= $h($full_name) ?>'
Email:     '<?= $h($email) ?>'
Phone:     '<?= $h($phone) ?>'
      </pre>
      <p><a href="javascript:history.back()">Go back and complete the form</a></p>
    </div>
  </body></html>
  <?php
  exit;
}

// 4) (Optional) Send to your CRM / email / monday.com here
// Example placeholder (do your API call here):
// $token = getenv('MONDAY_TOKEN'); // set in Azure App Settings
// ...curl to API...

// 5) Success: simple thank-you page (or redirect)
$h = fn($s) => htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
// Redirect example:
// header("Location: /thank-you.html", true, 303); exit;
?>
<!doctype html>
<html lang="en"><head>
  <meta charset="utf-8" />
  <title>Application Received</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Arial,sans-serif;margin:40px}
    .card{max-width:720px;background:#fff;border:1px solid #e6eef0;border-radius:12px;padding:24px}
    h1{margin:0 0 10px}
    .muted{color:#566}
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
  </div>
</body></html>
