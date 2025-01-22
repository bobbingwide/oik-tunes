<?php
/*
Plugin Name: oik tunes
Plugin URI: https://www.oik-plugins.com/oik-plugins/oik-tunes
Description: Record catalogue - recordings and tracks 
Version: 2.0.1
Author: bobbingwide
Author URI: https://bobbingwide.com/about-bobbing-wide
License: GPL3

    Copyright 2013-2019, 2023, 2024, 2025 Bobbing Wide (email : herb@bobbingwide.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/
add_action( "oik_fields_loaded", "oik_tunes_fields_loaded" );
add_action( "oik_admin_menu", "oik_tunes_admin_menu" );
add_action( "wp_ajax_oik_tunes_create_tune", "oik_tunes_ajax_oik_tunes_create_tune" );

/**
 * Implement "oik_admin_menu" action for oik-tunes 
 */
function oik_tunes_admin_menu() {
  oik_register_plugin_server( __FILE__ );
  oik_require( "admin/oik-tunes.php", "oik-tunes" );
  oik_tunes_lazy_admin_menu();
}

/**
 * This is not part of oik_tunes_admin_menu since it must run even if oik is not activated
 */
add_action( "admin_notices", "oik_tunes_activation" );
/**
 * Implement "admin_notices" action for oik-tunes 
*/ 
function oik_tunes_activation() {
  static $plugin_basename = null;
  if ( !$plugin_basename ) {
    $plugin_basename = plugin_basename(__FILE__);
    add_action( "after_plugin_row_" . $plugin_basename, __FUNCTION__ );   
    require_once( "admin/oik-activation.php" );
  }  
  $depends = "oik:4.14.1,oik-fields:1.54.3";
  oik_plugin_lazy_activation( __FILE__, $depends, "oik_plugin_plugin_inactive" );
}

/**
 * Implement "oik_fields_loaded" for oik-tunes 
 */
function oik_tunes_fields_loaded() {
  oik_tunes_register_recording();
  oik_tunes_register_track();
  oik_tunes_register_taxonomies();
  //oik_tunes_register_artist();
  oik_tunes_add_shortcodes();
  add_filter( 'aql_query_vars', 'oik_tunes_aql_query_vars', 10, 3 );
  add_filter( 'the_content', 'oik_tunes_the_content' );
  add_filter( 'query_loop_block_query_vars' ,'oik_tunes_query_loop_block_query_vars', 10, 3 );
  add_filter( 'bw_post_array', 'oik_tunes_bw_post_array', 10, 2 );
}

/**
 * Return an array of recording types.  
 * @return array - recording types e.g. CD, DVD, Vinyl in an associative array?
 */
function oik_tunes_recording_types() {
  $recording_types = array( "CD", "DVD", "Vinyl" );
  return( $recording_types);
} 

/**
 * Register the oik-recording Custom Post Type
 *
 * A Recording is a set of tracks. If there is more than one volume in the recording 
 * you will need to create more than one recording. 
 *  e.g. Title: The World Is Yours: The Anthology 1968-1976 Disc 3 
 * 
 * Tracks (oik-track) refer to a specific volume of a recording
 * Copyright information, label, and other stuff can go in the content
 * Should there be a link to a product - like oik-plugins has to WooCommerce or EDD's products **?**
 *
 * You should upload the album cover photos as attachments
 * With front first in the menu - so that it can be selected in a slider or other display
 *
 * We need some way to uniquely identify the album - this is the mediaprimaryclassid
 * which is pretty hideous... {D1607DBC-E323-4BE2-86A1-48A42A28441E}
 * 
 */
