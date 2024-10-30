<?php
	#parse options to $ih array
	global $is_human;
	$is_human->get_ih_options();
	$ih = array();
	foreach ($is_human->ih as $option => $value) {
		$option = str_replace('ih_', '', $option);
		$ih[$option] = $value;
	}
?>
	<script type="text/javascript">
		var answers = [], xml = make_xml();
		var logs_per_page = <?php echo $ih['admin_logs_per_page'] ? $ih['admin_logs_per_page'] : 10; ?>;
		function $ (id) {
			return document.getElementById(id);	
		}
		function toggle (id) {
			$(id).style.display = $(id).style.display == 'none' ? 'block' : 'none';
		}
		function add_answer () {
			if ($('gen_new_answer').value.length > 0) {
				answers.push({'answer': $('gen_new_answer').value, 'correct': $('gen_new_answer_correct').value});
				$('gen_new_answer').value = '';
				$('gen_new_answer_correct').value = 'incorrect';
				update_list();
			}
		}
		function remove_answer (id) {
			answers.splice(id, 1);
			update_list();
		}
		function update_list () {
			$('gen_answers').innerHTML = '';
			$('gen_answers_label').style.display = $('gen_answers').style.display = answers.length > 0 ? 'block' : 'none';
			for (i in answers) {
				answer = answers[i];
				new_answer = document.createElement('li');
				new_answer.innerHTML = answer['answer'] + ' (<strong>' + answer['correct'] + '</strong>) <a href="#" onclick="remove_answer(' + i + '); return false;"><?php _e('Remove'); ?></a>';
				$('gen_answers').appendChild(new_answer);
			}
		}
		function generate () {
			if ($('gen_question').value.length > 0 && answers.length > 0) {
				answers_string = '';
				for (i in answers) {
					answers_string += answers[i]['answer'] + (answers[i]['correct'] == 'correct' ? '*' : '') + (i != answers.length - 1 ? '|' : '');
				}
				question = '\n' + $('gen_question').value + '[' + answers_string + ']';
				$('questions_list').value += question;
				$('questions_list').style.backgroundColor = '#FFFFDC';
				answers = [];
				$('gen_question').value = '';
				update_list();
				$('question_generator').style.display = 'none';
				alert('<?php _e('Your question has been added to the highlited list!'); ?>');
			}
		}
		function update_captcha () {
			$('image_preview').innerHTML = '<img src="<?php echo get_option('siteurl') . '/' . PLUGINDIR; ?>/is-human/captcha-image.php?width=' + $('image_width').value + '&height=' + $('image_height').value + '&font=' + $('image_font').value + '&font_size=' + $('image_font_size').value + '&font_color=' + $('image_font_color').value + '&background=' + $('image_background').value + '" />';
		}
		function color_preview () {
			colors = ['image_background', 'image_font_color'];
			for (i = 0; i < colors.length; i++) {
				if (typeof($(colors[i] + '_preview')) != 'undefined') {
					$(colors[i] + '_preview').style.backgroundColor = '#' + $(colors[i]).value;
					if (parseInt($(colors[i]).value) <= 777777) {
						$(colors[i] + '_preview').style.color = '#FFFFFF';
					} else {
						$(colors[i] + '_preview').style.color = '#000000';
					}
				}
			}
		}
		function blacklist (ip, timestamp) {
			list = $('blacklist').value;
			if (!list.match(ip)) {
				$('blacklist').value += (list.substr(list.length - 1) == ',' || list == '' ? ip : ',' + ip);
				$('blacklisting').style.display = 'block';
				$('blacklist').style.backgroundColor = '#FFFFDC';
				$('blacklist_link_' + timestamp).style.fontWeight = 'bold';
				$('blacklist_link_' + timestamp).style.color = '#333333';
				$('blacklist_link_' + timestamp).style.textDecoration = 'none';
				$('blacklist_link_' + timestamp).style.cursor = 'default';
				$('blacklist_link_' + timestamp).innerHTML = '<?php _e('Blacklisted'); ?>';
				window.location.hash = 'blacklisting';
			}
		}
		function logs_switch_page (page_number, type, total, current_page) {
			children = current_page.parentNode.childNodes;
			for (i in children) {
				children[i].className = 'page';	
			}
			current_page.className = 'page current';
			xml.open('get', '/<?php echo PLUGINDIR; ?>/is-human/engine.php?action=log-reload&type=' + type + '&page=' + page_number);
			xml.onreadystatechange = function () {
				if (xml.readyState == 4) {
					$('log_' + type + '_inline').innerHTML = xml.responseText;
				} else {
					$('log_' + type + '_inline').innerHTML = '<img src="/<?php echo PLUGINDIR; ?>/is-human/img/loading.gif" alt="<?php _e('Loading'); ?>..." />';	
				}
			}
			xml.send(null);
		}
		function logs_paging (type, total) {
			total_pages = Math.ceil(total / logs_per_page);
			if (total > 0 && total_pages > 1) {
				document.write('<ul id="log_' + type + '_paging" class="paging"></ul><br clear="all" />');
				paging = $('log_' + type + '_paging');
				for (i = 1; i <= total_pages; i++) {
					page = document.createElement('li');
					page.className = 'page';
					page.id = 'log_' + type + '_page_' + i;
					page.innerHTML = '<a href="#" onclick="logs_switch_page(' + i + ', \'' + type + '\', ' + total + ', this.parentNode); return false;">' + i + '</a>';
					paging.appendChild(page);
				}
			}
		}
		function reset_logs (type) {
			xml.open('get', '/<?php echo PLUGINDIR; ?>/is-human/engine.php?action=log-reset&type=' + type);
			xml.onreadystatechange = function () {
				if (xml.readyState == 4) {
					try {
						$('log_' + type + '_paging').parentNode.removeChild($('log_' + type + '_paging'));
					} catch (e) {}
					$('log_' + type + '_inline').innerHTML = xml.responseText;
				} else {
					$('log_' + type + '_inline').innerHTML = '<img src="/<?php echo PLUGINDIR; ?>/is-human/img/loading.gif" alt="<?php _e('Loading'); ?>..." />';
				}
			}
			xml.send(null);
		}
		function make_xml () {
			if (typeof XMLHttpRequest == 'undefined') {
				objects = Array(
					'Microsoft.XMLHTTP',
					'MSXML2.XMLHTTP',
					'MSXML2.XMLHTTP.3.0',
					'MSXML2.XMLHTTP.4.0',
					'MSXML2.XMLHTTP.5.0'
				);
				for (i in objects) {
					try {
						return new ActiveXObject(objects[i]);
					} catch (e) {}
				}
			} else {
				return new XMLHttpRequest();
			}
		}
		setInterval('color_preview()', 200);
	</script>
	<style type="text/css">
		.input {
			font-family: sans-serif;
			font-size: 11px;
			padding: 3px;
			margin: 6px 0px 8px 0px;
			border: 1px solid #DDDDDD;
			border-top: 1px solid #CCCCCC;
		}
		/*.button {
			font-family: sans-serif;
			font-size: 16px;
			font-weight: bold;
			padding: 3px;
			cursor: pointer;
			margin: 3px 0px;
		}*/
		.ih-header {
			margin-bottom: 16px;
		}
		.install {
			margin-bottom: 0px;
			padding: 4px 6px 12px 6px;
			border-bottom: 1px solid #CCCCCC;
		}
		#install {
			background-color: #E4F2FD;
			display: none;
			margin-bottom: 8px;
			padding: 12px 6px 12px 6px;
			border-bottom: 1px solid #CCCCCC;
		}
		pre {
			display: inline;
		}
		code {
			font-weight: bold;
			background-color: #FFFFCD;
			display: block;
			float: left;
			margin-top: 10px;
			padding: 4px;
			border: 1px solid #FFBBAA;
		}
		fieldset {
			background-color: #FAFAFA;
			margin: 4px 0px 8px 0px;
			position: relative;
			border: 2px solid #CCCCCC;
			-moz-border-radius: 6px;
			-webkit-border-radius: 6px;
		}
			fieldset legend, fieldset .reset {
				background-color: #E4F2FD;
				padding: 2px 6px 4px 6px;
				border: 1px solid #CCCCCC;
				-moz-border-radius: 3px;
				-webkit-border-radius: 3px;
			}
			fieldset .reset {
				position: absolute;
				top: -26px;
				right: 16px;
			}
			fieldset .comments-log {
				margin: 0px;
				padding: 0px;
				list-style: none;
			}
				fieldset .comments-log li {
					background-color: #EEEEEE;
					margin: 2px;
					padding: 8px;
					border-bottom: 1px solid #DDDDDD;
					-moz-border-radius: 4px;
					-webkit-border-radius: 4px;
				}
					fieldset .comments-log li p {
						font-size: 1.1em;
						margin: 3px 0px 0px 8px;
					}
					fieldset .comments-log li .right {
						float: right;
					}
			fieldset .users-log {
				margin: 0px;
				padding: 0px;
				list-style: none;
			}
				fieldset .users-log li {
					background-color: #EEEEEE;
					margin: 2px;
					padding: 8px;
					border-bottom: 1px solid #DDDDDD;
					-moz-border-radius: 4px;
					-webkit-border-radius: 4px;
				}
					fieldset .users-log li .right {
						float: right;
					}
			fieldset fieldset {
				background-color: #FFFFFF;
				border: 1px solid #CCCCCC;
			}
		.selector {
			text-align: center;
			background-color: #FFFFFF;
			width: 15px;
			height: 15px;
			margin: 3px;
			padding: 2px;
			float: left;
			border: 1px solid #CCCCCC;
			-moz-border-radius: 3px;
			-webkit-border-radius: 3px;
		}
		ul.paging {
			list-style: none;
			margin: 4px;
			padding: 0px;
		}
			ul.paging li {
				background-color: #FFFFFF;
				display: block;
				float: left;
				margin: 0px 2px 0px 0px;
				padding: 4px;
				border: 1px solid #CCCCCC;
			}
			ul.paging .current {
				background-color: #FFFFAE;
			}
				ul.paging .current a {
					color: #000000;
					text-decoration: none;
					font-weight: bold;
				}
		label.reset-all {
			background-color: #E4F2FD;
			margin: 4px 0px;
			padding: 8px;
			display: block;
			float: left;
			border: 1px solid #C6D9E9;
		}
		.message {
			width: 60%;
			margin: 6px 3px;
			padding: 4px;
			border: 2px solid #000000;
		}
			.red {
				background-color: #FFF4F4;
				border-color: #FF0000;
			}
	</style>
