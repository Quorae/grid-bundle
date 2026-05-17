import { Controller } from '@hotwired/stimulus';
import { isInteractiveDescendant } from '../src/interactive_descendant';

/**
 * Row-link controller — makes a `<tr>` navigable via its `data-href`.
 *
 * When `frameValue` is set, loads the URL into the named Turbo Frame
 * instead of performing a full-page navigation. This enables modal/panel
 * patterns without coupling the grid bundle to any specific UI.
 */
export default class extends Controller {
    static values = {
        frame: { type: String, default: '' },
    };

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
        if (!href) return;

        if (this.frameValue) {
            const frame = document.getElementById(this.frameValue);
            if (frame) {
                frame.src = href;
                return;
            }
        }

        window.location.href = href;
    }
}
