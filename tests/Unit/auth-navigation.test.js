import assert from 'node:assert/strict';
import { test } from 'node:test';
import { setupAuthNavigation } from '../../resources/js/auth-navigation.js';

test('Back and Forward reload cached pages so the server checks the current session', () => {
    const events = new Map();
    let reloadCount = 0;
    const browser = {
        addEventListener: (name, handler) => events.set(name, handler),
        location: { reload: () => reloadCount++ },
    };
    setupAuthNavigation(browser);

    events.get('pageshow')({ persisted: true });

    assert.equal(reloadCount, 1);
});

test('normal page loads do not reload or interrupt navigation', () => {
    const events = new Map();
    let reloadCount = 0;
    const browser = {
        addEventListener: (name, handler) => events.set(name, handler),
        location: { reload: () => reloadCount++ },
    };
    setupAuthNavigation(browser);

    events.get('pageshow')({ persisted: false });

    assert.equal(reloadCount, 0);
});