<?php if (isset($updated_message)): ?>
<div id="message" class="updated fade"><p><?php echo $updated_message; ?></p></div>
<?php endif; ?>
<div class="wrap ih-header">
	<h2>is_human()</h2>
	<p class="install">
		<a href="<?php echo $base_url . '?page=' . $page; ?>&install=1" onclick="toggle('install'); return false;"><?php _e('Applying <strong>is_human()</strong> to your pages'); ?></a>
	</p>
	<div id="install" style="display: <?php echo (bool) $_GET['install'] ? 'block' : 'none'; ?>;">
		<?php _e("<strong>is_human()</strong> is automatically added to the user registration page. To call <strong>is_human()</strong> on your comment page, simply add this code to your template's <pre>comments.php</pre> page:"); ?>
		<code>&lt;?php is_human(); ?&gt;</code><br clear="all" />
	</div>
</div>
<div class="wrap relative">
	<form method="post" action="<?php echo $base_url . '?page=' . $page; ?>" enctype="multipart/form-data" id="form">
		<input type="hidden" name="action" value="save" />
		<fieldset>
			<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&spam=1" onclick="toggle('spam'); return false;"><?php _e('Spam Management'); ?></a></legend>
			<div id="spam" style="display: <?php echo (bool) $_GET['spam'] ? 'block' : 'none'; ?>;">
