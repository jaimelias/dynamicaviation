(function($) {
    'use strict';

	$(document).ready(function(){
	
		if($('.jet_list').length > 0)
		{
			algolia_execute();
		}
		if($('#jet_rates').length > 0)
		{
			min_rows();
			
			//textareas, container, number of rows
			register_grid($('#jet_rates'), $('#jet_rates_table'));			
		}
		if($('#jet_payment').length > 0)
		{
			$('#jet_payment, #jet_last_minute').change(function(){
				$('#post').attr({'action': $('#post').attr('action')+'#jet-last-minute'});
				$('#post').submit();
			});
		}
	});
	
	
	function register_grid(textareas, container)
	{
		var max_num = parseInt($('#jet_flights').val());
		
		if(isJson($(textareas).val()))
		{
			var data = JSON.parse($(textareas).val());
		}
		else
		{
			var data = initial_grid(textareas, container);
			console.log(['initial_grid', data]);
		}
		
		var grid = $(container);
		var headers = get_headers($(container));
		var columns = get_col_type($(container));	

		console.log(data);
		
		var args = {
			licenseKey: 'non-commercial-and-evaluation',
			data: data,
			stretchH: 'all',
			columns: columns,
			startCols: headers.length,
			minCols: headers.length,
			rowHeaders: true,
			colHeaders: headers,
			contextMenu: ['undo', 'redo'],
			minRows: 10,
			maxRows: max_num,
			afterChange: function(changes, source)
			{
				if (source !== 'loadData')
				{
					$(textareas).text(JSON.stringify(update_grid(textareas, grid.handsontable('getData'), container)));
				}
			}
		}
		
		grid.handsontable(args);
		
	$('#jet_flights').on('change blur click', function(){
		
		min_rows();
		var row_num = parseInt(grid.handsontable('countRows'));
		var max_num = parseInt($('#jet_flights').val());
		var instance = grid.handsontable('getInstance');
		
		if(row_num != max_num)
		{
			if(row_num < max_num)
			{
				var diff = max_num - row_num;
				instance.alter('insert_row', row_num, diff);
			}
			else
			{
				var diff = row_num - max_num;
				instance.alter('remove_row', (row_num-diff), diff);				
			}

			$(textareas).text(JSON.stringify(update_grid(textareas, grid.handsontable('getData'), container)));
		}
		
		instance.updateSettings({maxRows: max_num, data: grid.handsontable('getData')});
		instance.render();
	});			
		
	}
	
	function update_grid(textareas, data, container)
	{
		var grid_id_name = $(container).attr('id');
		var textareas_data = [];
		
		if(isJson($(textareas).val()))
		{
			var textareas_data = JSON.parse($(textareas).text());
		}
		else
		{
			var textareas_data = [];
		}	
		
		textareas_data = data;
		return textareas_data;
	}	
	
	function get_headers(container)
	{
		var headers = [];
		headers = $(container).attr('data-sensei-headers');
		//headers = headers.replace(/\s+/g, '');
		headers = headers.split(',');
		return headers;
	}
function get_col_type(container)
{
	var columns = [];
	columns = $(container).attr('data-sensei-type');
	columns = columns.replace(/\s+/g, '');
	columns = columns.split(',');
	var select_option = [];
	var output = [];
	
	for(var x = 0; x < columns.length; x++)
	{
		var row = {};
		
		if(columns[x] == 'numeric')
		{
			row.type = 'numeric';
			row.format = '0';
		}
		else if(columns[x] == 'currency')
		{
			row.type = 'numeric';
			row.format = '0.00';
		}		
		else if(columns[x] == 'date')
		{
			row.type = 'date';
			row.dateFormat = 'YYYY-MM-DD',
			row.correctFormat = true;
		}
		else if(columns[x] == 'dropdown')
		{
			
			select_option = $(container).attr('data-sensei-dropdown');
			select_option = select_option.replace(/\s+/g, '');
			select_option = select_option.split(',');
			row.type = 'dropdown';
			row.source = select_option;
		}
		else if(columns[x] == 'readonly')
		{
			row.readOnly = true;
		}
		else if(columns[x] == 'checkbox')
		{
			row.type = 'checkbox';
			row.className = 'htCenter';
		}		
		else
		{
			row.type = 'text';
		}
		output.push(row);
	}
	
	return output;	
}

	function initial_grid(textareas, container)
	{
		var headers = get_headers($(container));
		var max_num = parseInt($('#jet_flights').val());  
		var scale = {};
		var new_grid = [];
		var grid_id_name = $(container).attr('id');
		
		for(var x = 0; x < max_num; x++)
		{
			var row = [];
			
			for(var y = 0; y < headers.length; y++)
			{
				row.push(null);
			}
			new_grid.push(row);
		}
		
		$(textareas).text(JSON.stringify(new_grid));
		
		return new_grid;
	}
	
	function min_rows()
	{
		if(parseInt($('#jet_flights').val()) < 10 || $('#jet_flights').val() == '')
		{
			$('#jet_flights').val(10);
		}
	}

	function isJson(str) {
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}
	
	function algolia_execute()
	{

		$('.jet_list').each(function() {

			var this_id = $(this).attr('id');
			this_id = '#' + this_id;

			autocomplete(this_id, {
				hint: false
			}, [{
				source: autocomplete.sources.hits(algoliaIndex, {
					hitsPerPage: 5
				}),
				displayKey: 'iata',
				templates: {
					suggestion: function(suggestion) {
						var htmllang = $("html").attr("lang");
						htmllang = htmllang.slice(0, 2);
						htmllang.toLowerCase();
						
						var country_names = suggestion.country_names;
						var country_lang = null;
						
						for(var prop in country_names[0])
						{
							if(prop == htmllang)
							{
								country_lang = country_names[0][prop];
							}
							else
							{
								country_lang = country_names[0]['en'];
							}
						}
						
						
						var country_flag = suggestion.country_code;
						var flag_url = jsonsrc()+"img/flags/"+country_flag+'.svg';
						flag_url = flag_url.toLowerCase();
						
						//console.log(suggestion);

						var result = $('<div class="algolia_airport clearfix"><div class="sflag pull-left"><img width="45" height="33.75" /></div><div class="sdata"><div class="sairport"><span class="airport"></span> (<span class="iata"></span>)</div><div class="slocation"><span class="city"></span>, <span class="country"></span></div></div></div>');
						result.find('.sairport > .airport').html(suggestion._highlightResult.airport.value);
						result.find('.sairport > .iata').html(suggestion._highlightResult.iata.value);
						result.find('.slocation > .city').html(suggestion._highlightResult.city.value);
						result.find('.slocation > .country').html(country_lang);
						result.find('.sflag > img').attr({'src': flag_url});
						return result.html();
					}
				}
			}]).on('autocomplete:selected', function(event, suggestion, dataset) {
				$('.jet_lat').val(suggestion._geoloc.lat);
				$('.jet_lon').val(suggestion._geoloc.lng);
				$('.jet_base_name').val(suggestion.airport);
				$('.jet_base_city').val(suggestion.city+', '+suggestion.country_code);
			});
			
			$(this).focus(function(){
				$(this).val('');
				$('.jet_lon').val('');
				$('.jet_lat').val('');
				$('.jet_base_name').val('');
				$('.jet_base_city').val('');
			});
			
			$(this).blur(function(){
				if($(this).val().length < 3)
				{
					$(this).val('').attr({'placeholder': 'error'});
					$('.jet_lon').val('');
					$('.jet_lat').val('');					
				}
			});
			
		});		
	}	

})(jQuery);