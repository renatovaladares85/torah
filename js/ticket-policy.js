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

            Object.entries(policy.actor_itemtypes || {}).forEach(([role, itemtypes]) => {
               if (Array.isArray(itemtypes) && !itemtypes.includes('User')) {
                   document.querySelectorAll(`form[id^="addme_as_${role}_"] button`)
                       .forEach((element) => lockElement(element, policy.message));
               }
            });
         });
   };

    const currentPolicy = () => {
         const container = document.querySelector('[data-torah-ticket-policy]');
         if (!container) {
            return {};
         }

         try {
            return JSON.parse(container.dataset.torahTicketPolicy || '{}');
         } catch (_error) {
            return {};
         }
   };

    const allowedItemtypes = (role) => {
         const policy = currentPolicy();
         const itemtypes = policy.actor_itemtypes?.[role];

         return Array.isArray(itemtypes) && itemtypes.length > 0 ? itemtypes : null;
   };

    const normalizeItemtype = (itemtype) => itemtype === 'Email' ? 'User' : itemtype;

   if (window.jQuery) {
       window.jQuery.ajaxPrefilter((options, originalOptions) => {
            const data = options.data || originalOptions.data;
            if (!data || typeof data !== 'object' || data.action !== 'getActors') {
                return;
            }

            const itemtypes = allowedItemtypes(data.actortype);
            if (itemtypes !== null) {
                data.returned_itemtypes = itemtypes;
                options.data = data;
            }
         });

       window.jQuery(document).on('select2:select', 'select[data-actor-type]', function(event) {
           const role = this.dataset.actorType;
           const itemtypes = allowedItemtypes(role);
         if (itemtypes === null) {
            return;
         }

           const selected = event.params?.data || {};
           const itemtype = normalizeItemtype(selected.itemtype || selected.element?.dataset?.itemtype || '');
         if (itemtype === '' || itemtypes.includes(itemtype)) {
             return;
         }

           const values = window.jQuery(this).val() || [];
           window.jQuery(this).val(values.filter((value) => value !== selected.id)).trigger('change');
       });
   }

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
