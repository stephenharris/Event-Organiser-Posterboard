<?php
/*
 Plugin Name: Event Organiser Event Posterboard
Plugin URI: http://www.wp-event-organiser.com
Version: 0.2
Description: Display events in as a responsive board
Author: Stephen Harris
Author URI: http://www.stephenharris.info
*/
/*  Copyright 2013 Stephen Harris (contact@stephenharris.info)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

define( 'EVENT_ORGANISER_EVENT_BOARD_DIR',plugin_dir_path(__FILE__ ));
function _eventorganiser_event_board_set_constants(){
	/*
	 * Defines the plug-in directory url
	* <code>url:http://mysite.com/wp-content/plugins/event-organiser-pro</code>
	*/
	define( 'EVENT_ORGANISER_EVENT_BOARD_URL',plugin_dir_url(__FILE__ ));
}
add_action( 'after_setup_theme', '_eventorganiser_event_board_set_constants' );


function eventorganiser_event_board_register_stack( $stacks ){
	$stacks[] = EVENT_ORGANISER_EVENT_BOARD_DIR . 'templates';
	return $stacks;
}
add_filter( 'eventorganiser_template_stack', 'eventorganiser_event_board_register_stack' );


function eventorganiser_event_board_shortcode_handler( $atts ){
	
	$atts = shortcode_atts(array(
				'filters' => "",
			), $atts);
	
	//Get template
	ob_start();
	eo_locate_template( 'single-event-board-item.html', true, false );
	$template = ob_get_contents();
	ob_end_clean();
	
	//Load & 'localize' script
	$ext = (defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG) ? '' : '.min';
	wp_enqueue_script( 'eo_event_board', EVENT_ORGANISER_EVENT_BOARD_URL."js/event-board{$ext}.js", array( 'jquery', 'jquery-masonry' ) );
	wp_enqueue_style( 'eo_event_board', EVENT_ORGANISER_EVENT_BOARD_URL.'css/event-board.css' );
	wp_localize_script( 'eo_event_board', 'eventorganiser_event_board',
		array(
			'url' => admin_url( 'admin-ajax.php' ),
			'loading' => __( 'Loading...', 'eventorganiser' ),
			'load_more' => __( 'Load more', 'eventorganiser' ),
			'template' => $template
		));
	
	
	//Handle filters	
	$filters = explode(',', $atts['filters']);
	$filers_markup = '';
	
	$venues = eo_get_venues();
	$cats = get_terms( array('event-category'), array('hide_empty'=> false ) );

	//'state'/'country'/'city' functions only available in Pro
	$is_pro_active = in_array( 'event-organiser-pro/event-organiser-pro.php', (array) get_option( 'active_plugins', array() ) );
	
	if( $filters ):
	
	foreach( $filters as $filter ){
		
		$filter = strtolower( trim( $filter ) );
		
		switch( $filter ):
	
			case 'venue':
				if( $venues ){
					foreach( $venues as $venue ){
			
						$filers_markup .= sprintf(
								'<a href="#" class="event-board-filter filter-venue filter-venue-%1$d" data-filter-type="venue" data-venue="%1$d" data-filter-on="false">%2$s</a>',
								$venue->term_id,
								$venue->name
							);
					}		
				}
			break;
		
			case 'category':
				if( $cats ){
					foreach( $cats as $cat ){
						$filers_markup .= sprintf(
							'<a href="#" class="event-board-filter filter-category filter-category-%1$d" data-filter-type="category" data-category="%1$d" data-filter-on="false">%2$s</a>',
							$cat->term_id,
							$cat->name
						);
					}
				}
				$filers_markup .= sprintf(
					'<a href="#" class="event-board-filter filter-category filter-category-%1$d" data-filter-type="category" data-category="%1$d" data-filter-on="false">%2$s</a>',
					0,
					__('Uncategorised')
				);
			break;
			
			case 'city':
			case 'state':
			case 'country':
				
				//If Pro isn't active, this won't work
				if( !$is_pro_active )
					break;
				
				if( 'city' == $filter ){
					$terms = eo_get_venue_cities();
				}elseif( 'state' == $filter ){
					$terms = eo_get_venue_states();
				}else{
					$terms  = eo_get_venue_countries();
				}
				
				if( $terms ){
					foreach( $terms as $term ){
						$filers_markup .= sprintf(
							'<a href="#" class="event-board-filter filter-%1$s filter-%1$s-%2$s" data-filter-type="%1$s" data-%1$s="%2$s" data-filter-on="false">%2$s</a>',
							$filter,
							$term
						);
					}						
				}
			break;
		endswitch;
	}
	endif;
	
	return
		'<div id="event-board">' 
			.'<div id="event-board-filters" data-filters="">'. $filers_markup . '</div>'  
			.'<div id="event-board-items"></div>
			.<div id="event-board-more"></div>'
		.'</div>';
}
add_shortcode( 'event_board', 'eventorganiser_event_board_shortcode_handler' );


