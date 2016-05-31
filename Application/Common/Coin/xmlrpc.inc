<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
class xmlrpc_client
{
	public $path;
	public $server;
	public $port = 0;
	public $method = 'http';
	public $errno;
	public $errstr;
	public $debug = 0;
	public $username = '';
	public $password = '';
	public $authtype = 1;
	public $cert = '';
	public $certpass = '';
	public $cacert = '';
	public $cacertdir = '';
	public $key = '';
	public $keypass = '';
	public $verifypeer = true;
	public $verifyhost = 1;
	public $no_multicall = false;
	public $proxy = '';
	public $proxyport = 0;
	public $proxy_user = '';
	public $proxy_pass = '';
	public $proxy_authtype = 1;
	public $cookies = array();
	public $extracurlopts = array();
	/**
		* List of http compression methods accepted by the client for responses.
		* NB: PHP supports deflate, gzip compressions out of the box if compiled w. zlib
		*
		* NNB: you can set it to any non-empty array for HTTP11 and HTTPS, since
		* in those cases it will be up to CURL to decide the compression methods
		* it supports. You might check for the presence of 'zlib' in the output of
		* curl_version() to determine wheter compression is supported or not
		*/
	public $accepted_compression = array();
	/**
		* Name of compression scheme to be used for sending requests.
		* Either null, gzip or deflate
		*/
	public $request_compression = '';
	/**
		* CURL handle: used for keep-alive connections (PHP 4.3.8 up, see:
		* http://curl.haxx.se/docs/faq.html#7.3)
		*/
	public $xmlrpc_curl_handle;
	public $keepalive = false;
	public $accepted_charset_encodings = array();
	public $request_charset_encoding = '';
	/**
		* Decides the content of xmlrpcresp objects returned by calls to send()
		* valid strings are 'xmlrpcvals', 'phpvals' or 'xml'
		*/
	public $return_type = 'xmlrpcvals';
	/**
		* Sent to servers in http headers
		*/
	public $user_agent;

	public function xmlrpc_client($path, $server = '', $port = '', $method = '')
	{
		if (($server == '') && ($port == '') && ($method == '')) {
			$parts = parse_url($path);
			$server = $parts['host'];
			$path = (isset($parts['path']) ? $parts['path'] : '');

			if (isset($parts['query'])) {
				$path .= '?' . $parts['query'];
			}

			if (isset($parts['fragment'])) {
				$path .= '#' . $parts['fragment'];
			}

			if (isset($parts['port'])) {
				$port = $parts['port'];
			}

			if (isset($parts['scheme'])) {
				$method = $parts['scheme'];
			}

			if (isset($parts['user'])) {
				$this->username = $parts['user'];
			}

			if (isset($parts['pass'])) {
				$this->password = $parts['pass'];
			}
		}

		if (($path == '') || ($path[0] != '/')) {
			$this->path = '/' . $path;
		}
		else {
			$this->path = $path;
		}

		$this->server = $server;

		if ($port != '') {
			$this->port = $port;
		}

		if ($method != '') {
			$this->method = $method;
		}

		if (function_exists('gzinflate') || (function_exists('curl_init') && ($info = curl_version()) && ((is_string($info) && (strpos($info, 'zlib') !== NULL)) || isset($info['libz_version'])))) {
			$this->accepted_compression = array('gzip', 'deflate');
		}

		$this->keepalive = true;
		$this->accepted_charset_encodings = array('UTF-8', 'ISO-8859-1', 'US-ASCII');
		$this->user_agent = $GLOBALS['xmlrpcName'] . ' ' . $GLOBALS['xmlrpcVersion'];

		if (function_exists('mb_internal_encoding')) {
			$this->request_charset_encoding = $GLOBALS['xmlrpc_internalencoding'] = mb_internal_encoding();
		}
	}

	public function setDebug($in)
	{
		$this->debug = $in;
	}

	public function setCredentials($u, $p, $t = 1)
	{
		$this->username = $u;
		$this->password = $p;
		$this->authtype = $t;
	}

	public function setCertificate($cert, $certpass)
	{
		$this->cert = $cert;
		$this->certpass = $certpass;
	}

	public function setCaCertificate($cacert, $is_dir = false)
	{
		if ($is_dir) {
			$this->cacertdir = $cacert;
		}
		else {
			$this->cacert = $cacert;
		}
	}

	public function setKey($key, $keypass)
	{
		$this->key = $key;
		$this->keypass = $keypass;
	}

	public function setSSLVerifyPeer($i)
	{
		$this->verifypeer = $i;
	}

	public function setSSLVerifyHost($i)
	{
		$this->verifyhost = $i;
	}

	public function setProxy($proxyhost, $proxyport, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1)
	{
		$this->proxy = $proxyhost;
		$this->proxyport = $proxyport;
		$this->proxy_user = $proxyusername;
		$this->proxy_pass = $proxypassword;
		$this->proxy_authtype = $proxyauthtype;
	}

	public function setAcceptedCompression($compmethod)
	{
		if ($compmethod == 'any') {
			$this->accepted_compression = array('gzip', 'deflate');
		}
		else {
			$this->accepted_compression = array($compmethod);
		}
	}

	public function setRequestCompression($compmethod)
	{
		$this->request_compression = $compmethod;
	}

	public function setCookie($name, $value = '', $path = '', $domain = '', $port = NULL)
	{
		$this->cookies[$name]['value'] = urlencode($value);
		if ($path || $domain || $port) {
			$this->cookies[$name]['path'] = $path;
			$this->cookies[$name]['domain'] = $domain;
			$this->cookies[$name]['port'] = $port;
			$this->cookies[$name]['version'] = 1;
		}
		else {
			$this->cookies[$name]['version'] = 0;
		}
	}

	public function SetCurlOptions($options)
	{
		$this->extracurlopts = $options;
	}

	public function SetUserAgent($agentstring)
	{
		$this->user_agent = $agentstring;
	}

	public function& send($msg, $timeout = 0, $method = '')
	{
		if ($method == '') {
			$method = $this->method;
		}

		if (is_array($msg)) {
			$r = $this->multicall($msg, $timeout, $method);
			return $r;
		}
		else if (is_string($msg)) {
			$n = new xmlrpcmsg('');
			$n->payload = $msg;
			$msg = $n;
		}

		$msg->debug = $this->debug;

		if ($method == 'https') {
			$r = &$this->sendPayloadHTTPS($msg, $this->server, $this->port, $timeout, $this->username, $this->password, $this->authtype, $this->cert, $this->certpass, $this->cacert, $this->cacertdir, $this->proxy, $this->proxyport, $this->proxy_user, $this->proxy_pass, $this->proxy_authtype, $this->keepalive, $this->key, $this->keypass);
		}
		else if ($method == 'http11') {
			$r = &$this->sendPayloadCURL($msg, $this->server, $this->port, $timeout, $this->username, $this->password, $this->authtype, NULL, NULL, NULL, NULL, $this->proxy, $this->proxyport, $this->proxy_user, $this->proxy_pass, $this->proxy_authtype, 'http', $this->keepalive);
		}
		else {
			$r = &$this->sendPayloadHTTP10($msg, $this->server, $this->port, $timeout, $this->username, $this->password, $this->authtype, $this->proxy, $this->proxyport, $this->proxy_user, $this->proxy_pass, $this->proxy_authtype);
		}

		return $r;
	}

	public function& sendPayloadHTTP10($msg, $server, $port, $timeout = 0, $username = '', $password = '', $authtype = 1, $proxyhost = '', $proxyport = 0, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1)
	{
		if ($port == 0) {
			$port = 80;
		}

		if (empty($msg->payload)) {
			$msg->createPayload($this->request_charset_encoding);
		}

		$payload = $msg->payload;
		if (function_exists('gzdeflate') && (($this->request_compression == 'gzip') || ($this->request_compression == 'deflate'))) {
			if ($this->request_compression == 'gzip') {
				$a = @gzencode($payload);

				if ($a) {
					$payload = $a;
					$encoding_hdr = "Content-Encoding: gzip\r\n";
				}
			}
			else {
				$a = @gzcompress($payload);

				if ($a) {
					$payload = $a;
					$encoding_hdr = "Content-Encoding: deflate\r\n";
				}
			}
		}
		else {
			$encoding_hdr = '';
		}

		$credentials = '';

		if ($username != '') {
			$credentials = 'Authorization: Basic ' . base64_encode($username . ':' . $password) . "\r\n";

			if ($authtype != 1) {
				error_log('XML-RPC: ' . 'xmlrpc_client::sendPayloadHTTP10' . ': warning. Only Basic auth is supported with HTTP 1.0');
			}
		}

		$accepted_encoding = '';
		if (is_array($this->accepted_compression) && count($this->accepted_compression)) {
			$accepted_encoding = 'Accept-Encoding: ' . implode(', ', $this->accepted_compression) . "\r\n";
		}

		$proxy_credentials = '';

		if ($proxyhost) {
			if ($proxyport == 0) {
				$proxyport = 8080;
			}

			$connectserver = $proxyhost;
			$connectport = $proxyport;
			$uri = 'http://' . $server . ':' . $port . $this->path;

			if ($proxyusername != '') {
				if ($proxyauthtype != 1) {
					error_log('XML-RPC: ' . 'xmlrpc_client::sendPayloadHTTP10' . ': warning. Only Basic auth to proxy is supported with HTTP 1.0');
				}

				$proxy_credentials = 'Proxy-Authorization: Basic ' . base64_encode($proxyusername . ':' . $proxypassword) . "\r\n";
			}
		}
		else {
			$connectserver = $server;
			$connectport = $port;
			$uri = $this->path;
		}

		$cookieheader = '';

		if (count($this->cookies)) {
			$version = '';

			foreach ($this->cookies as $name => $cookie) {
				if ($cookie['version']) {
					$version = ' $Version="' . $cookie['version'] . '";';
					$cookieheader .= ' ' . $name . '="' . $cookie['value'] . '";';

					if ($cookie['path']) {
						$cookieheader .= ' $Path="' . $cookie['path'] . '";';
					}

					if ($cookie['domain']) {
						$cookieheader .= ' $Domain="' . $cookie['domain'] . '";';
					}

					if ($cookie['port']) {
						$cookieheader .= ' $Port="' . $cookie['port'] . '";';
					}
				}
				else {
					$cookieheader .= ' ' . $name . '=' . $cookie['value'] . ';';
				}
			}

			$cookieheader = 'Cookie:' . $version . substr($cookieheader, 0, -1) . "\r\n";
		}

		$op = 'POST ' . $uri . " HTTP/1.0\r\n" . 'User-Agent: ' . $this->user_agent . "\r\n" . 'Host: ' . $server . ':' . $port . "\r\n" . $credentials . $proxy_credentials . $accepted_encoding . $encoding_hdr . 'Accept-Charset: ' . implode(',', $this->accepted_charset_encodings) . "\r\n" . $cookieheader . 'Content-Type: ' . $msg->content_type . "\r\nContent-Length: " . strlen($payload) . "\r\n\r\n" . $payload;

		if (1 < $this->debug) {
			print("<PRE>\n---SENDING---\n" . htmlentities($op) . "\n---END---\n</PRE>");
			flush();
		}

		if (0 < $timeout) {
			$fp = @fsockopen($connectserver, $connectport, $this->errno, $this->errstr, $timeout);
		}
		else {
			$fp = @fsockopen($connectserver, $connectport, $this->errno, $this->errstr);
		}

		if ($fp) {
			if ((0 < $timeout) && function_exists('stream_set_timeout')) {
				stream_set_timeout($fp, $timeout);
			}
		}
		else {
			$this->errstr = 'Connect error: ' . $this->errstr;
			$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['http_error'], $this->errstr . ' (' . $this->errno . ')');
			return $r;
		}

