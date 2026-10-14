const uploadLimit = 2 * 1024 * 1024;
const supportedTypes = ['image/jpeg', 'image/png', 'image/webp'];

export function validateProfilePhoto(file) {
    if (!supportedTypes.includes(file.type)) {
        throw new Error('Choose a JPG, PNG, or WebP image.');
    }

    if (file.size > uploadLimit) {
        throw new Error('The photo must not be larger than 2 MB.');
    }

    return file;
}

export function setupProfilePhotoUpload(browser, page) {
    const form = page.querySelector('[data-profile-photo-form]');

    if (!form) {
        return;
    }

    const input = form.querySelector('input[type="file"]');
    const button = form.querySelector('button[type="submit"]');
    const preview = form.querySelector('[data-photo-preview]');
    const placeholder = form.querySelector('[data-photo-placeholder]');
    const error = form.querySelector('[data-photo-error]');
    const serverError = form.querySelector('[data-photo-server-error]');
    const status = form.querySelector('[data-photo-status]');
    const originalSource = preview.getAttribute('src');
    let previewSource = null;

    const resetPreview = () => {
        if (previewSource) {
            browser.URL.revokeObjectURL(previewSource);
            previewSource = null;
        }

        preview.hidden = !originalSource;
        if (originalSource) {
            preview.src = originalSource;
        } else {
            preview.removeAttribute('src');
        }
        if (placeholder) {
            placeholder.hidden = Boolean(originalSource);
        }
    };

    input.addEventListener('change', () => {
        const selectedFile = input.files[0];
        error.hidden = true;
        if (serverError) {
            serverError.hidden = true;
        }
        input.setCustomValidity('');
        resetPreview();
        button.disabled = !selectedFile;
        status.hidden = true;

        if (!selectedFile) {
            return;
        }

        try {
            const photo = validateProfilePhoto(selectedFile);
            previewSource = browser.URL.createObjectURL(photo);
            preview.src = previewSource;
            preview.hidden = false;
            if (placeholder) {
                placeholder.hidden = true;
            }
            status.textContent = 'Photo ready to upload.';
            status.hidden = false;
            button.disabled = false;
        } catch (failure) {
            const message = failure.message || 'The photo could not be prepared. Choose another image.';
            input.value = '';
            input.setCustomValidity(message);
            error.textContent = message;
            error.hidden = false;
            status.hidden = true;
            button.disabled = true;
        }
    });

    form.addEventListener('submit', event => {
        const photo = input.files[0];

        try {
            if (!photo) {
                throw new Error('Choose a profile photo.');
            }
            validateProfilePhoto(photo);
        } catch (failure) {
            event.preventDefault();
            error.textContent = failure.message;
            error.hidden = false;
        }
    });

    browser.addEventListener('pagehide', event => {
        if (previewSource && !event.persisted) {
            browser.URL.revokeObjectURL(previewSource);
        }
    });
}
