const setLat = mapbox_vars().mapbox_base_lat;
const setLon = mapbox_vars().mapbox_base_lon;
const setZoom = mapbox_vars().mapbox_map_zoom;
const mapboxToken = mapbox_vars().mapbox_token;
const mapboxId = mapbox_vars().mapbox_map_id;

L.mapbox.accessToken = mapboxToken;

const map = L.mapbox.map('mapbox_airports', mapboxId, {
	zoomControl: false,
	minZoom: 4,
	maxZoom: 16
}).setView([setLat, setLon], setZoom);
map.touchZoom.disable();
map.doubleClickZoom.disable();
map.scrollWheelZoom.disable();
new L.Control.Zoom({
	position: 'bottomright'
}).addTo(map);

const overlays = L.layerGroup().addTo(map);

jQuery(() => {

    'use strict';
	
	if(!isMobile && jQuery('.mapbox_form').length)
	{
		load_mapbox(algoliaIndex, true);
		
		map.on('moveend zoomend', function() {
			load_mapbox(algoliaIndex, false);
		});
		
		jQuery('.mapbox_form').each(function(){
			
			const thisForm = jQuery(this);
			
			jQuery(thisForm).find('.jet_list').blur(function(){			
				
				if(jQuery(this).hasClass('jet_selected') && jQuery(thisForm).find('.jet_selected').length == 1)
				{
					const getLatLng = [jQuery(this).attr('data-lat'), jQuery(this).attr('data-lon')];
					map.fitBounds([getLatLng, getLatLng]);
					map.setZoom(13);
				}
				else if(jQuery(thisForm).find('.jet_selected').length == 2)
				{
					const cardinals = [];
					
					jQuery(thisForm).find('.jet_selected').each(function(){
						const row = [];
						row.push(jQuery(this).attr('data-lat'));
						row.push(jQuery(this).attr('data-lon'));
						cardinals.push(obj(row));
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
			});			
		});	
	}
});


const load_mapbox = (index, viaIP) => {

	let latlon = [map.getCenter().lat, map.getCenter().lng];
	latlon = latlon.join(',');

	if (viaIP === true) {
		index.search({
			hitsPerPage: 1000,
			aroundLatLngViaIP: true,
			minimumAroundRadius: 20000,
		}, seachByIp);
	} else {
		index.search({
			hitsPerPage: 1000,
			aroundLatLng: String(latlon),
			minimumAroundRadius: 20000,
		}, seachByIp);
	}
}

const seachByIp = (err, content) => {

	return new Promise((resolve, reject) => {
		
		if (err) {
			reject(err);
		}

		const json_array = [];
		overlays.clearLayers();
		const markers = new L.MarkerClusterGroup();

		markers.eachLayer(layer => {
			markers.removeLayer(layer);
		});

		for (let i = 0; i < content.hits.length; i++) {
			const json_obj = {};
			const lng = parseFloat(content.hits[i]._geoloc['lng']);
			const lat = parseFloat(content.hits[i]._geoloc['lat']);
			const city = content.hits[i]['city'];
			const airport = content.hits[i]['airport'];
			const iata = content.hits[i]['iata'];

			json_obj.type = 'Feature';
			json_obj.properties = {
				'marker-symbol': 'airport',
				'marker-color': '#9ACD32',
				'marker-size': 'large'
			};
			json_obj.geometry = {
				type: 'Point',
				coordinates: []
			};
			json_obj.geometry.coordinates.push(lng, lat);
			json_array.push(json_obj);

			let title = city + ' - ' + airport;
			
			if(iata != null)
			{
				title += ' (' + iata + ')';
			}

			const marker = L.marker(new L.LatLng(lat, lng), {
				icon: L.mapbox.marker.icon({
					'marker-symbol': 'airport',
					'marker-color': '#dd3333',
					'marker-size': 'large'
				}),
				title: title
			});
		   marker.bindPopup('<div class="text-center"><a target="_top" class="large" href="'+mapbox_vars().home_url+'fly/' + convertToSlug(content.hits[i]["airport"]) + '/">' + title + '</a></div>');

			markers.addLayer(marker);
		}
		
		overlays.addLayer(markers);
		resolve(overlays);
	});
}

const obj = i => {
	return { y: i[0], x: i[1] }; 
}

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
}

const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);