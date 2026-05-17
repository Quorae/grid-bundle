import { Controller } from '@hotwired/stimulus';
import { isInteractiveDescendant } from '../src/interactive_descendant';

/**
 * Row-link controller — makes a `<tr>` navigable via its `data-href`.
 *
 * Mounted on each `<tr>` that carries a `#[RowLink]` attribute.
 * Navigates on click (unless the target is an interactive descendant)
 * and on Enter/Space keypress (the row has `tabindex="0"`).
 */
export default class extends Controller {
    connect() {
        this.element.style.cursor = 'pointer';
    }

    click(event) {
        if (isInteractiveDescendant(event.target, this.element)) {
            return;
        }

        this.#navigate();
    }

    keydown(event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            this.#navigate();
        }
    }

    #navigate() {
        const href = this.element.dataset.href;
        if (href) {
            window.location.href = href;
        }
    }
}
