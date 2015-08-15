<?php
	require_once(dirname(__FILE__) . '/pbxpage.php');

	class statuspage extends pbxpage {

		public function __construct($config) { parent::__construct($config); }

		public function getStatus() {
			$page = $this->getPage('https://' . $this->config['pbx']['url'] . '/aws_www/cgi-bin/sysinfo.cgi');

			preg_match('#<td>System Alarm Status:&nbsp;&nbsp;</td>[^<]+<td><img src="[^"]+" style="[^"]+" alt="Alarm Status ([^\s]+) Icon" />([^<]+)</td>#misU', $page, $matches);

			$statusCodes = array();
			$statusCodes['good'] = 0;
			$statusCodes['fair'] = 1;
			$statusCodes['unstable'] = 2;

			$result = array();
			$result['status'] = $matches[1];
			$result['code'] = isset($statusCodes[strtolower($result['status'])]) ? $statusCodes[strtolower($result['status'])] : -1;
			$result['description'] = trim(html_entity_decode(str_replace('&nbsp;', '', $matches[2])));

			return $result;
		}
	}
?>
