<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Home\Controller;

class PayController extends HomeController
{
	public function index()
	{
		if (IS_POST) {
			if (isset($_POST['alipay'])) {
				$arr = explode('--', $_POST['alipay']);

				if (md5('movesay') != $arr[2]) {
					echo -1;
					exit();
				}

				if (!strstr($arr[0], 'Pay')) {
				}

				$arr[0] = trim(str_replace(PHP_EOL, '', $arr[0]));
				$arr[1] = trim(str_replace(PHP_EOL, '', $arr[1]));

				if (strstr($arr[0], '付款-')) {
					$arr[0] = str_replace('付款-', '', $arr[0]);
				}

				$mycz = M('Mycz')->where(array('tradeno' => $arr[0]))->find();

				if (!$mycz) {
					echo -3;
					exit();
				}

				if ($mycz['status']) {
					echo -4;
					exit();
				}

				if ($mycz['num'] != $arr[1]) {
					echo -5;
					exit();
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
					echo 1;
					exit();
				}
				else {
					$mo->execute('rollback');
					$mo->query('rollback');
					echo -6;
					exit();
				}
			}
		}
	}

	public function mycz()
	{
		$id = $_GET['id'];

		if (check($id, 'd')) {
			$mycz = M('Mycz')->where(array('id' => $id))->find();

			if ($mycz['type'] == 'bank') {
				$UserBankType = M('UserBankType')->where(array('status' => 1))->order('id desc')->select();
				$this->assign('UserBankType', $UserBankType);
			}

			$this->assign('mycz', $mycz);
			$this->display($mycz['type']);
		}
		else {
			$this->redirect('Finance/mycz');
		}
	}

	public function movepay()
	{
		if (IS_POST) {
			$movepay = $_POST['movepay'];
			$tradeno = $_POST['tradeno'];
			$num = $_POST['num'];
			$status = $_POST['status'];

			if (md5('movesay') != $movepay) {
				echo -1;
				exit();
			}

			$mycz = M('Mycz')->where(array('tradeno' => $tradeno))->find();

			if (!$mycz) {
				echo -2;
				exit();
			}

			if ($mycz['status']) {
				echo -3;
				exit();
			}

			if ($mycz['num'] != $num) {
				echo -4;
				exit();
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
				$this->redirect('Mycz/log');
				exit();
			}
			else {
				$mo->execute('rollback');
				$mo->query('rollback');
				echo -5;
				exit();
			}
		}
	}
}

?>
