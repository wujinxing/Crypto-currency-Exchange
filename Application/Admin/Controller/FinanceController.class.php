<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Admin\Controller;

class FinanceController extends AdminController
{
	public function index()
	{
		if (isset($_GET['name']) && $_GET['name']) {
			if (check($_GET['name'], 'username')) {
				$user = M('User')->where(array('username' => $_GET['name']))->find();

				if ($user) {
					$this->name = $_GET['name'];
					$where['userid'] = $user['id'];
				}
				else if (M('Mycz')->where(array('tradeno' => $_GET['name']))->find()) {
					$this->name = $_GET['name'];
					$where['tradeno'] = trim($_GET['name']);
				}
			}
		}
		else {
			if (isset($_GET['status']) && ($_GET['status'] != -2)) {
				$this->status = $where['status'] = trim($_GET['status']);
			}
		}

		$count = M('Mycz')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$Page->parameter .= 'status=' . $this->status . '&name=' . $this->name . '&';
		$show = $Page->show();
		$list = M('Mycz')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function config()
	{
		if (IS_POST) {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$input = I('post.');

			if (M('Config')->where(array('id' => 1))->save($input)) {
				$this->success('修改成功！');
			}
			else {
				$this->error('修改失败');
			}
		}
		else {
			$this->display();
		}
	}

	public function status()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		$id = $_GET['id'];

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$mycz = M('Mycz')->where(array('id' => $id))->find();

		if ($mycz['status'] == 1) {
			$this->error('已经处理，禁止再次操作！');
		}

		$mo = M();
		$mo->execute('set autocommit=0');
		$mo->execute('lock tables movesay_user_coin write,movesay_mycz write');
		$rs = array();
		$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $mycz['userid']))->setInc('cny', $mycz['num']);
		$rs[] = $mo->table('movesay_mycz')->where(array('id' => $mycz['id']))->setField('status', 1);

		if (check_arr($rs)) {
			$mo->execute('commit');
			$mo->execute('unlock tables');
			$this->success('操作成功！');
		}
		else {
			$mo->execute('rollback');
			$this->error(APP_DEBUG ? implode('|', $rs) : '操作失败！');
		}
	}

	public function type()
	{
		$count = M('MyczType')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$Page->parameter .= 'status=' . $this->status . '&name=' . $this->name . '&';
		$show = $Page->show();
		$list = M('MyczType')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function type_status()
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

		default:
			$this->error('参数非法');
		}

		if (M('MyczType')->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}
}

?>
