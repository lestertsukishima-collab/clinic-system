export function setupAuthNavigation(browser) {
    browser.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            browser.location.reload();
        }
    });
}