<?php
	$count_comments_log = is_array(unserialize($ih['log_comments'])) ? count(unserialize($ih['log_comments'])) : 0;
	$count_users_log = is_array(unserialize($ih['log_users'])) ? count(unserialize($ih['log_users'])) : 0;
?>
				<?php _e("is_human() has caught <strong>$count_comments_log</strong> spam comments and <strong>$count_users_log</strong> spam user registrations since " . date('F jS, Y', get_option('is_human_install_date')) . '.'); ?><br /><br />
				<fieldset>
					<div class="reset"><a href="#" onclick="reset_logs('comments'); return false;"><?php _e('Reset Logs'); ?></a></div>
					<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&spam=1&comments_log=1" onclick="toggle('comments_log'); return false;"><?php _e('Spam Comments Log'); ?></a></legend>
					<div id="comments_log" style="display: <?php echo (bool) $_GET['comments_log'] ? 'block' : 'none'; ?>; clear: both;">
						<script type="text/javascript">
							logs_paging('comments', <?php echo count(unserialize($ih['log_comments'])); ?>);
						</script>
						<div id="log_comments_inline">
							<noscript>
								<ul class="comments-log">
									<li><strong><?php _e('You need JavaScript turned on to use the logs completely.'); ?></strong></li>	
								</ul>
							</noscript>
							<?php echo $is_human->get_comments_log(0, $ih['admin_logs_per_page']); ?>
						</div>
					</div>
				</fieldset>
				<fieldset>
					<div class="reset"><a href="#" onclick="reset_logs('users'); return false;"><?php _e('Reset Logs'); ?></a></div>
					<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&spam=1&users_log=1" onclick="toggle('users_log'); return false;"><?php _e('Spam Users Log'); ?></a></legend>
					<div id="users_log" style="display: <?php echo (bool) $_GET['users_log'] ? 'block' : 'none'; ?>; clear: both;">
						<script type="text/javascript">
							logs_paging('users', <?php echo count(unserialize($ih['log_users'])); ?>);
						</script>
						<div id="log_users_inline">
							<noscript>
								<ul class="users-log">
									<li><strong><?php _e('You need JavaScript turned on to use the logs completely.'); ?></strong></li>	
								</ul>
							</noscript>
							<?php echo $is_human->get_users_log(0, $ih['admin_logs_per_page']); ?>
						</div>
					</div>
				</fieldset>
				<fieldset>
					<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&spam=1&blacklisting=1" onclick="toggle('blacklisting'); return false;" name="blacklisting"><?php _e('Blacklisting'); ?></a></legend>
					<div id="blacklisting" style="display: <?php echo (bool) $_GET['blacklisting'] ? 'block' : 'none'; ?>;">
