<?php
// Include this component after FAQ and before </body>. It uses the decoded $data array.
$footer = $data['footerSection'] ?? [];
if (!is_array($footer)) $footer = [];
$footerDefaults = [
 'enabled'=>true,'mobileBarEnabled'=>true,'ctaEyebrow'=>'YOUR DREAM HOME STARTS HERE',
 'ctaTitle'=>'Find Your Perfect Builder Floor in Gurgaon','ctaDescription'=>'Browse our complete inventory — detailed floor plans, pricing and free site visits available across all prime sectors.',
 'ctaButton'=>'Browse All Builder Floors','expertTitleHighlight'=>'Connect With Our','expertTitle'=>'Property Expert',
 'expertSubtitle'=>'Trusted by Buyers | Market Expert','expertDescription'=>'We deal in residential and commercial properties in Gurgaon. We offer our clients smarter, faster and reliable real estate services.',
 'expertButton'=>'Contact Now','phone'=>'9911922656','whatsapp'=>'8123002828','logoText'=>'FLOORS','logoHighlight'=>'GURGAON',
 'brandDescription'=>'Find premium builder floors in Gurgaon. Explore trusted developers, prime locations and luxury homes.',
 'copyright'=>'All Rights Reserved.','privacyUrl'=>'#privacy',
 'quickLinks'=>[['label'=>'Home','url'=>'#home'],['label'=>'Projects','url'=>'#projects'],['label'=>'Locations','url'=>'#locations'],['label'=>'About Us','url'=>'#about'],['label'=>'Contact','url'=>'#contact']],
 'exploreLinks'=>[['label'=>'New Launches','url'=>'#projects'],['label'=>'Top Builders','url'=>'#builders'],['label'=>'FAQs','url'=>'#faq'],['label'=>'Investment Benefits','url'=>'#investment']],
 'features'=>[['icon'=>'🏡','title'=>'Understand Localities','description'=>'Discover and get to know different localities'],['icon'=>'📈','title'=>'Get Price Trends','description'=>'Get to know and analyse property market trends'],['icon'=>'🛣️','title'=>'Discover Property Opportunities','description'=>'Explore upcoming locations and developments'],['icon'=>'💬','title'=>'Get Insights from Residents','description'=>'Know what residents say about different localities']]
];
$footer = array_replace($footerDefaults, $footer);
$footerEsc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$callDigits = preg_replace('/\D+/', '', (string)$footer['phone']);
$waDigits = preg_replace('/\D+/', '', (string)$footer['whatsapp']);
?>
<?php if (!empty($footer['enabled'])): ?>
<section class="footer-cta">
 <div class="footer-cta-content">
  <?php if($footer['ctaEyebrow']!==''):?><span class="footer-cta-tag"><?=$footerEsc($footer['ctaEyebrow'])?></span><?php endif;?>
  <h2><?=$footerEsc($footer['ctaTitle'])?></h2><p><?=$footerEsc($footer['ctaDescription'])?></p>
  <button type="button" class="footer-cta-btn" data-enquiry data-project="General Property Enquiry"><?=$footerEsc($footer['ctaButton'])?> <span>→</span></button>
 </div>
</section>
<section class="footer-expert"><div class="footer-expert-container">
 <div class="footer-expert-left">
  <h2><span><?=$footerEsc($footer['expertTitleHighlight'])?></span><br><?=$footerEsc($footer['expertTitle'])?></h2>
  <h4><?=$footerEsc($footer['expertSubtitle'])?></h4><p><?=$footerEsc($footer['expertDescription'])?></p>
  <div class="footer-expert-actions">
   <button type="button" class="footer-contact-btn" data-enquiry data-project="General Property Enquiry"><?=$footerEsc($footer['expertButton'])?></button>
   <a href="tel:<?=$footerEsc($callDigits)?>" class="footer-call-link">☎ Call Now: +<?=$footerEsc($callDigits)?></a>
  </div>
 </div>
 <div class="footer-expert-right">
 <?php foreach(($footer['features']??[]) as $feature): if(!is_array($feature))continue;?>
  <div class="footer-feature"><span class="footer-feature-icon"><?=$footerEsc($feature['icon']??'')?></span><div><strong><?=$footerEsc($feature['title']??'')?></strong><p><?=$footerEsc($feature['description']??'')?></p></div></div>
 <?php endforeach;?>
 </div>
</div></section>
<footer class="main-footer"><div class="main-footer-container">
 <div class="footer-brand"><a href="index.php" class="footer-logo"><?=$footerEsc($footer['logoText'])?> <span><?=$footerEsc($footer['logoHighlight'])?></span></a><p><?=$footerEsc($footer['brandDescription'])?></p></div>
 <div class="footer-links"><h3>Quick Links</h3><?php foreach(($footer['quickLinks']??[]) as $link):if(!is_array($link))continue;?><a href="<?=$footerEsc($link['url']??'#')?>"><?=$footerEsc($link['label']??'')?></a><?php endforeach;?></div>
 <div class="footer-links"><h3>Explore</h3><?php foreach(($footer['exploreLinks']??[]) as $link):if(!is_array($link))continue;?><a href="<?=$footerEsc($link['url']??'#')?>"><?=$footerEsc($link['label']??'')?></a><?php endforeach;?></div>
 <div class="footer-links footer-contact-info"><h3>Get In Touch</h3><a href="tel:<?=$footerEsc($callDigits)?>">+<?=$footerEsc($callDigits)?></a><a href="https://wa.me/<?=$footerEsc($waDigits)?>" target="_blank" rel="noopener">WhatsApp Us</a><button type="button" data-enquiry data-project="General Property Enquiry" class="footer-enquiry-link">Send an Enquiry</button></div>
</div><div class="footer-bottom"><p>© <?=date('Y')?> <?=$footerEsc($footer['logoText'].' '.$footer['logoHighlight'])?>. <?=$footerEsc($footer['copyright'])?></p><a href="<?=$footerEsc($footer['privacyUrl'])?>">Privacy Policy</a></div></footer>
<?php endif;?>
<?php if (!empty($footer['mobileBarEnabled'])): ?>
<nav class="mobile-contact-bar" aria-label="Quick contact options">
 <button type="button" class="mobile-contact-item mobile-enquire" data-enquiry data-project="General Property Enquiry"><span class="mobile-contact-icon">●</span><span>Enquire</span></button>
 <a href="tel:<?=$footerEsc($callDigits)?>" class="mobile-contact-item mobile-call" aria-label="Call Floors Gurgaon"><span class="mobile-contact-icon">☎</span><span>Call</span></a>
 <a href="https://wa.me/<?=$footerEsc($waDigits)?>" target="_blank" rel="noopener" class="mobile-contact-item mobile-whatsapp" aria-label="Chat on WhatsApp"><span class="mobile-contact-icon">◉</span><span>WhatsApp</span></a>
</nav>
<?php endif;?>
