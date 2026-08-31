jQuery(() => {
	validate_instant_quote();
});	


// leave all function for algolia
const validateAviationEstimateRequest  = async () => {
	
	let invalids = [];
	const thisForm = jQuery('#aircraft_booking_request');

	if(thisForm.length === 0) {
		return;
	}

	//Because the widget is inside #dy_package_request_form, Turnstile creates "cf-turnstile-response" field
	const turnstileToken = thisForm
		.find('[name="cf-turnstile-response"]')
		.val();

	if(!turnstileToken)
	{
		console.warn('Turnstile token is missing or expired.');
		return false;
	}

	const inputs = thisForm.find('input').add('select').add('textarea');
	const isOneWay = (parseInt(thisForm.find('input[name="aircraft_flight"]').val()) === 0) ? true : false;
	const requiredOnRoundTrip = ['end_date', 'end_time', 'end_itinerary'];

	inputs.each(function(){	
		
		const thisField = jQuery(this);
		const thisName = thisField.attr('name');
		const thisVal = thisField.val();

		if(thisVal === '')
		{
			if(isOneWay)
			{
				if(requiredOnRoundTrip.includes(thisName))
				{
					thisField.removeClass('invalid_field');
				}
				else
				{
					thisField.addClass('invalid_field');
					invalids.push(thisName);
				}				
			}
			else {
				thisField.addClass('invalid_field');
				invalids.push(thisName);				
			}
		}
		else
		{
			if(thisField.val() == '--')
			{
				thisField.addClass('invalid_field');
				invalids.push(thisName);
			}
			else
			{
				if(thisName === 'repeat_email')
				{
					if(thisVal !== thisForm.find('input[name="email"]').val())
					{
						thisField.addClass('invalid_field');
						invalids.push(thisName);
					}
					else
					{
						thisField.removeClass('invalid_field');
					}
				}
				else
				{
					thisField.removeClass('invalid_field');
				}
			}
		}
	});
			
	if(invalids.length === 0)
	{
		const findAmount = formToArray(thisForm).find(i => i.name === 'charter_price');
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

		const {dy_nonce} = (await getNonce()) ?? {};
		const action = atob(thisForm.attr('data-action'));
		const newAction = new URL(action, window.location.origin);

		newAction.pathname = `${newAction.pathname.replace(/\/$/, '')}/${dy_nonce}`;

		thisForm.attr('data-action', btoa(newAction.href));

		createFormSubmit(thisForm);
	}
	else
	{
		console.log({invalids});

		if(typeof turnstile !== 'undefined')
		{
			turnstile.reset();
		}
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

	const {charter_price, title, aircraft_flight, aircraft_origin, aircraft_destination} = formParams;
	const amount = parseFloat(charter_price);	
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
		const {charter_price} = formParams;
		const amount = parseFloat(charter_price);

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