import Tooltip from 'bootstrap/js/dist/tooltip';
import { actionSelector, actionTooltipConfig, detachedActions, nativeActionSelector, syncNativeActionTitle } from './action-tooltip-config';

export const initActionTooltips = (root = document) => {
    if (!root.body || Tooltip.getInstance(root.body)) return;

    const descriptions = new WeakMap();
    const generatedTitles = new WeakMap();
    const prepareNativeActions = (node) => {
        if (node.nodeType !== 1) return;
        if (node.matches(nativeActionSelector)) syncNativeActionTitle(node, generatedTitles);
        node.querySelectorAll(nativeActionSelector).forEach((action) => syncNativeActionTitle(action, generatedTitles));
    };

    prepareNativeActions(root.body);
    for (const eventName of ['pointerover', 'focusin']) {
        root.body.addEventListener(eventName, (event) => {
            const action = event.target.closest(nativeActionSelector);
            if (action) syncNativeActionTitle(action, generatedTitles);
        });
    }
    root.body.addEventListener('show.bs.tooltip', (event) => {
        const description = event.target.getAttribute('aria-describedby');
        if (description) descriptions.set(event.target, description);
    });
    root.body.addEventListener('shown.bs.tooltip', (event) => {
        const description = descriptions.get(event.target);
        if (description) event.target.setAttribute('aria-describedby', `${description} ${event.target.getAttribute('aria-describedby')}`);
    });
    root.body.addEventListener('hidden.bs.tooltip', (event) => {
        const description = descriptions.get(event.target);
        if (description) {
            event.target.setAttribute('aria-describedby', description);
            descriptions.delete(event.target);
        }
    });
    root.body.addEventListener('click', (event) => {
        const action = event.target.closest(actionSelector);
        if (action) Tooltip.getInstance(action)?.hide();
    });

    new Tooltip(root.body, actionTooltipConfig(root.body));

    new MutationObserver((changes) => {
        for (const change of changes) {
            for (const node of change.removedNodes) {
                for (const action of detachedActions(node)) Tooltip.getInstance(action)?.dispose();
            }
            for (const node of change.addedNodes) prepareNativeActions(node);
            if (change.target.nodeType === 1) {
                const action = change.target.closest(nativeActionSelector);
                if (action) syncNativeActionTitle(action, generatedTitles);
            }
        }
    }).observe(root.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['disabled', 'aria-label', 'data-bs-title', 'data-action-tooltip'],
    });
};