		if (!fputs($fp, $op, strlen($op))) {
			fclose($fp);
			$this->errstr = 'Write error';
			$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['http_error'], $this->errstr);
			return $r;
		}
		else {
			$this->errstr = '';
		}

		$ipd = '';

		do {
			$ipd .= fread($fp, 32768);
		} while (!feof($fp));

		fclose($fp);
		$r = &$msg->parseResponse($ipd, false, $this->return_type);
		return $r;
	}

	public function& sendPayloadHTTPS($msg, $server, $port, $timeout = 0, $username = '', $password = '', $authtype = 1, $cert = '', $certpass = '', $cacert = '', $cacertdir = '', $proxyhost = '', $proxyport = 0, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1, $keepalive = false, $key = '', $keypass = '')
	{
		$r = &$this->sendPayloadCURL($msg, $server, $port, $timeout, $username, $password, $authtype, $cert, $certpass, $cacert, $cacertdir, $proxyhost, $proxyport, $proxyusername, $proxypassword, $proxyauthtype, 'https', $keepalive, $key, $keypass);
		return $r;
	}

	public function& sendPayloadCURL($msg, $server, $port, $timeout = 0, $username = '', $password = '', $authtype = 1, $cert = '', $certpass = '', $cacert = '', $cacertdir = '', $proxyhost = '', $proxyport = 0, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1, $method = 'https', $keepalive = false, $key = '', $keypass = '')
	{
		if (!function_exists('curl_init')) {
			$this->errstr = 'CURL unavailable on this install';
			$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['no_curl'], $GLOBALS['xmlrpcstr']['no_curl']);
			return $r;
		}

		if ($method == 'https') {
			if (($info = curl_version()) && ((is_string($info) && (strpos($info, 'OpenSSL') === NULL)) || (is_array($info) && !isset($info['ssl_version'])))) {
				$this->errstr = 'SSL unavailable on this install';
				$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['no_ssl'], $GLOBALS['xmlrpcstr']['no_ssl']);
				return $r;
			}
		}

		if ($port == 0) {
			if ($method == 'http') {
				$port = 80;
			}
			else {
				$port = 443;
			}
		}

		if (empty($msg->payload)) {
			$msg->createPayload($this->request_charset_encoding);
		}

		$payload = $msg->payload;
		if (function_exists('gzdeflate') && (($this->request_compression == 'gzip') || ($this->request_compression == 'deflate'))) {
			if ($this->request_compression == 'gzip') {
				$a = @gzencode($payload);

				if ($a) {
					$payload = $a;
					$encoding_hdr = 'Content-Encoding: gzip';
				}
			}
			else {
				$a = @gzcompress($payload);

				if ($a) {
					$payload = $a;
					$encoding_hdr = 'Content-Encoding: deflate';
				}
			}
		}
		else {
			$encoding_hdr = '';
		}

		if (1 < $this->debug) {
			print("<PRE>\n---SENDING---\n" . htmlentities($payload) . "\n---END---\n</PRE>");
			flush();
		}

		if (!$keepalive || !$this->xmlrpc_curl_handle) {
			$curl = curl_init($method . '://' . $server . ':' . $port . $this->path);

			if ($keepalive) {
				$this->xmlrpc_curl_handle = $curl;
			}
		}
		else {
			$curl = $this->xmlrpc_curl_handle;
		}

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		if ($this->debug) {
			curl_setopt($curl, CURLOPT_VERBOSE, 1);
		}

		curl_setopt($curl, CURLOPT_USERAGENT, $this->user_agent);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		if (is_array($this->accepted_compression) && count($this->accepted_compression)) {
			if (count($this->accepted_compression) == 1) {
				curl_setopt($curl, CURLOPT_ENCODING, $this->accepted_compression[0]);
			}
			else {
				curl_setopt($curl, CURLOPT_ENCODING, '');
			}
		}

		$headers = array('Content-Type: ' . $msg->content_type, 'Accept-Charset: ' . implode(',', $this->accepted_charset_encodings));

		if (!$keepalive) {
			$headers[] = 'Connection: close';
		}

		if ($encoding_hdr) {
			$headers[] = $encoding_hdr;
		}

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		if ($timeout) {
			curl_setopt($curl, CURLOPT_TIMEOUT, $timeout == 1 ? 1 : $timeout - 1);
		}

		if ($username && $password) {
			curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);

			if (defined('CURLOPT_HTTPAUTH')) {
				curl_setopt($curl, CURLOPT_HTTPAUTH, $authtype);
			}
			else if ($authtype != 1) {
				error_log('XML-RPC: ' . 'xmlrpc_client::sendPayloadCURL' . ': warning. Only Basic auth is supported by the current PHP/curl install');
			}
		}

		if ($method == 'https') {
			if ($cert) {
				curl_setopt($curl, CURLOPT_SSLCERT, $cert);
			}

			if ($certpass) {
				curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $certpass);
			}

			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->verifypeer);

			if ($cacert) {
				curl_setopt($curl, CURLOPT_CAINFO, $cacert);
			}

			if ($cacertdir) {
				curl_setopt($curl, CURLOPT_CAPATH, $cacertdir);
			}

			if ($key) {
				curl_setopt($curl, CURLOPT_SSLKEY, $key);
			}

			if ($keypass) {
				curl_setopt($curl, CURLOPT_SSLKEYPASSWD, $keypass);
			}

			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->verifyhost);
		}

		if ($proxyhost) {
			if ($proxyport == 0) {
				$proxyport = 8080;
			}

			curl_setopt($curl, CURLOPT_PROXY, $proxyhost . ':' . $proxyport);

			if ($proxyusername) {
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyusername . ':' . $proxypassword);

				if (defined('CURLOPT_PROXYAUTH')) {
					curl_setopt($curl, CURLOPT_PROXYAUTH, $proxyauthtype);
				}
				else if ($proxyauthtype != 1) {
					error_log('XML-RPC: ' . 'xmlrpc_client::sendPayloadCURL' . ': warning. Only Basic auth to proxy is supported by the current PHP/curl install');
				}
			}
		}

		if (count($this->cookies)) {
			$cookieheader = '';

			foreach ($this->cookies as $name => $cookie) {
				$cookieheader .= $name . '=' . $cookie['value'] . '; ';
			}

			curl_setopt($curl, CURLOPT_COOKIE, substr($cookieheader, 0, -2));
		}

		foreach ($this->extracurlopts as $opt => $val) {
			curl_setopt($curl, $opt, $val);
		}

		$result = curl_exec($curl);

		if (1 < $this->debug) {
			print("<PRE>\n---CURL INFO---\n");

			foreach (curl_getinfo($curl) as $name => $val) {
				print($name . ': ' . htmlentities($val) . "\n");
			}

			print("---END---\n</PRE>");
		}

		if (!$result) {
			$this->errstr = 'no response';
			$resp = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['curl_fail'], $GLOBALS['xmlrpcstr']['curl_fail'] . ': ' . curl_error($curl));
			curl_close($curl);

			if ($keepalive) {
				$this->xmlrpc_curl_handle = NULL;
			}
		}
		else {
			if (!$keepalive) {
				curl_close($curl);
			}

			$resp = &$msg->parseResponse($result, true, $this->return_type);
		}

		return $resp;
	}

	public function multicall($msgs, $timeout = 0, $method = '', $fallback = true)
	{
		if ($method == '') {
			$method = $this->method;
		}

		if (!$this->no_multicall) {
			$results = $this->_try_multicall($msgs, $timeout, $method);

			if (is_array($results)) {
				return $results;
			}
			else if ($fallback) {
				$this->no_multicall = true;
			}
			else if (is_a($results, 'xmlrpcresp')) {
				$result = $results;
			}
			else {
				$result = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['multicall_error'], $GLOBALS['xmlrpcstr']['multicall_error']);
			}
		}
		else {
			$fallback = true;
		}

		$results = array();

		if ($fallback) {
			foreach ($msgs as $msg) {
				$results[] = &$this->send($msg, $timeout, $method);
			}
		}
		else {
			foreach ($msgs as $msg) {
				$results[] = $result;
			}
		}

		return $results;
	}

	public function _try_multicall($msgs, $timeout, $method)
	{
		$calls = array();

		foreach ($msgs as $msg) {
			$call['methodName'] = new xmlrpcval($msg->method(), 'string');
			$numParams = $msg->getNumParams();
			$params = array();

			for ($i = 0; $i < $numParams; $i++) {
				$params[$i] = $msg->getParam($i);
			}

			$call['params'] = new xmlrpcval($params, 'array');
			$calls[] = new xmlrpcval($call, 'struct');
		}

		$multicall = new xmlrpcmsg('system.multicall');
		$multicall->addParam(new xmlrpcval($calls, 'array'));
		$result = &$this->send($multicall, $timeout, $method);

		if ($result->faultCode() != 0) {
			return $result;
		}

		$rets = $result->value();

		if ($this->return_type == 'xml') {
			return $rets;
		}
		else if ($this->return_type == 'phpvals') {
			$rets = $result->value();

			if (!is_array($rets)) {
				return false;
			}

			$numRets = count($rets);

			if ($numRets != count($msgs)) {
				return false;
			}

			$response = array();

			for ($i = 0; $i < $numRets; $i++) {
				$val = $rets[$i];

				if (!is_array($val)) {
					return false;
				}

				switch (count($val)) {
				case 1:
					if (!isset($val[0])) {
						return false;
					}

					$response[$i] = new xmlrpcresp($val[0], 0, '', 'phpvals');
					break;

				case 2:
					$code = @$val['faultCode'];

					if (!is_int($code)) {
						return false;
					}

					$str = @$val['faultString'];

					if (!is_string($str)) {
						return false;
					}

					$response[$i] = new xmlrpcresp(0, $code, $str);
					break;

				default:
					return false;
				}
			}

			return $response;
		}
		else {
			$rets = $result->value();

			if ($rets->kindOf() != 'array') {
				return false;
			}

			$numRets = $rets->arraysize();

			if ($numRets != count($msgs)) {
				return false;
			}

			$response = array();

			for ($i = 0; $i < $numRets; $i++) {
				$val = $rets->arraymem($i);

				switch ($val->kindOf()) {
				case 'array':
					if ($val->arraysize() != 1) {
						return false;
					}

					$response[$i] = new xmlrpcresp($val->arraymem(0));
					break;

				case 'struct':
					$code = $val->structmem('faultCode');
					if (($code->kindOf() != 'scalar') || ($code->scalartyp() != 'int')) {
						return false;
					}

					$str = $val->structmem('faultString');
					if (($str->kindOf() != 'scalar') || ($str->scalartyp() != 'string')) {
						return false;
					}

					$response[$i] = new xmlrpcresp(0, $code->scalarval(), $str->scalarval());
					break;

				default:
					return false;
				}
			}

			return $response;
		}
	}
}

