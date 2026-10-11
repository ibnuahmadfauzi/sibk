const baseActionSelector = 'button, a[href], summary, [role="button"], [role="switch"], .form-switch label[for], input[type="button"], input[type="submit"], input[type="reset"]';
const bootstrapActionSelector = '[data-bs-toggle="tab"], [data-bs-toggle="pill"], [data-bs-toggle="dropdown"]';

export const actionSelector = `:is(${baseActionSelector}):not(:disabled, ${bootstrapActionSelector})`;
export const nativeActionSelector = `${bootstrapActionSelector}, button:disabled, [role="switch"]:disabled, input[type="button"]:disabled, input[type="submit"]:disabled, input[type="reset"]:disabled`;

const resolveActionTitle = (element, includeTitle) => {
    if (element.closest('[data-action-tooltip="off"]')) return '';

    const title = element.getAttribute('data-action-tooltip')
        || element.getAttribute('data-bs-title')
        || (includeTitle && element.getAttribute('title'))
        || element.getAttribute('aria-label')
        || element.getAttribute('data-bs-original-title')
        || element.labels?.[0]?.textContent
        || (element.tagName === 'INPUT' ? element.value : element.innerText);

    return title?.trim().replace(/\s+/g, ' ') || '';
};

export const actionTitle = (element) => resolveActionTitle(element, true);

export const syncNativeActionTitle = (element, generatedTitles) => {
    const current = element.getAttribute('title');
    if (current && current !== generatedTitles.get(element)) return;

    const title = resolveActionTitle(element, false);
    if (!title) {
        if (generatedTitles.has(element)) element.removeAttribute('title');
        generatedTitles.delete(element);
        return;
    }

    element.setAttribute('title', title);
    generatedTitles.set(element, title);
};

export const actionTooltipConfig = (container) => ({
    selector: actionSelector,
    title: actionTitle,
    html: false,
    trigger: 'hover focus',
    container,
    placement: 'auto',
});

export const detachedActions = (node) => {
    if (node.nodeType !== 1) return [];

    const actions = node.matches(baseActionSelector)
        ? [node, ...node.querySelectorAll(baseActionSelector)]
        : node.querySelectorAll(baseActionSelector);

    return [...actions].filter((action) => !action.isConnected);
};
