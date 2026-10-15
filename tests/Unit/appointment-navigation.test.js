import assert from 'node:assert/strict';
import { test } from 'node:test';
import { setupAppointmentNavigation } from '../../resources/js/appointment-navigation.js';

function createPage(url, storage = new Map(), state = null, hasBackLink = false) {
    const windowEvents = new Map();
    const pageEvents = new Map();
    const backEvents = new Map();
    const table = { scrollLeft: 0, style: {}, getBoundingClientRect: () => ({ height: 760 }) };
    const pagination = { offsetTop: 1800, getBoundingClientRect: () => ({ top: pagination.offsetTop - browser.scrollY }) };
    const backLink = {
        href: 'https://clinic-system.test/appointments',
        addEventListener: (name, handler) => backEvents.set(name, handler),
    };
    const browser = {
        location: new URL(url),
        scrollX: 0,
        scrollY: 0,
        sessionStorage: {
            getItem: key => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, value),
            removeItem: key => storage.delete(key),
        },
        history: {
            state,
            replaceState(value) { this.state = value; },
        },
        addEventListener: (name, handler) => windowEvents.set(name, handler),
        requestAnimationFrame: handler => handler(),
        scrollTo({ left, top }) { this.scrollX = left; this.scrollY = top; },
    };
    const page = {
        querySelector: () => hasBackLink ? backLink : null,
        querySelectorAll: selector => selector === '.clinic-pagination' ? [pagination] : [table],
        addEventListener: (name, handler) => pageEvents.set(name, handler),
    };
    const click = (href, overrides = {}, pagination = false) => pageEvents.get('click')({
        button: 0,
        target: { closest: selector => selector === '.clinic-pagination a[href]' && !pagination ? null : {
            href,
            closest: () => page.querySelectorAll('.clinic-pagination')[0],
        } },
        ...overrides,
    });
    const paginate = (href, overrides = {}) => click(href, overrides, true);

    return { browser, page, table, pagination, backLink, windowEvents, pageEvents, backEvents, click, paginate };
}

test('pagination Next and Previous keep the controls in place without reserving table space', () => {
    const storage = new Map();
    const firstUrl = 'https://clinic-system.test/dashboard?schedule=all&page=1';
    const secondUrl = 'https://clinic-system.test/dashboard?schedule=all&page=2';
    const first = createPage(firstUrl, storage);
    setupAppointmentNavigation(first.browser, first.page);
    first.browser.scrollY = 1240;
    first.table.scrollLeft = 180;
    first.paginate(secondUrl);
    first.windowEvents.get('pagehide')();

    const second = createPage(secondUrl, storage);
    setupAppointmentNavigation(second.browser, second.page);
    second.windowEvents.get('pageshow')();

    assert.equal(second.browser.scrollY, 1240);
    assert.equal(second.table.scrollLeft, 180);
    assert.equal(second.table.style.minHeight, undefined);

    second.browser.scrollY = 400;
    second.paginate(firstUrl);
    const returned = createPage(firstUrl, storage);
    setupAppointmentNavigation(returned.browser, returned.page);
    returned.windowEvents.get('pageshow')();

    assert.equal(returned.browser.scrollY, 400);
    assert.equal(returned.table.scrollLeft, 180);
});

test('numbered pagination stays visible when moving between full and shorter dashboard pages', () => {
    const storage = new Map();
    const secondUrl = 'https://clinic-system.test/dashboard?schedule=all&page=2';
    const lastUrl = 'https://clinic-system.test/dashboard?schedule=all&page=3';
    const second = createPage(secondUrl, storage);
    setupAppointmentNavigation(second.browser, second.page);
    second.browser.scrollY = 1240;
    second.paginate(lastUrl);

    const last = createPage(lastUrl, storage);
    last.pagination.offsetTop = 1100;
    setupAppointmentNavigation(last.browser, last.page);
    last.windowEvents.get('pageshow')();

    assert.equal(last.pagination.getBoundingClientRect().top, 560);
    assert.equal(last.browser.scrollY, 540);
    assert.equal(last.table.style.minHeight, undefined);

    last.paginate(secondUrl);
    const returned = createPage(secondUrl, storage);
    setupAppointmentNavigation(returned.browser, returned.page);
    returned.windowEvents.get('pageshow')();

    assert.equal(returned.pagination.getBoundingClientRect().top, 560);
    assert.equal(returned.browser.scrollY, 1240);
});

