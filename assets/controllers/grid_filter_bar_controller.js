import { Controller } from '@hotwired/stimulus';
import { createOutsideClickHandler } from '../src/outside_click';

/**
 * Grid filter bar controller — state machine + ARIA combobox pattern 1.
 *
 * State machine modes:
 *   'closed' — popover hidden
 *   'keys'   — popover shows filter key list
 *   'values' — popover shows values for a specific filter key
 *
 * ARIA pattern 1: focus stays on the input at all times.
 * Highlighted suggestion is tracked via `aria-activedescendant` +
 * visual `fb-pop-item--active` class, NOT via `element.focus()`.
 */
export default class extends Controller {
    static targets = ['input', 'popover', 'suggestion', 'bar', 'prefix', 'announcer'];
    static values = {
        open: { type: Boolean, default: false },
        prefixes: { type: Object, default: {} },
        debounce: { type: Number, default: 300 },
    };

    /** @type {{ signal: AbortSignal, teardown: () => void }|null} */
    #outsideHandler = null;

    /** @type {boolean} */
    #liveMode = false;

    /** @type {number|null} */
    #searchTimer = null;

    // ─── State machine fields ─────────────────────────────────────────────────

    /** @type {'closed'|'keys'|'values'} */
    #currentMode = 'closed';

    /** @type {string|null} */
    #currentKey = null;

    /** @type {number} Index into suggestionTargets, -1 = none highlighted */
    #highlightIndex = -1;

    /** @type {boolean} Suppress next onFocus reopen after #applyFilter */
    #suppressNextFocus = false;

    /** @type {string} Grid name extracted from popover id */
    #gridName = '';

    /** @type {((e: KeyboardEvent) => void)|null} */
    #globalKeyHandler = null;

    /** @type {Object<string, {label?: string, type?: string, choices?: Object, active?: boolean}>} */
    #prefixes = {};

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    connect() {
        this.#liveMode = !!this.element.closest('[data-controller~="live"]');
        this.#gridName = this.hasPopoverTarget
            ? this.popoverTarget.id.replace('fb-pop-', '')
            : '';
        try {
            const raw = this.prefixesValue;
            this.#prefixes = (raw && typeof raw === 'object' && !Array.isArray(raw)) ? raw : {};
        } catch {
            this.#prefixes = {};
        }
        this.#syncAria(this.openValue);

        this.#globalKeyHandler = this.#handleGlobalKey.bind(this);
        window.addEventListener('keydown', this.#globalKeyHandler);
    }

    disconnect() {
        this.#teardownOutsideListener();
        this.#cancelSearch();

        if (this.#globalKeyHandler) {
            window.removeEventListener('keydown', this.#globalKeyHandler);
            this.#globalKeyHandler = null;
        }
    }

    // ─── Value callbacks ──────────────────────────────────────────────────────

    openValueChanged(isOpen) {
        this.#syncAria(isOpen);

        if (isOpen) {
            this.#setupOutsideListener();
        } else {
            this.#teardownOutsideListener();
        }
    }

    // ─── State machine core ───────────────────────────────────────────────────

    /**
     * Transition the state machine to a new mode.
     * @param {'closed'|'keys'|'values'} mode
     * @param {string|null} [key]
     */
    #transition(mode, key = null) {
        this.#currentMode = mode;
        this.#currentKey = key;
        this.#applyState();
    }

    #applyState() {
        const isOpen = this.#currentMode !== 'closed';
        this.openValue = isOpen;

        if (this.#currentMode === 'closed' || this.#currentMode === 'keys') {
            this.#currentKey = null;
            if (this.hasPrefixTarget) {
                this.prefixTarget.textContent = '';
            }
        }
    }

    // ─── Announcements (screen reader) ────────────────────────────────────────

