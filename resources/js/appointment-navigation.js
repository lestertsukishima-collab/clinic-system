export function setupAppointmentNavigation(browser, page) {
    const contextKey = 'clinic:appointment-navigation';
    const returnKey = 'clinic:appointment-return';
    const stateKey = 'clinicAppointmentPosition';

    const read = (key) => {
        try {
            return JSON.parse(browser.sessionStorage.getItem(key));
        } catch {
            return null;
        }
    };

    const write = (key, value) => {
        try {
            browser.sessionStorage.setItem(key, JSON.stringify(value));
        } catch {
            // Browser history still restores position when tab storage is unavailable.
        }
    };

    const remember = (position) => {
        browser.history.replaceState({ ...browser.history.state, [stateKey]: position }, '');
    };

    const positionNow = () => ({
        url: browser.location.href,
        x: browser.scrollX,
        y: browser.scrollY,
        tables: [...page.querySelectorAll('.clinic-table-wrap')].map(table => table.scrollLeft),
    });

    const followsInThisTab = (event, link) => !event.defaultPrevented && event.button === 0
        && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey
        && (!link.target || link.target === '_self');

    const pendingReturn = read(returnKey);
    try {
        browser.sessionStorage.removeItem(returnKey);
    } catch {
        // Navigation continues normally when tab storage is unavailable.
    }
    if (pendingReturn?.url === browser.location.href) {
        remember(pendingReturn);
    }

    const restore = () => {
        const position = browser.history.state?.[stateKey];
        if (position?.url !== browser.location.href) {
            return;
        }

        const applyPosition = () => {
            browser.scrollTo({ left: position.x, top: position.y, behavior: 'instant' });
            page.querySelectorAll('.clinic-table-wrap').forEach((table, index) => {
                table.scrollLeft = position.tables[index] ?? 0;
            });
        };

        browser.requestAnimationFrame(applyPosition);
        page.fonts?.ready.then(applyPosition);
    };

    browser.addEventListener('pageshow', restore);
    browser.addEventListener('pagehide', () => {
        if (browser.history.state?.[stateKey]) {
            remember(positionNow());
        }
    });

    const backLink = page.querySelector('[data-appointment-return]');
    const context = read(contextKey);
    if (backLink && context?.to === browser.location.href) {
        try {
            const source = new URL(context.position.url);
            if (source.origin === browser.location.origin) {
                backLink.href = source.href;
                backLink.addEventListener('click', (event) => {
                    if (followsInThisTab(event, backLink)) {
                        write(returnKey, context.position);
                    }
                });
            }
        } catch {
            // Keep the normal appointment list link if saved navigation is invalid.
        }
    }

    page.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-appointment-view]');
        if (!link || !followsInThisTab(event, link)) {
            return;
        }

        const position = positionNow();
        remember(position);
        write(contextKey, { to: link.href, position });
    });
}
