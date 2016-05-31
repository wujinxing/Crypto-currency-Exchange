<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
function json_encode_entities($data, $src_encoding = '', $dest_encoding = '')
{
	if ($src_encoding == '') {
		$src_encoding = $GLOBALS['xmlrpc_internalencoding'];
	}

	switch (strtoupper($src_encoding . '_' . $dest_encoding)) {
	case 'ISO-8859-1_':
	case 'ISO-8859-1_US-ASCII':
		$escaped_data = str_replace(array('\\', '"', '/', '	', "\n", "\r", chr(8), chr(11), chr(12)), array('\\\\', '\\"', '\\/', '\\t', '\\n', '\\r', '\\b', '\\v', '\\f'), $data);
		$escaped_data = str_replace($GLOBALS['ecma262_iso88591_Entities']['in'], $GLOBALS['ecma262_iso88591_Entities']['out'], $escaped_data);
		break;

	case 'ISO-8859-1_UTF-8':
		$escaped_data = str_replace(array('\\', '"', '/', '	', "\n", "\r", chr(8), chr(11), chr(12)), array('\\\\', '\\"', '\\/', '\\t', '\\n', '\\r', '\\b', '\\v', '\\f'), $data);
		$escaped_data = utf8_encode($escaped_data);
		break;

	case 'ISO-8859-1_ISO-8859-1':
	case 'US-ASCII_US-ASCII':
	case 'US-ASCII_UTF-8':
	case 'US-ASCII_':
	case 'US-ASCII_ISO-8859-1':
	case 'UTF-8_UTF-8':
		$escaped_data = str_replace(array('\\', '"', '/', '	', "\n", "\r", chr(8), chr(11), chr(12)), array('\\\\', '\\"', '\\/', '\\t', '\\n', '\\r', '\\b', '\\v', '\\f'), $data);
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
				case 8:
					$escaped_data .= '\\b';
					break;

				case 9:
					$escaped_data .= '\\t';
					break;

				case 10:
					$escaped_data .= '\\n';
					break;

				case 11:
					$escaped_data .= '\\v';
					break;

				case 12:
					$escaped_data .= '\\f';
					break;

				case 13:
					$escaped_data .= '\\r';
					break;

				case 34:
					$escaped_data .= '\\"';
					break;

				case 47:
					$escaped_data .= '\\/';
					break;

				case 92:
					$escaped_data .= '\\\\';
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
				$ent = sprintf('\\u%\'04x', $ii);
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
				$ent = sprintf('\\u%\'04x', $ii);
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
				$ent = sprintf('\\u%\'04x', $ii);
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

function json_parse($data, $return_phpvals = false, $src_encoding = 'UTF-8', $dest_encoding = 'ISO-8859-1')
{
	$data = preg_replace(array('#^\\s*/\\*(.*)\\*/#Us', '#/\\*(.*)\\*/\\s*$#Us'), '', $data);
	$data = trim($data);

	if ($data == '') {
		$GLOBALS['_xh']['isf_reason'] = 'Invalid data (empty string?)';
		return false;
	}

	switch ($data[0]) {
	case '"':
	case '\'':
		$len = strlen($data);
		if (($data[$len - 1] == $data[0]) && (1 < $len)) {
			if (($src_encoding == 'UTF-8') && (($dest_encoding == 'ISO-8859-1') || ($dest_encoding == 'US-ASCII'))) {
				$data = utf8_decode($data);
				$len = strlen($data);
			}
			else {
				if (($dest_encoding == 'UTF-8') && (($src_encoding == 'ISO-8859-1') || ($src_encoding == 'US-ASCII'))) {
					$data = utf8_encode($data);
					$len = strlen($data);
				}
			}

			$outdata = '';
			$delim = $data[0];

			for ($i = 1; $i < ($len - 1); $i++) {
				switch ($data[$i]) {
				case '\\':
					if ($i == ($len - 2)) {
						break;
					}

					switch ($data[$i + 1]) {
					case 'b':
					case 'f':
					case 'n':
					case 'r':
					case 't':
					case 'v':
						$outdata .= $GLOBALS['ecma262_entities'][$data[$i + 1]];
						$i++;
						break;

					case 'u':
						if ($dest_encoding == 'UTF-8') {
							$ii = hexdec(substr($data, $i + 2, 4));

							if ($ii < 128) {
								$outdata .= chr($ii);
							}
							else if ($ii <= 2048) {
								$outdata .= chr(192 | ($ii >> 6)) . chr(128 | ($ii & 63));
							}
							else if ($ii <= 65536) {
								$outdata .= chr(224 | ($ii >> 12)) . chr(128 | (($ii >> 6) & 63)) . chr(128 | ($ii & 63));
							}
							else {
								$outdata .= chr(240 | ($ii >> 20)) . chr(128 | (($ii >> 12) & 63)) . chr(128 | (($ii >> 6) & 63)) . chr(128 | ($ii & 63));
							}

							$i += 5;
						}
						else {
							$outdata .= chr(hexdec(substr($data, $i + 4, 2)));
							$i += 5;
						}

						break;

					case 'x':
						if ($dest_encoding == 'UTF-8') {
							$ii = hexdec(substr($data, $i + 2, 2));

							if ($ii < 128) {
								$outdata .= chr($ii);
							}
							else if ($ii <= 2048) {
								$outdata .= chr(192 | ($ii >> 6)) . chr(128 | ($ii & 63));
							}
							else if ($ii <= 65536) {
								$outdata .= chr(224 | ($ii >> 12)) . chr(128 | (($ii >> 6) & 63)) . chr(128 | ($ii & 63));
							}
							else {
								$outdata .= chr(240 | ($ii >> 20)) . chr(128 | (($ii >> 12) & 63)) . chr(128 | (($ii >> 6) & 63)) . chr(128 | ($ii & 63));
							}

							$i += 3;
						}
						else {
							$outdata .= chr(hexdec(substr($data, $i + 2, 2)));
							$i += 3;
						}

						break;

					case '0':
					case '1':
					case '2':
					case '3':
					case '4':
					case '5':
					case '6':
					case '7':
					case '8':
					case '9':
						break;

					default:
						$outdata .= $data[$i + 1];
						$i++;
					}

					break;

				case $delim:
					$GLOBALS['_xh']['isf_reason'] = 'Invalid data (unescaped quote char inside string?)';
					return false;
				case "\n":
				case "\r":
					$GLOBALS['_xh']['isf_reason'] = 'Invalid data (line terminator char inside string?)';
					return false;
				default:
					$outdata .= $data[$i];
				}
			}

			$GLOBALS['_xh']['vt'] = 'string';
			$GLOBALS['_xh']['value'] = $outdata;
		}
		else {
			$GLOBALS['_xh']['isf_reason'] = 'Invalid data (string missing closing quote?)';
			return false;
		}

		break;

	case '[':
	case '{':
		$len = strlen($data);

		if ($data[0] == '[') {
			if ($data[$len - 1] != ']') {
				$GLOBALS['_xh']['isf_reason'] = 'Invalid data (array missing closing bracket?)';
				return false;
			}

			$GLOBALS['_xh']['vt'] = 'array';
		}
		else {
			if ($data[$len - 1] != '}') {
				$GLOBALS['_xh']['isf_reason'] = 'Invalid data (object missing closing bracket?)';
				return false;
			}

			$GLOBALS['_xh']['vt'] = 'struct';
		}

		$data = trim(substr($data, 1, -1));

		if ($data == '') {
			$GLOBALS['_xh']['value'] = array();
		}
		else {
			$valuestack = array();
			$last = array('type' => 'sl', 'start' => 0);
			$len = strlen($data);
			$value = array();
			$keypos = NULL;
			$vt = '';

			for ($i = 0; $i <= $len; $i++) {
				if (($i == $len) || (($data[$i] == ',') && ($last['type'] == 'sl'))) {
					$slice = substr($data, $last['start'], $i - $last['start']);

					if ($GLOBALS['_xh']['vt'] == 'array') {
						if ($slice == '') {
							if ($return_phpvals) {
								$value[] = NULL;
							}
							else {
								$value[] = new jsonrpcval(NULL, 'null');
							}
						}
						else if (!json_parse($slice, $return_phpvals, $src_encoding, $dest_encoding)) {
							return false;
						}
						else {
							$value[] = $GLOBALS['_xh']['value'];
							$GLOBALS['_xh']['vt'] = 'array';
						}
					}
					else if (!$keypos) {
						$GLOBALS['_xh']['isf_reason'] = 'Invalid data (missing object member name?)';
						return false;
					}
					else {
						if (!json_parse(substr($data, $last['start'], $keypos - $last['start']), true, $src_encoding, $dest_encoding) || ($GLOBALS['_xh']['vt'] != 'string')) {
							$key = trim(substr($data, $last['start'], $keypos - $last['start']));
						}
						else {
							$key = $GLOBALS['_xh']['value'];
						}

						if (!json_parse(substr($data, $keypos + 1, $i - $keypos - 1), $return_phpvals, $src_encoding, $dest_encoding)) {
							return false;
						}

						$value[$key] = $GLOBALS['_xh']['value'];
						$GLOBALS['_xh']['vt'] = 'struct';
						$keypos = NULL;
					}

					$last['start'] = $i + 1;
					$vt = '';
				}
				else {
					if (($data[$i] == '"') || ($data[$i] == '\'')) {
						$ok = false;

						for ($j = $i + 1; $j < $len; $j++) {
							if ($data[$j] == $data[$i]) {
								$ok = true;
								break;
							}
							else if ($data[$j] == '\\') {
								$j++;
							}
						}

						if ($ok) {
							$i = $j;
							$vt = 'st';
						}
						else {
							$GLOBALS['_xh']['isf_reason'] = 'Invalid data (string missing closing quote?)';
							return false;
						}
					}
					else if ($data[$i] == '[') {
						$valuestack[] = $last;
						$last = array('type' => 'ar', 'start' => $i);
					}
					else if ($data[$i] == '{') {
						$valuestack[] = $last;
						$last = array('type' => 'ob', 'start' => $i);
					}
					else if ($data[$i] == ']') {
						if ($last['type'] == 'ar') {
							$last = array_pop($valuestack);
							$vt = 'ar';
						}
						else {
							$GLOBALS['_xh']['isf_reason'] = 'Invalid data (unmatched array closing bracket?)';
							return false;
						}
					}
					else if ($data[$i] == '}') {
						if ($last['type'] == 'ob') {
							$last = array_pop($valuestack);
							$vt = 'ob';
						}
						else {
							$GLOBALS['_xh']['isf_reason'] = 'Invalid data (unmatched object closing bracket?)';
							return false;
						}
					}
					else {
						if (($data[$i] == ':') && ($last['type'] == 'sl') && !$keypos) {
							$keypos = $i;
						}
						else {
							if (($data[$i] == '/') && ($i < ($len - 1)) && ($data[$i + 1] == '*')) {
								$ok = false;

								for ($j = $i + 2; $j < ($len - 1); $j++) {
									if (($data[$j] == '*') && ($data[$j + 1] == '/')) {
										$ok = true;
										break;
									}
								}

								if ($ok) {
									$i = $j + 1;
								}
								else {
									$GLOBALS['_xh']['isf_reason'] = 'Invalid data (comment missing closing tag?)';
									return false;
								}
							}
						}
					}
				}
			}

			$GLOBALS['_xh']['value'] = $value;
		}

		break;

	default:
		$data = strtolower($data);

		if ($data == 'true') {
			$GLOBALS['_xh']['value'] = true;
			$GLOBALS['_xh']['vt'] = 'boolean';
		}
		else if ($data == 'false') {
			$GLOBALS['_xh']['value'] = false;
			$GLOBALS['_xh']['vt'] = 'boolean';
		}
		else if ($data == 'null') {
			$GLOBALS['_xh']['value'] = NULL;
			$GLOBALS['_xh']['vt'] = 'null';
		}
		else if (preg_match('#^-?(0|[1-9][0-9]*)(\\.[0-9]*)?([e][+-]?[0-9]+)?$#', $data)) {
			if (preg_match('#[.e]#', $data)) {
				$GLOBALS['_xh']['value'] = (double) $data;
				$GLOBALS['_xh']['vt'] = 'double';
			}
			else {
				$GLOBALS['_xh']['value'] = (int) $data;
				$GLOBALS['_xh']['vt'] = 'int';
			}
		}
		else if (preg_match('#^0x[0-9a-f]+$#', $data)) {
			$GLOBALS['_xh']['vt'] = 'int';
			$GLOBALS['_xh']['value'] = hexdec(substr($data, 2));
		}
		else {
			$GLOBALS['_xh']['isf_reason'] = 'Invalid data';
			return false;
		}
	}

	if (!$return_phpvals) {
		$GLOBALS['_xh']['value'] = new jsonrpcval($GLOBALS['_xh']['value'], $GLOBALS['_xh']['vt']);
	}

	return true;
}

function json_parse_native($data)
{
	$out = json_decode($data, true);

	if (!is_array($out)) {
		$GLOBALS['_xh']['isf_reason'] = 'JSON parsing failed';
		return false;
	}
	else {
		$GLOBALS['_xh']['value'] = $out;
		return true;
	}
}

function jsonrpc_parse_req($data, $return_phpvals = false, $use_extension = false, $src_encoding = '')
{
	$GLOBALS['_xh']['isf'] = 0;
	$GLOBALS['_xh']['isf_reason'] = '';
	$GLOBALS['_xh']['pt'] = array();
	if ($return_phpvals && $use_extension) {
		$ok = json_parse_native($data);
	}
	else {
		$ok = json_parse($data, $return_phpvals, $src_encoding);
	}

	if ($ok) {
		if (!$return_phpvals) {
			$GLOBALS['_xh']['value'] = @$GLOBALS['_xh']['value']->me['struct'];
		}

		if (!is_array($GLOBALS['_xh']['value']) || !array_key_exists('method', $GLOBALS['_xh']['value']) || !array_key_exists('params', $GLOBALS['_xh']['value']) || !array_key_exists('id', $GLOBALS['_xh']['value'])) {
			$GLOBALS['_xh']['isf_reason'] = 'JSON parsing did not return correct jsonrpc request object';
			return false;
		}
		else {
			$GLOBALS['_xh']['method'] = $GLOBALS['_xh']['value']['method'];
			$GLOBALS['_xh']['params'] = $GLOBALS['_xh']['value']['params'];
			$GLOBALS['_xh']['id'] = $GLOBALS['_xh']['value']['id'];

			if (!$return_phpvals) {
				$GLOBALS['_xh']['method'] = $GLOBALS['_xh']['method']->scalarval();
				$GLOBALS['_xh']['params'] = $GLOBALS['_xh']['params']->me['array'];
				$GLOBALS['_xh']['id'] = php_jsonrpc_decode($GLOBALS['_xh']['id']);
			}
			else {
				foreach ($GLOBALS['_xh']['params'] as $val) {
					$typ = gettype($val);
					if (($typ == 'array') && count($val) && (count(array_diff_key($val, array_fill(0, count($val), NULL))) !== 0)) {
						$typ = 'object';
					}

					$GLOBALS['_xh']['pt'][] = php_2_jsonrpc_type($typ);
				}
			}

			return true;
		}
	}
	else {
		return false;
	}
}

function jsonrpc_parse_resp($data, $return_phpvals = false, $use_extension = false, $src_encoding = '')
{
	$GLOBALS['_xh']['isf'] = 0;
	$GLOBALS['_xh']['isf_reason'] = '';
	if ($return_phpvals && $use_extension) {
		$ok = json_parse_native($data);
	}
	else {
		$ok = json_parse($data, $return_phpvals, $src_encoding);
	}

	if ($ok) {
		if (!$return_phpvals) {
			$GLOBALS['_xh']['value'] = @$GLOBALS['_xh']['value']->me['struct'];
		}

		if (!is_array($GLOBALS['_xh']['value']) || !array_key_exists('result', $GLOBALS['_xh']['value']) || !array_key_exists('error', $GLOBALS['_xh']['value']) || !array_key_exists('id', $GLOBALS['_xh']['value'])) {
			$GLOBALS['_xh']['isf_reason'] = 'JSON parsing did not return correct jsonrpc response object';
			return false;
		}

		if (!$return_phpvals) {
			$d_error = php_jsonrpc_decode($GLOBALS['_xh']['value']['error']);
			$GLOBALS['_xh']['value']['id'] = php_jsonrpc_decode($GLOBALS['_xh']['value']['id']);
		}
		else {
			$d_error = $GLOBALS['_xh']['value']['error'];
		}

		$GLOBALS['_xh']['id'] = $GLOBALS['_xh']['value']['id'];

		if ($d_error != NULL) {
			$GLOBALS['_xh']['isf'] = 1;
			if (is_array($d_error) && array_key_exists('faultCode', $d_error) && array_key_exists('faultString', $d_error)) {
				if ($d_error['faultCode'] == 0) {
					$d_error['faultCode'] = -1;
				}

				$GLOBALS['_xh']['value'] = $d_error;
			}
			else if ($return_phpvals) {
				$GLOBALS['_xh']['value'] = array('faultCode' => -1, 'faultString' => var_export($GLOBALS['_xh']['value']['error'], true));
			}
			else {
				$GLOBALS['_xh']['value'] = array('faultCode' => -1, 'faultString' => serialize_jsonrpcval($GLOBALS['_xh']['value']['error']));
			}
		}
		else {
			$GLOBALS['_xh']['value'] = $GLOBALS['_xh']['value']['result'];
		}

		return true;
	}
	else {
		return false;
	}
}

function php_jsonrpc_decode($jsonrpc_val, $options = array())
{
	$kind = $jsonrpc_val->kindOf();

	if ($kind == 'scalar') {
		return $jsonrpc_val->scalarval();
	}
	else if ($kind == 'array') {
		$size = $jsonrpc_val->arraysize();
		$arr = array();

		for ($i = 0; $i < $size; $i++) {
			$arr[] = php_jsonrpc_decode($jsonrpc_val->arraymem($i), $options);
		}

		return $arr;
	}
	else if ($kind == 'struct') {
		$jsonrpc_val->structreset();

		if (in_array('decode_php_objs', $options)) {
			if (($jsonrpc_val->_php_class != '') && class_exists($jsonrpc_val->_php_class)) {
				$obj = new $jsonrpc_val->_php_class();
			}
			else {
				$obj = new stdClass();
			}

			while (list($key, $value) = $jsonrpc_val->structeach()) {
				$obj->$key = php_jsonrpc_decode($value, $options);
			}

			return $obj;
		}
		else {
			$arr = array();

			while (list($key, $value) = $jsonrpc_val->structeach()) {
				$arr[$key] = php_jsonrpc_decode($value, $options);
			}

			return $arr;
		}
	}
}

function php_jsonrpc_encode($php_val, $options = '')
{
	$type = gettype($php_val);

	switch ($type) {
	case 'string':
		$jsonrpc_val = new jsonrpcval($php_val, $GLOBALS['xmlrpcString']);
		break;

	case 'integer':
		$jsonrpc_val = new jsonrpcval($php_val, $GLOBALS['xmlrpcInt']);
		break;

	case 'double':
		$jsonrpc_val = new jsonrpcval($php_val, $GLOBALS['xmlrpcDouble']);
		break;

	case 'boolean':
		$jsonrpc_val = new jsonrpcval($php_val, $GLOBALS['xmlrpcBoolean']);
		break;

	case 'resource':
	case 'NULL':
		$jsonrpc_val = new jsonrpcval($php_val, $GLOBALS['xmlrpcNull']);
		break;

	case 'array':
		$j = 0;
		$arr = array();
		$ko = false;

		foreach ($php_val as $key => $val) {
			$arr[$key] = php_jsonrpc_encode($val, $options);
			if (!$ko && ($key !== $j)) {
				$ko = true;
			}

			$j++;
		}

		if ($ko) {
			$jsonrpc_val = new jsonrpcval($arr, $GLOBALS['xmlrpcStruct']);
		}
		else {
			$jsonrpc_val = new jsonrpcval($arr, $GLOBALS['xmlrpcArray']);
		}

		break;

	case 'object':
		if (is_a($php_val, 'jsonrpcval')) {
			$jsonrpc_val = $php_val;
		}
		else {
			$arr = array();
			reset($php_val);

			while (list($k, $v) = each($php_val)) {
				$arr[$k] = php_jsonrpc_encode($v, $options);
			}

			$jsonrpc_val = new jsonrpcval($arr, $GLOBALS['xmlrpcStruct']);

			if (in_array('encode_php_objs', $options)) {
				$jsonrpc_val->_php_class = get_class($php_val);
			}
		}

		break;

	default:
		$jsonrpc_val = new jsonrpcval();
		break;
	}

	return $jsonrpc_val;
}

function php_jsonrpc_decode_json($json_val, $options = array())
{
	$src_encoding = (array_key_exists('src_encoding', $options) ? $options['src_encoding'] : $GLOBALS['xmlrpc_defencoding']);
	$dest_encoding = (array_key_exists('dest_encoding', $options) ? $options['dest_encoding'] : $GLOBALS['xmlrpc_internalencoding']);
	$GLOBALS['_xh']['isf'] = 0;

	if (!json_parse($json_val, false, $src_encoding, $dest_encoding)) {
		error_log($GLOBALS['_xh']['isf_reason']);
		return false;
	}
	else {
		$val = $GLOBALS['_xh']['value'];

		if ($GLOBALS['_xh']['value']->kindOf() == 'struct') {
			if ($GLOBALS['_xh']['value']->structSize() == 3) {
				if ($GLOBALS['_xh']['value']->structMemExists('method') && $GLOBALS['_xh']['value']->structMemExists('params') && $GLOBALS['_xh']['value']->structMemExists('id')) {
					$method = $GLOBALS['_xh']['value']->structMem('method');
					$msg = new jsonrpcmsg($method->scalarval(), NULL, php_jsonrpc_decode($GLOBALS['_xh']['value']->structMem('id')));
					$params = $GLOBALS['_xh']['value']->structMem('params');

					for ($i = 0; $i < $params->arraySize(); ++$i) {
						$msg->addparam($params->arrayMem($i));
					}

					return $msg;
				}
				else {
					if ($GLOBALS['_xh']['value']->structMemExists('result') && $GLOBALS['_xh']['value']->structMemExists('error') && $GLOBALS['_xh']['value']->structMemExists('id')) {
						$id = php_jsonrpc_decode($GLOBALS['_xh']['value']->structMem('id'));
						$err = php_jsonrpc_decode($GLOBALS['_xh']['value']->structMem('error'));

						if ($err == NULL) {
							$resp = new jsonrpcresp($GLOBALS['_xh']['value']->structMem('result'));
						}
						else {
							if (is_array($err) && array_key_exists('faultCode', $err) && array_key_exists('faultString', $err)) {
								if ($err['faultCode'] == 0) {
									$err['faultCode'] = -1;
								}
							}
							else {
								$err = array('faultCode' => -1, 'faultString' => serialize_jsonrpcval($GLOBALS['_xh']['value']->structMem('error')));
							}

							$resp = new jsonrpcresp(0, $err['faultCode'], $err['faultString']);
						}

						$resp->id = $id;
						return $resp;
					}
				}
			}
		}

		return $GLOBALS['_xh']['value'];
	}
}

function serialize_jsonrpcresp($resp, $id = NULL, $charset_encoding = '')
{
	$result = "{\n\"id\": ";

	switch (true) {
	case $id === NULL:
		$result .= 'null';
		break;

	case is_string($id):
		$result .= '"' . json_encode_entities($id, '', $charset_encoding) . '"';
		break;

	case is_bool($id):
		$result .= ($id ? 'true' : 'false');
		break;

	default:
		$result .= $id;
	}

	$result .= ', ';

	if ($resp->errno) {
		$result .= '"error": { "faultCode": ' . $resp->errno . ', "faultString": "' . json_encode_entities($resp->errstr, NULL, $charset_encoding) . '" }, "result": null';
	}
	else {
		if (!is_object($resp->val) || !is_a($resp->val, 'xmlrpcval')) {
			if (is_string($resp->val) && ($resp->valtyp == 'json')) {
				$result .= '"error": null, "result": ' . $resp->val;
			}
			else {
				exit('cannot serialize jsonrpcresp objects whose content is native php values');
			}
		}
		else {
			$result .= '"error": null, "result": ' . serialize_jsonrpcval($resp->val, $charset_encoding);
		}
	}

	$result .= "\n}";
	return $result;
}

function serialize_jsonrpcval($value, $charset_encoding = '')
{
	reset($value->me);
	list($typ, $val) = each($value->me);
	$rs = '';

	switch (@$GLOBALS['xmlrpcTypes'][$typ]) {
	case 1:
		switch ($typ) {
		case $GLOBALS['xmlrpcString']:
			$rs .= '"' . json_encode_entities($val, NULL, $charset_encoding) . '"';
			break;

		case $GLOBALS['xmlrpcI4']:
		case $GLOBALS['xmlrpcInt']:
			$rs .= (int) $val;
			break;

		case $GLOBALS['xmlrpcDateTime']:
			$rs .= '"' . $val . '"';
			break;

		case $GLOBALS['xmlrpcDouble']:
			$sval = strval((double) $val);
			$sval = str_replace(',', '.', $sval);
			if ((strpos($sval, '.') !== false) || (strpos($sval, 'e') !== false)) {
				$rs .= $sval;
			}
			else {
				$rs .= $val . '.0';
			}

			break;

		case $GLOBALS['xmlrpcBoolean']:
			$rs .= ($val ? 'true' : 'false');
			break;

		case $GLOBALS['xmlrpcBase64']:
			$rs .= '"' . base64_encode($val) . '"';
			break;

		default:
			$rs .= 'null';
		}

		break;

	case 2:
		if (array_keys($val) !== range(0, count($val) - 1)) {
			foreach ($val as $key2 => $val2) {
				$rs .= ',"' . json_encode_entities($key2, NULL, $charset_encoding) . '":';
				$rs .= serialize_jsonrpcval($val2, $charset_encoding);
			}

			$rs = '{' . substr($rs, 1) . '}';
			break;
		}

		$rs .= '[';
		$len = sizeof($val);

		if ($len) {
			for ($i = 0; $i < $len; $i++) {
				$rs .= serialize_jsonrpcval($val[$i], $charset_encoding);
				$rs .= ',';
			}

			$rs = substr($rs, 0, -1) . ']';
		}
		else {
			$rs .= ']';
		}

		break;

	case 3:
		foreach ($val as $key2 => $val2) {
			$rs .= ',"' . json_encode_entities($key2, NULL, $charset_encoding) . '":';
			$rs .= serialize_jsonrpcval($val2, $charset_encoding);
		}

		$rs = '{' . substr($rs, 1) . '}';
		break;

	case 0:
		$rs = '""';
		break;

	default:
		break;
	}

	return $rs;
}

function php_2_jsonrpc_type($phptype)
{
	switch (strtolower($phptype)) {
	case 'string':
		return $GLOBALS['xmlrpcString'];
	case 'integer':
	case $GLOBALS['xmlrpcInt']:
	case $GLOBALS['xmlrpcI4']:
		return $GLOBALS['xmlrpcInt'];
	case 'double':
		return $GLOBALS['xmlrpcDouble'];
	case 'boolean':
		return $GLOBALS['xmlrpcBoolean'];
	case 'array':
		return $GLOBALS['xmlrpcArray'];
	case 'object':
		return $GLOBALS['xmlrpcStruct'];
	case $GLOBALS['xmlrpcStruct']:
		return strtolower($phptype);
	case 'resource':
		return '';
	default:
		if (class_exists($phptype)) {
			return $GLOBALS['xmlrpcStruct'];
		}
		else {
			return $GLOBALS['xmlrpcValue'];
		}
	}
}

$GLOBALS['ecma262_entities'] = array('b' => chr(8), 'f' => chr(12), 'n' => chr(10), 'r' => chr(13), 't' => chr(9), 'v' => chr(11));
$GLOBALS['ecma262_iso88591_Entities'] = array();
$GLOBALS['ecma262_iso88591_Entities']['in'] = array();
$GLOBALS['ecma262_iso88591_Entities']['out'] = array();

for ($i = 0; $i < 32; $i++) {
	$GLOBALS['ecma262_iso88591_Entities']['in'][] = chr($i);
	$GLOBALS['ecma262_iso88591_Entities']['out'][] = sprintf('\\u%\'04x', $i);
}

for ($i = 160; $i < 256; $i++) {
	$GLOBALS['ecma262_iso88591_Entities']['in'][] = chr($i);
	$GLOBALS['ecma262_iso88591_Entities']['out'][] = sprintf('\\u%\'04x', $i);
}

class jsonrpc_client extends xmlrpc_client
{
	public $no_multicall = true;
	public $return_type = 'jsonrpcvals';
}
class jsonrpcmsg extends xmlrpcmsg
{
	public $id;
	public $content_type = 'application/json';

	public function jsonrpcmsg($meth, $pars = 0, $id = NULL)
	{
		$this->id = $id;
		$this->xmlrpcmsg($meth, $pars);
	}

	public function createPayload($charset_encoding = '')
	{
		if ($charset_encoding != '') {
			$this->content_type = 'application/json; charset=' . $charset_encoding;
		}
		else {
			$this->content_type = 'application/json';
		}

		$this->payload = "{\n\"method\": \"" . json_encode_entities($this->methodname, '', $charset_encoding) . "\",\n\"params\": [ ";

		for ($i = 0; $i < sizeof($this->params); $i++) {
			$p = $this->params[$i];
			$this->payload .= "\n  " . serialize_jsonrpcval($p, $charset_encoding) . ',';
		}

		$this->payload = substr($this->payload, 0, -1) . "\n],\n\"id\": ";

		switch (true) {
		case $this->id === NULL:
			$this->payload .= 'null';
			break;

		case is_string($this->id):
			$this->payload .= '"' . json_encode_entities($this->id, '', $charset_encoding) . '"';
			break;

		case is_bool($this->id):
			$this->payload .= ($this->id ? 'true' : 'false');
			break;

		default:
			$this->payload .= $this->id;
		}

		$this->payload .= "\n}\n";
	}

	public function& parseResponse($data = '', $headers_processed = false, $return_type = 'jsonrpcvals')
	{
		if ($this->debug) {
			print("<PRE>---GOT---\n" . htmlentities($data) . "\n---END---\n</PRE>");
		}

		if ($data == '') {
			error_log('XML-RPC: ' . 'jsonrpcmsg::parseResponse' . ': no response received from server.');
			$r = new jsonrpcresp(0, $GLOBALS['xmlrpcerr']['no_data'], $GLOBALS['xmlrpcstr']['no_data']);
			return $r;
		}

		$GLOBALS['_xh'] = array();
		$raw_data = $data;

		if (substr($data, 0, 4) == 'HTTP') {
			$r = &$this->parseResponseHeaders($data, $headers_processed);

			if ($r) {
				$rj = new jsonrpcresp(0, $r->faultCode(), $r->faultString());
				$rj->raw_data = $data;
				return $rj;
			}
		}
		else {
			$GLOBALS['_xh']['headers'] = array();
			$GLOBALS['_xh']['cookies'] = array();
		}

		if ($this->debug) {
			$start = strpos($data, '/* SERVER DEBUG INFO (BASE64 ENCODED):');

			if ($start !== false) {
				$start += strlen('/* SERVER DEBUG INFO (BASE64 ENCODED):');
				$end = strpos($data, '*/', $start);
				$comments = substr($data, $start, $end - $start);
				print("<PRE>---SERVER DEBUG INFO (DECODED) ---\n\t" . htmlentities(str_replace("\n", "\n\t", base64_decode($comments))) . "\n---END---\n</PRE>");
			}
		}

		$data = trim($data);
		$end = strrpos($data, '}');

		if ($end) {
			$data = substr($data, 0, $end + 1);
		}

		if ($return_type == 'json') {
			$r = new jsonrpcresp($data, 0, '', 'json');
			$r->hdrs = $GLOBALS['_xh']['headers'];
			$r->_cookies = $GLOBALS['_xh']['cookies'];
			$r->raw_data = $raw_data;
			return $r;
		}

		if (!jsonrpc_parse_resp($data, $return_type == 'phpvals')) {
			if ($this->debug) {
			}

			$r = new jsonrpcresp(0, $GLOBALS['xmlrpcerr']['invalid_return'], $GLOBALS['xmlrpcstr']['invalid_return'] . ' ' . $GLOBALS['_xh']['isf_reason']);
		}
		else {
			$v = $GLOBALS['_xh']['value'];

			if ($this->debug) {
				print("<PRE>---PARSED---\n");
				var_export($v);
				print("\n---END---</PRE>");
			}

			if ($GLOBALS['_xh']['isf']) {
				$r = new jsonrpcresp(0, $v['faultCode'], $v['faultString']);
			}
			else {
				$r = new jsonrpcresp($v, 0, '', $return_type);
			}

			$r->id = $GLOBALS['_xh']['id'];
		}

		$r->hdrs = $GLOBALS['_xh']['headers'];
		$r->_cookies = $GLOBALS['_xh']['cookies'];
		$r->raw_data = $raw_data;
		return $r;
	}
}
class z1pj6tqybgc extends xmlrpcresp
{
	public $content_type = 'application/json';
	public $id;

	public function serialize($charset_encoding = '')
	{
		if ($charset_encoding != '') {
			$this->content_type = 'application/json; charset=' . $charset_encoding;
		}
		else {
			$this->content_type = 'application/json';
		}

		$this->payload = serialize_jsonrpcresp($this, $this->id, $charset_encoding);
		return $this->payload;
	}
}
class jsonrpcval extends xmlrpcval
{
	public function serialize($charset_encoding = '')
	{
		return serialize_jsonrpcval($this, $charset_encoding);
	}
}

?>
