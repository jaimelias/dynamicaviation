jQuery(() => {
	
	handleLegs();
	algolia_execute();
	aircraft_datepicker();
	aircraft_timepicker();
	validateAircraftSearch();
});

const aircraft_timepicker = () =>	{
	jQuery('#aircraft_search_form').find('input.timepicker').each(function(){
		jQuery(this).pickatime();
	});
}

const aircraft_datepicker = () =>	{
	
	const args = {
		format: 'yyyy-mm-dd',
		min: true
	};

	jQuery('#aircraft_search_form').find('input.datepicker').each(function(){
		
		const thisField = jQuery(this);

		if(thisField.attr('type') == 'text')
		{
			thisField.pickadate(args);
		}
		else if(thisField.attr('type') == 'date')
		{
			thisField.attr({'type': 'text'});
			thisField.pickadate(args);
		}	
	});
}



const validateAircraftSearch = () => {

	const thisForm = jQuery('#aircraft_search_form');

	if(thisForm.length === 0) {
		return;
	}

	const optionalReturnFields = new Set([
		'end_date',
		'end_time',
		'end_hour'
	]);

	const button = thisForm.find('#aircraft_search_button');

	const getFieldsByName = name => {
		return thisForm.find('[name]').filter((_, field) => field.name === name);
	};

	const isEmpty = value => {
		if(Array.isArray(value)) {
			return value.length === 0;
		}

		return value == null || String(value).trim() === '';
	};

	const validateAndSubmit = async (e) => {

		e.preventDefault();

		const invalidFields = new Set();

		thisForm.find('.invalid_field').removeClass('invalid_field');

		const aircraftFlightField = thisForm.find('#aircraft_flight');
		const aircraftFlightVal = Number.parseInt(aircraftFlightField.val(), 10);
		const isOneWay = aircraftFlightVal === 0;

		if(
			aircraftFlightField.length === 0 ||
			!Number.isInteger(aircraftFlightVal)
		) {
			invalidFields.add('aircraft_flight');
			aircraftFlightField.addClass('invalid_field');
		}

		const formData = thisForm.serializeArray();

		formData.forEach(({name, value}) => {

			const thisField = getFieldsByName(name);

			// Datepicker-generated shadow fields.
			if(name.endsWith('_submit')) {
				return;
			}

			// Return fields are optional for a one-way flight.
			if(isOneWay && optionalReturnFields.has(name)) {
				return;
			}

			if(isEmpty(value)) {
				invalidFields.add(name);
				thisField.addClass('invalid_field');
				return;
			}

			if(
				thisField.hasClass('aircraft_list') &&
				!thisField.hasClass('aircraft_selected')
			) {
				invalidFields.add(name);
				thisField.addClass('invalid_field');
			}
		});

		/*
		 * serializeArray() does not include unchecked checkbox/radio
		 * controls, so explicitly validate required groups.
		 */
		thisForm.find(':input[required][name]').each((_, field) => {

			const thisField = jQuery(field);
			const name = field.name;
			const type = (field.type || '').toLowerCase();

			if(name.endsWith('_submit')) {
				return;
			}

			if(isOneWay && optionalReturnFields.has(name)) {
				return;
			}

			if(type !== 'checkbox' && type !== 'radio') {
				return;
			}

			const fields = getFieldsByName(name);

			if(!fields.is(':checked')) {
				invalidFields.add(name);
				fields.addClass('invalid_field');
			}
		});

		if(invalidFields.size > 0) {

			const invalid_field = [...invalidFields];

			console.log({invalid_field});

			return;
		}

		const origin = thisForm.find('#aircraft_origin').val();
		const destination = thisForm.find('#aircraft_destination').val();

		if(typeof gtag !== 'undefined') {


			const paxNum = Number.parseInt(
				thisForm.find('#pax_num').val(),
				10
			);

			const legs = aircraftFlightVal + 1;

			gtag('event', 'flight_pax_num', {
				value: paxNum
			});

			gtag('event', 'flight_legs', {
				value: legs
			});

			gtag('event', 'flight_origin', {
				origin_name: origin
			});

			gtag('event', 'flight_destination', {
				destination_name: destination
			});

			gtag('event', 'flight_route', {
				route_name: `${origin}_${destination}`
			});
		}

		if(typeof fbq !== 'undefined') {
			fbq('track', 'Search');
		}

		const route = `${origin}-${destination}`;
		const action = atob(thisForm.attr('data-action'));
		const newAction = new URL(action, window.location.origin);

		newAction.pathname = `${newAction.pathname.replace(/\/$/, '')}/${route}`;

		thisForm.attr('data-action', btoa(newAction.href));

		createFormSubmit(thisForm);
	};

	/*
	 * Preserve support for type="button" while also catching native
	 * form submission / Enter.
	 */
	button
		.off('click.validateAircraftSearch')
		.on('click.validateAircraftSearch', validateAndSubmit);

	thisForm
		.off('submit.validateAircraftSearch')
		.on('submit.validateAircraftSearch', validateAndSubmit);
};