class oo6bft5m60
{
	public $val = 0;
	public $valtyp;
	public $errno = 0;
	public $errstr = '';
	public $payload;
	public $hdrs = array();
	public $_cookies = array();
	public $content_type = 'text/xml';
	public $raw_data = '';

	public function xmlrpcresp($val, $fcode = 0, $fstr = '', $valtyp = '')
	{
		if ($fcode != 0) {
			$this->errno = $fcode;
			$this->errstr = $fstr;
		}
		else {
			$this->val = $val;

			if ($valtyp == '') {
				if (is_object($this->val) && is_a($this->val, 'xmlrpcval')) {
					$this->valtyp = 'xmlrpcvals';
				}
				else if (is_string($this->val)) {
					$this->valtyp = 'xml';
				}
				else {
					$this->valtyp = 'phpvals';
				}
			}
			else {
				$this->valtyp = $valtyp;
			}
		}
	}

	public function faultCode()
	{
		return $this->errno;
	}

	public function faultString()
	{
		return $this->errstr;
	}

	public function value()
	{
		return $this->val;
	}

	public function cookies()
	{
		return $this->_cookies;
	}

	public function serialize($charset_encoding = '')
	{
		if ($charset_encoding != '') {
			$this->content_type = 'text/xml; charset=' . $charset_encoding;
		}
		else {
			$this->content_type = 'text/xml';
		}

		$result = "<methodResponse>\n";

		if ($this->errno) {
			$result .= "<fault>\n" . "<value>\n<struct><member><name>faultCode</name>\n<value><int>" . $this->errno . "</int></value>\n</member>\n<member>\n<name>faultString</name>\n<value><string>" . xmlrpc_encode_entitites($this->errstr, $GLOBALS['xmlrpc_internalencoding'], $charset_encoding) . "</string></value>\n</member>\n" . "</struct>\n</value>\n</fault>";
		}
		else {
			if (!is_object($this->val) || !is_a($this->val, 'xmlrpcval')) {
				if (is_string($this->val) && ($this->valtyp == 'xml')) {
					$result .= "<params>\n<param>\n" . $this->val . "</param>\n</params>";
				}
				else {
					exit('cannot serialize xmlrpcresp objects whose content is native php values');
				}
			}
			else {
				$result .= "<params>\n<param>\n" . $this->val->serialize($charset_encoding) . "</param>\n</params>";
			}
		}

		$result .= "\n</methodResponse>";
		$this->payload = $result;
		return $result;
	}
}

class tw7_4jj0s
{
	public $payload;
	public $methodname;
	public $params = array();
	public $debug = 0;
	public $content_type = 'text/xml';

	public function xmlrpcmsg($meth, $pars = 0)
	{
		$this->methodname = $meth;
		if (is_array($pars) && (0 < count($pars))) {
			for ($i = 0; $i < count($pars); $i++) {
				$this->addParam($pars[$i]);
			}
		}
	}

	public function xml_header($charset_encoding = '')
	{
		if ($charset_encoding != '') {
			return '<?xml version="1.0" encoding="' . $charset_encoding . '" ?' . ">\n<methodCall>\n";
		}
		else {
			return '<?xml version="1.0"?' . ">\n<methodCall>\n";
		}
	}

	public function xml_footer()
	{
		return '</methodCall>';
	}

	public function kindOf()
	{
		return 'msg';
	}

	public function createPayload($charset_encoding = '')
	{
		if ($charset_encoding != '') {
			$this->content_type = 'text/xml; charset=' . $charset_encoding;
		}
		else {
			$this->content_type = 'text/xml';
		}

		$this->payload = $this->xml_header($charset_encoding);
		$this->payload .= '<methodName>' . $this->methodname . "</methodName>\n";
		$this->payload .= "<params>\n";

		for ($i = 0; $i < count($this->params); $i++) {
			$p = $this->params[$i];
			$this->payload .= "<param>\n" . $p->serialize($charset_encoding) . "</param>\n";
		}

		$this->payload .= "</params>\n";
		$this->payload .= $this->xml_footer();
	}

	public function method($meth = '')
	{
		if ($meth != '') {
			$this->methodname = $meth;
		}

		return $this->methodname;
	}

	public function serialize($charset_encoding = '')
	{
		$this->createPayload($charset_encoding);
		return $this->payload;
	}

	public function addParam($par)
	{
		if (is_object($par) && is_a($par, 'xmlrpcval')) {
			$this->params[] = $par;
			return true;
		}
		else {
			return false;
		}
	}

	public function getParam($i)
	{
		return $this->params[$i];
	}

	public function getNumParams()
	{
		return count($this->params);
	}

	public function& parseResponseFile($fp)
	{
		$ipd = '';

		while ($data = fread($fp, 32768)) {
			$ipd .= $data;
		}

		$r = &$this->parseResponse($ipd);
		return $r;
	}

	public function& parseResponseHeaders(&$data, $headers_processed = false)
	{
		if (preg_match('/^HTTP\\/1\\.[0-1] 200 Connection established/', $data)) {
			$pos = strpos($data, "\r\n\r\n");
			if ($pos || is_int($pos)) {
				$bd = $pos + 4;
			}
			else {
				$pos = strpos($data, "\n\n");
				if ($pos || is_int($pos)) {
					$bd = $pos + 2;
				}
				else {
					$bd = 0;
				}
			}

			if ($bd) {
				$data = substr($data, $bd);
			}
			else {
				error_log('XML-RPC: ' . 'xmlrpcmsg::parseResponseHeaders' . ': HTTPS via proxy error, tunnel connection possibly failed');
				$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['http_error'], $GLOBALS['xmlrpcstr']['http_error'] . ' (HTTPS via proxy error, tunnel connection possibly failed)');
				return $r;
			}
		}

		while (preg_match('/^HTTP\\/1\\.1 1[0-9]{2} /', $data)) {
			$pos = strpos($data, 'HTTP', 12);
			if (!$pos && !is_int($pos)) {
				break;
			}

			$data = substr($data, $pos);
		}

