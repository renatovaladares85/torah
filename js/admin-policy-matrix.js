(() => {
   'use strict';

   const formSelector = '[data-torah-policy-form]';
   const rowSelector = '[data-torah-field-row]';

   const isVisible = (row) => !row.hidden;

   const actionInputs = (row, action) => Array.from(
      row.querySelectorAll(`[data-torah-action="${action}"]`),
   );

   const syncBackend = (row) => {
      const backend = actionInputs(row, 'backend')[0];
      if (!backend) {
         return;
      }

      const enabled = ['opening', 'update'].some((action) => actionInputs(row, action).some((input) => input.checked));
      backend.disabled = !enabled;
      if (!enabled) {
         backend.checked = false;
      }
   };

   const isConfigured = (row) => ['opening', 'update', 'backend'].some(
      (action) => actionInputs(row, action).some((input) => input.checked),
   );

   const visibleActionInputs = (form, action) => Array.from(form.querySelectorAll(rowSelector)).flatMap((row) => {
      if (!isVisible(row)) {
         return [];
      }

      return actionInputs(row, action).filter((input) => !input.disabled);
   });

   const syncColumnToggle = (form, action) => {
      const toggle = form.querySelector(`[data-torah-column-toggle][data-torah-action="${action}"]`);
      if (!toggle) {
         return;
      }

      const inputs = visibleActionInputs(form, action);
      const checkedCount = inputs.filter((input) => input.checked).length;
      toggle.disabled = inputs.length === 0;
      toggle.checked = inputs.length > 0 && checkedCount === inputs.length;
      toggle.indeterminate = checkedCount > 0 && checkedCount < inputs.length;
   };

   const syncColumnToggles = (form) => {
      ['opening', 'update', 'backend'].forEach((action) => syncColumnToggle(form, action));
   };

   const applyFilters = (form) => {
      const search = form.querySelector('[data-torah-field-search]');
      const configuredOnly = form.querySelector('[data-torah-configured-filter]');
      const query = (search?.value ?? '').trim().toLocaleLowerCase();

      form.querySelectorAll(rowSelector).forEach((row) => {
         const label = row.dataset.torahFieldLabel ?? '';
         const matchesSearch = query === '' || label.includes(query);
         const matchesConfigured = !configuredOnly?.checked || isConfigured(row);
         row.hidden = !matchesSearch || !matchesConfigured;
      });
      syncColumnToggles(form);
   };

   const changeVisibleInputs = (form, action, checked) => {
      form.querySelectorAll(rowSelector).forEach((row) => {
         if (!isVisible(row)) {
            return;
         }

         actionInputs(row, action).forEach((input) => {
            if (!input.disabled) {
               input.checked = checked;
            }
         });
      if (action === 'opening' || action === 'update') {
         syncBackend(row);
      }
      });
      applyFilters(form);
   };

   const initializeTooltips = (form) => {
      if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
         return;
      }

      form.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
         bootstrap.Tooltip.getOrCreateInstance(element);
      });
   };

   const initializeForm = (form) => {
      if (form.dataset.torahPolicyMatrixInitialized === '1') {
         return;
      }
      form.dataset.torahPolicyMatrixInitialized = '1';

      form.querySelectorAll(rowSelector).forEach(syncBackend);
      initializeTooltips(form);
      applyFilters(form);

      form.querySelector('[data-torah-field-search]')?.addEventListener('input', () => applyFilters(form));
      form.querySelector('[data-torah-configured-filter]')?.addEventListener('change', () => applyFilters(form));

      form.addEventListener('change', (event) => {
         const input = event.target;
         if (!(input instanceof HTMLInputElement)) {
            return;
         }

         const action = input.dataset.torahAction;
         if (!action) {
            return;
         }

         const row = input.closest(rowSelector);
         if (!row) {
            return;
         }

         if (action === 'opening' || action === 'update') {
            syncBackend(row);
         }
         applyFilters(form);
      });

      form.querySelectorAll('[data-torah-column-toggle]').forEach((toggle) => {
         toggle.addEventListener('change', () => {
            const action = toggle.dataset.torahAction;
            if (!action) {
               return;
            }
            changeVisibleInputs(form, action, toggle.checked);
         });
      });
   };

   const initialize = () => document.querySelectorAll(formSelector).forEach(initializeForm);

   if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initialize, { once: true });
   } else {
      initialize();
   }
})();