function oik_tunes_register_recording() {
  $post_type = 'oik-recording';
  $post_type_args = array();
  $post_type_args['label'] = 'Recordings';
  $post_type_args['description'] = 'Audio and video recordings';
  $post_type_args['show_in_rest'] = true;
  $post_type_args['supports'] = [ 'title', 'editor', 'thumbnail', 'excerpt', 'home', 'publicize', 'author', 'revisions', 'custom-fields' ];
  bw_register_post_type( $post_type, $post_type_args );
  bw_register_field( "_oikt_type", "select", "Recording type", array( '#options' => oik_tunes_recording_types() ) ); 
  bw_register_field( "_oikt_year", "numeric", "Recording year(s)" );
  // The release year(s) may be a range of years
  bw_register_field( "_oikt_release", "numeric", "Release year(s)");
  //bw_register_field( "_oikt_MPCI", "text", "Media Primary Class ID - NOT a unique key" );
  // The album name needs to unique for each item in a Collection.
	// Albums in a collection are prefixed by their menu order,
	// which may also be in the title as Disk n
bw_register_field( "_oikt_URI", "text", "Album name" );
bw_register_field( '_oikt_wikipedia', 'URL', 'Wikipedia');
	bw_register_field( '_oikt_cocacamp', 'URL', 'CoCaCamp');
  bw_register_field_for_object_type( "_oikt_type", $post_type, true );
  bw_register_field_for_object_type( "_oikt_year", $post_type, true );
  bw_register_field_for_object_type( "_oikt_release", $post_type, true );

//	bw_register_field_for_object_type( "_oikt_MPCI", $post_type, true );
  bw_register_field_for_object_type( "_oikt_URI", $post_type, true );
  bw_register_field_for_object_type( '_oikt_wikipedia', $post_type, true );
bw_register_field_for_object_type( '_oikt_cocacamp', $post_type, true );

  add_filter( "manage_edit-{$post_type}_columns", "oik_tunes_oik_recording_columns", 10, 2 );
  add_action( "manage_{$post_type}_posts_custom_column", "bw_custom_column_admin", 10, 2 );
}

/**
 * Implements "manage_edit-oik-recording_columns" filter for oik-recording
 */
function oik_tunes_oik_recording_columns( $columns, $arg2=null ) {
  $columns["_oikt_year"] = __("Year"); 
  $columns["_oikt_URI"] = __("URI"); 
  bw_trace2();
  return( $columns ); 
}

function oik_tunes_register_taxonomies() {
	$taxonomies = [ [ 'composer', 'oik-track', 'Composer(s)'] // Pye Hastings, Richard Sinclair
		, [ 'format', 'oik-recording', 'Format'] // Vinyl, CD, DVD, Video, Blu-ray
		, [ 'recording-type', 'oik-recording', 'Recording type'] // Studio, Live, Compilation
		];
	foreach ( $taxonomies as $taxonomy_info ) {


		$taxonomy = $taxonomy_info[0];
		$post_type= $taxonomy_info[1];
		$args = $taxonomy_info[2];
		//$taxonomy ='composer';
		//$post_type='oik-track';
		bw_register_custom_category( $taxonomy, $post_type, $args );
		register_taxonomy_for_object_type( $taxonomy, $post_type );
		bw_register_field_for_object_type( $taxonomy, $post_type, true );
		//bw_register_custom_category( $taxonomy, $post_type, 'Composer(s)' );
		//register_taxonomy_for_object_type( $taxonomy, $post_type );
		//bw_register_field_for_object_type( $taxonomy, $post_type, true );
	}
}

/**
 * Register the oik-track Custom Post Type
 *
 * Most of the information we can determine from a track can be dynamically obtained at run-time using the getid3 PHP library
 * BUT we need to store some of it for when the actual file is NOT available on the server
 * 
 * So the fields we need are:
 * _oikt_recording  - reference to the actual recording
 * _oikt_track - the track number - for sorting - rather than using menu_order
 * _oikt_duration - the length of the track in mm:ss
 * _oikt_composer - the composers - separated by ;
 */
