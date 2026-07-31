(() => {
   'use strict';

   const marker = 'data-torah-locked';

   const restore = (form) => {
      form.querySelectorAll(`[${marker}]`).forEach((element) => {
         if (element.dataset.torahReadonly === '1') {
            element.readOnly = true;
         } else {
            element.removeAttribute('readonly');
         }
         if (element.dataset.torahAriaDisabled) {
            element.setAttribute('aria-disabled', element.dataset.torahAriaDisabled);
         } else {
            element.removeAttribute('aria-disabled');
         }
         element.removeAttribute(marker);
         delete element.dataset.torahReadonly;
         delete element.dataset.torahAriaDisabled;
         delete element.dataset.torahValue;
         element.classList.remove('pe-none', 'opacity-75');
      });
   };

   const lock = (element, message) => {
      if (element.hasAttribute(marker)) {
         return;
      }
      element.setAttribute(marker, '1');
      element.dataset.torahReadonly = element.readOnly ? '1' : '0';
      element.dataset.torahAriaDisabled = element.getAttribute('aria-disabled') || '';
      element.dataset.torahValue = element.value ?? '';
      element.setAttribute('aria-disabled', 'true');
      element.setAttribute('title', message);
      if (element.matches('input[type="text"], input[type="date"], input[type="number"], textarea')) {
         element.readOnly = true;
      } else {
         element.classList.add('pe-none', 'opacity-75');
         ['click', 'keydown', 'mousedown'].forEach((eventName) => element.addEventListener(eventName, (event) => event.preventDefault(), true));
         element.addEventListener('change', () => {
            if (element.dataset.torahValue !== undefined) {
               element.value = element.dataset.torahValue;
            }
         });
      }
   };

   const payload = (container) => {
      try {
         return JSON.parse(container.dataset.torahTicketPolicy || '{}'); } catch (_) {
         return {}; }
   };

   const apply = () => {
      document.querySelectorAll('[data-torah-ticket-policy]').forEach((container) => {
         const form = container.closest('form') || document.querySelector('form[name="form_ticket"]') || document.querySelector('form');
         if (!form) {
            return;
         }
         restore(form);
         const policy = payload(container);
         (policy.rules || []).forEach((rule) => (rule.selectors || []).forEach((selector) => {
            try {
               form.querySelectorAll(selector).forEach((element) => lock(element, policy.message || 'Blocked by Torah policy.')); } catch (_) {
               /* selector is compatibility data */ }
         }));
      });
   };

   let refreshPending = false;
   const requestPolicies = () => {
      if (refreshPending) {
         return;
      }
      refreshPending = true;
      window.setTimeout(() => {
         refreshPending = false;
         document.querySelectorAll('[data-torah-ticket-policy]').forEach((container) => {
            const url = container.dataset.torahPolicyUrl;
            if (!url) {
               return;
            }
            const form = container.closest('form');
            const entity = form?.querySelector('[name="entities_id"]')?.value || container.dataset.torahEntityId || '0';
            const params = new URLSearchParams({
               action: container.dataset.torahAction || 'update',
               tickets_id: container.dataset.torahTicketId || '0',
               entities_id: entity,
            });
            window.fetch(`${url}?${params.toString()}`, { credentials: 'same-origin' })
               .then((response) => response.ok ? response.json() : null)
               .then((next) => {
                  if (next) {
                     container.dataset.torahTicketPolicy = JSON.stringify(next);
                     apply();
                  }
               })
               .catch(() => { /* retain the last server-rendered policy */ });
         });
      }, 0);
   };

   const refreshOnContextChange = () => requestPolicies();

   if (window.jQuery) {
      window.jQuery(document).on('select2:open select2:select change', 'form select, form input', refreshOnContextChange);
   }
   document.addEventListener('DOMContentLoaded', () => {
      apply();
      requestPolicies();
   });
   document.addEventListener('shown.bs.collapse', refreshOnContextChange);
   window.addEventListener('focus', refreshOnContextChange);
   document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
         refreshOnContextChange();
      }
   });
})();
