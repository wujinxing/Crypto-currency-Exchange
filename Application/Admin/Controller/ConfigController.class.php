<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Admin\Controller;

class ConfigController extends AdminController
{
	private $Model;

	public function __construct()
	{
		parent::__construct();
		$this->Model = M('Config');
		$this->Title = '系统配置';
	}

	public function index()
	{
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
		$upload->rootPath = './Upload/public/';
		$upload->autoSub = false;
		$info = $upload->upload();

		if ($info) {
			foreach ($info as $k => $v) {
				$_POST[$v['key']] = $v['savename'];
			}
		}

		$_POST['addtime'] = time();

		if ($this->Model->where(array('id' => 1))->save($_POST)) {
			$this->success('修改成功！');
		}
		else {
			$this->error('修改失败');
		}
	}

	public function moble()
	{
		$this->display();
	}

	public function qita()
	{
		$this->display();
	}

	public function contact()
	{
		$this->display();
	}

	public function mycz()
	{
		$this->display();
	}

	public function mytx()
	{
		$this->display();
	}
}

?>
