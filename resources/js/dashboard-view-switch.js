export const initDashboardViewSwitch = (root = document, navigate = (url) => window.location.assign(url)) => {
    const toggle = root.querySelector('[data-dashboard-view-switch]');
    if (!toggle) return;

    toggle.addEventListener('change', () => {
        navigate(toggle.checked ? toggle.dataset.teacherUrl : toggle.dataset.coordinatorUrl);
    });
};
