(function () {
	'use strict';

	var header = document.querySelector('[data-header]');
	var toggle = document.querySelector('.menu-toggle');
	var nav = document.getElementById('primary-nav');

	if (header) {
		var updateHeader = function () {
			header.classList.toggle('is-scrolled', window.scrollY > 8);
		};
		updateHeader();
		window.addEventListener('scroll', updateHeader, { passive: true });
	}

	if (toggle && nav) {
		var closeNav = function () {
			nav.classList.remove('is-open');
			toggle.setAttribute('aria-expanded', 'false');
		};

		toggle.addEventListener('click', function () {
			var isOpen = nav.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		});

		nav.querySelectorAll('a').forEach(function (link) {
			link.addEventListener('click', closeNav);
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && nav.classList.contains('is-open')) {
				closeNav();
				toggle.focus();
			}
		});

		window.addEventListener('resize', function () {
			if (window.innerWidth > 980) {
				closeNav();
			}
		});
	}

	document.querySelectorAll('[data-auto-submit]').forEach(function (field) {
		field.addEventListener('change', function () {
			if (field.form) {
				field.form.submit();
			}
		});
	});
})();
