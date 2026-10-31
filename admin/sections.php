<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
$root = dirname(__DIR__); $jsonPath = $root . '/data/content.json';
$data = is_file($jsonPath) ? json_decode((string)file_get_contents($jsonPath), true) : [];
if (!is_array($data)) $data = [];
$known = [
 'projects' => ['title'=>'Property Projects', 'description'=>'Your existing project/property card sections'],
 'builders' => ['title'=>'Explore Floors by Builder', 'description'=>'Builder directory cards'],
 'locations' => ['title'=>'Prime Locations', 'description'=>'Location directory cards'],
 'investment' => ['title'=>'Investment Benefits', 'description'=>'Investment statistics and benefit cards'],
];
$layout = $data['homepageLayout'] ?? [];
$order = $layout['order'] ?? ['projects','builders','locations','investment'];
$enabled = $layout['enabled'] ?? array_fill_keys(array_keys($known), true);
$order = array_values(array_filter($order, fn($k)=>isset($known[$k])));
foreach (array_keys($known) as $k) if (!in_array($k,$order,true)) $order[]=$k;
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function se($v){return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$saved = $_GET['saved'] ?? ''; $error = $_GET['error'] ?? '';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Homepage Layout Manager</title><link rel="stylesheet" href="admin.css"><style>
.layout-wrap{max-width:1050px;margin:28px auto;padding:0 22px}.layout-hero{background:#fff;border:1px solid #e7e9ef;border-radius:18px;padding:26px;margin-bottom:20px}.layout-hero h2{margin:0 0 8px}.layout-hero p{color:#687386;margin:0;line-height:1.6}.layout-list{display:grid;gap:12px}.layout-row{display:grid;grid-template-columns:42px 1fr auto;gap:15px;align-items:center;background:#fff;border:1px solid #e4e7ed;border-radius:14px;padding:17px}.layout-row.dragging{opacity:.45}.layout-number{width:38px;height:38px;border-radius:11px;background:#f5eeee;color:#c94736;display:grid;place-items:center;font-weight:800}.layout-name{font-weight:800;color:#202638}.layout-desc{font-size:13px;color:#778196;margin-top:4px}.layout-actions{display:flex;align-items:center;gap:8px}.move-btn{border:1px solid #e2e5eb;background:#fff;border-radius:8px;padding:8px 11px;cursor:pointer}.move-btn:hover{border-color:#c94736;color:#c94736}.switch{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700}.switch input{width:18px;height:18px;accent-color:#c94736}.layout-footer{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-top:20px}.save-layout{background:#c94736;color:white;border:0;border-radius:9px;padding:13px 22px;font-weight:800;cursor:pointer}.save-layout:hover{background:#a93628}.notice{padding:12px 15px;border-radius:10px;margin-bottom:15px;background:#edf9f0;color:#176b35}.notice.error{background:#fff0ef;color:#a12e25}.layout-hint{font-size:13px;color:#687386;margin-top:16px}.back-link{display:inline-block;margin-bottom:15px;color:#c94736;text-decoration:none;font-weight:700}@media(max-width:650px){.layout-row{grid-template-columns:36px 1fr}.layout-actions{grid-column:1/-1;justify-content:space-between}.layout-footer{align-items:stretch;flex-direction:column}.save-layout{width:100%}}
</style></head><body>
<div class="layout-wrap">
<a class="back-link" href="index.php">← Back to Dashboard</a>
<?php if($saved): ?><div class="notice"><?=se($saved)?></div><?php endif; ?>
<?php if($error): ?><div class="notice error"><?=se($error)?></div><?php endif; ?>
<div class="layout-hero"><h2>Homepage Layout Manager</h2><p>Drag sections or use the arrow buttons to change their order. Turn a section off to hide it from the homepage. The Projects option controls all your existing property sections as one block.</p></div>
<form method="post" action="sections-save.php" id="layoutForm">
<input type="hidden" name="csrf_token" value="<?=se($_SESSION['csrf_token'])?>">
<input type="hidden" name="order" id="sectionOrder" value="<?=se(implode(',',$order))?>">
<div class="layout-list" id="layoutList">
<?php foreach($order as $i=>$key): $item=$known[$key]; ?>
<div class="layout-row" draggable="true" data-key="<?=se($key)?>">
 <div class="layout-number">0<?= $i+1 ?></div>
 <div><div class="layout-name"><?=se($item['title'])?></div><div class="layout-desc"><?=se($item['description'])?></div></div>
 <div class="layout-actions">
  <label class="switch"><input type="checkbox" name="enabled[<?=se($key)?>]" value="1" <?=!empty($enabled[$key])?'checked':''?>> Show</label>
  <button class="move-btn" type="button" data-move="up" aria-label="Move up">↑</button>
  <button class="move-btn" type="button" data-move="down" aria-label="Move down">↓</button>
 </div>
</div>
<?php endforeach; ?>
</div>
<div class="layout-footer"><span class="layout-hint">Changes are saved to data/content.json. Your existing project cards and their order remain unchanged.</span><button class="save-layout" type="submit">Save Homepage Layout →</button></div>
</form></div>
<script>
(()=>{const list=document.getElementById('layoutList'),orderInput=document.getElementById('sectionOrder');let dragged=null;
function sync(){[...list.children].forEach((row,i)=>row.querySelector('.layout-number').textContent=String(i+1).padStart(2,'0'));orderInput.value=[...list.children].map(x=>x.dataset.key).join(',');}
list.addEventListener('click',e=>{const b=e.target.closest('[data-move]');if(!b)return;const row=b.closest('.layout-row');if(b.dataset.move==='up'&&row.previousElementSibling)list.insertBefore(row,row.previousElementSibling);if(b.dataset.move==='down'&&row.nextElementSibling)list.insertBefore(row.nextElementSibling,row);sync();});
list.addEventListener('dragstart',e=>{const row=e.target.closest('.layout-row');if(!row)return;dragged=row;row.classList.add('dragging');e.dataTransfer.effectAllowed='move';});
list.addEventListener('dragend',()=>{if(dragged)dragged.classList.remove('dragging');dragged=null;sync();});
list.addEventListener('dragover',e=>{e.preventDefault();const target=e.target.closest('.layout-row');if(!dragged||!target||target===dragged)return;const r=target.getBoundingClientRect();list.insertBefore(dragged,e.clientY<r.top+r.height/2?target:target.nextElementSibling);});
document.getElementById('layoutForm').addEventListener('submit',sync);
})();
</script></body></html>
