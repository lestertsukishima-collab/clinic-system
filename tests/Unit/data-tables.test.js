import assert from 'node:assert/strict';
import { test } from 'node:test';
import { setupDataTables } from '../../resources/js/data-tables.js';

function createTable(url, fetchResponse = async () => ({ ok: true, json: async () => ({
    draw: 1, recordsTotal: 20, recordsFiltered: 12, data: ['<tr><td>Escaped row</td></tr>'],
}) }), dataset = {}) {
    const fallbacks = [{ hidden: false }];
    const messages = [];
    const requests = [];
    const tableWrapper = { scrollLeft: 0, style: {}, getBoundingClientRect: () => ({ height: 760 }) };
    const card = { querySelectorAll: () => fallbacks, prepend: message => messages.push(message) };
    const table = {
        innerHTML: 'Original table rows',
        dataset: { pageLength: '10', ...dataset },
        tHead: { rows: [{ cells: Array(4) }] },
        closest: selector => selector === '.clinic-table-wrap' ? tableWrapper : card,
        querySelectorAll: () => [],
    };
    const browser = {
        location: new URL(url),
        scrollX: 0,
        scrollY: 0,
        scrollTo({ left, top }) { this.scrollX = left; this.scrollY = top; },
        history: {
            state: { preserved: true },
            replaceState(state, title, nextUrl) { this.state = state; browser.location = new URL(nextUrl); },
        },
        AbortController,
        Event,
        DOMParser: class {
            parseFromString() {
                return { querySelector: () => ({ cells: [{ innerHTML: 'Escaped row' }] }) };
            }
        },
        fetch: async (requestUrl, options) => {
            requests.push({ url: requestUrl, options });
            return fetchResponse(requestUrl, options);
        },
    };
    const page = {
        querySelectorAll: () => [table],
        createElement: () => ({ setAttribute() {} }),
        dispatchEvent() {},
    };
    let options;
    let destroyed = false;
    class Table {
        constructor(element, settings) { options = settings; }
        destroy() { destroyed = true; table.innerHTML = 'Cleared by DataTables'; }
    }
    setupDataTables(Table, browser, page);
    const request = (overrides = {}, callback = () => {}) => options.ajax({
        draw: 1, start: 0, length: 10, search: { value: '' }, order: [], ...overrides,
    }, callback);

    return { browser, table, tableWrapper, options, requests, fallbacks, messages, request, isDestroyed: () => destroyed };
}

test('DataTables pagination starts at the top and clears reserved space on a shorter page', async () => {
    const fixture = createTable('https://clinic-system.test/appointments');
    await fixture.request();
    fixture.browser.scrollY = 900;
    fixture.tableWrapper.scrollLeft = 180;
    fixture.tableWrapper.style.minHeight = '760px';

    await fixture.request({ draw: 2, start: 10 }, () => {
        fixture.tableWrapper.scrollLeft = 0;
    });

    assert.equal(fixture.browser.scrollY, 0);
    assert.equal(fixture.tableWrapper.scrollLeft, 180);
    assert.equal(fixture.tableWrapper.style.minHeight, '');

    fixture.browser.scrollY = 400;
    await fixture.request({ draw: 3, start: 0 });

    assert.equal(fixture.browser.scrollY, 0);
});

test('changing a DataTables search clears old reserved height without resetting scroll', async () => {
    const fixture = createTable('https://clinic-system.test/appointments');
    await fixture.request();
    await fixture.request({ draw: 2, start: 10 });
    fixture.browser.scrollY = 200;
    fixture.tableWrapper.style.minHeight = '760px';

    await fixture.request({ draw: 3, search: { value: 'Different results' } });

    assert.equal(fixture.tableWrapper.style.minHeight, '');
    assert.equal(fixture.browser.scrollY, 200);
});

test('table requests preserve appointment filters and send global search and ordering to the server', async () => {
    const fixture = createTable('https://clinic-system.test/appointments?status=pending&date=2026-10-11&patient_id=7&page=3');
    let result;

    await fixture.request({ start: 20, search: { value: 'Consultation' }, order: [{ column: 1, dir: 'asc' }] }, data => { result = data; });

    const { url, options } = fixture.requests[0];
    assert.equal(url.searchParams.get('status'), 'pending');
    assert.equal(url.searchParams.get('date'), '2026-10-11');
    assert.equal(url.searchParams.get('patient_id'), '7');
    assert.equal(url.searchParams.get('table_search'), 'Consultation');
    assert.equal(url.searchParams.get('start'), '20');
    assert.equal(url.searchParams.get('order[0][column]'), '1');
    assert.equal(options.credentials, 'same-origin');
    assert.equal(options.cache, 'no-store');
    assert.deepEqual(result.data, [['Escaped row']]);
    assert.equal(result.recordsFiltered, 12);
    assert.equal(fixture.browser.location.searchParams.get('table_start'), '20');
    assert.equal(fixture.browser.location.searchParams.get('page'), null);
    assert.deepEqual(fixture.browser.history.state, { preserved: true });
});

test('returning to a list restores its search, page size, position, and ordering from the URL', () => {
    const fixture = createTable('https://clinic-system.test/services?table_search=Care&table_start=30&table_length=15&table_sort=1&table_direction=desc');

    assert.equal(fixture.options.search.search, 'Care');
    assert.equal(fixture.options.displayStart, 30);
    assert.equal(fixture.options.pageLength, 15);
    assert.deepEqual(fixture.options.order, [[1, 'desc']]);
});

test('clearing a patient search removes the old GET filter from data requests and the return URL', async () => {
    const fixture = createTable('https://clinic-system.test/patients?search=Alice', undefined, { tableSearch: 'Alice' });
    assert.equal(fixture.options.search.search, 'Alice');

    await fixture.request();

    assert.equal(fixture.requests[0].url.searchParams.get('search'), null);
    assert.equal(fixture.requests[0].url.searchParams.get('table_search'), '');
    assert.equal(fixture.browser.location.searchParams.get('search'), null);
});

test('failed data requests restore the normal list and its pagination', async () => {
    const fixture = createTable('https://clinic-system.test/services', async () => ({ ok: false }));

    await fixture.request();

    assert.equal(fixture.isDestroyed(), true);
    assert.equal(fixture.table.innerHTML, 'Original table rows');
    assert.equal(fixture.fallbacks[0].hidden, false);
    assert.match(fixture.messages[0].textContent, /You can still use the list below/);
});

test('a slower previous search cannot replace the latest results', async () => {
    const responses = [];
    const fixture = createTable('https://clinic-system.test/services', () => new Promise(resolve => responses.push(resolve)));
    const results = [];
    const first = fixture.request({ draw: 1 }, data => results.push(data.draw));
    const second = fixture.request({ draw: 2 }, data => results.push(data.draw));

    responses[1]({ ok: true, json: async () => ({ draw: 2, data: [] }) });
    await second;
    responses[0]({ ok: true, json: async () => ({ draw: 1, data: [] }) });
    await first;

    assert.equal(fixture.requests[0].options.signal.aborted, true);
    assert.deepEqual(results, [2]);
});