function oik_tunes_register_track() {
  $post_type = 'oik-track';
  $post_type_args = array();
  $post_type_args['label'] = 'Tracks';
  $post_type_args['description'] = 'Track';
  $post_type_args['show_in_rest'] = true;
  $post_type_args['supports'] = [ 'title', 'editor', 'thumbnail', 'excerpt', 'home', 'publicize', 'author', 'revisions', 'custom-fields' ];

  bw_register_post_type( $post_type, $post_type_args );
  bw_register_field( "_oikt_recording", "noderef", "Recording", array( '#type' => "oik-recording", '#optional' => true ) ); 
  bw_register_field( "_oikt_track", "numeric", "Track" );
  bw_register_field( "_oikt_duration", "text", "Duration (mm:ss)" );
  // Composer stores the values extracted from the file. These are then mapped to the Composer taxonomy
  bw_register_field( "_oikt_composer", "text", "Composer" );
  // Originally Unique File Identifier... this is now used to store the file name
  // of the file in the media library
  bw_register_field( "_oikt_UFI", "text", "File name" );
  bw_register_field( '_oikt_original', 'noderef', 'Original version', array( "#type" => array( "oik-track" ), "#multiple" => 5, "#optional" => true ) );
  bw_register_field_for_object_type( "_oikt_recording", $post_type, true );
  bw_register_field_for_object_type( "_oikt_track", $post_type, true );
  bw_register_field_for_object_type( "_oikt_duration", $post_type, true );
  bw_register_field_for_object_type( "_oikt_composer", $post_type, true );
  bw_register_field_for_object_type( "_oikt_UFI", $post_type, false );
  bw_register_field_for_object_type( "_oikt_original", $post_type, true );
	oik_tunes_register_virtual_fields();


	add_filter( "manage_edit-{$post_type}_columns", "oik_tunes_oik_track_columns", 10, 2 );
  add_action( "manage_{$post_type}_posts_custom_column", "bw_custom_column_admin", 10, 2 );
}

/**
 * Implements "manage_edit-oik-track_columns" filter for oik-track
 */
function oik_tunes_oik_track_columns( $columns, $arg2=null ) {
  $columns["_oikt_recording"] = __("Recording"); 
  $columns["_oikt_track"] = __("Track"); 
  $columns["_oikt_duration"] = __("Duration"); 
  bw_trace2();
  return( $columns ); 
} 

/**
 * Add the shortcodes delivered by oik-tunes
 */
function oik_tunes_add_shortcodes() {
  bw_add_shortcode( "oik-tracks", "oik_tracks", oik_path( "shortcodes/oik-tunes.php", "oik-tunes" ), false );

}

/**
 * Batch client for oik-tunes
 *
 * @param string $parms - folder or file name to pass to oik_tunes_import_recording()
 */
function oik_tunes_batch( $parms=null ) {
  oik_require( "admin/oik-tunes.php", "oik-tunes" );
  $folder = addslashes( $parms );
  oik_tunes_import_recording( $folder ); 
  bw_flush();
  exit();
}

/**
 * Implement "wp_ajax_oik_tunes_create_tune" for oik-tunes 
 */
function oik_tunes_ajax_oik_tunes_create_tune() {
 //e( "Tada from " );
 //e( __FUNCTION__ );
 oik_require( "admin/oik-tunes.php", "oik-tunes" );
 $post_id = oik_tunes_create_track( $_REQUEST );
 e( "post_id: $post_id" );
 bw_flush();
 exit();

}

/**
 * Sets the meta query value for _date using logic in oik-dates.
 *
 * The filter function attached to `oik_default_meta_value_date`
 * is expected to be `oikd8_default_meta_value_date()`.
 *
 * [0] => Array
 *
 * [meta_query] => Array
 *
 * [relation] => (string) "AND"
 * [0] => Array
 *
 * [key] => (string) "_oikt_recording"
 * [value] => (string) "1624"
 *
 * [1] => Array
 *
 * [key] => (string) "_oikt_track"
 * [compare] => (string) ">"
 *
 * @param array $query_args Arguments to be passed to WP_Query.
 * @param array $block_query The query attribute retrieved from the block.
 * @param boolean $inherited Whether the query is being inherited.
 *
 * @return array
 */
function oik_tunes_aql_query_vars( $query_args, $block_query, $inherited ) {
	bw_backtrace();
	bw_trace2();
	$id = bw_global_post_id();
	if ( isset( $query_args['meta_query'])) {
		foreach ( $query_args['meta_query'] as $index=>$meta_query ) {
			if ( $index === 'relation' ) {
				continue;
			}
			if ( '_oikt_recording' === $meta_query['key'] &&
			    isset( $meta_query['value'] ) &&
				str_contains( $meta_query['value'], '.' ) ) {
					$query_args['meta_query'][ $index ]['value']= $id;
			}
		}
	}
	return $query_args;
}

function oik_tunes_the_content( $content) {
	if ( defined( 'TUNES_FOLDER' ) && defined( 'DVINYL_SYMLINK') ) {

		$post = get_post();
		//print_r( $post );
		if ( 'oik-track' === $post->post_type ) {
			//$content.=TUNES_FOLDER;
			oik_require( 'classes/class-oik-tunes-track.php', 'oik-tunes');
			$oik_tunes_track = new Oik_Tunes_Track( $post );
			$content .= $oik_tunes_track->play();
		}
	}

	return $content;
}

