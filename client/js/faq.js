/* Accessible single-open FAQ accordion */
(() => {
  document.querySelectorAll('.faq-section').forEach(section => {
    const buttons = section.querySelectorAll('.faq-question');
    buttons.forEach(button => button.addEventListener('click', () => {
      const wasOpen = button.getAttribute('aria-expanded') === 'true';
      buttons.forEach(other => {
        other.setAttribute('aria-expanded', 'false');
        other.closest('.faq-item')?.classList.remove('is-open');
        const panel = document.getElementById(other.getAttribute('aria-controls'));
        if (panel) panel.hidden = true;
      });
      if (!wasOpen) {
        button.setAttribute('aria-expanded', 'true');
        button.closest('.faq-item')?.classList.add('is-open');
        const panel = document.getElementById(button.getAttribute('aria-controls'));
        if (panel) panel.hidden = false;
      }
    }));
  });
})();
