<?php
namespace Fgms\Communications;

class Model{
  private $wpdb;
  private static $db_name = [
    'submissions'  => 'communications_submissions',
    'results_auth' => 'communications_results_auth'
  ];
  public function __construct ($wpdb ) {
    $this->wpdb = $wpdb;
  }


  public function create_db($table_id,$callback=null){
    if ((function_exists($callback))){
      $sql = call_user_func($callback);
      if ( is_string($sql)){
        // if not in db_name then use a literal string.
        $table_name = empty(self::$db_name[$table_id]) ? $table_id : strip_tags(addslashes(self::$db_name[$table_id]));
        $sql = sprintf(
          $sql,
          $this->wpdb->prefix.$table_name,
          $this->wpdb->get_charset_collate()
        );
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        if ($this->wpdb->print_error()){
          error_log($this->wpdb->print_error());
        }
        return true;
      }
    }
    return false;
  }

  private static function get_contact_form_7_posts(){
    $wp= new \Fgms\WordPress\WordPressImpl();
    $args = [
      'numberposts' => 200,
      'post_type' => 'wpcf7_contact_form',
      'orderby' => 'title',
      'order'   => 'ASC'
    ];
    return $wp->get_posts($args);
  }

  public function get_submmission_results($since_time='7 DAY'){
    $results = [];
    foreach (self::get_contact_form_7_posts() as $post){
      $sql = sprintf(
        'SELECT C_status as `status`, C_email as `client_email`, C_data as `data`, C_gm_date as `submit_date`, C_email_mail_to as `admin_email`
        FROM %s
        WHERE C_form_id ="%d" AND  C_gm_date > (NOW()  - INTERVAL %s)
        ORDER BY C_gm_date  DESC',
        $this->wpdb->base_prefix.self::$db_name['submissions'],
        intval($post->ID),
        $since_time
      );
      $rows = $this->wpdb->get_results($sql, ARRAY_A);
      foreach ($rows as &$row){
        if (!empty($row['data'])){
          $row['data'] = json_decode($row['data'],true);
          $unset_list = ['company_name', 'comm_status', 'comm_accept_link', 'comm_decline_link', 'site_url'];
          foreach ($unset_list as $item){
            if (array_key_exists($item, $row['data'])){
              unset($row['data'][$item]);
            }
          }
        }
      }
      $mail_extra = \Fgms\Communications\Controller::get_mail_extra($post->ID);
      $results[]  = [
        'id'    => $post->ID,
        'title' => $post->post_title,
        'rollup_email' => (empty($mail_extra['email_rollup_address'])) ? '' : $mail_extra['email_rollup_address'],
        'rows'  => $rows
      ];
    }

    return $results;

  }


  public function get_submission_record($code){
    $cdata = array();
    $sql = sprintf(
      'SELECT C_ID, C_email, C_data, C_form_id FROM %s WHERE C_code="%s" AND C_status="ACTIVE"',
      $this->wpdb->base_prefix.self::$db_name['submissions'],
      $code
    );
    $rows = $this->wpdb->get_results($sql);
    if (count($rows) == 1){
      $cdata = $rows[0];
    }
    return $cdata;
  }

  public function update_submission_record($data, $where){
    $date = new \DateTime;
    $data = array_merge($data,['C_update_gm_date' => $date->format('Y-m-d H:i:s')]);
    $result = $this->wpdb->update(
      $this->wpdb->base_prefix.self::$db_name['submissions'],
      $data,
      $where
    );
    if ($result === false){
      error_log('Form submission record update FAILED');
      error_log($this->wpdb->print_error());
    }



  }

