<?php // (C) Copyright Bobbing Wide 2013


// @link http://www.id3.org/id3v2.3.0#head-e4b3c63f836c3eb26a39be082065c21fba4e0acc
  
function oik_tunes_lazy_admin_menu() {

  add_submenu_page( 'oik_menu', 'oik tunes', 'Import tracks', 'manage_options', 'oik_tunes', 'oik_tunes_do_page' );
  //add_posts_page( "", " move", 'manage_options', "oik_tunes", "oik_tunes_do_page");
  
  //add_submenu_page( 'edit.php?post_type=oik-track', "Import tracks", "manage_options", "oik_tunes", "oik_tunes_do_page"  );
  
}

/** 
 * Display the page to mass import tracks for a recording 
 */
function oik_tunes_do_page() {
  p( "Here you specify the folder for the recording." );
  p( "And click on the Import track(s) button " );
  
  oik_require( "bobbforms.inc" );
  bw_form();
  $folder = bw_array_get( $_REQUEST, "folder",  "C:/vinyl/My Music/Caravan" );
  bw_textfield( "folder", 80, "Directory for recording", stripslashes( $folder ) );
  e( isubmit(  "_create_oik_tune", "Import track(s)" , null, "button-primary" ) );
  etag( "form" );
  oik_tunes_import();
  bw_flush();
}

/** 
 * Import a Recording and all its tracks using the getID3() PHP library
 */
function oik_tunes_import_recording( $folder ) {
  $folder = stripslashes( $folder );
  p( "Analyzing $folder "); 
  if ( !class_exists( "getID3" ) ) {
  	p( "Loading getID3 from oik-tunes" );
    oik_require( "getid3/getid3.php", "oik-tunes" );
  }  
  // Initialize getID3 engine
  $getID3 = new getID3;
  $getID3->setOption( array( 'encoding' => "UTF-8" ));
  oik_tunes_analyze( $folder, $getID3 );
}

/**
 *
 */ 
function oik_tunes_import() {
  $folder = bw_array_get( $_REQUEST, "folder", null ); 
  if ( $folder ) {
    oik_tunes_import_recording( $folder );
  } 
}

/**
 * Return the duration of the track
 * @param array $fileinfo
 * @return string - duration of the track
 */
function oik_tunes_get_duration( $fileInfo ) {
  $duration = bw_array_get( $fileInfo, "playtime_string", null );
  p( "Duration: $duration ");
  return( $duration );
}

/**
 * Return the composer(s) taking into account additional fields that getID3() does not automatically merge into comments_html
 * 
 * @param array $fileInfo 
 * @return string - a string of composer names separated by semi-colons (;)
 *
 * The fields we're looking for are in the $fileInfo structure

   [asf]
     [header_extension_object] 
       [extension_data_parsed] 
         array
           [guid_name] = "GETID3_ASF_Metadata_Library_Object"
           [description_record]
             array 
               [data_type] = 0 
               [name] = "WM/Composer"  - stored in Unicode so we have to strip the hex 0's
               [data] = $composer - stored in Unicode so we have to strip the hex 0's
 */
function oik_tunes_get_composers( $fileInfo ) {
  $composers = array();
  $composers[] = oik_tunes_get_field( $fileInfo, "composer" );
  if ( is_array( $fileInfo['asf']['header_extension_object']['extension_data_parsed'] ) ) {
    $extension_data_parsed = $fileInfo['asf']['header_extension_object']['extension_data_parsed'];
    foreach ( $extension_data_parsed as $key => $data ) {
      if ( $data['guid_name'] == "GETID3_ASF_Metadata_Library_Object" ) {
        $description_record = $data['description_record'];
        foreach ( $description_record as $key => $record ) {
          if ( $record['data_type'] == 0 ) {
            $name = $record['name'];
            $name = str_replace( "\x00", "", $name );
            if ( $name == "WM/Composer" ) {
              $composer = $record['data'];
              $composer = str_replace( "\x00", "", $composer );
              $composers[] = $composer;
              bw_trace2( $composers, "composers", false );
            } else {
              // bw_trace2( $record, "record", false );
            }
          }  
        }
      }  
    }
  }   
  $composers = implode( ";", $composers );
  p( $composers );
  return( $composers );
}

