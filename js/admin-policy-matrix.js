(() => {
   'use strict';

   const formSelector = '[data-torah-policy-form]';
   const rowSelector = '[data-torah-field-row]';
   const actions = ['opening', 'update', 'backend'];

   const actionInput = (row, action) => row.querySelector(`[data-torah-action="${action}"]`);
   const rows = (form) => Array.from(form.querySelectorAll(rowSelector));

   const setState = (input, applicable) => {
      if (!input) {
         return;
      }
      const selected = applicable.filter((item) => item.checked).length;
      input.checked = applicable.length > 0 && selected === applicable.length;
      input.indeterminate = selected > 0 && selected < applicable.length;
   };

   const syncBackend = (row) => {
      const backend = actionInput(row, 'backend');
      const enabled = ['opening', 'update'].some((action) => actionInput(row, action)?.checked);
      if (!backend) {
         return;
      }
      backend.disabled = !enabled;
      if (!enabled) {
         backend.checked = false;
      }
   };

   const syncRow = (row) => {
      syncBackend(row);
      setState(row.querySelector('[data-torah-row-all]'), actions.map((action) => actionInput(row, action)).filter((input) => input && !input.disabled));
   };

   const syncColumns = (form) => {
      actions.forEach((action) => {
         const applicable = rows(form).map((row) => actionInput(row, action)).filter((input) => input && !input.disabled);
         setState(form.querySelector(`[data-torah-column-all="${action}"]`), applicable);
      });
   };

   const sync = (form) => {
      rows(form).forEach(syncRow);
      syncColumns(form);
   };

   const initializeTooltips = (form) => {
      if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
         return;
      }
      form.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => bootstrap.Tooltip.getOrCreateInstance(element));
   };

   const initializeForm = (form) => {
      if (form.dataset.torahPolicyMatrixInitialized === '1') {
         return;
      }
      form.dataset.torahPolicyMatrixInitialized = '1';
      initializeTooltips(form);
      sync(form);

      form.addEventListener('change', (event) => {
         const input = event.target;
         if (!(input instanceof HTMLInputElement)) {
            return;
         }
         const row = input.closest(rowSelector);
         if (row && input.dataset.torahAction) {
            sync(form);
            return;
         }
         if (row && input.matches('[data-torah-row-all]')) {
            actions.forEach((action) => {
               const current = actionInput(row, action);
               if (current && !current.disabled) {
                  current.checked = input.checked;
               }
            });
            sync(form);
            return;
         }
         const action = input.dataset.torahColumnAll;
         if (action) {
            rows(form).forEach((targetRow) => {
               const current = actionInput(targetRow, action);
               if (current && !current.disabled) {
                  current.checked = input.checked;
               }
            });
            sync(form);
         }
      });
   };

   const initialize = () => document.querySelectorAll(formSelector).forEach(initializeForm);
   if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initialize, { once: true });
   } else {
      initialize();
   }
})();
