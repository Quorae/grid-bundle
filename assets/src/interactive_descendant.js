/**
 * Returns true when the event target is an interactive descendant that
 * should handle the interaction itself (link, button, form control, or
 * element with a Stimulus data-action).
 *
 * @param {EventTarget|null} target
 * @param {EventTarget|null} [actionElement] — exclude this element from the check
 * @returns {boolean}
 */
export function isInteractiveDescendant(target, actionElement = null) {
    if (!(target instanceof Element)) {
        return false;
    }

    const interactive = target.closest('a, button, input, textarea, select, [data-action]');

    return interactive !== null && interactive !== actionElement;
}