function oik_tunes_get_artist( $fileInfo ) {
  $artist = oik_tunes_get_field( $fileInfo, "artist" ); 
  p( "Artist: $artist " );
  return( $artist );
}

function oik_tunes_get_title( $fileInfo ) {
  $title = oik_tunes_get_field( $fileInfo, "title" );
  p( "Title: $title " );
  return( $title );
}  

/** 
 * Return an imploded field from comments_html
 *
 *
 * Extract from structure.txt

   If you want to merge all available tags (for example, ID3v2 + ID3v1) into one array, you can call
    getid3_lib::CopyTagsToComments($ThisFileInfo)
   and you'll then have ['comments'] and ['comments_html'] which are
   identical to ['tags'] and ['tags_html'] except the array is one
   dimension shorter (no tag type array keys). 
   For example, artist is:
   ['tags_html']['id3v1']['artist'][0] or 
   ['comments_html']['artist'][0]

   Some commonly-used information is found in these locations:

   File type:        ['fileformat']                  // ex 'mp3'
   Song length:      ['playtime_string']             // ex '3:45'    (minutes:seconds)
                     ['playtime_seconds']            // ex 225.13    (seconds)
   Overall bitrate:  ['bitrate']                     // ex 113485.71 (bits-per-second - divide by 1000 for kbps)
   Audio frequency:  ['audio']['sample_rate']        // ex 44100     (Hertz)
   Artist name:      ['comments_html']['artist'][0]  // ex 'Elvis'   (if CopyTagsToComments() is used - see above)
                                                  //   more than one artist may be present, you may want to use implode:
                                                  //   implode(' & ', ['comments_html']['artist'])
*/
function oik_tunes_get_field( $fileInfo, $field ) {
  $comments_html = bw_array_get( $fileInfo, "comments_html", null );
  if ( $comments_html ) {
    bw_trace2( $comments_html, "comments_html", true );
    $value = bw_array_get( $comments_html, $field, null );
    if ( $value ) {
      $value = implode( ";", $value);
    }  
  } else {
    $value = null ;
  }
  p( "$field: $value " );
  return( $value );
}


/**
 * Analyze the file to determine the fields for oik-recording and oik-track and create the oik-track

  bw_register_field( "_oikt_recording", "noderef", "Recording", array( '#type' => "oik-recording", '#optional' => true ) ); 
  bw_register_field( "_oikt_track", "number", "Track number e.g. 1" );
  bw_register_field( "_oikt_duration", "text", "Duration in mm:ss" );
  
  bw_register_field( "_oikt_composer", "text", "Composer(s)" ); 
  
  Other fields:
  album, albumartist, artist, composer, encoding_time_unix, genre, mediaprimaryclassid, 
  provider, providerrating, providerstyle, publisher, title, track, uniquefileidentifier, year
  
*/  
function oik_tunes_analyze_file( $filename, $getID3 ) {
  $fileInfo = $getID3->analyze( $filename );
  getid3_lib::CopyTagsToComments( $fileInfo);
  bw_trace2( $fileInfo,  "fileInfo" );
  //print_r( $fileInfo );
  $result = array();
  $result['_oikt_album'] = oik_tunes_get_field( $fileInfo, "album" );
  $result['_oikt_artist'] = oik_tunes_get_artist( $fileInfo );
  $result['_oikt_composer'] = oik_tunes_get_composers( $fileInfo );
  $result['_oikt_duration'] = oik_tunes_get_duration( $fileInfo ); 
  $result['_oikt_title'] = oik_tunes_get_title( $fileInfo ); 
  $result['_oikt_track'] = oik_tunes_get_field( $fileInfo, "track_number" );
  $result['_oikt_year'] = oik_tunes_get_field( $fileInfo, "year" ); 
  $result['_oikt_publisher'] = oik_tunes_get_field( $fileInfo, "publisher" ); 
  $result['_oikt_MPCI'] = oik_tunes_get_field( $fileInfo, "mediaprimaryclassid" ); 
  $result['_oikt_UFI'] = oik_tunes_get_UFI( $result, $fileInfo ) ;
  $result['_oikt_URI'] = oik_tunes_get_URI( $result );
  bw_trace2( $result, "result", false ); 
  // $site =
  $post_id = oik_tunes_create_track_client( $result );
  return( $post_id );
}

