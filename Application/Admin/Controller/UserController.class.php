<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Admin\Controller;

class UserController extends AdminController
{
	private $Model;

	public function __construct()
	{
		parent::__construct();
		$this->Model = M('User');
		$this->Title = '用户管理';
	}

	public function index($name = NULL, $field = NULL, $status = NULL)
	{
		if ($status) {
			$where['status'] = trim($status - 1);
		}

		if ($name && $field) {
			$where[$field] = $name;
		}

		$count = $this->Model->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = $this->Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['invit_1'] = $this->Model->where(array('id' => $v['invit_1']))->getField('username');
			$list[$k]['invit_2'] = $this->Model->where(array('id' => $v['invit_2']))->getField('username');
			$list[$k]['invit_3'] = $this->Model->where(array('id' => $v['invit_3']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function edit($id = NULL)
	{
		if (empty($id)) {
			$this->data = null;
		}
		else {
			$this->data = $this->Model->where(array('id' => trim($id)))->find();
		}

		$this->display();
	}

	public function save()
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if ($_POST['password']) {
			$_POST['password'] = md5($_POST['password']);
		}
		else {
			unset($_POST['password']);
		}

		if ($_POST['paypassword']) {
			$_POST['paypassword'] = md5($_POST['paypassword']);
		}
		else {
			unset($_POST['paypassword']);
		}

		$_POST['mobletime'] = strtotime($_POST['mobletime']);

		if ($this->Model->save($_POST)) {
			$this->success('编辑成功！');
		}
		else {
			$this->error('编辑失败！');
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

	public function setpwd()
	{
		if (IS_POST) {
			defined('APP_DEMO') || define('APP_DEMO', 0);

			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$oldpassword = $_POST['oldpassword'];
			$newpassword = $_POST['newpassword'];
			$repassword = $_POST['repassword'];

			if (!check($oldpassword, 'password')) {
				$this->error('旧密码格式错误！');
			}

			if (md5($oldpassword) != session('admin_password')) {
				$this->error('旧密码错误！');
			}

			if (!check($newpassword, 'password')) {
				$this->error('新密码格式错误！');
			}

			if ($newpassword != $repassword) {
				$this->error('确认密码错误！');
			}

			if (D('Admin')->where(array('id' => session('admin_id')))->save(array('password' => md5($newpassword)))) {
				$this->success('登陆密码修改成功！', U('Login/loginout'));
			}
			else {
				$this->error('登陆密码修改失败！');
			}
		}

		$this->display();
	}
}

?>
