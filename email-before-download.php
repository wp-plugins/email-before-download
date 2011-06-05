<?php
/*
Plugin Name: Email Before Download
Plugin URI: http://www.mandsconsulting.com/
Description: This plugin seamlessly integrates two popular plugins (Contact Form 7 and Download Monitor) to create a simple shortcode for requesting an end-user to fill out a form before providing the download URL.  You can use an existing Contact Form 7 form, where you might typically request contact information like an email address, but the questions in the form are completely up to you.  Once the end user completes the form, you can choose to either show a link directly to the download or send an email with the direct link to the email provided in the contact form.
Author: M&S Consulting
Version: 2.5
Author URI: http://www.mandsconsulting.com

============================================================================================================
This software is provided "as is" and any express or implied warranties, including, but not limited to, the
implied warranties of merchantibility and fitness for a particular purpose are disclaimed. In no event shall
the copyright owner or contributors be liable for any direct, indirect, incidental, special, exemplary, or
consequential damages(including, but not limited to, procurement of substitute goods or services; loss of
use, data, or profits; or business interruption) however caused and on any theory of liability, whether in
contract, strict liability, or tort(including negligence or otherwise) arising in any way out of the use of
this software, even if advised of the possibility of such damage.

For full license details see license.txt
============================================================================================================ /
/*
 * Init database tables
*/

global $wpdb;

   $table_item = $wpdb->prefix . "ebd_item";
   $table_link = $wpdb->prefix . "ebd_link";
   $table_posted_data = $wpdb->prefix . "ebd_posted_data";

if($wpdb->get_var("SHOW TABLES LIKE '$table_item'") != $table_item) {

	$sql = "CREATE TABLE " . $table_item . " (
			  `id` int(11) NOT NULL auto_increment,
			  `download_id` varchar(128) NULL,
			  `file` varchar(255) NULL,
			  `title` varchar(255) NULL,
			  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  PRIMARY KEY  (`id`)
			)";
	$wpdb->query($sql);

}
if($wpdb->get_var("SHOW TABLES LIKE '$table_link'") != $table_link) {

	$sql = "CREATE TABLE " . $table_link . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			item_id int(11) NOT NULL,
			is_downloaded smallint(3) NOT NULL default '0',
			email VARCHAR(128) NOT NULL,
			expire_time bigint(11),
			time_requested bigint(11),
            uid varchar(255) NOT NULL,
            selected_id BIGINT NOT NULL,
            delivered_as varchar(255) NULL,
			UNIQUE KEY id (id)
			);";
	$wpdb->query($sql);
}

