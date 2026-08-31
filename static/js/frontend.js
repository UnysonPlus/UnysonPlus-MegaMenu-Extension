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

		// Config bridge (see helpers.php → filter 'fw:ext:megamenu:frontend-config').
		var CFG = window._fw_mega_menu || {};
		var openOn = CFG.openOn === 'click' ? 'click' : 'hover';
		var i18n = CFG.i18n || {};
		// The host theme's off-canvas drawer element id is filterable, so themes with a
		// differently-named drawer don't fall through to double toggles.
		var drawerId = CFG.drawerId || 'primary-navigation-drawer';
		// Hover intent (open on a deliberate hover; close after a short grace).
		var hoverIntent = CFG.hoverIntent !== false;
		var openDelay = (typeof CFG.openDelay === 'number') ? CFG.openDelay : 100;
		var closeDelay = (typeof CFG.closeDelay === 'number') ? CFG.closeDelay : 250;

		// When the host theme provides an off-canvas nav drawer it owns mobile
		// behavior (its own submenu toggles + accordion). Detect it and DON'T add
		// the extension's own toggle button — avoids double toggles on the Unyson+
		// theme. Standalone / other themes (no drawer) get the built-in toggle.
		var themeManaged = !!document.getElementById(drawerId);

		function closeSiblings(except) {
			Array.prototype.forEach.call(parents, function (other) {
				if (other !== except) {
					other.classList.remove('is-open');
					var otherBtn = other.querySelector('.mega-menu-toggle');
					if (otherBtn) {
						otherBtn.setAttribute('aria-expanded', 'false');
					}
				}
			});
		}

		Array.prototype.forEach.call(parents, function (parent) {
			var triggerLink = parent.querySelector(':scope > a');

			// A11y: mark the trigger as owning a popup. (aria-expanded is kept in
			// sync below for standalone; the Unyson+ theme's navigation.js already
			// syncs it, so we don't double-bind there.)
			if (triggerLink) {
				triggerLink.setAttribute('aria-haspopup', 'true');
				if (!triggerLink.hasAttribute('aria-expanded')) {
					triggerLink.setAttribute('aria-expanded', 'false');
				}
			}
			var inDrawer = !!parent.closest('#' + drawerId);
			var syncExpanded = function (open) {
				if (triggerLink) { triggerLink.setAttribute('aria-expanded', open ? 'true' : 'false'); }
			};
			if (openOn === 'hover' && hoverIntent && !inDrawer && triggerLink) {
				// Hover intent: suppress the instant CSS :hover open (via .mm-hover-intent) and
				// drive .is-open with small open/close delays. Keyboard focus still opens instantly.
				parent.classList.add('mm-hover-intent');
				var mmOpenT, mmCloseT;
				parent.addEventListener('mouseenter', function () {
					clearTimeout(mmCloseT);
					mmOpenT = setTimeout(function () {
						parent.classList.add('is-open'); syncExpanded(true); closeSiblings(parent);
					}, openDelay);
				});
				parent.addEventListener('mouseleave', function () {
					clearTimeout(mmOpenT);
					mmCloseT = setTimeout(function () {
						parent.classList.remove('is-open'); syncExpanded(false);
					}, closeDelay);
				});
				parent.addEventListener('focusin', function () {
					clearTimeout(mmCloseT); parent.classList.add('is-open'); syncExpanded(true);
				});
				parent.addEventListener('focusout', function (e) {
					if (!parent.contains(e.relatedTarget)) { parent.classList.remove('is-open'); syncExpanded(false); }
				});
			} else if (!themeManaged && openOn !== 'click' && triggerLink) {
				parent.addEventListener('mouseenter', function () { syncExpanded(true); });
				parent.addEventListener('mouseleave', function () { syncExpanded(false); });
				parent.addEventListener('focusin', function () { syncExpanded(true); });
				parent.addEventListener('focusout', function (e) {
					if (!parent.contains(e.relatedTarget)) { syncExpanded(false); }
				});
			}

			// Desktop click-to-open: suppress hover (via .mm-trigger-click) and
			// toggle the panel on the trigger link. Skip inside a drawer (mobile
			// accordion there is theme-owned).
			if (openOn === 'click' && !parent.closest('#' + drawerId)) {
				parent.classList.add('mm-trigger-click');
				if (triggerLink) {
					triggerLink.addEventListener('click', function (event) {
						event.preventDefault();
						var isOpen = parent.classList.toggle('is-open');
						triggerLink.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
						closeSiblings(parent);
					});
				}
			}

			// Mobile toggle button — only when the theme isn't managing the nav.
			if (themeManaged) {
				return;
			}

			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'mega-menu-toggle';
			btn.setAttribute('aria-expanded', 'false');
			btn.setAttribute('aria-label', i18n.toggleSubmenu || 'Toggle submenu');
			btn.innerHTML = '<span aria-hidden="true"></span>';

			btn.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();

				var isOpen = parent.classList.toggle('is-open');
				btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
				closeSiblings(parent);
			});

			var link = parent.querySelector('a');
			if (link && link.parentNode) {
				link.parentNode.insertBefore(btn, link.nextSibling);
			} else {
				parent.insertBefore(btn, parent.firstChild);
			}
		});

		function closeOpenPanel(parent) {
			parent.classList.remove('is-open');
			var btn = parent.querySelector('.mega-menu-toggle');
			if (btn) { btn.setAttribute('aria-expanded', 'false'); }
			var link = parent.querySelector(':scope > a');
			if (link && link.hasAttribute('aria-haspopup')) { link.setAttribute('aria-expanded', 'false'); }
		}

		// Close any open panel when clicking outside the menu
		document.addEventListener('click', function (event) {
			Array.prototype.forEach.call(parents, function (parent) {
				if (!parent.contains(event.target)) {
					closeOpenPanel(parent);
				}
			});
		});

		// A11y: Escape closes any open panel and returns focus to its trigger.
		document.addEventListener('keydown', function (event) {
			if (event.key !== 'Escape' && event.keyCode !== 27) { return; }
			Array.prototype.forEach.call(parents, function (parent) {
				if (parent.classList.contains('is-open')) {
					closeOpenPanel(parent);
					var link = parent.querySelector(':scope > a');
					if (link && typeof link.focus === 'function') { link.focus(); }
				}
			});
		});

		// Desktop: a full-width (position:fixed) panel needs its `top` set to the trigger's
		// bottom edge so it appears right under the menu bar, for any header height/theme.
		var fullParents = Array.prototype.filter.call(parents, function (p) {
			var panel = p.querySelector('.mega-menu.mega-menu-full');
			return panel && panel.parentNode === p;
		});
		if (fullParents.length) {
			var FULL_PANEL_OFFSET = 11; // px the full-width panel rides up under the header
			var placeFull = function (p) {
				var panel = p.querySelector('.mega-menu.mega-menu-full');
				if (panel && panel.parentNode === p) {
					panel.style.top = Math.round(p.getBoundingClientRect().bottom) - FULL_PANEL_OFFSET + 'px';
				}
			};
			fullParents.forEach(function (p) {
				p.addEventListener('mouseenter', function () { placeFull(p); });
				p.addEventListener('focusin', function () { placeFull(p); });
				placeFull(p);
			});
			window.addEventListener('resize', function () { fullParents.forEach(placeFull); });
			window.addEventListener('scroll', function () { fullParents.forEach(placeFull); }, { passive: true });
		}

		// Tabbed panel layout (row → Panel Layout = Tabs): build a tab rail from the
		// column titles; each column becomes a tab panel, one visible at a time.
		var tabPanels = document.querySelectorAll('.mega-menu.mega-menu--tabs');
		Array.prototype.forEach.call(tabPanels, function (panel) {
			// Skip the drawer copy — the host theme stacks columns there; tabs are a desktop layout.
			if (panel.closest('#' + drawerId)) { return; }
			var row = panel.querySelector(':scope > .mega-menu-row');
			if (!row) { return; }
			var cols = row.querySelectorAll(':scope > .mega-menu-col');
			if (cols.length < 2) { return; }

			var rail = document.createElement('ul');
			rail.className = 'mm-tab-rail';
			rail.setAttribute('role', 'tablist');
			var tabs = [];
			var activate = function (idx) {
				Array.prototype.forEach.call(cols, function (c, i) { c.classList.toggle('mm-tab-active', i === idx); });
				tabs.forEach(function (t, i) {
					t.li.classList.toggle('is-active', i === idx);
					t.btn.setAttribute('aria-selected', i === idx ? 'true' : 'false');
					t.btn.tabIndex = i === idx ? 0 : -1;
				});
			};
			Array.prototype.forEach.call(cols, function (col, i) {
				var titleEl = col.querySelector(':scope > a');
				var label = (titleEl ? (titleEl.textContent || '') : '').trim() || ('Tab ' + (i + 1));
				var li = document.createElement('li');
				li.setAttribute('role', 'presentation');
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.setAttribute('role', 'tab');
				btn.textContent = label;
				li.appendChild(btn);
				rail.appendChild(li);
				tabs.push({ li: li, btn: btn });
				btn.addEventListener('mouseenter', function () { activate(i); });
				btn.addEventListener('focus', function () { activate(i); });
				btn.addEventListener('click', function (e) { e.preventDefault(); activate(i); });
			});
			panel.insertBefore(rail, row);
			activate(0);
		});
	});
})();