<?php
	$blacklist = @implode(',', unserialize($ih['blacklist']));
	$blacklist = str_replace(' ', '', $blacklist);
?>
						<textarea name="blacklist" id="blacklist" class="input" rows="6" cols="70"><?php echo $blacklist; ?></textarea><br />
						<?php _e('You can choose to blacklist certain IP Addresses from the entire website. This should be applied only to IP addresses that have spammed the site previously. <strong>Separate IP Addresses with commas.</strong>'); ?>
					</div>
				</fieldset>
			</div>
		</fieldset>
		<fieldset>
			<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&admin=1" onclick="toggle('admin'); return false;"><?php _e('Admin Panel Settings'); ?></a></legend>
			<div id="admin" style="display: <?php echo (bool) $_GET['admin'] ? 'block' : 'none'; ?>;">
				<label for="admin_logs_per_page"><?php _e('Logs to display per page'); ?></label><br />
				<input type="text" name="admin_logs_per_page" id="admin_logs_per_page" class="input" value="<?php echo $ih['admin_logs_per_page']; ?>" size="40" /><br />
				<label for="log_active"><?php _e('Enable logging?'); ?></label><br />
				<select name="log_active" id="log_active" class="input">
					<option value="1"<?php echo $ih['log_active'] == '1' ? ' selected="selected"' : ''; ?>><?php _e('Yes'); ?></option>
					<option value="0"<?php echo $ih['log_active'] == '0' ? ' selected="selected"' : ''; ?>><?php _e('No'); ?></option>
				</select>
			</div>
		</fieldset>
		<fieldset>
			<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&general=1" onclick="toggle('general'); return false;"><?php _e('General Settings'); ?></a></legend>
			<div id="general" style="display: <?php echo (bool) $_GET['general'] ? 'block' : 'none'; ?>;">
				<label for="captcha_type"><?php _e('Verification Type'); ?></label><br />
				<select name="captcha_type" id="captcha_type" class="input">
					<option value="equation"<?php echo $ih['captcha_type'] == 'equation' ? ' selected="selected"' : ''; ?>><?php _e('Simple Equation (addition and subtraction)'); ?></option>
					<option value="image"<?php echo $ih['captcha_type'] == 'image' ? ' selected="selected"' : ''; ?>><?php _e('Random Text Image (standard)'); ?></option>
					<option value="questions"<?php echo $ih['captcha_type'] == 'questions' ? ' selected="selected"' : ''; ?>><?php _e('Simple questions (custom questions)'); ?></option>
					<option value="random"<?php echo $ih['captcha_type'] == 'random' ? ' selected="selected"' : ''; ?>><?php _e('Randomly choose one of the above (strongest)'); ?></option>
				</select><br />
				<label for="return_only"><?php _e('Return only (off by default)'); ?></label><br />
				<select name="return_only" id="return_only" class="input">
					<option value="0" style="color: #CC0000;"<?php echo $ih['return_only'] == '0' ? ' selected="selected"' : ''; ?>><?php _e('Off, print string instead'); ?></option>
					<option value="1" style="color: #006600;"<?php echo $ih['return_only'] == '1' ? ' selected="selected"' : ''; ?>><?php _e('On, return as a string'); ?></option>
				</select><br />
				<label for="show_user_registration"><?php _e('Display on user registration page?'); ?></label><br />
				<select name="show_user_registration" id="show_user_registration" class="input">
					<option value="1"<?php echo $ih['show_user_registration'] == '1' ? ' selected="selected"' : ''; ?>><?php _e('Yes'); ?></option>
					<option value="0"<?php echo $ih['show_user_registration'] == '0' ? ' selected="selected"' : ''; ?>><?php _e('No'); ?></option>
				</select><br />
				<label for="users_skip"><?php _e('Display is_human() to registered users?'); ?></label><br />
				<select name="users_skip" id="users_skip" class="input">
					<option value="0"<?php echo $ih['users_skip'] == '0' ? ' selected="selected"' : ''; ?>><?php _e('Yes'); ?></option>
					<option value="1"<?php echo $ih['users_skip'] == '1' ? ' selected="selected"' : ''; ?>><?php _e('No'); ?></option>
				</select><br />
				<label for="admins_skip"><?php _e('Display is_human() to administrators?'); ?></label><br />
				<select name="admins_skip" id="admins_skip" class="input">
					<option value="0"<?php echo $ih['admins_skip'] == '0' ? ' selected="selected"' : ''; ?>><?php _e('Yes'); ?></option>
					<option value="1"<?php echo $ih['admins_skip'] == '1' ? ' selected="selected"' : ''; ?>><?php _e('No'); ?></option>
				</select><br />
				<label for="reload"><?php _e('Enable Reload Button'); ?></label><br />
				<select name="reload" id="reload" class="input">
					<option value="0" style="color: #CC0000;"<?php echo $ih['reload'] == '0' ? ' selected="selected"' : ''; ?>><?php _e('Off'); ?></option>
					<option value="1" style="color: #006600;"<?php echo $ih['reload'] == '1' ? ' selected="selected"' : ''; ?>><?php _e('On'); ?></option>
				</select><br />
				<label for="reload_position"><?php _e('Reload Button Position'); ?></label><br />
				<select name="reload_position" id="reload_position" class="input">
					<option value="0"<?php echo $ih['reload_position'] == '0' ? ' selected="selected"' : ''; ?>><?php _e('Before Security Verification'); ?></option>
					<option value="1"<?php echo $ih['reload_position'] == '1' ? ' selected="selected"' : ''; ?>><?php _e('After Security Verification'); ?></option>
				</select><br />
				<label for="reload_text"><?php _e('Text to display beside Reload Button'); ?></label><br />
				<input type="text" name="reload_text" id="reload_text" class="input" value="<?php echo $ih['reload_text']; ?>" size="40" /><br />
				<label for="reload_tooltip_text"><?php _e("Text to display as Reload Button's Tooltip"); ?></label><br />
				<input type="text" name="reload_tooltip_text" id="reload_tooltip_text" class="input" value="<?php echo $ih['reload_tooltip_text']; ?>" size="40" /><br />
				<fieldset>
					<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&general=1&input=1" onclick="toggle('input'); return false;"><?php _e('Input Field Settings'); ?></a></legend>
					<div id="input" style="display: <?php echo (bool) $_GET['input'] ? 'block' : 'none'; ?>;">
						<label for="text_before"><?php _e('Code before (optional, xhtml allowed)'); ?></label><br />
						<input type="text" name="text_before" id="text_before" class="input" value="<?php echo $ih['text_before']; ?>" size="40" /><br />
						<label for="text_after"><?php _e('Code after (optional, xhtml allowed)'); ?></label><br />
						<input type="text" name="text_after" id="text_after" class="input" value="<?php echo $ih['text_after']; ?>" size="40" /><br />
						<label for="input_class"><?php _e('Class Name (optional)'); ?></label><br />
						<input type="text" name="input_class" id="input_class" class="input" value="<?php echo $ih['input_class']; ?>" size="40" /><br />
						<label for="input_id"><?php _e('Unique ID (optional)'); ?></label><br />
						<input type="text" name="input_id" id="input_id" class="input" value="<?php echo $ih['input_id']; ?>" size="40" /><br />
					</div>
				</fieldset>
			</div>
		</fieldset>
		<fieldset>
			<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&equations=1" onclick="toggle('equations'); return false;"><?php _e('Simple Equations Settings'); ?></a></legend>
			<div id="equations" style="display: <?php echo (bool) $_GET['equations'] ? 'block' : 'none'; ?>;">
				<label for="equation_from"><?php _e('Minimum number (for random pick)'); ?></label><br />
				<input type="text" name="equation_from" id="equation_from" class="input" value="<?php echo $ih['equation_from']; ?>" size="40" /><br />
				<label for="equation_to"><?php _e('Maximum number (for random pick)'); ?></label><br />
				<input type="text" name="equation_to" id="equation_to" class="input" value="<?php echo $ih['equation_to']; ?>" size="40" /><br />
			</div>
		</fieldset>
		<fieldset>
			<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&image=1" onclick="toggle('image'); return false;"><?php _e('Captcha Image Settings'); ?></a></legend>
			<div id="image" style="display: <?php echo (bool) $_GET['image'] ? 'block' : 'none'; ?>;">
				<label for="image_font"><?php _e('Font Family'); ?></label><br />
				<select name="image_font" id="image_font" class="input" onchange="update_captcha();">
					<option value="arial.ttf"<?php echo $ih['image_font'] == 'arial.ttf' ? ' selected="selected"' : ''; ?>>Arial</option>
					<option value="comic.ttf"<?php echo $ih['image_font'] == 'comic.ttf' ? ' selected="selected"' : ''; ?>>Comic Sans</option>
					<option value="courier.ttf"<?php echo $ih['image_font'] == 'courier.ttf' ? ' selected="selected"' : ''; ?>>Courier New</option>
					<option value="myriad.otf"<?php echo $ih['image_font'] == 'myriad.otf' ? ' selected="selected"' : ''; ?>>Myriad Pro</option>
					<option value="tahoma.ttf"<?php echo $ih['image_font'] == 'tahoma.ttf' ? ' selected="selected"' : ''; ?>>Tahoma</option>
					<option value="verdana.ttf"<?php echo $ih['image_font'] == 'verdana.ttf' ? ' selected="selected"' : ''; ?>>Verdana</option>
				</select><br />
				<label for="image_font_size"><?php _e('Font Size (in pixels)'); ?></label><br />
				<input type="text" name="image_font_size" id="image_font_size" class="input" value="<?php echo $ih['image_font_size']; ?>" size="40" onchange="update_captcha();" /><br />
				<label for="image_font_color"><?php _e('Font Color'); ?></label><br />
				<div id="image_font_color_preview" class="selector">#</div>
				<input type="text" name="image_font_color" id="image_font_color" class="input" value="<?php echo $ih['image_font_color']; ?>" size="40" onchange="update_captcha();" /><br />
				<label for="image_background"><?php _e('Background Color'); ?></label><br />
				<div id="image_background_preview" class="selector">#</div>
				<input type="text" name="image_background" id="image_background" class="input" value="<?php echo $ih['image_background']; ?>" size="40" onchange="update_captcha();" /><br />
				<label for="image_width"><?php _e('Width (in pixels)'); ?></label><br />
				<input type="text" name="image_width" id="image_width" class="input" value="<?php echo $ih['image_width']; ?>" size="40" onchange="update_captcha();" /><br />
				<label for="image_height"><?php _e('Height (in pixels)'); ?></label><br />
				<input type="text" name="image_height" id="image_height" class="input" value="<?php echo $ih['image_height']; ?>" size="40" onchange="update_captcha();" /><br />
				<label for="image_class"><?php _e('Class Name (optional)'); ?></label><br />
				<input type="text" name="image_class" id="image_class" class="input" value="<?php echo $ih['image_class']; ?>" size="40" onchange="update_captcha();" /><br />
				<label><?php _e('Captcha Preview'); ?></label><br />
				<div id="image_preview">
					<noscript>
						You need JavaScript turned on to view the Captcha Preview.
					</noscript>
				</div>
