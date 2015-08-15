<?php

	require_once(dirname(__FILE__) . '/../3rdparty/simpletest/browser.php');
	require_once(dirname(__FILE__) . '/../3rdparty/phpquery/phpQuery/phpQuery.php');

	abstract class pbxpage {
		protected $browser = null;
		protected $cachedDNS = array();
		protected $config = array();
		public function __construct($config) {
			$this->config = $config;
		}

		/**
		 * Create a new Browser Object.
		 */
		protected function newBrowser($loadCookies = true) {
			global $__simpleSocketContext;
			$__simpleSocketContext = array();
			$this->browser = new SimpleBrowser();
			$this->browser->setParser(new SimplePHPPageBuilder());
			$this->browser->setUserAgent('Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:35.0) Gecko/20100101 Firefox/35.0');
			if ($loadCookies) {
				$this->loadCookies();
			}
			$this->browser->setGetHostAddr(function ($host) { return $this->cacheResolveAddress($host); });
		}
		/**
		 * This function will resolve addresses once, and then remember them
		 * for the lifetime of the object. This gets around some fucky and
		 * broken load-balancers.
		 *
		 * @param $host Host to look up
		 * @return Address to connect to.
		 */
		protected function cacheResolveAddress($host) {
			if (!isset($this->cachedDNS[strtolower($host)])) {
				// Resolve once, remember forever.
				$this->cachedDNS[strtolower($host)] = gethostbyname($host);
			}
			return $this->cachedDNS[strtolower($host)];
		}
		protected function getCookieFile() {
			$reflector = new ReflectionObject($this);
			return dirname($reflector->getFileName()) . '/.cookies-' . str_replace('/', '_', crc32(serialize($this->config)));
		}
		protected function saveCookies() {
			$_SimpleBrowser = new ReflectionClass("SimpleBrowser");
			$_SimpleBrowser_user_agent = $_SimpleBrowser->getProperty("user_agent");
			$_SimpleBrowser_user_agent->setAccessible(true);
			$_SimpleUserAgent = new ReflectionClass("SimpleUserAgent");
			$_SimpleUserAgent_cookie_jar = $_SimpleUserAgent->getProperty("cookie_jar");
			$_SimpleUserAgent_cookie_jar->setAccessible(true);
			$useragent = $_SimpleBrowser_user_agent->getValue($this->browser);
			$cookie_jar = $_SimpleUserAgent_cookie_jar->getValue($useragent);
			file_put_contents($this->getCookieFile(), serialize($cookie_jar));
		}
		protected function loadCookies() {
			if (!file_exists($this->getCookieFile())) { return; }
			$_SimpleBrowser = new ReflectionClass("SimpleBrowser");
			$_SimpleBrowser_user_agent = $_SimpleBrowser->getProperty("user_agent");
			$_SimpleBrowser_user_agent->setAccessible(true);
			$_SimpleUserAgent = new ReflectionClass("SimpleUserAgent");
			$_SimpleUserAgent_cookie_jar = $_SimpleUserAgent->getProperty("cookie_jar");
			$_SimpleUserAgent_cookie_jar->setAccessible(true);
			$useragent = $_SimpleBrowser_user_agent->getValue($this->browser);
			$new_cookie_jar = unserialize(file_get_contents($this->getCookieFile()));
			$_SimpleUserAgent_cookie_jar->setValue($useragent, $new_cookie_jar);
		}
		/**
		 * Get the requested page, logging in if needed.
		 *
		 * @param $url URL of page to get.
		 * @param $justGet (Default: false) Just get the page, don't try to auth.
		 */
		protected function getPage($url, $justGet = false) {
			if ($this->browser == null) {
				if ($justGet) {
					$this->newBrowser();
				} else {
					// We need a new browser, so we're going to need to log in.
					if (!$this->login()) { return false; }
				}
			}
			$page = $this->browser->get($url);
			if (!$justGet && !$this->isLoggedIn($page)) {
				if (!$this->login()) { return false; }
				$page = $this->browser->get($url);
			}
			return $page;
		}
		public function login($fresh = false) {
			$page = $this->getPage('https://' . $this->config['pbx']['url'] . '/login.cgi', true);

			if (!preg_match('/var to_hash = username \+ "([^"]+)" \+ password;/', $page, $matches)) {
				return false;
			}


			$username = $this->config['pbx']['username'];
			$passhash = md5($this->config['pbx']['username'] . $matches[1] . $this->config['pbx']['password']);
			$lang = 'en-gb';

			$this->browser->setFieldById('language_hidden', $lang);
			$this->browser->setFieldById('username_hidden', $username);
			$this->browser->setFieldById('authhash_hidden', $passhash);

			$page = $this->browser->submitFormByName('login_form');
			if ($this->isLoggedIn($page)) {
				$this->saveCookies();
				return true;
			}

			return false;
		}
		public function isLoggedIn($page) {
			return !preg_match('/.*function submitLogin\(\) \{.*/', $page);
		}
		/**
		 * Get a nice tidied and phpQueryed version of a html page.
		 *
		 * @param $html HTML to parse
		 * @return PHPQuery document from the tidied html.
		 */
		protected function getDocument($html) {
			$config = array('indent' => TRUE,
			                'wrap' => 0,
			                'output-xhtml' => true,
			                'clean' => true,
			                'numeric-entities' => true,
			                'char-encoding' => 'utf8',
			                'input-encoding' => 'utf8',
			                'output-encoding' => 'utf8',
			                );
			$tidy = tidy_parse_string($html, $config, 'utf8');
			$tidy->cleanRepair();
			$html = $tidy->value;
			return phpQuery::newDocument($html);
		}
		/**
		 * Clean up an element.
		 *
		 * @return a clean element as a string.
		 */
		protected function cleanElement($element) {
			if (method_exists($element, 'html')) {
				$out = $element->html();
			} else {
				$out = $element->nodeValue;
			}
			// Decode entities.
			// Handle the silly space first.
			$out = str_replace('&#160;', ' ', $out);
			$out = str_replace(html_entity_decode('&#160;'), ' ', $out);
			// Now the rest.
			$out = trim(html_entity_decode($out));
			// I don't remember why I did this, so for now I'll leave it out.
			// $out = trim(preg_replace('#[^\s\w\d-._/\\\'*()<>{}\[\]@&;!"%^]#i', '', $element->html()));
			return trim($out);
		}
	}
?>
