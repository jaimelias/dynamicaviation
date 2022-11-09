jQuery(() => {
	
	one_way_round_trip();
	algolia_execute();
	dynamicaviation_cookies();
	aircraft_datepicker();
	aircraft_timepicker();
	validate_aircraft_form();
});

const aircraft_timepicker = () =>	{
	jQuery('form.aircraft_calculator').find('input.timepicker').each(function(){
		jQuery(this).pickatime();
	});
}

const aircraft_datepicker = () =>	{
	
	const args = {
		format: 'yyyy-mm-dd',
		min: true
	};

	jQuery('form.aircraft_calculator').find('input.datepicker').each(function(){
		
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

const dynamicaviation_cookies = () => {
	const thisForm = jQuery('#aircraft_booking_request');
	const landing = ['channel', 'device', 'landing_domain', 'landing_path'];
	let warnings = 0;
	const getCookie = (cname) => {
		let name = cname + '=';
		const ca = document.cookie.split(';');
		for(let i = 0; i < ca.length; i++) {
			let c = ca[i];
			while (c.charAt(0) == ' ') {
				c = c.substring(1);
			}
			if (c.indexOf(name) == 0) {
				return c.substring(name.length, c.length);
			}
		}
		return '';
	};	
	
	jQuery(thisForm).each(function(){
		
		for(let x = 0; x < landing.length; x++)
		{	
			jQuery(thisForm).find('input.'+landing[x]).each(function(){
				jQuery(this).val(getCookie(landing[x]));
			});
			
			if(jQuery(thisForm).find('input.'+landing[x]).length == 0)
			{
				console.warn('input.'+landing[x]+' not found');
				warnings++;
			}
			
		}
		
		if(warnings > 0)
		{
			console.warn('You can create custom fields with Pipedrive and track metrics.');
		}
		else
		{
			console.log('Pipedrive metric fields found.');
		}
		
	});			
}		


const validate_aircraft_form = () => {
	jQuery('.aircraft_calculator').each(function(){
		
		const thisForm = jQuery(this);
		const excludeArr = ['end_date', 'end_hour', 'aircraft_return_date_submit', 'aircraft_return_hour_submit'];
		
		jQuery(thisForm).find('#aircraft_submit').click(function(){
			
			let invalid_field = [];

			
			jQuery(thisForm).find('input').each(function(){

				const value = jQuery(this).val();
				const name = jQuery(this).attr('name');
				
				if(value === '')
				{
					if(jQuery('#aircraft_flight').val() == 0 && excludeArr.includes(name))
					{
						jQuery(this).removeClass('invalid_field');
					}
					else
					{
						invalid_field.push(name);
						jQuery(this).addClass('invalid_field');
					}
				}
				else
				{
					if(jQuery(this).hasClass('aircraft_list'))
					{
						if(!jQuery(this).hasClass('aircraft_selected'))
						{
							invalid_field.push(name);
							jQuery(this).addClass('invalid_field');
						}
						else
						{
							jQuery(this).removeClass('invalid_field');
						}
					}
					else
					{
						jQuery(this).removeClass('invalid_field');
					}
				}
			});


			if(invalid_field.length === 0)
			{
				const hash = sha512(jQuery(thisForm).find('input[name="pax_num"]').val()+jQuery(thisForm).find('input[name="start_date"]').val());
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
				jQuery(thisForm).attr({'action': jQuery(thisForm).attr('action')+hash});
				jQuery(thisForm).submit();
			}
			else
			{
				alert(invalid_field.join(', '));
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
			jQuery('#end_hour').val('');
		}
	});		
}

const algolia_execute = () => {

jQuery('.aircraft_calculator').each(function(){

	const htmlLang = (String(jQuery('html').attr('lang')).slice(0, 2)).toLowerCase() || 'en';
	const thisForm = jQuery(this);

	jQuery(this).find('.aircraft_list').each(function(){
		
		const this_field = jQuery(this);
		
		jQuery(this_field).autocomplete({
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
								if(loc.hasOwnProperty(htmlLang))
								{
									_highlightResult[k] = loc[htmlLang];
								}
							}
						}
					});


					const {airport, iata: _iata, city} = _highlightResult;

					const country = (country_names.hasOwnProperty(htmlLang)) ? country_names[htmlLang] : null;
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
				? (airport_names.hasOwnProperty(htmlLang)) 
				? airport_names[htmlLang] 
				: airport
				: airport;
			
			jQuery(thisForm)
				.find('#'+jQuery(this_field).attr('id')+'_l')
				.val(`${airport}${icao || iata.length === 3 ? ' ('+ iata + ')':  ''}, ${city}, ${country_code}`);
			
			jQuery(this_field).attr({
				'data-iata': iata,
				'data-lat': _geoloc.lat,
				'data-lon': _geoloc.lng
			}).addClass('aircraft_selected').val(iata);	

			jQuery(this_field).blur(() => {
				if (jQuery(this_field).hasClass('aircraft_selected'))
				{
					jQuery(this_field).val(iata);
				}
				else
				{
					jQuery(this_field).val('');
					jQuery(this_field).removeClass('aircraft_selected');
					jQuery(this_field).addClass('invalid_field');
					jQuery(this_field).removeAttr('data-iata');
					jQuery(this_field).removeAttr('data-lat');
					jQuery(this_field).removeAttr('data-lon');						
				}
			});
				
			jQuery(this_field).focus(() => {
				jQuery(this_field).val('');
				jQuery(this_field).removeClass('aircraft_selected');
				jQuery(this_field).removeClass('invalid_field');
				jQuery(this_field).removeAttr('data-iata');
				jQuery(this_field).removeAttr('data-lat');
				jQuery(this_field).removeAttr('data-lon');
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
				jQuery(this_field).blur();
			}
			
		});
	});
});

}