    /**
     * Write a message to the sr-only live region.
     * Clears then sets in a new frame to force re-announcement.
     * @param {string} msg
     */
    #announce(msg) {
        if (this.hasAnnouncerTarget) {
            this.announcerTarget.textContent = '';
            requestAnimationFrame(() => {
                this.announcerTarget.textContent = msg;
            });
        }
    }

    // ─── Highlight helpers (ARIA pattern 1) ───────────────────────────────────

    /**
     * @returns {HTMLElement|null} The currently highlighted suggestion, or null.
     */
    #getHighlightedSuggestion() {
        const suggestions = this.suggestionTargets;
        if (this.#highlightIndex >= 0 && this.#highlightIndex < suggestions.length) {
            return suggestions[this.#highlightIndex];
        }
        return null;
    }

    /**
     * Move the visual highlight by delta (+1 or -1).
     * Focus stays on the input (ARIA pattern 1).
     * @param {number} delta
     */
    #moveFocus(delta) {
        const suggestions = this.suggestionTargets;
        if (suggestions.length === 0) {
            return;
        }

        // Clear previous highlight
        this.#clearHighlight();

        let nextIndex = this.#highlightIndex + delta;

        // At top and pressing up: return to input (no highlight)
        if (nextIndex < 0) {
            this.#highlightIndex = -1;
            if (this.hasInputTarget) {
                this.inputTarget.removeAttribute('aria-activedescendant');
            }
            return;
        }

        // Wrap around at bottom
        if (nextIndex >= suggestions.length) {
            nextIndex = 0;
        }

        this.#highlightIndex = nextIndex;
        const target = suggestions[nextIndex];

        // Visual highlight
        target.classList.add('fb-pop-item--active');

        // ARIA: mark selected
        target.setAttribute('aria-selected', 'true');

        // ARIA: activedescendant on input
        if (this.hasInputTarget && target.id) {
            this.inputTarget.setAttribute('aria-activedescendant', target.id);
        }

        // Scroll into view
        target.scrollIntoView({ block: 'nearest' });
    }

    /**
     * Remove visual highlight and aria-selected from all suggestions.
     */
    #clearHighlight() {
        this.suggestionTargets.forEach((s) => {
            s.classList.remove('fb-pop-item--active');
            s.setAttribute('aria-selected', 'false');
        });
    }

    // ─── Public actions ───────────────────────────────────────────────────────

    onInput(event) {
        const value = /** @type {HTMLInputElement} */ (event.currentTarget).value;

        if (this.#currentKey !== null) {
            this.#cancelSearch();
            this.#renderSuggestions(this.#currentKey, `${this.#currentKey}:${value}`);
            this.openValue = true;
            return;
        }

        const prefix = this.#detectPrefix(value);

        if (prefix !== null) {
            this.#cancelSearch();
            const afterColon = value.slice(value.indexOf(':') + 1).trim();
            this.#enterValueMode(prefix);
            if (this.hasInputTarget) {
                this.inputTarget.value = afterColon;
            }
            this.#renderSuggestions(prefix, `${prefix}:${afterColon}`);
            this.openValue = true;
        } else if (value.trim() === '') {
            this.#cancelSearch();
            this.#currentKey = null;
            this.#currentMode = 'keys';
            this.#renderAllFilters();
            this.openValue = true;
            if (this.#liveMode) {
                this.#syncSearchModel();
            }
        } else {
            const matches = this.#matchByLabel(value.trim());
            if (matches.length > 0) {
                this.#cancelSearch();
                this.#currentKey = null;
                this.#currentMode = 'keys';
                this.#renderKeyList(matches);
                this.openValue = true;
            } else {
                this.#currentKey = null;
                this.#currentMode = 'closed';
                this.openValue = false;
                if (this.#liveMode) {
                    this.#debouncedSearch();
                }
            }
        }
    }

    onFocus() {
        if (this.#suppressNextFocus) {
            this.#suppressNextFocus = false;
            return;
        }

        if (this.hasBarTarget) {
            this.barTarget.classList.add('fb-bar--focus');
        }

        // Pre-render suggestions so ArrowDown can highlight immediately,
        // but do NOT open the popover — let onInput or ArrowDown handle that.
        if (this.#currentKey !== null) {
            const value = this.hasInputTarget ? this.inputTarget.value : '';
            this.#renderSuggestions(this.#currentKey, `${this.#currentKey}:${value}`);
        } else if (this.hasInputTarget) {
            const value = this.inputTarget.value;
            const prefix = this.#detectPrefix(value);

            if (prefix !== null) {
                this.#renderSuggestions(prefix, value);
            } else if (value.trim() !== '') {
                const matches = this.#matchByLabel(value.trim());
                this.#renderKeyList(matches.length > 0 ? matches : Object.entries(this.#prefixes));
            } else {
                this.#renderAllFilters();
            }
        }
    }

    onBlur() {
        if (this.hasBarTarget) {
            this.barTarget.classList.remove('fb-bar--focus');
        }
    }

    onKeydown(event) {
        switch (event.key) {
            case 'ArrowDown':
                if (this.#currentMode === 'closed' || !this.openValue) {
                    this.#transition('keys');
                    this.#renderAllFilters();
                    const count = this.suggestionTargets.length;
                    this.#announce(`Filtres, ${count} option${count > 1 ? 's' : ''}`);
                    this.#moveFocus(1);
                } else {
                    event.preventDefault();
                    this.#moveFocus(1);
                }
                break;
            case 'ArrowUp':
                if (this.#currentMode !== 'closed') {
                    event.preventDefault();
                    this.#moveFocus(-1);
                }
                break;
            case 'Enter': {
                const highlighted = this.#getHighlightedSuggestion();
                if (this.#currentMode !== 'closed' && highlighted) {
                    event.preventDefault();
                    this.#applyFilter(highlighted);
                }
                // else: let browser handle (e.g. form submit in Turbo mode)
                break;
            }
            case 'Escape':
                if (this.#currentMode === 'values') {
                    event.preventDefault();
                    event.stopPropagation();
                    this.#exitValueMode();
                } else if (this.#currentMode === 'keys') {
                    event.preventDefault();
                    this.#close();
                } else if (this.hasInputTarget && this.inputTarget.value !== '') {
                    event.preventDefault();
                    this.inputTarget.value = '';
                    if (this.#liveMode) {
                        this.#syncSearchModel();
                    }
                }
                break;
            case 'Backspace':
                if (this.#currentMode !== 'closed' && this.hasInputTarget && this.inputTarget.value === '') {
                    event.preventDefault();
                }
                break;
            default:
                break;
        }
    }

    onPopoverKeydown(event) {
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.#moveFocus(1);
                this.#refocusInput();
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.#moveFocus(-1);
                this.#refocusInput();
                break;
            case 'Escape':
                if (this.#currentMode === 'values') {
                    event.preventDefault();
                    event.stopPropagation();
                    this.#exitValueMode();
                } else {
                    event.preventDefault();
                    this.#close();
                }
                this.#refocusInput();
                break;
            case 'Enter':
            case ' ': {
                const focused = /** @type {HTMLElement|null} */ (event.target);
                if (focused?.dataset?.filterProperty) {
                    event.preventDefault();
                    this.#applyFilter(focused);
                }
                this.#refocusInput();
                break;
            }
            case 'Tab':
                this.#close();
                // No preventDefault — browser advances focus naturally
                break;
            default:
                break;
        }
    }

    selectSuggestion(event) {
        this.#applyFilter(/** @type {HTMLElement} */ (event.currentTarget));
    }

    #applyFilter(el) {
        const property = el.dataset.filterProperty ?? '';
        const value = el.dataset.filterValue ?? '';
        const label = el.dataset.filterLabel ?? value;

        if (!property) {
            return;
        }

        if (el.dataset.filterIsKey === 'true') {
            this.#enterValueMode(property);
            if (this.hasInputTarget) {
                this.inputTarget.value = '';
                this.inputTarget.focus();
            }
            this.#renderSuggestions(property, `${property}:`);
            const count = this.suggestionTargets.length;
            this.#announce(`${this.#prefixes[property]?.label ?? property}, ${count} valeur${count > 1 ? 's' : ''}`);
            return;
        }

        this.#cancelSearch();

        if (this.hasInputTarget) {
            this.inputTarget.value = '';
        }
        if (this.hasPrefixTarget) {
            this.prefixTarget.textContent = '';
        }
        this.#currentKey = null;

        this.#close();
        this.#suppressNextFocus = true;
        this.#refocusInput();

        // Announce the applied filter
        const filterLabel = this.#prefixes[property]?.label ?? property;
        let announcement = `Filtre ${filterLabel} appliqué`;

        if (this.#liveMode) {
            this.#syncSearchModel();
            this.#invokeLiveAction('setFilter', { property, value });

            // Append active filter count for Live mode
            const activeCount = this.#countActiveFilters();
            announcement += `. ${activeCount} filtre${activeCount > 1 ? 's' : ''} actif${activeCount > 1 ? 's' : ''}`;
        } else {
            const form = this.element.closest('form') ?? this.element;
            if (form.tagName === 'FORM') {
                const hidden = form.querySelector(`input[name="criteria[${property}]"]`);
                if (value === '') {
                    hidden?.remove();
                } else if (hidden instanceof HTMLInputElement) {
                    hidden.value = value;
                } else {
                    const nextHidden = document.createElement('input');
                    nextHidden.type = 'hidden';
                    nextHidden.name = `criteria[${property}]`;
                    nextHidden.value = value;
                    form.appendChild(nextHidden);
                }
            }
            this.dispatch('selected', {
                detail: { property, value, label },
                bubbles: true,
                cancelable: false,
            });
        }

        this.#announce(announcement);
    }

    removeToken(event) {
        const el = /** @type {HTMLElement} */ (event.currentTarget);
        const property = el.dataset.filterProperty ?? '';

        if (!property) {
            return;
        }

        const filterLabel = this.#prefixes[property]?.label ?? property;

        this.dispatch('token-remove', {
            detail: { property },
            bubbles: true,
            cancelable: false,
        });

        this.#announce(`Filtre ${filterLabel} retiré`);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    #detectPrefix(value) {
        const colonIndex = value.indexOf(':');
        if (colonIndex === -1) {
            return null;
        }

        const typed = this.#normalize(value.slice(0, colonIndex).trim());
        if (typed === '') {
            return null;
        }

        for (const [key, def] of Object.entries(this.#prefixes)) {
            const labelNorm = this.#normalize(def.label ?? key);
            if (labelNorm.startsWith(typed) || this.#normalize(key).startsWith(typed)) {
                return key;
            }
        }

        return null;
    }

    #normalize(str) {
        return str
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase();
    }

    #matchByLabel(query) {
        const norm = this.#normalize(query);
        const results = [];

        for (const [key, def] of Object.entries(this.#prefixes)) {
            const labelNorm = this.#normalize(def.label ?? key);
            const keyNorm = this.#normalize(key);

            if (labelNorm.includes(norm) || keyNorm.includes(norm)) {
                results.push([key, def]);
            }
        }

        return results;
    }

    /**
     * Count currently active filters by inspecting the prefixesValue definitions.
     * @returns {number}
     */
    #countActiveFilters() {
        let count = 0;
        for (const def of Object.values(this.#prefixes)) {
            if (def.type === 'toggle') {
                if (def.active === true) {
                    count++;
                }
            } else if (def.active === true) {
                count++;
            }
        }
        return count;
    }

    // ─── Live mode: debounced q sync ──────────────────────────────────────────

    #debouncedSearch() {
        this.#cancelSearch();
        this.#searchTimer = window.setTimeout(() => {
            this.#searchTimer = null;
            this.#syncSearchModel();
        }, this.debounceValue);
    }

    #cancelSearch() {
        if (this.#searchTimer !== null) {
            window.clearTimeout(this.#searchTimer);
            this.#searchTimer = null;
        }
    }

    /**
     * Enter value mode for a given filter key.
     * @param {string} key
     */
    #enterValueMode(key) {
        this.#transition('values', key);
        const def = this.#prefixes[key];
        if (this.hasPrefixTarget && def) {
            this.prefixTarget.textContent = `${def.label ?? key} : `;
        }
    }

    #exitValueMode() {
        if (this.hasInputTarget) {
            this.inputTarget.value = '';
        }
        this.#transition('keys', null);
        this.#renderAllFilters();
        const count = this.suggestionTargets.length;
        this.#announce(`Filtres, ${count} option${count > 1 ? 's' : ''}`);
    }

    #syncSearchModel() {
        if (this.hasInputTarget) {
            this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    /**
     * Calls a LiveAction on the parent Live Component via the public
     * `getComponent` API — works for both click and keyboard selection.
     * @param {string} actionName
     * @param {Object} args
     */
    async #invokeLiveAction(actionName, args) {
        const liveRoot = this.element.closest('[data-controller~="live"]');
        if (!liveRoot) {
            return;
        }
        try {
            const { getComponent } = await import('@symfony/ux-live-component');
            const component = await getComponent(liveRoot);
            component.action(actionName, args);
        } catch (error) {
            throw new Error(`Unable to invoke Live action "${actionName}".`, { cause: error });
        }
    }

    // ─── Popover rendering ────────────────────────────────────────────────────

    #renderAllFilters() {
        this.#renderKeyList(Object.entries(this.#prefixes));
    }

    #renderSuggestions(prefix, rawInput) {
        if (!this.hasPopoverTarget) {
            return;
        }

        const def = this.#prefixes[prefix];
        if (!def) {
            return;
        }

        this.#clearDynamic();

        const afterColon = rawInput.slice(rawInput.indexOf(':') + 1).trim();
        const fragment = this.#buildValuesFragment(prefix, def, afterColon);

        this.popoverTarget.appendChild(fragment);
    }

    /**
     * "Keys" mode: render one clickable item per filter key.
     * Click/Enter on a key → transitions to "values" mode for that key.
     */
    #renderKeyList(entries) {
        if (!this.hasPopoverTarget) {
            return;
        }

        this.#clearDynamic();

        const fragment = document.createDocumentFragment();
        let lastGroup = undefined;

        for (const [key, def] of entries) {
            const group = def.group ?? null;

            if (group !== lastGroup && lastGroup !== undefined) {
                const divider = document.createElement('div');
                divider.className = 'fb-pop-divider';
                divider.setAttribute('aria-hidden', 'true');
                divider.dataset.rendered = 'dynamic';
                fragment.appendChild(divider);
            }

            lastGroup = group;

            const item = this.#makeKeyItemEl(key, def);
            item.dataset.rendered = 'dynamic';
            fragment.appendChild(item);
        }

        this.popoverTarget.appendChild(fragment);
    }

    /**
     * Build a fragment with a section header + value items for a single filter key.
     * Used in "values" mode after user selects a key.
     * @param {string} property
     * @param {{ label?: string, type?: string, choices?: Object<string, string|number>, active?: boolean }} definition
     * @param {string} [query]
     * @returns {DocumentFragment}
     */
    #buildValuesFragment(property, definition, query = '') {
        const fragment = document.createDocumentFragment();
        const options = this.#buildFilterOptions(property, definition, query);

        if (options.length === 0) {
            return fragment;
        }

        const section = this.#makeSectionEl(definition.label ?? property);
        section.dataset.rendered = 'dynamic';
        fragment.appendChild(section);

        for (const option of options) {
            const item = this.#makeSuggestionEl(property, option.value, option.label, definition.label);
            item.dataset.rendered = 'dynamic';
            fragment.appendChild(item);
        }

        return fragment;
    }

    /**
     * Create a clickable key item for "keys" mode.
     * Click → enters "values" mode for this key.
     */
    #makeKeyItemEl(property, definition) {
        const el = document.createElement('div');
        el.className = 'fb-pop-item fb-pop-item--key';
        el.setAttribute('role', 'option');
        el.setAttribute('tabindex', '-1');
        el.setAttribute('aria-selected', 'false');
        el.id = `fb-opt-${this.#gridName}-key-${property}`;
        el.dataset.filterProperty = property;
        el.dataset.filterIsKey = 'true';
        el.dataset.gridFilterBarTarget = 'suggestion';

        el.setAttribute('data-action', 'click->grid-filter-bar#selectSuggestion');

        const labelSpan = document.createElement('span');
        labelSpan.className = 'fb-pop-item__key';
        labelSpan.textContent = definition.label ?? property;

        const captionSpan = document.createElement('span');
        captionSpan.className = 'fb-pop-item__caption';
        const choiceCount = definition.choices ? Object.keys(definition.choices).length : 0;
        captionSpan.textContent = definition.caption
            ?? (definition.type === 'toggle' ? 'Oui / Non' : `${choiceCount} choix`);

        const hintSpan = document.createElement('span');
        hintSpan.className = 'fb-pop-item__hint kbd';
        hintSpan.setAttribute('aria-hidden', 'true');
        hintSpan.textContent = '→';

        el.appendChild(labelSpan);
        el.appendChild(captionSpan);
        el.appendChild(hintSpan);

        return el;
    }

    /**
     * @param {string} property
     * @param {{ label?: string, type?: string, choices?: Object<string, string|number>, active?: boolean }} definition
     * @param {string} query
     * @returns {Array<{ value: string, label: string }>}
     */
    #buildFilterOptions(property, definition, query) {
        const normalizedQuery = this.#normalize(query.trim());

        if (definition.type === 'toggle') {
            const active = definition.active === true;
            const label = active ? 'Désactiver' : 'Activer';

            if (
                normalizedQuery !== ''
                && ![property, definition.label ?? property, label].some((candidate) =>
                    this.#normalize(candidate).includes(normalizedQuery),
                )
            ) {
                return [];
            }

            return [{ value: active ? '' : '1', label }];
        }

        return Object.entries(definition.choices ?? {})
            .filter(([choiceValue, choiceLabel]) => {
                if (normalizedQuery === '') {
                    return true;
                }

                return [String(choiceValue), String(choiceLabel)].some((candidate) =>
                    this.#normalize(candidate).includes(normalizedQuery),
                );
            })
            .map(([choiceValue, choiceLabel]) => ({
                value: String(choiceValue),
                label: String(choiceLabel),
            }));
    }

    #clearDynamic() {
        if (!this.hasPopoverTarget) {
            return;
        }
        this.popoverTarget.querySelectorAll('[data-rendered="dynamic"]').forEach((el) => el.remove());
        this.#highlightIndex = -1;
        if (this.hasInputTarget) {
            this.inputTarget.removeAttribute('aria-activedescendant');
        }
    }

    #makeSectionEl(label) {
        const el = document.createElement('div');
        el.className = 'fb-pop-section';
        el.setAttribute('aria-hidden', 'true');
        el.textContent = label;
        return el;
    }

    /**
     * @param {string} property
     * @param {string} value
     * @param {string} label
     * @param {string} [filterLabel]
     * @returns {HTMLElement}
     */
    #makeSuggestionEl(property, value, label, filterLabel) {
        const def = this.#prefixes[property];
        const el = document.createElement('div');
        el.className = 'fb-pop-item';
        el.setAttribute('role', 'option');
        el.setAttribute('tabindex', '-1');
        el.setAttribute('aria-selected', 'false');
        el.id = `fb-opt-${this.#gridName}-${property}-${encodeURIComponent(value)}`;
        el.dataset.filterProperty = property;
        el.dataset.filterValue = value;
        el.dataset.filterLabel = label;
        el.dataset.gridFilterBarTarget = 'suggestion';

        el.setAttribute('data-action', 'click->grid-filter-bar#selectSuggestion');

        const keySpan = document.createElement('span');
        keySpan.className = 'fb-pop-item__key';
        keySpan.textContent = filterLabel || property;

        const valSpan = document.createElement('span');
        valSpan.className = def?.valueMonospace ? 'fb-pop-item__val num' : 'fb-pop-item__val prose';
        valSpan.textContent = label;

        const hintSpan = document.createElement('span');
        hintSpan.className = 'fb-pop-item__hint kbd';
        hintSpan.setAttribute('aria-hidden', 'true');
        hintSpan.textContent = '↵';

        el.appendChild(keySpan);
        el.appendChild(valSpan);
        el.appendChild(hintSpan);

        if (def?.caption) {
            const captionSpan = document.createElement('span');
            captionSpan.className = 'fb-pop-item__caption';
            captionSpan.textContent = def.caption;
            el.insertBefore(captionSpan, valSpan);
        }

        return el;
    }

    // ─── Popover state ────────────────────────────────────────────────────────

    #close() {
        this.#highlightIndex = -1;
        if (this.hasInputTarget) {
            this.inputTarget.removeAttribute('aria-activedescendant');
        }
        this.#transition('closed', null);
    }

    #refocusInput() {
        if (this.hasInputTarget) {
            this.inputTarget.focus();
        }
    }

    #syncAria(isOpen) {
        if (this.hasPopoverTarget) {
            this.popoverTarget.hidden = !isOpen;
        }
        if (this.hasInputTarget) {
            this.inputTarget.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    #setupOutsideListener() {
        this.#teardownOutsideListener();
        this.#outsideHandler = createOutsideClickHandler(this.element, () => this.#close());
    }

    #teardownOutsideListener() {
        if (this.#outsideHandler !== null) {
            this.#outsideHandler.teardown();
            this.#outsideHandler = null;
        }
    }

    // ─── Condensed mode +N ───────────────────────────────────────────────────

    toggleOverflow(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!this.hasBarTarget) {
            return;
        }

        const expanded = this.barTarget.classList.toggle('fb-bar--expanded');
        const moreBtn = this.barTarget.querySelector('.fb-token--more');

        if (moreBtn) {
            const hiddenCount = this.barTarget.querySelectorAll('.fb-token--overflow').length;
            moreBtn.textContent = expanded ? `−${hiddenCount}` : `+${hiddenCount}`;
            moreBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }

        this.#announce(expanded ? 'Tous les filtres affichés' : 'Filtres condensés');
    }

    // ─── DateRange token interactions ─────────────────────────────────────────

    focusDateInput(event) {
        const inputId = event.currentTarget?.dataset?.dateInputId;
        if (inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.focus();
            }
        }
    }

    clearDateRange(event) {
        event.preventDefault();
        event.stopPropagation();

        const property = event.currentTarget?.dataset?.dateProperty;
        if (!property) {
            return;
        }

        const fromInput = this.element.querySelector(
            `[data-model="${property}From"], input[name="criteria[${property}_from]"]`,
        );
        const toInput = this.element.querySelector(
            `[data-model="${property}To"], input[name="criteria[${property}_to]"]`,
        );

        if (fromInput) { fromInput.value = ''; }
        if (toInput) { toInput.value = ''; }

        if (this.#liveMode) {
            if (fromInput) { fromInput.dispatchEvent(new Event('change', { bubbles: true })); }
            if (toInput) { toInput.dispatchEvent(new Event('change', { bubbles: true })); }
        } else {
            const form = this.element.closest('form') ?? this.element.querySelector('form');
            if (form?.requestSubmit) {
                form.requestSubmit();
            }
        }

        this.#announce(`Filtre période retiré`);
    }

    // ─── Global keyboard shortcut (/ to focus) ───────────────────────────────

    /**
     * @param {KeyboardEvent} event
     */
    #handleGlobalKey(event) {
        if (event.key !== '/') {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.altKey) {
            return;
        }

        const tag = /** @type {HTMLElement} */ (event.target)?.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
            return;
        }
        if (/** @type {HTMLElement} */ (event.target)?.isContentEditable) {
            return;
        }

        event.preventDefault();

        if (this.hasInputTarget) {
            this.inputTarget.focus();
        }
    }
}
