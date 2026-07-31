(() => {
   'use strict';

   const formSelector = '[data-torah-policy-form]';
   const rowSelector = '[data-torah-field-row]';

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

   const selectAll = (form) => {
      form.querySelectorAll(rowSelector).forEach((row) => {
         ['opening', 'update'].forEach((action) => actionInputs(row, action).forEach((input) => {
            input.checked = true;
         }));
         syncBackend(row);
         actionInputs(row, 'backend').forEach((input) => {
            input.checked = !input.disabled;
         });
      });
   };

   const clearAll = (form) => {
      form.querySelectorAll(rowSelector).forEach((row) => {
         ['opening', 'update', 'backend'].forEach((action) => actionInputs(row, action).forEach((input) => {
            input.checked = false;
         }));
         syncBackend(row);
      });
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

      form.addEventListener('change', (event) => {
         const input = event.target;
         if (!(input instanceof HTMLInputElement) || !['opening', 'update'].includes(input.dataset.torahAction || '')) {
            return;
         }
         const row = input.closest(rowSelector);
         if (row) {
            syncBackend(row);
         }
      });
      form.querySelector('[data-torah-select-all]')?.addEventListener('click', () => selectAll(form));
      form.querySelector('[data-torah-clear-all]')?.addEventListener('click', () => clearAll(form));
   };

   const initialize = () => document.querySelectorAll(formSelector).forEach(initializeForm);
   if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initialize, { once: true });
   } else {
      initialize();
   }
})();
