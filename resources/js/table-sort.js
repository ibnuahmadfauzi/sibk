export const nextSortState = (current) => current === 'none' ? 'ascending' : current === 'ascending' ? 'descending' : 'none';

export const sortItems = (original, value, type, state) => {
    if (state === 'none') return [...original];
    const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
    return [...original].sort((left, right) => {
        const a = value(left);
        const b = value(right);
        const compared = type === 'number' ? Number(a || 0) - Number(b || 0)
            : type === 'date' ? String(a).localeCompare(String(b)) : collator.compare(String(a), String(b));
        return (state === 'descending' ? -1 : 1) * compared || original.indexOf(left) - original.indexOf(right);
    });
};

if (typeof document !== 'undefined') document.querySelectorAll('table[data-client-sort]').forEach((table) => {
    const body = table.tBodies[0];
    if (!body) return;
    let original = [];
    const headers = [...table.tHead?.rows[0]?.cells ?? []].filter((cell) => cell.dataset.sortType);
    headers.forEach((header) => {
        const button = header.querySelector('button');
        if (!button) return;
        header.setAttribute('aria-sort', 'none');
        button.addEventListener('click', () => {
            const currentRows = [...body.rows];
            original = original.filter((group) => currentRows.includes(group[0]));
            currentRows.forEach((row) => {
                if (original.some((group) => group.includes(row))) return;
                if (row.classList.contains('sibk-report-detail-row') && original.length) original.at(-1).push(row);
                else original.push([row]);
            });
            const current = header.getAttribute('aria-sort');
            const next = nextSortState(current);
            headers.forEach((cell) => {
                cell.setAttribute('aria-sort', 'none');
                cell.querySelector('button')?.classList.remove('is-active');
                const icon = cell.querySelector('.sibk-sort-header__icon');
                if (icon) icon.textContent = '↕';
            });
            header.setAttribute('aria-sort', next);
            button.classList.toggle('is-active', next !== 'none');
            header.querySelector('.sibk-sort-header__icon').textContent = next === 'ascending' ? '↑' : next === 'descending' ? '↓' : '↕';
            const index = header.cellIndex;
            const value = (group) => group[0].cells[index]?.dataset.sortValue ?? group[0].cells[index]?.textContent.trim() ?? '';
            const rows = sortItems(original, value, header.dataset.sortType, next);
            body.append(...rows.flat());
            rows.forEach((group, rowIndex) => {
                const number = group[0].querySelector('[data-row-number]');
                if (number) number.textContent = String(rowIndex + 1);
            });
        });
    });
});

if (typeof document !== 'undefined') document.querySelectorAll('table[data-client-sort-groups]').forEach((table) => {
    const header = table.tHead?.querySelector('th[data-sort-type]');
    const button = header?.querySelector('button');
    if (!button) return;
    const original = [...table.tBodies];
    button.addEventListener('click', () => {
        const next = nextSortState(header.getAttribute('aria-sort'));
        header.setAttribute('aria-sort', next);
        button.classList.toggle('is-active', next !== 'none');
        header.querySelector('.sibk-sort-header__icon').textContent = next === 'ascending' ? '↑' : next === 'descending' ? '↓' : '↕';
        sortItems(original, (group) => group.querySelector('th[scope="rowgroup"]')?.textContent.trim() ?? '', 'text', next)
            .forEach((group) => table.append(group));
    });
});
