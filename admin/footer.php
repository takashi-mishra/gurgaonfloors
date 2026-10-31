<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
$root = dirname(__DIR__);
$jsonPath = $root . '/data/content.json';
$data = is_file($jsonPath) ? json_decode((string)file_get_contents($jsonPath), true) : [];
if (!is_array($data)) $data = [];
$f = $data['footerSection'] ?? [];
if (!is_array($f)) $f = [];
$defaults = [
 'enabled'=>true,'ctaEyebrow'=>'YOUR DREAM HOME STARTS HERE',
 'ctaTitle'=>'Find Your Perfect Builder Floor in Gurgaon',
 'ctaDescription'=>'Browse our complete inventory — detailed floor plans, pricing and free site visits available across all prime sectors.',
 'ctaButton'=>'Browse All Builder Floors','expertTitleHighlight'=>'Connect With Our',
 'expertTitle'=>'Property Expert','expertSubtitle'=>'Trusted by Buyers | Market Expert',
 'expertDescription'=>'We deal in residential and commercial properties in Gurgaon. We offer our clients smarter, faster and reliable real estate services.',
 'expertButton'=>'Contact Now','phone'=>'9911922656','whatsapp'=>'8123002828',
 'logoText'=>'FLOORS','logoHighlight'=>'GURGAON',
 'brandDescription'=>'Find premium builder floors in Gurgaon. Explore trusted developers, prime locations and luxury homes.',
 'copyright'=>'All Rights Reserved.','privacyUrl'=>'#privacy',
 'quickLinks'=>[
  ['label'=>'Home','url'=>'#home'],['label'=>'Projects','url'=>'#projects'],['label'=>'Locations','url'=>'#locations'],['label'=>'About Us','url'=>'#about'],['label'=>'Contact','url'=>'#contact']],
 'exploreLinks'=>[
  ['label'=>'New Launches','url'=>'#projects'],['label'=>'Top Builders','url'=>'#builders'],['label'=>'FAQs','url'=>'#faq'],['label'=>'Investment Benefits','url'=>'#investment']],
 'features'=>[
  ['icon'=>'🏡','title'=>'Understand Localities','description'=>'Discover and get to know different localities'],
  ['icon'=>'📈','title'=>'Get Price Trends','description'=>'Get to know and analyse property market trends'],
  ['icon'=>'🛣️','title'=>'Discover Property Opportunities','description'=>'Explore upcoming locations and developments'],
  ['icon'=>'💬','title'=>'Get Insights from Residents','description'=>'Know what residents say about different localities']]
];
$f = array_replace($defaults, $f);
foreach (['quickLinks','exploreLinks','features'] as $k) if (!is_array($f[$k])) $f[$k]=$defaults[$k];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
function fe($v){return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$saved=$_GET['saved']??''; $error=$_GET['error']??'';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Footer Manager</title><link rel="stylesheet" href="admin.css"><style>
.fm-wrap{max-width:1180px;margin:28px auto;padding:0 22px}.fm-top{display:flex;justify-content:space-between;gap:12px;margin-bottom:18px}.fm-back{color:#c94736;text-decoration:none;font-weight:700}.fm-panel{background:#fff;border:1px solid #e5e8ef;border-radius:15px;padding:23px;margin-bottom:17px}.fm-panel h2,.fm-panel h3{margin:0 0 8px;color:#202638}.fm-muted{color:#758096;line-height:1.6}.fm-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;margin-top:17px}.fm-field{display:flex;flex-direction:column;gap:6px;min-width:0}.fm-field.full{grid-column:1/-1}.fm-field label{font-size:13px;font-weight:700;color:#39445a}.fm-field input,.fm-field textarea{box-sizing:border-box;width:100%;padding:11px 12px;border:1px solid #dfe3eb;border-radius:8px;font:inherit}.fm-field textarea{min-height:85px;resize:vertical}.fm-toggle{display:flex;align-items:center;gap:8px;font-weight:700}.fm-toggle input{accent-color:#c94736;width:18px;height:18px}.fm-row{border:1px solid #e3e6ed;border-radius:11px;padding:14px;margin-top:12px;background:#fbfcfe}.fm-row-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}.fm-btn{border:1px solid #dfe3eb;background:white;border-radius:8px;padding:8px 12px;cursor:pointer;font-weight:700}.fm-btn.danger{color:#b52f28}.fm-add{margin-top:12px;background:#fff7f5;color:#b83e30;border-color:#efc8c2}.fm-savebar{position:sticky;bottom:10px;background:white;border:1px solid #e5e7ed;border-radius:12px;padding:13px;display:flex;justify-content:space-between;align-items:center;gap:12px;box-shadow:0 8px 25px #1c243015}.fm-save{background:#c94736;color:white;border:0;border-radius:8px;padding:13px 22px;font-weight:800;cursor:pointer}.fm-notice{padding:12px;border-radius:9px;background:#edf9f0;color:#176b35;margin-bottom:12px}.fm-notice.err{background:#fff0ef;color:#a12e25}.fm-checks{display:flex;gap:20px;flex-wrap:wrap;margin-top:15px}@media(max-width:650px){.fm-grid{grid-template-columns:1fr}.fm-field.full{grid-column:auto}.fm-savebar,.fm-top{flex-direction:column;align-items:stretch}.fm-save{width:100%}}
</style></head><body><main class="fm-wrap">
<div class="fm-top"><a class="fm-back" href="index.php">← Back to Dashboard</a><a class="fm-back" href="../index.php" target="_blank">Preview Website ↗</a></div>
<?php if($saved):?><div class="fm-notice"><?=fe($saved)?></div><?php endif;?>
<?php if($error):?><div class="fm-notice err"><?=fe($error)?></div><?php endif;?>
<form method="post" action="footer-save.php" id="footerForm"><input type="hidden" name="csrf_token" value="<?=fe($_SESSION['csrf_token'])?>">
<section class="fm-panel"><h2>Footer Manager</h2><p class="fm-muted">Manage the footer CTA, property expert block, contact numbers, footer links and mobile contact bar. Changes are stored in data/content.json.</p>
<div class="fm-checks"><label class="fm-toggle"><input type="checkbox" name="enabled" value="1" <?=!empty($f['enabled'])?'checked':''?>> Show entire footer</label><label class="fm-toggle"><input type="checkbox" name="mobileBarEnabled" value="1" <?=!empty($f['mobileBarEnabled']) || !array_key_exists('mobileBarEnabled',$f)?'checked':''?>> Show mobile bottom bar</label></div></section>
<section class="fm-panel"><h3>Top CTA Banner</h3><div class="fm-grid">
<div class="fm-field"><label>Eyebrow</label><input name="ctaEyebrow" maxlength="150" value="<?=fe($f['ctaEyebrow'])?>"></div>
<div class="fm-field"><label>Button text</label><input name="ctaButton" maxlength="100" value="<?=fe($f['ctaButton'])?>"></div>
<div class="fm-field full"><label>Heading</label><input name="ctaTitle" maxlength="220" value="<?=fe($f['ctaTitle'])?>"></div>
<div class="fm-field full"><label>Description</label><textarea name="ctaDescription" maxlength="1000"><?=fe($f['ctaDescription'])?></textarea></div>
</div></section>
<section class="fm-panel"><h3>Property Expert Section</h3><div class="fm-grid">
<div class="fm-field"><label>Highlighted heading</label><input name="expertTitleHighlight" maxlength="120" value="<?=fe($f['expertTitleHighlight'])?>"></div>
<div class="fm-field"><label>Heading second line</label><input name="expertTitle" maxlength="120" value="<?=fe($f['expertTitle'])?>"></div>
<div class="fm-field"><label>Subtitle</label><input name="expertSubtitle" maxlength="180" value="<?=fe($f['expertSubtitle'])?>"></div>
<div class="fm-field"><label>Button text</label><input name="expertButton" maxlength="100" value="<?=fe($f['expertButton'])?>"></div>
<div class="fm-field full"><label>Description</label><textarea name="expertDescription" maxlength="1000"><?=fe($f['expertDescription'])?></textarea></div>
</div>
<div class="fm-row-list" id="featuresList"><h3>Expert feature points</h3>
<?php foreach($f['features'] as $i=>$row): if(!is_array($row))continue;?>
<div class="fm-row" data-row="feature"><div class="fm-row-head"><strong>Feature <?=($i+1)?></strong><button type="button" class="fm-btn danger" data-remove>Delete</button></div><div class="fm-grid">
<div class="fm-field"><label>Icon / emoji</label><input name="features[<?=$i?>][icon]" value="<?=fe($row['icon']??'')?>" maxlength="20"></div>
<div class="fm-field"><label>Title</label><input name="features[<?=$i?>][title]" value="<?=fe($row['title']??'')?>" maxlength="150" required></div>
<div class="fm-field full"><label>Description</label><input name="features[<?=$i?>][description]" value="<?=fe($row['description']??'')?>" maxlength="300"></div>
</div></div><?php endforeach;?>
</div><button type="button" class="fm-btn fm-add" data-add="feature">+ Add feature</button></section>
<section class="fm-panel"><h3>Contact & Brand Details</h3><div class="fm-grid">
<div class="fm-field"><label>Call phone number</label><input name="phone" value="<?=fe($f['phone'])?>" maxlength="20" required></div>
<div class="fm-field"><label>WhatsApp number</label><input name="whatsapp" value="<?=fe($f['whatsapp'])?>" maxlength="20" required></div>
<div class="fm-field"><label>Logo text</label><input name="logoText" value="<?=fe($f['logoText'])?>" maxlength="50"></div>
<div class="fm-field"><label>Logo highlight</label><input name="logoHighlight" value="<?=fe($f['logoHighlight'])?>" maxlength="70"></div>
<div class="fm-field full"><label>Brand description</label><textarea name="brandDescription" maxlength="700"><?=fe($f['brandDescription'])?></textarea></div>
<div class="fm-field"><label>Copyright suffix</label><input name="copyright" value="<?=fe($f['copyright'])?>" maxlength="150"></div>
<div class="fm-field"><label>Privacy Policy URL</label><input name="privacyUrl" value="<?=fe($f['privacyUrl'])?>" maxlength="500"></div>
</div></section>
<?php foreach(['quickLinks'=>'Quick Links','exploreLinks'=>'Explore Links'] as $key=>$label):?>
<section class="fm-panel"><h3><?=fe($label)?></h3><div id="<?=fe($key)?>List">
<?php foreach($f[$key] as $i=>$row):if(!is_array($row))continue;?>
<div class="fm-row" data-row="link"><div class="fm-row-head"><strong><?=fe($label)?> <?=($i+1)?></strong><button type="button" class="fm-btn danger" data-remove>Delete</button></div><div class="fm-grid">
<div class="fm-field"><label>Link label</label><input name="<?=$key?>[<?=$i?>][label]" value="<?=fe($row['label']??'')?>" maxlength="100" required></div>
<div class="fm-field"><label>Link URL</label><input name="<?=$key?>[<?=$i?>][url]" value="<?=fe($row['url']??'#')?>" maxlength="500" required></div>
</div></div><?php endforeach;?></div><button type="button" class="fm-btn fm-add" data-add="<?=$key?>">+ Add link</button></section>
<?php endforeach;?>
<div class="fm-savebar"><span class="fm-muted">Back up content.json before saving for the first time.</span><button class="fm-save" type="submit">Save Footer Settings →</button></div>
</form></main>
<script>
(()=>{
 const form=document.getElementById('footerForm');
 function reindex(container,kind){
  [...container.querySelectorAll('[data-row]')].forEach((row,i)=>{
   row.querySelector('strong').textContent=(kind==='feature'?'Feature ':'Link ')+(i+1);
   row.querySelectorAll('input[name]').forEach(el=>{
    const m=el.name.match(/\[(icon|title|description|label|url)\]$/);
    if(m)el.name=`${kind==='feature'?'features':container.id.replace('List','')}[${i}][${m[1]}]`;
   });
  });
 }
 document.addEventListener('click',e=>{
  const remove=e.target.closest('[data-remove]');
  if(remove){remove.closest('[data-row]').remove();document.querySelectorAll('.fm-row-list, #quickLinksList, #exploreLinksList').forEach(c=>{if(c.id||c.id==='featuresList')reindex(c,c.id==='featuresList'?'feature':'link')});}
  const add=e.target.closest('[data-add]');
  if(!add)return;
  const type=add.dataset.add, feature=type==='feature';
  const container=document.getElementById(feature?'featuresList':type+'List');
  const i=container.querySelectorAll('[data-row]').length;
  const row=document.createElement('div');row.className='fm-row';row.dataset.row=feature?'feature':'link';
  if(feature){
   row.innerHTML=`<div class="fm-row-head"><strong>Feature</strong><button type="button" class="fm-btn danger" data-remove>Delete</button></div><div class="fm-grid"><div class="fm-field"><label>Icon / emoji</label><input name="features[${i}][icon]" maxlength="20"></div><div class="fm-field"><label>Title</label><input name="features[${i}][title]" maxlength="150" required></div><div class="fm-field full"><label>Description</label><input name="features[${i}][description]" maxlength="300"></div></div>`;
  }else{
   row.innerHTML=`<div class="fm-row-head"><strong>Link</strong><button type="button" class="fm-btn danger" data-remove>Delete</button></div><div class="fm-grid"><div class="fm-field"><label>Link label</label><input name="${type}[${i}][label]" maxlength="100" required></div><div class="fm-field"><label>Link URL</label><input name="${type}[${i}][url]" maxlength="500" value="#" required></div></div>`;
  }
  container.appendChild(row);
 });
 form.addEventListener('submit',()=>{['featuresList','quickLinksList','exploreLinksList'].forEach(id=>{const c=document.getElementById(id);if(c)reindex(c,id==='featuresList'?'feature':'link')})});
})();
</script></body></html>