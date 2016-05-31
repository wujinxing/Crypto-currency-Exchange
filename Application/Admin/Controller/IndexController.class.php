<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Admin\Controller;

class IndexController extends AdminController
{
	public function index()
	{
		$arr = array();
		$arr['reg_sum'] = M('User')->count();
		$arr['cny_num'] = M('UserCoin')->sum('cny') + M('UserCoin')->sum('cnyd');
		$arr['trance_mum'] = M('TradeLog')->sum('mum');

		if (10000 < $arr['trance_mum']) {
			$arr['trance_mum'] = round($arr['trance_mum'] / 10000) . '万';
		}

		if (100000000 < $arr['trance_mum']) {
			$arr['trance_mum'] = round($arr['trance_mum'] / 100000000) . '亿';
		}

		$arr['art_sum'] = M('Article')->count();
		$data = array();
		$time = time() - (30 * 24 * 60 * 60);

		for ($i = 0; $i < 30; $i++) {
			$a = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time, 'Y-m-d');
			$mycz = M('Mycz')->where(array(
	'status'  => 1,
	'addtime' => array(
		array('gt', $a),
		array('lt', $time)
		)
	))->sum('num');
			$mytx = M('Mytx')->where(array(
	'status'  => 1,
	'addtime' => array(
		array('gt', $a),
		array('lt', $time)
		)
	))->sum('num');
			if ($mycz || $mytx) {
				$data['cztx'][] = array('date' => $date, 'charge' => $mycz, 'withdraw' => $mytx);
			}
		}

		$time = time() - (30 * 24 * 60 * 60);

		for ($i = 0; $i < 60; $i++) {
			$a = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time, 'Y-m-d');
			$user = M('User')->where(array(
	'addtime' => array(
		array('gt', $a),
		array('lt', $time)
		)
	))->count();

