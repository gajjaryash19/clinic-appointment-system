<?php
// Shared by create.php and edit.php.
// Expects: $old, $errors, $specializations, $formAction, $submitLabel
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
    <label for="specialization_id">Specialization</label>
    <select id="specialization_id" name="specialization_id" required>
      <option value="">Choose a specialization</option>
      <?php foreach ($specializations as $s): ?>
        <option value="<?= (int) $s['id'] ?>"<?= (int) $old['specialization_id'] === (int) $s['id'] ? ' selected' : '' ?>>
          <?= e($s['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['specialization_id'])): ?><p class="field-error"><?= e($errors['specialization_id']) ?></p><?php endif; ?>
  </div>

  <div class="field">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required maxlength="120" value="<?= e($old['email']) ?>">
    <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
  </div>

  <div class="field">
    <label for="phone">Phone</label>
    <input type="tel" id="phone" name="phone" required maxlength="20"
           pattern="\+?[0-9\s\-]{7,20}" inputmode="tel" value="<?= e($old['phone']) ?>">
    <p class="hint">Digits only, with an optional leading +. Spaces and dashes are fine.</p>
    <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
    <a class="btn" href="<?= BASE_URL ?>/doctors/index.php">Cancel</a>
  </div>
</form>
