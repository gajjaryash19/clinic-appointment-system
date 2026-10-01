<?php
// Expects (optional): $pageTitle, $activeNav
$nav = [
    'doctors' => ['Doctors', '/doctors/index.php'],
    'patients' => ['Patients', '/patients/index.php'],
    'appointments' => ['Appointments', '/appointments/index.php'],
    // Each new feature adds its own link here.
];
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($pageTitle ?? 'Home') . ' - ' . APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="<?= BASE_URL ?>/"><?= e(APP_NAME) ?></a>
    <nav aria-label="Main">
      <?php foreach ($nav as $key => [$label, $path]): ?>
        <a href="<?= BASE_URL . $path ?>"<?= $key === $activeNav ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php foreach (flash_get() as $flash): ?>
  <div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