			if ($user) {
				$data['reg'][] = array('date' => $date, 'sum' => $user);
			}
		}

		$this->assign('cztx', json_encode($data['cztx']));
		$this->assign('reg', json_encode($data['reg']));
		$this->assign('arr', $arr);
		$v_file = scandir(APP_PATH . 'ulogs', 1);
		$new = (isset($v_file[0]) ? basename($v_file[0], '.txt') : '2.0');
		$old = '';
		$count = 0;

		foreach ($v_file as $file) {
			if (($file == '..') || ($file == '.')) {
				continue;
			}

			$count++;

			if (5 < $count) {
				break;
			}

			$old .= basename($file, '.txt') . ' ';
		}

		$this->assign('varsion', array('new' => $new, 'old' => $old));
		$this->display();
	}

	public function market($name = NULL)
	{
		if (!$name) {
			$name = C('market_mr');
		}

		$name = trim($name);
		list($xnb) = explode('_', $name);
		list(, $rmb) = explode('_', $name);
		$this->assign('xnb', $xnb);
		$this->assign('rmb', $rmb);
		$this->assign('name', $name);
		$data = array();
		$data['trance_num'] = M('TradeLog')->where(array('market' => $name))->sum('num');
		$data['trance_buyfee'] = M('TradeLog')->where(array('market' => $name))->sum('fee_buy');
		$data['trance_sellfee'] = M('TradeLog')->where(array('market' => $name))->sum('fee_sell');
		$data['trance_fee'] = $data['trance_buyfee'] + $data['trance_sellfee'];
		$data['trance_mum'] = M('TradeLog')->where(array('market' => $name))->sum('mum');
		$data['trance_ci'] = M('TradeLog')->where(array('market' => $name))->count();
		$market_json = M('Market_json')->where(array('name' => $name))->order('id desc')->find();

		if ($market_json) {
			$addtime = $market_json['addtime'] + 60;
		}
		else {
			$addtime = M('TradeLog')->where(array('market' => $name))->order('id asc')->find()['addtime'];
		}

		$t = $addtime;
		$start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
		$end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));

		if (($end + 60) < time()) {
			$trade_num = M('TradeLog')->where(array(
	'addtime' => array(
		array('egt', $start),
		array('elt', $end)
		)
	))->sum('num');
			$trade_mum = M('TradeLog')->where(array(
	'addtime' => array(
		array('egt', $start),
		array('elt', $end)
		)
	))->sum('mum');
			$trade_fee_buy = M('TradeLog')->where(array(
	'addtime' => array(
		array('egt', $start),
		array('elt', $end)
		)
	))->sum('fee_buy');
			$trade_fee_sell = M('TradeLog')->where(array(
	'addtime' => array(
		array('egt', $start),
		array('elt', $end)
		)
	))->sum('fee_sell');
			$d = array($trade_num, $trade_mum, $trade_fee_buy, $trade_fee_sell);

			if (M('Market_json')->where(array('name' => $name, 'addtime' => $end))->find()) {
				M('Market_json')->where(array('name' => $name, 'addtime' => $end))->save(array('data' => json_encode($d)));
			}
			else {
				M('Market_json')->add(array('name' => $name, 'data' => json_encode($d), 'addtime' => $end));
			}
		}

		$tradeJson = M('Market_json')->where(array('name' => $name))->order('id asc')->limit(100)->select();

		foreach ($tradeJson as $k => $v) {
			$date = addtime($v['addtime'], 'Y-m-d H:i:s');
			$json_data = json_decode($v['data'], true);
			$cztx[] = array('date' => $date, 'num' => $json_data[0], 'mum' => $json_data[1], 'fee_buy' => $json_data[2], 'fee_sell' => $json_data[3]);
		}

		$this->assign('cztx', json_encode($cztx));
		$this->assign('data', $data);
		$this->display();
	}

	public function coin($name = NULL)
	{
		if (!$name) {
			$name = C('xnb_mr');
		}

		$this->assign('name', $name);
		$data = array();
		$data['trance_b'] = M('UserCoin')->sum($name);
		$data['trance_s'] = M('UserCoin')->sum($name . 'd');
		$data['trance_num'] = $data['trance_b'] + $data['trance_s'];
		$data['trance_song'] = M('Myzr')->where(array('coinname' => $name))->sum('fee');
		$data['trance_fee'] = M('Myzc')->where(array('coinname' => $name))->sum('fee');

		if (C('coin')[$name]['type'] == 'qbb') {
			$dj_username = C('coin')[$name]['dj_yh'];
			$dj_password = C('coin')[$name]['dj_mm'];
			$dj_address = C('coin')[$name]['dj_zj'];
			$dj_port = C('coin')[$name]['dj_dk'];
			$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
			$json = $CoinClient->getinfo();
			if (!isset($json['version']) || !$json['version']) {
				$this->error('钱包链接失败！');
			}

			$data['trance_mum'] = $json['balance'];
		}
		else {
			$data['trance_mum'] = 0;
		}

		$this->assign('data', $data);
		$market_json = M('Coin_json')->where(array('name' => $name))->order('id desc')->find();

		if ($market_json) {
			$addtime = $market_json['addtime'] + 60;
		}
		else {
			$addtime = M('Myzr')->where(array('name' => $name))->order('id asc')->find()['addtime'];
		}

		$t = $addtime;
		$start = mktime(0, 0, 0, date('m', $t), date('d', $t), date('Y', $t));
		$end = mktime(23, 59, 59, date('m', $t), date('d', $t), date('Y', $t));

		if (($end + 60) < time()) {
			$trade_num = M('UserCoin')->where(array(
	'addtime' => array(
		array('egt', $start),
		array('elt', $end)
		)
	))->sum($name);
			$trade_mum = M('UserCoin')->where(array(
	'addtime' => array(
		array('egt', $start),
		array('elt', $end)
		)
	))->sum($name . 'd');
			$aa = $trade_num + $trade_mum;

			if (C($name)['type'] == 'qbb') {
				$bb = $json['balance'];
			}
			else {
				$bb = 0;
			}

			$trade_fee_buy = M('Myzr')->where(array(
	'addtime' => array(
		array('egt', $start),
		array('elt', $end)
		)
	))->sum('fee');
			$trade_fee_sell = M('Myzc')->where(array(
	'addtime' => array(
		array('egt', $start),
		array('elt', $end)
		)
	))->sum('fee');
			$d = array($aa, $bb, $trade_fee_buy, $trade_fee_sell);

			if (M('Coin_json')->where(array('name' => $name, 'addtime' => $end))->find()) {
				M('Coin_json')->where(array('name' => $name, 'addtime' => $end))->save(array('data' => json_encode($d)));
			}
			else {
				M('Coin_json')->add(array('name' => $name, 'data' => json_encode($d), 'addtime' => $end));
			}
		}

		$tradeJson = M('Coin_json')->where(array('name' => $name))->order('id asc')->limit(100)->select();

		foreach ($tradeJson as $k => $v) {
			$date = addtime($v['addtime'], 'Y-m-d H:i:s');
			$json_data = json_decode($v['data'], true);
			$cztx[] = array('date' => $date, 'num' => $json_data[0], 'mum' => $json_data[1], 'fee_buy' => $json_data[2], 'fee_sell' => $json_data[3]);
		}

		$this->assign('cztx', json_encode($cztx));
		$this->display();
	}

	public function info()
	{
		$this->display();
	}
}

?>
