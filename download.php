<?php
   $wp_root = dirname(__FILE__) .'/../../../';
   if(file_exists($wp_root . 'wp-load.php')) {
	require_once($wp_root . "wp-load.php");
   } else if(file_exists($wp_root . 'wp-config.php')) {
	require_once($wp_root . "wp-config.php");
  } else {
	exit;
  }	


  //get file id
  $dId = $_REQUEST['dl'];

 //
  global $wpdb;
  $table_item = $wpdb->prefix . "ebd_item";
  $table_link = $wpdb->prefix . "ebd_link";
  $ebd_link = $wpdb->get_row( "SELECT * FROM $table_link  WHERE uid = '".esc_sql($dId)."';" );
  
  $dld = null;
   $is_new_dm = false;
    $old_rep = error_reporting(E_ERROR | E_PARSE);;
  
    $pd =  &get_file_data(  WP_PLUGIN_DIR . "/download-monitor/download-monitor.php", array("Version"=>"Version"), 'plugin');
    if(!($pd['Version'])) {
    }
    else $is_new_dm = true;

    $new = error_reporting($old_rep);
    
  if($ebd_link->expire_time != NULL && $ebd_link->expire_time != 0 && $ebd_link->expire_time < time()){
    @header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
    wp_die( sprintf(__('The link you are trying to access is expired. <br/><br/><a href="%1$s"><strong>← Back to %2$s</strong></a>', "email-before-download"), get_bloginfo('url'), get_bloginfo('name')), __('The link you are trying to access is expired.',"email-before-download"));
  }
  $is_force_download = $ebd_link->is_force_download == 'yes' || $ebd_link->is_force_download == 'true';
  if($ebd_link->selected_id != NULL && $ebd_link->selected_id != 0){
    $dl = $wpdb->get_row( "SELECT * FROM $wp_dlm_db  WHERE id = ".esc_sql($ebd_link->selected_id).";" );
    $file = '';
    if(!$is_new_dm){
      $downloads = get_downloads('include='.$ebd_link->selected_id.'');
      $file = $downloads[0]->url;
    }
    else $file = do_shortcode('[download_data id="'.$ebd_link->selected_id.'" data="download_link"]');

    $wpdb->update( $table_link, array("is_downloaded"=>1), array("uid"=>esc_sql($dId)) );
    header("Location: $file");
    exit(0);
  }
  $ebd_item = $wpdb->get_row( "SELECT * FROM $table_item  WHERE id = ".esc_sql($ebd_link->item_id).";" );

  $is_masked = get_option('email_before_download_hide');
  //is the "hide" option overriden for the individual download
  if($ebd_link->is_masked != NULL)
    $is_masked = $ebd_link->is_masked == 'yes' || $ebd_link->is_masked == 'true';

  if($is_force_download){
    $is_masked = true;
  }
  $file = '';
  if($ebd_item->file){
   $file = $ebd_item->file;
  }
  if($ebd_item->download_id){
    if(!$is_new_dm){
      $dl = $wpdb->get_row( "SELECT * FROM $wp_dlm_db  WHERE id = ".esc_sql($ebd_item->download_id).";" );

      //another way of getting downloads from download monitor
      $downloads = get_downloads('include='.$ebd_item->download_id.'');

      $d = new downloadable_file($dl);
      $file = $downloads[0]->url;

      //if the link is masked use the real path of the DM file
      if ($is_masked && function_exists('curl_init')) $file = $d->filename;
    }
    else{
      //$file = do_shortcode('[download_data id="'.$ebd_item->download_id.'" data="download_link"]');
      if ($is_masked && function_exists('curl_init')) {
         $dld = new DLM_Download($ebd_item->download_id);
         
         $file  = $dld->get_file_version()->url;
         if(!isset($_SERVER['HTTP_RANGE'])){
           $dld->get_file_version()->increase_download_count();
         }
      }
      else $file = do_shortcode('[download_data id="'.$ebd_item->download_id.'" data="download_link"]');
     }
  }
  $wpdb->update( $table_link, array("is_downloaded"=>1), array("uid"=>esc_sql($dId)) );


//Check if the cUrl functions are available and the url hide option is enabled.
//If not, just rederect to real file url.

