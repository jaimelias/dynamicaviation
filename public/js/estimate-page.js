jQuery(() => {
	validate_instant_quote();
	country_dropdown();
});


const country_dropdown = () => {
	if(typeof jsonsrc !== 'undefined')
	{
		if(jQuery('form#aircraft_booking_request').find('.countrylist').length > 0)
		{
			aircraft_country_dropdown(jsonsrc(), jQuery('html').attr('lang').slice(0, -3));
		}
	}	
}		


// leave all function for algolia
async function validateAviationEstimateRequest (token) {
	
	let invalids = [];
	const thisForm = jQuery('#aircraft_booking_request');
	const inputs = jQuery(thisForm).find('input').add('select').add('textarea');
	const queryString = window.location.search;
	const urlParams = new URLSearchParams(queryString);
	const isOneWay = urlParams.has('aircraft_flight') ? parseInt(urlParams.get('aircraft_flight')) === 0 ? true : false : false;
	const action = jQuery(thisForm).attr('action')+token;
	const requiredOnRoundTrip = ['aircraft_return_date', 'aircraft_return_hour', 'return_itinerary'];

	jQuery(inputs).each(function(){	
		
		const thisName = jQuery(this).attr('name');
		const thisVal = jQuery(this).val();

		if(thisVal == '' && thisName != 'g-recaptcha-response')
		{
			if(isOneWay)
			{
				if(requiredOnRoundTrip.includes(thisName))
				{
					jQuery(this).removeClass('invalid_field');
				}
				else
				{
					jQuery(this).addClass('invalid_field');
					invalids.push(thisName);
				}				
			}
			else {
				jQuery(this).addClass('invalid_field');
				invalids.push(thisName);				
			}
		}
		else
		{
			if(jQuery(this).val() == '--')
			{
				jQuery(this).addClass('invalid_field');
				invalids.push(thisName);
			}
			else
			{
				jQuery(this).removeClass('invalid_field');
			}
		}
	});
			
	if(invalids.length === 0)
	{
		jQuery(thisForm).attr({action}).submit();
	}
	else
	{
		console.log({invalids});
		grecaptcha.reset();
	}
}

const validate_instant_quote = () =>
{
	jQuery('button[data-aircraft]').click(function(){
		
		const aircraft_fields = jQuery('#aircraft_booking_request').find('#aircraft_fields');
		let inputs = jQuery(this).attr('data-aircraft');
		

		inputs = JSON.parse(inputs);
		jQuery(aircraft_fields).text('');
		
		for(let k in inputs)
		{
			jQuery(aircraft_fields).append(jQuery('<input>').attr({'type': 'text', 'name': k, 'value': inputs[k]}));
		}
		
		jQuery('#aircraft_booking_container').removeClass('hidden');
		jQuery('.instant_quote_table').addClass('hidden');			
		jQuery('#aircraft_booking_request').find('input[name="first_name"]').focus();
	});
	
	jQuery('#aircraft_booking_container').find('.close').click(function(){
		jQuery('#aircraft_booking_container').addClass('hidden');
		jQuery('.instant_quote_table').removeClass('hidden');
	});
	
	
}



const aircraft_country_dropdown = (pluginurl, htmllang) => {
	$.getJSON( pluginurl + 'countries/'+htmllang+'.json')
		.done(data => {
			aircraftCountryOptions(data);
		})
		.fail(() => {
			$.getJSON(pluginurl + 'countries/en.json', data => {
				aircraftCountryOptions(data);
			});				
		});			
}	

const aircraftCountryOptions = data => {

	data = data
		.filter(i => i[0] && i[1])
		.sort((a, b) => a[1].localeCompare(b[1]));

	jQuery('.countrylist').each(function() {
		for (let x = 0; x < data.length; x++) 
		{
			jQuery(this).append('<option value=' + data[x][0] + '>' + data[x][1] + '</option>');
		}
	});		
}