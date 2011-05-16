<?php
 //wordpress
  define('WP_USE_THEMES', false);
   $wp_root = dirname(__FILE__) .'/../../../';
   if(file_exists($wp_root . 'wp-load.php')) {
	require_once($wp_root . "wp-load.php");
   } else if(file_exists($wp_root . 'wp-config.php')) {
	require_once($wp_root . "wp-config.php");
  } else {
	exit;
  }

  if ( !current_user_can('manage_options') ) {
		exit(0);
  }

  global $wpdb,$wp_dlm_root, $wp_dlm_db;
  $table_item = $wpdb->prefix . "ebd_item";
  $table_link = $wpdb->prefix . "ebd_link";
  $table_posted_data = $wpdb->prefix . "ebd_posted_data";


  $sql = "SELECT l.item_id as item_id,
  				 l.is_downloaded as is_downloaded,
                 l.email as email,
                 l.delivered_as as delivered_as,
                 i.file as filename,
                 i.download_id as download_id,
                 d.title as title,
                 p.posted_data as posted_data,
                 i.title as item_title,
                 l.time_requested as time_requested
          from
  $table_item i
    left outer join
      $table_link l
        left outer join
          $wp_dlm_db d
          on l.selected_id = d.id
        left outer join
          $table_posted_data p
        on l.time_requested = p.time_requested
    on l.item_id = i.id
    order by l.time_requested desc";
  $downloads = $wpdb->get_results($sql);

  $csv = "item_id,email,download_id,filename,item_title,time_requested,posted_data,delivered_as\n";

  $clean_csv_search = array("\n","\r","\t", ",");
  $clean_csv_replace = array(" "," "," ", ";");

  if($downloads){
    foreach($downloads as $d){
      $csv .= $d->item_id . "," .
              $d->email . "," .
              str_replace(',', ';', $d->download_id ). "," .
              $d->filename . "," .
              $d->item_title . "," .
              date("Y-m-d G:i", $d->time_requested). "," .
              str_replace($clean_csv_search, $clean_csv_replace, $d->posted_data). "," .
              $d->delivered_as . "\n" ;
    }
  }

  $size = strlen($csv);
  header("Content-type: text/csv");
  header("Content-Disposition: attachment; filename=download_log_" . date("Y-m-d") . ".csv; size=$size");

 print $csv;
?>