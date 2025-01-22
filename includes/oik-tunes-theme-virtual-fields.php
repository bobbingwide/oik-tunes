<?php

/**
 * Lists the other versions of the track.
 *
 *
 * @param $_oikt_original - the noderef to the original of the track. Could contain more than one value.
 * @param $id - the post ID of the track
 *
 * @return void
 */

function oik_tunes_theme_other_versions( $_oikt_original, $id  ) {
	if ( empty( $_oikt_original ) )
		return;
	//e( "Other versions");
	//bw_backtrace();
	bw_trace2();
	//e( "Original track: $_oikt_original");
	$args = [ "post_type" => "oik-track"
	, "numberposts" => "-1"
	, "meta_key" => "_oikt_original"
	, "meta_value" => $_oikt_original
	, "exclude" => -1
	, "orderby" => "date"
	, "order" => "ASC"
	];
	oik_require( "includes/bw_posts.php" );
	$posts = bw_get_posts( $args );
	//bw_trace2( $posts, "posts", false  );
	sol();
	foreach ( $posts as $post ) {
		$version = oik_tunes_get_version_info( $post);
		//$linkt = retlink( "oik-track", get_permalink( $post->ID), $linktext );
		if ( $post->ID == $_oikt_original ) {
			lit( "$version (original)", 'original' );
		} else {
			li( $version );
		}

	}
	eol();
}

function oik_tunes_get_version_info( $post ) {
	$version = retlink( "oik-track", get_permalink( $post->ID), $post->post_title );
	$version .= ' ';
	$version .= get_post_meta( $post->ID, '_oikt_duration', true );
	$version .= ' ';

	$recording_id = get_post_meta( $post->ID, '_oikt_recording', true );
	$recording = get_post( $recording_id );
	if ( $recording ) {
		if ( $recording->post_parent ) {
			$parent=get_post( $recording->post_parent );
			$version .= retlink( "oik-recording", get_permalink( $parent->ID), $parent->post_title );
			$version.= ' > ';
		}

		$version .= retlink( "oik-recording", get_permalink( $recording->ID), $recording->post_title );
		$version .= ' ';
		$version .= oik_tunes_format_post_date( $recording->post_date );
	}
	return $version;
}

function oik_tunes_format_post_date( $date ) {
	$format = get_option( 'date_format' );
	$date = strtotime( $date );
	$formatted = date_i18n( $format, $date );
	return $formatted;
}