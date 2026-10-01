<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/doctors.php';

$showAll = ($_GET['show'] ?? '') === 'all';
$doctors = doctors_list($showAll);

$pageTitle = 'Doctors';
$activeNav = 'doctors';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head">
  <h1>Doctors</h1>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/doctors/create.php">Add doctor</a>
</div>

<p class="muted">
  <?php if ($showAll): ?>
    Showing all doctors. <a href="<?= BASE_URL ?>/doctors/index.php">Show active only</a>
  <?php else: ?>
    Showing active doctors. <a href="<?= BASE_URL ?>/doctors/index.php?show=all">Include inactive</a>
  <?php endif; ?>
</p>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Name</th><th>Specialization</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$doctors): ?>
      <tr><td colspan="6" class="empty">No doctors yet. Add the first one to start booking appointments.</td></tr>
    <?php endif; ?>
    <?php foreach ($doctors as $d): ?>
      <?php $active = (int) $d['is_active'] === 1; ?>
      <tr class="<?= $active ? '' : 'is-inactive' ?>">
        <td><?= e($d['full_name']) ?></td>
        <td><?= e($d['specialization']) ?></td>
        <td><?= e($d['email']) ?></td>
        <td><?= e($d['phone']) ?></td>
        <td><span class="badge <?= $active ? 'badge-ok' : '' ?>"><?= $active ? 'Active' : 'Inactive' ?></span></td>
        <td class="actions">
          <a href="<?= BASE_URL ?>/doctors/edit.php?id=<?= (int) $d['id'] ?>">Edit</a>
          <form method="post" action="<?= BASE_URL ?>/doctors/set_active.php"
                data-confirm="<?= e(($active ? 'Deactivate ' : 'Reactivate ') . $d['full_name'] . '?') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
            <input type="hidden" name="active" value="<?= $active ? '0' : '1' ?>">
            <button type="submit" class="link-button <?= $active ? 'danger' : '' ?>">
              <?= $active ? 'Deactivate' : 'Reactivate' ?>
            </button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