test('pagination falls back to the saved scroll position if the controls disappear', () => {
    const storage = new Map();
    const first = createPage('https://clinic-system.test/dashboard?page=1', storage);
    setupAppointmentNavigation(first.browser, first.page);
    first.browser.scrollY = 900;
    first.paginate('https://clinic-system.test/dashboard?page=2');
    const next = createPage('https://clinic-system.test/dashboard?page=2', storage);
    next.page.querySelectorAll = selector => selector === '.clinic-pagination' ? [] : [next.table];

    setupAppointmentNavigation(next.browser, next.page);
    next.windowEvents.get('pageshow')();

    assert.equal(next.browser.scrollY, 900);
});

test('old saved table heights do not add blank space when returning to a list', () => {
    const url = 'https://clinic-system.test/appointments?table_start=10';
    const list = createPage(url, new Map(), {
        clinicAppointmentPosition: { url, x: 0, y: 200, tables: [0], tableHeights: [760] },
    });
    setupAppointmentNavigation(list.browser, list.page);

    list.windowEvents.get('pageshow')();

    assert.equal(list.browser.scrollY, 200);
    assert.equal(list.table.style.minHeight, undefined);
});

test('browser Back restores the source page after following a pagination link', () => {
    const first = createPage('https://clinic-system.test/dashboard?schedule=all&page=1');
    setupAppointmentNavigation(first.browser, first.page);
    first.browser.scrollY = 900;
    first.paginate('https://clinic-system.test/dashboard?schedule=all&page=2');
    first.browser.scrollY = 0;

    first.windowEvents.get('pageshow')();

    assert.equal(first.browser.scrollY, 900);
});

test('opening a pagination link in a new tab does not save a pending scroll position', () => {
    const storage = new Map();
    const first = createPage('https://clinic-system.test/dashboard?page=1', storage);
    setupAppointmentNavigation(first.browser, first.page);

    first.paginate('https://clinic-system.test/dashboard?page=2', { ctrlKey: true });

    assert.equal(storage.size, 0);
    assert.equal(first.browser.history.state, null);
});

test('pagination links cannot carry a scroll position to another site or screen', () => {
    const storage = new Map();
    const first = createPage('https://clinic-system.test/dashboard?page=1', storage);
    setupAppointmentNavigation(first.browser, first.page);

    first.paginate('https://example.com/dashboard?page=2');
    first.paginate('https://clinic-system.test/profile?page=2');

    assert.equal(storage.size, 0);
});

test('returning to an appointment list restores scrolling after its table finishes loading', () => {
    const url = 'https://clinic-system.test/appointments?table_start=10';
    const list = createPage(url, new Map(), {
        clinicAppointmentPosition: { url, x: 0, y: 900, tables: [180] },
    });
    setupAppointmentNavigation(list.browser, list.page);

    list.pageEvents.get('clinic:table-ready')();

    assert.equal(list.browser.scrollY, 900);
    assert.equal(list.table.scrollLeft, 180);
});