  static function create_results_auth_db(){
    $sql =
      'CREATE TABLE %s (
        `A_gm_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `A_email` varchar(255) NOT NULL,
        `A_code` varchar(24) NOT NULL,
        `A_email_message` longtext,
        `A_acl` varchar(50) NOT NULL COMMENT \'Comma Seperated form id\'
      ) %s
    ';
    return $sql;
  }

  static function create_communication_db(){
      $sql =
         'CREATE TABLE %s (
          `C_ID` int(11) NOT NULL AUTO_INCREMENT,
          `C_status` varchar(24) COMMENT \'ACTIVE, ACCEPT, DECLINED, CANCELED\',
          `C_gm_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `C_form_id` int(11) NOT NULL,
          `C_code` varchar(24) NOT NULL,
          `C_email` varchar(255) NOT NULL,
          `C_data` longtext NOT NULL,
          `C_update_gm_date` timestamp,
          `C_resend_flag` varchar(255),
          `C_email_mail_sent` tinyint(1) DEFAULT NULL,
          `C_email_mail_to` varchar(255) NOT NULL,
          `C_email_mail_headers` varchar(255),
          `C_email_mail_message` longtext,
          `C_email_mail_2_sent` tinyint(1) DEFAULT NULL,
          `C_email_mail_2_to` varchar(255),
          `C_email_mail_2_headers` varchar(255) ,
          `C_email_mail_2_message` longtext,
          PRIMARY KEY (C_ID)
        ) %s';
        return $sql;
  }

  public function set_communication_data($contact_form,$mail_extra=array(),$success=false){
    $mail = $contact_form->prop('mail');
    $mail_2 = $contact_form->prop('mail_2');
    $mail_extra = \Fgms\Communications\Controller::get_mail_extra($contact_form->id());
    $requires_response = empty($mail_extra['requires_response']) ? false : $mail_extra['requires_response'];
    // getting data
    $submission = \WPCF7_Submission::get_instance();
    $posted = $submission->get_posted_data();
    $id = empty($posted['_wpcf7']) ? 0 : intval($posted['_wpcf7']);
    $email = empty($posted['email']) ? false : strip_tags(addslashes($posted['email']));
    $status = ($requires_response) ? 'ACTIVE' : null;

    $data = [];
    // stripping wpcf7 data for insertion.
    foreach ($posted as $key=>$value){
      preg_match('/_*wpcf7.*/',$key,$match_contact7_data);
      preg_match('/comm_.*/',$key,$match_web_comm_data);
      if ((count($match_contact7_data) ==  0) AND (count($match_web_comm_data) == 0)){
        $data[$key] = $value;
      }
    }
    $db = [
     'C_status'         => $status,
     'C_form_id'        => $id,
     'C_code'           => $posted['wpcf7_auth_code'],
     'C_email'          => $email,
     'C_data'           => json_encode($data),
     'C_email_mail_to'        => empty($mail['recipient']) ? null : $mail['recipient'],
     'C_email_mail_sent'      => $success,
     'C_email_mail_headers'   => empty($mail['additional_headers']) ? null : $mail['additional_headers'],
     'C_email_mail_message'   => empty($mail['body']) ? null : ($mail['body']),
   ];
   // this means we add it initally because it is active and it doesn't require response.
   if ((!empty($mail_2['active'])) and ($mail_2['active']) and ($require_response == false))  {
     $db = array_merge($db,self::set_communications_data_mail_2($mail_2, $success));
   }
   $this->wpdb->insert($this->wpdb->base_prefix.self::$db_name['submissions'], $db);
   if ($this->wpdb->insert_id == 0){
     error_log($this->wpdb->print_error());
   }
 }

 public function set_communications_data_mail_2($mail_2, $success=false){
   return  [
     'C_email_mail_2_to'      => empty($mail_2['recipient']) ? null : $mail_2['recipient'],
     'C_email_mail_2_headers' => empty($mail_2['additional_headers']) ? null : $mail_2['additional_headers'],
     'C_email_mail_sent'      => $success,
     'C_email_mail_2_message' => empty($mail_2['body']) ? null : ($mail_2['body']),
   ];
 }

 public function set_results_auth(){
   //$code = Controller::get_randomCode(8);
   $rollups = [];
   foreach (self::get_contact_form_7_posts() as $post){
     $mail_extra = $mail_extra = Controller::get_mail_extra($post->ID);
     $email = Controller::validate($mail_extra['email_rollup_address'],'email');
     if (
          (!empty(($mail_extra['email_rollup']))) AND
          ($mail_extra['email_rollup']) AND
          (!empty($email))
        ){
        $day = intval($mail_extra['email_rollup_day']);
        $rollups[$email][$day] = empty($rollups[$email][$day]) ? [$post->ID] : array_merge($rollups[$email][$day],[$post->ID]);
     }
   }
   error_log('results auth '. print_R($rollups,true));
 }

/*
 $output['requires_response'] = (bool) $input['requires_response'];
 $output['manager_email'] =  strip_tags(addslashes($input['manager_email']));
 $output['headers_bcc'] =  strip_tags(addslashes($input['headers_bcc']));
 $output['email_rollup'] = (bool) $input['email_rollup'];
 $output['email_rollup_day'] = intval($input['email_rollup_day']);
 $output['email_rollup_address'] =  strip_tags(addslashes($input['email_rollup_address']));
 $output['twig_template_enable'] = (bool) $input['twig_template_enable'];
*/
}
