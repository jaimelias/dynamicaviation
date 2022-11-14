const convertToSlug = str => {
	str = str.toLowerCase();
	str = str.replace(/á/gi,'a');
	str = str.replace(/é/gi,'e');
	str = str.replace(/í/gi,'i');
	str = str.replace(/ó/gi,'o');
	str = str.replace(/ú/gi,'u');
	str = str.replace(/ñ/gi,'n');
	str = str.replace(/ +/g,'-');
	str = str.replace(/[`~!@#$%^&*()_|+\=?;:'",.<>\{\}\[\]\\\/]/gi, '');
	str = str.replace(/\-\-/gi,'-');		
	return str;
};

const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

const RenderMap =  () => {


	if(!document.getElementById('aviation_map'))
	{
		return;
	}

	const args = mapbox_vars();
	const {mapbox_base_lat, mapbox_base_lon, mapbox_map_zoom, mapbox_token, mapbox_map_id} = args;
	
	L.mapbox.accessToken = mapbox_token;
	
	const map = L.mapbox.map('aviation_map', mapbox_map_id, {
		zoomControl: false,
		minZoom: 4,
		maxZoom: 16
	}).setView([mapbox_base_lat, mapbox_base_lon], mapbox_map_zoom);
	map.touchZoom.disable();
	map.doubleClickZoom.disable();
	map.scrollWheelZoom.disable();
	new L.Control.Zoom({
		position: 'bottomright'
	}).addTo(map);
	
	const overlays = L.layerGroup().addTo(map);
	
	jQuery(() => {
	
		
		if(!isMobile && jQuery('.aviation_search_form_container').length)
		{

			loadMapBox({algoliaIndex, viaIP: true, center: map.getCenter()});
			
			map.on('moveend zoomend', function() {
				loadMapBox({algoliaIndex, viaIP: false, center: map.getCenter()});
			});
			
			jQuery('.aviation_search_form_container').each(function(){
				
				const thisForm = jQuery(this);

				jQuery(thisForm).find('.aircraft_list').each(function(){

					jQuery(this).blur(function(){

						const allSelectedFields = jQuery(thisForm).find('.aircraft_selected');
						const countAllSelectedFields = jQuery(thisForm).find('.aircraft_selected').length;

						if(jQuery(allSelectedFields).length)
						{
							if(countAllSelectedFields == 1)
							{
								map.fitBounds([[jQuery(this).attr('data-lat'), jQuery(this).attr('data-lon')]]);
								map.setZoom(13);
							}
							else if(countAllSelectedFields == 2)
							{
								const cardinals = [];
								
								jQuery(thisForm).find('.aircraft_selected').each(function()
								{
									cardinals.push({y: jQuery(this).attr('data-lat'), x: jQuery(this).attr('data-lon')});
								});
								
								map.eachLayer(layer => {
									if(layer.hasOwnProperty('_path'))
									{
										map.removeLayer(layer);
									}
								});	
			
								const generator = new arc.GreatCircle(cardinals[0], cardinals[1]);
								const line = generator.Arc(100, { offset: 10 });
								const arcLine = L.polyline(line.geometries[0].coords.map(c => {
									return c.reverse();
								}), {
									color: '#ff6d33',
									weight: 5
								})
								.addTo(map);
								
								map.fitBounds(arcLine.getBounds(), {padding: [20,20]});
							}
						}				
					});	
				});
						
			});	
		}
	});
	
	
	const loadMapBox = ({algoliaIndex, viaIP, center}) => {
	
		const {lat, lng} = center;
	
		if (viaIP === true) {
			algoliaIndex.search({
				hitsPerPage: 1000,
				aroundLatLngViaIP: true,
				minimumAroundRadius: 20000,
			}, buildMap);
		} else {
			algoliaIndex.search({
				hitsPerPage: 1000,
				aroundLatLng: `${lat},${lng}`,
				minimumAroundRadius: 20000,
			}, buildMap);
		}
	}
	
	const buildMap = (err, content) => {
	
		return new Promise((resolve, reject) => {
			
			if (err) {
				reject(err);
			}
			
			const {lang} = dyCoreArgs;

			overlays.clearLayers();
			const markers = new L.MarkerClusterGroup();
	
			markers.eachLayer(layer => {
				markers.removeLayer(layer);
			});
	
			for (let i = 0; i < content.hits.length; i++) {
				
				const hits = content.hits[i];
				const {_geoloc, city, airport, iata, airport_names} = hits;
				const {lng, lat} = _geoloc;

				let title = (city !== airport) ?  `${city} - ${airport}` : airport;

				if(airport_names)
				{
					if(airport_names.hasOwnProperty(lang))
					{
						title = airport_names[lang];
					}
				}
				
				if(iata)
				{
					title += ` (${iata})`;
				}
	
				const marker = L.marker(new L.LatLng(lat, lng), {
					icon: L.mapbox.marker.icon({
						'marker-symbol': 'airport',
						'marker-color': '#dd3333',
						'marker-size': 'large'
					}),
					title
				});

				const {home_url} =  mapbox_vars();
				const slug = convertToSlug(airport);
				const url = `${home_url}fly/${slug}`;

				marker.bindPopup(`<div class="text-center"><a target="_top" class="large" href="${url}">` + title + '</a></div>');
	
				markers.addLayer(marker);
			}
			
			overlays.addLayer(markers);
			resolve(overlays);
		});
	}
};


RenderMap();