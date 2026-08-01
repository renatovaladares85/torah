(() => {
   'use strict';

   const marker = 'data-torah-locked';
   const validRoles = ['requester', 'observer', 'assign'];
   const lockableAttributes = ['aria-disabled', 'aria-readonly', 'aria-label', 'title', 'tabindex'];

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

   const selectedValues = (element) => element.matches?.('select')
      ? Array.from(element.options).filter((option) => option.selected).map((option) => option.value)
      : null;
   const snapshot = (element) => ({
      readOnly: Boolean(element.readOnly),
      disabled: Boolean(element.disabled),
      hidden: Boolean(element.hidden),
      selectedValues: selectedValues(element),
      attributes: Object.fromEntries(lockableAttributes.map((name) => [name, element.getAttribute(name)])),
   });
   const restoreSnapshot = (element, state) => {
      element.readOnly = state.readOnly;
      element.disabled = state.disabled;
      element.hidden = state.hidden;
      if (state.selectedValues !== null) {
         const selected = new Set(state.selectedValues);
         Array.from(element.options).forEach((option) => { option.selected = selected.has(option.value); });
      }
      Object.entries(state.attributes).forEach(([name, value]) => {
         if (value === null) {
            element.removeAttribute(name);
         } else {
            element.setAttribute(name, value);
         }
      });
   };
   const prevent = (event) => event.preventDefault();
   const addPreventers = (element, state, events = ['click', 'mousedown', 'pointerdown', 'keydown']) => {
      events.forEach((eventName) => {
         if (state.handlers.some(([target, savedEvent]) => target === element && savedEvent === eventName)) {
            return;
         }
         element.addEventListener(eventName, prevent, true);
         state.handlers.push([element, eventName, prevent]);
      });
   };
   const readonly = (element, message) => {
      element.readOnly = true;
      element.setAttribute('aria-readonly', 'true');
      element.setAttribute('aria-disabled', 'true');
      element.setAttribute('aria-label', message);
      element.setAttribute('title', message);
   };
   const disabledControl = (element, message) => {
      element.disabled = true;
      element.hidden = true;
      element.setAttribute('aria-disabled', 'true');
      element.setAttribute('title', message);
      element.setAttribute('tabindex', '-1');
   };
   const restore = (form) => form.querySelectorAll(`[${marker}]`).forEach((element) => {
      const state = element._torahLockState;
      if (!state) {
         element.removeAttribute(marker);
         return;
      }
      state.handlers.forEach(([target, eventName, handler]) => target.removeEventListener(eventName, handler, true));
      state.cleanup.forEach((callback) => callback());
      state.elements.forEach(([target, saved]) => restoreSnapshot(target, saved));
      if (state.flatpickr && typeof state.flatpickr.instance.set === 'function') {
         state.flatpickr.instance.set({
            allowInput: state.flatpickr.allowInput,
            clickOpens: state.flatpickr.clickOpens,
         });
      }
      state.addedClasses.forEach(([target, classes]) => target.classList.remove(...classes));
      element.removeAttribute(marker);
      delete element._torahLockState;
   });
   const stateFor = (element) => {
      if (element.hasAttribute(marker)) {
         return null;
      }
      const state = { elements: [[element, snapshot(element)]], handlers: [], cleanup: [], addedClasses: [] };
      element._torahLockState = state;
      element.setAttribute(marker, '1');
      return state;
   };
   const remember = (state, element) => {
      if (element && !state.elements.some(([target]) => target === element)) {
         state.elements.push([element, snapshot(element)]);
      }
   };
   const addClasses = (state, element, classes) => {
      const added = classes.filter((name) => !element.classList.contains(name));
      if (added.length > 0) {
         element.classList.add(...added);
         state.addedClasses.push([element, added]);
      }
   };
   const lockSimpleInput = (element, message) => {
      const state = stateFor(element);
      if (!state) {
         return;
      }
      readonly(element, message);
      addPreventers(element, state);
   };
   const restoreSelectValue = (element, state) => {
      const saved = state.elements.find(([target]) => target === element)?.[1];
      if (Array.isArray(saved?.selectedValues)) {
         const selected = new Set(saved.selectedValues);
         Array.from(element.options).forEach((option) => { option.selected = selected.has(option.value); });
      }
   };
   const lockNativeSelect = (element, message) => {
      const state = element._torahLockState || stateFor(element);
      if (!state) {
         return state;
      }
      if (state.nativeSelectLocked) {
         restoreSelectValue(element, state);
         return state;
      }
      state.nativeSelectLocked = true;
      addClasses(state, element, ['pe-none', 'opacity-75']);
      element.setAttribute('aria-disabled', 'true');
      element.setAttribute('title', message);
      element.setAttribute('tabindex', '-1');
      addPreventers(element, state, ['click', 'mousedown', 'pointerdown', 'keydown', 'wheel']);
      const restoreValue = () => restoreSelectValue(element, state);
      element.addEventListener('change', restoreValue, true);
      state.handlers.push([element, 'change', restoreValue]);

      return state;
   };
   const select2Container = (element) => {
      if (!window.jQuery) {
         return null;
      }
      const instanceContainer = window.jQuery(element).data('select2')?.$container?.[0];
      if (instanceContainer) {
         return instanceContainer;
      }

      return element.nextElementSibling?.matches('.select2-container') ? element.nextElementSibling : null;
   };
   const preventSelect2Events = (element, state) => {
      if (!window.jQuery || state.select2EventsLocked) {
         return;
      }
      const events = 'select2:opening.torahPolicy select2:selecting.torahPolicy';
      const jqueryElement = window.jQuery(element);
      jqueryElement.on(events, prevent);
      state.cleanup.push(() => jqueryElement.off(events, prevent));
      state.select2EventsLocked = true;
   };
   const lockSelect2 = (element, message) => {
      const state = lockNativeSelect(element, message) || element._torahLockState;
      if (!state) {
         return;
      }
      preventSelect2Events(element, state);
      const container = select2Container(element);
      if (!container || state.select2Containers?.includes(container)) {
         return;
      }
      state.select2Containers = [...(state.select2Containers || []), container];
      remember(state, container);
      addClasses(state, container, ['pe-none', 'opacity-75']);
      container.setAttribute('aria-disabled', 'true');
      container.setAttribute('aria-readonly', 'true');
      container.setAttribute('title', message);
      container.setAttribute('tabindex', '-1');
      addPreventers(container, state, ['click', 'mousedown', 'pointerdown', 'keydown']);
   };
   const lockFlatpickr = (original, message) => {
      const wrapper = original.closest('.flatpickr');
      if (!wrapper || wrapper.hasAttribute(marker)) {
         return;
      }
      const instance = wrapper._flatpickr || original._flatpickr;
      if (!instance) {
         lockSimpleInput(original, message);
         return;
      }
      const state = stateFor(wrapper);
      if (!state) {
         return;
      }
      state.flatpickr = {
         instance,
         allowInput: instance.config.allowInput,
         clickOpens: instance.config.clickOpens,
      };
      remember(state, original);
      readonly(original, message);
      const altInput = instance.altInput;
      if (altInput) {
         remember(state, altInput);
         readonly(altInput, message);
         addPreventers(altInput, state, ['click', 'mousedown', 'pointerdown', 'keydown', 'focus']);
      }
      wrapper.querySelectorAll('[data-toggle], [data-clear]').forEach((control) => {
         remember(state, control);
         disabledControl(control, message);
         addPreventers(control, state);
      });
      addPreventers(original, state, ['click', 'mousedown', 'pointerdown', 'keydown', 'focus']);
   if (typeof instance.close === 'function') {
      instance.close();
   }
   if (typeof instance.set === 'function') {
      instance.set({ allowInput: false, clickOpens: false });
   }
      addClasses(state, wrapper, ['opacity-75']);
   };
   const lockCompositeControl = (form, rule, message) => {
      if ((rule.controls || []).length === 0) {
         applyRule(form, { ...rule, strategy: 'text' }, message);
         return;
      }
      rule.controls.forEach((control) => applyRule(form, control, message));
   };
   const lockActor = (element, message) => {
      const state = stateFor(element);
      if (!state) {
         return;
      }
      addClasses(state, element, ['pe-none', 'opacity-75']);
      element.setAttribute('aria-disabled', 'true');
      element.setAttribute('title', message);
      addPreventers(element, state);
   };
   const applyRule = (form, rule, message) => {
      if (!rule || typeof rule !== 'object') {
         return;
      }
      if (rule.strategy === 'composite') {
         lockCompositeControl(form, rule, message);
         return;
      }
      (rule.selectors || []).forEach((selector) => {
         try {
            form.querySelectorAll(selector).forEach((element) => {
               if (rule.strategy === 'flatpickr') {
                  lockFlatpickr(element, message);
               } else if (rule.strategy === 'select2') {
                  lockSelect2(element, message);
               } else if (rule.strategy === 'select') {
                  lockNativeSelect(element, message);
               } else if (rule.strategy === 'actor' || rule.strategy === 'relation') {
                  lockActor(element, message);
               } else {
                  lockSimpleInput(element, message);
               }
            });
         } catch (_) {
            warn('A compatibility selector could not be applied.');
         }
      });
   };

   const validRule = (rule) => rule && typeof rule === 'object'
      && typeof rule.key === 'string' && typeof rule.label === 'string' && typeof rule.strategy === 'string'
      && Array.isArray(rule.selectors) && Array.isArray(rule.controls);
   const validPayload = (value) => value && typeof value === 'object' && Array.isArray(value.rules)
      && value.rules.every(validRule) && value.actor_itemtypes && typeof value.actor_itemtypes === 'object'
      && validRoles.every((role) => Array.isArray(value.actor_itemtypes[role]));
   const payload = (container) => {
      try {
         const value = JSON.parse(container.dataset.torahTicketPolicy || '{}');
         if (!validPayload(value)) {
            throw new Error('invalid payload structure');
         }
         return value;
      } catch (_) {
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
               if (!type || option.selected) {
                  return;
               }
               option.hidden = !allowed.has(type);
               option.disabled = !allowed.has(type);
            });
         });
      });
   };
   const applyContainer = (container, restoreExisting = true) => {
      const form = findForm(container);
      const policy = payload(container);
      if (!form || !policy) {
         return;
      }
      if (restoreExisting) {
         restore(form);
      }
      policy.rules.forEach((rule) => applyRule(form, rule, (policy.message || 'Blocked by Torah policy.').replace('%s', rule.label)));
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
      form.addEventListener('submit', () => applyContainer(container, false));
      const observer = new MutationObserver(() => {
         applyContainer(container, false);
         refresh();
      });
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
   document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
         document.querySelectorAll('[data-torah-ticket-policy]').forEach(requestPolicy);
      }
   });
})();
