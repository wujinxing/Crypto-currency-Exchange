<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Home\Controller;

class ShopController extends HomeController
{
	public function __construct()
	{
		parent::__construct();
		$this->title = '集市交易';
	}

	public function index($name = NULL, $type = NULL, $deal = 'deal_desc', $addtime = 'addtime_desc', $price = 'price_desc', $ls = 50)
	{
		if (!userid()) {
			redirect('/#login');
		}

		if ($name) {
			$where['name'] = array('like', '%' . trim($name) . '%');
		}

		if ($type) {
			if (M('ShopType')->where(array('name' => $name))->find()) {
				$where['type'] = trim($type);
			}
		}

		$deal_arr = explode('_', $deal);
		if (($deal_arr[1] == 'asc') || ($deal_arr[1] == 'desc')) {
			$order['deal'] = $deal_arr[1];
		}
		else {
			$order['deal'] = 'desc';
		}

		$addtime_arr = explode('_', $addtime);
		if (($addtime_arr[1] == 'asc') || ($addtime_arr[1] == 'desc')) {
			$order['addtime'] = $addtime_arr[1];
		}
		else {
			$order['addtime'] = 'desc';
		}

		$price_arr = explode('_', $price);
		if (($price_arr[1] == 'asc') || ($price_arr[1] == 'desc')) {
			$order['price'] = $price_arr[1];
		}
		else {
			$order['price'] = 'desc';
		}

		$where['status'] = 1;
		$shop = M('Shop');
		$count = $shop->where($where)->count();
		$Page = new \Think\Page($count, $ls);
		$Page->parameter .= 'name=' . $name . '&type=' . $type . '&deal=' . $deal . '&addtime=' . $addtime . '&price=' . $price . '&';
		$show = $Page->show();
		$list = $shop->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['price'] = $v['price'] * 1;
			$list[$k]['price_1'] = $v['price_1'] * 1;
			$list[$k]['num'] = $v['num'] * 1;
			$list[$k]['deal'] = $v['deal'] * 1;
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function view($id)
	{
		if (!userid()) {
			redirect('/#login');
		}

		if (!check($id, 'd')) {
			$this->error('参数错误！');
		}

		$Shop = M('Shop')->where(array('id' => $id))->find();

		if (!$Shop) {
			$this->error('商品错误！');
		}

		$this->display();
	}

	public function log($ls = 15)
	{
		if (!userid()) {
			redirect('/#login');
		}

		$where['status'] = array('egt', 0);
		$where['userid'] = userid();
		$ShopLog = M('ShopLog');
		$count = $ShopLog->where($where)->count();
		$Page = new \Think\Page($count, $ls);
		$show = $Page->show();
		$list = $ShopLog->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function address()
	{
		if (!userid()) {
			redirect('/#login');
		}

		$ShopAddr = M('ShopAddr')->where(array('userid' => userid()))->find();
		$this->assign('ShopAddr', $ShopAddr);
		$this->display();
	}

	public function shopaddr()
	{
		exit();

		if (!userid()) {
			redirect('/#login');
		}

		$this->display();
	}

	public function buyShop($id, $num, $paypassword, $coin)
	{
		if (!userid()) {
			redirect('/#login');
		}

		if (!check($id, 'd')) {
			$this->error('参数错误！');
		}

		if (!check($num, 'd')) {
			$this->error('购买数量格式错误！');
		}

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		if (!check($coin, 'w')) {
			$this->error('付款方式格式错误！');
		}

		$User = M('User')->where(array('id' => userid()))->find();

		if ($User['paypassword']) {
			$this->error('交易密码非法！');
		}

		if (md5($paypassword) != $User['paypassword']) {
			$this->error('交易密码错误！');
		}

		$Shop = M('Shop')->where(array('id' => $id))->find();

		if (!$Shop) {
			$this->error('商品错误！');
		}

		if (!$Shop['status']) {
			$this->error('当前商品已经卖完！');
		}

		$shop_min = 1;
		$shop_max = 100000000;

		if ($num < $shop_min) {
			$this->error('购买数量超过系统最小限制！');
		}

		if ($shop_max < $num) {
			$this->error('购买数量超过系统最大限制！');
		}

		if (($Shop['num'] - $Shop['deal']) < $num) {
			$this->error('购买数量超过当前剩余量！');
		}

		$coinlistArr = explode(',', $Shop['coinlist']);

		foreach ($coinlistArr as $k => $v) {
			$arr = explode('|', $v);
			$CoinList[$arr[0]] = $v[1];
		}

		if (!$CoinList[$coin]) {
			$this->error('付款方式错误！');
		}

		if ($CoinList[$coin] == 'market') {
			$coin_price = M('Market')->where(array('name' => $coin . '_cny'))->getField('new_price');
		}
		else {
			$coin_price = $CoinList[$coin];
		}

		if (!$coin_price) {
			$this->error('当前币种价格错误！');
		}

		$mum = round(($Shop['price'] * $num) / $coin_price, 6);

		if (!$mum) {
			$this->error('购买总额错误');
		}

		$usercoin = M('UserCoin')->where(array('userid' => userid()))->getField($coin);

		if ($usercoin < $mum) {
			$this->error('可用' . C('coin')[$coin]['title'] . '余额不足');
		}

		$shopaddr = M('ShopAddr')->where(array('userid' => userid()))->find();

		if (!$shopaddr) {
			$this->error('收货地址没有认证');
		}

		$mo = M();
		$mo->execute('set autocommit=0');
		$mo->execute('lock tables movesay_user_coin write,movesay_shop write,movesay_shop_log write');
		$rs = array();
		$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => userid()))->setDec($coin, $mum);
		$rs[] = $mo->table('movesay_shop')->where(array('id' => $Shop['id']))->save(array(
	'deal' => array('exp', 'deal+' . $num),
	'num'  => array('exp', 'num-' . $num)
	));

		if (($Shop['num'] - $num) <= 0) {
			$rs[] = $mo->table('movesay_shop')->where(array('id' => $Shop['id']))->save(array('status' => 0));
		}

		$rs[] = $mo->table('movesay_shop_log')->add(array('userid' => userid(), 'shopid' => $shop['id'], 'price' => $Shop['price'], 'coinname' => $coin, 'num' => $num, 'mum' => $mum, 'addr' => $shopaddr['truename'] . '|' . $shopaddr['moble'] . '|' . $shopaddr['name'], 'addtime' => time(), 'status' => 0));

		if (check_arr($rs)) {
			$mo->execute('commit');
			$mo->execute('unlock tables');
			$this->success('购买成功！');
		}
		else {
			$mo->execute('rollback');
			$this->error('购买失败！');
		}
	}

	public function setaddress($truename, $moble, $name)
	{
		if (!userid()) {
			redirect('/#login');
		}

		if (!check($truename, 'truename')) {
			$this->error('收货人姓名格式错误');
		}

		if (!check($moble, 'moble')) {
			$this->error('收货人电话格式错误');
		}

		if (!check($name, 'a')) {
			$this->error('收货地址格式错误');
		}

		$ShopAddr = M('ShopAddr')->where(array('userid' => userid()))->find();

		if ($ShopAddr) {
			$rs = M('ShopAddr')->where(array('userid' => userid()))->save(array('truename' => $truename, 'moble' => $moble, 'name' => $name));
		}
		else {
			$rs = M('ShopAddr')->add(array('userid' => userid(), 'truename' => $truename, 'moble' => $moble, 'name' => $name));
		}

		if ($rs) {
			$this->success('提交成功');
		}
		else {
			$this->error('提交失败');
		}
	}
}

?>