/**
 * Determine how to create the track
 * 
 * oikb_get_site() won't be loaded if we're not processing batch
 */
function oik_tunes_create_track_client( $result ) {
  if ( function_exists( "oikb_get_site" ) ) {
    $site = oikb_get_site(); 
  } else {
    $site = null;
  }
  if ( $site ) {
    $post_id = oik_tunes_create_track_admin_ajax( $result, $site );
  } else {
    $post_id = oik_tunes_create_track( $result );
  }
  return( $post_id );
}

/**
 * Invoke AJAX to create the track on the remote server
 */
function oik_tunes_create_track_admin_ajax( $result, $site ) {
  global $oikb_cookies;
  $url = "$site/wp-admin/admin-ajax.php" ;
  $body = $result;
  $body["action"] = "oik_tunes_create_tune"; 
  $args = array( "body" => $body
               , "cookies" => $oikb_cookies
               );
  $result = oikb_remote_post( $url, $args );
  echo $result . PHP_EOL;
  return( $result );
}

/**
 * Create a part of the content field for an oik-track 
 */
function oik_tunes_create_content_field( $result, $field, $title, $default=null ) {
  $otag = "div";
  $itag = "span";
  $value = retstag( $otag, "{$field}_title" ); 
  $value .= $title;
  $value .= ": ";
  $value .= retstag( $itag, "{$field}_value" );
  $value .= bw_array_get( $result, $field, $default );
  $value .= retetag( $itag );
  $value .= retetag( $otag );
  return( $value );
} 

/**
 * Build album_link for a track
 *
 * @param array $result - array of values
 * @param ID $oikt_album - post ID of the album (recording)
 * @return string - the result
 

  $url = get_permalink( $oikt_album );
  $link = retlink( null, $url, $result['_oikt_album']);
 * 
 */
function oik_tunes_album_link( $result, $oikt_album ) {
  $link = "[bw_field name=_oikt_recording]";
  return( $link );
}

/**
 * Create the content field for an oik-track
 * 
 */
function oik_tunes_create_track_content( $result, $oikt_album ) {
  $content = bw_array_get( $result, '_oikt_title', null );
  $content .= " ";
  $content .= "( ";
  $content .= bw_array_get( $result, '_oikt_year', null );
  $content .= " )"; 
  $content .= "<!--more -->"; 
  $result['album_link'] = oik_tunes_album_link( $result, $oikt_album );  
  $content .= oik_tunes_create_content_field( $result, "album_link", "Album" );
  $content .= oik_tunes_create_content_field( $result, "_oikt_track" , "Track" );
  $content .= oik_tunes_create_content_field( $result, "_oikt_duration", "Duration" ); 
  $content .= oik_tunes_create_content_field( $result, "_oikt_composer", "Composer(s)" );
  $content .= oik_tunes_create_content_field( $result, "_oikt_publisher", "Publisher" ); 
  $content .= oik_tunes_create_content_field( $result, "_oikt_artist", "Artist" );  
  return( $content ); 
}
 
/**
 * Create the content field for an oik-recording
 * 
 * e.g 
 
Blind Dog at St. Dunstan's 1976
<!--more-->
[bw_images]
 */
