jQuery(() => {
	
	if(jQuery('#aircraft_rates_table').length > 0)
	{
		initGridsFromTextArea();
	}


	if(jQuery('.aircraft_list').length > 0)
	{
		algolia_execute();
	}
});

const algolia_execute = () => {


	let iata = null;
	const href = window.location.href;
	const url = new URL(href);
	const {searchParams, pathname} = url;

	if(pathname.endsWith('post-new.php') && searchParams.has('iata'))
	{
		iata = searchParams.get('iata');
	}

	jQuery('.aircraft_list').each(function() {

		let thisId = jQuery(this).attr('id');
		thisId = '#' + thisId;

		if(!jQuery(this).val() && iata)
		{
			jQuery(this).val(iata);
		}

		autocomplete(thisId, {
			hint: false
		}, [{
			source: autocomplete.sources.hits(algoliaIndex, {
				hitsPerPage: 5
			}),
			displayKey: 'iata',
			templates: {
				suggestion: suggestion => {

					const {lang} = dyCoreArgs;
					let {_highlightResult, country_names, country_code, iata} = suggestion;
					const country = (country_names.hasOwnProperty(lang)) ? country_names[lang] : null;
					const localize = ['airport', 'city'];


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
		
					let flagUrl = jsonsrc()+"img/flags/"+country_code+'.svg';
					flagUrl = flagUrl.toLowerCase();
					
					let result = jQuery('<div class="algolia_airport clearfix"><div class="sflag pull-left"><img width="45" height="33.75" /></div><div class="sdata"><div class="sairport"><span class="airport"></span> <span class="iata"></span></div><div class="slocation"><span class="city"></span>, <span class="country"></span></div></div></div>');
					result.find('.sairport > .airport').html(airport.value);

					if(iata.length === 3)
					{
						result.find('.sairport > .iata').html(`(${_iata.value})`);
					}
					
					result.find('.slocation > .city').html(city.value);
					result.find('.slocation > .country').html(country);
					result.find('.sflag > img').attr({'src': flagUrl});
					return result.html();
				}
			}
		}]).on('autocomplete:selected', function(event, suggestion, dataset) {

			const {_geoloc, airport, city, country_code} = suggestion;

			jQuery('.aircraft_lat').val(_geoloc.lat);
			jQuery('.aircraft_lon').val(_geoloc.lng);
			jQuery('.aircraft_base_name').val(airport);
			jQuery('.aircraft_base_city').val(city+', '+country_code);
		});
		
		jQuery(this).focus(function(){
			jQuery(this).val('');
			jQuery('.aircraft_lon').val('');
			jQuery('.aircraft_lat').val('');
			jQuery('.aircraft_base_name').val('');
			jQuery('.aircraft_base_city').val('');
		});
		
		jQuery(this).blur(function(){
			if(jQuery(this).val().length < 3)
			{
				jQuery(this).val('').attr({'placeholder': 'error'});
				jQuery('.aircraft_lon').val('');
				jQuery('.aircraft_lat').val('');					
			}
		});
		
	});		
}	