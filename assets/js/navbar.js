/*!
 * Renobattery — Navbar scroll behavior
 * - Toggle .is-scrolled past 40px (background fades in via CSS)
 * - Toggle .is-hidden when scrolling down past 120px, reveal on scroll up
 * - Passive listeners, rAF-throttled
 */
(function () {
	'use strict';

	var nav = document.querySelector('.rb-navbar');
	if (!nav) return;

	var SCROLL_THRESHOLD = 40;
	var HIDE_AFTER = 120;
	var lastY = window.scrollY;
	var ticking = false;

	function update() {
		var y = window.scrollY;

		if (y > SCROLL_THRESHOLD) {
			nav.classList.add('is-scrolled');
		} else {
			nav.classList.remove('is-scrolled');
		}

		if (y > HIDE_AFTER && y > lastY) {
			nav.classList.add('is-hidden');
		} else if (y < lastY) {
			nav.classList.remove('is-hidden');
		}

		lastY = y;
		ticking = false;
	}

	window.addEventListener('scroll', function () {
		if (!ticking) {
			window.requestAnimationFrame(update);
			ticking = true;
		}
	}, { passive: true });

	// Initial state (e.g. after page refresh mid-scroll).
	update();
})();
