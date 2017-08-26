<?php
namespace Fgms\Communications;

class View{

  public static $wp;
  public $model;
  public function __construct ( ) {
    global $wpdb;
    $model = new Model($wpdb);
  }




  public static function editor_panel_mail ($post){
      $mail_extra = \Fgms\Communications\Controller::get_mail_extra($post->id());
      $id = 'wpcf7-mail';
      $args = [
        'id' => $id,
    		'name' => 'mail',
    		'title' => __( 'Admin Mail', 'contact-form-7' ),
    		'use' => null,
        'fields' => [
          [
            'label' =>'<h3 style="margin-bottom:0;font-weight: 400;">Property Response</h3>'
          ],
          [
            'label'   => '<label>Enable</label>',
            'input_html'  => sprintf('<label for="%1$s-requires-response"><input type="checkbox" id="%1$s-requires-response" name="wpcf7-mail-extra[requires_response]"  value="1" %2$s  /> Activate "Accept/Decline"  email communications </label>',
              $id,
              $mail_extra['requires_response']  ? ' checked="checked"' : ''
            )
          ],
          [
            'label' => sprintf('<label for="%s-manager-email">Supervisor Email</label>',$id),
            'input_html' => sprintf('<input type="text" id="%s-manager-email" name="wpcf7-mail-extra[manager_email]" class="large-text code" size="50"  value="%s" />',
            $id,
            $mail_extra['manager_email']
            )
          ],
          [
            'label' => sprintf('<label for="%s-headers-bcc">Bcc Email</label>',$id),
            'input_html' => sprintf('<input type="text" id="%s-headers-bcc" name="wpcf7-mail-extra[headers_bcc]" class="large-text code" size="50"  value="%s" />',
            $id,
            $mail_extra['headers_bcc']
            )
          ],
          [
            'label' =>'<h3 style="margin-bottom:0;font-weight: 400;">Rollup Report</h3>'
          ],
          [
            'label'   => '<label>Enable</label>',
            'input_html'  => sprintf('<label for="%1$s-email-rollup"><input type="checkbox" id="%1$s-email-rollup" name="wpcf7-mail-extra[email_rollup]"  value="1" %2$s  />Activate weekly rollup reports, delivery at @12:00pm</label>',
              $id,
              $mail_extra['email_rollup']  ? ' checked="checked"' : ''
            )
          ],
          [
            'label' => sprintf('<label for="%s-email-rollup-day">Delivery Day</label>',$id),
            'input_html' => sprintf('
              <select id="%s-email-rollup-day" name="wpcf7-mail-extra[email_rollup_day]">
                <option value="1" '. (($mail_extra['email_rollup_day'] == 1) ? 'selected="selected"': '') .' >Monday</option>
                <option value="2" '. (($mail_extra['email_rollup_day'] == 2) ? 'selected="selected"': '') .' >Tuesday</option>
                <option value="3" '. (($mail_extra['email_rollup_day'] == 3) ? 'selected="selected"': '' ).'>Wednesday</option>
                <option value="4" '. (($mail_extra['email_rollup_day'] == 4) ? 'selected="selected"': '' ).' >Thursday</option>
                <option value="5" '. (($mail_extra['email_rollup_day'] == 5) ? 'selected="selected"': '') .'  >Friday</option>
                <option value="6" '. (($mail_extra['email_rollup_day'] == 6) ? 'selected="selected"': '') .'  >Saturday</option>
                <option value="7" '. (($mail_extra['email_rollup_day'] == 7) ? 'selected="selected"': '' ).'  >Sunday</option>
              </select>
            ',
            $id
            )
          ],
          [
            'label' => sprintf('<label for="%s-manager-email">Recipient</label>',$id),
            'input_html' => sprintf('<input type="text" id="%s-rollup-email" name="wpcf7-mail-extra[email_rollup_address]" class="large-text code" size="50"  value="%s" />',
            $id,
            $mail_extra['email_rollup_address']
            )
          ],
          [
            'label' =>'<h3 style="margin-bottom:0;font-weight: 400;">Twig Templates</h3>'
          ],
          [
            'label'   => '<label>Enable</label>',
            'input_html'  => sprintf('<label for="%1$s-twig_template_enable"><input type="checkbox" id="%1$s-twig_template_enable" name="wpcf7-mail-extra[twig_template_enable]"  value="1" %2$s  />Active twig template emails</label>',
              $id,
              $mail_extra['twig_template_enable']  ? ' checked="checked"' : ''
            )
          ]
        ]
      ];


      $args = apply_filters('wpcf7_web_comm_mail_panel_args',$args);

      self::editor_panel_mail_setup( $post, $args);
    	echo '<br class="clear" />';
    	self::editor_panel_mail_setup( $post, array(
    		'id' => 'wpcf7-mail-2',
    		'name' => 'mail_2',
    		'title' => __( 'Client Mail', 'contact-form-7' ),
    		'use' => __( 'Use Client Mail', 'contact-form-7' ) ) );
  }

