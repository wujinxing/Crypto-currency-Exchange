<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Home\Controller;

class ArticleController extends HomeController
{
	public function index()
	{
		$ArticleType = M('ArticleType')->where(array('status' => 1))->order('sort asc ,id desc')->select();

		foreach ($ArticleType as $k => $v) {
			$ArticleTypeList[$v['name']] = $v;
		}

		$this->assign('ArticleTypeList', $ArticleTypeList);
		$input = I('get.');

		if (!check($input['type'], 'w')) {
			$input['type'] = $ArticleType[0]['name'];
		}

		if (!$ArticleTypeList[$input['type']]) {
			$input['type'] = $ArticleType[0]['name'];
		}

		$where = array('type' => $input['type']);
		$this->assign('type', $where['type']);
		$Model = M('Article');
		$count = $Model->where($where)->count();
		$Page = new \Think\Page($count, 10);
		$show = $Page->show();
		$list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function detail()
	{
		$ArticleType = (APP_DEBUG ? null : S('ArticleType'));

		if (!$ArticleType) {
			$ArticleType = M('ArticleType')->where(array('status' => 1))->order('sort asc ,id desc')->select();
		}

		foreach ($ArticleType as $k => $v) {
			$ArticleTypeList[$v['name']] = $v;
		}

		$this->assign('ArticleTypeList', $ArticleTypeList);
		$input = I('get.');
		$id = (is_numeric($input['id']) ? trim($input['id']) : 1);
		$data = M('Article')->where(array('id' => $id))->find();
		$this->assign('data', $data);
		$this->assign('type', $data['type']);
		$this->display();
	}
}

?>
