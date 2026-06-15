(() => {
    'use strict';

    const lockElement = (element, message) => {
         if (element.matches('input, select, textarea, button')) {
            element.disabled = true;
         }
         element.setAttribute('aria-disabled', 'true');
         element.setAttribute('data-torah-blocked', 'true');
         element.setAttribute('title', message);

         if (element.matches('select') && element.nextElementSibling?.classList.contains('select2')) {
            element.nextElementSibling.setAttribute('aria-disabled', 'true');
            element.nextElementSibling.setAttribute('title', message);
            element.nextElementSibling.classList.add('pe-none', 'opacity-75');
         }

         if (element.matches('label')) {
            element.closest('.form-field')?.querySelectorAll('input, select, textarea, button, [role="button"]')
               .forEach((control) => lockElement(control, message));
         }
   };

    const applyPolicy = () => {
         document.querySelectorAll('[data-torah-ticket-policy]').forEach((container) => {
            let policy;
            try {
                policy = JSON.parse(container.dataset.torahTicketPolicy || '{}');
            } catch (_error) {
               return;
            }

            (policy.rules || []).forEach((rule) => {
                (rule.selectors || []).forEach((selector) => {
                     document.querySelectorAll(selector).forEach((element) => lockElement(element, policy.message));
                  });
            });
         });
   };

    document.addEventListener('DOMContentLoaded', applyPolicy);
    document.addEventListener('shown.bs.collapse', applyPolicy);
    window.setTimeout(applyPolicy, 0);

    const observer = new MutationObserver((mutations) => {
         if (mutations.some((mutation) => mutation.addedNodes.length > 0)) {
            applyPolicy();
         }
      });
    observer.observe(document.documentElement, {childList: true, subtree: true});
})();
