<?php

namespace Home\Controller;

class FinanceController extends HomeController
{
	public function index()
	{
		if (!userid()) {
			redirect('/#login');
		}

		$CoinList = M('Coin')->where(array('status' => 1))->select();
		$UserCoin = M('UserCoin')->where(array('userid' => userid()))->find();
		$Market = M('Market')->where(array('status' => 1))->select();

		foreach ($Market as $k => $v) {
			$Market[$v['name']] = $v;
		}

		$cny['zj'] = 0;

		foreach ($CoinList as $k => $v) {
			if ($v['name'] == 'cny') {
				$cny['ky'] = round($UserCoin[$v['name']] * 1,2);
				$cny['dj'] = round($UserCoin[$v['name'] . 'd'] * 1,2);
				$cny['zj'] = round($cny['zj'] + $cny['ky'] + $cny['dj'],2);
			}
			else {
				if ($Market[$v['name'] . '_cny']['new_price']) {
					$jia = $Market[$v['name'] . '_cny']['new_price'];
				}
				else {
					$jia = 1;
				}

				$coinList[$v['name']] = array('name' => $v['name'], 'img' => $v['img'], 'title' => $v['title'] . '(' . strtoupper($v['name']) . ')', 'xnb' => $UserCoin[$v['name']] * 1, 'xnbd' => $UserCoin[$v['name'] . 'd'] * 1, 'xnbz' => round($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd'], 8), 'jia' => $jia * 1, 'zhehe' => round(($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia, 2));
				$cny['zj'] = round($cny['zj'] + (round(($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia, 8) * 1),2);
			}
		}

		$this->assign('cny', $cny);
		$this->assign('coinList', $coinList);
		$this->display();
	}

	public function mycz()
	{
		if (!userid()) {
			redirect('/#login');
		}

		$myczType = M('MyczType')->where(array('status' => 1))->select();

		foreach ($myczType as $k => $v) {
			$myczTypeList[$v['name']] = $v['title'];
		}

		$this->assign('myczTypeList', $myczTypeList);
		$user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
		$this->assign('user_coin', $user_coin);
		$moble = M('User')->where(array('id' => userid()))->getField('moble');

		if ($moble) {
			$moble = substr_replace($moble, '****', 3, 4);
		}
		else {
			$this->error('请先认证手机！');
		}

		$this->assign('moble', $moble);
		$where['userid'] = userid();
		$Model = M('Mycz');
		$count = $Model->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['type'] = M('MyczType')->where(array('name' => $v['type']))->getField('title');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function upmycz($type, $num)
	{
		if (!userid()) {
			redirect('/#login');
		}

		if (!check($type, 'n')) {
			$this->error('充值方式格式错误！');
		}

		$myczType = M('MyczType')->where(array('status' => 1))->select();

		foreach ($myczType as $k => $v) {
			$myczTypeList[$v['name']] = $v['title'];
		}

		if (!$myczTypeList[$type]) {
			$this->error('充值方式错误！');
		}

		if (!check($num, 'cny')) {
			$this->error('充值金额格式错误！');
		}

		$mycz_min = (C('mycz_min') ? C('mycz_min') : 1);
		$mycz_max = (C('mycz_max') ? C('mycz_max') : 100000);

		if ($num < $mycz_min) {
			$this->error('充值金额不能小于' . $mycz_min . '元！');
		}

		if ($mycz_max < $num) {
			$this->error('充值金额不能大于' . $mycz_max . '元！');
		}

		for (; ; ) {
			$tradeno = tradeno();

			if (!M('Mycz')->where(array('tradeno' => $tradeno))->find()) {
				break;
			}
		}

		$mycz = M('Mycz')->add(array('userid' => userid(), 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0));

		if ($mycz) {
			$this->success('充值订单创建成功！', array('id' => $mycz));
		}
		else {
			$this->error('提现订单创建失败！');
		}
	}

	public function mytx()
	{
		if (!userid()) {
			redirect('/#login');
		}

		$moble = M('User')->where(array('id' => userid()))->getField('moble');

		if ($moble) {
			$moble = substr_replace($moble, '****', 3, 4);
		}
		else {
			$this->error('请先认证手机！');
		}

		$this->assign('moble', $moble);
		$user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
		$this->assign('user_coin', $user_coin);
		$userBankList = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
		$this->assign('userBankList', $userBankList);
		$where['userid'] = userid();
		$Model = M('Mytx');
		$count = $Model->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function upmytx($moble_verify, $num, $paypassword, $type)
	{
		if (!userid()) {
			redirect('/#login');
		}

		if (!check($moble_verify, 'd')) {
			$this->error('短信验证码格式错误！');
		}

		if ($moble_verify != session('mytx_verify')) {
			$this->error('短信验证码错误！');
		}

		if (!check($num, 'd')) {
			$this->error('提现金额格式错误！');
		}

		$mytx_min = (C('mytx_min') ? C('mytx_min') : 1);
		$mytx_max = (C('mytx_max') ? C('mytx_max') : 1000000);
		$mytx_bei = C('mytx_bei');
		$mytx_fee = C('mytx_fee');

		if ($num < $mytx_min) {
			$this->error('每次提现金额不能小于' . $mytx_min . '元！');
		}

		if ($mytx_max < $num) {
			$this->error('每次提现金额不能大于' . $mytx_max . '元！');
		}

		if ($mytx_bei) {
			if (($num % $mytx_bei) != 0) {
				$this->error('每次提现金额必须是' . $mytx_bei . '的整倍数！');
			}
		}

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		$userBank = M('UserBank')->where(array('id' => $type))->find();

		if (!$userBank) {
			$this->error('提现地址错误！');
		}

		$user = M('User')->where(array('id' => userid()))->find();

		if (md5($paypassword) != $user['paypassword']) {
			$this->error('交易密码错误！');
		}

		$cny = M('UserCoin')->where(array('userid' => userid()))->getField('cny');

		if ($cny < $num) {
			$this->error('可用人民币余额不足！');
		}

		$fee = round(($num / 100) * $mytx_fee, 2);
		$mum = round(($num / 100) * (100 - $mytx_fee), 2);
		$mo = M();
		$mo->execute('set autocommit=0');
		$mo->execute('lock tables movesay_mytx write , movesay_user_coin  write ');
		$rs = array();
		$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => userid()))->setDec('cny', $num);
		$rs[] = $mo->table('movesay_mytx')->add(array('userid' => userid(), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'name' => $userBank['name'], 'truename' => $user['truename'], 'bank' => $userBank['bank'], 'bankprov' => $userBank['bankprov'], 'bankcity' => $userBank['bankcity'], 'bankaddr' => $userBank['bankaddr'], 'bankcard' => $userBank['bankcard'], 'addtime' => time(), 'status' => 0));

		if (check_arr($rs)) {
			$mo->execute('commit');
			$mo->execute('unlock tables');
			$this->success('提现订单创建成功！');
		}
		else {
			$mo->execute('rollback');
			$this->error('提现订单创建失败！');
		}
	}

	public function myzr($coin = NULL)
	{
		if (!userid()) {
			redirect('/#login');
		}

		if (C('coin')[$coin]) {
			$coin = trim($coin);
		}
		else {
			$coin = C('xnb_mr');
		}

		$this->assign('xnb', $coin);
		$Coin = M('Coin')->where(array(
	'status' => 1,
	'name'   => array('neq', 'cny')
	))->select();

		foreach ($Coin as $k => $v) {
			$coin_list[$v['name']] = $v;
		}

		$this->assign('coin_list', $coin_list);
		$user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
		$this->assign('user_coin', $user_coin);
		$Coin = M('Coin')->where(array('name' => $coin))->find();
		$this->assign('zr_jz', $Coin['zr_jz']);

		if (!$Coin['zr_jz']) {
			$qianbao = '当前币种暂停转入！';
		}
		else {
			$qbdz = $coin . 'b';

			if (!$user_coin[$qbdz]) {
				if ($Coin['type'] == 'rgb') {
					$qianbao = md5(username() . $coin);
					$rs = M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao));

					if (!$rs) {
						$this->error('生成钱包地址出错！');
					}
				}

				if ($Coin['type'] == 'qbb') {
					$dj_username = $Coin['dj_yh'];
					$dj_password = $Coin['dj_mm'];
					$dj_address = $Coin['dj_zj'];
					$dj_port = $Coin['dj_dk'];
					$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
					$json = $CoinClient->getinfo();
					if (!isset($json['version']) || !$json['version']) {
						$this->error('钱包链接失败！');
					}

					$qianbao_addr = $CoinClient->getaddressesbyaccount(username());

					if (!is_array($qianbao_addr)) {
						$qianbao_ad = $CoinClient->getnewaddress(username());

						if (!$qianbao_ad) {
							$this->error('生成钱包地址出错1！');
						}
						else {
							$qianbao = $qianbao_ad;
						}
					}
					else {
						$qianbao = $qianbao_addr[0];
					}

					if (!$qianbao) {
						$this->error('生成钱包地址出错2！');
					}

					$rs = M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao));

					if (!$rs) {
						$this->error('钱包地址添加出错3！');
					}
				}
			}
			else {
				$qianbao = $user_coin[$coin . 'b'];
			}
		}

