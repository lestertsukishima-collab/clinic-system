const pageLengths = [10, 15, 25, 50, 100];

export function setupDataTables(DataTable, browser, page) {
    page.querySelectorAll('[data-clinic-table]').forEach((table) => {
        const originalMarkup = table.innerHTML;
        const card = table.closest('.clinic-record-list-card');
        const tableWrapper = table.closest('.clinic-table-wrap');
        const fallbacks = card.querySelectorAll('[data-table-fallback]');
        const location = new URL(browser.location.href);
        const requestedLength = Number(location.searchParams.get('table_length'));
        const pageLength = pageLengths.includes(requestedLength) ? requestedLength : Number(table.dataset.pageLength);
        const requestedStart = Number(location.searchParams.get('table_start') ?? (Number(location.searchParams.get('page') ?? 1) - 1) * pageLength);
        const displayStart = Number.isSafeInteger(requestedStart) && requestedStart >= 0 ? requestedStart : 0;
        const sortColumn = Number(location.searchParams.get('table_sort'));
        const sortDirection = location.searchParams.get('table_direction');
        const order = Number.isInteger(sortColumn) && sortColumn >= 0 && sortColumn < table.tHead.rows[0].cells.length - 1
            && ['asc', 'desc'].includes(sortDirection) ? [[sortColumn, sortDirection]] : [];
        let activeRequest;
        let instance;
        let previousDraw;

        table.querySelectorAll('tbody tr:has(td[colspan])').forEach(row => row.remove());

        instance = new DataTable(table, {
            serverSide: true,
            processing: true,
            autoWidth: false,
            pageLength,
            displayStart,
            lengthMenu: pageLengths,
            order,
            orderMulti: false,
            search: { search: (location.searchParams.get('table_search') ?? table.dataset.tableSearch ?? '').slice(0, 100) },
            searchDelay: 350,
            columnDefs: [{ targets: -1, orderable: false, searchable: false }],
            language: {
                search: 'Search records:',
                searchPlaceholder: 'Type to search…',
                emptyTable: 'No records available.',
                zeroRecords: 'No matching records. Try another search or clear the filters.',
            },
            ajax: async (data, callback) => {
                activeRequest?.abort();
                const controller = new browser.AbortController();
                activeRequest = controller;
                const url = new URL(browser.location.href);
                url.searchParams.delete('search');
                url.searchParams.set('draw', data.draw);
                url.searchParams.set('start', data.start);
                url.searchParams.set('length', data.length);
                url.searchParams.set('table_search', data.search.value.slice(0, 100));
                data.order.forEach((sort, index) => {
                    url.searchParams.set(`order[${index}][column]`, sort.column);
                    url.searchParams.set(`order[${index}][dir]`, sort.dir);
                });

                try {
                    const response = await browser.fetch(url, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal: controller.signal,
                    });
                    if (!response.ok) {
                        throw new Error('Unable to load records');
                    }
                    const json = await response.json();
                    if (activeRequest !== controller) {
                        return;
                    }
                    const rows = json.data.map((html) => {
                        const document = new browser.DOMParser().parseFromString(`<table><tbody>${html}</tbody></table>`, 'text/html');
                        return [...document.querySelector('tr').cells].map(cell => cell.innerHTML);
                    });
                    const sameResults = previousDraw && previousDraw.search === data.search.value && previousDraw.length === data.length;
                    const paging = sameResults && previousDraw.start !== data.start;
                    const position = {
                        x: browser.scrollX,
                        y: browser.scrollY,
                        left: tableWrapper.scrollLeft,
                        paginationTop: card.querySelector('.dt-paging')?.getBoundingClientRect().top,
                    };
                    tableWrapper.style.minHeight = '';
                    callback({ ...json, data: rows });
                    if (paging) {
                        const pagination = card.querySelector('.dt-paging');
                        const top = pagination && position.paginationTop !== undefined
                            ? browser.scrollY + pagination.getBoundingClientRect().top - position.paginationTop
                            : position.y;
                        browser.scrollTo({ left: position.x, top, behavior: 'instant' });
                        tableWrapper.scrollLeft = position.left;
                    }
                    previousDraw = { search: data.search.value, length: data.length, start: data.start };

                    const currentUrl = new URL(browser.location.href);
                    currentUrl.searchParams.delete('page');
                    if (table.dataset.tableSearch !== undefined) {
                        currentUrl.searchParams.delete('search');
                    }
                    currentUrl.searchParams.set('table_search', data.search.value.slice(0, 100));
                    currentUrl.searchParams.set('table_start', data.start);
                    currentUrl.searchParams.set('table_length', data.length);
                    currentUrl.searchParams.delete('table_sort');
                    currentUrl.searchParams.delete('table_direction');
                    if (data.order.length) {
                        currentUrl.searchParams.set('table_sort', data.order[0].column);
                        currentUrl.searchParams.set('table_direction', data.order[0].dir);
                    }
                    browser.history.replaceState(browser.history.state, '', currentUrl);
                } catch (error) {
                    if (error.name === 'AbortError' || activeRequest !== controller) {
                        return;
                    }
                    instance.destroy();
                    table.innerHTML = originalMarkup;
                    fallbacks.forEach(element => { element.hidden = false; });
                    const message = page.createElement('p');
                    message.className = 'clinic-alert clinic-alert-error';
                    message.setAttribute('role', 'alert');
                    message.textContent = 'Table tools could not load. You can still use the list below. Refresh to try again.';
                    card.prepend(message);
                }
            },
            initComplete() {
                this.api().table().container().querySelector('input[type="search"]').maxLength = 100;
                page.dispatchEvent(new browser.Event('clinic:table-ready'));
            },
        });
        fallbacks.forEach(element => { element.hidden = true; });
    });
}
