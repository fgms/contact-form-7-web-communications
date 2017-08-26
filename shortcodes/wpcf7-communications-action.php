<?php
	call_user_func(function () {
		add_shortcode('wpcf7_communications_action',function ($atts, $content)  {

      function sanatize_requests($raw){
        $data = [];
        if (!empty($raw['action'])){
          if ($raw['action'] == 'accept'){
            $data['action'] = 'ACCEPT';
          }
          if ($raw['action'] == 'decline'){
            $data['action'] = 'DECLINED';
          }
        }
        if ((!empty($raw['request'] )) and (preg_match('/^20\\d{2}-\\d{1,3}-[a-zA-Z0-9]{6}$/', $raw['request'], $matches))){
          $data['request'] = $raw['request'];
        }
        return $data;
      }

      $accept_message = (empty($atts['accept_message'])) ? 'You have accepted %s request' : strip_tags(addslashes($atts['accept_message']));
      $decline_message = (empty($atts['decline_message'])) ? 'You have declined %s request' : strip_tags(addslashes($atts['decline_message']));
      $not_found_message =(empty($atts['not_found_message'])) ? 'Sorry no records match this query.' : strip_tags(addslashes($atts['not_found_message']));

      $diag = [];
      $post = sanatize_requests($_REQUEST);

      if ((!empty($post['request'])) AND (!empty($post['action']))){
				global $wpdb;
				$model = new \Fgms\Communications\Model($wpdb);
				$row = $model->get_submission_record($post['request']);
				if (is_object($row)){
					$cid = $row->C_ID;
          $cemail = $row->C_email;
          $cdata = json_decode($row->C_data,true);
          $formid = $row->C_form_id;
					$message = $post['action'] == 'ACCEPT' ? sprintf($accept_message,$cemail) : sprintf($decline_message,$cemail);
					$cdata['comm_status'] = $message;
					add_filter( 'wpcf7_special_mail_tags',function($special, $tagname, $html) use($cdata){
            return $cdata[$tagname];
          },10,3);

					$contact_form = wpcf7_contact_form($formid);
					$mail_2 = $contact_form->prop( 'mail_2' );
					$mail_status = \WPCF7_Mail::send($mail_2, 'mail_2');
					$diag['cdata']=$cdata;
					$submit_data = array_merge(['C_status' => $post['action']],\Fgms\Communications\Model::set_communications_data_mail_2($mail_2,$mail_status));
					$model->update_submission_record(
						$submit_data,
            ['C_ID' => $cid,'C_status' =>'ACTIVE']
					);
				}
				else {
					$message = $not_found_message;
				}
      }
			//	Content is ignored
			$atts['content'] = do_shortcode($content);
			return $atts['content'] .$message. '<pre>'.print_R($diag,true).'</pre>' ;
		});
	});
?>