		if (!preg_match('/^HTTP\\/[0-9.]+ 200 /', $data)) {
			$errstr = substr($data, 0, strpos($data, "\n") - 1);
			error_log('XML-RPC: ' . 'xmlrpcmsg::parseResponseHeaders' . ': HTTP error, got response: ' . $errstr);
			$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['http_error'], $GLOBALS['xmlrpcstr']['http_error'] . ' (' . $errstr . ')');
			return $r;
		}

		$GLOBALS['_xh']['headers'] = array();
		$GLOBALS['_xh']['cookies'] = array();
		$pos = strpos($data, "\r\n\r\n");
		if ($pos || is_int($pos)) {
			$bd = $pos + 4;
		}
		else {
			$pos = strpos($data, "\n\n");
			if ($pos || is_int($pos)) {
				$bd = $pos + 2;
			}
			else {
				$bd = 0;
			}
		}

		$ar = preg_split("/\r?\n/", trim(substr($data, 0, $pos)));

		while (list(, $line) = @each($ar)) {
			$arr = explode(':', $line, 2);

			if (1 < count($arr)) {
				$header_name = strtolower(trim($arr[0]));
				if (($header_name == 'set-cookie') || ($header_name == 'set-cookie2')) {
					if ($header_name == 'set-cookie2') {
						$cookies = explode(',', $arr[1]);
					}
					else {
						$cookies = array($arr[1]);
					}

					foreach ($cookies as $cookie) {
						if (isset($GLOBALS['_xh']['headers'][$header_name])) {
							$GLOBALS['_xh']['headers'][$header_name] .= ', ' . trim($cookie);
						}
						else {
							$GLOBALS['_xh']['headers'][$header_name] = trim($cookie);
						}

						$cookie = explode(';', $cookie);

						foreach ($cookie as $pos => $val) {
							$val = explode('=', $val, 2);
							$tag = trim($val[0]);
							$val = trim(@$val[1]);

							if ($pos == 0) {
								$cookiename = $tag;
								$GLOBALS['_xh']['cookies'][$tag] = array();
								$GLOBALS['_xh']['cookies'][$cookiename]['value'] = urldecode($val);
							}
							else if ($tag != 'value') {
								$GLOBALS['_xh']['cookies'][$cookiename][$tag] = $val;
							}
						}
					}
				}
				else {
					$GLOBALS['_xh']['headers'][$header_name] = trim($arr[1]);
				}
			}
			else if (isset($header_name)) {
				$GLOBALS['_xh']['headers'][$header_name] .= ' ' . trim($line);
			}
		}

		$data = substr($data, $bd);
		if ($this->debug && count($GLOBALS['_xh']['headers'])) {
			print('<PRE>');

			foreach ($GLOBALS['_xh']['headers'] as $header => $value) {
				print(htmlentities('HEADER: ' . $header . ': ' . $value . "\n"));
			}

			foreach ($GLOBALS['_xh']['cookies'] as $header => $value) {
				print(htmlentities('COOKIE: ' . $header . '=' . $value['value'] . "\n"));
			}

			print("</PRE>\n");
		}

		if (!$headers_processed) {
			if (isset($GLOBALS['_xh']['headers']['transfer-encoding']) && ($GLOBALS['_xh']['headers']['transfer-encoding'] == 'chunked')) {
				if (!($data = decode_chunked($data))) {
					error_log('XML-RPC: ' . 'xmlrpcmsg::parseResponseHeaders' . ': errors occurred when trying to rebuild the chunked data received from server');
					$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['dechunk_fail'], $GLOBALS['xmlrpcstr']['dechunk_fail']);
					return $r;
				}
			}

			if (isset($GLOBALS['_xh']['headers']['content-encoding'])) {
				$GLOBALS['_xh']['headers']['content-encoding'] = str_replace('x-', '', $GLOBALS['_xh']['headers']['content-encoding']);
				if (($GLOBALS['_xh']['headers']['content-encoding'] == 'deflate') || ($GLOBALS['_xh']['headers']['content-encoding'] == 'gzip')) {
					if (function_exists('gzinflate')) {
						if (($GLOBALS['_xh']['headers']['content-encoding'] == 'deflate') && ($degzdata = @gzuncompress($data))) {
							$data = $degzdata;

							if ($this->debug) {
								print('<PRE>---INFLATED RESPONSE---[' . strlen($data) . " chars]---\n" . htmlentities($data) . "\n---END---</PRE>");
							}
						}
						else {
							if (($GLOBALS['_xh']['headers']['content-encoding'] == 'gzip') && ($degzdata = @gzinflate(substr($data, 10)))) {
								$data = $degzdata;

								if ($this->debug) {
									print('<PRE>---INFLATED RESPONSE---[' . strlen($data) . " chars]---\n" . htmlentities($data) . "\n---END---</PRE>");
								}
							}
							else {
								error_log('XML-RPC: ' . 'xmlrpcmsg::parseResponseHeaders' . ': errors occurred when trying to decode the deflated data received from server');
								$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['decompress_fail'], $GLOBALS['xmlrpcstr']['decompress_fail']);
								return $r;
							}
						}
					}
					else {
						error_log('XML-RPC: ' . 'xmlrpcmsg::parseResponseHeaders' . ': the server sent deflated data. Your php install must have the Zlib extension compiled in to support this.');
						$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['cannot_decompress'], $GLOBALS['xmlrpcstr']['cannot_decompress']);
						return $r;
					}
				}
			}
		}

		$r = NULL;
		$r = &$r;
		return $r;
	}

	public function& parseResponse($data = '', $headers_processed = false, $return_type = 'xmlrpcvals')
	{
		if ($this->debug) {
			print("<PRE>---GOT---\n" . htmlentities($data) . "\n---END---\n</PRE>");
		}

		if ($data == '') {
			error_log('XML-RPC: ' . 'xmlrpcmsg::parseResponse' . ': no response received from server.');
			$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['no_data'], $GLOBALS['xmlrpcstr']['no_data']);
			return $r;
		}

		$GLOBALS['_xh'] = array();
		$raw_data = $data;

		if (substr($data, 0, 4) == 'HTTP') {
			$r = &$this->parseResponseHeaders($data, $headers_processed);

			if ($r) {
				$r->raw_data = $data;
				return $r;
			}
		}
		else {
			$GLOBALS['_xh']['headers'] = array();
			$GLOBALS['_xh']['cookies'] = array();
		}

		if ($this->debug) {
			$start = strpos($data, '<!-- SERVER DEBUG INFO (BASE64 ENCODED):');

			if ($start) {
				$start += strlen('<!-- SERVER DEBUG INFO (BASE64 ENCODED):');
				$end = strpos($data, '-->', $start);
				$comments = substr($data, $start, $end - $start);
				print("<PRE>---SERVER DEBUG INFO (DECODED) ---\n\t" . htmlentities(str_replace("\n", "\n\t", base64_decode($comments))) . "\n---END---\n</PRE>");
			}
		}

		$data = trim($data);
		$pos = strrpos($data, '</methodResponse>');

		if ($pos !== false) {
			$data = substr($data, 0, $pos + 17);
		}

		if ($return_type == 'xml') {
			$r = new xmlrpcresp($data, 0, '', 'xml');
			$r->hdrs = $GLOBALS['_xh']['headers'];
			$r->_cookies = $GLOBALS['_xh']['cookies'];
			$r->raw_data = $raw_data;
			return $r;
		}

		$resp_encoding = guess_encoding(@$GLOBALS['_xh']['headers']['content-type'], $data);
		$GLOBALS['_xh']['ac'] = '';
		$GLOBALS['_xh']['stack'] = array();
		$GLOBALS['_xh']['valuestack'] = array();
		$GLOBALS['_xh']['isf'] = 0;
		$GLOBALS['_xh']['isf_reason'] = '';
		$GLOBALS['_xh']['rt'] = '';

		if (!in_array($resp_encoding, array('UTF-8', 'ISO-8859-1', 'US-ASCII'))) {
			error_log('XML-RPC: ' . 'xmlrpcmsg::parseResponse' . ': invalid charset encoding of received response: ' . $resp_encoding);
			$resp_encoding = $GLOBALS['xmlrpc_defencoding'];
		}

		$parser = xml_parser_create($resp_encoding);
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);

		if (!in_array($GLOBALS['xmlrpc_internalencoding'], array('UTF-8', 'ISO-8859-1', 'US-ASCII'))) {
			xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
		}
		else {
			xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $GLOBALS['xmlrpc_internalencoding']);
		}

		if ($return_type == 'phpvals') {
			xml_set_element_handler($parser, 'xmlrpc_se', 'xmlrpc_ee_fast');
		}
		else {
			xml_set_element_handler($parser, 'xmlrpc_se', 'xmlrpc_ee');
		}

		xml_set_character_data_handler($parser, 'xmlrpc_cd');
		xml_set_default_handler($parser, 'xmlrpc_dh');

		if (!xml_parse($parser, $data, count($data))) {
			if (xml_get_current_line_number($parser) == 1) {
				$errstr = 'XML error at line 1, check URL';
			}
			else {
				$errstr = sprintf('XML error: %s at line %d, column %d', xml_error_string(xml_get_error_code($parser)), xml_get_current_line_number($parser), xml_get_current_column_number($parser));
			}

			error_log($errstr);
			$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['invalid_return'], $GLOBALS['xmlrpcstr']['invalid_return'] . ' (' . $errstr . ')');
			xml_parser_free($parser);

			if ($this->debug) {
				print($errstr);
			}

			$r->hdrs = $GLOBALS['_xh']['headers'];
			$r->_cookies = $GLOBALS['_xh']['cookies'];
			$r->raw_data = $raw_data;
			return $r;
		}

		xml_parser_free($parser);

		if (1 < $GLOBALS['_xh']['isf']) {
			if ($this->debug) {
			}

			$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['invalid_return'], $GLOBALS['xmlrpcstr']['invalid_return'] . ' ' . $GLOBALS['_xh']['isf_reason']);
		}
		else {
			if (($return_type == 'xmlrpcvals') && !is_object($GLOBALS['_xh']['value'])) {
				$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['invalid_return'], $GLOBALS['xmlrpcstr']['invalid_return']);
			}
			else {
				if ($this->debug) {
					print("<PRE>---PARSED---\n");
					print(htmlspecialchars(var_export($GLOBALS['_xh']['value'], true)));
					print("\n---END---</PRE>");
				}

				$v = &$GLOBALS['_xh']['value'];

				if ($GLOBALS['_xh']['isf']) {
					if ($return_type == 'xmlrpcvals') {
						$errno_v = $v->structmem('faultCode');
						$errstr_v = $v->structmem('faultString');
						$errno = $errno_v->scalarval();
						$errstr = $errstr_v->scalarval();
					}
					else {
						$errno = $v['faultCode'];
						$errstr = $v['faultString'];
					}

					if ($errno == 0) {
						$errno = -1;
					}

					$r = new xmlrpcresp(0, $errno, $errstr);
				}
				else {
					$r = new xmlrpcresp($v, 0, '', $return_type);
				}
			}
		}

		$r->hdrs = $GLOBALS['_xh']['headers'];
		$r->_cookies = $GLOBALS['_xh']['cookies'];
		$r->raw_data = $raw_data;
		return $r;
	}
}

class xmlrpcval
{
	public $me = array();
	public $mytype = 0;
	public $_php_class;

	public function xmlrpcval($val = -1, $type = '')
	{
		if (($val !== -1) || ($type != '')) {
			switch ($type) {
			case '':
				$this->mytype = 1;
				$this->me['string'] = $val;
				break;

			case 'i4':
			case 'int':
			case 'double':
			case 'string':
			case 'boolean':
			case 'dateTime.iso8601':
			case 'base64':
			case 'null':
				$this->mytype = 1;
				$this->me[$type] = $val;
				break;

			case 'array':
				$this->mytype = 2;
				$this->me['array'] = $val;
				break;

			case 'struct':
				$this->mytype = 3;
				$this->me['struct'] = $val;
				break;

			default:
				error_log('XML-RPC: ' . 'xmlrpcval::xmlrpcval' . ': not a known type (' . $type . ')');
			}
		}
	}

