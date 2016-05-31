<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Admin\Controller;

class TradeController extends AdminController
{
	private $Model;

	public function __construct()
	{
		parent::__construct();
		$this->Model = M('Trade');
		$this->Title = '委托记录';
	}

	public function index($name = NULL, $status = NULL, $market = NULL)
	{
		if ($name) {
			$where['userid'] = get_user($name, 'id', 'username');
		}

		if ($status) {
			$where['status'] = trim($status - 1);
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
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
}

?>
