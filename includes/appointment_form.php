<?php
// Shared by appointments/create.php and appointments/edit.php.
// Expects: $mode ('create' | 'reschedule'), $old, $errors, $freeSlots (array|null),
//          $formAction, $submitLabel
// create mode also needs $doctors and $patients; reschedule mode needs $appointment.
$isCreate = $mode === 'create';
?>
<form method="post" action="<?= e($formAction) ?>" class="form">
  <?= csrf_field() ?>

<?php if ($isCreate): ?>
  <div class="field">
    <label for="patient_id">Patient</label>
    <select id="patient_id" name="patient_id" required>
      <option value="">Choose a patient</option>
      <?php foreach ($patients as $p): ?>
        <option value="<?= (int) $p['id'] ?>"<?= (int) $old['patient_id'] === (int) $p['id'] ? ' selected' : '' ?>>
          <?= e($p['full_name'] . ' - ' . $p['phone']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['patient_id'])): ?><p class="field-error"><?= e($errors['patient_id']) ?></p><?php endif; ?>
  </div>

  <div class="field">
    <label for="doctor_id">Doctor</label>
    <select id="doctor_id" name="doctor_id" required>
      <option value="">Choose a doctor</option>
      <?php foreach ($doctors as $d): ?>
        <option value="<?= (int) $d['id'] ?>"<?= (int) $old['doctor_id'] === (int) $d['id'] ? ' selected' : '' ?>>
          <?= e($d['full_name'] . ' - ' . $d['specialization']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['doctor_id'])): ?><p class="field-error"><?= e($errors['doctor_id']) ?></p><?php endif; ?>
  </div>
<?php else: ?>
  <dl class="summary">
    <div><dt>Patient</dt><dd><?= e($appointment['patient_name']) ?></dd></div>
    <div><dt>Doctor</dt><dd><?= e($appointment['doctor_name']) ?></dd></div>
  </dl>
  <?php foreach (['patient_id', 'doctor_id'] as $key): ?>
    <?php if (isset($errors[$key])): ?><p class="field-error"><?= e($errors[$key]) ?></p><?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>

  <div class="field">
    <label for="appointment_date">Date</label>
    <input type="date" id="appointment_date" name="appointment_date" required
           min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+' . MAX_BOOKING_DAYS . ' days')) ?>"
           value="<?= e($old['appointment_date']) ?>">
    <?php if (isset($errors['appointment_date'])): ?><p class="field-error"><?= e($errors['appointment_date']) ?></p><?php endif; ?>
  </div>

  <div class="field">
    <label for="appointment_time">Time</label>
    <select id="appointment_time" name="appointment_time" required>
      <option value="">Choose a time</option>
      <?php foreach (slot_times() as $time): ?>
        <option value="<?= e($time) ?>"<?= $old['appointment_time'] === $time ? ' selected' : '' ?>><?= e($time) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['appointment_time'])): ?><p class="field-error"><?= e($errors['appointment_time']) ?></p><?php endif; ?>
    <p class="hint">
      <?php if ($freeSlots === null): ?>
        Appointments are <?= SLOT_MINUTES ?> minutes, from <?= e(CLINIC_OPEN) ?> to <?= e(CLINIC_CLOSE) ?>.
      <?php elseif ($freeSlots === []): ?>
        No free times left for this doctor on this date. Try another date.
      <?php else: ?>
        Free for this doctor on this date: <?= e(implode(', ', $freeSlots)) ?>.
      <?php endif; ?>
    </p>
  </div>

  <div class="field">
    <label for="reason">Reason for visit (optional)</label>
    <input type="text" id="reason" name="reason" maxlength="255" value="<?= e($old['reason']) ?>">
    <?php if (isset($errors['reason'])): ?><p class="field-error"><?= e($errors['reason']) ?></p><?php endif; ?>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
    <a class="btn" href="<?= BASE_URL ?>/appointments/index.php">Cancel</a>
  </div>
</form>