function oik_tunes_create_recording_content( $result ) {
  $content = bw_array_get( $result, '_oikt_album', null );
  $content .= " ";
  $content .= "( ";
  $content .= bw_array_get( $result, '_oikt_year', null );
  $content .= " )"; 
  $content .= "<!--more -->"; 
  $content .= "[bw_images titles=n link=0]";
  $content .= oik_tunes_create_content_field( $result, "_oikt_year", "Year" ); 
  $content .= oik_tunes_create_content_field( $result, "_oikt_format", "Format", "CD" ); 
  $content .= oik_tunes_create_content_field( $result, "_oikt_publisher", "Publisher" ); 
  $content .= oik_tunes_create_content_field( $result, "_oikt_artist", "Artist" ); 
  $content .= "[oik-tracks]" ; 
  return( $content ); 
}

/**
 * Get a unique album identifier from UniqueFileIdentifier or Album, Artist, Year
 *
 * The format of the UniqueFileIdentifier is expected to consist of 3 parts
 * AMGa_id=R 32302;AMGp_id=P 16292;AMGt_id=T 3513495 
 * where we treat the 3rd part as the track ID
 * In order to create a unique Identifier for the recording we strip the 3rd part
 * BUT if there aren't 3 parts then we invent one from the Album, Artist and Year
 * 
 * @link http://lists.musicbrainz.org/pipermail/musicbrainz-users/2006-September/013980.html
 *
 */
function oik_tunes_get_URI( $result ) {
  $URI = bw_array_get( $result, "_oikt_UFI", null ); 
  //p( "$URI before" );
  $URIs = explode( ";", $URI );
  if ( count( $URIs ) == 3 ) {
    array_pop( $URIs );
  } else {
    p( "Missing uniquefileidentifier using Album;Artist;Year" );
    $URIs = array();
    $URIs[] = bw_array_get( $result, "_oikt_album", "?" );
    $URIs[] = bw_array_get( $result, "_oikt_artist","?" );
    $URIs[] = bw_array_get( $result, "_oik_year", "?" );
  }
  $URI = implode( ";", $URIs );
  return( $URI );
}

/**
 * Get a unique file identifier from UniqueFileIdentifier or Album, Year, Track
 * @param array $result - array of fields
 * @param 
 */

function oik_tunes_get_UFI( $result, $fileinfo ) {
  $UFI = oik_tunes_get_field( $fileinfo, "uniquefileidentifier" );
  $UFIs = explode( ";", $UFI );
  if ( count( $UFIs ) == 3 ) {
    // array_pop( $URIs );
  } else {
    p( "Missing uniquefileidentifier using Album;Year;Track" );
    $UFIs = array();
    $UFIs[] = bw_array_get( $result, "_oikt_album", "?" );
    $UFIs[] = bw_array_get( $result, "_oikt_artist","?" );
    $UFIs[] = bw_array_get( $result, "_oikt_track", "?" );
  }
  $UFI = implode( ";", $UFIs );
  return( $UFI );
} 

/**
 * Determine the post_id of the oik-recording based on: 
 * We can't really use album title and artist so why not try the MPCI - mediaprimaryclassid as the key
 * Another way would be to have it included in the $result array! 
 */
function oik_tunes_query_recording( $result ) { 
  oik_require( "includes/bw_posts.php" );
  $atts = array();
  $atts['post_type'] = "oik-recording" ;
  $atts['numberposts'] = 1; 
  $atts['meta_key'] = "_oikt_URI";
  $atts['meta_value'] = bw_array_get( $result, "_oikt_URI", null );
  $posts = bw_get_posts( $atts ); 
  $post = bw_array_get( $posts, 0, null );
  bw_trace2( $post, "oik-recording?" );
  if ( !$post ) {
    p( "Creating a new recording for: {$result['_oikt_album']} " );
    $post_id = oik_tunes_create_recording( $result ); 
  } else {
    $post_id = $post->ID; 
    p( "Recording already exists as $post_id" );
  }
  return( $post_id );
}

