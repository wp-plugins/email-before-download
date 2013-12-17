<?php
/*
Plugin Name: Email Before Download
Plugin URI: http://www.mandsconsulting.com/
Description: This plugin seamlessly integrates two popular plugins (Contact Form 7 and Download Monitor) to create a simple shortcode for requesting an end-user to fill out a form before providing the download URL.  You can use an existing Contact Form 7 form, where you might typically request contact information like an email address, but the questions in the form are completely up to you.  Once the end user completes the form, you can choose to either show a link directly to the download or send an email with the direct link to the email provided in the contact form.
Author: M&S Consulting
Version: 3.2.8
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

 if(!$wpdb->get_row("SHOW COLUMNS
  FROM $table_link
  LIKE 'is_force_download'"))
 $wpdb->query("ALTER TABLE `$table_link` ADD `is_force_download` VARCHAR(4) NULL DEFAULT NULL;");

if($wpdb->get_var("SHOW TABLES LIKE '$table_posted_data'") != $table_posted_data) {

	$sql = "CREATE TABLE " . $table_posted_data . " (
			time_requested bigint(20),
			email VARCHAR(128) NULL,
			user_name VARCHAR(128)CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
                        posted_data text(2000) NOT NULL,
			UNIQUE KEY id (time_requested)
			);";
	$wpdb->query($sql);
}
//check title field collation
$show_query = "SHOW FULL COLUMNS FROM `$table_item` LIKE 'title'"; 
$collation_row = $wpdb->get_row($show_query);
if($collation_row->Collation != 'utf8_unicode_ci'){
  $wpdb->query("ALTER TABLE `$table_item` CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");
}
 if(!$wpdb->get_row("SHOW COLUMNS
  FROM $table_posted_data
  LIKE 'user_name'"))
 $wpdb->query("ALTER TABLE `$table_posted_data` ADD `user_name` VARCHAR(128)CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;");

 if(!$wpdb->get_row("SHOW COLUMNS
  FROM $table_posted_data
  LIKE 'email'"))
 $wpdb->query("ALTER TABLE `$table_posted_data` ADD `email` VARCHAR(128) NULL DEFAULT NULL;");
 

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
  'force_download'=>NULL,
  'email_from'=>NULL,
  'checked'=>NULL,
  'hidden_form'=>NULL,
  'use_radio'=>NULL

 ), $atts));

  global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;

  $str = '';
  $chekboxes = "";
  $chekboxesL = "";
  //$title = '';

  $url = '';
  $hf = '';
  $dldArray = array();
  $table_item = $wpdb->prefix . "ebd_item";
  $is_new_dm = false;
  if($download_id != NULL){
    $ebd_item = $wpdb->get_row( "SELECT * FROM $table_item  WHERE download_id = '".$wpdb->escape($download_id)."';" );
    
    $old_rep = error_reporting(E_ERROR | E_PARSE);;
  
    $pd =  &get_file_data(  WP_PLUGIN_DIR . "/download-monitor/download-monitor.php", array("Version"=>"Version"), 'plugin');
    if(!($pd['Version'])) {
    }
    else $is_new_dm = true;
    
    $new = error_reporting($old_rep);
    
     
    $dldArray = explode(",", $download_id);
    $title_tmp = '';
    foreach ($dldArray as $dl_id) {
      $d = NULL;   
      if(!$is_new_dm){
        $dl = $wpdb->get_row( "SELECT * FROM $wp_dlm_db  WHERE id = ".$wpdb->escape($dl_id).";" );
        $d = new downloadable_file($dl);
      }
      else{
        $d = new stdClass;
        
        $d->title = do_shortcode('[download_data id="'.$dl_id.'" data="title"]');
        
      }
   $checked_state_html = 'checked="true"';
   $checked_state = get_option('email_before_download_chekboxes_state');
   if($checked != NULL){
     $checked_state = $checked;
   }
   if($checked_state == 'no') $checked_state_html = '';

   $checkbox = 'checkbox';
   
   $is_radio = get_option('email_before_download_is_radio');

   if($use_radio !=NULL) 
     $is_radio = $use_radio; 
   
   if($is_radio == 'yes'){
     $checkbox = 'radio';
     $checked_state_html = '';
   }
   
   

      if (!empty($d)) {
            //$date = date("jS M Y", strtotime($d->date));
            if ($title == NULL || $title == '') $title_tmp .= $d->title . '|';
            
         $chekboxes .= '<br />' . $d->title. ' <input type="'.$checkbox.'" '.$checked_state_html.' name="ebd_downloads[]" value="'. $dl_id . '"/>';
         $chekboxesL .= '<br /> <input type="'.$checkbox.'" '.$checked_state_html.' name="ebd_downloads[]" value="'. $dl_id . '"/> '. $d->title;
      }

    }
    if(count($title_tmp) > 0) $title = rtrim($title_tmp, '|');

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
  $contact_form = do_shortcode("[contact-form-7 id=\"$contact_form_id\" \"$title\"]");

  if(strpos($contact_form, 'contact-form-7 404')!== false) $contact_form = do_shortcode("[contact-form id=\"$contact_form_id\" \"$title\"]");

  // add checkboxes if count is more than one
  $hidden = get_option('email_before_download_hidden_form');
   if($hidden_form != NULL){
     $hidden = ($hidden_form == 'yes');
   }  
  
  if (count($dldArray) > 1){
     if($hidden){
       $doc = new DOMDocument();
       $doc->loadXML(xml_character_encode($contact_form));
       $form = $doc->getElementsByTagName('form')->item(0);
       $form_children = array();
       $domElemsToRemove = array();
       foreach ($form->childNodes as $child){
         $domElemsToRemove[] = $child; 
       }
       
       foreach ($domElemsToRemove as $child){
         $form_children[] = $form->removeChild($child);
       }
       
       $f = $doc->createDocumentFragment();
       
       if(strpos($contact_form, "<ebd_left />") !== false)
         $f->appendXML($chekboxesL);
       else
         $f->appendXML($chekboxes);
       $form->appendChild($f);
       
       $hidden_css = 'display:none;';
       $css_option = get_option('email_before_hidden_div_css');
       
       if($css_option){
       $hidden_css = $css_option;
       }
       $hidden_div = $doc->createDocumentFragment();
       $hidden_div->appendXML('<div  id="downloadinputform" style="' . $hidden_css .'" />');
       $hidden_div = $form->appendChild($hidden_div);
       

       foreach ($form_children as $child){
         $hidden_div->appendChild($child);
       }
       
       $contact_form = $doc->saveHTML();
       $js = '<script>
       function countChecked() {
         var n = jQuery( "input:checked[name*=ebd_downloads]" ).length;
         if(n > 0) jQuery( "#downloadinputform" ).show();
         else jQuery( "#downloadinputform" ).hide();
         };
        jQuery(document).ready(function(){
         jQuery( "input[name*=ebd_downloads]" ).on( "click", countChecked );
         countChecked();
       });
       
      </script>';
       $contact_form .= $js;
       
     }
     else {
       $contact_form = str_replace("<ebd />", $chekboxes, $contact_form);
       $contact_form = str_replace("<ebd_left />", $chekboxesL, $contact_form);
     }
  }
  else {
    $contact_form = str_replace("<ebd />", "", $contact_form);
    $contact_form = str_replace("<ebd_left />", "", $contact_form);
  }

  if($delivered_as != NULL)
    $hf .= '<input type="hidden" name="delivered_as" value="' . $delivered_as. '" />';
    //masked
  if($masked != NULL)
    $hf .= '<input type="hidden" name="masked" value="' . $masked. '" />';

  if($force_download != NULL)
    $hf .= '<input type="hidden" name="force_download" value="' . $force_download . '" />';

  if($attachment != NULL)
    $hf .= '<input type="hidden" name="attachment" value="' . $attachment. '" />';
  if($format != NULL)
    $hf .= '<input type="hidden" name="format" value="' . $format. '" />';
  if($email_from != NULL)
    $hf .= '<input type="hidden" name="email_from" value="' . urlencode($email_from) . '" />';


  $hf .= '<input type="hidden" name="_wpcf7_download_id" value="' . $download_id. '" /></form>';

  $contact_form = str_replace("</form>", $hf, $contact_form);

  $wrap_in_div =  get_option('email_before_download_wrap_in_div');
  $div_class = '';
  if(strlen(trim($wrap_in_div)) > 0 ){
    $div_class = 'class="' .  trim($wrap_in_div) . '"';
  }
  
  return "<br/>" . $contact_form .  '<div id="wpm_download_' . $download_id . '" ' . $div_class . ' style="display:none;">  </div>';
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

/* helper function to translate some html entities to safer xml format */
function xml_character_encode($string, $trans='') { 
  $trans = (is_array($trans)) ? $trans : get_html_translation_table(HTML_ENTITIES); 
  $trans2 =array();

  foreach ($trans as $k=>$v) {
    
    if(in_array($v, array('&quot;', '&lt;', '&gt;', '&amp;', '&apos;' ))) { continue;}
    $trans2[$v]= "&#".ord($k).";"; 
  }

  return strtr($string, $trans2); 
} 

