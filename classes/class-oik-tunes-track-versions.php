<?php

/**
 * (C) Copyright Bobbing Wide 2025
 * @package oik-tunes
 *
 * Enable tracks to reference back to the original track
 * to support the display of "Other versions".
 *
 * Note: The `_oikt_original` field is rather unwieldly when presented as a multi-select list.
 * This UI may make it easier to map the tracks.
 * First we'll make a list of all the tracks and then see how easy it is to select the original track
 * and have the other versions auto-populated.
 */

class Oik_Tunes_Track_versions {

	private $oik_tracks = [];
	private $oik_recordings = [];
	private $recording_post_dates = [];
	private $first_letters = [];
	function __construct() {

	}

	function process() {
		p( "Here you match the tracks to their versions." );
		p( "Select the original track" );
		p( "Select other tracks which are versions");

		oik_require( "bobbforms.inc" );
		bw_form();
		bw_form_field_noderef( '_oikt_original', '', 'Select the original', '', ['#type' => 'oik-track']  );

		//bw_select( "folder", "Directory for recording", stripslashes( $folder), [ '#options' => $options]);
		e( isubmit(  "_set_track_versions", "Set track versions" , null, "button-primary" ) );
		etag( "form" );
		$this->list_recordings();
		$this->list_recordings_post_dates();
		$this->list_tracks();
		$this->set_tracks_post_dates();
		$this->build_first_letters();
		$this->sort_first_letters();
		//$this->report_first_letters();
		$this->pick_first_letters();
		$this->report_first_letters();
		//$this->load_options();
		$this->choose_an_original();

		bw_flush();

	}

	function list_tracks() {
		$args = [ 'post_type' => 'oik-track'
		, 'numberposts' => -1
		, 'orderby' => 'title date'
		, 'order' => 'ASC'];
		$this->oik_tracks = get_posts($args);
		p( count( $this->oik_tracks));
	}

	function build_first_letters() {
		foreach ( $this->oik_tracks as $post ) {
			$firstletters=$this->get_first_letters( $post->post_name );
			//echo $post->post_date;
			// 2025/01/18 For the time being treat as a single selection
			$original = get_post_meta( $post->ID, '_oikt_original', true );
			$post_title = substr( $post->post_title,0, 106 );
			$this->first_letters[ $post->ID] = [  $firstletters, $post->post_date, $post->ID,  $original, strlen( $post->post_title), $post_title  ];
		}
	}

	function get_first_letters( $post_name ) {
		//echo '<br />';
		//echo $post_name;
		$firstletters = '';
		$words = explode( '-', $post_name);
		//array_pop( $words );
		foreach ( $words as $word ) {
			$firstletter = substr( $word, 0, 1 );
			$lastletter = substr( $word, -1 );
			if ( !is_numeric( $firstletter )) {
				$firstletters.= $firstletter;
				$firstletters.= $lastletter;
			}
		}
		if ( strlen( $firstletters ) < 3 ) {
			$firstletters = $words[0];
		}
		///echo ' ';
		//echo $firstletters;
		return $firstletters;

	}

	function report_first_letters() {
		//bw_is_table( false );
		stag( 'table');
		stag( 'thead');
		bw_tablerow( ['Letters', 'Post date', 'ID', 'Original', 'Len', 'Title'], 'tr', 'th' );
		etag( 'thead');
		stag( 'tbody');

		foreach ( $this->first_letters as $id => $data ) {

			bw_tablerow( $data );
		}
		etag( "tbody" );
		etag( "table" );
	}

	function load_options() {
		$options = bw_load_noderef2( ['#type' => 'oik-track']);
		print_r( $options );
	}

	function list_recordings() {
		$args = [ "post_type" => 'oik-recording',
			'numberposts' => -1
		, 'orderby'=>'ID'];
		$this->oik_recordings = get_posts( $args);
		p( count( $this->oik_recordings));

	}
	function list_recordings_post_dates() {
		foreach ( $this->oik_recordings as $recording ) {
			$this->recording_post_dates[ $recording->ID] = $recording->post_date;
		}
	}

	function set_tracks_post_dates() {
		$updates = 0;
		foreach ( $this->oik_tracks as $track ) {
			$recording_id = get_post_meta( $track->ID, '_oikt_recording', true );

			if ( $recording_id ) {
				$post_date = isset( $this->recording_post_dates[ $recording_id ] ) ? $this->recording_post_dates[ $recording_id ]: null;

				if ( $track->post_date != $post_date ) {
					br();
					e( "Updating track {$track->ID} {$track->post_title} {$track->post_date} to $post_date" );

					$track->post_date = $post_date;
					wp_update_post( $track );
					$updates++;
				} else {
					//br();
					//e( "<b>OK track</b> {$track->ID} {$track->post_title} {$track->post_date} to $post_date" );
				}
			} else {
				p( "<b>No recording ID</b> for {$track->ID} {$track->post_title} {$track->post_date}");
				break;
			}
		}
		p( "Updated post dates: $updates");
	}


	function choose_an_original() {
		//$this->originals =

	}

	function sort_first_letters() {
		uasort( $this->first_letters, array( $this, "compare_first_letters" ) );
	}

	/**
	 * Compares first letter arrays.
	 *

	 *  $a < $b return -1
	 *  $a = $b return 0
	 *  $a > $b return 1
	 * @param $a
	 * @param $b
	 *
	 * @return
	 *
	 */
	function compare_first_letters( $a, $b ) {
		if ( $a[0] < $b[0] )
			return -1;
		elseif ( $a[0] == $b[0] ) {
			if ( $a[1] < $b[1] )
				return -1;
			elseif ( $a[1] == $b[1] )
				return 0;
			else
				return 1;
		}
		else
			return 1;
	}

	function pick_first_letters() {
		$current_letters = '.';
		$current_original = null;
		foreach ( $this->first_letters as $ID => $data ) {
			$letters = $data[0];
			if ( str_starts_with( $letters, $current_letters) && strlen( $current_letters) > 2  ) {
				$data[3] .= '?' . $current_original;
				$this->first_letters[ $ID ] = $data;
			} else {
				$current_letters = $letters;
				$current_original = $ID;
				$data[3] .= '!' . $current_original;
				$this->first_letters[ $ID ] = $data;
			}
			$this->set_original( $ID, $current_original );
		}
	}

	function set_original( $ID, $original) {
		$current_value = get_post_meta( $ID, '_oikt_original', true );
		if ( !$current_value ) {
			add_post_meta( $ID, '_oikt_original', $original );
		}
	}
}