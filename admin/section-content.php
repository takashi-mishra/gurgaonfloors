<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

$root = dirname(__DIR__);
$jsonPath = $root . '/data/content.json';
$data = is_file($jsonPath) ? json_decode((string)file_get_contents($jsonPath), true) : [];
if (!is_array($data)) $data = [];

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

function cm_e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function cm_rows($data, $key): array {
    return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
}

$builder = $data['builderSection'] ?? [];
$locations = $data['locationSection'] ?? [];
$investment = $data['investmentSection'] ?? [];
$sections = [
    'builder' => [
        'title' => 'Explore Floors by Builder',
        'description' => 'Manage builder directory cards.',
        'rows' => cm_rows($builder, 'items')
    ],
    'locations' => [
        'title' => 'Prime Locations',
        'description' => 'Manage Gurgaon location cards.',
        'rows' => cm_rows($locations, 'items')
    ],
    'stats' => [
        'title' => 'Investment Statistics',
        'description' => 'Manage the four large investment KPI cards.',
        'rows' => cm_rows($investment, 'stats')
    ],
    'benefits' => [
        'title' => 'Investment Benefits',
        'description' => 'Manage the investment benefit cards.',
        'rows' => cm_rows($investment, 'benefits')
    ]
];
$saved = $_GET['saved'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Section Content Manager</title>
<link rel="stylesheet" href="admin.css">
<style>
.cm-wrap{max-width:1250px;margin:28px auto;padding:0 22px}
.cm-top{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px}
.cm-back{color:#c94736;text-decoration:none;font-weight:700}
.cm-intro,.cm-section{background:#fff;border:1px solid #e6e8ee;border-radius:16px;padding:24px;margin-bottom:18px}
.cm-intro h2{margin:0 0 8px;color:#202638}.cm-intro p,.cm-muted{color:#748096;line-height:1.6}
.cm-section-head{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:16px}
.cm-section-head h3{margin:0;color:#202638}
.cm-section-head p{margin:5px 0 0;color:#7b8495;font-size:13px}
.cm-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;margin-bottom:18px}
.cm-field{display:flex;flex-direction:column;gap:6px}.cm-field.full{grid-column:1/-1}
.cm-field label{font-size:13px;font-weight:700;color:#39445a}
.cm-field input,.cm-field textarea{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid #dfe3eb;border-radius:9px;font:inherit;color:#283247;background:#fff}
.cm-field textarea{min-height:82px;resize:vertical}
.cm-list{display:grid;gap:12px}
.cm-card{border:1px solid #e3e6ed;border-radius:13px;padding:16px;background:#fbfcfe}
.cm-card-top{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:13px}
.cm-card-title{font-weight:800;color:#283247}.cm-card-actions{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.cm-btn{border:1px solid #dfe3eb;background:#fff;color:#39445a;padding:8px 11px;border-radius:8px;cursor:pointer;font-weight:700}
.cm-btn:hover{border-color:#c94736;color:#c94736}
.cm-btn.danger{color:#b52f28}.cm-btn.add{background:#fff7f5;border-color:#efc8c2;color:#b83e30}
.cm-enabled{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:#475166}
.cm-enabled input{accent-color:#c94736;width:17px;height:17px}
.cm-savebar{position:sticky;bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:12px;background:#fff;border:1px solid #e5e7ed;border-radius:13px;padding:13px 16px;box-shadow:0 8px 28px #1c243015}
.cm-save{border:0;background:#c94736;color:white;padding:13px 22px;border-radius:9px;font-weight:800;cursor:pointer}
.cm-save:hover{background:#a93628}.cm-notice{padding:12px 15px;border-radius:9px;margin-bottom:14px;background:#edf9f0;color:#176b35}.cm-notice.error{background:#fff0ef;color:#a12e25}
.cm-nav{display:flex;gap:9px;flex-wrap:wrap;margin-bottom:18px}.cm-nav a{padding:9px 13px;border-radius:8px;background:#fff;border:1px solid #e5e7ed;color:#39445a;text-decoration:none;font-size:13px;font-weight:700}
.cm-help{font-size:12px;color:#7b8495;margin-top:4px}
@media(max-width:700px){.cm-fields{grid-template-columns:1fr}.cm-field.full{grid-column:auto}.cm-top,.cm-section-head{align-items:flex-start;flex-direction:column}.cm-savebar{align-items:stretch;flex-direction:column}.cm-save{width:100%}}
</style>
</head>
<body>
<div class="cm-wrap">
  <div class="cm-top">
    <a class="cm-back" href="index.php">← Back to Dashboard</a>
    <a class="cm-back" href="sections.php">Homepage Layout →</a>
  </div>

  <?php if ($saved): ?><div class="cm-notice"><?=cm_e($saved)?></div><?php endif; ?>
  <?php if ($error): ?><div class="cm-notice error"><?=cm_e($error)?></div><?php endif; ?>

  <div class="cm-intro">
    <h2>Section Content Manager</h2>
    <p>Edit the Builder, Location and Investment content shown on the homepage. Add, remove, enable, disable and reorder cards. Changes are saved in <code>data/content.json</code>.</p>
  </div>

  <nav class="cm-nav">
    <a href="#builder">Builder Cards</a>
    <a href="#locations">Location Cards</a>
    <a href="#stats">Investment Stats</a>
    <a href="#benefits">Investment Benefits</a>
  </nav>

  <form method="post" action="section-content-save.php" id="contentForm">
    <input type="hidden" name="csrf_token" value="<?=cm_e($_SESSION['csrf_token'])?>">

    <section class="cm-section" id="builder">
      <div class="cm-section-head"><div><h3>Explore Floors by Builder</h3><p>Section heading, description, button and builder cards.</p></div><button type="button" class="cm-btn add" data-add="builder">+ Add Builder</button></div>
      <div class="cm-fields">
        <div class="cm-field"><label>Section title</label><input name="builder_title" value="<?=cm_e($builder['title'] ?? 'Explore Floors by Builder')?>"></div>
        <div class="cm-field"><label>Highlighted text</label><input name="builder_highlight" value="<?=cm_e($builder['highlight'] ?? 'Builder')?>"></div>
        <div class="cm-field full"><label>Description</label><textarea name="builder_description"><?=cm_e($builder['description'] ?? '')?></textarea></div>
        <div class="cm-field"><label>Button text</label><input name="builder_button_text" value="<?=cm_e($builder['buttonText'] ?? 'All Builders')?>"></div>
        <div class="cm-field"><label>Button URL</label><input name="builder_button_url" value="<?=cm_e($builder['buttonUrl'] ?? '#')?>"></div>
      </div>
      <div class="cm-list" data-list="builder">
        <?php foreach ($sections['builder']['rows'] as $i=>$row): ?>
        <div class="cm-card" data-card>
          <div class="cm-card-top"><span class="cm-card-title">Builder card <?=($i+1)?></span><div class="cm-card-actions"><label class="cm-enabled"><input type="checkbox" name="builder[<?=$i?>][enabled]" value="1" <?=(!array_key_exists('enabled',$row)||!empty($row['enabled']))?'checked':''?>> Show</label><button type="button" class="cm-btn" data-up>↑</button><button type="button" class="cm-btn" data-down>↓</button><button type="button" class="cm-btn danger" data-remove>Delete</button></div></div>
          <div class="cm-fields">
            <div class="cm-field"><label>Builder name</label><input name="builder[<?=$i?>][name]" value="<?=cm_e($row['name'] ?? '')?>" required></div>
            <div class="cm-field"><label>Project count text</label><input name="builder[<?=$i?>][count]" value="<?=cm_e($row['count'] ?? '')?>" placeholder="12+ Projects"></div>
            <div class="cm-field"><label>Icon (emoji or symbol)</label><input name="builder[<?=$i?>][icon]" value="<?=cm_e($row['icon'] ?? '▦')?>"></div>
            <div class="cm-field"><label>Card link URL</label><input name="builder[<?=$i?>][url]" value="<?=cm_e($row['url'] ?? '#')?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="cm-section" id="locations">
      <div class="cm-section-head"><div><h3>Prime Locations</h3><p>Section heading, description, CTA and location cards.</p></div><button type="button" class="cm-btn add" data-add="locations">+ Add Location</button></div>
      <div class="cm-fields">
        <div class="cm-field full"><label>Section title</label><input name="locations_title" value="<?=cm_e($locations['title'] ?? '')?>"></div>
        <div class="cm-field full"><label>Description</label><textarea name="locations_description"><?=cm_e($locations['description'] ?? '')?></textarea></div>
        <div class="cm-field"><label>Button text</label><input name="locations_button_text" value="<?=cm_e($locations['buttonText'] ?? '')?>"></div>
        <div class="cm-field"><label>Button URL</label><input name="locations_button_url" value="<?=cm_e($locations['buttonUrl'] ?? '#')?>"></div>
      </div>
      <div class="cm-list" data-list="locations">
        <?php foreach ($sections['locations']['rows'] as $i=>$row): ?>
        <div class="cm-card" data-card>
          <div class="cm-card-top"><span class="cm-card-title">Location card <?=($i+1)?></span><div class="cm-card-actions"><label class="cm-enabled"><input type="checkbox" name="locations[<?=$i?>][enabled]" value="1" <?=(!array_key_exists('enabled',$row)||!empty($row['enabled']))?'checked':''?>> Show</label><label class="cm-enabled"><input type="checkbox" name="locations[<?=$i?>][featured]" value="1" <?=!empty($row['featured'])?'checked':''?>> Highlight</label><button type="button" class="cm-btn" data-up>↑</button><button type="button" class="cm-btn" data-down>↓</button><button type="button" class="cm-btn danger" data-remove>Delete</button></div></div>
          <div class="cm-fields">
            <div class="cm-field"><label>Location name</label><input name="locations[<?=$i?>][name]" value="<?=cm_e($row['name'] ?? '')?>" required></div>
            <div class="cm-field"><label>Icon (emoji or symbol)</label><input name="locations[<?=$i?>][icon]" value="<?=cm_e($row['icon'] ?? '⌖')?>"></div>
            <div class="cm-field full"><label>Short description</label><textarea name="locations[<?=$i?>][description]"><?=cm_e($row['description'] ?? '')?></textarea></div>
            <div class="cm-field full"><label>Card link URL</label><input name="locations[<?=$i?>][url]" value="<?=cm_e($row['url'] ?? '#')?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="cm-section" id="stats">
      <div class="cm-section-head"><div><h3>Investment Section Settings</h3><p>Heading, description and CTA shown above/below investment cards.</p></div></div>
      <div class="cm-fields">
        <div class="cm-field full"><label>Section title</label><input name="investment_title" value="<?=cm_e($investment['title'] ?? '')?>"></div>
        <div class="cm-field full"><label>Description</label><textarea name="investment_description"><?=cm_e($investment['description'] ?? '')?></textarea></div>
        <div class="cm-field"><label>Button text</label><input name="investment_button_text" value="<?=cm_e($investment['buttonText'] ?? '')?>"></div>
        <div class="cm-field"><label>Button URL</label><input name="investment_button_url" value="<?=cm_e($investment['buttonUrl'] ?? '#contact')?>"></div>
      </div>
      <div class="cm-section-head"><div><h3>Investment Statistics</h3><p>Large numeric/stat cards.</p></div><button type="button" class="cm-btn add" data-add="stats">+ Add Statistic</button></div>
      <div class="cm-list" data-list="stats">
        <?php foreach ($sections['stats']['rows'] as $i=>$row): ?>
        <div class="cm-card" data-card>
          <div class="cm-card-top"><span class="cm-card-title">Statistic <?=($i+1)?></span><div class="cm-card-actions"><label class="cm-enabled"><input type="checkbox" name="stats[<?=$i?>][enabled]" value="1" <?=(!array_key_exists('enabled',$row)||!empty($row['enabled']))?'checked':''?>> Show</label><button type="button" class="cm-btn" data-up>↑</button><button type="button" class="cm-btn" data-down>↓</button><button type="button" class="cm-btn danger" data-remove>Delete</button></div></div>
          <div class="cm-fields"><div class="cm-field"><label>Value</label><input name="stats[<?=$i?>][value]" value="<?=cm_e($row['value'] ?? '')?>" required></div><div class="cm-field"><label>Label</label><input name="stats[<?=$i?>][label]" value="<?=cm_e($row['label'] ?? '')?>" required></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="cm-section" id="benefits">
      <div class="cm-section-head"><div><h3>Investment Benefits</h3><p>Icon, title and description benefit cards.</p></div><button type="button" class="cm-btn add" data-add="benefits">+ Add Benefit</button></div>
      <div class="cm-list" data-list="benefits">
        <?php foreach ($sections['benefits']['rows'] as $i=>$row): ?>
        <div class="cm-card" data-card>
          <div class="cm-card-top"><span class="cm-card-title">Benefit <?=($i+1)?></span><div class="cm-card-actions"><label class="cm-enabled"><input type="checkbox" name="benefits[<?=$i?>][enabled]" value="1" <?=(!array_key_exists('enabled',$row)||!empty($row['enabled']))?'checked':''?>> Show</label><button type="button" class="cm-btn" data-up>↑</button><button type="button" class="cm-btn" data-down>↓</button><button type="button" class="cm-btn danger" data-remove>Delete</button></div></div>
          <div class="cm-fields">
            <div class="cm-field"><label>Icon (emoji or symbol)</label><input name="benefits[<?=$i?>][icon]" value="<?=cm_e($row['icon'] ?? '')?>"></div>
            <div class="cm-field"><label>Benefit title</label><input name="benefits[<?=$i?>][title]" value="<?=cm_e($row['title'] ?? '')?>" required></div>
            <div class="cm-field full"><label>Description</label><textarea name="benefits[<?=$i?>][description]"><?=cm_e($row['description'] ?? '')?></textarea></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <div class="cm-savebar">
      <span class="cm-muted">Changes are written to data/content.json. Take a backup before your first save.</span>
      <button class="cm-save" type="submit">Save All Section Content →</button>
    </div>
  </form>
</div>

<script>
(() => {
  const templates = {
    builder: `<div class="cm-card" data-card><div class="cm-card-top"><span class="cm-card-title">New builder</span><div class="cm-card-actions"><label class="cm-enabled"><input type="checkbox" data-field="enabled" value="1" checked> Show</label><button type="button" class="cm-btn" data-up>↑</button><button type="button" class="cm-btn" data-down>↓</button><button type="button" class="cm-btn danger" data-remove>Delete</button></div></div><div class="cm-fields"><div class="cm-field"><label>Builder name</label><input data-field="name" required></div><div class="cm-field"><label>Project count text</label><input data-field="count" placeholder="5+ Projects"></div><div class="cm-field"><label>Icon</label><input data-field="icon" value="▦"></div><div class="cm-field"><label>Card link URL</label><input data-field="url" value="#"></div></div></div>`,
    locations: `<div class="cm-card" data-card><div class="cm-card-top"><span class="cm-card-title">New location</span><div class="cm-card-actions"><label class="cm-enabled"><input type="checkbox" data-field="enabled" value="1" checked> Show</label><label class="cm-enabled"><input type="checkbox" data-field="featured" value="1"> Highlight</label><button type="button" class="cm-btn" data-up>↑</button><button type="button" class="cm-btn" data-down>↓</button><button type="button" class="cm-btn danger" data-remove>Delete</button></div></div><div class="cm-fields"><div class="cm-field"><label>Location name</label><input data-field="name" required></div><div class="cm-field"><label>Icon</label><input data-field="icon" value="⌖"></div><div class="cm-field full"><label>Description</label><textarea data-field="description"></textarea></div><div class="cm-field full"><label>Card link URL</label><input data-field="url" value="#"></div></div></div>`,
    stats: `<div class="cm-card" data-card><div class="cm-card-top"><span class="cm-card-title">New statistic</span><div class="cm-card-actions"><label class="cm-enabled"><input type="checkbox" data-field="enabled" value="1" checked> Show</label><button type="button" class="cm-btn" data-up>↑</button><button type="button" class="cm-btn" data-down>↓</button><button type="button" class="cm-btn danger" data-remove>Delete</button></div></div><div class="cm-fields"><div class="cm-field"><label>Value</label><input data-field="value" required></div><div class="cm-field"><label>Label</label><input data-field="label" required></div></div></div>`,
    benefits: `<div class="cm-card" data-card><div class="cm-card-top"><span class="cm-card-title">New benefit</span><div class="cm-card-actions"><label class="cm-enabled"><input type="checkbox" data-field="enabled" value="1" checked> Show</label><button type="button" class="cm-btn" data-up>↑</button><button type="button" class="cm-btn" data-down>↓</button><button type="button" class="cm-btn danger" data-remove>Delete</button></div></div><div class="cm-fields"><div class="cm-field"><label>Icon</label><input data-field="icon"></div><div class="cm-field"><label>Benefit title</label><input data-field="title" required></div><div class="cm-field full"><label>Description</label><textarea data-field="description"></textarea></div></div></div>`
  };

  function reindex(listName) {
    const list = document.querySelector(`[data-list="${listName}"]`);
    [...list.children].forEach((card, i) => {
      card.querySelector('.cm-card-title').textContent =
        ({builder:'Builder card ',locations:'Location card ',stats:'Statistic ',benefits:'Benefit '})[listName] + (i + 1);
      card.querySelectorAll('[data-field]').forEach(el => {
        el.name = `${listName}[${i}][${el.dataset.field}]`;
        el.removeAttribute('data-field');
      });
      card.querySelectorAll('input[name],textarea[name]').forEach(el => {
        const m = el.name.match(/\[(name|count|icon|url|description|value|label|featured|enabled|title)\]$/);
        if (m) el.name = `${listName}[${i}][${m[1]}]`;
      });
    });
  }

  document.querySelectorAll('[data-add]').forEach(btn => btn.addEventListener('click', () => {
    const key = btn.dataset.add;
    const list = document.querySelector(`[data-list="${key}"]`);
    const wrap = document.createElement('div');
    wrap.innerHTML = templates[key].trim();
    list.appendChild(wrap.firstElementChild);
    reindex(key);
    list.lastElementChild.scrollIntoView({behavior:'smooth',block:'center'});
  }));

  document.getElementById('contentForm').addEventListener('click', e => {
    const card = e.target.closest('[data-card]');
    if (!card) return;
    const list = card.parentElement;
    const key = list.dataset.list;
    if (e.target.closest('[data-remove]')) {
      if (confirm('Delete this card? It will be removed after you save.')) card.remove();
      reindex(key);
    } else if (e.target.closest('[data-up]') && card.previousElementSibling) {
      list.insertBefore(card, card.previousElementSibling); reindex(key);
    } else if (e.target.closest('[data-down]') && card.nextElementSibling) {
      list.insertBefore(card.nextElementSibling, card); reindex(key);
    }
  });

  ['builder','locations','stats','benefits'].forEach(reindex);
})();
</script>
</body>
</html>
