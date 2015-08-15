<?php
	$config['pbx']['url'] = 'pbx.office';
	$config['pbx']['username'] = 'admin';
	$config['pbx']['password'] = 'password';

	$config['prtg']['status']['probe'] = '127.0.0.1:5050';
	$config['prtg']['status']['token'] = 'AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA';

	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		require_once(dirname(__FILE__) . '/config.local.php');
	}
?>
