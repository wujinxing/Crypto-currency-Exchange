<?php

namespace Admin\Controller;

class CoinController extends AdminController
{
	private $Model;

	public function __construct()
	{
		parent::__construct();
		$this->Model = M('Coin');
		$this->Title = '币种配置';
	}

	public function index($name = NULL)
	{
		if ($name) {
			$where['name'] = $name;
		}

		$count = $this->Model->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function edit($id = NULL)
	{
		if (empty($id)) {
			$this->data = array();
		}
		else {
			$this->data = $this->Model->where(array('id' => trim($_GET['id'])))->find();
		}

		$this->display();
	}

	public function save()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/coin/';
		$upload->autoSub = false;
		$info = $upload->upload();

		if ($info) {
			foreach ($info as $k => $v) {
				$_POST[$v['key']] = $v['savename'];
			}
		}

		$_POST['fee_bili'] = floatval($_POST['fee_bili']);
		if ($_POST['fee_bili'] && (($_POST['fee_bili'] < 0.01) || (100 < $_POST['fee_bili']))) {
			$this->error('挂单比例只能是0.01--100之间(不用填写%)！');
		}

		$_POST['zr_zs'] = floatval($_POST['zr_zs']);
		if ($_POST['zr_zs'] && (($_POST['zr_zs'] < 0.01) || (100 < $_POST['zr_zs']))) {
			$this->error('转入赠送只能是0.01--100之间(不用填写%)！');
		}

		$_POST['zr_dz'] = intval($_POST['zr_dz']);
		$_POST['zc_fee'] = floatval($_POST['zc_fee']);
		if ($_POST['zc_fee'] && (($_POST['zc_fee'] < 0.01) || (100 < $_POST['zc_fee']))) {
			$this->error('转出手续费只能是0.01--100之间(不用填写%)！');
		}

		if ($_POST['zc_user']) {
			if (!check($_POST['zc_user'], 'dw')) {
				$this->error('官方手续费地址格式不正确！');
			}

			$ZcUser = M('UserCoin')->where(array($_POST['name'] . 'b' => $_POST['zc_user']))->find();

			if (!$ZcUser) {
				$this->error('在系统中查询不到[官方手续费地址],请务必填写正确！');
			}
		}

		$_POST['zc_min'] = intval($_POST['zc_min']);
		$_POST['zc_max'] = intval($_POST['zc_max']);

		if ($_POST['id']) {
			$rs = $this->Model->save($_POST);
		}
		else {
			if (!check($_POST['name'], 'n')) {
				$this->error('币种简称只能是小写字母！');
			}

			$_POST['name'] = strtolower($_POST['name']);

			if (check($_POST['name'], 'username')) {
				$this->error('币种名称格式不正确！');
			}

			if ($this->Model->where(array('name' => $_POST['name']))->find()) {
				$this->error('币种存在！');
			}

			$rea = M()->execute('ALTER TABLE  `movesay_user_coin` ADD  `' . $_POST['name'] . '` DECIMAL(20,8) UNSIGNED NOT NULL');
			$reb = M()->execute('ALTER TABLE  `movesay_user_coin` ADD  `' . $_POST['name'] . 'd` DECIMAL(20,8) UNSIGNED NOT NULL ');
			$rec = M()->execute('ALTER TABLE  `movesay_user_coin` ADD  `' . $_POST['name'] . 'b` VARCHAR(200) NOT NULL ');
			$rs = $this->Model->add($_POST);
		}

		if ($rs) {
			$this->success('操作成功！', U('Coin/index'));
		}
		else {
			$this->error('数据未修改！');
		}
	}

	public function status()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (IS_POST) {
			$id = array();
			$id = implode(',', $_POST['id']);
		}
		else {
			$id = $_GET['id'];
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$method = $_GET['method'];

		switch (strtolower($method)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'delete':
			$rs = $this->Model->where($where)->select();

			foreach ($rs as $k => $v) {
				$rs[] = M()->execute('ALTER TABLE  `movesay_user_coin` DROP COLUMN ' . $v['name']);
				$rs[] = M()->execute('ALTER TABLE  `movesay_user_coin` DROP COLUMN ' . $v['name'] . 'd');
				$rs[] = M()->execute('ALTER TABLE  `movesay_user_coin` DROP COLUMN ' . $v['name'] . 'b');
			}

			if ($this->Model->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('参数非法');
		}

		if ($this->Model->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function info($coin)
	{
		$dj_username = C('coin')[$coin]['dj_yh'];
		$dj_password = C('coin')[$coin]['dj_mm'];
		$dj_address = C('coin')[$coin]['dj_zj'];
		$dj_port = C('coin')[$coin]['dj_dk'];
		$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);

		if (!$CoinClient) {
			$this->error('钱包对接失败！');
		}

		$info['b'] = $CoinClient->getinfo();
		$info['num'] = M('UserCoin')->sum($coin) + M('UserCoin')->sum($coin . 'd');
		$info['coin'] = $coin;
		$this->assign('data', $info);
		$this->display();
	}

	public function user($coin)
	{
		$dj_username = C('coin')[$coin]['dj_yh'];
		$dj_password = C('coin')[$coin]['dj_mm'];
		$dj_address = C('coin')[$coin]['dj_zj'];
		$dj_port = C('coin')[$coin]['dj_dk'];
		$CoinClient = CoinClient($dj_username, $dj_password, $dj_address, $dj_port);

		if (!$CoinClient) {
			$this->error('钱包对接失败！');
		}

		$arr = $CoinClient->listaccounts();

		foreach ($arr as $k => $v) {
			if ($k) {
				if ($v < 1.0000000000000001E-5) {
					$v = 0;
				}

				$list[$k]['num'] = $v;
				$str = '';
				$addr = $CoinClient->getaddressesbyaccount($k);

				foreach ($addr as $kk => $vv) {
					$str .= $vv . '<br>';
				}

				$list[$k]['addr'] = $str;
				$userid = M('User')->where(array('username' => $k))->getField('id');
				$user_coin = M('UserCoin')->where(array('userid' => $userid))->find();
				$list[$k]['xnb'] = $user_coin[$coin];
				$list[$k]['xnbd'] = $user_coin[$coin . 'd'];
				$list[$k]['zj'] = $list[$k]['xnb'] + $list[$k]['xnbd'];
				$list[$k]['xnbb'] = $user_coin[$coin . 'b'];
				unset($str);
			}
		}

		$this->assign('list', $list);
		$this->display();
	}

	public function qing($coin)
	{
		if (!C('coin')[$coin]) {
			$this->error('参数错误！');
		}

		$info = M()->execute('UPDATE `movesay_user_coin` SET `' . trim($coin) . 'b`=\'\' ;');

		if ($info) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}
}

?>
