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

  global $wpdb,$wp_dlm_root;
  $table_item = $wpdb->prefix . "ebd_item";
  $table_link = $wpdb->prefix . "ebd_link";

  $sql = "SELECT l.item_id as item_id, l.is_downloaded as is_downloaded,
                 l.email as email, l.delivered_as as delivered_as, i.file as filename, i.download_id as download_id
          FROM $table_item i, $table_link l
        WHERE i.id = l.item_id";
  $downloads = $wpdb->get_results($sql);

  $csv = "item_id,email,download_id,filename,delivered_as\n";

  if($downloads){
    foreach($downloads as $d){
      $csv .= $d->item_id . "," .
              $d->email . "," .
              $d->download_id . "," .
              $d->filename . "," .
              $d->delivered_as . "\n" ;
    }
  }

  $size = strlen($csv);
  header("Content-type: text/csv");
  header("Content-Disposition: attachment; filename=download_log_" . date("Y-m-d") . ".csv; size=$size");

 print $csv;
?>