<?php
namespace Fgms\Communications;

class Controller
{
  private $wp;
  private $wpdb;
  private $db_version=0;
  private $model;
  private $view;
  private $mail_panel_override=false;

  public function __construct (\Fgms\WordPress\WordPress $wp, $wpdb ) {
    $this->wp = $wp;
    $this->wpdb = $wpdb;
    $this->model = new Model($wpdb);
    $this->view = new View();
    $this->wp_filters();
    $this->wp_actions();
    $this->mail_panel_override = true;
  }


  public function wp_cron(){
    error_log('wp_crond:: ' .print_R(new \DateTime(),true));
  }
  private function wp_filters(){
    /*** Filter updates mail and addds communication panel ***/
    $this->wp->add_filter('wpcf7_editor_panels',function($panels){
        $mail_panel = $this->mail_panel_override ?  ['title'=>__('Mail'),'callback' => '\Fgms\Communications\View::editor_panel_mail'] : $panels['mail-panel'];
        $p = [
          'form-panel'                => $panels['form-panel'],
          'mail-panel'                => $mail_panel,
          'messages-panel'            => $panels['messages-panel'],
          'additional-settings-panel' => $panels['additional-settings-panel'],
          'communications-panel'      => [
            'title'     => __('Web Communicaionts'),
            'callback'  => '\Fgms\Communications\View::editor_panel_web_communications'
          ]
        ];
        return $p;
    }, 20, 3);
    /*** Filter adds random code to mail ----- might put this somewhere else ***/
    $this->wp->add_filter('wpcf7_posted_data',function($data){
      $contact_form = \WPCF7_ContactForm::get_current();
      $code = self::get_randomCode();
      $data['wpcf7_auth_code'] = $code;
      $data = array_merge($data, self::update_email_tags($code));
      return $data;
    },20,3);

    /*** Filter to add twig templates. ***/
    $this->wp->add_filter( 'wpcf7_mail_components',function($components,$form, $mail ){
        $submission = \WPCF7_Submission::get_instance();
        $form_prop = $form->get_properties();
        $form_prop['title'] = $form->title();
        // do nothing, but will have its own template
        $body = $components['body'];
        $mail_extra = self::get_mail_extra($form->id());
        $twig_templates_enable = ((!empty($mail_extra['twig_template_enable'])) AND ($mail_extra['twig_template_enable']));
        if ((class_exists('Timber')) AND ($twig_templates_enable)){
            $message = _('Twig Error %1$s Could not load %2$s template');
            $template = 'wpcf7-email-mail.twig';
            $data = array('posted'=>$submission->get_posted_data(), 'form'=>$form_prop,'body_message'=>$body);

            // adding filter hook to update variables.
            $data = $this->wp->apply_filters('wpcf7_fg_email_data',$data);
            if ((empty($mail_extra['requires_response'])) OR ($mail_extra['requires_response'] == false)){
              $unset_list = ['wpcf7_auth_code', 'company_name', 'comm_status', 'comm_accept_link', 'comm_decline_link'];
              foreach ($unset_list as $item){
                if (array_key_exists($item, $data['posted'])){
                  unset($data['posted'][$item]);
                }
              }
            }
            if ($mail->name() == 'mail_2') {
                $template = 'wpcf7-email-mail-2.twig';
            }

            try {
                $body = \Timber::compile($template, $data );
            }
            catch (\Twig_Error_Loader $e){       }
        }
        $components['body'] = $body;
        return $components;
    },10,3);


  }
  private function wp_actions(){
    /*** Action saves from content, and  ***/
    $this->wp->add_action( 'wpcf7_save_contact_form', function($contact_form, $args, $context){
        $id = empty($args['id']) ? false : $args['id'];
        if (($id !== false ) AND (intval($id) > 0)){
          $mail_extras = isset( $_POST['wpcf7-mail-extra'] )
            ? $this->sanitize_mail_extra( $_POST['wpcf7-mail-extra'] )
            : array();
          $this->wp->update_post_meta($id,'_wpcf7_mail_extra',$mail_extras);
        }
    },10,3);
    /*** Action surpresses client email from being sent  ***/
    $this->wp->add_action( 'wpcf7_before_send_mail', function($contact_form){
      $p = $contact_form->get_properties();
      $mail_extra = self::get_mail_extra($contact_form->id());
      $requires_response = empty($mail_extra['requires_response']) ? false : $mail_extra['requires_response'];

      //this prevents client email from getting sent if it requires a response.
      if ( (!empty($p['mail_2']['active'])) AND ($p['mail_2']['active']) AND ($requires_response) ){
        $p['mail_2']['active'] = false;
        $contact_form->set_properties($p);
      }
    },10);
    /*** Action adds form entry to database with successful Email ***/
    $this->wp->add_action( 'wpcf7_mail_sent', function($contact_form){
      $this->model->set_communication_data($contact_form,true);
    },10 );
    /*** Action adds form entry to database with failed Email ***/
    $this->wp->add_action( 'wpcf7_mail_fail', function($contact_form){
      $this->model->set_communication_data($contact_form,false);
    },10 );
  }