function eventorganiser_event_boad_ajax_response(){

	$page = $_GET['page'];
	$event_query = new WP_Query( array(
			'post_type' => 'event',
			'event_start_after' => 'today',
			'numberposts' => 10,
			'paged' => $page,
	));

	$response = array();
	if( $event_query->have_posts() ){
		
		global $post;
		
		while( $event_query->have_posts() ){
			
			$event_query->the_post();
			$start_format = get_option( 'time_format' );
			
			if( eo_get_the_start( 'Y-m-d' ) == eo_get_the_end( 'Y-m-d' )  ){
				$end_format = get_option( 'time_format' );
			}else{
				$end_format = 'j M '.get_option( 'time_format' );
			}
			
			$venue_id = eo_get_venue();
			$categories = get_the_terms( get_the_ID(), 'event-category' );
			$colour = ( eo_get_event_color() ? eo_get_event_color() : '#1e8cbe' );
			$address = eo_get_venue_address( $venue_id );
			
			$event = array(
					'event_id' => get_the_ID(),
					'occurrence_id' => $post->occurrence_id,
					'event_title' => get_the_title( ),
					'event_color' => $colour,
					'event_color_light' => eo_color_luminance( $colour, 0.3 ),
					'event_start_day' => eo_get_the_start( 'j'),
					'event_start_month' => eo_get_the_start( 'M' ),
					'event_content' => get_the_excerpt(),
					'event_thumbnail' => get_the_post_thumbnail( get_the_ID(), array( '200', '200' ), array( 'class' => 'aligncenter' ) ),
					'event_permalink' => get_permalink(),
					'event_categories' => get_the_term_list( get_the_ID(),'event-category', '#', ', #', '' ),
					'event_venue' => ( $venue_id ? eo_get_venue_name( $venue_id ) : false ),
					'event_venue_id' => $venue_id,
					'event_venue_city' => ( $venue_id ? $address['city'] : false ),
					'event_venue_state' => ( $venue_id ? $address['state'] : false ),
					'event_venue_country' => ( $venue_id ? $address['country'] : false ),
					'event_venue_url' => ( $venue_id ? eo_get_venue_link( $venue_id ) : false ),
					'event_is_all_day' => eo_is_all_day(),
					'event_cat_ids' =>  $categories ? array_values( wp_list_pluck( $categories, 'term_id' ) ) : array( 0 ), 
					'event_range' => eo_get_the_start( $start_format ) . ' - ' . eo_get_the_end( $end_format ),
			);
			
			$event = apply_filters( 'eventorganiser_event_board_item', $event, $event['event_id'], $event['occurrence_id'] );
			$response[] = $event;
		}
	}

	wp_reset_postdata();
	
	echo json_encode($response);
	exit();
}
add_action( 'wp_ajax_event_board', 'eventorganiser_event_boad_ajax_response' );
add_action( 'wp_ajax_nopriv_event_board', 'eventorganiser_event_boad_ajax_response' );



