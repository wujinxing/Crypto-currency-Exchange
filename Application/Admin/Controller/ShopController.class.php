<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Admin\Controller;

class ShopController extends AdminController
{
	public function index()
	{
		$this->name = trim($_GET['name']);

		if ($this->name) {
			$where = array(
				'name'   => array('like', '%' . $this->name . '%'),
				'status' => array('egt', 0)
				);
		}
		else {
			$where = array(
				'status' => array('egt', 0)
				);
		}

		$count = M('Shop')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Shop')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['type'] = M('ShopType')->where(array('name' => $v['type']))->getField('title');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function log()
	{
		$this->name = trim($_GET['name']);

		if ($this->name) {
			$where = array(
				'name'   => array('like', '%' . $this->name . '%'),
				'status' => array('egt', 0)
				);
		}
		else {
			$where = array(
				'status' => array('egt', 0)
				);
		}

		$count = M('ShopLog')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('ShopLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
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

			if (M('Config')->where(array('id' => 1))->save($_POST)) {
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

	public function type()
	{
		$this->name = trim($_GET['name']);

		if ($this->name) {
			$where = array(
				'name'   => array('like', '%' . $this->name . '%'),
				'status' => array('egt', 0)
				);
		}
		else {
			$where = array(
				'status' => array('egt', 0)
				);
		}

		$count = M('ShopType')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('ShopType')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function images()
	{
		$baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/shop/';
		$upload->autoSub = false;
		$info = $upload->upload();

		if ($info) {
			$data = array('url' => str_replace('./', '/', $upload->rootPath) . $info['imgFile']['savename'], 'error' => 0);
			exit(json_encode($data));
		}
		else {
			$error['error'] = 1;
			$error['message'] = $upload->getError();
			exit(json_encode($error));
		}
	}

	public function edit()
	{
		if (IS_POST) {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if (IS_POST) {
				$upload = new \Think\Upload();
				$upload->maxSize = 3145728;
				$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
				$upload->rootPath = './Upload/shop/';
				$upload->autoSub = false;
				$info = $upload->upload();

				if ($info) {
					foreach ($info as $k => $v) {
						$_POST[$v['key']] = $v['savename'];
					}
				}

				$_POST['addtime'] = time();

				foreach ($_POST['coinlistarr'] as $k => $v) {
					$_POST['coinlist'] .= $v . ',';
				}

				if ($_POST['id']) {
					$rs = M('Shop')->save($_POST);
				}
				else {
					$rs = M('Shop')->add($_POST);
				}

				if ($rs) {
					$this->success('操作成功！');
				}
				else {
					$this->error('操作失败！');
				}
			}
		}
		else {
			$ShopType = M('ShopType')->select();
			$shoptypelsit = array();

			foreach ($ShopType as $k => $v) {
				$shoptypelsit[$v['name']] = $v['title'];
			}

			$this->assign('shoptype', $shoptypelsit);

			if (empty($_GET['id'])) {
				$this->data = false;
			}
			else {
				$this->data = M('Shop')->where(array('id' => trim($_GET['id'])))->find();
			}

			$this->display();
		}
	}

	public function edit_type()
	{
		if (IS_POST) {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if (IS_POST) {
				if ($_POST['id']) {
					$rs = M('ShopType')->save($_POST);
				}
				else if (M('ShopType')->where(array('name' => $_POST['name']))->find()) {
					$this->error('类型标识存在！');
				}
				else {
					$rs = M('ShopType')->add($_POST);
				}

				if ($rs) {
					$this->success('操作成功！');
				}
				else {
					$this->error('操作失败！');
				}
			}
		}
		else {
			if (empty($_GET['id'])) {
				$this->data = false;
			}
			else {
				$this->data = M('ShopType')->where(array('id' => trim($_GET['id'])))->find();
			}

			$this->display();
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
			$data = array('status' => -1);
			break;

		default:
			$this->error('参数非法');
		}

		if (M('Shop')->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function log_status()
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

		case 'fahuo':
			$data = array('status' => 2);
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		default:
			$this->error('参数非法');
		}

		if (M('ShopLog')->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
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

		case 'delete':
			if (M('ShopType')->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('参数非法');
		}

		if (M('ShopType')->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}
}

?>
