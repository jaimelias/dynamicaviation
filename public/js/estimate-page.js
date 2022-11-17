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
				jQuery(this).removeClass('invalid_field');
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

		const {aircraft_price, aircraft_name} = inputs;
		const amount = parseFloat(aircraft_price);

		if(typeof gtag !== 'undefined' && aircraft_price && aircraft_name)
		{
			const form = jQuery('#aircraft_booking_request');
			const legs = parseFloat(jQuery(form).find('[name="aircraft_flight"]').val())  + 1;
			const origin = jQuery(form).find('[name="aircraft_origin"]').val();
			const destination = jQuery(form).find('[name="aircraft_destination"]').val();
			const paxNum = jQuery(form).find('[name="pax_num"]').val();

			let addToCartArgs = {
				value: amount,
				currency: 'USD',
				items: [{
					item_name: aircraft_name,
					affiliation: 'Dynamic Aviation',
					price: (amount / legs),
					quantity: legs,
					item_category: `${origin}_${destination}`,
					item_variant: `${paxNum}_pax`
				}]
			};

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