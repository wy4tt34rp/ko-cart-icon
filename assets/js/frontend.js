(function () {
	'use strict';

	function toCount(value) {
		var parsed = parseInt(value, 10);
		return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
	}

	function formatCount(count) {
		return count > 999 ? '999+' : String(count);
	}

	function getBaseLabel(icon) {
		return icon.getAttribute('data-ko-cart-label') || 'View cart';
	}

	function buildLabel(baseLabel, count) {
		var i18n = window.koCartIcon && window.koCartIcon.i18n ? window.koCartIcon.i18n : {};

		if (count <= 0) {
			return (i18n.cartEmpty || '%s, cart is empty').replace('%s', baseLabel);
		}

		var template = count === 1 ? (i18n.cartOneItem || '%1$s, %2$d product in cart') : (i18n.cartManyItems || '%1$s, %2$d products in cart');

		return template.replace('%1$s', baseLabel).replace('%2$d', String(count));
	}

	function updateIcon(icon, count) {
		var badge = icon.querySelector('.ko-ci-count-badge');
		var label = buildLabel(getBaseLabel(icon), count);
		var screenReader = icon.querySelector('.screen-reader-text');

		icon.setAttribute('data-ko-cart-count', String(count));
		icon.setAttribute('aria-label', label);
		icon.classList.toggle('ko-ci-has-items', count > 0);
		icon.classList.toggle('ko-ci-is-empty', count <= 0);

		if (badge) {
			badge.textContent = formatCount(count);
			badge.setAttribute('aria-hidden', 'true');
		}

		if (screenReader) {
			screenReader.textContent = label;
		}
	}

	function updateAll(count) {
		document.querySelectorAll('[data-ko-cart-icon]').forEach(function (icon) {
			updateIcon(icon, count);
		});
	}

	function getRenderedCount() {
		var icon = document.querySelector('[data-ko-cart-icon]');

		return icon ? toCount(icon.getAttribute('data-ko-cart-count')) : 0;
	}

	function fetchCount() {
		if (!window.koCartIcon || !window.koCartIcon.ajaxUrl || !window.koCartIcon.action) {
			return;
		}

		var body = new window.URLSearchParams();
		body.append('action', window.koCartIcon.action);

		window.fetch(window.koCartIcon.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: body.toString()
		}).then(function (response) {
			return response.json();
		}).then(function (payload) {
			if (payload && payload.success && payload.data) {
				updateAll(toCount(payload.data.count));
			}
		}).catch(function () {});
	}

	function bindDexCartEvents() {
		if (!window.koPartCartAddedCallback || typeof window.koPartCartAddedCallback.add !== 'function') {
			return;
		}

		window.koPartCartAddedCallback.add(function (change) {
			updateAll(getRenderedCount() + toCount(change || 1));

			window.setTimeout(function () {
				fetchCount();
			}, 60);
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		bindDexCartEvents();
		fetchCount();
	});
})();