if ($is_masked && function_exists('curl_init')) {
$filesize = 0;  
if($dld == null){
   $curl = curl_init();
   $url = $file; 
   $options = array
   (
     CURLOPT_URL=>$url,
     CURLOPT_HEADER=>true,
     CURLOPT_RETURNTRANSFER=>true,
     CURLOPT_NOBODY=>TRUE,
   );
  curl_setopt_array($curl,$options);    
  $r = curl_exec ($curl);
  $header_size = curl_getinfo($curl,CURLINFO_HEADER_SIZE);
  $header = substr($r, 0, $header_size);
  $body = substr( $r, $header_size );

  curl_close ($curl);

  
  $my_headers = ebd_parse_headers ( $header );

 
  $regex = '/Content-Length:\s([0-9].+?)\s/';
  $count = preg_match($regex, $header, $matches);
	 
  $filesize =   isset($matches[1]) ? $matches[1] : "";

$dirs = wp_upload_dir();
 $uploadpath = trailingslashit( $dirs['baseurl'] );
 $absuploadpath = trailingslashit( $dirs['basedir'] );
 
 if ( $uploadpath && ( strstr ( $file, $uploadpath ) || strstr ( $file, $absuploadpath )) ) {
   $file = str_replace( $uploadpath , "" , $file);
   if(is_file($absuploadpath.$file)){
     $file = $absuploadpath.$file;
   }
   else {
   //
     @header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
     wp_die( sprintf(__('The file you are trying to download is not available. <br/><br/><a href="%1$s"><strong>← Back to %2$s</strong></a>', "email-before-download"), get_bloginfo('url'), get_bloginfo('name')), __('The link you are trying to access is expired.',"email-before-download"));
   }
 }
  
foreach($my_headers as $key=>$value){
  if($key == 'Location') continue;
  header("$key: $value");  

}
}
else{
  $filesize = $dld->get_file_version()->filesize;
  $mimetypes =  get_allowed_mime_types();

  $mime_type = 'application/force-download';
  foreach(get_allowed_mime_types() as $mime => $type) {
			$mimes = explode( '|', $mime );
			if (strpos($mime, $dld->get_file_version()->filetype) !== false) {
				$mime_type = $type;
				break;
			}
	}

$dirs = wp_upload_dir();
 $uploadpath = trailingslashit( $dirs['baseurl'] );
 $absuploadpath = trailingslashit( $dirs['basedir'] );
 
 if ( $uploadpath && ( strstr ( $file, $uploadpath ) || strstr ( $file, $absuploadpath )) ) {
   $file = str_replace( $uploadpath , "" , $file);
   if(is_file($absuploadpath.$file)){
     $file = $absuploadpath.$file;
   }
   else {
   //
     @header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
     wp_die( sprintf(__('The file you are trying to download is not available. <br/><br/><a href="%1$s"><strong>← Back to %2$s</strong></a>', "email-before-download"), get_bloginfo('url'), get_bloginfo('name')), __('The link you are trying to access is expired.',"email-before-download"));
   }
 }
 
	header( "Robots: none" );
	header( "Content-Type: " . $mime_type );
	header( "Content-Description: File Transfer" );

}
//
//HTTPRange support
  $size =$filesize;
  $begin=0;
  $end=$size;
 
  if(isset($_SERVER['HTTP_RANGE']))
  { if(preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches))
    { $begin=intval($matches[0]);
      if(!empty($matches[1]))
        $end=intval($matches[1]);
    }
  }
 
  if($begin>0||$end<$size)
    header('HTTP/1.0 206 Partial Content');
  else
    header('HTTP/1.0 200 OK');  
 
  

header('Accept-Ranges: bytes');

header("Content-Range: bytes $begin-$end/$size"); 

$base_file_name = basename($file);
if (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE") != false) { 
  $base_file_name = urlencode(basename($file));
  $is_force_download = TRUE;
}
if($is_force_download)
  header("Content-Disposition: attachment; filename=\"" . $base_file_name . "\"");
else header("Content-Disposition: filename=\"" . $base_file_name . "\"");

header('Content-Length:'.($end-$begin));



$chunksize = 1 * (1024 * 1024); // how many bytes per chunk
if ($filesize > $chunksize) {
  $handle = fopen($file, 'rb');
  $buffer = '';
  // If it's a large file we don't want the script to timeout, so:
  @set_time_limit(0);
  fseek($handle,$begin,0);
  while (!feof($handle)) {

    
    $buffer = fread($handle, $chunksize);
    echo $buffer;
    ob_flush();
    flush();
  }
  fclose($handle);
} else {
  readfile($file);
} 

 
  exit(0);

}
else {
   header("Location: $file");
}

    function ebd_parse_headers( $header )
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach( $fields as $field ) {
            if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }
		

?>