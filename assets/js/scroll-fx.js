/*!
 * Renobattery — Tesla-mode scroll effects
 *
 * Guards on body.rb-tesla. When the body does NOT have that class, this
 * file is a no-op (returns immediately).
 *
 * Provides:
 *   B1. --rb-scroll-y / --rb-scroll-progress CSS vars (consumed by CSS parallax)
 *   B2. Per-child --i index on [.rb-stagger-grid] containers
 *   B3. Auto-wraps [.rb-headline-reveal] contents in <span> for clip reveal
 *   B4. Mirrors body.rb-panel-snap onto <html> so scroll-snap works on root
 *
 * Respects prefers-reduced-motion.
 */
(function () {
	'use strict';

	if (!document.body.classList.contains('rb-tesla')) {
		return;
	}

	var html = document.documentElement;
	var reduceMotion = window.matchMedia &&
		window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* ---------------- B1: scroll progress CSS variable ---------------- */
	var ticking = false;
	function updateScroll() {
		var y   = window.scrollY;
		var max = Math.max(1, html.scrollHeight - window.innerHeight);
		var p   = Math.min(1, Math.max(0, y / max));
		html.style.setProperty('--rb-scroll-y', y + 'px');
		html.style.setProperty('--rb-scroll-progress', p.toFixed(4));
		ticking = false;
	}
	if (!reduceMotion) {
		window.addEventListener('scroll', function () {
			if (!ticking) {
				window.requestAnimationFrame(updateScroll);
				ticking = true;
			}
		}, { passive: true });
		updateScroll();
	}

	/* ---------------- B2: stagger grid — set --i on children ---------------- */
	Array.prototype.forEach.call(
		document.querySelectorAll('.rb-stagger-grid'),
		function (grid) {
			var kids = grid.children;
			for (var i = 0; i < kids.length; i++) {
				kids[i].style.setProperty('--i', String(i));
			}
		}
	);

	/* ---------------- B3: auto-wrap headline contents ---------------- */
	Array.prototype.forEach.call(
		document.querySelectorAll('.rb-headline-reveal'),
		function (el) {
			// Skip if already wrapped.
			if (el.firstElementChild && el.firstElementChild.tagName === 'SPAN' &&
				el.firstElementChild === el.lastElementChild) {
				return;
			}
			var span = document.createElement('span');
			span.innerHTML = el.innerHTML;
			el.textContent = '';
			el.appendChild(span);
		}
	);

	/* ---------------- Reveal via IntersectionObserver ---------------- */
	var targets = document.querySelectorAll('.rb-stagger-grid, .rb-headline-reveal');
	if (reduceMotion || !('IntersectionObserver' in window)) {
		Array.prototype.forEach.call(targets, function (el) {
			el.classList.add('is-revealed');
		});
	} else {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting) {
					e.target.classList.add('is-revealed');
					io.unobserve(e.target);
				}
			});
		}, { threshold: 0.1, rootMargin: '0px 0px -8% 0px' });
		Array.prototype.forEach.call(targets, function (el) { io.observe(el); });
	}

	/* ---------------- B4: mirror panel-snap onto <html> ---------------- */
	if (document.body.classList.contains('rb-panel-snap')) {
		html.classList.add('rb-panel-snap');
	}
})();
