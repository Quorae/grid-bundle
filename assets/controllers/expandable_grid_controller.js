import { Controller } from '@hotwired/stimulus';
import { isInteractiveDescendant } from '../src/interactive_descendant';

/**
 * Expandable-grid controller — Grid framework, direction Ledger.
 *
 * Mounted on the `<tbody>` (Turbo Frame mode) or the wrapping
 * `<div class="grid-scroll">` (LiveGrid mode) of a grid that declares
 * `expandable: true` in its `#[AsGrid]` attribute. Manages
 * expand/collapse of detail panels below each data row, with accordion
 * behaviour (only one row expanded at a time).
 *
 * Each data `<tr>` carries:
 *   - `data-action="click->expandable-grid#toggle"`
 *   - `data-expandable-grid-id-param="<rowId>"`
 *
 * Each panel `<tr>` carries:
 *   - `data-expandable-grid-target="panel"`
 *   - `data-row-id="<rowId>"`
 *   - `hidden` attribute (collapsed by default)
 *
 * Each chevron `<span>` carries:
 *   - `data-expandable-grid-target="chevron"`
 *   - `data-row-id="<rowId>"`
 *
 * In LiveGrid mode, a hidden `<input>` with
 * `data-expandable-grid-target="syncInput"` + `data-model="expandedRowId"`
 * synchronises the expanded state back to the Live Component so
 * re-renders preserve expansion across filter/sort/pagination changes.
 *
 * Targets:
 *   - chevron   : the `▸` indicators, matched by `data-row-id`
 *   - panel     : hidden `<tr>` elements, matched by `data-row-id`
 *   - syncInput : (optional) hidden `<input>` for LiveProp sync
 *
 * Dispatches:
 *   - `expandable-grid:expanded`  (detail: { rowId })
 *   - `expandable-grid:collapsed` (detail: { rowId })
 *
 * Accessibility:
 *   - Chevron carries `aria-expanded` toggled on expand/collapse.
 *   - Panel `<tr>` uses `hidden` attribute for native visibility.
 *   - Clicks on interactive descendants (<a>, <button>, <input>,
 *     <textarea>, <select>) inside the data row are ignored so
 *     nested actions (action menus, links) work normally.
 */
export default class extends Controller {
    static targets = ['chevron', 'panel', 'syncInput'];

    /**
     * Toggle expand/collapse for a row.
     * Called by `data-action="click->expandable-grid#toggle"` on data `<tr>`.
     *
     * @param {MouseEvent|KeyboardEvent} event
     */
    toggle(event) {
        if (isInteractiveDescendant(event.target, event.currentTarget)) {
            return;
        }

        const rowId = event.params.id?.toString();
        if (!rowId) {
            return;
        }

        const panel = this.panelTargets.find((p) => p.dataset.rowId === rowId);
        if (!panel) {
            return;
        }

        const dataRow = panel.previousElementSibling;

        if (!panel.hidden) {
            // Collapse this row.
            this.#collapse(panel, dataRow, rowId);
            this.#syncExpandedRowId('');
            this.dispatch('collapsed', { detail: { rowId } });

            return;
        }

        // Accordion: collapse any other expanded row first.
        this.panelTargets.forEach((p) => {
            if (!p.hidden) {
                this.#collapse(p, p.previousElementSibling, p.dataset.rowId);
            }
        });

        // Expand the target row.
        this.#expand(panel, dataRow, rowId);
        this.#syncExpandedRowId(rowId);
        this.dispatch('expanded', { detail: { rowId } });
    }

    // --- Private helpers -------------------------------------------------------

    /**
     * Expand a panel row and update its data row + chevron state.
     *
     * @param {HTMLTableRowElement} panel
     * @param {Element|null} dataRow
     * @param {string} rowId
     */
    #expand(panel, dataRow, rowId) {
        panel.hidden = false;
        dataRow?.classList.add('grid-row--expanded');

        const chevron = this.chevronTargets.find((c) => c.dataset.rowId === rowId);
        if (chevron) {
            chevron.setAttribute('aria-expanded', 'true');
        }
    }

    /**
     * Collapse a panel row and update its data row + chevron state.
     *
     * @param {HTMLTableRowElement} panel
     * @param {Element|null} dataRow
     * @param {string} rowId
     */
    #collapse(panel, dataRow, rowId) {
        panel.hidden = true;
        dataRow?.classList.remove('grid-row--expanded');

        const chevron = this.chevronTargets.find((c) => c.dataset.rowId === rowId);
        if (chevron) {
            chevron.setAttribute('aria-expanded', 'false');
        }
    }

    /**
     * Sync the expanded row id to the hidden input for LiveProp binding.
     * No-op when the syncInput target is absent (Turbo Frame mode).
     *
     * @param {string} value
     */
    #syncExpandedRowId(value) {
        if (!this.hasSyncInputTarget) {
            return;
        }

        this.syncInputTarget.value = value;
        // Dispatch input event so Live Component picks up the change.
        this.syncInputTarget.dispatchEvent(new Event('input', { bubbles: true }));
    }

}
