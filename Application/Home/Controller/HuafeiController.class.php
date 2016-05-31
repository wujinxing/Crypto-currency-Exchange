<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Home\Controller;

class HuafeiController extends HomeController
{
	public function __construct()
	{
		parent::__construct();
		exit();
	}

	public function index()
	{
		if (IS_POST) {
			$input = I('post.');

			if (!check($input['num'], 'd')) {
				$this->error('充值数量格式错误！');
			}

			if ($input['num'] != 10) {
				if ($input['num'] != 50) {
					if ($input['num'] != 100) {
						if ($input['num'] != 300) {
							$this->error('充值数量错误！');
						}
					}
				}
			}

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

			if (!check($input['type'], 'w')) {
				$this->error('付款方式格式错误！');
			}

			$coin = M('Coin')->where(array('name' => $input['type']))->find();

			if (!$coin) {
				$this->error('付款方式错误！');
			}

			if (!$coin['huafei']) {
				$this->error('付款方式没有开通！');
			}

			if ($input['type'] == 'cny') {
				$new_price = 1;
			}
			else {
				$new_price = C('coin')[$input['type']]['huafeifee'];
			}

			if (!$new_price) {
				$this->error('当前付款方式数据错误！');
			}

			$num = round($input['num'], 0);

			if (!$num) {
				$this->error('充值数量错误111！');
			}

			$xuyao = round($num / $new_price, 2);

			if (!$xuyao) {
				$this->error('购买成交价错误！');
			}

			if ($user['coin'][$input['type']] < $xuyao) {
				$this->error('您的' . $input['type'] . '余额不足');
			}

			$xianzhi = M('Huafei')->where(array(
	'userid'  => $user['id'],
	'status'  => 1,
	'addtime' => array('gt', time() - (60 * 60 * 24 * 30))
	))->find();

			if ($xianzhi) {
				$this->error('30天内 你已充值过一次!请下个月再充值');
			}

			$moble = $user['moble'];
			$tradeno = 'hf' . tradeno();
			$mo = M();
			$mo->execute('set autocommit=0');
			$mo->execute('lock tables movesay_user_coin write,movesay_huafei write');
			$rs = array();
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $user['id']))->setDec($input['type'], $xuyao);
			$rs[] = $mo->table('movesay_huafei')->add(array('userid' => $user['id'], 'moble' => $user['moble'], 'num' => $num, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 1));

			if (check_arr($rs)) {
				$mo->execute('commit');
				$mo->execute('unlock tables');
				$aa = huafei_cz($num, $moble, $tradeno);

				if ($aa == 1) {
					M('Huafei')->where(array('tradeno' => $tradeno))->setField('status', 1);
					$this->success('充值成功！');
				}
				else {
					M('UserCoin')->where(array('userid' => $user['id']))->setInc($input['type'], $xuyao);
					$this->error('充值失败！' . $aa);
				}
			}
			else {
				$mo->execute('rollback');
				$this->error('充值订单创建失败！');
			}
		}
		else {
			$user = $this->User();
			$coin = M('Coin')->where(array('status' => 1, 'huafei' => 1))->select();

			foreach ($coin as $k => $v) {
				$coin_list[$v['name']]['name'] = $v['title'];
				$coin_list[$v['name']]['price'] = $v['huafeifee'];
			}

			$this->assign('coin_list', $coin_list);
			$this->display();
		}
	}

	public function log()
	{
		$user = $this->User();
		$input = I('get.');
		$where['status'] = array('egt', 0);
		$where['userid'] = $user['id'];
		$Model = M('Huafei');
		$count = $Model->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
}

?>
