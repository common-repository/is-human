<?php
	include_once(dirname(__FILE__) . '../../../../wp-config.php');
	include_once('is-human.php');
	$action = $_GET['action'] ? $_GET['action'] : $_POST['action'];
	switch ($action) {
		default: case 'captcha-reload':
			is_human(true);
		break;
		case 'log-reload':
			#page the logs
			global $is_human;
			$is_human->get_ih_options();
			$total = count(unserialize($is_human->ih['log_' . $_GET['type']]));
			if ($total > 0) {
				$total_pages = ($total / $is_human->ih['admin_logs_per_page']);
				$start = $is_human->ih['admin_logs_per_page'] * ($_GET['page'] - 1);
				$stop = $_GET['page'] == '1' ? $start + ($is_human->ih['admin_logs_per_page'] - 1) : $start + ($is_human->ih['admin_logs_per_page']);
				$stop = $stop > $total ? $total : $stop;
				eval('echo $is_human->get_' . $_GET['type'] . '_log(' . $start . ', ' . $stop . ');');
			} else {
				eval('echo $is_human->get_' . $_GET['type'] . '_log();');
			}
		break;
		case 'log-reset':
			global $is_human;
			$is_human->get_ih_options();
			$is_human->ih['log_' . $_GET['type']] = serialize(array());
			$is_human->update_ih_options();
			eval('echo $is_human->get_' . $_GET['type'] . '_log();');
		break;
	}
	exit;
?>