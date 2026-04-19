/*!
 * Renobattery — Mega menu + mobile drawer
 *
 * Mega menu: hover/focus a nav item with .has-rb-megamenu → open panel with
 *   matching trigger id (anchor href "#mm-products" → panel #mm-products).
 *
 * Mobile drawer: clicking .elementor-menu-toggle (Elementor burger) also
 *   toggles .rb-drawer.is-open + body.is-drawer-open.
 */
(function () {
	'use strict';

	/* ---------------- mega menu ---------------- */
	var panels = document.querySelectorAll('[data-rb-megamenu]');
	var wrap   = document.querySelector('.rb-megamenu-wrap');
	if (panels.length && wrap) {
		var triggers = document.querySelectorAll('.rb-navbar .has-rb-megamenu > a');
		var closeTimer;

		Array.prototype.forEach.call(triggers, function (a) {
			var href = a.getAttribute('href') || '';
			var id   = href.charAt(0) === '#' ? href.slice(1) : '';
			if (!id) return;
			var panel = document.getElementById(id);
			if (!panel) return;

			var open = function () {
				clearTimeout(closeTimer);
				Array.prototype.forEach.call(panels, function (p) {
					p.classList.toggle('is-open', p === panel);
					p.hidden = (p !== panel);
				});
				wrap.classList.add('is-open');
				a.parentNode.classList.add('is-open');
			};

			a.addEventListener('mouseenter', open);
			a.addEventListener('focus', open);
			a.addEventListener('click', function (e) {
				// Prevent # anchor jumps; allow touch devices to toggle.
				e.preventDefault();
				if (wrap.classList.contains('is-open') && panel.classList.contains('is-open')) {
					closeAll();
				} else {
					open();
				}
			});
		});

		var closeAll = function () {
			wrap.classList.remove('is-open');
			Array.prototype.forEach.call(panels, function (p) {
				p.classList.remove('is-open');
				p.hidden = true;
			});
			Array.prototype.forEach.call(
				document.querySelectorAll('.rb-navbar .has-rb-megamenu'),
				function (li) { li.classList.remove('is-open'); }
			);
		};

		wrap.addEventListener('mouseleave', function () {
			closeTimer = setTimeout(closeAll, 180);
		});
		wrap.addEventListener('mouseenter', function () { clearTimeout(closeTimer); });
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') closeAll();
		});
		document.addEventListener('click', function (e) {
			if (!e.target.closest('.rb-navbar') && !e.target.closest('.rb-megamenu-wrap')) {
				closeAll();
			}
		});
	}

	/* ---------------- mobile drawer (with focus-trap) ---------------- */
	var drawer = document.querySelector('.rb-drawer');
	var burger = document.querySelector('.rb-navbar .elementor-menu-toggle, .rb-navbar [data-rb-burger]');
	if (drawer && burger) {
		drawer.hidden = true;
		drawer.setAttribute('role', 'dialog');
		drawer.setAttribute('aria-modal', 'true');
		drawer.setAttribute('aria-hidden', 'true');
		burger.setAttribute('aria-expanded', 'false');

		var FOCUSABLE = [
			'a[href]',
			'button:not([disabled])',
			'input:not([disabled]):not([type="hidden"])',
			'select:not([disabled])',
			'textarea:not([disabled])',
			'[tabindex]:not([tabindex="-1"])'
		].join(',');
		var restoreFocusTo = null;

		function getFocusable() {
			return Array.prototype.filter.call(
				drawer.querySelectorAll(FOCUSABLE),
				function (el) {
					// Skip hidden / display:none elements.
					return el.offsetParent !== null || el === document.activeElement;
				}
			);
		}

		function openDrawer() {
			restoreFocusTo = document.activeElement;
			drawer.classList.add('is-open');
			drawer.hidden = false;
			drawer.setAttribute('aria-hidden', 'false');
			document.body.classList.add('is-drawer-open');
			burger.setAttribute('aria-expanded', 'true');
			// Defer focus so transition can start (and :focus ring doesn't flash).
			requestAnimationFrame(function () {
				var f = getFocusable();
				if (f.length) { f[0].focus(); }
			});
		}

		function closeDrawer() {
			drawer.classList.remove('is-open');
			drawer.hidden = true;
			drawer.setAttribute('aria-hidden', 'true');
			document.body.classList.remove('is-drawer-open');
			burger.setAttribute('aria-expanded', 'false');
			if (restoreFocusTo && typeof restoreFocusTo.focus === 'function') {
				try { restoreFocusTo.focus(); } catch (_e) {}
				restoreFocusTo = null;
			}
		}

		function toggle() {
			drawer.classList.contains('is-open') ? closeDrawer() : openDrawer();
		}

		burger.addEventListener('click', function (e) {
			e.preventDefault();
			toggle();
		});

		// Close on link click inside drawer.
		drawer.addEventListener('click', function (e) {
			if (e.target.closest('a')) { closeDrawer(); }
		});

		// Tab trap — cycles focus inside the drawer while open.
		drawer.addEventListener('keydown', function (e) {
			if (e.key !== 'Tab') { return; }
			var f = getFocusable();
			if (!f.length) { e.preventDefault(); return; }
			var first  = f[0];
			var last   = f[f.length - 1];
			var active = document.activeElement;
			if (e.shiftKey) {
				if (active === first || !drawer.contains(active)) {
					e.preventDefault();
					last.focus();
				}
			} else {
				if (active === last || !drawer.contains(active)) {
					e.preventDefault();
					first.focus();
				}
			}
		});

		// ESC (document-level so it works even if focus escapes somehow).
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
				e.preventDefault();
				closeDrawer();
			}
		});
	}
})();