		$this->assign('qianbao', $qianbao);
		$where['userid'] = userid();
		$where['coinname'] = $coin;
		$Moble = M('Myzr');
		$count = $Moble->where($where)->count();
		$Page = new \Think\Page($count, 10);
		$show = $Page->show();
		$list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function myzc($coin = NULL)
	{
		if (!userid()) {
			redirect('/#login');
		}

		if (C('coin')[$coin]) {
			$coin = trim($coin);
		}
		else {
			$coin = C('xnb_mr');
		}

		$this->assign('xnb', $coin);
		$Coin = M('Coin')->where(array(
	'status' => 1,
	'name'   => array('neq', 'cny')
	))->select();

		foreach ($Coin as $k => $v) {
			$coin_list[$v['name']] = $v;
		}

		$this->assign('coin_list', $coin_list);
		$user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
		$this->assign('user_coin', $user_coin);

		if (!$coin_list[$coin]['zc_jz']) {
			$this->assign('zc_jz', '当前币种暂停转出！');
		}
		else {
			$userQianbaoList = M('UserQianbao')->where(array('userid' => userid(), 'status' => 1, 'coinname' => $coin))->order('id desc')->select();
			$this->assign('userQianbaoList', $userQianbaoList);
			$moble = M('User')->where(array('id' => userid()))->getField('moble');

			if ($moble) {
				$moble = substr_replace($moble, '****', 3, 4);
			}
			else {
				$this->error('你的手机没有认证！');
			}

			$this->assign('moble', $moble);
		}

		$where['userid'] = userid();
		$where['coinname'] = $coin;
		$Moble = M('Myzc');
		$count = $Moble->where($where)->count();
		$Page = new \Think\Page($count, 10);
		$show = $Page->show();
		$list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function upmyzc($coin, $num, $addr, $paypassword, $moble_verify)
	{
		if (!userid()) {
			$this->error('您没有登录请先登录！');
		}

		if (!check($moble_verify, 'd')) {
			$this->error('短信验证码格式错误！');
		}

		if ($moble_verify != session('myzc_verify')) {
			if (!APP_DEBUG) {
				$this->error('短信验证码错误！');
			}
		}

		$num = abs($num);

		if (!check($num, 'currency')) {
			$this->error('数量格式错误！');
		}

		if (!check($addr, 'dw')) {
			$this->error('钱包地址格式错误！');
		}

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		if (!check($coin, 'n')) {
			$this->error('币种格式错误！');
		}

		if (!C('coin')[$coin]) {
			$this->error('币种错误！');
		}

		$Coin = M('Coin')->where(array('name' => $coin))->find();

		if (!$Coin) {
			$this->error('币种错误！');
		}

		$myzc_min = ($Coin['zc_min'] ? abs($Coin['zc_min']) : 0.0001);
		$myzc_max = ($Coin['zc_max'] ? abs($Coin['zc_max']) : 10000000);

		if ($num < $myzc_min) {
			$this->error('转出数量超过系统最小限制！');
		}

		if ($myzc_max < $num) {
			$this->error('转出数量超过系统最大限制！');
		}

		$user = M('User')->where(array('id' => userid()))->find();

		if (md5($paypassword) != $user['paypassword']) {
			$this->error('交易密码错误！');
		}

		$user_coin = M('UserCoin')->where(array('userid' => userid()))->find();

		if ($user_coin[$coin] < $num) {
			$this->error('可用余额不足');
		}

		$qbdz = $coin . 'b';
		$fee_user = M('UserCoin')->where(array($qbdz => $Coin['zc_user']))->find();

		if ($fee_user) {
			debug('手续费地址: ' . $Coin['zc_user'] . ' 存在,有手续费');
			$fee = round(($num / 100) * $Coin['zc_fee'], 8);
			$mum = round($num - $fee, 8);

			if ($mum < 0) {
				$this->error('转出手续费错误！');
			}

			if ($fee < 0) {
				$this->error('转出手续费设置错误！');
			}
		}
		else {
			debug('手续费地址: ' . $Coin['zc_user'] . ' 不存在,无手续费');
			$fee = 0;
			$mum = $num;
		}

		if ($Coin['type'] == 'rgb') {
			debug($Coin, '开始认购币转出');
			$peer = M('UserCoin')->where(array($qbdz => $addr))->find();

			if (!$peer) {
				$this->error('转出认购币地址不存在！');
			}

			$mo = M();
			$mo->execute('set autocommit=0');
			$mo->execute('lock tables  movesay_user_coin write  , movesay_myzc write  , movesay_myzr write , movesay_myzc_fee write');
			$rs = array();
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

			if ($fee) {
				if ($mo->table('movesay_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
					$rs[] = $mo->table('movesay_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
					debug(array('msg' => '转出收取手续费' . $fee), 'fee');
				}
				else {
					$rs[] = $mo->table('movesay_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
					debug(array('msg' => '转出收取手续费' . $fee), 'fee');
				}
			}

			$rs[] = $mo->table('movesay_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
			$rs[] = $mo->table('movesay_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

			if ($fee_user) {
				$rs[] = $mo->table('movesay_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
			}

			if (check_arr($rs)) {
				$mo->execute('commit');
				$mo->execute('unlock tables');
				$this->success('转账成功！');
			}
			else {
				$mo->execute('rollback');
				debug(implode('|', $rs), '失败');
				$this->error('转账失败!');
			}
		}

		if ($Coin['type'] == 'qbb') {
			$mo = M();

			if ($mo->table('movesay_user_coin')->where(array($qbdz => $addr))->find()) {
				debug($Coin, '开始钱包币站内转出');
				$peer = M('UserCoin')->where(array($qbdz => $addr))->find();

				if (!$peer) {
					$this->error('转出地址不存在！');
				}

				$mo = M();
				$mo->execute('set autocommit=0');
				$mo->execute('lock tables  movesay_user_coin write  , movesay_myzc write  , movesay_myzr write , movesay_myzc_fee write');
				$rs = array();
				$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
				$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

				if ($fee) {
					if ($mo->table('movesay_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
						$rs[] = $mo->table('movesay_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
						debug(array('msg' => '转出收取手续费' . $fee), 'fee');
					}
					else {
						$rs[] = $mo->table('movesay_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
						debug(array('msg' => '转出收取手续费' . $fee), 'fee');
					}
				}

				$rs[] = $mo->table('movesay_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
				$rs[] = $mo->table('movesay_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

				if ($fee_user) {
					$rs[] = $mo->table('movesay_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
				}

				if (check_arr($rs)) {
					$mo->execute('commit');
					$mo->execute('unlock tables');
					$this->success('转账成功！');
				}
				else {
					$mo->execute('rollback');
					debug(implode('|', $rs), '失败');
					$this->error('转账失败!');
				}
			}
			else {
				debug($Coin, '开始钱包币站外转出');
				$dj_username = $Coin['dj_yh'];
				$dj_password = $Coin['dj_mm'];
				$dj_address = $Coin['dj_zj'];
				$dj_port = $Coin['dj_dk'];
				$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port, 5, array(), 1);
				$json = $CoinClient->getinfo();
				if (!isset($json['version']) || !$json['version']) {
					$this->error('钱包链接失败！');
				}

				$valid_res = $CoinClient->validateaddress($addr);

				if (!$valid_res['isvalid']) {
					$this->error($addr . '不是一个有效的钱包地址！');
				}

				$auto_status = ($Coin['zc_zd'] && ($num < $Coin['zc_zd']) ? 1 : 0);
				debug(array('zc_zd' => $Coin['zc_zd'], 'mum' => $mum, 'auto_status' => $auto_status), '是否需要审核');

				if ($json['balance'] < $num) {
					$this->error('钱包余额不足');
				}

				$mo = M();
				$mo->execute('set autocommit=0');
				$mo->execute('lock tables  movesay_user_coin write  , movesay_myzc write ,movesay_myzr write, movesay_myzc_fee write');
				$rs = array();
				$rs[] = $r = $mo->table('movesay_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
				debug(array('res' => $r, 'lastsql' => $mo->table('movesay_user_coin')->getLastSql()), '更新用户人民币');
				$rs[] = $aid = $mo->table('movesay_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
				if ($fee && $auto_status) {
					$rs[] = $mo->table('movesay_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

					if ($mo->table('movesay_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
						$rs[] = $r = $mo->table('movesay_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
						debug(array('res' => $r, 'lastsql' => $mo->table('movesay_user_coin')->getLastSql()), '新增费用');
					}
					else {
						$rs[] = $r = $mo->table('movesay_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
					}
				}

				debug(array('res' => $r, 'lastsql' => $mo->table('movesay_myzc')->getLastSql()), '转出记录');

				if (check_arr($rs)) {
					if ($auto_status) {
						$sendrs = $CoinClient->sendtoaddress($addr, $mum);

						if ($sendrs) {
							$flag = 1;
							$arr = json_decode($sendrs, true);
							if (isset($arr['status']) && ($arr['status'] == 0)) {
								$flag = 0;
							}
						}
						else {
							$flag = 0;
						}

						if (!$flag) {
							$mo->execute('rollback');
							$mo->execute('unlock tables');
							$this->error('钱包服务器转出币失败!');
						}
					}

					if ($auto_status) {
						$mo->execute('commit');
						$mo->execute('unlock tables');
						$this->success('转出成功!');
					}
					else {
						$mo->execute('commit');
						$mo->execute('unlock tables');
						$this->success('转出申请成功,请等待审核！');
					}
				}
				else {
					$mo->execute('rollback');
					$this->error('转出失败!');
				}
			}
		}
	}

	public function mywt($market = NULL, $type = NULL, $status = NULL)
	{
		if (!userid()) {
			redirect('/#login');
		}

		$Coin = M('Coin')->where(array('status' => 1))->select();

		foreach ($Coin as $k => $v) {
			$coin_list[$v['name']] = $v;
		}

		$this->assign('coin_list', $coin_list);
		$Market = M('Market')->where(array('status' => 1))->select();

		foreach ($Market as $k => $v) {
			list($v['xnb']) = explode('_', $v['name']);
			list(, $v['rmb']) = explode('_', $v['name']);
			$market_list[$v['name']] = $v;
		}

		$this->assign('market_list', $market_list);

		if (!$market_list[$market]) {
			$market = $Market[0]['name'];
		}

		$where['market'] = $market;
		if (($type == 1) || ($type == 2)) {
			$where['type'] = $type;
		}

		if (($status == 1) || ($status == 2) || ($status == 3)) {
			$where['status'] = $status - 1;
		}

		$where['userid'] = userid();
		$this->assign('market', $market);
		$this->assign('type', $type);
		$this->assign('status', $status);
		$Moble = M('Trade');
		$count = $Moble->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$Page->parameter .= 'type=' . $type . '&status=' . $status . '&market=' . $market . '&';
		$show = $Page->show();
		$list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['num'] = $v['num'] * 1;
			$list[$k]['price'] = $v['price'] * 1;
			$list[$k]['deal'] = $v['deal'] * 1;
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function mycj($market = NULL, $type = NULL)
	{
		if (!userid()) {
			redirect('/#login');
		}

		$Coin = M('Coin')->where(array('status' => 1))->select();

		foreach ($Coin as $k => $v) {
			$coin_list[$v['name']] = $v;
		}

		$this->assign('coin_list', $coin_list);
		$Market = M('Market')->where(array('status' => 1))->select();

		foreach ($Market as $k => $v) {
			list($v['xnb']) = explode('_', $v['name']);
			list(, $v['rmb']) = explode('_', $v['name']);
			$market_list[$v['name']] = $v;
		}

		$this->assign('market_list', $market_list);

		if (!$market_list[$market]) {
			$market = $Market[0]['name'];
		}

		if ($type == 1) {
			$where = 'userid=' . userid() . ' && market=\'' . $market . '\'';
		}
		else if ($type == 2) {
			$where = 'peerid=' . userid() . ' && market=\'' . $market . '\'';
		}
		else {
			$where = '((userid=' . userid() . ') || (peerid=' . userid() . ')) && market=\'' . $market . '\'';
		}

		$this->assign('market', $market);
		$this->assign('type', $type);
		$this->assign('userid', userid());
		$Moble = M('TradeLog');
		$count = $Moble->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$Page->parameter .= 'type=' . $type . '&market=' . $market . '&';
		$show = $Page->show();
		$list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['num'] = $v['num'] * 1;
			$list[$k]['price'] = $v['price'] * 1;
			$list[$k]['mum'] = $v['mum'] * 1;
			$list[$k]['fee_buy'] = $v['fee_buy'] * 1;
			$list[$k]['fee_sell'] = $v['fee_sell'] * 1;
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function mytj()
	{
		if (!userid()) {
			redirect('/#login');
		}

		$user = M('User')->where(array('id' => userid()))->find();

		if (!$user['invit']) {
			for (; ; ) {
				$tradeno = tradenoa();

				if (!M('User')->where(array('invit' => $tradeno))->find()) {
					break;
				}
			}

			M('User')->where(array('id' => userid()))->save(array('invit' => $tradeno));
			$user = M('User')->where(array('id' => userid()))->find();
		}

		$this->assign('user', $user);
		$this->display();
	}

	public function mywd()
	{
		if (!userid()) {
			redirect('/#login');
		}

		$where['invit_1'] = userid();
		$Model = M('User');
		$count = $Model->where($where)->count();
		$Page = new \Think\Page($count, 10);
		$show = $Page->show();
		$list = $Model->where($where)->order('id asc')->field('id,username,moble,addtime,invit_1')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['invits'] = M('User')->where(array('invit_1' => $v['id']))->order('id asc')->field('id,username,moble,addtime,invit_1')->select();
			$list[$k]['invitss'] = count($list[$k]['invits']);

			foreach ($list[$k]['invits'] as $kk => $vv) {
				$list[$k]['invits'][$kk]['invits'] = M('User')->where(array('invit_1' => $vv['id']))->order('id asc')->field('id,username,moble,addtime,invit_1')->select();
				$list[$k]['invits'][$kk]['invitss'] = count($list[$k]['invits'][$kk]['invits']);
			}
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function myjp()
	{
		if (!userid()) {
			redirect('/#login');
		}

		$where['userid'] = userid();
		$Model = M('Invit');
		$count = $Model->where($where)->count();
		$Page = new \Think\Page($count, 10);
		$show = $Page->show();
		$list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['invit'] = M('User')->where(array('id' => $v['invit']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
}

?>