/*Function that processes contact form 7, generates links, sends emails */
function ebd_process_email_form( $cf7 ) {
  if(isset( $_POST['_wpcf7_download_id'] )){
    global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;

    $is_new_dm = false;
    $old_rep = error_reporting(E_ERROR | E_PARSE);;
  
    $pd =  &get_file_data(  WP_PLUGIN_DIR . "/download-monitor/download-monitor.php", array("Version"=>"Version"), 'plugin');
    if(!($pd['Version'])) {
    }
    else $is_new_dm = true;
    
    $new = error_reporting($old_rep);
    
    
    //table names
    $table_item = $wpdb->prefix . "ebd_item";
    $table_link = $wpdb->prefix . "ebd_link";
    $table_posted_data = $wpdb->prefix . "ebd_posted_data";

    $delivered_as = get_option('email_before_download_send_email');
    $emailFrom = get_option('email_before_download_email_from');
    if (isset($_POST['email_from'])){
      $emailFrom = htmlspecialchars_decode(urldecode($_POST['email_from']));;
    }

    if (strlen($emailFrom) > 0 )
      $emailFrom = 'From: '. $emailFrom . "\r\n";

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
        if(!$is_new_dm){
          $dl_it = $wpdb->get_row( "SELECT * FROM $wp_dlm_db  WHERE id = ".$wpdb->escape($id).";" );

          $dl_items[] = new downloadable_file($dl_it);
        }
        else {
          $dl_it = new stdClass;
        
          //$dl_it->title = do_shortcode('[download_data id="'.$id.'" data="title"]');
          //$d->url  = do_shortcode('[download_data id="'.$id.'" data="filename"]');
          $dl_tmp = new DLM_Download($id);
          $dl_it->title = $dl_tmp->get_the_title();
          $dl_it->filename  = $dl_tmp->get_file_version()->url;
          $dl_it->id = $id;
          $dl_items[] = $dl_it;
        }
      }

    //get edb items: it's common for all
    $dId = $_POST['_wpcf7_download_id'];
    
    $ebd_item = $wpdb->get_row( "SELECT * FROM $table_item  WHERE id = ".$wpdb->escape($dId).";" );

    $d = null;
    $dl = null; 
    //get single download, multible are comma separated so the $dl for this will be NULL
    if(!$is_new_dm){
      $dl = $wpdb->get_row( "SELECT * FROM $wp_dlm_db  WHERE id = ".$wpdb->escape($ebd_item->download_id).";" );
      $d = new downloadable_file($dl);
    }
    else{
      $d = new stdClass;
      $dl_tmp = new DLM_Download($ebd_item->download_id);
      $d->title = $dl_tmp->get_the_title();
      $d->filename  = $dl_tmp->get_file_version()->url;
    }




    //variable for the title it wll be used only for the single downloads and the email subject
	$title = '';

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
         $innerHtml .= '<a class="icon-button download-icon" target="' . $target . '" href="' . $url .'"><span class="et-icon"><span>' . addslashes( $dl_item->title ). '</span></span></a><br />' ;

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
   else if(!empty($d) || !empty($ebd_item->file) ){
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
      $link_data['selected_id'] = 0;
      if(isset($_POST['masked'])) $link_data['is_masked'] = $_POST['masked'];
      if(isset($_POST['force_download'])) $link_data['is_force_download'] = $_POST['force_download'];
      $wpdb->insert( $table_link, $link_data );

      if(isset($_POST['format']) && $ebd_item->download_id != NULL){
        $link = do_shortcode('[download id="' . $ebd_item->download_id. '" format="' .$_POST['format'].'"]');
        $innerHtml .= $link .   '<br />';
      }
      else {
        $url = WP_PLUGIN_URL."/email-before-download/download.php?dl=".$uid;
        $innerHtml = '<a class="icon-button download-icon" target="' . $target . '" href="' . $url .'"><span class="et-icon"><span>' . addslashes($title) . '</span></span></a><br />' ;
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
      @wp_mail( $cf7->posted_data['your-email'], $email_subject , stripslashes($message), $emailFrom . "Content-Type: text/html\n", $attachments);
      $cf7->additional_settings .= "\n". "on_sent_ok: \"document.getElementById('wpm_download_$dId').style.display = 'inline'; document.getElementById('wpm_download_$dId').innerHTML='The link to the file(s) has been emailed to you.'; \"";
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
      @wp_mail( $cf7->posted_data['your-email'], $email_subject , $message, $emailFrom . "Content-Type: text/html\n", $attachments);
      $cf7->additional_settings .= "\n". "on_sent_ok: \"document.getElementById('wpm_download_$dId').style.display = 'inline'; document.getElementById('wpm_download_$dId').innerHTML='$innerHtml'; \"";
    }
    else{
      $cf7->additional_settings .= "\n". "on_sent_ok: \"document.getElementById('wpm_download_$dId').style.display = 'inline'; document.getElementById('wpm_download_$dId').innerHTML='$innerHtml'; \"";
    }
    // save the extra form information into the xml
     $xml = new SimpleXMLElement('<posted_data></posted_data>');
     foreach ($cf7->posted_data as $key => $value){
      if (is_array($value))
        $value = implode(',', $value);
      $xml->addChild($key, htmlentities($value, ENT_QUOTES,'utf-8'));//encode some chars like '&'
     }
     $posted_data = array();
     $posted_data['time_requested'] = $time_requested;
     $posted_data['posted_data'] = $xml->asXML();
     $posted_data['email'] = $cf7->posted_data['your-email'];
     $posted_data['user_name'] = $cf7->posted_data['your-name'];
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
  register_setting( 'email-before-download-group', 'email_before_download_chekboxes_state' );
  register_setting( 'email-before-download-group', 'email_before_download_forbidden_domains' );
  register_setting( 'email-before-download-group', 'email_before_download_email_from' );
  register_setting( 'email-before-download-group', 'email_before_download_hidden_form' );
  register_setting( 'email-before-download-group', 'email_before_hidden_div_css' );
  register_setting( 'email-before-download-group', 'email_before_download_is_radio' );

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
<strong style="font:bold 18pt Arial;">Email Before Download Options</strong><br/>
<br/>
<strong style="font:bold 14pt Arial;">Email Before Download Log:</strong><br/>
<a style="font:bold 12pt Arial;" href="<?php echo WP_PLUGIN_URL."/email-before-download/export.php"; ?>" target="_blank">Click to export the Email Before Download log as a .CSV file</a><br/>
<br/>
<a href="#" target="_blank" onclick="clearLog();return false;">Click to clear Email Before Download log</a><br/><em>Note: This will permanently delete all Email Before Download log entries from the database.</em><br/>
<script type="text/javascript">
function clearLog(){
	var answer = confirm ("Are you sure you want to clear the log?")
	if(answer){
		jQuery.ajax({
			  type: "POST",
			  url: "<?php echo WP_PLUGIN_URL."/email-before-download/clearlog.php"; ?>",
			  success: function(data){
			    alert(data);
			  }
		});
		return false;
	}
	//else
	//alert ("NO");

}
</script>
<br/>
<strong style="font:bold 14pt Arial;">Support Links:</strong><br/>

<ul>
<li><a href="http://www.mandsconsulting.com/products/wp-email-before-download" target="_blank">Plugin Homepage at M&amp;S Consulting with Live Demos and Test Download</a></li>
<li><a href="http://bit.ly/dF9AxV" target="_blank">Plugin Homepage at WordPress</a></li>
<li><a href="http://bit.ly/lBo3HN" target="_blank">Plugin Changelog: Current and Past Releases</a></li>
<li><a href="http://bit.ly/lU7Tdt" target="_blank">Plugin Support Forums</a></li>
</ul>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="hosted_button_id" value="47FLSBA363KAU" />
<input type="image" name="submit" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" alt="PayPal - The safer, easier way to pay online!" />
<img src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" alt="" width="1" height="1" border="0" />
</form>
<br/>
<strong style="font:bold 14pt Arial;">Configuration Options:</strong><br/>
<br/>
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

        <tr valign="top" class="alert"><td colspan="2"><p class="alert">#9 through #11 only apply if you selected "Send Email" or "Both" as the Delivery Format in #1</p></td></tr>
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
        
        
		<tr valign="top">
		<th scope="row"><p>11. Email Subject</p></th>
		<td><p><input type="test" size="40" name="email_before_download_subject"  value="<?php echo get_option('email_before_download_subject'); ?>"  />
		<br />
		 <font size="-1"><i> If this field is left blank, the default subject is: "Requested URL for the file(s): &lt; file titles &gt;".</i><br />
		 <i>Note: When populating, you can use the following placeholder if you want the file titles to appear in the email subject: [files]. </i><br /></font>
		</p>
		</td>
		</tr>
        
        <tr valign="top" class="alert"><td colspan="2"><p class="alert">#12 through #15 only apply if you have multiple urls in you shortcode</p></td></tr>		
		<tr valign="top">
        <th scope="row"><p>12. Multiple Checkboxes' Default State</p></th>
        <td><p><input type="checkbox" size="40" name="email_before_download_chekboxes_state"  value="no" <?php if(get_option('email_before_download_chekboxes_state')) echo 'checked="checked"'; ?> />
        <br />
         <font size="-1"><i>Select this if you want the default state of the Multiple Checkboxes to be "not checked"</i></font>
        </p>
        </td>
        </tr>
        
		<tr valign="top">
        <th scope="row"><p>13. Hidden Form</p></th>
        <td><p><input type="checkbox" size="40" name="email_before_download_hidden_form"  value="no" <?php if(get_option('email_before_download_hidden_form')) echo 'checked="checked"'; ?> />
        <br />
         <font size="-1"><i>Select this if you want the form to be hidden untill user checks one of the Multiple Checkboxes</i></font>
        </p>
        </td>
        </tr>        

<tr valign="top">
		<th scope="row"><p>14. 'Hidden Form Div Style</p></th>
		<td><p><input type="test" size="40" name="email_before_hidden_div_css"  value="<?php echo get_option('email_before_hidden_div_css'); ?>"  />
		<br />
		 <font size="-1"><i> You can customize the appearance of the hidden form.</i><br />
		 <i>Default is: display:none; . </i><br /></font>
		</p>
		</td>
		</tr>
		
		<tr valign="top">
        <th scope="row"><p>15. Downloads as Radio Buttons</p></th>
        <td><p><input type="checkbox" size="40" name="email_before_download_is_radio"  value="yes" <?php if(get_option('email_before_download_is_radio')) echo 'checked="checked"'; ?> />
        <br />
         <font size="-1"><i>Select this if you want the Multiple Checkboxes to be turned into Radio buttons</i></font>
        </p>
        </td>
        </tr>
        		
		<!--<tr valign="top">
		<th scope="row"><p>12. Email From</p></th>
		<td><p><input type="test" size="40" name="email_before_download_email_from"  value="<?php //echo get_option('email_before_download_email_from'); ?>"  />
		<br />
		 <font size="-1"><i> If this field is left blank, the default wordpress email will be used. Use the following format:My Name &lt;myname@mydomain.com&gt;".</i><br />
		  </i><br /></font>
		</p>
		</td>
		</tr>
    --></table>

    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
Email Before Download started as a plugin we developed for personal in house needs. We realized this could be useful to other WordPress users. At that point, we made the decision to release the plugin for anyone to use. Offering free support has been the only option, but with the increase in popularity our plugin has seen, offering a paid support option will improve our ability to help you as the user.  The WordPress forums will continue to be monitored and updated when we have the chance. If you have a problem or need assistance more rapidly,  now offer that paid support option, at a price of $10.00. With paid support, you will get a personal response from us within 24 hours of submitting a help request. We will work with you to get your issue to resolution, but can.t spend more than 1 hr for the $10. Beyond that, we offer consulting services you can inquire about as well. Click below to pay the $10.00 for our paid support and email us at ebd.support@mandsconsulting.com with your PayPal confirmation number and we will get started.
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="FQTJLT67MLLN6">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>

<?php

}
?>