<?php
// Shared by patients/create.php and patients/edit.php.
// Expects: $old, $errors, $formAction, $submitLabel
?>
<form method="post" action="<?= e($formAction) ?>" class="form">
  <?= csrf_field() ?>

  <div class="field">
    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" required minlength="2" maxlength="100"
           value="<?= e($old['full_name']) ?>">
    <?php if (isset($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name']) ?></p><?php endif; ?>
  </div>

  <div class="field">
    <label for="gender">Gender</label>
    <select id="gender" name="gender" required>
      <option value="">Choose gender</option>
      <?php foreach (PATIENT_GENDERS as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= $old['gender'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['gender'])): ?><p class="field-error"><?= e($errors['gender']) ?></p><?php endif; ?>
  </div>

  <div class="field">
    <label for="date_of_birth">Date of birth</label>
    <input type="date" id="date_of_birth" name="date_of_birth" required
           min="<?= date('Y-m-d', strtotime('-120 years')) ?>" max="<?= date('Y-m-d') ?>"
           value="<?= e($old['date_of_birth']) ?>">
    <?php if (isset($errors['date_of_birth'])): ?><p class="field-error"><?= e($errors['date_of_birth']) ?></p><?php endif; ?>
  </div>

  <div class="field">
    <label for="phone">Phone</label>
    <input type="tel" id="phone" name="phone" required maxlength="20"
           pattern="\+?[0-9\s\-]{7,20}" inputmode="tel" value="<?= e($old['phone']) ?>">
    <p class="hint">Digits only, with an optional leading +. Spaces and dashes are fine.</p>
    <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
  </div>

  <div class="field">
    <label for="email">Email (optional)</label>
    <input type="email" id="email" name="email" maxlength="120" value="<?= e($old['email']) ?>">
    <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
  </div>

  <div class="field">
    <label for="address">Address (optional)</label>
    <textarea id="address" name="address" maxlength="255" rows="3"><?= e($old['address']) ?></textarea>
    <?php if (isset($errors['address'])): ?><p class="field-error"><?= e($errors['address']) ?></p><?php endif; ?>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
    <a class="btn" href="<?= BASE_URL ?>/patients/index.php">Cancel</a>
  </div>
</form>
