jQuery(() => {
	
	one_way_round_trip();
	algolia_execute();
	validate_instant_quote();
	country_dropdown();
	dynamicaviation_cookies();
	jet_datepicker();
	jet_timepicker();
	validate_jet_form();
});

const jet_timepicker = () =>	{
	jQuery('form.jet_calculator').find('input.timepicker').each(function(){
		jQuery(this).pickatime();
	});
}

const jet_datepicker = () =>	{
	
	const args = {
		format: 'yyyy-mm-dd',
		min: true
	};

	jQuery('form.jet_calculator').find('input.datepicker').each(function(){
		
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

const country_dropdown = () => {
	if(typeof jsonsrc !== typeof undefined)
	{
		if(jQuery('form#jet_booking_request').find('.countrylist').length > 0)
		{
			jet_country_dropdown(jsonsrc(), jQuery('html').attr('lang').slice(0, -3));
		}
	}	
}

const dynamicaviation_cookies = () => {
	const thisForm = jQuery('#jet_booking_request');
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



function validate_request_quote (token) {
	
	return new Promise((resolve, reject) => { 

		let count = 0;
		const thisForm = jQuery('#jet_booking_request');
		const getUrlParameter = (sParam) => {
			const sPageURL = decodeURIComponent(window.location.search.substring(1));
			const sURLVariables = sPageURL.split('&');
			let sParameterName = null;

			for (let i = 0; i < sURLVariables.length; i++) {
				sParameterName = sURLVariables[i].split('=');

				if (sParameterName[0] === sParam) {
					return sParameterName[1] === undefined ? true : sParameterName[1];
				}
			}
		};
		
		jQuery(thisForm).find('input').add('select').add('textarea').each(function(){			
			if(jQuery(this).val() == '' && jQuery(this).attr('name') != 'g-recaptcha-response')
			{
				if(getUrlParameter('jet_flight') == 0)
				{
					if(jQuery(this).attr('name') == 'jet_return_date' || jQuery(this).attr('name') == 'jet_return_hour' || jQuery(this).attr('name') == 'return_itinerary')
					{
						jQuery(this).removeClass('invalid_field');
						console.log(jQuery(this).attr('name'));
					}
					else
					{
						jQuery(this).addClass('invalid_field');
						console.log(jQuery(this).attr('name'));
						count++;
					}
				}
				else
				{
					jQuery(this).addClass('invalid_field');
					console.log(jQuery(this).attr('name'));
					count++;
				}
			}
			else
			{
				if(jQuery(this).val() == '--')
				{
					jQuery(this).addClass('invalid_field');
					console.log(jQuery(this).attr('name'));
					count++;
				}
				else
				{
					jQuery(this).removeClass('invalid_field');
					console.log(jQuery(this).attr('name'));
				}
			}
		});
		
		console.log( jQuery(thisForm).serializeArray() );
		
		if(count == 0 && jQuery(thisForm).attr('data-form-ready') == 'true')
		{
			jQuery(thisForm).attr({'action': jQuery(thisForm).attr('action')+token});
			resolve();
			jQuery(thisForm).submit();
		}
		else
		{
			reject();
			grecaptcha.reset();
		}
	});
	
}

const validate_instant_quote = () =>
{
	jQuery('button[data-aircraft]').click(function(){
		
		const aircraft_fields = jQuery('#jet_booking_request').find('#aircraft_fields');
		let inputs = jQuery(this).attr('data-aircraft');
		

		inputs = JSON.parse(inputs);
		jQuery(aircraft_fields).text('');
		
		for(let k in inputs)
		{
			jQuery(aircraft_fields).append(jQuery('<input>').attr({'type': 'text', 'name': k, 'value': inputs[k]}));
		}
		
		jQuery('#jet_booking_container').removeClass('hidden');
		jQuery('.instant_quote_table').addClass('hidden');			
		jQuery('#jet_booking_request').attr({'data-form-ready': 'true'});
		jQuery('#jet_booking_request').find('input[name="lead_name"]').focus();
	});
	
	jQuery('#jet_booking_container').find('.close').click(function(){
		jQuery('#jet_booking_container').addClass('hidden');
		jQuery('.instant_quote_table').removeClass('hidden');
	});
	
	
}
const validate_jet_form = () => {
	jQuery('.jet_calculator').each(function(){
		
		const thisForm = jQuery(this);
		
		jQuery(thisForm).find('#jet_submit').click(function(){
			
			let invalid_field = 0;
			
			jQuery(thisForm).find('input').each(function(){
				
				if(jQuery(this).val() == '')
				{
					if(jQuery('#jet_flight').val() == 0 && (jQuery(this).attr('name') == 'jet_return_date' || jQuery(this).attr('name') == 'jet_return_hour' || jQuery(this).attr('name') == 'jet_return_date_submit' || jQuery(this).attr('name') == 'jet_return_hour_submit'))
					{
						jQuery(this).removeClass('invalid_field');
					}
					else
					{
						invalid_field++;
						jQuery(this).addClass('invalid_field');
					}
				}
				else
				{
					if(jQuery(this).hasClass('jet_list'))
					{
						if(!jQuery(this).hasClass('jet_selected'))
						{
							invalid_field++;
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

			if(invalid_field == 0)
			{
				const hash = sha512(jQuery(thisForm).find('input[name="jet_pax"]').val()+jQuery(thisForm).find('input[name="jet_departure_date"]').val());
				const departure = Date.parse(jQuery('input[name="jet_departure_date"]').val());
				let today = new Date();
				today.setDate(today.getDate() - 2);
				today = Date.parse(today);
				const days_between = Math.round((departure-today)/(1000*60*60*24));				
				const itinerary = jQuery('#jet_origin').val()+'/'+jQuery('#jet_destination').val();
				
				if(typeof gtag !== 'undefined')
				{	
					gtag('event', 'search_flight', {
						itinerary,
						days_between,
						departure: jQuery('#jet_departure_date').val(),
						pax: jQuery('#jet_pax').val()
					});
				}
				else
				{
					console.log('dynamicaviation: gtag not defined');
				}
				jQuery(thisForm).attr({'action': jQuery(thisForm).attr('action')+hash});
				jQuery(thisForm).submit();
			}
		});			
	});
}


const country_name = (lang, country_name, country_code) => {
	if(country_code)
	{
		if(country_name.hasOwnProperty(lang))
		{
			return country_name[lang];
		}
		else
		{
			return country_code;
		}
	}
}

const one_way_round_trip = () => {
	if(jQuery('#jet_flight').val() == 1)
	{
		jQuery('.jet_return').fadeIn();
	}
	jQuery('#jet_flight').change(function(){
		if(jQuery(this).val() == 1)
		{
			jQuery('.jet_return').fadeIn();
		}
		else
		{
			jQuery('.jet_return').fadeOut();
			jQuery('#jet_return_date').val('');
			jQuery('#jet_return_hour').val('');
		}
	});		
}

const algolia_execute = () => {

jQuery('.jet_calculator').each(function(){

	const thisForm = jQuery(this);
	
	jQuery(this).find('.jet_list').each(function(){
		
		const this_field = jQuery(this);
		
		jQuery(this_field).autocomplete({
			hint: false
		},[{
			source: $.fn.autocomplete.sources.hits(algoliaIndex, {
				hitsPerPage: 4
			}),
			displayKey: 'airport',
			templates: {
				suggestion: function(suggestion) {

					let htmllang = jQuery('html').attr('lang');
					htmllang = htmllang.slice(0, 2);
					htmllang.toLowerCase();
					const country_names = suggestion.country_names;
					const country_flag = suggestion.country_code;
					let flag_url = jsonsrc() + "img/flags/" + country_flag + '.svg';
					flag_url = flag_url.toLowerCase();
					const result = jQuery('<div class="algolia_airport clearfix"><div class="sflag pull-left"><img width="45" height="33.75" /></div><div class="sdata"><div class="sairport"><span class="airport"></span> <strong class="iata"></strong></div><div class="slocation"><span class="city"></span>, <span class="country"></span></div></div></div>');
					result.find('.sairport > .airport').html(suggestion._highlightResult.airport.value);
					
					if(suggestion._highlightResult.hasOwnProperty('iata'))
					{
						result.find('.sairport > .iata').html(suggestion._highlightResult.iata.value);
					}
					
					result.find('.slocation > .city').html(suggestion._highlightResult.city.value);
					result.find('.slocation > .country').html(country_name(htmllang, country_names, suggestion.country_code));
					result.find('.sflag > img').attr({
						'src': flag_url
					});
					return result.html();
				}
			}
		}]).on('autocomplete:selected', function(event, suggestion) {
			
			let selectedAirport = null
			
			if(suggestion.hasOwnProperty('iata'))
			{
				if(suggestion.iata != null)
				{
					selectedAirport = suggestion.iata;
				}
				else
				{
					 selectedAirport = 'IATA missing... '+suggestion.airport;
				}
			}
			
			jQuery(thisForm).find('#'+jQuery(this_field).attr('id')+'_l').val(suggestion.airport+' ('+suggestion.iata+'), '+suggestion.city+' ('+suggestion.country_code+')');
			

			jQuery(this_field).attr({
				'data-iata': suggestion.iata,
				'data-lat': suggestion._geoloc.lat,
				'data-lon': suggestion._geoloc.lng
			}).addClass('jet_selected').val(selectedAirport);	

			jQuery(this_field).blur(() => {
				if (jQuery(this_field).hasClass('jet_selected'))
				{
					jQuery(this_field).val(selectedAirport);
				}
				else
				{
					jQuery(this_field).val('');
					jQuery(this_field).removeClass('jet_selected');
					jQuery(this_field).addClass('invalid_field');
					jQuery(this_field).removeAttr('data-iata');
					jQuery(this_field).removeAttr('data-lat');
					jQuery(this_field).removeAttr('data-lon');						
				}
			});
				
			jQuery(this_field).focus(() => {
				jQuery(this_field).val('');
				jQuery(this_field).removeClass('jet_selected');
				jQuery(this_field).removeClass('invalid_field');
				jQuery(this_field).removeAttr('data-iata');
				jQuery(this_field).removeAttr('data-lat');
				jQuery(this_field).removeAttr('data-lon');
			});					
					
			if(jQuery(thisForm).find('.jet_selected').length == 1)
			{
				jQuery('.jet_list').not('.jet_selected').focus();
			}
			if(jQuery(thisForm).find('.jet_selected').length == 2)
			{
				jQuery(thisForm).find('input[name="jet_pax"]').focus();
			}
			else
			{
				jQuery(this_field).blur();
			}
			
		});
	});
});

}

const jet_country_dropdown = (pluginurl, htmllang) => {
	$.getJSON( pluginurl + 'countries/'+htmllang+'.json')
		.done(data => {
			jetCountryOptions(data);
		})
		.fail(() => {
			$.getJSON(pluginurl + 'countries/en.json', data => {
				jetCountryOptions(data);
			});				
		});			
}	

const jetCountryOptions = data => {
	jQuery('.countrylist').each(function() {
		for (let x = 0; x < data.length; x++) 
		{
			jQuery(this).append('<option value=' + data[x][0] + '>' + data[x][1] + '</option>');
		}
	});		
}