	public function addScalar($val, $type = 'string')
	{
		$typeof = @$GLOBALS['xmlrpcTypes'][$type];

		if ($typeof != 1) {
			error_log('XML-RPC: ' . 'xmlrpcval::addScalar' . ': not a scalar type (' . $type . ')');
			return 0;
		}

		if ($type == $GLOBALS['xmlrpcBoolean']) {
			if ((strcasecmp($val, 'true') == 0) || ($val == 1) || (($val == true) && strcasecmp($val, 'false'))) {
				$val = true;
			}
			else {
				$val = false;
			}
		}

		switch ($this->mytype) {
		case 1:
			error_log('XML-RPC: ' . 'xmlrpcval::addScalar' . ': scalar xmlrpcval can have only one value');
			return 0;
		case 3:
			error_log('XML-RPC: ' . 'xmlrpcval::addScalar' . ': cannot add anonymous scalar to struct xmlrpcval');
			return 0;
		case 2:
			$this->me['array'][] = new xmlrpcval($val, $type);
			return 1;
		default:
			$this->me[$type] = $val;
			$this->mytype = $typeof;
			return 1;
		}
	}

	public function addArray($vals)
	{
		if ($this->mytype == 0) {
			$this->mytype = $GLOBALS['xmlrpcTypes']['array'];
			$this->me['array'] = $vals;
			return 1;
		}
		else if ($this->mytype == 2) {
			$this->me['array'] = array_merge($this->me['array'], $vals);
			return 1;
		}
		else {
			error_log('XML-RPC: ' . 'xmlrpcval::addArray' . ': already initialized as a [' . $this->kindOf() . ']');
			return 0;
		}
	}

	public function addStruct($vals)
	{
		if ($this->mytype == 0) {
			$this->mytype = $GLOBALS['xmlrpcTypes']['struct'];
			$this->me['struct'] = $vals;
			return 1;
		}
		else if ($this->mytype == 3) {
			$this->me['struct'] = array_merge($this->me['struct'], $vals);
			return 1;
		}
		else {
			error_log('XML-RPC: ' . 'xmlrpcval::addStruct' . ': already initialized as a [' . $this->kindOf() . ']');
			return 0;
		}
	}

	public function dump($ar)
	{
		foreach ($ar as $key => $val) {
			echo $key . ' => ' . $val . '<br />';

			if ($key == 'array') {
				while (list($key2, $val2) = each($val)) {
					echo '-- ' . $key2 . ' => ' . $val2 . '<br />';
				}
			}
		}
	}

	public function kindOf()
	{
		switch ($this->mytype) {
		case 3:
			return 'struct';
			break;

		case 2:
			return 'array';
			break;

		case 1:
			return 'scalar';
			break;

		default:
			return 'undef';
		}
	}

	public function serializedata($typ, $val, $charset_encoding = '')
	{
		$rs = '';

		switch (@$GLOBALS['xmlrpcTypes'][$typ]) {
		case 1:
			switch ($typ) {
			case $GLOBALS['xmlrpcBase64']:
				$rs .= '<' . $typ . '>' . base64_encode($val) . '</' . $typ . '>';
				break;

			case $GLOBALS['xmlrpcBoolean']:
				$rs .= '<' . $typ . '>' . ($val ? '1' : '0') . '</' . $typ . '>';
				break;

			case $GLOBALS['xmlrpcString']:
				$rs .= '<' . $typ . '>' . xmlrpc_encode_entitites($val, $GLOBALS['xmlrpc_internalencoding'], $charset_encoding) . '</' . $typ . '>';
				break;

			case $GLOBALS['xmlrpcInt']:
			case $GLOBALS['xmlrpcI4']:
				$rs .= '<' . $typ . '>' . (int) $val . '</' . $typ . '>';
				break;

			case $GLOBALS['xmlrpcDouble']:
				$rs .= '<' . $typ . '>' . preg_replace('/\\.?0+$/', '', number_format((double) $val, 128, '.', '')) . '</' . $typ . '>';
				break;

			case $GLOBALS['xmlrpcDateTime']:
				if (is_string($val)) {
					$rs .= '<' . $typ . '>' . $val . '</' . $typ . '>';
				}
				else if (is_a($val, 'DateTime')) {
					$rs .= '<' . $typ . '>' . $val->format('Ymd\\TH:i:s') . '</' . $typ . '>';
				}
				else if (is_int($val)) {
					$rs .= '<' . $typ . '>' . strftime('%Y%m%dT%H:%M:%S', $val) . '</' . $typ . '>';
				}
				else {
					$rs .= '<' . $typ . '>' . $val . '</' . $typ . '>';
				}

				break;

			case $GLOBALS['xmlrpcNull']:
				if ($GLOBALS['xmlrpc_null_apache_encoding']) {
					$rs .= '<ex:nil/>';
				}
				else {
					$rs .= '<nil/>';
				}

				break;

			default:
				$rs .= '<' . $typ . '>' . $val . '</' . $typ . '>';
			}

			break;

		case 3:
			if ($this->_php_class) {
				$rs .= '<struct php_class="' . $this->_php_class . "\">\n";
			}
			else {
				$rs .= "<struct>\n";
			}

			foreach ($val as $key2 => $val2) {
				$rs .= '<member><name>' . xmlrpc_encode_entitites($key2, $GLOBALS['xmlrpc_internalencoding'], $charset_encoding) . "</name>\n";
				$rs .= $val2->serialize($charset_encoding);
				$rs .= "</member>\n";
			}

			$rs .= '</struct>';
			break;

		case 2:
			$rs .= "<array>\n<data>\n";

			for ($i = 0; $i < count($val); $i++) {
				$rs .= $val[$i]->serialize($charset_encoding);
			}

			$rs .= "</data>\n</array>";
			break;

		default:
			break;
		}

		return $rs;
	}

	public function serialize($charset_encoding = '')
	{
		reset($this->me);
		list($typ, $val) = each($this->me);
		return '<value>' . $this->serializedata($typ, $val, $charset_encoding) . "</value>\n";
	}

	public function serializeval($o)
	{
		$ar = $o->me;
		reset($ar);
		list($typ, $val) = each($ar);
		return '<value>' . $this->serializedata($typ, $val) . "</value>\n";
	}

	public function structmemexists($m)
	{
		return array_key_exists($m, $this->me['struct']);
	}

	public function structmem($m)
	{
		return $this->me['struct'][$m];
	}

	public function structreset()
	{
		reset($this->me['struct']);
	}

	public function structeach()
	{
		return each($this->me['struct']);
	}

	public function getval()
	{
		reset($this->me);
		list($a, $b) = each($this->me);

		if (is_array($b)) {
			@reset($b);

			while (list($id, $cont) = @each($b)) {
				$b[$id] = $cont->scalarval();
			}
		}

		if (is_object($b)) {
			$t = get_object_vars($b);
			@reset($t);

			while (list($id, $cont) = @each($t)) {
				$t[$id] = $cont->scalarval();
			}

			@reset($t);

			while (list($id, $cont) = @each($t)) {
				@$b->$id = $cont;
			}
		}

		return $b;
	}

	public function scalarval()
	{
		reset($this->me);
		list(, $b) = each($this->me);
		return $b;
	}

	public function scalartyp()
	{
		reset($this->me);
		list($a) = each($this->me);

		if ($a == $GLOBALS['xmlrpcI4']) {
			$a = $GLOBALS['xmlrpcInt'];
		}

		return $a;
	}

	public function arraymem($m)
	{
		return $this->me['array'][$m];
	}

	public function arraysize()
	{
		return count($this->me['array']);
	}

	public function structsize()
	{
		return count($this->me['struct']);
	}
}

