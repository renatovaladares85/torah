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

      form.querySelectorAll('[data-torah-bulk]').forEach((button) => {
         button.addEventListener('click', () => {
            const action = button.dataset.torahAction;
            if (!action) {
               return;
            }
            changeVisibleInputs(form, action, button.dataset.torahBulk === 'select');
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
