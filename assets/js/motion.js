/*!
 * Renobattery — Motion
 * - Reveal elements with [data-rb-reveal] on scroll into view
 * - Flip body.is-loaded once content is ready (for fade-in)
 * - Respects prefers-reduced-motion (CSS also enforces this)
 */
(function () {
	'use strict';

	var reduceMotion = window.matchMedia &&
		window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// Body fade-in on load (CSS handles the transition).
	function markLoaded() {
		document.body.classList.add('is-loaded');
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			requestAnimationFrame(markLoaded);
		});
	} else {
		requestAnimationFrame(markLoaded);
	}

	// Scroll-reveal.
	var targets = document.querySelectorAll('[data-rb-reveal]');
	if (!targets.length) return;

	if (reduceMotion || !('IntersectionObserver' in window)) {
		// Graceful fallback: reveal immediately.
		Array.prototype.forEach.call(targets, function (el) {
			el.classList.add('is-revealed');
		});
		return;
	}

	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (entry.isIntersecting) {
				entry.target.classList.add('is-revealed');
				io.unobserve(entry.target);
			}
		});
	}, {
		threshold: 0.15,
		rootMargin: '0px 0px -10% 0px'
	});

	Array.prototype.forEach.call(targets, function (el) {
		io.observe(el);
	});
})();
