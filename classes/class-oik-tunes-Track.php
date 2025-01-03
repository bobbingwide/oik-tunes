<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2024
 */

class Oik_Tunes_Track {

	private $post = null;
	private $recording = null;
	private $recording_parent = null;
	private $track_url = null;
	function __construct( $post ) {
		$this->post = $post;

	}

	/**
	 * DVINYL_SYMLINK is expected to be called dvinyl and this points to `D:/vinyl/My Music/Caravan`
	 * We need to determine folder name from the track's recording metadata ( via _oikt_recording )
	 * and potentially the recording's parent.
	 *
	 * @return string
	 */

	function play() {
		$html = '';
		//$html = "Play {$this->post->post_title}";
		//$src = DVINYL_SYMLINK;
		//$html .= site_url(  $src );
		$file = ABSPATH . DVINYL_SYMLINK;
		//$html .= $file;

		if ( file_exists( $file ) ) {
			$this->get_recording();
			$this->get_recording_parent();
			$this->get_track_url();
			$html.= $this->play_audio();
		}
		return $html;
	}

	function get_recording() {
		$oikt_recording =get_post_meta( $this->post->ID, '_oikt_recording', true );
		$this->recording=get_post( $oikt_recording );
		$recording_URI  =get_post_meta( $this->recording->ID, '_oikt_URI', true );
		//echo "£ $recording_URI £";

	}

	function get_recording_parent() {
		if ( $this->recording ) {
			if ( 0 !== $this->recording->post_parent ) {
				$this->recording_parent = get_post( $this->recording->post_parent );
			} else {
				$this->recording_parent=null;
			}
		}
	}

	function get_track_url() {
		$src = DVINYL_SYMLINK;

		if ( $this->recording_parent ) {
			$src .= '/';
			$src .= get_post_meta( $this->recording_parent->ID, '_oikt_URI', true );
		}
		if ( $this->recording ) {
			$src .= '/';
			if ( $this->recording_parent) {
				$src.=$this->recording->menu_order;
				$src.='. ';
			}
			$src .= get_post_meta( $this->recording->ID, '_oikt_URI', true );
			$src .= '/';
			$src .= $this->get_track_file();

		}

		$this->track_url =site_url(  $src );


	}

	function get_track_file() {
		$track_file = '';
		//$track_file = '01 Memory Lain, Hugh - Headloss.mp3';
		$track_number = get_post_meta( $this->post->ID, '_oikt_track', true );
		//$track_file = sprintf( '%02d', $track_number);
		//$track_file .= ' ';
		$track_file .= get_post_meta( $this->post->ID, '_oikt_UFI', true);
		//$track_file .= '.mp3';
		$track_file = str_replace( '%', '%25', $track_file );
		return  $track_file ;

	}

	/**
	 * <!-- wp:audio {"id":632} -->
	 * <figure class="wp-block-audio">
	 *     <audio controls src="https://s.b/officialcaravan/wp-content/uploads/2013/02/Caravan_-_The_Dog_The_Dog_He_s_At_It_Again_Live_at_the_Bataclan_Paris_1973.mp3">
	 *         </audio>
	 * </figure>
	 * <!-- /wp:audio -->
	 */
	function play_audio() {
		$html = '<audio controls src="';
		$html .= $this->track_url;
		$html .= '">';
		return $html;
	}



}