test('the detail Back link returns to the same filtered page and restores vertical and horizontal scrolling', () => {
    const storage = new Map();
    const source = 'https://clinic-system.test/appointments?status=pending&page=2';
    const destination = 'https://clinic-system.test/appointments/8';
    const list = createPage(source, storage);
    setupAppointmentNavigation(list.browser, list.page);
    list.browser.scrollY = 1240;
    list.table.scrollLeft = 180;
    list.click(destination);
    const detail = createPage(destination, storage, null, true);
    setupAppointmentNavigation(detail.browser, detail.page);

    assert.equal(detail.backLink.href, source);
    detail.backEvents.get('click')({ button: 0 });
    const returned = createPage(detail.backLink.href, storage);
    setupAppointmentNavigation(returned.browser, returned.page);
    returned.windowEvents.get('pageshow')();

    assert.equal(returned.browser.scrollY, 1240);
    assert.equal(returned.table.scrollLeft, 180);
    assert.equal(returned.browser.location.search, '?status=pending&page=2');
});

test('browser Back restores the list position even when tab storage is blocked', () => {
    const list = createPage('https://clinic-system.test/appointments');
    list.browser.sessionStorage = {
        getItem() { throw new Error('Storage blocked'); },
        setItem() { throw new Error('Storage blocked'); },
        removeItem() { throw new Error('Storage blocked'); },
    };
    setupAppointmentNavigation(list.browser, list.page);
    list.browser.scrollY = 900;
    list.click('https://clinic-system.test/appointments/8');
    list.browser.scrollY = 0;

    list.windowEvents.get('pageshow')();

    assert.equal(list.browser.scrollY, 900);
});

test('returning from a dashboard appointment preserves the dashboard page', () => {
    const storage = new Map();
    const list = createPage('https://clinic-system.test/dashboard?schedule=all&page=2', storage);
    setupAppointmentNavigation(list.browser, list.page);
    list.browser.scrollY = 1500;
    list.click('https://clinic-system.test/appointments/8');
    const detail = createPage('https://clinic-system.test/appointments/8', storage, null, true);

    setupAppointmentNavigation(detail.browser, detail.page);

    assert.equal(detail.backLink.href, 'https://clinic-system.test/dashboard?schedule=all&page=2');
});

test('fresh navigation to a list starts at the top instead of using an earlier visit', () => {
    const storage = new Map();
    const previous = createPage('https://clinic-system.test/appointments', storage);
    setupAppointmentNavigation(previous.browser, previous.page);
    previous.browser.scrollY = 900;
    previous.click('https://clinic-system.test/appointments/8');
    const fresh = createPage('https://clinic-system.test/appointments', storage);
    setupAppointmentNavigation(fresh.browser, fresh.page);

    fresh.windowEvents.get('pageshow')();

    assert.equal(fresh.browser.scrollY, 0);
});

test('leaving a returned list saves its latest position for subsequent browser Back navigation', () => {
    const list = createPage('https://clinic-system.test/appointments');
    setupAppointmentNavigation(list.browser, list.page);
    list.browser.scrollY = 900;
    list.click('https://clinic-system.test/appointments/8');
    list.browser.scrollY = 650;
    list.windowEvents.get('pagehide')();
    list.browser.scrollY = 0;

    list.windowEvents.get('pageshow')();

    assert.equal(list.browser.scrollY, 650);
});

test('opening a View link in a new tab leaves the current navigation context alone', () => {
    const storage = new Map();
    const list = createPage('https://clinic-system.test/appointments', storage);
    setupAppointmentNavigation(list.browser, list.page);

    list.click('https://clinic-system.test/appointments/8', { ctrlKey: true });

    assert.equal(storage.size, 0);
    assert.equal(list.browser.history.state, null);
});

test('saved external return URLs cannot replace the normal Back link', () => {
    const storage = new Map([['clinic:appointment-navigation', JSON.stringify({
        to: 'https://clinic-system.test/appointments/8',
        position: { url: 'https://example.com/appointments', x: 0, y: 100, tables: [] },
    })]]);
    const detail = createPage('https://clinic-system.test/appointments/8', storage, null, true);

    setupAppointmentNavigation(detail.browser, detail.page);

    assert.equal(detail.backLink.href, 'https://clinic-system.test/appointments');
});
