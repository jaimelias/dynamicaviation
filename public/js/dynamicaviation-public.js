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

	const validateAndSubmit = e => {

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

		if(typeof gtag !== 'undefined') {

			const origin = thisForm.find('#aircraft_origin').val();
			const destination = thisForm.find('#aircraft_destination').val();
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

	if(thisForm.length === 0) {
		return;
	}

	const {lang} = dyCoreArgs;

	thisForm.find('.aircraft_list').each(function(){
		
		const thisField = jQuery(this);

		thisField.autocomplete({
			hint: false
		},[{
			source: jQuery.fn.autocomplete.sources.hits(algoliaIndex, {
				hitsPerPage: 4
			}),
			displayKey: 'airport',
			templates: {
				suggestion: suggestion => {

					const localize = ['airport', 'city'];

					let {
						country_names,
						country_code,
						_highlightResult,
						iata
					} = suggestion;

					localize.forEach(k => {

						if(_highlightResult.hasOwnProperty(k))
						{
							const localizedKey = `${k}_names`;
							const loc = _highlightResult[localizedKey];

							if(loc)
							{
								if(loc.hasOwnProperty(lang))
								{
									_highlightResult[k] = loc[lang];
								}
							}
						}
					});


					const {
						airport,
						iata: _iata,
						city
					} = _highlightResult;

					const country = country_names?.hasOwnProperty(lang)
						? country_names[lang]
						: null;

					const flag_url = String(
						jsonsrc() + "img/flags/" + country_code + '.svg'
					).toLowerCase();

					const result = jQuery(
						'<div class="algolia_airport clearfix"><div class="sflag pull-left"><img width="45" height="33.75" /></div><div class="sdata"><div class="sairport"><span class="airport"></span> <strong class="iata"></strong></div><div class="slocation"><span class="city"></span>, <span class="country"></span></div></div></div>'
					);

					result.find('.sairport > .airport').html(airport?.value || '');
					
					if(iata?.length === 3)
					{
						result.find('.sairport > .iata').html(`(${_iata?.value || iata})`);
					}
					
					result.find('.slocation > .city').html(city?.value || '');
					result.find('.slocation > .country').html(country || '');
					result.find('.sflag > img').attr({
						src: flag_url
					});

					return result.html();
				}
			}
		}]).on('autocomplete:selected', function(event, suggestion) {
			
			let {
				iata,
				icao,
				airport,
				airport_names,
				city,
				city_names,
				country_code,
				_geoloc
			} = suggestion;

			if(airport_names?.hasOwnProperty(lang))
			{
				airport = airport_names[lang];
			}

			if(city_names?.hasOwnProperty(lang))
			{
				city = city_names[lang];
			}

			const locationField = thisForm.find(
				'#' + thisField.attr('id') + '_l'
			);
			
			locationField.val(
				`${airport}${iata?.length === 3 ? ` (${iata})` : ''}, ${city}, ${country_code}`
			);
			
			thisField.attr({
				'data-iata': iata,
				'data-lat': _geoloc?.lat,
				'data-lon': _geoloc?.lng
			})
			.addClass('aircraft_selected')
			.val(iata);

			/*
			 * Prevent adding the handlers again after
			 * every autocomplete selection.
			 */
			thisField
				.off('blur.aircraft')
				.on('blur.aircraft', () => {

					if(thisField.hasClass('aircraft_selected'))
					{
						thisField.val(
							thisField.attr('data-iata')
						);
					}
					else
					{
						thisField.val('');
						locationField.val('');

						thisField
							.removeClass('aircraft_selected')
							.addClass('invalid_field')
							.removeAttr('data-iata')
							.removeAttr('data-lat')
							.removeAttr('data-lon');
					}
				});

			thisField
				.off('focus.aircraft')
				.on('focus.aircraft', () => {

					thisField.val('');
					locationField.val('');

					thisField
						.removeClass('aircraft_selected invalid_field')
						.removeAttr('data-iata')
						.removeAttr('data-lat')
						.removeAttr('data-lon');
				});

			const selected = thisForm.find('.aircraft_selected').length;
					
			if(selected === 1)
			{
				thisForm
					.find('.aircraft_list')
					.not('.aircraft_selected')
					.focus();
			}
			else if(selected === 2)
			{
				thisForm
					.find('input[name="pax_num"]')
					.focus();
			}
			
		}).on('autocomplete:closed', function(){

			if(!thisField.attr('data-iata'))
			{
				thisField.val('');
			}

		});
	});

};