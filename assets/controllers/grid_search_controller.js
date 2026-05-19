import { Controller } from '@hotwired/stimulus';

/**
 * Grid search controller — auto-submit debouncé pour le framework Grid.
 *
 * Attaché au `<form>` qui wrap la barre de filtres/search d'un listing en
 * mode Turbo Frame. Chaque `input` / `select` marqué comme target de ce
 * controller déclenche une soumission différée du formulaire après
 * `debounceValue` ms d'inactivité — le Turbo Frame parent (`data-turbo-frame="grid-<name>"`)
 * réactualise alors le contenu sans full reload.
 *
 * Trois modes de déclenchement :
 *   - `debouncedSubmit` (recherche textuelle) : debounced, on respecte le temps de frappe.
 *   - `submit` (pills / select / toggle) : submit immédiat —
 *     l'utilisateur a fait un choix final.
 *   - `dateSubmit` (daterange inputs) : debounced (800ms), gate on validity —
 *     les inputs type=date natifs fire `change` sur chaque segment (jour/mois/année),
 *     un submit immédiat interrompt la saisie et tronque l'année.
 *
 * Préservation du focus : quand Turbo remplace le frame après submit, les
 * inputs sont re-créés. On mémorise `sessionStorage` + id de l'input
 * focalisé avant submit et on restaure au `inputTargetConnected` du nouveau
 * DOM — sinon l'utilisateur perd la frappe en cours.
 *
 * Values :
 *   - `debounce` (Number, default 300) : délai en ms avant submit sur frappe.
 *
 * Targets :
 *   - `input` : champ de recherche texte (focus restoration on frame swap).
 */
const FOCUS_STORAGE_KEY = 'grid-search:focus';

export default class extends Controller {
    static targets = ['input'];
    static values = { debounce: { type: Number, default: 300 } };

    debouncedSubmit(event) {
        this.#cancel();
        if (event?.currentTarget?.id) {
            this.#rememberFocus(event.currentTarget);
        }
        this.#timer = window.setTimeout(() => this.#requestSubmit(), this.debounceValue);
    }

    submit() {
        this.#cancel();
        this.#requestSubmit();
    }

    dateSubmit(event) {
        this.#cancel();
        const input = event.currentTarget;

        if (input instanceof HTMLInputElement && input.value !== '' && !input.validity.valid) {
            return;
        }

        if (input?.id) {
            this.#rememberFocus(input);
        }
        this.#timer = window.setTimeout(() => this.#requestSubmit(), 800);
    }

    inputTargetConnected(input) {
        this.#restoreFocus(input);
    }

    disconnect() {
        this.#cancel();
    }

    #requestSubmit() {
        const form = this.element.tagName === 'FORM' ? this.element : this.element.closest('form');

        if (!form) {
            return;
        }

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    #cancel() {
        if (this.#timer !== null) {
            window.clearTimeout(this.#timer);
            this.#timer = null;
        }
    }

    #rememberFocus(input) {
        try {
            window.sessionStorage.setItem(FOCUS_STORAGE_KEY, JSON.stringify({
                id: input.id,
                cursor: input.selectionEnd ?? input.value.length,
            }));
        } catch (_) {
            // sessionStorage can be blocked — no-op, graceful degradation.
        }
    }

    #restoreFocus(input) {
        let snapshot = null;
        try {
            const raw = window.sessionStorage.getItem(FOCUS_STORAGE_KEY);
            snapshot = raw ? JSON.parse(raw) : null;
        } catch (_) {
            snapshot = null;
        }
        if (!snapshot || snapshot.id !== input.id) {
            return;
        }
        try {
            window.sessionStorage.removeItem(FOCUS_STORAGE_KEY);
        } catch (_) {
            // ignore
        }
        // Defer to next microtask : Turbo may still be finalising the frame
        // swap, focus applied too early gets eaten.
        window.requestAnimationFrame(() => {
            if (!input.isConnected) {
                return;
            }
            input.focus();
            const end = typeof snapshot.cursor === 'number' ? snapshot.cursor : input.value.length;
            try {
                input.setSelectionRange(end, end);
            } catch (_) {
                // Some input types (type=date) don't support selectionRange.
            }
        });
    }

    #timer = null;
}
