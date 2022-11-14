jQuery(() => {
	
	one_way_round_trip();
	algolia_execute();
	aircraft_datepicker();
	aircraft_timepicker();
	validateAircraftSearch();
});

const aircraft_timepicker = () =>	{
	jQuery('form.aircraft_search_form').find('input.timepicker').each(function(){
		jQuery(this).pickatime();
	});
}

const aircraft_datepicker = () =>	{
	
	const args = {
		format: 'yyyy-mm-dd',
		min: true
	};

	jQuery('form.aircraft_search_form').find('input.datepicker').each(function(){
		
		if(jQuery(this).attr('type') == 'text')
		{
			jQuery(this).pickadate(args);
		}
		else if(jQuery(this).attr('type') == 'date')
		{
			jQuery(this).attr({'type': 'text'});
			jQuery(this).pickadate(args);
		}	
	});
}



const validateAircraftSearch = () => {
	jQuery('.aircraft_search_form').each(function(){
		
		const thisForm = jQuery(this);
		const excludeArr = ['end_date', 'end_time'];
		const button = jQuery(thisForm).find('#aircraft_search_button');

		jQuery(button).click(async () => {
			let invalid_field = [];
			const formData = jQuery(thisForm).serializeArray();

			formData.forEach(o => {
				const {name, value} = o;
				const thisField = jQuery(`[name="${name}"]`);

				if(value === '')
				{
					if(jQuery('#aircraft_flight').val() == 0 && excludeArr.includes(name))
					{
						jQuery(thisField).removeClass('invalid_field');
					}
					else if(name.endsWith('_submit'))
					{
						//fix date picke bug that adds end_date_submit and end_hour_submit
						jQuery(thisField).removeClass('invalid_field');
					}
					else
					{
						invalid_field.push(name);
						jQuery(thisField).addClass('invalid_field');
					}
				}
				else
				{
					if(jQuery(thisField).hasClass('aircraft_list'))
					{
						if(!jQuery(thisField).hasClass('aircraft_selected'))
						{
							invalid_field.push(name);
							jQuery(thisField).addClass('invalid_field');
						}
						else
						{
							jQuery(thisField).removeClass('invalid_field');
						}
					}
					else
					{
						jQuery(thisField).removeClass('invalid_field');
					}
				}			

			});

			if(invalid_field.length === 0)
			{
				const departure = Date.parse(jQuery('input[name="start_date"]').val());
				let today = new Date();
				today.setDate(today.getDate() - 2);
				today = Date.parse(today);
				const days_between = Math.round((departure-today)/(1000*60*60*24));				
				const itinerary = jQuery('#aircraft_origin').val()+'/'+jQuery('#aircraft_destination').val();
				
				if(typeof gtag !== 'undefined')
				{	
					gtag('event', 'search_flight', {
						itinerary,
						days_between,
						departure: jQuery('#start_date').val(),
						pax: jQuery('#pax_num').val()
					});
				}
				else
				{
					console.log('dynamicaviation: gtag not defined');
				}

				createFormSubmit(thisForm);
			}
			else
			{
				console.log({invalid_field});
				alert(JSON.stringify({invalid_field}));
			}
		});


	});
}

const one_way_round_trip = () => {
	if(jQuery('#aircraft_flight').val() == 1)
	{
		jQuery('.aircraft_return').fadeIn();
	}
	jQuery('#aircraft_flight').change(function(){
		if(jQuery(this).val() == 1)
		{
			jQuery('.aircraft_return').fadeIn();
		}
		else
		{
			jQuery('.aircraft_return').fadeOut();
			jQuery('#end_date').val('');
			jQuery('#end_time').val('');
		}
	});		
}

const algolia_execute = () => {

jQuery('.aircraft_search_form').each(function(){

	const {lang} = dyCoreArgs;
	const thisForm = jQuery(this);

	jQuery(this).find('.aircraft_list').each(function(){
		
		const thisField = jQuery(this);
		
		jQuery(thisField).autocomplete({
			hint: false
		},[{
			source: $.fn.autocomplete.sources.hits(algoliaIndex, {
				hitsPerPage: 4
			}),
			displayKey: 'airport',
			templates: {
				suggestion: suggestion => {

					const localize = ['airport', 'city'];

					let {country_names, country_code, _highlightResult, iata} = suggestion;

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


					const {airport, iata: _iata, city} = _highlightResult;

					const country = (country_names.hasOwnProperty(lang)) ? country_names[lang] : null;
					let flag_url = String(jsonsrc() + "img/flags/" + country_code + '.svg').toLowerCase();
					const result = jQuery('<div class="algolia_airport clearfix"><div class="sflag pull-left"><img width="45" height="33.75" /></div><div class="sdata"><div class="sairport"><span class="airport"></span> <strong class="iata"></strong></div><div class="slocation"><span class="city"></span>, <span class="country"></span></div></div></div>');
					result.find('.sairport > .airport').html(airport.value);
					
					if(iata.length === 3)
					{
						result.find('.sairport > .iata').html(`(${_iata.value})`);
					}
					
					result.find('.slocation > .city').html(city.value);
					result.find('.slocation > .country').html(country);
					result.find('.sflag > img').attr({
						'src': flag_url
					});
					return result.html();
				}
			}
		}]).on('autocomplete:selected', function(event, suggestion) {
			

			let {iata, icao, airport, airport_names, city, country_code, _geoloc} = suggestion;

			airport = (typeof airport_names !== 'undefined')
				? (airport_names.hasOwnProperty(lang)) 
				? airport_names[lang] 
				: airport
				: airport;
			
			jQuery(thisForm)
				.find('#'+jQuery(thisField).attr('id')+'_l')
				.val(`${airport}${icao || iata.length === 3 ? ' ('+ iata + ')':  ''}, ${city}, ${country_code}`);
			
			jQuery(thisField).attr({
				'data-iata': iata,
				'data-lat': _geoloc.lat,
				'data-lon': _geoloc.lng
			}).addClass('aircraft_selected').val(iata);

			jQuery(thisField).blur(() => {
				if (jQuery(thisField).hasClass('aircraft_selected'))
				{
					jQuery(thisField).val(iata);
				}
				else
				{
					jQuery(thisField).val('');
					jQuery(thisField).removeClass('aircraft_selected');
					jQuery(thisField).addClass('invalid_field');
					jQuery(thisField).removeAttr('data-iata');
					jQuery(thisField).removeAttr('data-lat');
					jQuery(thisField).removeAttr('data-lon');						
				}
			});
				
			jQuery(thisField).focus(() => {
				jQuery(thisField).val('');
				jQuery(thisField).removeClass('aircraft_selected');
				jQuery(thisField).removeClass('invalid_field');
				jQuery(thisField).removeAttr('data-iata');
				jQuery(thisField).removeAttr('data-lat');
				jQuery(thisField).removeAttr('data-lon');
			});					
					
			if(jQuery(thisForm).find('.aircraft_selected').length == 1)
			{
				jQuery('.aircraft_list').not('.aircraft_selected').focus();
			}
			if(jQuery(thisForm).find('.aircraft_selected').length == 2)
			{
				jQuery(thisForm).find('input[name="pax_num"]').focus();
			}
			else
			{
				jQuery(thisField).blur();
			}
			
		});
	});
});

}