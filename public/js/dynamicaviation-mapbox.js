

const convertToSlug = str => {
	return String(str)
		.toLowerCase()
		.normalize('NFD')
		.replace(/[\u0300-\u036f]/g, '')
		.replace(/[^a-z0-9\s-]/g, '')
		.trim()
		.replace(/\s+/g, '-')
		.replace(/-+/g, '-');
};

const isMobile = /Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

const RenderMap = () => {

	if(!document.getElementById('aviation_map'))
	{
		return;
	}

	const {
		mapbox_base_lat,
		mapbox_base_lon,
		mapbox_map_zoom,
		mapbox_token,
		mapbox_map_id,
		home_url
	} = mapbox_vars();
	
	L.mapbox.accessToken = mapbox_token;
	
	const map = L.mapbox.map('aviation_map', mapbox_map_id, {
		zoomControl: false,
		minZoom: 4,
		maxZoom: 16
	}).setView(
		[mapbox_base_lat, mapbox_base_lon],
		mapbox_map_zoom
	);

	map.touchZoom.disable();
	map.doubleClickZoom.disable();
	map.scrollWheelZoom.disable();

	new L.Control.Zoom({
		position: 'bottomright'
	}).addTo(map);
	
	const overlays = L.layerGroup().addTo(map);

	let arcLine = null;
	let searchSequence = 0;


	const buildMap = (err, content, sequence) => {
	
		if(err)
		{
			console.error(err);
			return;
		}

		/*
		 * Ignore results from an older map search.
		 */
		if(sequence !== searchSequence)
		{
			return;
		}

		if(!content?.hits)
		{
			return;
		}
			
		const {lang} = dyCoreArgs;

		overlays.clearLayers();

		const markers = new L.MarkerClusterGroup();
	
		for(let i = 0; i < content.hits.length; i++)
		{
			const hits = content.hits[i];

			const {
				_geoloc,
				city,
				airport,
				iata,
				airport_names
			} = hits;

			if(!_geoloc)
			{
				continue;
			}

			const {
				lng,
				lat
			} = _geoloc;

			let title = city !== airport
				? `${city} - ${airport}`
				: airport;

			if(airport_names?.hasOwnProperty(lang))
			{
				title = airport_names[lang];
			}
			
			if(iata)
			{
				title += ` (${iata})`;
			}

			const marker = L.marker(
				new L.LatLng(lat, lng),
				{
					icon: L.mapbox.marker.icon({
						'marker-symbol': 'airport',
						'marker-color': '#dd3333',
						'marker-size': 'large'
					}),
					title
				}
			);

			const slug = convertToSlug(airport);

			const url = `${
				home_url.replace(/\/?$/, '/')
			}fly/${slug}`;

			marker.bindPopup(
				`<div class="text-center"><a target="_top" class="large" href="${url}">${title}</a></div>`
			);
	
			markers.addLayer(marker);
		}
			
		overlays.addLayer(markers);
	};


	const loadMapBox = ({
		algoliaIndex,
		viaIP,
		center
	}) => {
	
		const {lat, lng} = center;

		const sequence = ++searchSequence;
	
		const callback = (err, content) => {
			buildMap(err, content, sequence);
		};
	
		if(viaIP === true)
		{
			algoliaIndex.search({
				hitsPerPage: 1000,
				aroundLatLngViaIP: true,
				minimumAroundRadius: 20000
			}, callback);
		}
		else
		{
			algoliaIndex.search({
				hitsPerPage: 1000,
				aroundLatLng: `${lat},${lng}`,
				minimumAroundRadius: 20000
			}, callback);
		}
	};


	jQuery(() => {

		/*
		 * Load the map on desktop and mobile.
		 */
		loadMapBox({
			algoliaIndex,
			viaIP: true,
			center: map.getCenter()
		});

		map.on('moveend', () => {

			loadMapBox({
				algoliaIndex,
				viaIP: false,
				center: map.getCenter()
			});

		});
	
		
		if(!isMobile && jQuery('.aviation_search_form_container').length)
		{
			jQuery('.aviation_search_form_container').each(function(){
				
				const thisForm = jQuery(this);

				thisForm.find('.aircraft_list').each(function(){

					jQuery(this).blur(function(){

						const selectedFields = thisForm.find('.aircraft_selected');
						const countSelectedFields = selectedFields.length;

						const lat = Number(jQuery(this).attr('data-lat'));
						const lon = Number(jQuery(this).attr('data-lon'));

						if(!Number.isFinite(lat) || !Number.isFinite(lon))
						{
							return;
						}

						/*
						 * Remove previous route regardless
						 * of the current selection count.
						 */
						if(arcLine)
						{
							map.removeLayer(arcLine);
							arcLine = null;
						}

						if(countSelectedFields === 1)
						{
							map.setView([lat, lon], 13);
						}
						else if(countSelectedFields === 2)
						{
							const cardinals = [];
								
							selectedFields.each(function()
							{
								cardinals.push({
									y: Number(jQuery(this).attr('data-lat')),
									x: Number(jQuery(this).attr('data-lon'))
								});
							});
			
							const generator = new arc.GreatCircle(
								cardinals[0],
								cardinals[1]
							);

							const line = generator.Arc(100, {
								offset: 10
							});

							const coordinates = line.geometries.map(geometry => {
								return geometry.coords.map(
									([lng, lat]) => [lat, lng]
								);
							});

							arcLine = L.polyline(coordinates, {
								color: '#ff6d33',
								weight: 5
							}).addTo(map);
								
							map.fitBounds(
								arcLine.getBounds(),
								{
									padding: [20, 20]
								}
							);
						}
					});	
				});
						
			});
		}
	});
};


RenderMap();