/**
 * Conjure up a post_date based on the year, track number and duration
 *
 * @return string - post date in format [ Y-m-d H:i:s ] //The time post was made.
 */
function oik_tunes_create_post_date( $result ) {
  $year = $result['_oikt_year'];
  $mon = $result['_oikt_track'];
  if ( $mon > 12 ) { 
    $mon = 12; 
  }
  $day = $result['_oikt_track'];
  $minsec = $result['_oikt_duration'];
  $post_date = bw_format_date( "$year-$mon-$day 00:$minsec", "Y-m-d H:i:s" ); 
  p( $post_date );
  bw_trace2( $post_date, "post_date" );
  return( $post_date );
}

/**
 * Update the track with the "correct" version
 * 
 * What about the content? That may have been updated manually.
 * That's a reason to have used a shortcode ... but we could check the content against the fields and see if it still matches
 * and only replace the content if it does still match.
 */
function oik_tunes_update_track( $post, $result ) {
  $oikt_album = oik_tunes_query_recording( $result );
  // $content = oik_tunes_create_track_content( $result, $oikt_album ); 
  //$post = array( 'post_type' => "oik-track"
  //             , 'post_title' => $result['_oikt_title']
  //             , 'post_name' => $result['_oikt_title']
  //             , 'post_content' => $content
  //             , 'post_status' => 'publish'
  //             , 'post_date' => oik_tunes_create_post_date( $result )  
  //             );
  //}
  /* Set metadata fields */
  $_POST['_oikt_recording'] = $oikt_album;
  $_POST['_oikt_track'] = bw_array_get( $result, "_oikt_track", null );
  $_POST['_oikt_duration'] = bw_array_get( $result, "_oikt_duration", null );
  $_POST['_oikt_composer'] = bw_array_get( $result, "_oikt_composer", null );
  $_POST['_oikt_UFI'] = bw_array_get( $result, "_oikt_UFI", null );
  $post_id = wp_update_post( $post, TRUE );
  bw_trace2( $post_id );
  return( $post->ID );
}

/**
 * Insert an oik-track with the fields provided, creating the album (oik-recording) as required
 <pre>
 result Array
(
    [_oikt_album] => A Night's Tale
    [_oikt_artist] => Caravan
    [_oikt_composer] => Pye Hastings
    [_oikt_duration] => 11:29
    [_oikt_title] => All the Way/A Very Smelly Grubby Little Oik
    [_oikt_track] => 1
    [_oikt_year] => 2003
    [_oikt_publisher] => Classic Rock Legends
    [_oikt_MPCI] => {D1607DBC-E323-4BE2-86A1-48A42A28441E}
    [_oikt_URI] = AMGa_id=R 654019;AMGp_id=P 16292
    [_oikt_UFI] = AMGa_id=R 654019;AMGp_id=P 16292;AMGt_id=T 6661543
)
</pre>

 */
function oik_tunes_insert_track( $result ) {
  $oikt_album = oik_tunes_query_recording( $result );
  $content = oik_tunes_create_track_content( $result, $oikt_album ); 
  $post = array( 'post_type' => "oik-track"
               , 'post_title' => $result['_oikt_title']
               , 'post_name' => $result['_oikt_title']
               , 'post_content' => $content
               , 'post_status' => 'publish'
               , 'post_date' => oik_tunes_create_post_date( $result )  
               );
  /* Set metadata fields */
  $_POST['_oikt_recording'] = $oikt_album;
  $_POST['_oikt_track'] = bw_array_get( $result, "_oikt_track", null );
  $_POST['_oikt_duration'] = bw_array_get( $result, "_oikt_duration", null );
  $_POST['_oikt_composer'] = bw_array_get( $result, "_oikt_composer", null );
  $_POST['_oikt_UFI'] = bw_array_get( $result, "_oikt_UFI", null );
  $post_id = wp_insert_post( $post, TRUE );
  bw_trace2( $post_id );
  return( $post_id );
}

