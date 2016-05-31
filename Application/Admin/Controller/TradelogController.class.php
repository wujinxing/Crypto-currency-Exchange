<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Admin\Controller;

class TradelogController extends AdminController
{
	private $Model;

	public function __construct()
	{
		parent::__construct();
		$this->Model = M('TradeLog');
		$this->Title = '交易记录';
	}

	public function index($name = NULL, $market = NULL)
	{
		if ($name) {
			$where = array('userid|peerid' => get_user($name, 'id', 'username'));
		}
		else {
			$where = array();
		}

		if ($market) {
			$where['market'] = trim($market);
		}

		$count = $this->Model->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
			$list[$k]['peername'] = M('User')->where(array('id' => $v['peerid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
}

?>
