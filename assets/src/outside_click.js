/**
 * Shared outside-click handler via AbortController.
 *
 * @param {Element} element — boundary element (clicks outside trigger callback)
 * @param {() => void} onOutsideClick
 * @returns {{ signal: AbortSignal, teardown: () => void }}
 */
export function createOutsideClickHandler(element, onOutsideClick) {
    const abort = new AbortController();

    document.addEventListener(
        'click',
        (event) => {
            if (!element.contains(/** @type {Node} */ (event.target))) {
                onOutsideClick();
            }
        },
        { signal: abort.signal, capture: true },
    );

    return { signal: abort.signal, teardown: () => abort.abort() };
}
