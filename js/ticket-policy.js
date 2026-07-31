(() => {
   'use strict';

   const marker = 'data-torah-locked';
   const validRoles = ['requester', 'observer', 'assign'];

   const warn = (message) => console.warn(`[Torah] ${message}`);
   const findForm = (container) => {
      const local = container.closest('form');
      if (local?.matches('form#itil-form')) {
         return local;
      }
      const selector = container.dataset.torahFormSelector;
      const form = selector ? document.querySelector(selector) : null;
      return form instanceof HTMLFormElement ? form : null;
   };

   const restore = (form) => form.querySelectorAll(`[${marker}]`).forEach((element) => {
      const state = element._torahLockState;
      if (state) {
         element.readOnly = state.readOnly;
         if (state.ariaDisabled === null) {
            element.removeAttribute('aria-disabled');
         } else {
            element.setAttribute('aria-disabled', state.ariaDisabled);
         }
         if (state.title === null) {
            element.removeAttribute('title');
         } else {
            element.setAttribute('title', state.title);
         }
         state.handlers.forEach(([eventName, handler]) => element.removeEventListener(eventName, handler, true));
      }
      element.classList.remove('pe-none', 'opacity-75');
      if (window.jQuery && window.jQuery(element).data('select2')) {
         window.jQuery(element).next('.select2-container').removeClass('pe-none opacity-75');
      }
      element.removeAttribute(marker);
      delete element._torahLockState;
   });

   const lock = (element, message) => {
      if (element.hasAttribute(marker)) {
         return;
      }
      const state = { readOnly: Boolean(element.readOnly), ariaDisabled: element.getAttribute('aria-disabled'), title: element.getAttribute('title'), value: element.value, handlers: [] };
      const prevent = (event) => event.preventDefault();
      const restoreValue = () => { if (element.value !== state.value) {
            element.value = state.value;
      } };
      ['click', 'keydown', 'mousedown', 'select2:opening'].forEach((eventName) => {
         element.addEventListener(eventName, prevent, true);
         state.handlers.push([eventName, prevent]);
      });
      element.addEventListener('change', restoreValue, true);
      state.handlers.push(['change', restoreValue]);
      element._torahLockState = state;
      element.setAttribute(marker, '1');
      element.setAttribute('aria-disabled', 'true');
      element.setAttribute('title', message);
   if (element.matches('input[type="text"], input[type="date"], input[type="number"], textarea')) {
      element.readOnly = true;
   } else {
      element.classList.add('pe-none', 'opacity-75');
   }
   if (window.jQuery && window.jQuery(element).data('select2')) {
      window.jQuery(element).next('.select2-container').addClass('pe-none opacity-75');
   }
   };

   const validPayload = (value) => {
      if (!value || typeof value !== 'object' || !Array.isArray(value.rules) || !value.actor_itemtypes || typeof value.actor_itemtypes !== 'object') {
         return false;
      }
      return validRoles.every((role) => Array.isArray(value.actor_itemtypes[role]));
   };

   const payload = (container) => {
      try {
         const value = JSON.parse(container.dataset.torahTicketPolicy || '{}');
         if (!validPayload(value)) {
            throw new Error('invalid payload structure');
         }
         return value;
      } catch (error) {
         warn('The ticket policy payload is invalid; keeping the form unchanged.');
         return null;
      }
   };

   const actorType = (value) => {
      if (typeof value !== 'string') {
         return null;
      }
      const normalized = value.replace(/^.*\\/, '');
      return ['User', 'Group', 'Supplier'].includes(normalized) ? normalized : null;
   };

   const applyActorTypes = (form, allowedByRole) => {
      validRoles.forEach((role) => {
         const allowed = new Set(allowedByRole[role]);
         form.querySelectorAll(`[data-actor-type="${role}"], [data-torah-actor-role="${role}"]`).forEach((area) => {
            area.querySelectorAll('[data-itemtype], option[value]').forEach((option) => {
               const type = actorType(option.dataset.itemtype || option.value);
               if (!type) {
                  return;
               }
               if (option.selected) {
                  return;
               }
               option.hidden = !allowed.has(type);
               option.disabled = !allowed.has(type);
            });
         });
      });
   };

   const applyContainer = (container) => {
      const form = findForm(container);
      const policy = payload(container);
      if (!form || !policy) {
         return;
      }
      restore(form);
      policy.rules.forEach((rule) => (rule.selectors || []).forEach((selector) => {
         try {
            form.querySelectorAll(selector).forEach((element) => lock(element, policy.message || 'Blocked by Torah policy.')); } catch (_) {
            warn('A compatibility selector could not be applied.'); }
      }));
      applyActorTypes(form, policy.actor_itemtypes);
   };

   const apply = () => document.querySelectorAll('[data-torah-ticket-policy]').forEach(applyContainer);
   const requestPolicy = (container) => {
      const form = findForm(container);
      const url = container.dataset.torahPolicyUrl;
      if (!form || !url || container.dataset.torahRefreshing === '1') {
         return;
      }
      container.dataset.torahRefreshing = '1';
      const entity = form.querySelector('[name="entities_id"]')?.value || container.dataset.torahEntityId || '0';
      const params = new URLSearchParams({ action: container.dataset.torahAction || 'update', tickets_id: container.dataset.torahTicketId || '0', entities_id: entity });
      window.fetch(`${url}?${params.toString()}`, { credentials: 'same-origin' })
         .then((response) => {
            if (!response.ok) {
               throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
         })
         .then((next) => {
            if (!validPayload(next)) {
               throw new Error('invalid payload structure');
            }
            container.dataset.torahTicketPolicy = JSON.stringify(next);
            applyContainer(container);
         })
         .catch((error) => warn(`Unable to refresh ticket policy (${error.message}).`))
         .finally(() => { delete container.dataset.torahRefreshing; });
   };

   const initialize = () => document.querySelectorAll('[data-torah-ticket-policy]').forEach((container) => {
      const form = findForm(container);
      if (!form || form.dataset.torahPolicyInitialized === '1') {
         return;
      }
      form.dataset.torahPolicyInitialized = '1';
      let timer = null;
      const refresh = () => {
         window.clearTimeout(timer);
         timer = window.setTimeout(() => requestPolicy(container), 80);
      };
      form.addEventListener('change', refresh);
      form.addEventListener('shown.bs.collapse', refresh);
      form.addEventListener('submit', () => applyContainer(container));
      const observer = new MutationObserver(refresh);
      observer.observe(form, { childList: true, subtree: true });
      applyContainer(container);
      refresh();
   });

if (document.readyState === 'loading') {
   document.addEventListener('DOMContentLoaded', initialize, { once: true });
} else {
   initialize();
}
   window.addEventListener('focus', () => document.querySelectorAll('[data-torah-ticket-policy]').forEach(requestPolicy));
   document.addEventListener('visibilitychange', () => { if (!document.hidden) {
         document.querySelectorAll('[data-torah-ticket-policy]').forEach(requestPolicy);
   } });
})();
