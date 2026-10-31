<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

$root = dirname(__DIR__);
$jsonPath = $root . '/data/content.json';
$data = is_file($jsonPath) ? json_decode((string)file_get_contents($jsonPath), true) : [];
if (!is_array($data)) $data = [];
$faq = $data['faqSection'] ?? [];
if (!is_array($faq)) $faq = [];
$items = isset($faq['items']) && is_array($faq['items']) ? $faq['items'] : [];

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function faq_admin_e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$saved = $_GET['saved'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FAQ Manager</title>
<link rel="stylesheet" href="admin.css">
<style>
.faq-admin-wrap{max-width:1250px;margin:28px auto;padding:0 22px}
.faq-admin-top{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:20px}
.faq-admin-back{color:#c94736;text-decoration:none;font-weight:700}
.faq-admin-intro,.faq-admin-section{background:#fff;border:1px solid #e6e8ee;border-radius:16px;padding:24px;margin-bottom:18px}
.faq-admin-intro h2,.faq-admin-section h3{margin:0 0 8px;color:#202638}
.faq-admin-intro p,.faq-admin-muted{color:#748096;line-height:1.6}
.faq-admin-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;margin:16px 0}
.faq-admin-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.faq-admin-field.full{grid-column:1/-1}
.faq-admin-field label{font-size:13px;font-weight:700;color:#39445a}
.faq-admin-field input,.faq-admin-field textarea{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid #dfe3eb;border-radius:9px;font:inherit;color:#283247;background:#fff}
.faq-admin-field textarea{min-height:110px;resize:vertical}
.faq-admin-list{display:grid;gap:12px;margin-top:16px}
.faq-admin-card{border:1px solid #e3e6ed;border-radius:13px;padding:16px;background:#fbfcfe}
.faq-admin-card-top{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:13px;flex-wrap:wrap}
.faq-admin-card-title{font-weight:800;color:#283247}
.faq-admin-actions{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.faq-admin-btn{border:1px solid #dfe3eb;background:#fff;color:#39445a;padding:8px 11px;border-radius:8px;cursor:pointer;font-weight:700}
.faq-admin-btn:hover{border-color:#c94736;color:#c94736}
.faq-admin-btn.danger{color:#b52f28}
.faq-admin-btn.add{background:#fff7f5;border-color:#efc8c2;color:#b83e30}
.faq-admin-enabled{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:#475166}
.faq-admin-enabled input{accent-color:#c94736;width:17px;height:17px}
.faq-admin-savebar{position:sticky;bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:12px;background:#fff;border:1px solid #e5e7ed;border-radius:13px;padding:13px 16px;box-shadow:0 8px 28px #1c243015}
.faq-admin-save{border:0;background:#c94736;color:white;padding:13px 22px;border-radius:9px;font-weight:800;cursor:pointer}
.faq-admin-save:hover{background:#a93628}
.faq-admin-notice{padding:12px 15px;border-radius:9px;margin-bottom:14px;background:#edf9f0;color:#176b35}
.faq-admin-notice.error{background:#fff0ef;color:#a12e25}
.faq-admin-hint{font-size:12px;color:#7b8495;margin-top:5px}
@media(max-width:700px){.faq-admin-fields{grid-template-columns:1fr}.faq-admin-field.full{grid-column:auto}.faq-admin-top{align-items:flex-start;flex-direction:column}.faq-admin-savebar{align-items:stretch;flex-direction:column}.faq-admin-save{width:100%}}
</style>
</head>
<body>
<div class="faq-admin-wrap">
  <div class="faq-admin-top">
    <a class="faq-admin-back" href="index.php">← Back to Dashboard</a>
    <a class="faq-admin-back" href="sections.php">Homepage Layout →</a>
  </div>
  <?php if ($saved): ?><div class="faq-admin-notice"><?=faq_admin_e($saved)?></div><?php endif; ?>
  <?php if ($error): ?><div class="faq-admin-notice error"><?=faq_admin_e($error)?></div><?php endif; ?>

  <div class="faq-admin-intro">
    <h2>FAQ Manager</h2>
    <p>Update the FAQ heading and description, add or edit questions and answers, hide individual FAQs, delete them, and change their display order. Changes are saved to <code>data/content.json</code>.</p>
  </div>

  <form method="post" action="faq-save.php" id="faqForm">
    <input type="hidden" name="csrf_token" value="<?=faq_admin_e($_SESSION['csrf_token'])?>">
    <section class="faq-admin-section">
      <h3>FAQ Section Settings</h3>
      <div class="faq-admin-fields">
        <div class="faq-admin-field"><label for="faq_id">Section ID</label><input id="faq_id" name="faq_id" value="<?=faq_admin_e($faq['id'] ?? 'faq')?>" maxlength="80"></div>
        <div class="faq-admin-field"><label for="faq_eyebrow">Eyebrow text</label><input id="faq_eyebrow" name="faq_eyebrow" value="<?=faq_admin_e($faq['eyebrow'] ?? '')?>" maxlength="160"></div>
        <div class="faq-admin-field full"><label for="faq_title">Section title</label><input id="faq_title" name="faq_title" value="<?=faq_admin_e($faq['title'] ?? 'Frequently Asked Questions')?>" maxlength="180"></div>
        <div class="faq-admin-field full"><label for="faq_description">Description</label><textarea id="faq_description" name="faq_description" maxlength="1000"><?=faq_admin_e($faq['description'] ?? '')?></textarea></div>
        <div class="faq-admin-field full"><label for="faq_bottom_text">Bottom text</label><input id="faq_bottom_text" name="faq_bottom_text" value="<?=faq_admin_e($faq['bottomText'] ?? '')?>" maxlength="500"></div>
      </div>
    </section>

    <section class="faq-admin-section">
      <div class="faq-admin-card-top">
        <div><h3>Questions &amp; Answers</h3><p class="faq-admin-muted">Drag cards or use arrows to change their order.</p></div>
        <button type="button" class="faq-admin-btn add" id="addFaq">+ Add FAQ</button>
      </div>
      <div class="faq-admin-list" id="faqList">
      <?php foreach ($items as $i => $item): if (!is_array($item)) continue; ?>
        <article class="faq-admin-card" data-card>
          <div class="faq-admin-card-top">
            <span class="faq-admin-card-title">FAQ <?=((int)$i+1)?></span>
            <div class="faq-admin-actions">
              <label class="faq-admin-enabled"><input type="checkbox" name="items[<?=$i?>][enabled]" value="1" <?=(!array_key_exists('enabled',$item)||!empty($item['enabled']))?'checked':''?>> Show</label>
              <button type="button" class="faq-admin-btn" data-up aria-label="Move FAQ up">↑</button>
              <button type="button" class="faq-admin-btn" data-down aria-label="Move FAQ down">↓</button>
              <button type="button" class="faq-admin-btn danger" data-remove>Delete</button>
            </div>
          </div>
          <div class="faq-admin-fields">
            <div class="faq-admin-field full"><label>Question</label><input name="items[<?=$i?>][question]" value="<?=faq_admin_e($item['question'] ?? '')?>" maxlength="500" required></div>
            <div class="faq-admin-field full"><label>Answer</label><textarea name="items[<?=$i?>][answer]" maxlength="5000" required><?=faq_admin_e($item['answer'] ?? '')?></textarea></div>
          </div>
        </article>
      <?php endforeach; ?>
      </div>
      <p class="faq-admin-hint">At least one complete FAQ is recommended. Hidden FAQs remain saved and can be shown again later.</p>
    </section>
    <div class="faq-admin-savebar">
      <span class="faq-admin-muted">Take a backup of content.json before the first save.</span>
      <button class="faq-admin-save" type="submit">Save FAQ Content →</button>
    </div>
  </form>
</div>
<script>
(() => {
  const list = document.getElementById('faqList');
  const form = document.getElementById('faqForm');
  let dragged = null;

  function reindex() {
    [...list.querySelectorAll('[data-card]')].forEach((card, i) => {
      card.querySelector('.faq-admin-card-title').textContent = `FAQ ${i + 1}`;
      card.querySelectorAll('input[name], textarea[name]').forEach(el => {
        const match = el.name.match(/\[(question|answer|enabled)\]$/);
        if (match) el.name = `items[${i}][${match[1]}]`;
      });
    });
  }
  function addCard() {
    const card = document.createElement('article');
    card.className = 'faq-admin-card';
    card.setAttribute('data-card', '');
    card.innerHTML = `
      <div class="faq-admin-card-top">
        <span class="faq-admin-card-title">New FAQ</span>
        <div class="faq-admin-actions">
          <label class="faq-admin-enabled"><input type="checkbox" data-field="enabled" value="1" checked> Show</label>
          <button type="button" class="faq-admin-btn" data-up>↑</button>
          <button type="button" class="faq-admin-btn" data-down>↓</button>
          <button type="button" class="faq-admin-btn danger" data-remove>Delete</button>
        </div>
      </div>
      <div class="faq-admin-fields">
        <div class="faq-admin-field full"><label>Question</label><input data-field="question" maxlength="500" required></div>
        <div class="faq-admin-field full"><label>Answer</label><textarea data-field="answer" maxlength="5000" required></textarea></div>
      </div>`;
    list.appendChild(card);
    card.querySelectorAll('[data-field]').forEach(el => {
      el.name = `items[${list.querySelectorAll('[data-card]').length - 1}][${el.dataset.field}]`;
      el.removeAttribute('data-field');
    });
    reindex();
    card.scrollIntoView({behavior:'smooth', block:'center'});
    card.querySelector('input[name$="[question]"]').focus();
  }

  document.getElementById('addFaq').addEventListener('click', addCard);
  list.addEventListener('click', e => {
    const card = e.target.closest('[data-card]');
    if (!card) return;
    if (e.target.closest('[data-remove]')) {
      if (confirm('Delete this FAQ? It will be removed after you save.')) card.remove();
      reindex();
    } else if (e.target.closest('[data-up]') && card.previousElementSibling) {
      list.insertBefore(card, card.previousElementSibling); reindex();
    } else if (e.target.closest('[data-down]') && card.nextElementSibling) {
      list.insertBefore(card.nextElementSibling, card); reindex();
    }
  });
  list.addEventListener('dragstart', e => {
    const card = e.target.closest('[data-card]');
    if (!card) return;
    dragged = card; card.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
  });
  list.addEventListener('dragover', e => {
    if (!dragged) return;
    e.preventDefault();
    const target = e.target.closest('[data-card]');
    if (!target || target === dragged) return;
    const rect = target.getBoundingClientRect();
    list.insertBefore(dragged, e.clientY < rect.top + rect.height / 2 ? target : target.nextElementSibling);
  });
  list.addEventListener('dragend', () => {
    if (dragged) dragged.classList.remove('dragging');
    dragged = null; reindex();
  });
  // Enable drag using the card header, without interfering with text fields.
  list.querySelectorAll('[data-card]').forEach(card => card.setAttribute('draggable', 'true'));
  const observer = new MutationObserver(() => list.querySelectorAll('[data-card]').forEach(card => card.setAttribute('draggable', 'true')));
  observer.observe(list, {childList:true});
  form.addEventListener('submit', reindex);
})();
</script>
</body>
</html>
