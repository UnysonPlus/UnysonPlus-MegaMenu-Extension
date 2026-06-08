/**
 * Mega Menu — front-end mobile/touch toggle.
 *
 * Adds an accessible toggle button to each mega-menu parent so the panel can be
 * opened by tap/click on touch screens (where :hover is unreliable). On desktop
 * the button is hidden via CSS and :hover / :focus-within drive the panel.
 *
 * Part of the opt-out baseline; disabled together with the CSS via
 * the 'fw:ext:megamenu:enqueue-frontend-css' filter.
 */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		var parents = document.querySelectorAll('.menu-item-has-mega-menu');
		if (!parents.length) {
			return;
		}

		Array.prototype.forEach.call(parents, function (parent) {
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'mega-menu-toggle';
			btn.setAttribute('aria-expanded', 'false');
			btn.setAttribute('aria-label', 'Toggle submenu');
			btn.innerHTML = '<span aria-hidden="true"></span>';

			btn.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();

				var isOpen = parent.classList.toggle('is-open');
				btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

				// Close sibling panels
				Array.prototype.forEach.call(parents, function (other) {
					if (other !== parent) {
						other.classList.remove('is-open');
						var otherBtn = other.querySelector('.mega-menu-toggle');
						if (otherBtn) {
							otherBtn.setAttribute('aria-expanded', 'false');
						}
					}
				});
			});

			var link = parent.querySelector('a');
			if (link && link.parentNode) {
				link.parentNode.insertBefore(btn, link.nextSibling);
			} else {
				parent.insertBefore(btn, parent.firstChild);
			}
		});

		// Close any open panel when clicking outside the menu
		document.addEventListener('click', function (event) {
			Array.prototype.forEach.call(parents, function (parent) {
				if (!parent.contains(event.target)) {
					parent.classList.remove('is-open');
					var btn = parent.querySelector('.mega-menu-toggle');
					if (btn) {
						btn.setAttribute('aria-expanded', 'false');
					}
				}
			});
		});
	});
})();