function eventorganiser_event_board_plm_license_check( $license='')
{
	$license = strtoupper( str_replace( '-', '', $license ) );

	$prefix= 'eventorganiser_event_board';

	$public_key = '-----BEGIN PUBLIC KEY-----
MEwwDQYJKoZIhvcNAQEBBQADOwAwOAIxAMLNmUtiu8fYuthqj7secWdxL9K25rQm
DqYp4yZw4lxg0E/Sy/7R9dQbtPDFJgGNQwIDAQAB
-----END PUBLIC KEY-----
';

	$product_id ='event-organiser-event-board/event-organiser-event-board.php';

	$grace_period = 0;

	$local_period = 14;

	$plm_url = 'http://wp-event-organiser.com';

	$local_key = get_option($prefix.'_plm_local_key');

	$last_checked =0;

	$check_lock = 15*60;

	//Token depends on key being checked to instantly invalidate the local period when key is changed.
	$token = wp_hash($license.'|'.$_SERVER['SERVER_NAME'].'|'.$_SERVER['SERVER_ADDR'].'|'.$product_id);

	if( $local_key ){
		//Checking local key
		$signature = $local_key['signature'];
		$response = $local_key['response'];
		$signature = base64_decode($signature);
		$verified = openssl_verify($response, $signature, $public_key);

		//Unserialise response. Its an array with keys: 'valid', 'date_checked'
		$response = maybe_unserialize($response);

		if( $verified && $token == $response['token'] ){

			$last_checked = isset($response['date_checked']) ?  intval($response['date_checked'] ) : 0;
			$expires = $last_checked + intval($local_period)*24*60*60;

			if( $response['valid'] == 'TRUE' &&  ( time() < $expires ) ){
				//Local key is still valid
				return true;
			}
		}

	}

	//Check license format
	if( empty( $license ) )
		return new WP_Error( 'no-key-given' );
	if( preg_match('/[^A-Z234567]/i', $license) )
		return new WP_Error( 'invalid-license-format' );

	if( $is_valid_license = get_transient( '_check' ) && false !== get_transient( $prefix . '_check_lock' ) ){
		if( true === $is_valid_license )
			return $is_valid_license;
	}

	//Check license remotely
	$resp = wp_remote_post($plm_url, array(
			'method' => 'POST',
			'timeout' => 45,
			'body' => array(
					'plm-action' => 'check_license',
					'license' => $license,
					'product'=>$product_id,
					'domain'=>$_SERVER['SERVER_NAME'],
					'token'=> $token,
			),
	));

	$body =(array) json_decode(wp_remote_retrieve_body( $resp ));

	if( !$body || !isset($body['signature']) || !isset($body['response']) ){
		//No response or error
		$grace =  $last_checked + intval($grace_period)*24*60*60;

		if(  time() < $grace )
			return true;

		return new WP_Error( 'invalid-response' );
	}

	$signature = $body['signature'];
	$response = $body['response'];
	$signature = base64_decode($signature);

	if( !function_exists( 'openssl_verify' ) )
		return new WP_Error( 'openssl-not-enabled' );

	$verified = openssl_verify($response, $signature, $public_key);

	if( !$verified )
		return false;

	update_option($prefix.'_plm_local_key',$body);

	$response = maybe_unserialize($response);

	if( $token != $response['token'] )
		return new WP_Error( 'invalid-token' );

	if( $response['valid'] == 'TRUE' )
		$is_valid_license = true;
	else
		$is_valid_license = new WP_Error( $response['reason'] );

	if( $response['valid'] == 'TRUE' && $token == $response['token'] )
		return true;

	if( $check_lock ){
		set_transient( $prefix . '_check_lock', $license, $check_lock  );
		set_transient( $prefix . '_check', $is_valid_license, $check_lock );
	}

	return $is_valid_license;
}


class eventorganiser_event_board_PLM_Update_Handler
{

	var $prefix= 'eventorganiser_event_board';

	var $plugin_slug ='event-organiser-event-board/event-organiser-event-board.php';

	var $plm_url ='http://wp-event-organiser.com';

	var $license = false;

	function __construct(){

		/* Fired just before setting the update_plugins site transient. Transient stores if new
		 version exists, so we must add this manually to the transient after checking if one exists */
		add_filter ('pre_set_site_transient_update_plugins', array($this,'check_update'));

		/* Remotely retrieve this plug-ins formation. Hook in late as some plug-ins do this wrong*/
		add_filter ('plugins_api', array($this,'plugin_info'),9999,3);

	}


	/**
	 * A callback hooked inside 'plugins_api()' is called. We use this hook to 'abort' plugins_api() early
	 *  and run our request to check the plug-in's data from our custom 'repository'.
	 */
	public function plugin_info( $check, $action, $args ){

		if ( $args->slug == $this->plugin_slug ) {
			$obj = $this->get_remote_plugin_info('plugin_info');
			return $obj;
		}
		return $check;
	}


	/**
	 * Get's current version of installed plug-in.
	 */
	public function get_current_version(){
		$plugins = get_plugins();

		if( !isset( $plugins[$this->plugin_slug] ) )
			return false;

		$plugin_data = $plugins[$this->plugin_slug];
		return $plugin_data['Version'];
	}


	/**
	 * Fired just before setting the update_plugins site transient. Remotely checks if a new version is available
	 */
	public function check_update($transient){

		/**
		 * wp_update_plugin() triggers this callback twice by saving the transient twice
		 * The repsonse is kept in a transient - so there isn't much of it a hit.
		 */

		//Get remote information
		$plugin_info = $this->get_remote_plugin_info('plugin_info');

		// If a newer version is available, add the update
		if (version_compare($this->get_current_version(), $plugin_info->new_version, '<') ){

			$obj = new stdClass();
			$obj->slug = $this->plugin_slug;
			$obj->new_version = $plugin_info->new_version;
			$obj->package =$plugin_info->download_link;
				
			if( isset( $plugin_info->sections['upgrade_notice'] ) ){
				$obj->upgrade_notice = $plugin_info->sections['upgrade_notice'];
			}

			//Add plugin to transient.
			$transient->response[$this->plugin_slug] = $obj;
		}

		return $transient;
	}


