<?php // (C) Copyright Bobbing Wide 2013

/**
 * Implement [oik-tracks] shortcode 
 * 
 * @return string - an ordered list of tracks for the recording
 *
 * The oik-tracks shortcode is the equivalent of expanding: [bw_posts post_type="oik-track" meta_key="_oikt_recording" meta_value=$id orderby="_oikt_track" order=ASC]]
 *  
 * @link http://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters
 * 
 * When no ID is specified then we use the global post id. 
 */
function oik_tracks( $atts=null, $content=null, $tag=null ) {
  $id = bw_array_get_dcb( $atts, "id", "ID", "bw_global_post_id" );
  if ( $id ) {
    $atts['post_type'] = "oik-track";
    $atts['meta_key'] = "_oikt_track";
    $atts['orderby'] = "meta_value_num";
    $atts['order'] = "ASC";
    $meta_query = array();
    $meta_query[] = array( "key" => "_oikt_recording"
                       , "value" => $id
                       , "compare" => "IN"  
                       );
    $atts['meta_query'] = $meta_query;
    oik_require( "includes/bw_posts.php" );
    $posts = bw_get_posts( $atts ); 
    
    sol( bw_array_get( $atts, 'class', 'oik-tracks' ));
    foreach ( $posts as $post ) {
      bw_format_list( $post, $atts ); 
    }  
    eol();
    
  } else {
    p( "Missing ID for oik-recording" );
  }   
  return( bw_ret() ); 
}



