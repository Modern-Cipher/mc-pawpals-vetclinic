<?php
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>404 - Not Found | PawPals</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700&display=swap" rel="stylesheet">
  <style>
    :root { --primary:#20B2AA; --text:#2c3e50; }
    *{box-sizing:border-box} body{margin:0;font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f8fafb;color:var(--text)}
    .wrap{min-height:100vh;display:grid;place-items:center;padding:40px}
    .card{max-width:720px;width:100%;background:#fff;border-radius:20px;box-shadow:0 10px 40px rgba(0,0,0,.07);padding:36px;text-align:center}
    h1{font-size:2.2rem;margin:0 0 10px} p{margin:0 0 22px;opacity:.85}
    a.btn{display:inline-block;padding:12px 22px;border-radius:40px;background:var(--primary);color:#08343a;text-decoration:none;font-weight:700}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>404 – Page not found</h1>
      <p>Oops! The page you’re looking for doesn’t exist.</p>
      <a class="btn" href="<?= $BASE ?>">← Back to Home</a>
    </div>
  </div>
</body>
</html>
