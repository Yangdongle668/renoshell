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

	/* ---------------- mobile drawer ---------------- */
	var drawer = document.querySelector('.rb-drawer');
	var burger = document.querySelector('.rb-navbar .elementor-menu-toggle, .rb-navbar [data-rb-burger]');
	if (drawer && burger) {
		drawer.hidden = true;
		var toggle = function () {
			var open = !drawer.classList.contains('is-open');
			drawer.classList.toggle('is-open', open);
			drawer.hidden = !open;
			document.body.classList.toggle('is-drawer-open', open);
			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
		};
		burger.addEventListener('click', function (e) {
			e.preventDefault();
			toggle();
		});
		// Close drawer when a link inside it is clicked.
		drawer.addEventListener('click', function (e) {
			if (e.target.closest('a')) {
				drawer.classList.remove('is-open');
				drawer.hidden = true;
				document.body.classList.remove('is-drawer-open');
			}
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && drawer.classList.contains('is-open')) toggle();
		});
	}
})();
