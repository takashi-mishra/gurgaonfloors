<?php
// Uses the decoded content.json array in $data.
$faqSection = $data['faqSection'] ?? [];
$faqItems = $faqSection['items'] ?? [];
if (!is_array($faqItems)) $faqItems = [];
if (!function_exists('faqEscape')) {
    function faqEscape($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
$visibleFaqs = [];
foreach ($faqItems as $faq) {
    if (!is_array($faq)) continue;
    if (array_key_exists('enabled', $faq) && !$faq['enabled']) continue;
    $q = trim((string)($faq['question'] ?? ''));
    $a = trim((string)($faq['answer'] ?? ''));
    if ($q !== '' && $a !== '') $visibleFaqs[] = ['question'=>$q, 'answer'=>$a];
}
?>
<?php if ($visibleFaqs): ?>
<section class="faq-section" id="<?=faqEscape($faqSection['id'] ?? 'faq')?>" aria-labelledby="faq-heading">
  <div class="faq-container">
    <header class="faq-header">
      <?php if (!empty($faqSection['eyebrow'])): ?><span class="faq-eyebrow"><?=faqEscape($faqSection['eyebrow'])?></span><?php endif; ?>
      <h2 id="faq-heading"><?=faqEscape($faqSection['title'] ?? 'Frequently Asked Questions')?></h2>
      <?php if (!empty($faqSection['description'])): ?><p><?=faqEscape($faqSection['description'])?></p><?php endif; ?>
    </header>
    <div class="faq-list">
      <?php foreach ($visibleFaqs as $i=>$faq):
        $qid='faq-question-'.$i; $aid='faq-answer-'.$i; ?>
        <article class="faq-item">
          <h3 class="faq-item-heading">
            <button class="faq-question" type="button" id="<?=faqEscape($qid)?>" aria-expanded="false" aria-controls="<?=faqEscape($aid)?>">
              <span><?=faqEscape($faq['question'])?></span>
              <span class="faq-toggle" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5"/></svg></span>
            </button>
          </h3>
          <div class="faq-answer" id="<?=faqEscape($aid)?>" role="region" aria-labelledby="<?=faqEscape($qid)?>" hidden>
            <div class="faq-answer-inner"><p><?=nl2br(faqEscape($faq['answer']))?></p></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($faqSection['bottomText'])): ?><p class="faq-bottom-text"><?=faqEscape($faqSection['bottomText'])?></p><?php endif; ?>
  </div>
</section>
<?php endif; ?>
