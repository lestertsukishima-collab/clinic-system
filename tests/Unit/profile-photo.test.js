import assert from 'node:assert/strict';
import { test } from 'node:test';
import { validateProfilePhoto, setupProfilePhotoUpload } from '../../resources/js/profile-photo.js';

function createForm(originalSource = null) {
    const inputEvents = new Map();
    const formEvents = new Map();
    const windowEvents = new Map();
    const revoked = [];
    const input = {
        files: [],
        validityMessage: '',
        setCustomValidity(message) { this.validityMessage = message; },
        set value(value) { if (value === '') this.files = []; },
        addEventListener: (name, callback) => inputEvents.set(name, callback),
    };
    const button = { disabled: false };
    const preview = {
        src: originalSource,
        hidden: !originalSource,
        getAttribute: () => originalSource,
        removeAttribute() { this.src = null; },
    };
    const placeholder = { hidden: Boolean(originalSource) };
    const error = { hidden: true };
    const serverError = { hidden: false };
    const status = { hidden: true };
    const elements = {
        'input[type="file"]': input,
        'button[type="submit"]': button,
        '[data-photo-preview]': preview,
        '[data-photo-placeholder]': placeholder,
        '[data-photo-error]': error,
        '[data-photo-server-error]': serverError,
        '[data-photo-status]': status,
    };
    const form = {
        querySelector: selector => elements[selector],
        addEventListener: (name, callback) => formEvents.set(name, callback),
    };
    const browser = {
        URL: { createObjectURL: () => 'blob:preview', revokeObjectURL: url => revoked.push(url) },
        addEventListener: (name, callback) => windowEvents.set(name, callback),
    };
    setupProfilePhotoUpload(browser, { querySelector: () => form });

    return {
        input, button, preview, placeholder, error, serverError, status, revoked, windowEvents,
        select(file) { input.files = file ? [file] : []; inputEvents.get('change')(); },
        submit() {
            let prevented = false;
            formEvents.get('submit')({ preventDefault() { prevented = true; } });
            return prevented;
        },
    };
}

for (const type of ['image/jpeg', 'image/png', 'image/webp']) {
    test(`accepts ${type} at exactly 2 MB without changing the image`, () => {
        const file = { type, size: 2 * 1024 * 1024 };

        assert.equal(validateProfilePhoto(file), file);
    });
}

test('rejects an image one byte above 2 MB', () => {
    assert.throws(() => validateProfilePhoto({ type: 'image/jpeg', size: 2 * 1024 * 1024 + 1 }), /must not be larger than 2 MB/);
});

test('rejects unsupported image types', () => {
    assert.throws(() => validateProfilePhoto({ type: 'image/svg+xml', size: 100 }), /Choose a JPG, PNG, or WebP image/);
});

test('previews a valid selection and permits submission', () => {
    const form = createForm();

    form.select({ type: 'image/png', size: 1000 });

    assert.equal(form.preview.src, 'blob:preview');
    assert.equal(form.preview.hidden, false);
    assert.equal(form.placeholder.hidden, true);
    assert.equal(form.button.disabled, false);
    assert.equal(form.status.textContent, 'Photo ready to upload.');
    assert.equal(form.submit(), false);
});

test('an oversized selection shows a clear error and keeps the saved preview', () => {
    const form = createForm('/storage/profiles/current.jpg');

    form.select({ type: 'image/jpeg', size: 3 * 1024 * 1024 });

    assert.equal(form.error.hidden, false);
    assert.equal(form.error.textContent, 'The photo must not be larger than 2 MB.');
    assert.equal(form.preview.src, '/storage/profiles/current.jpg');
    assert.equal(form.input.files.length, 0);
    assert.equal(form.button.disabled, true);
    assert.equal(form.submit(), true);
});

test('an invalid file is blocked even if the selection handler was bypassed', () => {
    const form = createForm();
    form.input.files = [{ type: 'image/jpeg', size: 3 * 1024 * 1024 }];

    assert.equal(form.submit(), true);
    assert.equal(form.error.textContent, 'The photo must not be larger than 2 MB.');
});

test('choosing a valid image after an error clears validation and enables upload', () => {
    const form = createForm();
    form.select({ type: 'image/gif', size: 100 });

    form.select({ type: 'image/webp', size: 100 });

    assert.equal(form.input.validityMessage, '');
    assert.equal(form.error.hidden, true);
    assert.equal(form.serverError.hidden, true);
    assert.equal(form.button.disabled, false);
    assert.equal(form.submit(), false);
});

test('clearing a selection restores the saved image and releases the preview', () => {
    const form = createForm('/storage/profiles/current.jpg');
    form.select({ type: 'image/png', size: 100 });

    form.select(null);

    assert.equal(form.preview.src, '/storage/profiles/current.jpg');
    assert.deepEqual(form.revoked, ['blob:preview']);
    assert.equal(form.button.disabled, true);
});

test('the browser back cache keeps its preview URL alive', () => {
    const form = createForm();
    form.select({ type: 'image/png', size: 100 });

    form.windowEvents.get('pagehide')({ persisted: true });

    assert.deepEqual(form.revoked, []);
    form.windowEvents.get('pagehide')({ persisted: false });
    assert.deepEqual(form.revoked, ['blob:preview']);
});

test('does not initialize upload controls on unrelated pages', () => {
    setupProfilePhotoUpload({}, { querySelector: () => null });
});
