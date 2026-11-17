<?php if (!empty($flash)): ?>
    <div class="alert <?= ($flash['type'] ?? '') === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message'] ?? '') ?></div>
<?php endif; ?>
