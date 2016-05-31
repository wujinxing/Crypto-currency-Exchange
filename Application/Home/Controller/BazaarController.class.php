<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Home\Controller;

class BazaarController extends HomeController
{
	public function __construct()
	{
		parent::__construct();
		$this->title = '集市交易';
		exit();
	}

	public function index()
	{
		if (IS_POST) {
			if (!C('bazaar_kai')) {
				$this->error('交易市场关闭！');
			}

			$input = I('post.');

			if (!C('coin')[$input['coin']]) {
				$this->error('交易市场错误！');
			}

			if (!check($input['num'], 'double')) {
				$this->error('交易数量格式错误！');
			}

			$bazaar_min_num = (C('bazaar_min_num') ? C('bazaar_min_num') : 9.9999999999999995E-7);
			$bazaar_max_num = (C('bazaar_max_num') ? C('bazaar_max_num') : 1000000);

			if ($input['num'] < $bazaar_min_num) {
				$this->error('交易数量不能小于' . $bazaar_min_num . '元');
			}

			if ($bazaar_max_num < $input['num']) {
				$this->error('交易数量不能大于' . $bazaar_max_num . '元');
			}

			if (!check($input['price'], 'double')) {
				$this->error('交易价格格式错误');
			}

			$bazaar_min_price = (C('bazaar_min_price') ? C('bazaar_min_price') : 9.9999999999999995E-7);
			$bazaar_max_price = (C('bazaar_max_price') ? C('bazaar_max_price') : 1000000);

			if ($input['price'] < $bazaar_min_price) {
				$this->error('交易价格不能小于' . $bazaar_min_price . '元');
			}

			if ($bazaar_max_price < $input['price']) {
				$this->error('交易价格不能大于' . $bazaar_max_price . '元');
			}

			$mum = round($input['num'] * $input['price'], 6);
			$fee = C('bazaar_fee');

			if (!check($input['paypassword'], 'password')) {
				$this->error('交易密码格式错误！');
			}

			$user = $this->User(0, 0);

			if (!$user['id']) {
				$this->error('请先登录！');
			}

			if (md5($input['paypassword']) != $user['paypassword']) {
				$this->error('交易密码错误！');
			}

			if ($user['coin'][$input['coin']] < $input['num']) {
				$this->error('可用余额不足');
			}

			$mo = M();
			$mo->execute('set autocommit=0');
			$mo->execute('lock tables movesay_user_coin write  , movesay_bazaar write');
			$rs = array();
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $user['id']))->setDec($input['coin'], $input['num']);
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $user['id']))->setInc($input['coin'] . 'j', $input['num']);
			$rs[] = $mo->table('movesay_bazaar')->add(array('userid' => $user['id'], 'coin' => $input['coin'], 'price' => $input['price'], 'num' => $input['num'], 'mum' => $mum, 'fee' => $fee, 'addtime' => time(), 'status' => 0));

			if (check_arr($rs)) {
				$mo->execute('commit');
				$mo->execute('unlock tables');
				$this->success('委托成功！');
			}
			else {
				$mo->execute('rollback');
				$this->error(APP_DEBUG ? implode('|', $rs) : '委托失败!');
			}
		}
		else {
/* [31m * TODO SEPARATE[0m */
			$this->get_text();
			$input = I('get.');
			$coin = (is_array(C('coin')[$input['coin']]) ? trim($input['coin']) : C('xnb_mr'));
			$this->assign('coin', $coin);
			$where['coin'] = $coin;
			$where['status'] = 0;
			import('ORG.Util.Page');
			$Moble = M('Bazaar');
			$count = $Moble->where($where)->count();
			$Page = new \Think\Page($count, 30);
			$show = $Page->show();
			$list = $Moble->where($where)->order('price asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

			foreach ($list as $k => $v) {
				$list[$k]['price'] = $v['price'] * 1;
				$list[$k]['num'] = $v['num'] * 1;
				$list[$k]['mum'] = $v['mum'] * 1;
			}
/* [31m * TODO SEPARATE[0m */

			$this->assign('list', $list);
			$this->assign('page', $show);
			$input = I('get.');
			$coin = (is_array(C('coin')[$input['coin']]) ? trim($input['coin']) : C('xnb_mr'));
			$this->assign('coin', $coin);
			$this->assign('dongjie', $user['coin'][$coin . 'j']);
			$this->display();
		}
	}

	public function log()
	{
		if (IS_POST) {
			$input = I('post.');

			if (!check($input['id'], 'd')) {
				$this->error('请选择要要买入的挂单！');
			}

			if (!check($input['num'], 'double')) {
				$this->error('交易数量格式错误');
			}
			else {
				$num = round(trim($input['num']), 6);
			}

			if (10000000 < $num) {
				$this->error('交易数量超过最大限制！');
			}

			if ($num < 9.9999999999999995E-7) {
				$this->error('交易数量超过最小限制！');
			}

			$user = $this->User(0, 0);

			if (!$user['id']) {
				$this->error('请先登录！');
			}

			$bazaar = M('Bazaar')->where(array('id' => $input['id'], 'status' => 0))->find();

			if (!$bazaar) {
				$this->error('挂单错误！');
			}

			if (md5($input['paypassword']) != $user['paypassword']) {
				$this->error('交易密码错误！');
			}

			if (($bazaar['num'] - $bazaar['deal']) < $input['num']) {
				$this->error('剩余量不足！');
			}

			$mum = round($bazaar['price'] * $input['num'], 6);
			$fee = C('bazaar_fee');

			if ($user['coin'][$bazaar['coin']] < $mum) {
				$this->error('可用余额不足');
			}

			$buy_shang_mum = round(((($mum / 100) * (100 - $fee)) / 100) * (100 - C('bazaar_invit1')), 6);
			$sell_mum = round(($mum / 100) * (100 - $fee), 6);
			$zong_fee = round(($mum / 100) * $fee, 6);
			$mo = M();
			$mo->execute('set autocommit=0');
			$mo->execute('lock tables movesay_invit write , movesay_user write , movesay_user_coin write  , movesay_bazaar write  , movesay_bazaar_log write');
			$rs = array();
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $user['id']))->setDec(C('rmb_mr'), $mum);
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $user['id']))->setInc($bazaar['coin'], $input['num']);
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $bazaar['userid']))->setInc(C('rmb_mr'), $sell_mum);
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $bazaar['userid']))->setDec($bazaar['coin'], $input['num']);
			$rs[] = $mo->table('movesay_bazaar')->where(array('id' => $bazaar['id']))->setInc('deal', $input['num']);

			if ($bazaar['num'] <= $bazaar['deal']) {
				$rs[] = $mo->table('movesay_bazaar')->where(array('id' => $bazaar['id']))->save(array('status' => 1));
			}

			$rs[] = $mo->table('movesay_bazaar_log')->add(array('userid' => $user['id'], 'peerid' => $bazaar['userid'], 'coin' => $bazaar['coin'], 'price' => $bazaar['price'], 'num' => $input['num'], 'mum' => $mum, 'fee' => $zong_fee, 'addtime' => time(), 'status' => 1));

			if ($buy_shang_mum) {
				$invit = $mo->table('movesay_user')->where(array('id' => $bazaar['userid']))->find();

				if ($invit['id']) {
					$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $invit['id']))->setInc(C('rmb_mr'), $buy_shang_mum);
					$rs[] = $mo->table('movesay_invit')->add(array('userid' => $bazaar['userid'], 'invit' => $invit['username'], 'type' => '集市赠送', 'num' => $mum, 'mum' => $buy_shang_mum, 'addtime' => time(), 'status' => 1));
				}
			}

			if (check_arr($rs)) {
				$mo->execute('commit');
				$mo->execute('unlock tables');
				$this->success('购买成功！');
			}
			else {
				$mo->execute('rollback');
				$this->error(APP_DEBUG ? implode('|', $rs) : '购买失败!');
			}
		}
		else {
/* [31m * TODO SEPARATE[0m */
			$this->get_text();
			$input = I('get.');
			$coin = (is_array(C('coin')[$input['coin']]) ? trim($input['coin']) : C('xnb_mr'));
			$this->assign('coin', $coin);
			$where['coin'] = $coin;
			$where['status'] = 0;
			import('ORG.Util.Page');
			$Moble = M('Bazaar');
			$count = $Moble->where($where)->count();
			$Page = new \Think\Page($count, 30);
			$show = $Page->show();
			$list = $Moble->where($where)->order('price asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

			foreach ($list as $k => $v) {
				$list[$k]['price'] = $v['price'] * 1;
				$list[$k]['num'] = $v['num'] * 1;
				$list[$k]['mum'] = $v['mum'] * 1;
			}

			$this->assign('list', $list);
			$this->assign('page', $show);
			$this->display();
		}
	}

	public function whole()
	{
/* [31m * TODO SEPARATE[0m */
		$this->get_text();
		$user = $this->User();
		$input = I('get.');
		$coin = (is_array(C('coin')[$input['coin']]) ? trim($input['coin']) : C('xnb_mr'));
		$this->assign('coin', $coin);
		$where['coin'] = $coin;
		$where['status'] = 1;
		import('ORG.Util.Page');
		$Moble = M('BazaarLog');
		$count = $Moble->where($where)->count();
		$Page = new \Think\Page($count, 30);
		$show = $Page->show();
		$list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['price'] = $v['price'] * 1;
			$list[$k]['num'] = $v['num'] * 1;
			$list[$k]['mum'] = $v['mum'] * 1;
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function mybuy()
	{
/* [31m * TODO SEPARATE[0m */
		$this->get_text();
		$user = $this->User();
		$input = I('get.');
		$coin = (is_array(C('coin')[$input['coin']]) ? trim($input['coin']) : C('xnb_mr'));
		$this->assign('coin', $coin);
		$where['coin'] = $coin;
		$where['status'] = 1;
		$where['userid'] = $user['id'];
		import('ORG.Util.Page');
		$Moble = M('BazaarLog');
		$count = $Moble->where($where)->count();
		$Page = new \Think\Page($count, 30);
		$show = $Page->show();
		$list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['price'] = $v['price'] * 1;
			$list[$k]['num'] = $v['num'] * 1;
			$list[$k]['mum'] = $v['mum'] * 1;
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function mysell()
	{
		if (IS_POST) {
			$input = I('post.');

			if (!check($input['id'], 'd')) {
				$this->error('请选择要撤销的委托！');
			}

			$user = $this->User(0, 0);

			if (!$user['id']) {
				$this->error('请先登录！');
			}

			$bazaar = M('Bazaar')->where(array('id' => $input['id'], 'userid' => $user['id'], 'status' => 0))->find();

			if (!$bazaar) {
				$this->error('撤销委托参数错误！');
			}

			$fee = C('bazaar_fee');
			$mo = M();
			$mo->execute('set autocommit=0');
			$mo->execute('lock tables movesay_user_coin write  , movesay_bazaar write ');
			$rs = array();
			$mun = round($bazaar['num'] - $bazaar['deal'], 6);
			$user_sell = $mo->table('movesay_user_coin')->where(array('userid' => $bazaar['userid']))->find();

			if ($mun <= round($user_sell[$bazaar['coin'] . 'j'], 6)) {
				$save_sell_xnb = $mun;
			}
			else if ($mun <= round($user_sell[$bazaar['coin'] . 'j'], 6) + 1) {
				$save_sell_xnb = $user_sell[$bazaar['coin'] . 'j'];
			}
			else {
				$mo->execute('rollback');
				$this->error('撤销失败!');
			}

			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $bazaar['userid']))->setInc($bazaar['coin'], $save_sell_xnb);
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $bazaar['userid']))->setDec($bazaar['coin'] . 'j', $save_sell_xnb);
			$rs[] = $mo->table('movesay_bazaar')->where(array('id' => $bazaar['id']))->setField('status', 2);
			$you_sell = $mo->table('movesay_bazaar')->where(array('coin' => $bazaar['coin'], 'status' => 0, 'userid' => $bazaar['userid']))->find();

			if (!$you_sell) {
				$you_user_sell = $mo->table('movesay_user_coin')->where(array('userid' => $bazaar['userid']))->find();

				if (0 < $you_user_sell[$bazaar['coin'] . 'j']) {
					$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $bazaar['userid']))->setField($bazaar['coin'] . 'j', 0);
				}
			}

			if (check_arr($rs)) {
				$mo->execute('commit');
				$mo->execute('unlock tables');
				$this->success('撤销成功！');
			}
			else {
				$mo->execute('rollback');
				$this->error(APP_DEBUG ? implode('|', $rs) : '撤销失败!');
			}
		}
		else {
/* [31m * TODO SEPARATE[0m */
			$this->get_text();
			$user = $this->User();
			$input = I('get.');
			$coin = (is_array(C('coin')[$input['coin']]) ? trim($input['coin']) : C('xnb_mr'));
			$this->assign('coin', $coin);
			$where['coin'] = $coin;
			$where['status'] = array('egt', 0);
			$where['userid'] = $user['id'];
			import('ORG.Util.Page');
			$Moble = M('Bazaar');
			$count = $Moble->where($where)->count();
			$Page = new \Think\Page($count, 30);
			$show = $Page->show();
			$list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

			foreach ($list as $k => $v) {
				$list[$k]['price'] = $v['price'] * 1;
				$list[$k]['num'] = $v['num'] * 1;
				$list[$k]['mum'] = $v['mum'] * 1;
			}

			$this->assign('list', $list);
			$this->assign('page', $show);
			$this->display();
		}
	}
}

?>
