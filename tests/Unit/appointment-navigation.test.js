import assert from 'node:assert/strict';
import { test } from 'node:test';
import { setupAppointmentNavigation } from '../../resources/js/appointment-navigation.js';

function createPage(url, storage = new Map(), state = null, hasBackLink = false) {
    const windowEvents = new Map();
    const pageEvents = new Map();
    const backEvents = new Map();
    const table = { scrollLeft: 0 };
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
        querySelectorAll: () => [table],
        addEventListener: (name, handler) => pageEvents.set(name, handler),
    };
    const click = (href, overrides = {}) => pageEvents.get('click')({
        button: 0,
        target: { closest: () => ({ href }) },
        ...overrides,
    });

    return { browser, page, table, backLink, windowEvents, backEvents, click };
}

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