//			time_requested bigint(11),
//ALTER TABLE `wp_ebd_link` ADD `selected_id` VARCHAR( 128 ) NULL AFTER `uid` ;
if(!$wpdb->get_row("SHOW COLUMNS
  FROM $table_link
  LIKE 'selected_id'"))
 $wpdb->query("ALTER TABLE `$table_link` ADD `selected_id` BIGINT NOT NULL ;");
//ALTER TABLE `wp_ebd_link` ADD `time_requested` BIGINT NOT NULL ;
if(!$wpdb->get_row("SHOW COLUMNS
  FROM $table_link
  LIKE 'time_requested'"))
 $wpdb->query("ALTER TABLE `$table_link` ADD `time_requested` BIGINT NOT NULL ;");

 if(!$wpdb->get_row("SHOW COLUMNS
  FROM $table_link
  LIKE 'is_masked'"))
 $wpdb->query("ALTER TABLE `$table_link` ADD `is_masked` VARCHAR(4) NULL DEFAULT NULL;");

if($wpdb->get_var("SHOW TABLES LIKE '$table_posted_data'") != $table_posted_data) {

	$sql = "CREATE TABLE " . $table_posted_data . " (
			time_requested bigint(20),
                        posted_data text(2000) NOT NULL,
			UNIQUE KEY id (time_requested)
			);";
	$wpdb->query($sql);
}
//Shortcode function
function emailreqtag_func($atts) {
 extract(shortcode_atts(array(
  'download_id' => NULL,
  'contact_form_id' => NULL,
  'title' => NULL,
  'file' => NULL,
  'format' => NULL,
  'delivered_as' => NULL,
  'masked'=>NULL,
  'attachment'=>NULL,
 ), $atts));

  global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;

  $str = '';
  $chekboxes = "";
  //$title = '';

  $url = '';
  $hf = '';
  $dldArray = array();
  $table_item = $wpdb->prefix . "ebd_item";
  if($download_id != NULL){
    $ebd_item = $wpdb->get_row( "SELECT * FROM $table_item  WHERE download_id = '$download_id' " );
    $dldArray = explode(",", $download_id);
    $title_tmp = '';
    foreach ($dldArray as $dl_id) {
      $dl = $wpdb->get_row( "SELECT * FROM $wp_dlm_db  WHERE id = ".$wpdb->escape($dl_id).";" );


      $d = new downloadable_file($dl);

      if (!empty($d)) {
            $date = date("jS M Y", strtotime($d->date));
            if ($title == NULL || $title == '') $title_tmp .= $d->title . '|';
            $url = $d->url;
         $chekboxes .= '<br />' . $d->title. ' <input type="checkbox" name="ebd_downloads[]" value="'. $dl_id . '">';
      }

    }
    if(count($title_tmp) > 0) $title = rtrim($title_tmp, '|');
//    rtrim($title, '|');
    if (empty($ebd_item)){
      $wpdb->insert( $table_item, array("download_id"=>$download_id, "title"=>$title) );
      $download_id = $wpdb->insert_id;
      $ebd_item = $wpdb->get_row( "SELECT * FROM $table_item  WHERE id = ".$wpdb->escape($download_id).";" );
    }
    else $download_id = $ebd_item->id;
    //update title if needed
    if(($title != NULL || $title != '') && $ebd_item->title != $title)
      $wpdb->update( $table_item, array("title"=>$title), array("id"=>$download_id) );
  }
  else if($file){

  	if ($title == NULL || $title == '') $title = basename($file);

  // return "<br/>" .  '<div id="wpm_download_" ' . $div_class . ' style="inline;"> ' .$file. ' </div> ';
    $ebd_item = $wpdb->get_row( "SELECT * FROM $table_item  WHERE file = '".$wpdb->escape($file)."';" );

    if (empty($ebd_item)){
      $wpdb->insert( $table_item, array("file"=>$wpdb->escape($file), "title"=>$title) );
      $download_id = $wpdb->insert_id;
      $ebd_item = $wpdb->get_row( "SELECT * FROM $table_item  WHERE file = '".$wpdb->escape($file)."';" );

    }
    else $download_id = $ebd_item->id;
    //update title if needed
    if(($title != NULL || $title != '') && $ebd_item->title != $title)
      $wpdb->update( $table_item, array("title"=>$title), array("id"=>$download_id) );

  }
  $contact_form = do_shortcode("[contact-form $contact_form_id \"$title\"]");
  // add checkboxes if count is more than one
  if (count($dldArray) > 1){
      //$chekboxes $chekboxes
     $contact_form = str_replace("<ebd />", $chekboxes, $contact_form);
  }
  else $contact_form = str_replace("<ebd />", "", $contact_form);

  if($delivered_as != NULL)
    $hf .= '<input type="hidden" name="delivered_as" value="' . $delivered_as. '" />';
    //masked
  if($masked != NULL)
    $hf .= '<input type="hidden" name="masked" value="' . $masked. '" />';

  if($attachment != NULL)
    $hf .= '<input type="hidden" name="attachment" value="' . $attachment. '" />';
  if($format != NULL)
    $hf .= '<input type="hidden" name="format" value="' . $format. '" />';


  $hf .= '<input type="hidden" name="_wpcf7_download_id" value="' . $download_id. '" /></form>';

  $contact_form = str_replace("</form>", $hf, $contact_form);

  $wrap_in_div =  get_option('email_before_download_wrap_in_div');
  $div_class = '';
  if(strlen(trim($wrap_in_div)) > 0 ){
    $div_class = 'class="' .  trim($wrap_in_div) . '"';
  }
  return "<br/>" . $contact_form .  '<div id="wpm_download_' . $download_id . '" ' . $div_class . ' style="display:none;">  </div> ';
}
add_shortcode('emailreq', 'emailreqtag_func');
add_shortcode('email-download', 'emailreqtag_func');

/* Helper functions to check the allowed domains */
function check_domains($haystack, $domains, $offset=0) {
    foreach($domains as $needle) {
      $pos = stripos($haystack, trim($needle), $offset);

      if ($pos !== false) {
        return true;
      }
    }
    return false;
}

/*Function that processes contact form 7, generates links, sends emails */
function ebd_process_email_form( $cf7 ) {
  if(isset( $_POST['_wpcf7_download_id'] )){
    global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;

    //table names
    $table_item = $wpdb->prefix . "ebd_item";
    $table_link = $wpdb->prefix . "ebd_link";
    $table_posted_data = $wpdb->prefix . "ebd_posted_data";

    $delivered_as = get_option('email_before_download_send_email');
    $use_attachments = get_option('email_before_download_attachment');
    if(isset($_POST['delivered_as'])) $delivered_as = $_POST['delivered_as'];
    if(isset($_POST['attachment'])) $use_attachments = trim($_POST['attachment']) == 'yes';

    //check if email is allowed

    $email = $cf7->posted_data['your-email'];
    //compare email againts not allowed domains.
    $forbidden_domains = get_option('email_before_download_forbidden_domains');
    $domains = explode(',', $forbidden_domains);

    if(check_domains($email, $domains)){
      $id = (int) $_POST['_wpcf7'];
	  $unit_tag = $_POST['_wpcf7_unit_tag'];

      $items = array(
				'mailSent' => false,
				'into' => '#' . $unit_tag,
				'captcha' => null );
                //error message
				$items['message'] = "The email that you provided is not allowed. Please provide another one.";
				$on_sent_ok = $cf7->additional_setting( 'on_sent_ok', false );
				$items['onSentOk'] = $on_sent_ok;
      $echo = json_encode( $items );

	  @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
	  echo $echo;
	  die();
    }


    //get selected downloads
    $dIds = $_POST['ebd_downloads'];

    $dl_items = array();
    $multipleLinks = '';
    $message_mult = '';
    $attachments = array();
    $time_requested = time();
    $target = '_blank';

    //get all download monitor objects
    if($dIds)
      foreach($dIds as $id){
        $dl_it = $wpdb->get_row( "SELECT * FROM $wp_dlm_db  WHERE id = ".$wpdb->escape($id).";" );

        $dl_items[] = new downloadable_file($dl_it);
      }

    //get edb items: it's common for all
    $dId = $_POST['_wpcf7_download_id'];
    $ebd_item = $wpdb->get_row( "SELECT * FROM $table_item  WHERE id = ".$wpdb->escape($dId).";" );


    //get single download, multible are comma separated so the $dl for this will be NULL
    $dl = $wpdb->get_row( "SELECT * FROM $wp_dlm_db  WHERE id = ".$wpdb->escape($ebd_item->download_id).";" );
    $d = new downloadable_file($dl);




    //variable for the title it wll be used only for the single downloads and the email subject
	$title = '';
	//echo 'debug: ' . $ebd_item->id . ' ' . $ebd_item->title;
	//print_r($ebd_item);
	$title = $ebd_item->title;

	if($title == NULL || $title == '')
	{
		if($ebd_item->file)
		{
			$title = basename($ebd_item->file);
		}
		else
		{
			$title = $d->title;
		}
	}

   $url = '';
//titles and urls for multiple
   $titles = array();
   $urls = array();

   $innerHtml = '';

  //if checkboxes were selected
   if(count($dl_items) > 0){
     foreach($dl_items as $dl_item){
         //generate unique id for the file (link)
       $uid = md5(uniqid(rand(), true));

        //expiration date if needed if it's 0 or NULL the link will never expire
       $expireAt = 0;
       if(get_option('email_before_download_expire_time') != NULL && get_option('email_before_download_expire_time') != "0")
         $expireAt = strtotime(get_option('email_before_download_expire_time'));

       $link_data = array();
       $link_data['uid'] = $uid;
       $link_data['selected_id'] = $dl_item->id;
       $link_data['expire_time'] = $expireAt;
       $link_data['time_requested'] = $time_requested;
       $link_data['email'] = $cf7->posted_data['your-email'];
       $link_data['item_id'] = $_POST['_wpcf7_download_id'];
       $link_data['delivered_as'] = $delivered_as;
       if(isset($_POST['masked'])) $link_data['is_masked'] = $_POST['masked'];
       $wpdb->insert( $table_link, $link_data );

       //
       $url = WP_PLUGIN_URL."/email-before-download/download.php?dl=".$uid;
       $titles[] = $dl_item->title ;
       $title = implode($titles, '|');
       if(isset($_POST['format'])){
        $link = do_shortcode('[download id="' . $dl_item->id. '" format="' .$_POST['format'].'"]');
        $innerHtml .= $link .   '<br />';
       }
       else
         $innerHtml .= '<a class="icon-button download-icon" target="' . $target . '" href="' . $url .'"><span class="et-icon"><span>' . $dl_item->title . '</span></span></a><br clear="both" /> <br />' ;

//       if(get_option('email_before_download_send_email') == 'Send Email' || get_option('email_before_download_send_email') == 'Both'){
//       }

       if($use_attachments){
         $dirs = wp_upload_dir();
         $uploadpath = trailingslashit( $dirs['baseurl'] );
         $absuploadpath = trailingslashit( $dirs['basedir'] );
         $attachment = NULL;
         if ( $uploadpath && ( strstr ( $dl_item->filename, $uploadpath ) || strstr ( $dl_item->filename, $absuploadpath )) ) {

           $file = str_replace( $uploadpath , "" , $dl_item->filename);
           if(is_file($absuploadpath.$file)){
             $attachment = $absuploadpath.$file;
           }
         }
         $attachments[] = $attachment;
       }

     }
   }
   // single download for the download monitor file or file lnk
   else if(!empty($dl) || !empty($ebd_item->file) ){
    //generate unique id for the file (link)
    $uid = md5(uniqid(rand(), true));

    //expiration date if needed if it's 0 or NULL the link will never expire
    $expireAt = 0;
    if(get_option('email_before_download_expire_time') != NULL && get_option('email_before_download_expire_time') != "0")
      $expireAt = strtotime(get_option('email_before_download_expire_time'));

      $link_data = array();
      $link_data['uid'] = $uid;
      $link_data['expire_time'] = $expireAt;
      $link_data['time_requested'] = $time_requested;
      $link_data['email'] = $cf7->posted_data['your-email'];
      $link_data['item_id'] = $_POST['_wpcf7_download_id'];
      $link_data['delivered_as'] = $delivered_as;
      if(isset($_POST['masked'])) $link_data['is_masked'] = $_POST['masked'];
      $wpdb->insert( $table_link, $link_data );

      if(isset($_POST['format']) && $ebd_item->download_id != NULL){
        $link = do_shortcode('[download id="' . $ebd_item->download_id. '" format="' .$_POST['format'].'"]');
        $innerHtml .= $link .   '<br />';
      }
      else {
        $url = WP_PLUGIN_URL."/email-before-download/download.php?dl=".$uid;
        $innerHtml = '<a class="icon-button download-icon" target="' . $target . '" href="' . $url .'"><span class="et-icon"><span>' . $title . '</span></span></a><br clear="both" /> <br />' ;
     }
   }
   //nothing is selected for the download
   else {
   //we don't sent an email and throw an error
     $cf7->skip_mail = true;
     //this message doesn't seem to appear but we leave it for now
     $cf7->additional_settings = "on_sent_ok: \"document.getElementById('wpm_download_$dId').style.display = 'inline'; document.getElementById('wpm_download_$dId').innerHTML='You should select the files to dowload.'; \"";
     $id = (int) $_POST['_wpcf7'];
	 $unit_tag = $_POST['_wpcf7_unit_tag'];

     $items = array(
				'mailSent' => false,
				'into' => '#' . $unit_tag,
				'captcha' => null );
                //error message
				$items['message'] = "Please select at least one of the documents";
				$on_sent_ok = $cf7->additional_setting( 'on_sent_ok', false );
				$items['onSentOk'] = $on_sent_ok;
     $echo = json_encode( $items );

	 @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
	 echo $echo;
	 die();
   }
   $cf7->posted_data['your-message'] = 'The downloaded file name(s): ' . $title;
   if(strpos($cf7->mail['body'], "[your-message]") === false ){
      $cf7->posted_data['your-message'] =  $title;
      $cf7->mail['body']  = $cf7->mail['body'] ."\nThe downloaded file name: [your-message]";

    }



    $target = get_option('email_before_download_link_target');
    $html_before = get_option('email_before_download_html_before_link');
    $html_after = get_option('email_before_download_html_after_link');


    //if multiple files are downloaded ???
    $message = '';
    if(count($dl_items) > 0){
      $email_template = get_option('email_before_download_email_template_mult');
      if(strlen(trim($email_template)) > 0){
       $message = str_replace(array('[requesting_name]', '[file_urls]'), array($cf7->posted_data['your-name'], $innerHtml), trim($email_template));
      }
      else  $message = $innerHtml;
    }
    else {
      $email_template = get_option('email_before_download_email_template');
      if(strlen(trim($email_template)) > 0){
        if(isset($_POST['format']) && $ebd_item->download_id != NULL)
          $message = 'You requested: ' .$innerHtml;
        else
          $message = str_replace(array('[requesting_name]', '[file_url]', '[file_name]'), array($cf7->posted_data['your-name'], $url, $title), trim($email_template));
      }
      else  {
        if(isset($_POST['format']) && $ebd_item->download_id != NULL)
          $message = 'You requested: ' .$innerHtml;
        else
          $message = '<a class="icon-button download-icon" target="' . $target . '" href="' . $url .'">' . $title . '</a>';
      }
    }

    //$title = "Click this link to download this file.";
    $innerHtml = $html_before . $innerHtml . $html_after;


    if($delivered_as == 'Send Email') {
      // $attachments = NULL;
      if($use_attachments && count($dl_items) == 0) {
        $dirs = wp_upload_dir();
        $uploadpath = trailingslashit( $dirs['baseurl'] );
        $absuploadpath = trailingslashit( $dirs['basedir'] );
        $attachment = NULL;
        if ( $uploadpath && ( strstr ( $d->filename, $uploadpath ) || strstr ( $d->filename, $absuploadpath )) ) {

          $file = str_replace( $uploadpath , "" , $d->filename);
          if(is_file($absuploadpath.$file)){
            $attachment = $absuploadpath.$file;
          }
        }
        $attachments = array($attachment);
      }
      if(count($attachments) == 0) $attachments = NULL;
      $email_subject = get_option('email_before_download_subject');
      if(strlen(trim($email_subject)) > 0){
      	$email_subject = str_replace('[files]', $title, $email_subject);
      }
      else $email_subject = 'Requested URL for the file(s): '. $title;
      //email_before_download_subject
      @wp_mail( $cf7->posted_data['your-email'], $email_subject , $message, "Content-Type: text/html\n", $attachments);
      $cf7->additional_settings = "on_sent_ok: \"document.getElementById('wpm_download_$dId').style.display = 'inline'; document.getElementById('wpm_download_$dId').innerHTML='The link to the file(s) has been emailed to you.'; \"";
    }
    else if ($delivered_as == 'Both'){
      //$attachments = NULL;
      if($use_attachments && count($dl_items) == 0) {
        $dirs = wp_upload_dir();
        $uploadpath = trailingslashit( $dirs['baseurl'] );
        $absuploadpath = trailingslashit( $dirs['basedir'] );
        $attachment = NULL;
        if ( $uploadpath && ( strstr ( $d->filename, $uploadpath ) || strstr ( $d->filename, $absuploadpath )) ) {

          $file = str_replace( $uploadpath , "" , $d->filename);
          if(is_file($absuploadpath.$file)){
            $attachment = $absuploadpath.$file;
          }
        }
        $attachments = array($attachment);
      }
	  if(count($attachments) == 0) $attachments = NULL;
	  $email_subject = get_option('email_before_download_subject');
      if(strlen(trim($email_subject)) > 0){
      	$email_subject = str_replace('[files]', $title, $email_subject);
      }
      else $email_subject = 'Requested URL for the file(s): '. $title;
      @wp_mail( $cf7->posted_data['your-email'], $email_subject , $message, "Content-Type: text/html\n", $attachments);
      $cf7->additional_settings = "on_sent_ok: \"document.getElementById('wpm_download_$dId').style.display = 'inline'; document.getElementById('wpm_download_$dId').innerHTML='$innerHtml'; \"";
    }
    else{
      $cf7->additional_settings = "on_sent_ok: \"document.getElementById('wpm_download_$dId').style.display = 'inline'; document.getElementById('wpm_download_$dId').innerHTML='$innerHtml'; \"";
    }
    // save the extra form information into the xml
     $xml = new SimpleXMLElement('<posted_data></posted_data>');
     foreach ($cf7->posted_data as $key => $value){
      $xml->addChild($key, $value);
     }
     $posted_data = array();
     $posted_data['time_requested'] = $time_requested;
     $posted_data['posted_data'] = $xml->asXML();
     $wpdb->insert( $table_posted_data, $posted_data );
  }

  return $cf7;
}
add_action( 'wpcf7_before_send_mail', 'ebd_process_email_form' );

add_action('admin_menu', 'ebd_plugin_menu');

function ebd_plugin_menu() {

  add_options_page('Email Before Download Options', 'Email Before Download', 'manage_options', 'email-before-download', 'email_before_download_options');
  add_action( 'admin_init', 'register_email_before_download_settings' );

}

add_filter( 'plugin_action_links', 'ebd_plugin_action_links', 10, 2 );

function ebd_plugin_action_links( $links, $file ) {
	if ( $file != plugin_basename( __FILE__ ))
		return $links;

	$settings_link = '<a href="options-general.php?page=email-before-download">' . __( 'Settings', 'email-before-download' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

function register_email_before_download_settings() {
  register_setting( 'email-before-download-group', 'email_before_download_link_target' );
  register_setting( 'email-before-download-group', 'email_before_download_wrap_in_div' );
  register_setting( 'email-before-download-group', 'email_before_download_html_before_link' );
  register_setting( 'email-before-download-group', 'email_before_download_html_after_link' );
  register_setting( 'email-before-download-group', 'email_before_download_send_email' );
  register_setting( 'email-before-download-group', 'email_before_download_email_template' );
  register_setting( 'email-before-download-group', 'email_before_download_email_template_mult' );
  register_setting( 'email-before-download-group', 'email_before_download_expire_time' );
  register_setting( 'email-before-download-group', 'email_before_download_hide' );
  register_setting( 'email-before-download-group', 'email_before_download_attachment' );
  register_setting( 'email-before-download-group', 'email_before_download_subject' );
  register_setting( 'email-before-download-group', 'email_before_download_forbidden_domains' );

}

function email_before_download_options() {

  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }

?>
<style type="text/css">
.ebd th, .ebd td
{
vertical-align:top;
}
.ebd th
{
	text-align: left;
	padding-right:8px;
}
.ebd .alert
{
	padding:8px;
	font:bold 12pt Arial;
	border:1px dashed red;
}
</style>

<div class="wrap">
<h2>Email Before Download Options</h2>
<p>
<a href="<?php echo WP_PLUGIN_URL."/email-before-download/export.php"; ?>" target="_blank">Click to export the Email Before Download log as a .CSV file</a><br/>
<br/>
<a href="http://www.mandsconsulting.com/products/wp-email-before-download" target="_blank">Plugin Homepage at M&amp;S Consulting</a><br/>
<a href="http://bit.ly/dF9AxV" target="_blank">Plugin Homepage at WordPress</a><br/>
<a href="http://bit.ly/lBo3HN" target="_blank">Plugin Changelog: Current and Past Releases</a><br/>
<a href="http://bit.ly/lU7Tdt" target="_blank">Plugin Support Forums</a><br/>
</p>
<form method="post" action="options.php">
    <?php settings_fields( 'email-before-download-group' ); ?>
    <table class="optiontable ebd">
        <tr valign="top">
        <th scope="row"><p>1. Delivery Format</p></th>
        <td><p>
        <select name="email_before_download_send_email">
         <option value="Inline Link" <?php if(get_option('email_before_download_send_email') == 'Inline Link') echo 'selected="selected"'; ?> >Inline Link</option>
         <option value="Send Email" <?php if(get_option('email_before_download_send_email') == 'Send Email') echo 'selected="selected"'; ?> >Send Email</option>
         <option value="Both" <?php if(get_option('email_before_download_send_email') == 'Both') echo 'selected="selected"'; ?> >Both</option>
        </select><br />
        </p>
       </td>
        </tr>


        <tr valign="top">
        <th scope="row"><p>2. Hide/Mask Real Link</p></th>
        <td><p><input type="checkbox" size="40" name="email_before_download_hide"  value="1" <?php if(get_option('email_before_download_hide')) echo 'checked="checked"'; ?> />
        <br /> <font size="-1"><i>Hide/mask the real file link from the user.  It requires the cURL php extention to be enabled, which is done by uncommenting it from the php.ini file.  <a href="#"
            onclick="window.open('<?php echo WP_PLUGIN_URL."/email-before-download/checkcurl.php" ?>','Check cURL','menubar=no,width=430,height=360,toolbar=no'); return false;">Check if cURL is enabled</a>.</i></font>
            </p>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><p>3. Link Expiration Time (only applies if #2 is checked)</p></th>
        <td><p>
        <select name="email_before_download_expire_time">
         <option value="0" <?php if(get_option('email_before_download_expire_time') == '0') echo 'selected="selected"'; ?> >Never</option>
         <option value="+1 minute" <?php if(get_option('email_before_download_expire_time') == '+1 minute') echo 'selected="selected"'; ?> >1 min</option>
         <option value="+3 minute" <?php if(get_option('email_before_download_expire_time') == '+3 minute') echo 'selected="selected"'; ?> >3 min</option>
         <option value="+10 minute" <?php if(get_option('email_before_download_expire_time') == '+10 minute') echo 'selected="selected"'; ?> >10 min</option>
         <option value="+30 minute" <?php if(get_option('email_before_download_expire_time') == '+30 minute') echo 'selected="selected"'; ?> >30 min</option>
         <option value="+1 hour" <?php if(get_option('email_before_download_expire_time') == '+1 hour') echo 'selected="selected"'; ?> >1 hr</option>
         <option value="+12 hour" <?php if(get_option('email_before_download_expire_time') == '+12 hour') echo 'selected="selected"'; ?> >12 hr</option>
         <option value="+1 day" <?php if(get_option('email_before_download_expire_time') == '+1 day') echo 'selected="selected"'; ?> >1 day</option>
         <option value="+1 week" <?php if(get_option('email_before_download_expire_time') == '+1 week') echo 'selected="selected"'; ?> >1 week</option>
        </select>
        <br /> <font size="-1"><i>If you are masking the link by checking #2, this option will expire the link sent the user so if they click it again (or send it to someone else who tries) after the specified period, they will have to submit a new form.</i></font>
        </p>
       </td>
        </tr>

        <tr valign="top">
        <th scope="row"><p>4. Forbidden Email Domains</p></th>
        <td><p><textarea cols="40" rows="10" name="email_before_download_forbidden_domains" ><?php echo get_option('email_before_download_forbidden_domains'); ?></textarea>
        <br />
         <font size="-1"><i> You can enter here the comma separated list of the forbidden domains </i><br />
         <i> </i><br /></font>
        </p>
        </td>
        </tr>


        <tr valign="top"><td colspan="2"><p class="alert">#5 through #8 only apply if you selected "Inline Link" or "Both" as the Delivery Format in #1</p></td></tr>
        <tr valign="top">
        <th scope="row"><p>5. Inline Link Target</p></th>
        <td><p>
        <select name="email_before_download_link_target">
         <option value="_blank" <?php if(get_option('email_before_download_link_target') == '_blank') echo 'selected="selected"'; ?> >_blank</option>
         <option value="_self" <?php if(get_option('email_before_download_link_target') == '_self') echo 'selected="selected"'; ?> >_self</option>
        </select> <br />
         <font size="-1"><i>If "_self" is selected link will open in the same browser window/tab, if "_blank" is selected the link will open in the new browser window/tab</i></font>
         </p>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><p>6. Inline Link Custom CSS</p></th>
        <td><p><input type="text" size="40" name="email_before_download_wrap_in_div" value="<?php echo get_option('email_before_download_wrap_in_div'); ?>" />
        <br /> <font size="-1"><i>CSS class used to render the div and the link (this is only used if you choose to display the link inline in #5)</i></font>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><p>7. HTML Before Inline Link</p></th>
        <td><p><input type="text" name="email_before_download_html_before_link" size="40" value="<?php echo get_option('email_before_download_html_before_link'); ?>" /><br />
         <font size="-1"><i>HTML you want to be added before the link</i></font>
        </p>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><p>8. HTML After Inline Link</p></th>
        <td> <p><input type="text" size="40" name="email_before_download_html_after_link" value="<?php echo get_option('email_before_download_html_after_link'); ?>" />
        <br /><font size="-1"><i>HTML you want to be added after the link</i></font>
        </p>
        </td>
        </tr>

        <tr valign="top" class="alert"><td colspan="2"><p class="alert">#9 through #10 only apply if you selected "Send Email" or "Both" as the Delivery Format in #1</p></td></tr>
        <tr valign="top">
        <th scope="row"><p>9. Email Template</p> 9.1  - single url</th>
        <td><textarea cols="40" rows="10" name="email_before_download_email_template"><?php echo get_option('email_before_download_email_template'); ?> </textarea><br />
<i>You can use the following placeholders: [requesting_name], [file_url] and [file_name]. </i><br />
<i>So if you, for example, don't provide the [file_url] placeholder, the
<br />user will not receive any link. Here is an example of the template:<br /><br />
<b> Hello [requesting_name], <br />

Here is the download for &lt;a href="[file_url]"&gt;[file_name]&lt;/a&gt; that you requested.<br />

Sincerely,<br />

My Company name </b>
<br /><br /> Note. If you leave this field empty, an email containing only the file URL will be sent.

 </i>
<br />
        </td>
        </tr>
        <tr valign="top">
        <th scope="row"> 9.2 - multiple urls</th>
         <td>
<textarea cols="40" rows="10" name="email_before_download_email_template_mult"><?php echo get_option('email_before_download_email_template_mult'); ?> </textarea><br />
<i>You can use the following placeholders for multiple urls: [file_urls] </i><br />
        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><p>10. Attachment</p></th>
        <td><p><input type="checkbox" size="40" name="email_before_download_attachment"  value="1" <?php if(get_option('email_before_download_attachment')) echo 'checked="checked"'; ?> />
        <br />
         <font size="-1"><i>"Attachment" can only be applied to the files uploaded using Download Monitor plugin.</i></font>
        </p>
        </td>
        </tr>

    </table>

    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php

}
?>