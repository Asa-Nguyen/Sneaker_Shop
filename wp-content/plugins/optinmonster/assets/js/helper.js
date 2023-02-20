/* ==========================================================
 * helper.js
 * ==========================================================
 * Copyright 2022 Awesome Motive.
 * https://awesomemotive.com
 * ========================================================== */
window.OMAPI_Helper = window.OMAPI_Helper || {};
(function (window, document, app) {
	'use strict';

	document.addEventListener('om.Styles.positionFloating', function (event) {
		var campaign = event.detail.Campaign;
		if (
			'floating' === campaign.Types.type &&
			'top' === campaign.options.position &&
			document.getElementById('wpadminbar')
		) {
			const marginTop = window.matchMedia('(max-width: 782px)').matches ? '46px' : '32px';
			campaign.contain.style.marginTop = marginTop;
		}
	});

	document.addEventListener('om.Main.init', (event) => {
		app.trigger = event.detail._utils.events.trigger;
		app.on = event.detail._utils.helpers.on;
		app.off = event.detail._utils.helpers.off;
		app.each = event.detail._utils.helpers.each;
	});

	// Find any WPForms forms and listen for a submission to trigger a conversion.
	document.addEventListener('om.Html.append.after', (event) => {
		const campaignId = event.detail.Campaign.id;
		const forms = document.querySelectorAll(`#om-${campaignId} form`);

		app.each(forms, (i, form) => {
			const isWPForms = form.id ? form.id.includes('wpforms-form-') : false;

			if (isWPForms) {
				const cb = () => {
					// Ensure WPForms has time to add errors to the DOM.
					setTimeout(() => {
						const hasError = document.getElementsByClassName('wpforms-has-error');

						if (!hasError.length) {
							app.trigger(form, 'omWpformsSuccess');
						}
					}, 500);
				};

				app.on(form, 'submit.omWpformsConversion', cb);
			}
		});
	});

	// Remove WPForms listener on campaign close.
	document.addEventListener('om.Campaign.startClose', (event) => {
		const campaignId = event.detail.Campaign.id;
		const forms = document.querySelectorAll(`#om-${campaignId} form`);

		app.each(forms, (i, form) => {
			const isWPForms = form.id ? form.id.includes('wpforms-form-') : false;

			if (isWPForms) {
				app.off(form, 'submit.omWpformsConversion');
			}
		});
	});
})(window, document, window.OMAPI_Helper);
