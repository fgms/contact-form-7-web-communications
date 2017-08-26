<?php
	call_user_func(function () {
		add_shortcode('wpcf7_communications_results',function ($atts, $content)  {
			global $wpdb;
			$model = new \Fgms\Communications\Model($wpdb);
			$results = $model->get_submmission_results('7 DAY');
			ob_start();
			$view = \Fgms\Communications\View::get_submission_html($results);
			$output = ob_get_contents();
			ob_end_clean();
			$model->set_results_auth();
      return $output ;//.'<pre>'. print_R($results,true).'</pre>';
    });


	});
?>