/**
 * Updates the query loop query when the post template's classname is 'top-level'.
 *
 */
function oik_tunes_query_loop_block_query_vars( $default_query, $block, $page ) {
	//bw_trace2();
	//bw_backtrace();
	$template_className = $block->parsed_block['attrs']['className'] ?? null;
	if ( $template_className === 'top-level') {
		$default_query['post_parent__in'] = [ 0 ];
		$default_query['meta_key'] = '_oikt_year';
		$default_query['meta_value'] = '';
		$default_query['meta_compare'] = 'GE';
		$default_query['orderby'] = 'meta_value';
		$default_query['order'] = 'DESC';
	}
	return $default_query;
}

function oik_tunes_bw_post_array( $post_title, $post ) {
	if ( $post->post_type === 'oik-track') {
		$post_title  .=' ';
		$post_title  .=$post->ID;
		$recording_id=get_post_meta( $post->ID, '_oikt_recording', true );
		if ( $recording_id ) {
			$recording=get_post( $recording_id );
			if ( $recording ) {
				$post_title.=' ';
				$post_title.=$recording->post_title;
				$post_title.=' ';
				$post_title.=$recording->post_parent;

			}
		}
		bw_trace2();
	}
	return $post_title;
}

/**
 * Themes a Recording noderef as breadcrumb links.
 *
 * @param $key
 * @param $value
 * @param $field
 * @return void
 */

function bw_theme_field_noderef__oikt_recording( $key, $value, $field ) {
	bw_trace2();
	if ( $value && $value[0] ) {
		$recording = get_post( $value[0]);
		if ( $recording && $recording->post_parent ) {
			$parent = [ $recording->post_parent ];
			bw_theme_field_noderef( $key, $parent, $field );
			sepan( 'breadcrumb-separator', ' > ');
		}
		bw_theme_field_noderef( $key, $value, $field );
	} else {
		e( "Invalid _oikt_recording?" );
	}
}

function bw_theme_field_noderef__oikt_original( $key, $value, $field ) {
	bw_trace2();
	if ( 0 === count( $value)) {
		sepan( 'not set', 'Not set');
	}
	$id = bw_current_post_id();
	$post = get_post();
	foreach ( $value as $original ) {
		//sepan( 'debug', ".$original.$id.");
		if ( $id === (int) $original ) {
			sepan( 'original', 'This is the original');
		} else {
			//bw_theme_field_noderef( $key, [ $original ], $field );
		}
	}
}

function oik_tunes_register_virtual_fields() {
	$field_args=array(
		"#callback"=>"oik_tunes_theme_other_versions",
		"#parms"   =>"_oikt_original",
		"#plugin"  =>"oik-tunes",
		"#file"    =>"includes/oik-tunes-theme-virtual-fields.php",
		"#form"    =>false,
		"hint"     =>__( "virtual field", "oik-tunes" ),
		"#theme"   =>false
	);
	bw_register_field( "other_versions", "virtual", "Other versions", $field_args );
	bw_register_field_for_object_type( 'other_versions', 'oik-track', true );
}

/**
 * Themes a link to Wikipedia.
 *
 * eg https://en.wikipedia.org/wiki/Live_at_the_Fairfield_Halls,_1974
 *
 * @param $key
 * @param $value
 * @param $field
 *
 * @return void
 *
 */
function bw_theme_field_url__oikt_wikipedia( $key, $value, $field ) {
	oik_tunes_theme_url( $key, $value, $field );
}

/**
 * Themes a link to cocacamp.
 *
 * eg
 * @param $key
 * @param $value
 * @param $field
 *
 * @return void
 */
function bw_theme_field_url__oikt_cocacamp( $key, $value, $field ) {
	oik_tunes_theme_url( $key, $value, $field );
}

function oik_tunes_theme_url( $key, $value, $field ) {
	$v0=bw_array_get( $value, 0, $value );
	if ( $v0 ) {
		//oik_require( "shortcodes/oik-link.php" );
		$link=retlink( $field['#title'], $v0, $field['#title'], "View in {$field['#title']}", 'target="_blank"' );
		e( $link );
	}
}


