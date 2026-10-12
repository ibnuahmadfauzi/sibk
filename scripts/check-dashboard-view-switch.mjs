import assert from 'node:assert/strict';
import { initDashboardViewSwitch } from '../resources/js/dashboard-view-switch.js';

let change;
const toggle = {
    checked: false,
    dataset: {
        teacherUrl: '/dashboard?dashboard_view=teacher&academic_year_id=11',
        coordinatorUrl: '/dashboard?dashboard_view=coordinator&academic_year_id=11',
    },
    addEventListener: (event, listener) => { if (event === 'change') change = listener; },
};
const destinations = [];
initDashboardViewSwitch({ querySelector: () => toggle }, (url) => destinations.push(url));
assert.deepEqual(destinations, []);
toggle.checked = true;
change();
toggle.checked = false;
change();
assert.deepEqual(destinations, [toggle.dataset.teacherUrl, toggle.dataset.coordinatorUrl]);
assert.doesNotThrow(() => initDashboardViewSwitch({ querySelector: () => null }));
console.log('Dashboard toggle checks passed.');