function xmlrpc_encode_entitites($data, $src_encoding = '', $dest_encoding = '')
{
	if ($src_encoding == '') {
		$src_encoding = $GLOBALS['xmlrpc_internalencoding'];
	}

	switch (strtoupper($src_encoding . '_' . $dest_encoding)) {
	case 'ISO-8859-1_':
	case 'ISO-8859-1_US-ASCII':
		$escaped_data = str_replace(array('&', '"', '\'', '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $data);
		$escaped_data = str_replace($GLOBALS['xml_iso88591_Entities']['in'], $GLOBALS['xml_iso88591_Entities']['out'], $escaped_data);
		break;

	case 'ISO-8859-1_UTF-8':
		$escaped_data = str_replace(array('&', '"', '\'', '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $data);
		$escaped_data = utf8_encode($escaped_data);
		break;

	case 'ISO-8859-1_ISO-8859-1':
	case 'US-ASCII_US-ASCII':
	case 'US-ASCII_UTF-8':
	case 'US-ASCII_':
	case 'US-ASCII_ISO-8859-1':
	case 'UTF-8_UTF-8':
		$escaped_data = str_replace(array('&', '"', '\'', '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $data);
		break;

	case 'UTF-8_':
	case 'UTF-8_US-ASCII':
	case 'UTF-8_ISO-8859-1':
		$escaped_data = '';
		$data = (string) $data;
		$ns = strlen($data);

		for ($nn = 0; $nn < $ns; $nn++) {
			$ch = $data[$nn];
			$ii = ord($ch);

			if ($ii < 128) {
				switch ($ii) {
				case 34:
					$escaped_data .= '&quot;';
					break;

				case 38:
					$escaped_data .= '&amp;';
					break;

				case 39:
					$escaped_data .= '&apos;';
					break;

				case 60:
					$escaped_data .= '&lt;';
					break;

				case 62:
					$escaped_data .= '&gt;';
					break;

				default:
					$escaped_data .= $ch;
				}
			}
			else if (($ii >> 5) == 6) {
				$b1 = $ii & 31;
				$ii = ord($data[$nn + 1]);
				$b2 = $ii & 63;
				$ii = ($b1 * 64) + $b2;
				$ent = sprintf('&#%d;', $ii);
				$escaped_data .= $ent;
				$nn += 1;
			}
			else if (($ii >> 4) == 14) {
				$b1 = $ii & 15;
				$ii = ord($data[$nn + 1]);
				$b2 = $ii & 63;
				$ii = ord($data[$nn + 2]);
				$b3 = $ii & 63;
				$ii = ((($b1 * 64) + $b2) * 64) + $b3;
				$ent = sprintf('&#%d;', $ii);
				$escaped_data .= $ent;
				$nn += 2;
			}
			else if (($ii >> 3) == 30) {
				$b1 = $ii & 7;
				$ii = ord($data[$nn + 1]);
				$b2 = $ii & 63;
				$ii = ord($data[$nn + 2]);
				$b3 = $ii & 63;
				$ii = ord($data[$nn + 3]);
				$b4 = $ii & 63;
				$ii = ((((($b1 * 64) + $b2) * 64) + $b3) * 64) + $b4;
				$ent = sprintf('&#%d;', $ii);
				$escaped_data .= $ent;
				$nn += 3;
			}
		}

		break;

	default:
		$escaped_data = '';
		error_log('Converting from ' . $src_encoding . ' to ' . $dest_encoding . ': not supported...');
	}

	return $escaped_data;
}

function xmlrpc_se($parser, $name, $attrs, $accept_single_vals = false)
{
	if ($GLOBALS['_xh']['isf'] < 2) {
		if (count($GLOBALS['_xh']['stack']) == 0) {
			if (($name != 'METHODRESPONSE') && ($name != 'METHODCALL') && ($name != 'VALUE') && !$accept_single_vals) {
				$GLOBALS['_xh']['isf'] = 2;
				$GLOBALS['_xh']['isf_reason'] = 'missing top level xmlrpc element';
				return NULL;
			}
			else {
				$GLOBALS['_xh']['rt'] = strtolower($name);
				$GLOBALS['_xh']['rt'] = strtolower($name);
			}
		}
		else {
			$parent = end($GLOBALS['_xh']['stack']);
			if (!array_key_exists($name, $GLOBALS['xmlrpc_valid_parents']) || !in_array($parent, $GLOBALS['xmlrpc_valid_parents'][$name])) {
				$GLOBALS['_xh']['isf'] = 2;
				$GLOBALS['_xh']['isf_reason'] = 'xmlrpc element ' . $name . ' cannot be child of ' . $parent;
				return NULL;
			}
		}

		switch ($name) {
		case 'VALUE':
			$GLOBALS['_xh']['vt'] = 'value';
			$GLOBALS['_xh']['ac'] = '';
			$GLOBALS['_xh']['lv'] = 1;
			$GLOBALS['_xh']['php_class'] = NULL;
			break;

		case 'I4':
		case 'INT':
		case 'STRING':
		case 'BOOLEAN':
		case 'DOUBLE':
		case 'DATETIME.ISO8601':
		case 'BASE64':
			if ($GLOBALS['_xh']['vt'] != 'value') {
				$GLOBALS['_xh']['isf'] = 2;
				$GLOBALS['_xh']['isf_reason'] = $name . ' element following a ' . $GLOBALS['_xh']['vt'] . ' element inside a single value';
				return NULL;
			}

			$GLOBALS['_xh']['ac'] = '';
			break;

		case 'STRUCT':
		case 'ARRAY':
			if ($GLOBALS['_xh']['vt'] != 'value') {
				$GLOBALS['_xh']['isf'] = 2;
				$GLOBALS['_xh']['isf_reason'] = $name . ' element following a ' . $GLOBALS['_xh']['vt'] . ' element inside a single value';
				return NULL;
			}

			$cur_val = array();
			$cur_val['values'] = array();
			$cur_val['type'] = $name;

			if (@isset($attrs['PHP_CLASS'])) {
				$cur_val['php_class'] = $attrs['PHP_CLASS'];
			}

			$GLOBALS['_xh']['valuestack'][] = $cur_val;
			$GLOBALS['_xh']['vt'] = 'data';
			break;

		case 'DATA':
			if ($GLOBALS['_xh']['vt'] != 'data') {
				$GLOBALS['_xh']['isf'] = 2;
				$GLOBALS['_xh']['isf_reason'] = 'found two data elements inside an array element';
				return NULL;
			}
		case 'METHODCALL':
		case 'METHODRESPONSE':
		case 'PARAMS':
			break;

		case 'METHODNAME':
		case 'NAME':
			$GLOBALS['_xh']['ac'] = '';
			break;

		case 'FAULT':
			$GLOBALS['_xh']['isf'] = 1;
			break;

		case 'MEMBER':
			$GLOBALS['_xh']['valuestack'][count($GLOBALS['_xh']['valuestack']) - 1]['name'] = '';
		case 'PARAM':
			$GLOBALS['_xh']['vt'] = NULL;
			break;

		case 'NIL':
		case 'EX:NIL':
			if ($GLOBALS['xmlrpc_null_extension']) {
				if ($GLOBALS['_xh']['vt'] != 'value') {
					$GLOBALS['_xh']['isf'] = 2;
					$GLOBALS['_xh']['isf_reason'] = $name . ' element following a ' . $GLOBALS['_xh']['vt'] . ' element inside a single value';
					return NULL;
				}

				$GLOBALS['_xh']['ac'] = '';
				break;
			}
		default:
			$GLOBALS['_xh']['isf'] = 2;
			$GLOBALS['_xh']['isf_reason'] = 'found not-xmlrpc xml element ' . $name;
			break;
		}

		$GLOBALS['_xh']['stack'][] = $name;

		if ($name != 'VALUE') {
			$GLOBALS['_xh']['lv'] = 0;
		}
	}
}

function xmlrpc_se_any($parser, $name, $attrs)
{
	xmlrpc_se($parser, $name, $attrs, true);
}

function xmlrpc_ee($parser, $name, $rebuild_xmlrpcvals = true)
{
	if ($GLOBALS['_xh']['isf'] < 2) {
		$curr_elem = array_pop($GLOBALS['_xh']['stack']);

		switch ($name) {
		case 'VALUE':
			if ($GLOBALS['_xh']['vt'] == 'value') {
				$GLOBALS['_xh']['value'] = $GLOBALS['_xh']['ac'];
				$GLOBALS['_xh']['vt'] = $GLOBALS['xmlrpcString'];
			}

			if ($rebuild_xmlrpcvals) {
				$temp = new xmlrpcval($GLOBALS['_xh']['value'], $GLOBALS['_xh']['vt']);

				if (isset($GLOBALS['_xh']['php_class'])) {
					$temp->_php_class = $GLOBALS['_xh']['php_class'];
				}

				$vscount = count($GLOBALS['_xh']['valuestack']);
				if ($vscount && ($GLOBALS['_xh']['valuestack'][$vscount - 1]['type'] == 'ARRAY')) {
					$GLOBALS['_xh']['valuestack'][$vscount - 1]['values'][] = $temp;
				}
				else {
					$GLOBALS['_xh']['value'] = $temp;
				}
			}
			else {
				if (isset($GLOBALS['_xh']['php_class'])) {
				}

				$vscount = count($GLOBALS['_xh']['valuestack']);
				if ($vscount && ($GLOBALS['_xh']['valuestack'][$vscount - 1]['type'] == 'ARRAY')) {
					$GLOBALS['_xh']['valuestack'][$vscount - 1]['values'][] = $GLOBALS['_xh']['value'];
				}
			}

			break;

		case 'BOOLEAN':
		case 'I4':
		case 'INT':
		case 'STRING':
		case 'DOUBLE':
		case 'DATETIME.ISO8601':
		case 'BASE64':
			$GLOBALS['_xh']['vt'] = strtolower($name);

			if ($name == 'STRING') {
				$GLOBALS['_xh']['value'] = $GLOBALS['_xh']['ac'];
			}
			else if ($name == 'DATETIME.ISO8601') {
				if (!preg_match('/^[0-9]{8}T[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $GLOBALS['_xh']['ac'])) {
					error_log('XML-RPC: invalid value received in DATETIME: ' . $GLOBALS['_xh']['ac']);
				}

				$GLOBALS['_xh']['vt'] = $GLOBALS['xmlrpcDateTime'];
				$GLOBALS['_xh']['value'] = $GLOBALS['_xh']['ac'];
			}
			else if ($name == 'BASE64') {
				$GLOBALS['_xh']['value'] = base64_decode($GLOBALS['_xh']['ac']);
			}
			else if ($name == 'BOOLEAN') {
				if (($GLOBALS['_xh']['ac'] == '1') || (strcasecmp($GLOBALS['_xh']['ac'], 'true') == 0)) {
					$GLOBALS['_xh']['value'] = true;
				}
				else {
					if (($GLOBALS['_xh']['ac'] != '0') && (strcasecmp($GLOBALS['_xh']['ac'], 'false') != 0)) {
						error_log('XML-RPC: invalid value received in BOOLEAN: ' . $GLOBALS['_xh']['ac']);
					}

					$GLOBALS['_xh']['value'] = false;
				}
			}
			else if ($name == 'DOUBLE') {
				if (!preg_match('/^[+-eE0123456789 \\t.]+$/', $GLOBALS['_xh']['ac'])) {
					error_log('XML-RPC: non numeric value received in DOUBLE: ' . $GLOBALS['_xh']['ac']);
					$GLOBALS['_xh']['value'] = 'ERROR_NON_NUMERIC_FOUND';
				}
				else {
					$GLOBALS['_xh']['value'] = (double) $GLOBALS['_xh']['ac'];
				}
			}
			else if (!preg_match('/^[+-]?[0123456789 \\t]+$/', $GLOBALS['_xh']['ac'])) {
				error_log('XML-RPC: non numeric value received in INT: ' . $GLOBALS['_xh']['ac']);
				$GLOBALS['_xh']['value'] = 'ERROR_NON_NUMERIC_FOUND';
			}
			else {
				$GLOBALS['_xh']['value'] = (int) $GLOBALS['_xh']['ac'];
			}

			$GLOBALS['_xh']['lv'] = 3;
			break;

		case 'NAME':
			$GLOBALS['_xh']['valuestack'][count($GLOBALS['_xh']['valuestack']) - 1]['name'] = $GLOBALS['_xh']['ac'];
			break;

		case 'MEMBER':
			if ($GLOBALS['_xh']['vt']) {
				$vscount = count($GLOBALS['_xh']['valuestack']);
				$GLOBALS['_xh']['valuestack'][$vscount - 1]['values'][$GLOBALS['_xh']['valuestack'][$vscount - 1]['name']] = $GLOBALS['_xh']['value'];
			}
			else {
				error_log('XML-RPC: missing VALUE inside STRUCT in received xml');
			}

			break;

		case 'DATA':
			$GLOBALS['_xh']['vt'] = NULL;
			break;

		case 'STRUCT':
		case 'ARRAY':
			$curr_val = array_pop($GLOBALS['_xh']['valuestack']);
			$GLOBALS['_xh']['value'] = $curr_val['values'];
			$GLOBALS['_xh']['vt'] = strtolower($name);

			if (isset($curr_val['php_class'])) {
				$GLOBALS['_xh']['php_class'] = $curr_val['php_class'];
			}

			break;

		case 'PARAM':
			if ($GLOBALS['_xh']['vt']) {
				$GLOBALS['_xh']['params'][] = $GLOBALS['_xh']['value'];
				$GLOBALS['_xh']['pt'][] = $GLOBALS['_xh']['vt'];
			}
			else {
				error_log('XML-RPC: missing VALUE inside PARAM in received xml');
			}

			break;

		case 'METHODNAME':
			$GLOBALS['_xh']['method'] = preg_replace('/^[\\n\\r\\t ]+/', '', $GLOBALS['_xh']['ac']);
			break;

		case 'NIL':
		case 'EX:NIL':
			if ($GLOBALS['xmlrpc_null_extension']) {
				$GLOBALS['_xh']['vt'] = 'null';
				$GLOBALS['_xh']['value'] = NULL;
				$GLOBALS['_xh']['lv'] = 3;
				break;
			}
		case 'PARAMS':
		case 'FAULT':
		case 'METHODCALL':
		case 'METHORESPONSE':
			break;

		default:
			break;
		}
	}
}

function xmlrpc_ee_fast($parser, $name)
{
	xmlrpc_ee($parser, $name, false);
}

function xmlrpc_cd($parser, $data)
{
	if ($GLOBALS['_xh']['isf'] < 2) {
		if ($GLOBALS['_xh']['lv'] != 3) {
			$GLOBALS['_xh']['ac'] .= $data;
		}
	}
}

function xmlrpc_dh($parser, $data)
{
	if ($GLOBALS['_xh']['isf'] < 2) {
		if ((substr($data, 0, 1) == '&') && (substr($data, -1, 1) == ';')) {
			$GLOBALS['_xh']['ac'] .= $data;
		}
	}

	return true;
}

function iso8601_encode($timet, $utc = 0)
{
	if (!$utc) {
		$t = strftime('%Y%m%dT%H:%M:%S', $timet);
	}
	else if (function_exists('gmstrftime')) {
		$t = gmstrftime('%Y%m%dT%H:%M:%S', $timet);
	}
	else {
		$t = strftime('%Y%m%dT%H:%M:%S', $timet - date('Z'));
	}

	return $t;
}

function iso8601_decode($idate, $utc = 0)
{
	$t = 0;

	if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})/', $idate, $regs)) {
		if ($utc) {
			$t = gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
		}
		else {
			$t = mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
		}
	}

	return $t;
}

function php_xmlrpc_decode($xmlrpc_val, $options = array())
{
	switch ($xmlrpc_val->kindOf()) {
	case 'scalar':
		if (in_array('extension_api', $options)) {
			reset($xmlrpc_val->me);
			list($typ, $val) = each($xmlrpc_val->me);

			switch ($typ) {
			case 'dateTime.iso8601':
				$xmlrpc_val->scalar = $val;
				$xmlrpc_val->xmlrpc_type = 'datetime';
				$xmlrpc_val->timestamp = iso8601_decode($val);
				return $xmlrpc_val;
			case 'base64':
				$xmlrpc_val->scalar = $val;
				$xmlrpc_val->type = $typ;
				return $xmlrpc_val;
			default:
				return $xmlrpc_val->scalarval();
			}
		}

		if (in_array('dates_as_objects', $options) && ($xmlrpc_val->scalartyp() == 'dateTime.iso8601')) {
			$out = $xmlrpc_val->scalarval();

			if (is_string($out)) {
				$out = strtotime($out);
			}

			if (is_int($out)) {
				$result = new Datetime();
				$result->setTimestamp($out);
				return $result;
			}
			else if (is_a($out, 'Datetime')) {
				return $out;
			}
		}

		return $xmlrpc_val->scalarval();
	case 'array':
		$size = $xmlrpc_val->arraysize();
		$arr = array();

		for ($i = 0; $i < $size; $i++) {
			$arr[] = php_xmlrpc_decode($xmlrpc_val->arraymem($i), $options);
		}

		return $arr;
	case 'struct':
		$xmlrpc_val->structreset();
		if (in_array('decode_php_objs', $options) && ($xmlrpc_val->_php_class != '') && class_exists($xmlrpc_val->_php_class)) {
			$obj = new $xmlrpc_val->_php_class();

			while (list($key, $value) = $xmlrpc_val->structeach()) {
				$obj->$key = php_xmlrpc_decode($value, $options);
			}

			return $obj;
		}
		else {
			$arr = array();

			while (list($key, $value) = $xmlrpc_val->structeach()) {
				$arr[$key] = php_xmlrpc_decode($value, $options);
			}

			return $arr;
		}
	case 'msg':
		$paramcount = $xmlrpc_val->getNumParams();
		$arr = array();

		for ($i = 0; $i < $paramcount; $i++) {
			$arr[] = php_xmlrpc_decode($xmlrpc_val->getParam($i));
		}

		return $arr;
	}
}

function php_xmlrpc_encode($php_val, $options = array())
{
	$type = gettype($php_val);

	switch ($type) {
	case 'string':
		if (in_array('auto_dates', $options) && preg_match('/^[0-9]{8}T[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $php_val)) {
			$xmlrpc_val = new xmlrpcval($php_val, $GLOBALS['xmlrpcDateTime']);
		}
		else {
			$xmlrpc_val = new xmlrpcval($php_val, $GLOBALS['xmlrpcString']);
		}

		break;

	case 'integer':
		$xmlrpc_val = new xmlrpcval($php_val, $GLOBALS['xmlrpcInt']);
		break;

	case 'double':
		$xmlrpc_val = new xmlrpcval($php_val, $GLOBALS['xmlrpcDouble']);
		break;

	case 'boolean':
		$xmlrpc_val = new xmlrpcval($php_val, $GLOBALS['xmlrpcBoolean']);
		break;

	case 'array':
		$j = 0;
		$arr = array();
		$ko = false;

		foreach ($php_val as $key => $val) {
			$arr[$key] = php_xmlrpc_encode($val, $options);
			if (!$ko && ($key !== $j)) {
				$ko = true;
			}

			$j++;
		}

		if ($ko) {
			$xmlrpc_val = new xmlrpcval($arr, $GLOBALS['xmlrpcStruct']);
		}
		else {
			$xmlrpc_val = new xmlrpcval($arr, $GLOBALS['xmlrpcArray']);
		}

		break;

	case 'object':
		if (is_a($php_val, 'xmlrpcval')) {
			$xmlrpc_val = $php_val;
		}
		else if (is_a($php_val, 'DateTime')) {
			$xmlrpc_val = new xmlrpcval($php_val->format('Ymd\\TH:i:s'), $GLOBALS['xmlrpcStruct']);
		}
		else {
			$arr = array();
			reset($php_val);

			while (list($k, $v) = each($php_val)) {
				$arr[$k] = php_xmlrpc_encode($v, $options);
			}

			$xmlrpc_val = new xmlrpcval($arr, $GLOBALS['xmlrpcStruct']);

			if (in_array('encode_php_objs', $options)) {
				$xmlrpc_val->_php_class = get_class($php_val);
			}
		}

		break;

	case 'NULL':
		if (in_array('extension_api', $options)) {
			$xmlrpc_val = new xmlrpcval('', $GLOBALS['xmlrpcString']);
		}
		else if (in_array('null_extension', $options)) {
			$xmlrpc_val = new xmlrpcval('', $GLOBALS['xmlrpcNull']);
		}
		else {
			$xmlrpc_val = new xmlrpcval();
		}

		break;

	case 'resource':
		if (in_array('extension_api', $options)) {
			$xmlrpc_val = new xmlrpcval((int) $php_val, $GLOBALS['xmlrpcInt']);
		}
		else {
			$xmlrpc_val = new xmlrpcval();
		}
	default:
		$xmlrpc_val = new xmlrpcval();
		break;
	}

	return $xmlrpc_val;
}

function php_xmlrpc_decode_xml($xml_val, $options = array())
{
	$GLOBALS['_xh'] = array();
	$GLOBALS['_xh']['ac'] = '';
	$GLOBALS['_xh']['stack'] = array();
	$GLOBALS['_xh']['valuestack'] = array();
	$GLOBALS['_xh']['params'] = array();
	$GLOBALS['_xh']['pt'] = array();
	$GLOBALS['_xh']['isf'] = 0;
	$GLOBALS['_xh']['isf_reason'] = '';
	$GLOBALS['_xh']['method'] = false;
	$GLOBALS['_xh']['rt'] = '';
	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);

	if (!in_array($GLOBALS['xmlrpc_internalencoding'], array('UTF-8', 'ISO-8859-1', 'US-ASCII'))) {
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
	}
	else {
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $GLOBALS['xmlrpc_internalencoding']);
	}

	xml_set_element_handler($parser, 'xmlrpc_se_any', 'xmlrpc_ee');
	xml_set_character_data_handler($parser, 'xmlrpc_cd');
	xml_set_default_handler($parser, 'xmlrpc_dh');

	if (!xml_parse($parser, $xml_val, 1)) {
		$errstr = sprintf('XML error: %s at line %d, column %d', xml_error_string(xml_get_error_code($parser)), xml_get_current_line_number($parser), xml_get_current_column_number($parser));
		error_log($errstr);
		xml_parser_free($parser);
		return false;
	}

	xml_parser_free($parser);

	if (1 < $GLOBALS['_xh']['isf']) {
		error_log($GLOBALS['_xh']['isf_reason']);
		return false;
	}

	switch ($GLOBALS['_xh']['rt']) {
	case 'methodresponse':
		$v = &$GLOBALS['_xh']['value'];

		if ($GLOBALS['_xh']['isf'] == 1) {
			$vc = $v->structmem('faultCode');
			$vs = $v->structmem('faultString');
			$r = new xmlrpcresp(0, $vc->scalarval(), $vs->scalarval());
		}
		else {
			$r = new xmlrpcresp($v);
		}

		return $r;
	case 'methodcall':
		$m = new xmlrpcmsg($GLOBALS['_xh']['method']);

		for ($i = 0; $i < count($GLOBALS['_xh']['params']); $i++) {
			$m->addParam($GLOBALS['_xh']['params'][$i]);
		}

		return $m;
	case 'value':
		return $GLOBALS['_xh']['value'];
	default:
		return false;
	}
}

function decode_chunked($buffer)
{
	$length = 0;
	$new = '';
	$chunkend = strpos($buffer, "\r\n") + 2;
	$temp = substr($buffer, 0, $chunkend);
	$chunk_size = hexdec(trim($temp));
	$chunkstart = $chunkend;

	while (0 < $chunk_size) {
		$chunkend = strpos($buffer, "\r\n", $chunkstart + $chunk_size);

		if ($chunkend == false) {
			$chunk = substr($buffer, $chunkstart);
			$new .= $chunk;
			$length += strlen($chunk);
			break;
		}

		$chunk = substr($buffer, $chunkstart, $chunkend - $chunkstart);
		$new .= $chunk;
		$length += strlen($chunk);
		$chunkstart = $chunkend + 2;
		$chunkend = strpos($buffer, "\r\n", $chunkstart) + 2;

		if ($chunkend == false) {
			break;
		}

		$temp = substr($buffer, $chunkstart, $chunkend - $chunkstart);
		$chunk_size = hexdec(trim($temp));
		$chunkstart = $chunkend;
	}

	return $new;
}

function guess_encoding($httpheader = '', $xmlchunk = '', $encoding_prefs = NULL)
{
	$matches = array();

	if (preg_match('/;\\s*charset\\s*=([^;]+)/i', $httpheader, $matches)) {
		return strtoupper(trim($matches[1], ' 	"'));
	}

	if (preg_match('/^(\\x00\\x00\\xFE\\xFF|\\xFF\\xFE\\x00\\x00|\\x00\\x00\\xFF\\xFE|\\xFE\\xFF\\x00\\x00)/', $xmlchunk)) {
		return 'UCS-4';
	}
	else if (preg_match('/^(\\xFE\\xFF|\\xFF\\xFE)/', $xmlchunk)) {
		return 'UTF-16';
	}
	else if (preg_match('/^(\\xEF\\xBB\\xBF)/', $xmlchunk)) {
		return 'UTF-8';
	}

	if (preg_match('/^<\\?xml\\s+version\\s*=\\s*' . '((?:"[a-zA-Z0-9_.:-]+")|(?:\'[a-zA-Z0-9_.:-]+\'))' . '\\s+encoding\\s*=\\s*' . '((?:"[A-Za-z][A-Za-z0-9._-]*")|(?:\'[A-Za-z][A-Za-z0-9._-]*\'))/', $xmlchunk, $matches)) {
		return strtoupper(substr($matches[2], 1, -1));
	}

	if (extension_loaded('mbstring')) {
		if ($encoding_prefs) {
			$enc = mb_detect_encoding($xmlchunk, $encoding_prefs);
		}
		else {
			$enc = mb_detect_encoding($xmlchunk);
		}

		if ($enc == 'ASCII') {
			$enc = 'US-' . $enc;
		}

		return $enc;
	}
	else {
		return $GLOBALS['xmlrpc_defencoding'];
	}
}

function is_valid_charset($encoding, $validlist)
{
	$charset_supersets = array(
		'US-ASCII' => array('ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5', 'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10', 'ISO-8859-11', 'ISO-8859-12', 'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'UTF-8', 'EUC-JP', 'EUC-', 'EUC-KR', 'EUC-CN')
		);

	if (is_string($validlist)) {
		$validlist = explode(',', $validlist);
	}

	if (@in_array(strtoupper($encoding), $validlist)) {
		return true;
	}
	else {
		if (array_key_exists($encoding, $charset_supersets)) {
			foreach ($validlist as $allowed) {
				if (in_array($allowed, $charset_supersets[$encoding])) {
					return true;
				}
			}
		}

		return false;
	}
}

if (!function_exists('xml_parser_create')) {
	if (strtoupper(substr(PHP_OS, 0, 3) != 'WIN')) {
		dl('xml.so');
	}
}

$GLOBALS['xmlrpcI4'] = 'i4';
$GLOBALS['xmlrpcInt'] = 'int';
$GLOBALS['xmlrpcBoolean'] = 'boolean';
$GLOBALS['xmlrpcDouble'] = 'double';
$GLOBALS['xmlrpcString'] = 'string';
$GLOBALS['xmlrpcDateTime'] = 'dateTime.iso8601';
$GLOBALS['xmlrpcBase64'] = 'base64';
$GLOBALS['xmlrpcArray'] = 'array';
$GLOBALS['xmlrpcStruct'] = 'struct';
$GLOBALS['xmlrpcValue'] = 'undefined';
$GLOBALS['xmlrpcTypes'] = array($GLOBALS['xmlrpcI4'] => 1, $GLOBALS['xmlrpcInt'] => 1, $GLOBALS['xmlrpcBoolean'] => 1, $GLOBALS['xmlrpcString'] => 1, $GLOBALS['xmlrpcDouble'] => 1, $GLOBALS['xmlrpcDateTime'] => 1, $GLOBALS['xmlrpcBase64'] => 1, $GLOBALS['xmlrpcArray'] => 2, $GLOBALS['xmlrpcStruct'] => 3);
$GLOBALS['xmlrpc_valid_parents'] = array(
	'VALUE'            => array('MEMBER', 'DATA', 'PARAM', 'FAULT'),
	'BOOLEAN'          => array('VALUE'),
	'I4'               => array('VALUE'),
	'INT'              => array('VALUE'),
	'STRING'           => array('VALUE'),
	'DOUBLE'           => array('VALUE'),
	'DATETIME.ISO8601' => array('VALUE'),
	'BASE64'           => array('VALUE'),
	'MEMBER'           => array('STRUCT'),
	'NAME'             => array('MEMBER'),
	'DATA'             => array('ARRAY'),
	'ARRAY'            => array('VALUE'),
	'STRUCT'           => array('VALUE'),
	'PARAM'            => array('PARAMS'),
	'METHODNAME'       => array('METHODCALL'),
	'PARAMS'           => array('METHODCALL', 'METHODRESPONSE'),
	'FAULT'            => array('METHODRESPONSE'),
	'NIL'              => array('VALUE'),
	'EX:NIL'           => array('VALUE')
	);
$GLOBALS['xmlrpcNull'] = 'null';
$GLOBALS['xmlrpcTypes']['null'] = 1;
$GLOBALS['xmlEntities'] = array('amp' => '&', 'quot' => '"', 'lt' => '<', 'gt' => '>', 'apos' => '\'');
$GLOBALS['xml_iso88591_Entities'] = array();
$GLOBALS['xml_iso88591_Entities']['in'] = array();
$GLOBALS['xml_iso88591_Entities']['out'] = array();

for ($i = 0; $i < 32; $i++) {
	$GLOBALS['xml_iso88591_Entities']['in'][] = chr($i);
	$GLOBALS['xml_iso88591_Entities']['out'][] = '&#' . $i . ';';
}

for ($i = 160; $i < 256; $i++) {
	$GLOBALS['xml_iso88591_Entities']['in'][] = chr($i);
	$GLOBALS['xml_iso88591_Entities']['out'][] = '&#' . $i . ';';
}

$GLOBALS['xmlrpcerr'] = array('unknown_method' => 1, 'invalid_return' => 2, 'incorrect_params' => 3, 'introspect_unknown' => 4, 'http_error' => 5, 'no_data' => 6, 'no_ssl' => 7, 'curl_fail' => 8, 'invalid_request' => 15, 'no_curl' => 16, 'server_error' => 17, 'multicall_error' => 18, 'multicall_notstruct' => 9, 'multicall_nomethod' => 10, 'multicall_notstring' => 11, 'multicall_recursion' => 12, 'multicall_noparams' => 13, 'multicall_notarray' => 14, 'cannot_decompress' => 103, 'decompress_fail' => 104, 'dechunk_fail' => 105, 'server_cannot_decompress' => 106, 'server_decompress_fail' => 107);
$GLOBALS['xmlrpcstr'] = array('unknown_method' => 'Unknown method', 'invalid_return' => 'Invalid return payload: enable debugging to examine incoming payload', 'incorrect_params' => 'Incorrect parameters passed to method', 'introspect_unknown' => 'Can\'t introspect: method unknown', 'http_error' => 'Didn\'t receive 200 OK from remote server.', 'no_data' => 'No data received from server.', 'no_ssl' => 'No SSL support compiled in.', 'curl_fail' => 'CURL error', 'invalid_request' => 'Invalid request payload', 'no_curl' => 'No CURL support compiled in.', 'server_error' => 'Internal server error', 'multicall_error' => 'Received from server invalid multicall response', 'multicall_notstruct' => 'system.multicall expected struct', 'multicall_nomethod' => 'missing methodName', 'multicall_notstring' => 'methodName is not a string', 'multicall_recursion' => 'recursive system.multicall forbidden', 'multicall_noparams' => 'missing params', 'multicall_notarray' => 'params is not an array', 'cannot_decompress' => 'Received from server compressed HTTP and cannot decompress', 'decompress_fail' => 'Received from server invalid compressed HTTP', 'dechunk_fail' => 'Received from server invalid chunked HTTP', 'server_cannot_decompress' => 'Received from client compressed HTTP request and cannot decompress', 'server_decompress_fail' => 'Received from client invalid compressed HTTP request');
$GLOBALS['xmlrpc_defencoding'] = 'UTF-8';
$GLOBALS['xmlrpc_internalencoding'] = 'ISO-8859-1';
$GLOBALS['xmlrpcName'] = 'XML-RPC for PHP';
$GLOBALS['xmlrpcVersion'] = '3.0.0.beta';
$GLOBALS['xmlrpcerruser'] = 800;
$GLOBALS['xmlrpcerrxml'] = 100;
$GLOBALS['xmlrpc_backslash'] = chr(92) . chr(92);
$GLOBALS['xmlrpc_null_extension'] = false;
$GLOBALS['xmlrpc_null_apache_encoding'] = false;
$GLOBALS['_xh'] = NULL;

if (function_exists('xmlrpc_decode')) {
	define('XMLRPC_EPI_ENABLED', '1');
}
else {
	define('XMLRPC_EPI_ENABLED', '0');
}

?>
