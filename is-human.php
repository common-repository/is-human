<?php
	/*
	Plugin Name: is_human()
	Plugin URI: http://www.pancak.es/plugins/is-human/
	Description: Multi-method verification of humanity for comments and user registration.
	Author: Nick Berlette
	Version: 1.4.2
	Author URI: http://www.pancak.es/
	*/
	class is_human {
	
		var $ih = array();
	
		function __construct () {
			session_start();
			#set it!
			$this->set();
			#update main $ih variable
			$this->get_ih_options();
			#add a status to the dashboard
			add_action('rightnow_end', array(&$this, 'dashboard_status'));
			#add the links to admin panel
			add_action('admin_menu', array(&$this, 'add_pages'));
			#register the widget
			add_action('init', array(&$this, 'widget_register'));
			#add the captcha and check the forms
			
			add_action('comment_post', array(&$this, 'comment_posted'));
			if ($this->ih['show_user_registration'] == '1') {
				add_action('user_register', array(&$this, 'user_registration'));
				add_action('register_form', 'is_human');
			}
			#check for blacklisting
			$blacklist = is_array(unserialize($this->ih['blacklist'])) ? unserialize($this->ih['blacklist']) : array();
			if (in_array($_SERVER['REMOTE_ADDR'], $blacklist)) {
				wp_die('You have been blacklisted from the website for spamming.');
			}
			#initial timestamp for spam stats
			if (!get_option('is_human_install_date')) {
				update_option('is_human_install_date', time());
			}
			#set initial properties after install
			add_action('activate_' . strtr(plugin_basename(__FILE__), '\\', '/'), array(&$this, 'set_initial'));
			#reset all options?
			if ((bool) $_GET['reset']) {
				$this->set_initial();
			}
		}
		
		function set () {
			$this->ih = array(
				'captcha_type' => 'equation',
				'return_only' => '0',
				'reload' => '1',
				'reload_tooltip_text' => 'Reload This',
				'reload_text' => 'Reload This',
				'reload_position' => '1',
				'text_before' => '<br />',
				'text_after' => '<br />',
				'users_skip' => '0',
				'admins_skip' => '1',
				'show_user_registration' => '1',
				'equation_from' => '1',
				'equation_to' => '9',
				'input_class' => '',
				'input_id' => 'code',
				'image_font' => 'arial.ttf',
				'image_font_size' => '16',
				'image_font_color' => 'FFFFFF',
				'image_background' => '222222',
				'image_width' => '100',
				'image_height' => '30',
				'image_class' => 'captcha',
				'questions' => "Which is a living animal?[Cat*|Rock|Sky]\nWhat shape does the Earth resemble?[Sphere*|Cube|Cone]\nWhich is a country?[Russia*|Ocean|Mountain]\nIf Pepperoni is better than Cheese, what is Cheese?[Worse than Pepperoni*|Better than Pepperoni|Yummy]\nI have 5 apples. How many apples do I have?[Five*|Two|Ten]",
				'log_active' => '1',
				'log_comments' => serialize(array()),
				'log_users' => serialize(array()),
				'blacklist' => serialize(array()),
				'admin_logs_per_page' => '10'
			);
		}
		
		function set_initial () {
			$this->set();
			$this->update_ih_options();
		}
		
		function get_ih_options () {
			$ih = array();
			foreach ($this->ih as $option => $value) {
				$ih[$option] = get_option('ih_' . $option);
			}
			$this->ih = $ih;
			return $ih;
		}
		
		function update_ih_options ($options = false) {
			if (count($this->ih) < 5) {
				$this->set();
			}
			foreach ($this->ih as $option => $value) {
				update_option('ih_' . $option, $value);
			}
		}
		
		function comment_posted ($commentID) {
			include_once(dirname(__FILE__) . '../../../../wp-config.php');
			global $userdata;
			if ($this->ih['users_skip'] == '1' && $userdata->user_login !== '') return;
			if ($this->ih['admins_skip'] == '1' && $userdata->user_level == '10') return;
			if (strtoupper($_POST['__is_human_value']) !== strtoupper($_SESSION['__is_human_value'])) {
				#logging
				if ($this->ih['log_active'] == '1') {
					global $userdata;
					get_currentuserinfo();
					if ($userdata->user_login !== '') {
						$author = $userdata->user_login;
						$email = $userdata->user_email;
					} else {
						$author = $_POST['author'];
						$email = $_POST['email'];
					}
					$logs = unserialize($this->ih['log_comments']);
					$logs[] = array(
						'ip' => $_SERVER['REMOTE_ADDR'],
						'name' => $author,
						'email' => $email,
						'comment' => $_POST['comment'],
						'timestamp' => time()
					);
					$this->ih['log_comments'] = serialize($logs);
					$this->update_ih_options();
				}
				#delete it
				wp_delete_comment($commentID);
				wp_die('The entered verification was not correct.');
			}
		}
		
		function user_registration ($userID) {
			include_once(dirname(__FILE__) . '../../../../wp-config.php');
			global $userdata;
			if ($this->ih['users_skip'] == '1' && $userdata->user_login !== '') return;
			if ($this->ih['admins_skip'] == '1' && $userdata->user_level == '10') return;
			if (!function_exists('wp_delete_user')) {
				function wp_delete_user($id) { 
					global $wpdb; 
					$id = (int) $id; 
					$post_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_author = $id"); 
					if ($post_ids) { 
						$post_ids = implode(',', $post_ids); 
						$wpdb->query("DELETE FROM $wpdb->comments WHERE comment_post_ID IN ($post_ids)"); 
						$wpdb->query("DELETE FROM $wpdb->post2cat WHERE post_id IN ($post_ids)"); 
						$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id IN ($post_ids)"); 
						$wpdb->query("DELETE FROM $wpdb->links WHERE link_owner = $id"); 
						$wpdb->query("DELETE FROM $wpdb->posts WHERE post_author = $id"); 
					} 
					$wpdb->query("DELETE FROM $wpdb->users WHERE ID = $id"); 
					do_action('delete_user', $id); 
					return true; 
				} 
			}
			if (strtoupper($_POST['__is_human_value']) !== strtoupper($_SESSION['__is_human_value'])) {
				#logging
				if ($this->ih['log_active'] == '1') {
					$logs = unserialize($this->ih['log_users']);
					$logs[] = array(
						'ip' => $_SERVER['REMOTE_ADDR'],
						'username' => $_POST['user_login'],
						'email' => $_POST['user_email'],
						'timestamp' => time()
					);
					$this->ih['log_users'] = serialize($logs);
					$this->update_ih_options();
				}
				#delete 'em
				wp_delete_user($userID);
				wp_die('The entered verification was not correct.');
			}
		}
		
		function display ($inline = false) {
			$ih = $this->get_ih_options();
			$types = array('equation', 'image', 'questions');
			$type = $types[rand(0, count($types) - 1)];
			$captcha_type = $ih['captcha_type'] == 'random' ? $type : $ih['captcha_type'];
			$return = ($ih['reload'] == '1' && $ih['reload_position'] == '0' && !$inline) ? '<script type="text/javascript" src="/' . PLUGINDIR . '/is-human/js/reload.js"></script><script type="text/javascript">root = \'/' . PLUGINDIR . '/is-human\'; reload_text = \'' . $ih['reload_text'] . '\'; reload_tooltip_text = \'' . $ih['reload_tooltip_text'] . '\';</script>' : '';
			switch ($captcha_type) {
				default: case 'equation':
					$first = rand($ih['equation_from'], $ih['equation_to']);
					$second = rand($ih['equation_from'], $ih['equation_to']);
					$options = array('add', 'subtract');
					if ($first < $second) {
						$total = intval($first) + intval($second);
						$equation = '<strong>' . $first . '</strong> +  <strong>' . $second . '</strong> equals:';
					} elseif ($first >= $second) {
						$total = intval($first) - intval($second);
						$equation = '<strong>' . $first . '</strong> - <strong>' . $second . '</strong> equals:';
					}
					unset($_SESSION['__is_human_value']);
					$_SESSION['__is_human_value'] = $total;
					$extra = $ih['input_id'] ? ' for="' . $ih['input_id'] . '"' : '';
					$return .= '<label' . $extra . '>' . $equation . '</label>';
					$extra = '';
					$extra .= $ih['input_class'] ? ' class="' . $ih['input_class'] . ' is-human"' : ' class="is-human"';
					$extra .= $ih['input_id'] ? ' id="' . $ih['input_id'] . '"' : '';
					$return .= $ih['text_before'] . '<input type="text" name="__is_human_value"' . $extra . ' />' . $ih['text_after'];
				break;
				case 'image':
					$extra = $ih['input_id'] ? ' for="' . $ih['input_id'] . '"' : '';
					$return .= '<label' . $extra . '>Enter the code you see in the image below</label>' . $ih['text_before'] . '<img src="' . get_option('siteurl') . '/' . PLUGINDIR . '/is-human/captcha-image.php?width=' . $ih['image_width'] . '&height=' . $ih['image_height'] . '&font=' . $ih['image_font'] . '&font_size=' . $ih['image_font_size'] . '&font_color=' . $ih['image_font_color'] . '&background=' . $ih['image_background'] . '&' . time() . '" alt="" class="' . $ih['image_class'] . '" style="float: left;" />';
					$extra = '';
					$extra .= $ih['input_class'] ? ' class="' . $ih['input_class'] . ' is-human"' : ' class="is-human"';
					$extra .= $ih['input_id'] ? ' id="' . $ih['input_id'] . '"' : '';
					$return .= '<input type="text" name="__is_human_value"' . $extra . ' />' . $ih['text_after'];
				break;
				case 'questions':
					$questions = array();
					$x = 0;
					foreach (explode("\n", $ih['questions']) as $question) {
						$question = explode('[', str_replace(']', '', $question));
						$answers = explode('|', $question[1]);
						$questions[$x] = array(
							'question' => $question[0],
							'answers' => array()
						);
						$i = 0;
						foreach ($answers as $answer) {
							$key = strpos($answer, '*') ? 'correct' : $i;
							$answer = str_replace('*', '', $answer);
							$questions[$x]['answers'][$key] = $answer;
							$i++;
						}
						$x++;
					}
					$question = $questions[array_rand($questions)];
					unset($_SESSION['__is_human_value']);
					$_SESSION['__is_human_value'] = $question['answers']['correct'];
					$extra = $ih['input_id'] ? ' for="' . $ih['input_id'] . '"' : '';
					$return .= '<label' . $extra . '>' . $question['question'] . '</label>';
					$extra = '';
					$extra .= $ih['input_class'] ? ' class="' . $ih['input_class'] . '"' : '';
					$extra .= $ih['input_id'] ? ' id="' . $ih['input_id'] . '"' : '';
					if (count($question['answers']) > 1) {
						$input = '<select name="__is_human_value"' . $extra . '>';
						$input .= '<option>Select an answer...</option>';
						foreach ($question['answers'] as $key => $answer) {
							$input .= '<option value="' . $answer . '">' . $answer . '</option>';
						}
						$input .= '</select>';
					} else {
						$input = '<input type="text" name="__is_human_value"' . $extra . ' />';
					}
					$return .= $ih['text_before'] . $input . $ih['text_after'];
				break;
			}
			$return .= ($ih['reload'] == '1' && $ih['reload_position'] == '1' && !$inline) ? '<script type="text/javascript" src="/' . PLUGINDIR . '/is-human/js/reload.js"></script><script type="text/javascript">root = \'/' . PLUGINDIR . '/is-human\'; reload_text = \'' . $ih['reload_text'] . '\'; reload_tooltip_text = \'' . $ih['reload_tooltip_text'] . '\';</script>' : '';
			return $return;
		}
		
		function add_pages () {
			$base_url = get_option('siteurl') . '/wp-admin/options-general.php';
			$page = 'is-human/admin.php';
			add_options_page('Manage is_human() Settings', 'is_human()', 8, 'is-human/admin.php', array(&$this, 'option_page'));
		}
		
		function option_page () {
			$base_url = get_option('siteurl') . '/wp-admin/options-general.php';
			$page = 'is-human/admin.php';
			#update information
			if ($_POST['action']) {
				$_POST['log_comments'] = $this->ih['log_comments'];
				$_POST['log_users'] = $this->ih['log_users'];
				if (!$_GET['reset']) {
					foreach ($this->ih as $key => $value) {
						$this->ih[$key] = $key == 'blacklist' ? serialize(explode(',', str_replace(' ', '', $_POST['blacklist']))) : $_POST[$key];
					}
				} else {
					if ($_GET['reset'] || $_POST['reset']) {
						$this->set();
					}
				}
				$this->update_ih_options();
				$updated_message = __('Your verification settings have been <strong>saved</strong>.');
			}
			#display admin page
			require_once(ABSPATH . PLUGINDIR . '/is-human/admin.php');
		}
		
		function get_comments_log ($start = false, $stop = false) {
			$html = "<ul class=\"comments-log\">\n";
			$blacklist = unserialize($this->ih['blacklist']);
			$logs = array_reverse(unserialize($this->ih['log_comments']));
			if (is_array($logs) && count($logs)) {
				if (!$start || !$stop) {
					$start = 0;
					$stop = $this->ih['admin_logs_per_page'] - 1;
				}
				$i = 0;
				foreach ($logs as $log) {
					if (is_array($log)) {
						if ($i >= $start) {
							if ($i <= $stop) {
								$timestamp = date('F jS, Y \a\t g:ia', $log['timestamp']);
								$blacklist_link = in_array($log['ip'], $blacklist) ? '<strong class="right">Blacklisted</strong>' : "<a href=\"#\" onclick=\"blacklist('$log[ip]', '$log[timestamp]'); return false;\" class=\"right\" id=\"blacklist_link_$log[timestamp]\">Blacklist</a>";
								$html .= "\t<li>\n\t\t$blacklist_link\n\t\tBy <strong><a href=\"mailto:$log[email]\">$log[name]</a></strong> (<pre>$log[ip]</pre>) on $timestamp<br />\n\t\t<p>\n\t\t\t$log[comment]\n\t\t</p>\n\t</li>\n";
							}
						}
					}
					$i++;
				}
			} else {
				$html .= "\t<li>\n\t\tThere are no rejected comments to display.\n\t</li>\n";
			}
			$html .= '</ul>';
			return $html;
		}
		
		function get_users_log ($start = false, $stop = false) {
			$html = "<ul class=\"users-log\">\n";
			$logs = @array_reverse(unserialize($this->ih['log_users']));
			$blacklist = is_array(unserialize($this->ih['blacklist'])) ? @unserialize($this->ih['blacklist']) : array();
			if (is_array($logs) && count($logs)) {
				if (!$start || !$stop) {
					$start = 0;
					$stop = $this->ih['admin_logs_per_page'];
				}
				$i = 0;
				foreach ($logs as $log) {
					if (is_array($log)) {
						if ($i >= $start) {
							if ($i <= $stop) {
								$timestamp = date('F jS, Y', $log['timestamp']);
								$blacklist_link = in_array($log['ip'], $blacklist) ? '<strong class="right">Blacklisted</strong>' : "<a href=\"#\" onclick=\"blacklist('$log[ip]', '$log[timestamp]'); return false;\" class=\"right\" id=\"blacklist_link_$log[timestamp]\">Blacklist</a>";
								$html .= "\t<li>\n\t\t$blacklist_link\n\t\t<pre><strong>$log[ip]</strong></pre> attempted to register <strong>$log[name]</strong> with <strong>$log[email]</strong> on $timestamp\n\t\t<br clear=\"all\" />\n\t</li>\n";
							}
						}
					}
					$i++;
				}
			} else {
				$html .= "\t<li>\n\t\tThere are no rejected user registrations to display.\n\t</li>\n";
			}
			$html .= '</ul>';
			return $html;
		}
		
		function dashboard_status () {
			$comment_count = is_array(unserialize($this->ih['log_comments'])) ? number_format(count(unserialize($this->ih['log_comments']))) : 0;
			$users_count = is_array(unserialize($this->ih['log_users'])) ? number_format(count(unserialize($this->ih['log_users']))) : 0;
			$status = sprintf('<a href="http://www.pancak.es/plugins/is-human/" target="_blank">is_human()</a> has rejected <a href="/wp-admin/options-general.php?page=is-human/admin.php&spam=1&comments_log=1">%s comments</a> and <a href="/wp-admin/options-general.php?page=is-human/admin.php&spam=1&users_log=1">%s new users</a> since %s.', $comment_count, $users_count, date('F jS, Y', get_option('is_human_install_date')));
			echo "<p>$status</p>";
		}
		
		function widget_register () {
			if (function_exists('register_sidebar_widget')) {
				register_sidebar_widget('is_human()', array(&$this, 'widget'), null, 'is_human');
				register_widget_control('is_human()', array(&$this, 'widget_control'), null, null, 'is_human');
			}
		}
		
		function widget ($args) {
			extract($args);
			$options = get_option('ih_widget');
			$comment_count = is_array(unserialize($this->ih['log_comments'])) ? number_format(count(unserialize($this->ih['log_comments']))) : 0;
			$users_count = is_array(unserialize($this->ih['log_users'])) ? number_format(count(unserialize($this->ih['log_users']))) : 0;
			echo $before_widget . $before_title . $options['title'] . $after_title;
			printf('<p><a href="http://www.pancak.es/plugins/is-human/">is_human()</a> has blocked <strong>%s comments</strong> and <strong>%s new users</strong> that would have cluttered this website.</p>', $comment_count, $users_count);
			echo $after_widget; 
		}
				
		function widget_control () {
			$options = get_option('ih_widget');
			$new = get_option('ih_widget');
			if ($_POST['is-human-save']) {
				$new['title'] = strip_tags(stripslashes($_POST['is-human-title']));
				if (!$new['title']) {
					$titles = array(
						'Spam-free',
						'Spam is bad',
						'Uncluttered',
						'Humanized',
						"We don't like spam"
					);
					$new['title'] = $titles[rand(0, count($titles) - 1)];
				}
			}
			$options = $options == $new ? $options : $new;
			update_option('ih_widget', $options);
			$title = htmlspecialchars($options['title'], ENT_QUOTES);
			echo '<p><label for="is-human-title">Title:<input style="width: 225px;" id="is-human-title" name="is-human-title" type="text" class="widefat" value="' . $title . '" /></label></p><input type="hidden" name="is-human-save" value="1" />';
		}
		
	}
	$is_human = new is_human;
	#friendly alias for quick display
	function is_human ($inline = false) {
		global $is_human, $userdata;
		$is_human->get_ih_options();
		get_currentuserinfo();
		if ($is_human->ih['users_skip'] == '1' && $userdata->user_login !== '') return;
		if ($is_human->ih['admins_skip'] == '1' && $userdata->user_level == '10') return;
		if (!$inline) {
			$return = $is_human->display();
			if ($is_human->ih['return_only'] == '0') {
				echo '<div id="__is_human_inline">' . $return . '</div>';
			} else {
				return '<div id="__is_human_inline">' . $return . '</div>';
			}
		} else {
			$return = $is_human->display(true);
			if ($is_human->ih['return_only'] == '0') {
				echo $return;
			} else {
				return $return;
			}
		}
	}
?>