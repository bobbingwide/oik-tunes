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
	//e( "Other versions");
	//bw_backtrace();
	//bw_trace2();
	//e( "Original track: $_oikt_original");
	$args = [ "post_type" => "oik-track"
	, "numberposts" => "-1"
	, "meta_key" => "_oikt_original"
	, "meta_value" => $_oikt_original
	//, "exclude" => -1
	, "orderby" => "date"
	, "order" => "ASC"
	];
	oik_require( "includes/bw_posts.php" );
	$posts = bw_get_posts( $args );
	//bw_trace2( $posts, "posts", false  );
	sol();
	foreach ( $posts as $post ) {
		$linktext = oik_tunes_get_full_linktext( $post);
		$linkt = retlink( "oik-track", get_permalink( $post->ID), $linktext );
		li( $linkt );
	}
	eol();
}

function oik_tunes_get_full_linktext( $post ) {
	$linktext = $post->post_title;
	$linktext .= ': ';
	$recording_id = get_post_meta( $post->ID, '_oikt_recording', true );
	$recording = get_post( $recording_id );
	if ( $recording ) {
		if ( $recording->post_parent ) {
			$parent=get_post( $recording->post_parent );
			$linktext .= $parent->post_title;
			$linktext.= ' > ';
		}
		$linktext.=$recording->post_title;
	}
	return $linktext;
}