  public static function editor_panel_mail_setup2($post, $args){
    ?>
    <h2>Mail setup test</h2>
    <?php
  }
  public static function editor_panel_mail_setup($post, $args=''){
    self::$wp = new \Fgms\WordPress\WordPressImpl();

  	$id = esc_attr( $args['id'] );
  	$mail = self::$wp->wp_parse_args( $post->prop( $args['name'] ), array(
  		'active' => false,
  		'recipient' => '',
  		'sender' => '',
  		'subject' => '',
  		'body' => '',
  		'additional_headers' => '',
  		'attachments' => '',
  		'use_html' => false,
  		'exclude_blank' => false,
  	) );


    // should clean code up foreach loop.

    $additional_tags = '';
    foreach( Controller::update_email_tags() as $key=>$value){
      $additional_tags .= sprintf('<span class="mailtag code used">[%s]</span>',$key);
    }
      ?>
    <?php if (! empty($args['fields'])) : ?>

      <div style="border:1px solid #999;padding:15px;margin-bottom: 30px;background-color:#fefefe">
        <h2 style="font-size: 2em;">Configuration</h2>
        <table class="form-table" >
          <tbody>
            <?php foreach ($args['fields'] as $field ) : ?>
              <tr>
                <? if (!empty($field['input_html'])) : ?>
                <th scope="row"><?php echo (empty($field['label']) ? '' : $field['label'])?></th>
                <td>
                <?php echo $field['input_html'] ?>
                </td>
                <?php else: ?>
                  <th scope="row" colspan="2"><?php echo $field['label'] ?></th>
                <?php endif; ?>

              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <pre><?php //echo htmlspecialchars(print_R($args['fields'],true)) ?></pre>
    <?php endif; ?>
    <div class="contact-form-editor-box-mail" id="<?php echo $id; ?>">
    <h2><?php echo esc_html( $args['title'] ); ?></h2>
    <strong><?php echo $page_id_of_sc; ?></strong>
    <?php
    	if ( ! empty( $args['use'] ) ) :
    ?>
    <label for="<?php echo $id; ?>-active"><input type="checkbox" id="<?php echo $id; ?>-active" name="<?php echo $id; ?>[active]" class="toggle-form-table" value="1"<?php echo ( $mail['active'] ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( $args['use'] ); ?></label>
    <p class="description"><?php echo esc_html( __( "", 'contact-form-7' ) ); ?></p>
    <?php
    	endif;
    ?>

    <fieldset>
    <legend><?php echo esc_html( __( "In the following fields, you can use these mail-tags:", 'contact-form-7' ) ); ?><br />
    <?php $post->suggest_mail_tags( $args['name'] ); echo $additional_tags ?></legend>

    <table class="form-table">
    <tbody>
      <?php  	if ( ! empty( $args['use'] ) ) :
        $page_id_of_sc = \Fgms\Communications\Controller::check_for_shortcode('wpcf7_communications_action');//check_for_accept_decline_shortcode();
        if (true):
          if ($page_id_of_sc > 0):
            ?>
            <tr>
              <th scope="row"></th>
              <td>
                Found Authorization page at <?php echo '<a target="_blank" href="' .self::$wp->get_permalink($page_id_of_sc) .'">'.self::$wp->get_permalink($page_id_of_sc).'</a>' ?>
              </td>
            </tr>
            <?php
          else:
            ?>
            <p><strong style="color:#ff0000;">NO Authorizatoin Page Found</strong></p>
            <p>Please create an authorization page with the following shortcode [wpcf7_communications_action]</p>
            <?php
          endif;
        endif;

     ?>

      <?php endif; ?>
    	<tr>
    	<th scope="row">
    		<label for="<?php echo $id; ?>-recipient"><?php echo esc_html( __( 'To', 'contact-form-7' ) ); ?></label>
    	</th>
    	<td>
    		<input type="text" id="<?php echo $id; ?>-recipient" name="<?php echo $id; ?>[recipient]" class="large-text code" size="70" value="<?php echo esc_attr( $mail['recipient'] ); ?>" data-config-field="<?php echo sprintf( '%s.recipient', esc_attr( $args['name'] ) ); ?>" />
    	</td>
    	</tr>

    	<tr>
    	<th scope="row">
    		<label for="<?php echo $id; ?>-sender"><?php echo esc_html( __( 'From', 'contact-form-7' ) ); ?></label>
    	</th>
    	<td>
    		<input type="text" id="<?php echo $id; ?>-sender" name="<?php echo $id; ?>[sender]" class="large-text code" size="70" value="<?php echo esc_attr( $mail['sender'] ); ?>" data-config-field="<?php echo sprintf( '%s.sender', esc_attr( $args['name'] ) ); ?>" />
    	</td>
    	</tr>

    	<tr>
    	<th scope="row">
    		<label for="<?php echo $id; ?>-subject"><?php echo esc_html( __( 'Subject', 'contact-form-7' ) ); ?></label>
    	</th>
    	<td>
    		<input type="text" id="<?php echo $id; ?>-subject" name="<?php echo $id; ?>[subject]" class="large-text code" size="70" value="<?php echo esc_attr( $mail['subject'] ); ?>" data-config-field="<?php echo sprintf( '%s.subject', esc_attr( $args['name'] ) ); ?>" />
    	</td>
    	</tr>

    	<tr>
    	<th scope="row">
    		<label for="<?php echo $id; ?>-additional-headers"><?php echo esc_html( __( 'Additional Headers', 'contact-form-7' ) ); ?></label>
    	</th>
    	<td>
    		<textarea id="<?php echo $id; ?>-additional-headers" name="<?php echo $id; ?>[additional_headers]" cols="100" rows="4" class="large-text code" data-config-field="<?php echo sprintf( '%s.additional_headers', esc_attr( $args['name'] ) ); ?>"><?php echo esc_textarea( $mail['additional_headers'] ); ?></textarea>
    	</td>
    	</tr>
    	<tr>
    	<th scope="row">
    		<label for="<?php echo $id; ?>-body"><?php echo esc_html( __( 'Message Body', 'contact-form-7' ) ); ?></label>
    	</th>
    	<td>
    		<textarea id="<?php echo $id; ?>-body" name="<?php echo $id; ?>[body]" cols="100" rows="18" class="large-text code" data-config-field="<?php echo sprintf( '%s.body', esc_attr( $args['name'] ) ); ?>"><?php echo esc_textarea( $mail['body'] ); ?></textarea>

    		<p><label for="<?php echo $id; ?>-exclude-blank"><input type="checkbox" id="<?php echo $id; ?>-exclude-blank" name="<?php echo $id; ?>[exclude_blank]" value="1"<?php echo ( ! empty( $mail['exclude_blank'] ) ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Exclude lines with blank mail-tags from output', 'contact-form-7' ) ); ?></label></p>

    		<p><label for="<?php echo $id; ?>-use-html"><input type="checkbox" id="<?php echo $id; ?>-use-html" name="<?php echo $id; ?>[use_html]" value="1"<?php echo ( $mail['use_html'] ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Use HTML content type', 'contact-form-7' ) ); ?></label></p>
    	</td>
    	</tr>

    	<tr>
    	<th scope="row">
    		<label for="<?php echo $id; ?>-attachments"><?php echo esc_html( __( 'File Attachments', 'contact-form-7' ) ); ?></label>
    	</th>
    	<td>
    		<textarea id="<?php echo $id; ?>-attachments" name="<?php echo $id; ?>[attachments]" cols="100" rows="4" class="large-text code" data-config-field="<?php echo sprintf( '%s.attachments', esc_attr( $args['name'] ) ); ?>"><?php echo esc_textarea( $mail['attachments'] ); ?></textarea>
    	</td>
    	</tr>
    </tbody>
    </table>
    </fieldset>
    </div>
    <?php
  }



  public static function editor_panel_web_communications($post, $args=''){
    ?>
      <h2>Web Communications</h2>
      <p>This is where all of the different forms would show up for stats (number of entries, last entry, csv download)</p>
    <?php
  }


  public static function get_submission_html($forms){
    ?>
    <ul class="form-submission-tables">
    <?php foreach ($forms as $form ) : ?>
      <li >
        <?php echo self::get_submission_table($form); ?>
        <div><a class="btn btn-primary" >Download CSV</a>
      </li>
    <?php endforeach; ?>
    <style>
      ul.form-submission-tables {
        margin-top: 30px;
      }
      .form-submission-tables li h3 {
        margin: 0 0 8px;
      }

      .form-submission-tables li {
        margin-bottom: 30px;
        padding: 24px;
        border: 1px solid #ccc;
      }
    </style>
    <?php
  }

  public static function get_submission_table($form){
    ?>
    <h3><?php echo esc_html($form['title']); ?></h3>
    <p><em>For last 7 days</em></p>
    <table class="form-submission-table table">
      <thead>
        <tr>
          <th  style="min-width: 50px;">Status</th>
          <th style="min-width: 150px;">Date</th>
          <th style="width: 15%">Email</th>
          <th style="width: 65%">Data</th>
          <th style="width: 15%">Admin Email</th>
        </tr>
    <?php foreach ($form['rows'] as $row ) :
        $date_string = (!empty($row['submit_date'])) ? $row['submit_date'] : 'NOW';
        $date = new \DateTime($date_string);

    ?>
      <tr >
        <td><?php if (!empty($row['status'])): echo $row['status']; endif; ?></td>
        <td><?php echo $date->format('M d, Y H:i') ?></td>
        <td><?php if (!empty($row['client_email'])): echo $row['client_email'];endif; ?></td>
        <td>
        <?php foreach ($row['data'] as $key=>$value) : ?>
            <div class="form-data" style="font-size:0.8em; line-height: 1.5em;"><strong><?php echo esc_html($key); ?></strong> <span> <?php echo esc_html($value); ?> </span></div>
        <?php endforeach; ?>
        </td>
        <td><?php if (!empty($row['admin_email'])): echo $row['admin_email'];endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </table>
    <?php
  }

}
