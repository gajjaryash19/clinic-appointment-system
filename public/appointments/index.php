<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/appointments.php';

[$filters, $filterErrors] = appointment_filters($_GET);
$appointments = appointments_list($filters);
$total        = appointments_count($filters);
$doctors      = doctors_list(true);   // include inactive: they still have history
$patients     = patients_list();
$returnQuery  = (string) ($_SERVER['QUERY_STRING'] ?? '');
$hasFilters   = $filters['doctor_id'] !== null || $filters['patient_id'] !== null
                || $filters['date_from'] !== '' || $filters['date_to'] !== '';

$pageTitle = 'Appointments';
$activeNav = 'appointments';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-head">
  <h1>Appointments</h1>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/appointments/create.php">Book appointment</a>
</div>

<form method="get" action="<?= BASE_URL ?>/appointments/index.php" class="filters">
  <div class="field">
    <label for="doctor_id">Doctor</label>
    <select id="doctor_id" name="doctor_id">
      <option value="">All doctors</option>
      <?php foreach ($doctors as $d): ?>
        <option value="<?= (int) $d['id'] ?>"<?= $filters['doctor_id'] === (int) $d['id'] ? ' selected' : '' ?>>
          <?= e($d['full_name'] . ((int) $d['is_active'] === 1 ? '' : ' (inactive)')) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="patient_id">Patient</label>
    <select id="patient_id" name="patient_id">
      <option value="">All patients</option>
      <?php foreach ($patients as $p): ?>
        <option value="<?= (int) $p['id'] ?>"<?= $filters['patient_id'] === (int) $p['id'] ? ' selected' : '' ?>>
          <?= e($p['full_name'] . ' - ' . $p['phone']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="date_from">From</label>
    <input type="date" id="date_from" name="date_from" value="<?= e($filters['date_from']) ?>">
    <?php if (isset($filterErrors['date_from'])): ?><p class="field-error"><?= e($filterErrors['date_from']) ?></p><?php endif; ?>
  </div>
  <div class="field">
    <label for="date_to">To</label>
    <input type="date" id="date_to" name="date_to" value="<?= e($filters['date_to']) ?>">
    <?php if (isset($filterErrors['date_to'])): ?><p class="field-error"><?= e($filterErrors['date_to']) ?></p><?php endif; ?>
  </div>
  <div class="filters-actions">
    <button type="submit" class="btn btn-primary">Filter</button>
    <a class="btn" href="<?= BASE_URL ?>/appointments/index.php">Reset</a>
  </div>
</form>
<p class="hint">For a single day, set From and To to the same date.</p>

<p class="muted">
  <?php if ($total > APPOINTMENT_LIST_LIMIT): ?>
    Showing the latest <?= APPOINTMENT_LIST_LIMIT ?> of <?= $total ?> appointments. Use the filters to narrow the list.
  <?php else: ?>
    <?= $total ?> appointment<?= $total === 1 ? '' : 's' ?>
  <?php endif; ?>
</p>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>Date</th><th>Time</th><th>Doctor</th><th>Patient</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (!$appointments): ?>
      <tr><td colspan="6" class="empty">
        <?= $hasFilters ? 'No appointments match these filters.' : 'No appointments yet. Book the first one.' ?>
      </td></tr>
    <?php endif; ?>
    <?php foreach ($appointments as $a): ?>
      <tr class="is-<?= e($a['status']) ?>">
        <td><?= e(date('D, d M Y', strtotime($a['appointment_date']))) ?></td>
        <td><?= e($a['appt_time']) ?></td>
        <td><?= e($a['doctor_name']) ?><span class="sub"><?= e($a['specialization']) ?></span></td>
        <td><?= e($a['patient_name']) ?><?php if ($a['reason'] !== null && $a['reason'] !== ''): ?><span class="sub"><?= e($a['reason']) ?></span><?php endif; ?></td>
        <td><span class="badge badge-<?= e($a['status']) ?>"><?= e(APPOINTMENT_STATUSES[$a['status']]) ?></span></td>
        <td class="actions wrap">
        <?php if ($a['status'] === 'scheduled'): ?>
          <a href="<?= BASE_URL ?>/appointments/edit.php?id=<?= (int) $a['id'] ?>">Reschedule</a>
          <?php foreach (['completed' => 'Complete', 'no_show' => 'No-show', 'cancelled' => 'Cancel'] as $status => $label): ?>
            <form method="post" action="<?= BASE_URL ?>/appointments/update_status.php"
                  data-confirm="<?= e("Mark " . $a['patient_name'] . "'s appointment as " . strtolower(APPOINTMENT_STATUSES[$status]) . '?') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
              <input type="hidden" name="status" value="<?= e($status) ?>">
              <input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
              <button type="submit" class="link-button <?= $status === 'cancelled' ? 'danger' : '' ?>"><?= e($label) ?></button>
            </form>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="muted">-</span>
        <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
