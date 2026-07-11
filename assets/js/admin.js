(function () {
	'use strict';

	function setProperty(previews, name, value) {
		previews.forEach(function (preview) {
			preview.style.setProperty(name, value);
		});
	}

	function removeProperty(previews, name) {
		previews.forEach(function (preview) {
			preview.style.removeProperty(name);
		});
	}

	function updateLibrary(previews, library) {
		previews.forEach(function (preview) {
			preview.classList.toggle('ko-ci-icon-library-dexsvg', library === 'dexsvg');
			preview.classList.toggle('ko-ci-icon-library-media', library === 'media');
			preview.classList.toggle('ko-ci-icon-library-etmodules', library === 'etmodules');
			preview.classList.toggle('ko-ci-icon-library-fontawesome', library === 'fontawesome');
		});
	}

	function cleanEtmodulesGlyph(value) {
		return String(value || '').toLowerCase().replace(/[^a-f0-9]/g, '').slice(0, 6) || 'e015';
	}

	function cssUrl(value) {
		var url = String(value || '').replace(/\\/g, '\\\\').replace(/"/g, '%22');

		return url ? 'url("' + url + '")' : 'none';
	}

	function updatePanels(panels, library) {
		panels.forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-ko-icon-panel') !== library;
		});
	}

	function setMediaPreview(preview, url) {
		if (!preview) {
			return;
		}

		preview.innerHTML = '';

		if (!url) {
			return;
		}

		var image = document.createElement('img');
		image.src = url;
		image.alt = '';
		preview.appendChild(image);
	}

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.querySelector('[data-ko-cart-icon-admin-form]');
		var previews = Array.prototype.slice.call(document.querySelectorAll('.ko-ci-admin-preview'));

		if (!form || previews.length === 0) {
			return;
		}

		var library = form.querySelector('[data-ko-preview-library]');
		var panels = Array.prototype.slice.call(form.querySelectorAll('[data-ko-icon-panel]'));
		var etmodulesIcon = form.querySelector('[data-ko-preview-etmodules-icon]');
		var mediaId = form.querySelector('[data-ko-preview-media-id]');
		var mediaUrl = form.querySelector('[data-ko-preview-media-url]');
		var mediaPreview = form.querySelector('[data-ko-media-preview]');
		var selectMedia = form.querySelector('[data-ko-select-media]');
		var removeMedia = form.querySelector('[data-ko-remove-media]');
		var size = form.querySelector('[data-ko-preview-size]');
		var emptyColor = form.querySelector('[data-ko-preview-empty-color]');
		var activeColor = form.querySelector('[data-ko-preview-active-color]');
		var textColor = form.querySelector('[data-ko-preview-text-color]');
		var badgeBackgroundColor = form.querySelector('[data-ko-preview-badge-background-color]');
		var badgeBackgroundTransparent = form.querySelector('[data-ko-preview-badge-background-transparent]');
		var badgeBorderColor = form.querySelector('[data-ko-preview-badge-border-color]');
		var badgeBorderWidth = form.querySelector('[data-ko-preview-badge-border-width]');
		var badgeFontSize = form.querySelector('[data-ko-preview-badge-font-size]');
		var mediaFrame = null;

		function updatePreview() {
			var libraryValue = library ? library.value : 'dexsvg';

			if (library) {
				updateLibrary(previews, libraryValue);
				updatePanels(panels, libraryValue);
			}

			if (etmodulesIcon) {
				setProperty(previews, '--ko-ci-etmodules-glyph', '"\\' + cleanEtmodulesGlyph(etmodulesIcon.value) + '"');
			}

			if (mediaUrl) {
				setProperty(previews, '--ko-ci-icon-image', cssUrl(mediaUrl.value));
			}

			if (size) {
				setProperty(previews, '--ko-ci-icon-size', Math.max(12, Math.min(96, parseInt(size.value, 10) || 28)) + 'px');
			}

			if (emptyColor) {
				setProperty(previews, '--ko-ci-empty-color', emptyColor.value);
			}

			if (activeColor) {
				setProperty(previews, '--ko-ci-active-color', activeColor.value);
			}

			if (textColor) {
				setProperty(previews, '--ko-ci-text-color', textColor.value);
			}

			if (badgeBackgroundColor) {
				setProperty(previews, '--ko-ci-badge-background-color', badgeBackgroundTransparent && badgeBackgroundTransparent.checked ? 'transparent' : badgeBackgroundColor.value);
				setProperty(previews, '--ko-ci-badge-shadow', badgeBackgroundTransparent && badgeBackgroundTransparent.checked ? 'none' : '0 3px 8px rgba(15,23,42,0.18)');
			}

			if (badgeBorderColor) {
				setProperty(previews, '--ko-ci-badge-border-color', badgeBorderColor.value);
			}

			if (badgeBorderWidth) {
				setProperty(previews, '--ko-ci-badge-border-width', Math.max(0, Math.min(8, parseInt(badgeBorderWidth.value, 10) || 0)) + 'px');
			}

			if (badgeFontSize) {
				var nextFontSize = Math.max(0, Math.min(32, parseInt(badgeFontSize.value, 10) || 0));

				if (nextFontSize > 0) {
					setProperty(previews, '--ko-ci-badge-font-size', nextFontSize + 'px');
				} else {
					removeProperty(previews, '--ko-ci-badge-font-size');
				}
			}
		}

		if (selectMedia) {
			selectMedia.addEventListener('click', function () {
				if (!window.wp || !window.wp.media) {
					return;
				}

				if (!mediaFrame) {
					mediaFrame = window.wp.media({
						title: window.koCartIconAdmin && window.koCartIconAdmin.mediaTitle ? window.koCartIconAdmin.mediaTitle : 'Select Cart Icon Image',
						button: {
							text: window.koCartIconAdmin && window.koCartIconAdmin.mediaButton ? window.koCartIconAdmin.mediaButton : 'Use This Image'
						},
						multiple: false
					});

					mediaFrame.on('select', function () {
						var attachment = mediaFrame.state().get('selection').first().toJSON();
						var url = attachment.url || '';

						if (mediaId) {
							mediaId.value = attachment.id || 0;
						}

						if (mediaUrl) {
							mediaUrl.value = url;
						}

						setMediaPreview(mediaPreview, url);
						updatePreview();
					});
				}

				mediaFrame.open();
			});
		}

		if (removeMedia) {
			removeMedia.addEventListener('click', function () {
				if (mediaId) {
					mediaId.value = 0;
				}

				if (mediaUrl) {
					mediaUrl.value = '';
				}

				setMediaPreview(mediaPreview, '');
				updatePreview();
			});
		}

		form.addEventListener('input', updatePreview);
		form.addEventListener('change', updatePreview);
		updatePreview();
	});
})();
