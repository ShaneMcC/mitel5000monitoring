<?php
	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/classes/statuspage.php');

	$statuspage = new statuspage($config);
	$status = $statuspage->getStatus();

	$prtg = array('prtg' => array('result' => array(), 'text' => ''));
	$prtg['prtg']['text'] = $status['description'];
	$prtg['prtg']['result'][] = array('channel' => 'PBX Status', 'value' => $status['code']);

	$pushURL = 'http://' . $config['prtg']['status']['probe'] . '/' . $config['prtg']['status']['token'] . '?content=' . urlencode(json_encode($prtg));
	file_get_contents($pushURL);
?>
