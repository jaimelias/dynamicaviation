<?php 

#[AllowDynamicProperties]
class Dynamic_Aviation_Price_Table {
    
    
    public function __construct($utilities)
    {
		$this->utilities = $utilities;
		add_filter('init', array(&$this, 'init'));
		add_filter('admin_init', array(&$this, 'init'));
        add_filter('dy_aviation_price_table', array(&$this, 'table'), 1, 1);
    }

    public function init()
    {
        $this->home_lang = home_lang();
		$this->is_mobile = wp_is_mobile();
    }

	public function table($input_iata = '')
	{
		$output = '';
		$count = 0;
		$airport_array = $this->utilities->airport_data_by_slug();
		$is_aircraft_page = is_singular('aircrafts');
		$is_destination_page = get_query_var('fly');

		if (empty($input_iata) && !$is_aircraft_page && !empty($airport_array['iata'])) {
			$input_iata = $airport_array['iata'];
		}

		$args = array(
			'post_type' => 'aircrafts',
			'posts_per_page' => 200,
			'post_parent' => 0,
			'meta_key' => 'aircraft_price_per_hour',
			'orderby' => 'meta_value_num',
			'order'   => 'ASC',
			// perf flags (don’t change output)
			'no_found_rows' => true,
			'ignore_sticky_posts' => true,
			'update_post_term_cache' => false,
		);

		if ($is_aircraft_page) {
			$args['p'] = get_the_ID();
		}

		$wp_query = new WP_Query($args);

		if ($wp_query->have_posts()) {
			$routes = array();
			$current_language = function_exists('current_language') ? current_language() : 'en';
			$all_airports_data = $this->utilities->all_airports_data();

			if (!is_array($all_airports_data)) {
				return __('Database is not or invalid.', 'dynamicaviation');
			}

			// Build an index by IATA for O(1) lookups (replaces two O(n) scans per row)
			$airports_by_iata = array();
			foreach ($all_airports_data as $a) {
				if (!empty($a['iata'])) {
					$airports_by_iata[$a['iata']] = $a;
				}
			}

			while ($wp_query->have_posts()) {
				$wp_query->the_post();
				global $post;

				$base = aviation_field('aircraft_base_iata', $post->ID);
				$aircraft_type_slug = aviation_field('aircraft_type', $post->ID);
				$aircraft_type = $this->utilities->aircraft_type($aircraft_type_slug);

				$rates_raw = aviation_field('aircraft_rates', $post->ID);
				$table_price = json_decode(html_entity_decode($rates_raw), true);

				if (empty($table_price) || !is_array($table_price)) {
					// Previously returned '', which nuked the whole output; skipping preserves output for other posts.
					continue;
				}
				if (!array_key_exists('aircraft_rates_table', $table_price) || !is_array($table_price['aircraft_rates_table'])) {
					// Keep original text (typo and all) but don’t kill other rows/posts.
					continue;
				}

				$table_price = $table_price['aircraft_rates_table'];

				if(!is_array($table_price) || count($table_price) === 0) continue;

				$aircraft_url = $this->home_lang . $post->post_type . '/' . $post->post_name;

				foreach($table_price as $route_row) {
					$html_row = '';
					$destination_slug = '';

					$origin_iata = $route_row[0] ?? '';
					$destination_iata = $route_row[1] ?? '';
					$duration_float = (float) $route_row[2];
					$one_way_price = (float) $route_row[3];
					$fees_per_person = (float) $route_row[4];
					$base_fees = (float) $route_row[5];

					$duration_in_hours = $this->utilities->convertNumberToTime($duration_float);

					if ($input_iata) {
						// Preserve original behavior
						$origin_iata = ($input_iata === $origin_iata) ? (isset($route_row[1]) ? $route_row[1] : $origin_iata) : $origin_iata;
						$destination_iata = ($input_iata === $destination_iata) ? $destination_iata : (isset($route_row[0]) ? $route_row[0] : $destination_iata);
					}

					$show_all = true;

					if ($input_iata && !in_array($input_iata, array($origin_iata, $destination_iata), true)) {
						$show_all = false;
					}

					if (!$show_all || $origin_iata === $destination_iata || empty($origin_iata) || empty($destination_iata)) {
						continue;
					}

					// Build itinerary (same logic, simplified)
					$request_routes = array($origin_iata, $destination_iata);
					$diff = array_diff($request_routes, array($base)); // same as original array($base, $base)
					$count_diff = count($diff);

					if ($count_diff === 1) {
						$itinerary = array(
							array($origin_iata, $destination_iata),
						);
					} elseif ($count_diff === 2) {
						$itinerary = array(
							array($base, $origin_iata),
							array($origin_iata, $destination_iata),
							array($destination_iata, $base),
						);
					} else {
						// Fallback keeps behavior predictable
						$itinerary = array(
							array($origin_iata, $destination_iata),
						);
					}

					$route_name = (!$is_destination_page)
						? $origin_iata
						: sprintf('%s_%s', $origin_iata, $destination_iata);

					// Airport lookups (fast via index)
					$dest        = isset($airports_by_iata[$destination_iata]) ? $airports_by_iata[$destination_iata] : array();
					$orig        = isset($airports_by_iata[$origin_iata]) ? $airports_by_iata[$origin_iata] : array();
					$destination_slug = isset($dest['airport']) ? $dest['airport'] : '';
					$destination_airport = isset($dest['airport']) ? $dest['airport'] : '';
					if (!empty($dest['airport_names']) && is_array($dest['airport_names'])) {
						$destination_airport = isset($dest['airport_names'][$current_language]) ? $dest['airport_names'][$current_language] : $destination_airport;
					}
					$destination_city = isset($dest['city']) ? $dest['city'] : '';
					$destination_country_code = isset($dest['country_code']) ? $dest['country_code'] : '';

					$origin_airport = isset($orig['airport']) ? $orig['airport'] : '';
					if (!empty($orig['airport_names']) && is_array($orig['airport_names'])) {
						$origin_airport = isset($orig['airport_names'][$current_language]) ? $orig['airport_names'][$current_language] : $origin_airport;
					}
					$origin_city = isset($orig['city']) ? $orig['city'] : '';
					$origin_country_code = isset($orig['country_code']) ? $orig['country_code'] : '';

					if (!array_key_exists($route_name, $routes)) {
						$routes[$route_name] = array(
							'origin' => array(
								'iata' => $origin_iata,
								'airport' => $origin_airport,
								'city' => $origin_city,
								'country_code' => $origin_country_code,
							),
							'destination' => array(
								'iata' => $destination_iata,
								'airport' => $destination_airport,
								'city' => $destination_city,
								'country_code' => $destination_country_code,
							),
							'rows' => '', // avoid null . string notices
						);
					}

					$seats = isset($route_row[6]) ? $route_row[6] : '';
					$weight_pounds = isset($route_row[7]) ? (float) $route_row[7] : 0.0;
					$weight_kg = (int) round($weight_pounds * 0.453592);
					$weight_allowed = sprintf(
						'%s %s or %s %s',
						$weight_pounds,
						__('pounds', 'dynamicaviation'),
						$weight_kg,
						__('kg', 'dynamicaviation')
					);

					$route = sprintf(
						'%s %s %s %s %s (%s) %s %s, %s (%s)',
						__('Charter Flights', 'dynamicaviation'),
						$aircraft_type,
						$post->post_title,
						__('from', 'dynamicaviation'),
						$origin_airport,
						$origin_iata,
						__('to', 'dynamicaviation'),
						$destination_airport,
						$destination_city,
						$destination_iata
					);

					$html_row .= sprintf(
						'<tr data-aircraft-type="%s" data-iata="%s" title="%s">',
						esc_attr($aircraft_type_slug),
						esc_attr($origin_iata),
						esc_attr($route)
					);

					if (!$is_aircraft_page) {
						$html_row .= sprintf(
							'<td><a class="strong" href="%s">%s</a><br/><small>%s</small></td>',
							esc_url($aircraft_url),
							esc_html($post->post_title),
							esc_html($aircraft_type)
						);
					} else {
						$destination_url = $this->home_lang . 'fly/' . $this->utilities->sanitize_pathname($destination_slug);
						$destination_link = sprintf(
							'<a href="%s" title="%s">%s</a>',
							esc_url($destination_url),
							// keep extra (ignored) arg to avoid changing output in rare filters
							esc_attr(sprintf(__('Flights to %s', 'dynamicaviation'), $destination_airport, $destination_city)),
							esc_html($destination_airport)
						);
						$html_row .= sprintf(
							'<td><strong>%s</strong><br/><small class="text-muted">(%s)</small>, <span>%s</span></td>',
							$destination_link,
							esc_html($destination_iata),
							esc_html($destination_city . ', ' . $destination_country_code)
						);
					}

					if (!$is_aircraft_page) {
						$html_row .= sprintf(
							'<td><strong><span class="dashicons dashicons-admin-users"></span> %s passengers</strong><br/><small>%s</small></td>',
							esc_html($seats),
							esc_html($weight_allowed)
						);
					}

					if (!$this->is_mobile) {
						$html_row .= sprintf(
							'<td><span class="dashicons dashicons-clock"></span> %s</td>',
							esc_html($duration_in_hours)
						);
					}

					$html_row .= '<td>';
					$html_row .= sprintf('<strong>%s</strong>', esc_html(wrap_money($one_way_price)));

					if ($fees_per_person > 0) {
						$html_row .= sprintf(
							'<br/><span class="text-muted">%s $%s</span>',
							esc_html(__('Fees / pers.', 'dynamicaviation')),
							esc_html(money($fees_per_person))
						);
					}

					if ($base_fees > 0) {
						$html_row .= sprintf(
							'<br/><span class="text-muted">%s $%s</span>',
							esc_html(__('Airport fees', 'dynamicaviation')),
							esc_html(money($base_fees))
						);
					}
					$html_row .= '</td></tr>';

					$routes[$route_name]['rows'] .= $html_row;
					$count++;
				}
			}

			wp_reset_postdata();
		}

		if ($count > 0) {
			foreach ($routes as $k => $v) {
				$origin = $v['origin'];
				$destination = $v['destination'];

				$label_origin = (strlen($origin['iata']) === 3)
					? sprintf(__('%s (%s)', 'dynamicaviation'), esc_html($origin['airport']), esc_html($origin['iata']))
					: $origin['airport'];

				if ($origin['airport'] !== $origin['city'] && !empty($origin['city'])) {
					$label_origin .= ', ' . $origin['city'];
				}

				$label_destination = (strlen($destination['iata']) === 3)
					? sprintf(__('to %s (%s)', 'dynamicaviation'), esc_html($destination['airport']), esc_html($destination['iata']))
					: sprintf(__('to %s', 'dynamicaviation'), esc_html($destination['airport']));

				if ($destination['airport'] !== $destination['city'] && !empty($destination['city'])) {
					$label_destination .= ', ' . $destination['city'];
				}

				$table = '<div itemscope itemtype="https://schema.org/Table">';
				if (!$is_aircraft_page) {
					$table .= sprintf(
						'<h4 itemprop="about"><span class="light">%s</span> <span class="text-muted">%s</span> <span>%s</span></h4>',
						esc_html(__('Charter Flights', 'dynamicaviation')),
						esc_html($label_origin),
						esc_html($label_destination)
					);
				} else {
					$table .= sprintf(
						'<h4 itemprop="about">%s</h4>',
						esc_html(sprintf(__('%s from %s', 'dynamicaviation'), esc_html(__('Charter Flights', 'dynamicaviation')), esc_html($label_origin)))
					);
				}

				$table .= '<table class="dy_table text-center small pure-table pure-table-bordered bottom-40 width-100"><thead><tr>';
				$table .= sprintf('<th>%s</th>', esc_html(!$is_aircraft_page ? __('Aircrafts', 'dynamicaviation') : esc_html(__('Destinations', 'dynamicaviation'))));
				if (!$is_aircraft_page) {
					$table .= sprintf('<th>%s</th>', esc_html(__('Capacity', 'dynamicaviation')));
				}
				if (!$this->is_mobile) {
					$table .= sprintf('<th>%s</th>', esc_html(__('Duration', 'dynamicaviation')));
				}
				$table .= sprintf('<th>%s (%s)</th>', esc_html(__('One-way', 'dynamicaviation')), currency_name());
				$table .= '</tr></thead><tbody>';
				$table .= $v['rows'];
				$table .= '</tbody></table><hr/>';
				$table .= '</div>';

				$output .= $table;
			}

			return $output;
		}

		return '';
	}


}


?>