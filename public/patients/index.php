<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/patients.php';

$patients = patients_list();

$pageTitle = 'Patients';
$activeNav = 'patients';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head">
  <h1>Patients</h1>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/patients/create.php">Register patient</a>
</div>

<p class="muted"><?= count($patients) ?> registered</p>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Name</th><th>Gender</th><th>Age</th><th>Phone</th><th>Email</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$patients): ?>
      <tr><td colspan="6" class="empty">No patients yet. Register the first patient to start booking appointments.</td></tr>
    <?php endif; ?>
    <?php foreach ($patients as $p): ?>
      <tr>
        <td><?= e($p['full_name']) ?></td>
        <td><?= e(PATIENT_GENDERS[$p['gender']] ?? $p['gender']) ?></td>
        <td><?= (int) $p['age'] ?></td>
        <td><?= e($p['phone']) ?></td>
        <td><?= e($p['email']) ?></td>
        <td class="actions">
          <a href="<?= BASE_URL ?>/patients/edit.php?id=<?= (int) $p['id'] ?>">Edit</a>
          <a href="<?= BASE_URL ?>/appointments/create.php?patient_id=<?= (int) $p['id'] ?>">Book appointment</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