  public static function get_default_email_tags(){
    $wp = new \Fgms\WordPress\WordPressImpl();
    return  [
      'site_url'      => $wp->get_bloginfo('url'),
      'company_name'  => $wp->get_bloginfo('name'),
      'comm_status'   =>  '',
    ];
  }

  /**
  * Produces random code
  * @method static update_email_tags
  * @param code string random code
  * @return array
  **/
  public static function update_email_tags($code='abc123'){
    $et = self::get_default_email_tags();
    $auth_page_id = self::check_for_shortcode('wpcf7_communications_action');
    if ($auth_page_id > 0){
      $et['comm_accept_link'] = get_permalink($auth_page_id).'?request='.$code.'&action=accept';
      $et['comm_decline_link'] = get_permalink($auth_page_id).'?request='.$code.'&action=decline';
    }
    return $et;
  }

  public function get_model() {
    return $this->model;
  }

  public static function validate($value, $type='email'){
    if ($type == 'email'){
      $matches = [];
      if (preg_match('/^[A-Za-z0-9_\\.\\-]+@[A-Za-z0-9_\\.\\-]+\\.[A-Za-z]{2,}$/', $value, $matches)){
        error_log('has match '. $matches[0]);
        return $matches[0];
      };
      return '';
    }
    return false;
  }
  /**
  * Produces random code
  * @method static sanitize_mail_extra
  * @param length int - lenth of random code
  * @return array
  **/
  private function sanitize_mail_extra($input){
    $defaults = $this->wp->wp_parse_args( $defaults, array('requires_response' => false	));
  	$input = $this->wp->wp_parse_args( $input, $defaults );
  	$output = array();
  	$output['requires_response'] = (bool) $input['requires_response'];
    $output['manager_email'] =  self::validate($input['manager_email'],'email');
    $output['headers_bcc'] =  self::validate($input['headers_bcc'],'email');
    $output['email_rollup'] = (bool) $input['email_rollup'];
    $output['email_rollup_day'] = intval($input['email_rollup_day']);
    $output['email_rollup_address'] =  self::validate($input['email_rollup_address'],'email');
    $output['twig_template_enable'] = (bool) $input['twig_template_enable'];
  	return $output;
  }

  /**
  * Produces random code
  * @method static check_for_shortcode
  * @param shortcode string - lenth of random code
  * @return int
  **/
  public static function check_for_shortcode($shortcode='wpcf7_communications_action'){
    $wp = new \Fgms\WordPress\WordPressImpl();
    $id = 0;
    $pages = get_pages();
    foreach ($pages as $page) {
      if (has_shortcode( $page->post_content,$shortcode)){
        $id = $page->ID;
      }
    }
    return $id;
  }

  /**
  * Produces random code
  * @method static get_randomCode
  * @param length int - lenth of random code
  * @return string
  **/
  public static function  get_randomCode($length = 6) {
  	$date = getdate();
  	return  $date['year'] . '-'. $date['yday'] .'-' . substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
  }

  public static function get_mail_extra($id){
    $mail_extra = [];
    $wp = new \Fgms\WordPress\WordPressImpl();
    $mail_extra = $wp->get_post_meta($id,'_wpcf7_mail_extra');
    $mail_extra = empty($mail_extra[0]) ? ['requires_response' => false] : $mail_extra[0];
    return $mail_extra;
  }
}