/**
 * Find the track given the fields 
 */
function oik_tunes_query_track( $result ) {
  $oikt_UFI = bw_array_get( $result, "_oikt_UFI", null );
  bw_trace2();
  if ( $oikt_UFI ) {
    oik_require( "includes/bw_posts.php" );
    $args = array( "post_type" => "oik-track"
                 , "meta_key" => "_oikt_UFI"
                 , "meta_value" => $oikt_UFI
                 , "numberposts" => 1
                 );
    $posts = bw_get_posts( $args);
    $post = bw_array_get( $posts, 0, null ); 
  } else {
    $post = null;
    bw_trace2();
  } 
  return( $post );
}
 
/**
 * Create or update the track
 */
function oik_tunes_create_track( $result ) {
  $post = oik_tunes_query_track( $result );
  if ( $post ) {
    $post_id = oik_tunes_update_track( $post, $result );
  } else {
    $post_id = oik_tunes_insert_track( $result );
  }
  return( $post_id );
}  

/**
 * Create an oik-recording 
 */
function oik_tunes_create_recording( $result ) {
  $content = oik_tunes_create_recording_content( $result ); 
  $post = array( 'post_type' => "oik-recording"
               , 'post_title' => $result['_oikt_album']
               , 'post_name' => $result['_oikt_album']
               , 'post_content' => $content
               , 'post_status' => 'publish' 
               , 'post_date' => oik_tunes_create_post_date( $result )  
               );
  $_POST['_oikt_type'] = bw_array_get( $result, "_oikt_format", "CD" );
  $_POST['_oikt_year'] = bw_array_get( $result, "_oikt_year", null );
  $_POST['_oikt_MPCI'] = bw_array_get( $result, "_oikt_MPCI", null );
  $_POST['_oikt_URI'] = bw_array_get( $result, "_oikt_URI", null );
  $post_id = wp_insert_post( $post, TRUE );
  bw_trace2( $post_id );
  return( $post_id );
} 

/**
 * Import the information for a file
 */
function oik_tunes_analyze( $filename, $getID3 ) {
  $filename = str_replace( "\\", "/", $filename );
  $filename = str_replace( "//", "/", $filename );
  $file_exists = file_exists( $filename );
  if ( $file_exists ) { 
    $file_exists = is_file( $filename ); 
    if ( $file_exists ) {
      oik_tunes_analyze_file( $filename, $getID3 );
    } else {
      p( "File $filename is a folder" );
      oik_tunes_analyze_folder( $filename, $getID3 ); 
    }
  } else { 
    p( "File $filename does not exist" );
  }
} 

/**
 * Decides whether or not to process this file
 *  Errs towards yes, rather than no 
 */
function oik_tunes_consider_file( $file ) {
  if ( $file === '.' || $file === '..') {
    $dothis = false;
  } else { 
    $ext = pathinfo( $file, PATHINFO_EXTENSION );
    $ext = strtolower( $ext );
    $nos = array( "jpg" => false
                , "db" => false
                , "ini" => false
                , "" => false 
                );
    $dothis = bw_array_get( $nos, $ext, true );
  }  
  return( $dothis ); 
}

/**
 * Analyze the files in a folder 
 *
 */
function oik_tunes_analyze_folder( $folder, $getID3 ) {
  $cwd = getcwd();
  chdir( $folder );
  $newcd = getcwd();
  $handle = opendir( $newcd );
  while ( $file = readdir( $handle ) ) {
    p( "Processing $file" );
    bw_flush();
    $dothis = oik_tunes_consider_file( $file ); 
    if ( $dothis ) {
      oik_tunes_analyze( $file, $getID3 );
    }
  }
  closedir( $handle );
  chdir( $cwd );
}
