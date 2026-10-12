const baseActionSelector = 'button, a[href], summary, [role="button"], [role="switch"], .form-switch label[for], input[type="button"], input[type="submit"], input[type="reset"]';
const bootstrapActionSelector = '[data-bs-toggle="tab"], [data-bs-toggle="pill"], [data-bs-toggle="dropdown"]';

export const actionSelector = `:is(${baseActionSelector}):not(:disabled, ${bootstrapActionSelector})`;
export const nativeActionSelector = `${bootstrapActionSelector}, button:disabled, [role="switch"]:disabled, input[type="button"]:disabled, input[type="submit"]:disabled, input[type="reset"]:disabled`;

const cleanText = (value) => value?.trim().replace(/\s+/g, ' ') || '';

const visibleActionText = (element) => {
    if (element.tagName === 'INPUT') return cleanText(element.value);

    const visible = element.cloneNode(true);
    visible.querySelectorAll('svg, [aria-hidden="true"], [hidden], .visually-hidden, .sr-only').forEach((node) => node.remove());

    return cleanText(visible.textContent);
};

const explicitTitle = (element, includeTitle) => cleanText(
    element.getAttribute('data-action-tooltip')
    || element.getAttribute('data-bs-title')
    || (includeTitle && element.getAttribute('title'))
    || element.getAttribute('data-bs-original-title'),
);

const resolveActionTitle = (element, includeTitle) => {
    if (element.closest('[data-action-tooltip="off"]')) return '';

    const visible = visibleActionText(element);
    const explicit = explicitTitle(element, includeTitle);
    if (explicit) return explicit === visible ? '' : explicit;

    const label = cleanText(element.getAttribute('aria-label'));
    return !visible || element.classList.contains('sibk-sort-header') ? label : '';
};

export const actionTitle = (element) => resolveActionTitle(element, true);

export const removeRedundantTitle = (element) => {
    const visible = visibleActionText(element);
    for (const attribute of ['title', 'data-bs-original-title']) {
        if (element.closest('[data-action-tooltip="off"]')
            || cleanText(element.getAttribute(attribute)) === visible) element.removeAttribute(attribute);
    }
};

export const syncNativeActionTitle = (element, generatedTitles) => {
    if (element.closest('[data-action-tooltip="off"]')) {
        element.removeAttribute('title');
        generatedTitles.delete(element);
        return;
    }
    const current = element.getAttribute('title');
    const generated = generatedTitles.get(element);
    if (current && current !== generated) {
        if (current === visibleActionText(element) || element.closest('[data-action-tooltip="off"]')) element.removeAttribute('title');
        else return;
    }

    const title = element.matches(':disabled')
        ? explicitTitle(element, false)
        : resolveActionTitle(element, false);
    if (!title || title === visibleActionText(element)) {
        if (generated || current === visibleActionText(element)) element.removeAttribute('title');
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
