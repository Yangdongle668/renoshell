/*!
 * Renobattery — Archive filter (pills + sort)
 *
 * Strategy: URL-driven. Selecting a pill or changing sort:
 *   1. Toggles `.is-active` locally for instant feedback
 *   2. Builds the target URL using current query vars
 *   3. Fetches the target URL and swaps the grid's innerHTML
 *   4. Pushes history state (back/forward work)
 *
 * Server must support `?product_cat=slug`, `?application_cat=slug`, `?orderby=...`
 * on the /products/ archive. WP handles tax slugs natively on tax archives;
 * on the post type archive a small `pre_get_posts` filter in inc/ handles it.
 */
(function () {
	'use strict';

	var bar = document.querySelector('[data-rb-filter]');
	if (!bar) return;

	// Prefer an explicit marker; fall back to the rb-grid--products container.
	var grid = document.querySelector('[data-rb-grid]') ||
		document.querySelector('.rb-grid--products') ||
		document.querySelector('.rb-grid--archive-grid');
	if (!grid) return;

	var mode = bar.getAttribute('data-rb-mode') || 'ajax';

	bar.addEventListener('click', function (e) {
		var btn = e.target.closest('.rb-pill');
		if (!btn || btn.tagName === 'A') return; // let link-mode anchors navigate
		e.preventDefault();

		var group = btn.closest('[data-rb-tax]');
		if (!group) return;

		Array.prototype.forEach.call(group.querySelectorAll('.rb-pill'), function (p) {
			p.classList.toggle('is-active', p === btn);
		});

		apply();
	});

	var sort = bar.querySelector('[data-rb-sort]');
	if (sort) {
		sort.addEventListener('change', apply);
	}

	function collect() {
		var params = new URLSearchParams(window.location.search);

		Array.prototype.forEach.call(bar.querySelectorAll('[data-rb-tax]'), function (group) {
			var tax = group.getAttribute('data-rb-tax');
			var active = group.querySelector('.rb-pill.is-active');
			var term = active ? active.getAttribute('data-rb-term') : '';
			if (term && term !== 'all') {
				params.set(tax, term);
			} else {
				params.delete(tax);
			}
		});

		if (sort && sort.value) {
			params.set('orderby', sort.value);
		}

		return params;
	}

	function apply() {
		var params = collect();
		var url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');

		if (mode === 'link') {
			window.location.href = url;
			return;
		}

		grid.setAttribute('aria-busy', 'true');
		grid.style.opacity = '0.4';

		fetch(url, { headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' })
			.then(function (res) { return res.text(); })
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var next = doc.querySelector('[data-rb-grid]') ||
					doc.querySelector('.rb-grid--products') ||
					doc.querySelector('.rb-grid--archive-grid');
				if (next) {
					grid.innerHTML = next.innerHTML;
				}
				history.pushState(null, '', url);
			})
			.catch(function () { /* leave grid as-is */ })
			.finally(function () {
				grid.removeAttribute('aria-busy');
				grid.style.opacity = '';
			});
	}

	// Back/forward re-sync.
	window.addEventListener('popstate', function () {
		window.location.reload();
	});
})();
