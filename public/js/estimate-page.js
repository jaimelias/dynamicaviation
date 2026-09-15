jQuery(() => {
	validate_instant_quote();
	jQuery('#aircraft_booking_request').on('submit.aviationEstimate', event => {
		event.preventDefault();
		validateAviationEstimateRequest();
	});
});

const validateAviationEstimateRequest = async () => {
	const thisForm = jQuery('#aircraft_booking_request');

	if (thisForm.length === 0 || thisForm.data('submitting')) {
		return false;
	}

	const invalids = [];
	const isOneWay = Number.parseInt(thisForm.find('[name="aircraft_flight"]').val(), 10) === 0;
	const returnFields = ['end_date', 'end_time', 'end_itinerary'];
	const generatedFields = ['cf-turnstile-response', 'unique_tx_id', 'lang'];
	const formFields = formToArray(thisForm).filter(({name}) => name && !generatedFields.includes(name));

	formFields.forEach(({name, value}) => {
		const field = thisForm.find('[name]').filter((_, input) => input.name === name);
		field.removeClass('invalid_field');

		if (isOneWay && returnFields.includes(name)) {
			return;
		}

		if (!value || value === '--' || !isValidValue({name, value, thisForm})) {
			field.addClass('invalid_field');
			invalids.push(name);
		}
	});

	if (invalids.length > 0) {
		thisForm.find('.invalid_field').first().trigger('focus');
		return false;
	}

	if (!hasTurnstileWidgets()) {
		alert(dyAviationEstimateArgs.turnstileUnavailable);
		return false;
	}

	thisForm.data('submitting', true);
	thisForm.find('button').prop('disabled', true);

	try {
		// Use one snapshot for signing and submission, even if fields change while waiting.
		const values = Object.fromEntries(formFields.map(({name, value}) => [name, value]));
		const {turnstileWidget1, turnstileWidget2} = window.dyTurnstileWidgets;
		const signUrl = new URL(`${dyAviationEstimateArgs.transactionsUrl.replace(/\/$/, '')}/${values.dy_id}`);
		const unique_tx_id = await signDyTransaction({
			signUrl,
			signRequest: {
				dy_request: values.dy_request,
				email: values.email,
				action: 'sign-transaction'
			},
			widgetId: turnstileWidget1
		});
		const token = await executeTurnstileWithRetry(turnstileWidget2);
		const {dy_nonce} = (await getNonce()) ?? {};

		if (typeof dy_nonce !== 'string' || !dy_nonce) {
			throw new Error('The confirmation nonce is missing.');
		}

		// Keep the base action unchanged so a failed attempt can be retried.
		const action = new URL(atob(thisForm.attr('data-action')), window.location.origin);
		action.pathname = `${action.pathname.replace(/\/$/, '')}/${dy_nonce}`;

		formFields.push(
			{name: 'lang', value: dyCoreArgs.lang},
			{name: 'unique_tx_id', value: unique_tx_id},
			{name: 'cf-turnstile-response', value: token}
		);

		if (typeof Storage !== 'undefined') {
			formFields.forEach(({name, value}) => {
				if (storeFieldNames.includes(name)) {
					sessionStorage.setItem(name, value);
				}
			});
		}

		handleSubmitButton(thisForm);

		if (typeof fbq !== 'undefined') {
			fbq('track', 'Lead');
		}

		const amount = Number.parseFloat(values.charter_price);
		if (typeof gtag !== 'undefined' && amount) {
			gtag('event', 'generate_lead', {value: amount, currency: 'USD'});
		}

		// The shared createFormSubmit signs against a page-based endpoint; aviation
		// signs against its own endpoint above and uses the shared final POST helper.
		formSubmit({method: 'post', action: action.href, formFields});
		return true;
	} catch (error) {
		console.error('Aviation estimate submission failed:', error);
		thisForm.data('submitting', false);
		thisForm.find('button').prop('disabled', false);
		alert(dyAviationEstimateArgs.submitError);
		return false;
	}
};

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
		jQuery('#aircraft_booking_request').find('[name="dy_id"]').val(inputs.aircraft_id);

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
		if (jQuery('#aircraft_booking_request').data('submitting')) {
			return;
		}
		jQuery('#aircraft_booking_container').addClass('hidden');
		jQuery('.instant_quote_table').removeClass('hidden');
	});	
}