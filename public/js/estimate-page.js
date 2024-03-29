jQuery(() => {
	validate_instant_quote();
});	


// leave all function for algolia
async function validateAviationEstimateRequest (token) {
	
	let invalids = [];
	const thisForm = jQuery('#aircraft_booking_request');
	const inputs = jQuery(thisForm).find('input').add('select').add('textarea');
	const isOneWay = (parseInt(jQuery(thisForm).find('input[name="aircraft_flight"]').val()) === 0) ? true : false;
	const requiredOnRoundTrip = ['end_date', 'end_time', 'end_itinerary'];

	jQuery(inputs).each(function(){	
		
		const thisName = jQuery(this).attr('name');
		const thisVal = jQuery(this).val();

		if(thisVal === '')
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
				if(thisName === 'repeat_email')
				{
					if(thisVal !== jQuery(thisForm).find('input[name="email"]').val())
					{
						jQuery(this).addClass('invalid_field');
						invalids.push(thisName);
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
		}
	});
			
	if(invalids.length === 0)
	{
		const findAmount = formToArray(thisForm).find(i => i.name === 'aircraft_price');
		const amount = (findAmount) ? (findAmount.value) ? parseFloat(findAmount.value) : 0 : 0;

		if(typeof fbq !== typeof undefined)
		{
			fbq('track', 'Lead');
		}

		if(typeof gtag !== 'undefined' && amount)
		{
			//send to call
			gtag('event', 'generate_lead', {
				value: parseFloat(amount),
				currency: 'USD'
			});
		}

		createFormSubmit(thisForm);
	}
	else
	{
		console.log({invalids});
		grecaptcha.reset();
	}
}

const formArrayToParams = () => {

	const form = jQuery('#aircraft_booking_request');
	const inputs = formToArray(form);
	const params = {};

	inputs.forEach(o => {
		const {name, value} = o;

		params[name] = value;
	});

	return params;

};

const getCheckoutEventArgs2 = formParams => {

	const {aircraft_price, title, aircraft_flight, aircraft_origin, aircraft_destination} = formParams;
	const amount = parseFloat(aircraft_price);	
	const legs = parseFloat(aircraft_flight) + 1;

	return {
		value: amount,
		currency: 'USD',
		items: [{
			item_name: title,
			affiliation: 'Dynamic Aviation',
			price: (amount / legs),
			quantity: legs,
			item_category: `Charter Flights`,
			item_variant: `${aircraft_origin}_${aircraft_destination}`
		}]
	};
};

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

		const formParams = formArrayToParams();
		const {aircraft_price} = formParams;
		const amount = parseFloat(aircraft_price);

		if(typeof gtag !== 'undefined' && amount)
		{
			let addToCartArgs = getCheckoutEventArgs2(formParams);
			//send to call
			gtag('event', 'add_to_cart', addToCartArgs);
		}

		if(typeof fbq !== 'undefined')
		{
			fbq('track', 'AddToCart');
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