const handleLegs = () => {

	const aircraft_flight = jQuery('#aircraft_flight');

	if (aircraft_flight.length === 0) {
		return;
	}

	const toggleReturnFields = (val) => {
		const isRoundTrip = parseInt(val, 10) === 1;

		if (isRoundTrip) {
			jQuery('.aircraft_return').removeClass('hidden');
		} else {
			jQuery('.aircraft_return').addClass('hidden');
			jQuery('#end_date').val('');
			jQuery('#end_time').val('');
		}
	};

	// Run once on load so state matches the current value
	toggleReturnFields(aircraft_flight.val());

	aircraft_flight.on('change', function () {
		toggleReturnFields(jQuery(this).val());
	});
};


const algolia_execute = () => {

	const thisForm = jQuery('#aircraft_search_form');

	if(thisForm.length !== 1) {
		return;
	}

	const originField = thisForm.find('#aircraft_origin');
	const destinationField = thisForm.find('#aircraft_destination');
	const airportFields = thisForm.find('.aircraft_list');
	const paxField = thisForm.find('#pax_num');
	const searchButton = thisForm.find('#aircraft_search_button');

	if(
		originField.length !== 1 ||
		destinationField.length !== 1 ||
		searchButton.length !== 1
	) {
		return;
	}

	const {lang} = dyCoreArgs;

	/*
	 * Normalize an IATA value into the only format accepted
	 * by this form.
	 */
	const normalizeIata = value => String(value || '')
		.trim()
		.toUpperCase();

	const isValidIata = value => /^[A-Z]{3}$/.test(
		normalizeIata(value)
	);

	/*
	 * data-iata is the authoritative evidence that the value
	 * came from autocomplete:selected.
	 *
	 * The visible input value is never trusted.
	 */
	const getSelectedIata = field => {

		const iata = normalizeIata(
			field.attr('data-iata')
		);

		return (
			field.hasClass('aircraft_selected') &&
			isValidIata(iata)
		) ? iata : '';
	};

	const isSelectedAirport = field => (
		getSelectedIata(field) !== ''
	);

	/*
	 * Remove autocomplete selection state without necessarily
	 * clearing what the user is currently typing.
	 */
	const clearAirportSelection = field => {

		field
			.removeClass('aircraft_selected')
			.removeAttr('data-iata')
			.removeAttr('data-lat')
			.removeAttr('data-lon');
	};

	/*
	 * A rejected airport has no residual autocomplete state.
	 */
	const invalidateAirport = field => {

		clearAirportSelection(field);

		field
			.val('')
			.addClass('invalid_field');
	};

	/*
	 * Store one autocomplete selection.
	 */
	const selectAirport = (field, suggestion) => {

		const iata = normalizeIata(
			suggestion?.iata
		);

		if(!isValidIata(iata))
		{
			invalidateAirport(field);
			return false;
		}

		const lat = Number(
			suggestion?._geoloc?.lat
		);

		const lon = Number(
			suggestion?._geoloc?.lng
		);

		const attrs = {
			'data-iata': iata
		};

		if(Number.isFinite(lat))
		{
			attrs['data-lat'] = lat;
		}

		if(Number.isFinite(lon))
		{
			attrs['data-lon'] = lon;
		}

		field
			.attr(attrs)
			.removeClass('invalid_field')
			.addClass('aircraft_selected')
			.val(iata);

		return true;
	};

	const airportsAreEqual = () => {

		const origin = getSelectedIata(
			originField
		);

		const destination = getSelectedIata(
			destinationField
		);

		return (
			origin !== '' &&
			destination !== '' &&
			origin === destination
		);
	};

	/*
	 * Destination owns the duplicate-route error.
	 *
	 * This remains true even when origin was the airport most
	 * recently changed.
	 */
	const rejectDuplicateRoute = () => {

		if(!airportsAreEqual())
		{
			return false;
		}

		invalidateAirport(
			destinationField
		);

		destinationField.trigger('focus');

		return true;
	};

	/*
	 * Final route validation.
	 *
	 * Do not use field.val() here. A user can manually type
	 * PTY, MIA, etc. Only data-iata + aircraft_selected count.
	 */
	const validateAirports = () => {

		if(!isSelectedAirport(originField))
		{
			invalidateAirport(originField);
			originField.trigger('focus');

			return false;
		}

		if(!isSelectedAirport(destinationField))
		{
			invalidateAirport(destinationField);
			destinationField.trigger('focus');

			return false;
		}

		if(airportsAreEqual())
		{
			invalidateAirport(destinationField);
			destinationField.trigger('focus');

			return false;
		}

		return true;
	};

	/*
	 * Decide what should receive focus after a successful
	 * autocomplete selection.
	 */
	const focusNextField = () => {

		if(!isSelectedAirport(originField))
		{
			originField.trigger('focus');
			return;
		}

		if(!isSelectedAirport(destinationField))
		{
			destinationField.trigger('focus');
			return;
		}

		paxField.trigger('focus');
	};

	/*
	 * Read localized Algolia highlight data without mutating
	 * suggestion._highlightResult.
	 *
	 * The original code modified the hit object in place.
	 */
	const getLocalizedHighlight = (
		highlightResult,
		key
	) => {

		const localized = highlightResult?.[
			`${key}_names`
		]?.[lang];

		return (
			localized ||
			highlightResult?.[key] ||
			null
		);
	};

	const renderSuggestion = suggestion => {

		const {
			country_names,
			country_code,
			_highlightResult,
			iata
		} = suggestion;

		const airport = getLocalizedHighlight(
			_highlightResult,
			'airport'
		);

		const city = getLocalizedHighlight(
			_highlightResult,
			'city'
		);

		const highlightedIata =
			_highlightResult?.iata;

		const country =
			country_names?.[lang] ||
			country_names?.en ||
			country_code ||
			'';

		const result = jQuery(
			'<div class="algolia_airport clearfix">' +
				'<div class="sflag pull-left">' +
					'<img width="45" height="33.75" alt="" />' +
				'</div>' +
				'<div class="sdata">' +
					'<div class="sairport">' +
						'<span class="airport"></span> ' +
						'<strong class="iata"></strong>' +
					'</div>' +
					'<div class="slocation">' +
						'<span class="city"></span>, ' +
						'<span class="country"></span>' +
					'</div>' +
				'</div>' +
			'</div>'
		);

		/*
		 * Algolia highlight values intentionally contain its
		 * highlighting markup, so .html() is retained here.
		 */
		result
			.find('.sairport > .airport')
			.html(
				airport?.value || ''
			);

		if(isValidIata(iata))
		{
			result
				.find('.sairport > .iata')
				.html(
					`(${highlightedIata?.value || normalizeIata(iata)})`
				);
		}

		result
			.find('.slocation > .city')
			.html(
				city?.value || ''
			);

		/*
		 * Country is raw record data, not Algolia highlight
		 * markup, so use .text().
		 */
		result
			.find('.slocation > .country')
			.text(country);

		const normalizedCountryCode = String(
			country_code || ''
		)
			.trim()
			.toLowerCase();

		if(/^[a-z]{2}$/.test(normalizedCountryCode))
		{
			result
				.find('.sflag > img')
				.attr(
					'src',
					`${jsonsrc()}img/flags/${normalizedCountryCode}.svg`
				);
		}
		else
		{
			result
				.find('.sflag')
				.remove();
		}

		return result.html();
	};

	const autocompleteOptions = [{
		source: jQuery.fn.autocomplete.sources.hits(
			algoliaIndex,
			{
				hitsPerPage: 4
			}
		),
		displayKey: 'airport',
		templates: {
			suggestion: renderSuggestion
		}
	}];

	airportFields.each(function(){

		const thisField = jQuery(this);

		/*
		 * Editing a selected airport destroys the selection
		 * token, but does not immediately erase what the user
		 * is typing.
		 *
		 * FOCUS IS NOT ENOUGH TO INVALIDATE A SELECTION.
		 */
		thisField
			.off('input.aircraft')
			.on('input.aircraft', () => {

				if(
					thisField.hasClass('aircraft_selected') ||
					thisField.attr('data-iata')
				)
				{
					clearAirportSelection(
						thisField
					);
				}

				thisField.removeClass(
					'invalid_field'
				);
			});

		/*
		 * Blur validation is deferred one event-loop turn.
		 *
		 * This avoids clearing the input during the mouse
		 * sequence used to select an autocomplete suggestion.
		 */
		thisField
			.off('blur.aircraft')
			.on('blur.aircraft', () => {

				window.setTimeout(() => {

					if(isSelectedAirport(thisField))
					{
						thisField.val(
							getSelectedIata(
								thisField
							)
						);

						return;
					}

					invalidateAirport(
						thisField
					);

				}, 0);
			});

		/*
		 * algolia_execute() may run more than once.
		 *
		 * Do not initialize the autocomplete plugin repeatedly
		 * on the same DOM node.
		 */
		if(
			!thisField.data(
				'aircraft-autocomplete-initialized'
			)
		)
		{
			thisField.autocomplete(
				{
					hint: false
				},
				autocompleteOptions
			);

			thisField.data(
				'aircraft-autocomplete-initialized',
				true
			);
		}

		/*
		 * Our custom handlers are namespaced so they can be
		 * replaced safely if algolia_execute() runs again.
		 */
		thisField
			.off(
				'autocomplete:selected.aircraft ' +
				'autocomplete:closed.aircraft'
			)
			.on(
				'autocomplete:selected.aircraft',
				(event, suggestion) => {

					if(
						!selectAirport(
							thisField,
							suggestion
						)
					)
					{
						return;
					}

					/*
					 * Check the complete pair after EVERY
					 * selection, not just destination.
					 *
					 * This catches:
					 *
					 * PTY -> MIA -> change origin to MIA
					 */
					if(rejectDuplicateRoute())
					{
						return;
					}

					focusNextField();
				}
			)
			.on(
				'autocomplete:closed.aircraft',
				() => {

					/*
					 * Any text remaining without a valid
					 * autocomplete token is manual input.
					 */
					if(!isSelectedAirport(thisField))
					{
						invalidateAirport(
							thisField
						);
					}
				}
			);
	});

	/*
	 * The actual HTML uses:
	 *
	 * <button type="button" id="aircraft_search_button">
	 *
	 * Therefore form submit validation alone would not protect
	 * the aircraft search.
	 *
	 * Use a capturing listener so invalid airport data is
	 * rejected BEFORE another click handler attached directly
	 * to the search button can execute.
	 */
	const formElement = thisForm.get(0);

	const previousGuard = thisForm.data(
		'aircraft-search-validation-guard'
	);

	if(previousGuard)
	{
		formElement.removeEventListener(
			'click',
			previousGuard,
			true
		);
	}

	const searchValidationGuard = event => {

		const target = jQuery(
			event.target
		).closest(
			'#aircraft_search_button'
		);

		if(
			target.length === 0 ||
			!formElement.contains(target.get(0))
		)
		{
			return;
		}

		if(validateAirports())
		{
			return;
		}

		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();
	};

	formElement.addEventListener(
		'click',
		searchValidationGuard,
		true
	);

	thisForm.data(
		'aircraft-search-validation-guard',
		searchValidationGuard
	);
};