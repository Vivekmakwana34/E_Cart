document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('bannerForm');
    if (!form) return;

    const titleInput = document.getElementById('bannerTitle');
    const linkInput = document.getElementById('bannerLink');
    const typeSelect = document.getElementById('bannerType');
    const fileInput = document.getElementById('bannerFile');
    const videoUrlInput = document.getElementById('bannerVideoUrl');
    const statusEl = document.getElementById('bannerFormStatus');
    const previewEl = document.getElementById('bannerPreview');

    function clearErrors() {
        const errorEls = form.querySelectorAll('.field-error');
        errorEls.forEach(function (el) {
            el.textContent = '';
        });

        const errorInputs = form.querySelectorAll('.input-error');
        errorInputs.forEach(function (el) {
            el.classList.remove('input-error');
        });

        if (statusEl) {
            statusEl.textContent = '';
            statusEl.className = 'form-status';
        }
    }

    function showError(fieldId, message) {
        const errorEl = form.querySelector('.field-error[data-error-for="' + fieldId + '"]');
        const input = document.getElementById(fieldId);
        if (input) {
            input.classList.add('input-error');
        }
        if (errorEl) {
            errorEl.textContent = message;
        }
    }

    function isValidUrl(value) {
        if (!value) return false;
        const pattern = /^(https?:\/\/)[^\s]+$/i;
        return pattern.test(value);
    }

    function updatePreview() {
        if (!previewEl) return;
        previewEl.innerHTML = '';

        const file = fileInput && fileInput.files && fileInput.files[0];
        const videoUrl = videoUrlInput ? videoUrlInput.value.trim() : '';
        const type = typeSelect ? typeSelect.value : 'image';

        if (file) {
            const info = document.createElement('div');
            info.className = 'preview-info';
            info.textContent = 'Selected file: ' + file.name;
            previewEl.appendChild(info);

            if (file.type && file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.className = 'preview-image';
                img.src = URL.createObjectURL(file);
                previewEl.appendChild(img);
            } else if (file.type && file.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.className = 'preview-video';
                video.controls = true;
                video.src = URL.createObjectURL(file);
                previewEl.appendChild(video);
            }
        } else if (type === 'video' && videoUrl && isValidUrl(videoUrl)) {
            const iframe = document.createElement('iframe');
            iframe.className = 'preview-video';
            iframe.src = videoUrl;
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;
            previewEl.appendChild(iframe);
        }
    }

    if (fileInput) {
        fileInput.addEventListener('change', updatePreview);
    }

    if (videoUrlInput) {
        videoUrlInput.addEventListener('input', function () {
            updatePreview();
        });
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', function () {
            updatePreview();
        });
    }

    form.addEventListener('submit', function (event) {
        clearErrors();

        let hasError = false;

        const titleValue = titleInput ? titleInput.value.trim() : '';
        if (!titleValue) {
            showError('bannerTitle', 'Please enter a banner title.');
            hasError = true;
        } else if (titleValue.length < 3) {
            showError('bannerTitle', 'Title should be at least 3 characters.');
            hasError = true;
        }

        const linkValue = linkInput ? linkInput.value.trim() : '';
        if (linkValue && !isValidUrl(linkValue)) {
            showError('bannerLink', 'Please enter a valid URL starting with http:// or https://');
            hasError = true;
        }

        const typeValue = typeSelect ? typeSelect.value : '';
        if (!typeValue) {
            showError('bannerType', 'Please select a banner type.');
            hasError = true;
        }

        const file = fileInput && fileInput.files && fileInput.files[0];
        const videoUrlValue = videoUrlInput ? videoUrlInput.value.trim() : '';

        if (typeValue === 'image') {
            if (!file) {
                showError('bannerFile', 'Please upload an image for this banner.');
                hasError = true;
            } else if (!file.type || !file.type.startsWith('image/')) {
                showError('bannerFile', 'Please select a valid image file (JPG, PNG, WEBP, etc.).');
                hasError = true;
            }
        } else if (typeValue === 'video') {
            if (!file && !videoUrlValue) {
                showError('bannerFile', 'Upload a video file or provide a video URL.');
                showError('bannerVideoUrl', 'Upload a video file or provide a video URL.');
                hasError = true;
            } else {
                if (file && (!file.type || !file.type.startsWith('video/'))) {
                    showError('bannerFile', 'Please select a valid video file (MP4, etc.).');
                    hasError = true;
                }
                if (videoUrlValue && !isValidUrl(videoUrlValue)) {
                    showError('bannerVideoUrl', 'Please enter a valid video URL starting with http:// or https://');
                    hasError = true;
                }
            }
        }

        if (hasError) {
            event.preventDefault();
            if (statusEl) {
                statusEl.textContent = 'Please fix the highlighted errors.';
                statusEl.className = 'form-status error';
            }
            return;
        }

        // Demo: prevent real submit
        event.preventDefault();
        if (statusEl) {
            statusEl.textContent = 'Banner saved (demo only).';
            statusEl.className = 'form-status success';
        }

        form.reset();
        if (previewEl) {
            previewEl.innerHTML = '';
        }
    });
});