	/**
	 * Return remote data
	 * Store in transient for 12 hours for performance
	 *
	 * @param (string) $action -'info', 'version' or 'license'
	 * @return mixed $remote_version
	 */
	public function get_remote_plugin_info($action='plugin_info'){

		/* Get license from option */
		$this->license =''; //e.g. get_option('myplugin_license_key');

		$key = wp_hash('plm_eventorganiser_event_board_'.$action.'_'.$this->plugin_slug);
		if( false !== ($plugin_obj = get_site_transient( $key ) ) && !$this->force_request() ){
			return$plugin_obj;
		}

		$request = wp_remote_post($this->plm_url, array(
				'method' => 'POST',
				'timeout' => 45,
				'body' => array(
						'plm-action' => $action,
						'license' => $this->license,
						'product'=>$this->plugin_slug,
						'domain'=>$_SERVER['SERVER_NAME'],
				)
		));

		if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
			//If its the plug-in object, unserialize and store for 12 hours.
			$plugin_obj =   ( 'plugin_info' == $action ? unserialize($request['body']) : $request['body'] );
			set_site_transient( $key, $plugin_obj, 12*60*60 );
			return $plugin_obj;
		}
		//Don't try again for 5 minutes
		set_site_transient( $key, '', 5*60 );
		return false;
	}

	public function force_request(){

		//We don't use get_current_screen() because of conclict with InfiniteWP
		global $current_screen;

		if ( ! isset( $current_screen ) )
			return false;

		return isset($current_screen->id) && ( 'plugins' == $current_screen->id || 'update-core' == $current_screen->id );
	}
}
$eventorganiser_event_board_plm_update_handler = new eventorganiser_event_board_PLM_Update_Handler();


/**
 * Register settings & add License field to general tab
 */
function eventorganiser_event_board_register_liciense_field(){
	
	$setting = "eventorganiser_event_board_license";
	register_setting( 'eventorganiser_general', $setting );

	$installed_plugins = get_plugins();
	$eo_version = isset( $installed_plugins['event-organiser/event-organiser.php'] )  ? $installed_plugins['event-organiser/event-organiser.php']['Version'] : false;
	if( $eo_version && ( version_compare( $eo_version, '2.3'  ) >= 0 ) ){
		$section_id = 'general_licence';
	}else{
		$section_id = 'general';
	}

	add_settings_field(
		'eo-event-board-license',
		__( 'Event Posterboard', 'eventorganiserpb' ),
		'_eventorganiser_event_board_license_field',
		'eventorganiser_general',
		$section_id
	);
}
add_action('eventorganiser_register_tab_general', 'eventorganiser_event_board_register_liciense_field');

/**
 * Display settings field
*/
function _eventorganiser_event_board_license_field(){
	
	$setting = "eventorganiser_event_board_license";
	$url = 'http://wp-event-organiser.com/downloads/event-organiser-event-posterboard/';
	
	$license = get_option( $setting );
	$check = eventorganiser_event_board_plm_license_check( $license );
	$valid = !is_wp_error( $check );

	eventorganiser_text_field ( array(
		'label_for' => 'eo-event-board',
		'value' => $license,
		'name' => $setting,
		'style' => $valid ? 'background:#D7FFD7' : 'background:#FFEBE8',
		'help' => $valid ? '' : __( 'The license key you have entered is invalid.', 'eventorganiserp' )
			.' <a href="'.$url.'">'.__('Purchase a license key', 'eventorganiserp' ).'</a>'
			. eventorganiser_inline_help(
				__( 'Invalid license key', 'eventorganiserp' ). ' ( '.$check->get_error_code().' )',
					'<p>'
					.__( 'Without a valid license key you will not be eligable for automatic updates, priority support or unrestricted access to tutorials.','eventorganiserp')
					.' <a href="'.$url.'">'.__('Purchase a license key', 'eventorganiserp' ).'</a>.'
					.'</p>'							
					.'<p>If you have a entered valid license which does not seem to work, please <a href="http://wp-event-organiser.com/contact/">contact suppport</a></p>'
			)
	));
}