<?php
	if (!function_exists('imagecreate')):
?>
				<p class="message red">You need to have the <strong>GD Library</strong> installed for the Captcha Image to display properly. Contact your host about your PHP settings.</p>
<?php
	endif;
?>
				<script type="text/javascript">
					update_captcha();
				</script>
			</div>
		</fieldset>
		<fieldset>
			<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&questions=1" onclick="toggle('questions'); return false;"><?php _e('Simple Questions Settings'); ?></a></legend>
			<div id="questions" style="display: <?php echo (bool) $_GET['questions'] ? 'block' : 'none'; ?>;">
				<label><?php _e('Questions (one per line; see format below)'); ?></label><br />
				<textarea name="questions" id="questions_list" class="input" rows="6" cols="100"><?php echo $ih['questions']; ?></textarea><br />
				<fieldset style="display: none;" id="question_generator_parent">
					<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&questions=1&question_generator=1" onclick="toggle('question_generator'); return false;"><?php _e('Easy Question Generator'); ?></a></legend>
					<div id="question_generator" style="display: <?php echo (bool) $_GET['question_generator'] ? 'block' : 'none'; ?>;">
						<label for="gen_question"><?php _e('Question'); ?></label><br />
						<input type="text" name="gen_question" id="gen_question" class="input" size="50" /><br />
						<label id="gen_answers_label" style="display: none;"><?php _e('Answers'); ?></label><br />
						<ul id="gen_answers" style="display: none;"></ul>
						<label for="new_answer"><?php _e('Add a New Answer'); ?></label><br />
						<input type="text" name="gen_new_answer" id="gen_new_answer" class="input" size="40" style="vertical-align: middle;" />
						<select name="gen_new_answer_correct" id="gen_new_answer_correct" style="vertical-align: middle;">
							<option value="correct"><?php _e('Correct Answer'); ?></option>
							<option value="incorrect" selected="selected"><?php _e('Incorrect Answer'); ?></option>
						</select>
						<input type="button" id="gen_new_answer_add" class="button" value="<?php _e('Add Answer'); ?>" style="vertical-align: middle;" onclick="add_answer();" /><br />
						<input type="button" id="gen_submit_answer" class="button" value="<?php _e('Generate Question'); ?>" onclick="generate();" />
					</div>
				</fieldset>
				<script type="text/javascript">
					$('question_generator_parent').style.display = 'block';
				</script>
				<fieldset>
					<legend><a href="<?php echo $base_url . '?page=' . $page; ?>&questions=1&help_questions=1" onclick="toggle('help_questions'); return false;"><?php _e('Documentation on formatting Simple Questions'); ?></a></legend>
					<ul id="help_questions" style="display: <?php echo (bool) $_GET['help_questions'] ? 'block' : 'none'; ?>;">
						<li>
							<?php _e('If only one answer is specified, then:'); ?>
							<ol>
								<li><?php _e('an asterisk (<pre>*</pre>) must be placed before the closing bracket'); ?> (<pre>]</pre>)</li>
								<li><?php _e('a text field will be shown'); ?></li>
								<li>
									<?php _e('See this format:'); ?>
									<code>
										<em><?php _e('question'); ?></em>&nbsp;[&nbsp;<em><?php _e('correct_answer'); ?>*</em>&nbsp;]
									</code><br clear="all" />
								</li>
							</ol>
						</li>
						<li>
							<?php _e('If there is more than one answer, then:'); ?>
							<ol>
								<li><?php _e('an asterisk (<pre>*</pre>) must only be placed after the correct answer'); ?></li>
								<li><?php _e('separate answers with a dividing line'); ?> (<pre>|</pre>)</li>
								<li><?php _e('a drop-down field with all the answers will be shown'); ?></li>
								<li>
									<?php _e('See this format:'); ?>
									<code>
										<em><?php _e('question'); ?></em>&nbsp;[&nbsp;<em><?php _e('correct_answer'); ?>*</em>&nbsp;|&nbsp;<em><?php _e('incorrect_answer'); ?></em>&nbsp;]
									</code><br clear="all" />
								</li>
							</ol>
						</li>
						<li>
							<?php _e('Example of correct use:'); ?>
							<code>
								<?php _e("What is man's best friend?[dog*|cat|bird]"); ?>
							</code><br clear="all" />
						</li>
					</ul>
				</fieldset>
			</div>
		</fieldset>
		<label class="reset-all">
			<input type="checkbox" name="reset" value="1" onchange="$('form').action = this.checked == true ? '<?php echo $base_url . '?page=' . $page; ?>&reset=1' : '<?php echo $base_url . '?page=' . $page; ?>';" />
			<strong><?php _e('Reset all is_human() values to defaults?'); ?></strong>
		</label><br clear="all" /><br />
		<input type="submit" class="button" value="<?php _e('Save Verification Settings'); ?>" />
	</form>
</div>