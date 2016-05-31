<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Home\Controller;

class MoneyController extends HomeController
{
	public function __construct()
	{
		parent::__construct();
		exit();

		if (!userid()) {
			redirect('/#login');
		}
	}

	public function install()
	{
		$tables = M()->query('show tables');
		$tableMap = array();

		foreach ($tables as $table) {
			$tableMap[reset($table)] = 1;
		}

		if (!isset($tableMap['movesay_money'])) {
			M()->execute("CREATE TABLE `movesay_money` (\r\n\t\t\t\t\t\t  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,\r\n\t\t\t\t\t\t  `name` varchar(50) NOT NULL,\r\n\t\t\t\t\t\t  `coinname` varchar(50) NOT NULL,\r\n\t\t\t\t\t\t  `type` varchar(4) NOT NULL DEFAULT '1',\r\n\t\t\t\t\t\t  `num` bigint(20) unsigned NOT NULL DEFAULT '0',\r\n\t\t\t\t\t\t  `deal` int(11) unsigned NOT NULL DEFAULT '0',\r\n\t\t\t\t\t\t  `danwei` varchar(6) DEFAULT NULL,\r\n\t\t\t\t\t\t  `step` varchar(255) DEFAULT NULL,\r\n\t\t\t\t\t\t  `tian` int(11) unsigned NOT NULL,\r\n\t\t\t\t\t\t  `fee` int(11) unsigned NOT NULL,\r\n\t\t\t\t\t\t  `feecoin` varchar(50) DEFAULT NULL,\r\n\t\t\t\t\t\t  `outfee` int(11) unsigned NOT NULL,\r\n\t\t\t\t\t\t  `sort` int(11) unsigned NOT NULL,\r\n\t\t\t\t\t\t  `lasttime` int(11) DEFAULT NULL,\r\n\t\t\t\t\t\t  `addtime` int(11) unsigned NOT NULL,\r\n\t\t\t\t\t\t  `endtime` int(11) unsigned NOT NULL,\r\n\t\t\t\t\t\t  `status` int(4) NOT NULL,\r\n\t\t\t\t\t\t  PRIMARY KEY (`id`),\r\n\t\t\t\t\t\t  KEY `status` (`status`),\r\n\t\t\t\t\t\t  KEY `name` (`name`)\r\n\t\t\t\t\t\t) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8");
		}
		else {
			echo 'movesay_money is found!<br>';
		}

		if (!isset($tableMap['movesay_money_dlog'])) {
			M()->execute("CREATE TABLE `movesay_money_dlog` (\r\n\t\t\t\t  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,\r\n\t\t\t\t  `userid` int(11) unsigned NOT NULL,\r\n\t\t\t\t  `money_id` int(11) NOT NULL,\r\n\t\t\t\t  `type` tinyint(4) NOT NULL,\r\n\t\t\t\t  `num` int(6) NOT NULL,\r\n\t\t\t\t  `content` varchar(255) NOT NULL,\r\n\t\t\t\t  `addtime` int(11) unsigned NOT NULL,\r\n\t\t\t\t  PRIMARY KEY (`id`),\r\n\t\t\t\t  KEY `userid` (`userid`)\r\n\t\t\t\t) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT=''");
		}
		else {
			echo 'movesay_money_dlog is found!<br>';
		}

		if (!isset($tableMap['movesay_money_log'])) {
			M()->execute("CREATE TABLE `movesay_money_log` (\r\n\t\t\t\t  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,\r\n\t\t\t\t  `userid` int(11) unsigned NOT NULL,\r\n\t\t\t\t  `money_id` int(11) NOT NULL,\r\n\t\t\t\t  `allfee` decimal(20,8) NOT NULL,\r\n\t\t\t\t  `times` int(6) NOT NULL,\r\n\t\t\t\t  `num` int(11) unsigned NOT NULL,\r\n\t\t\t\t  `sort` int(11) unsigned NOT NULL,\r\n\t\t\t\t  `chktime` int(11) DEFAULT NULL,\r\n\t\t\t\t  `addtime` int(11) unsigned NOT NULL,\r\n\t\t\t\t  `status` int(4) NOT NULL,\r\n\t\t\t\t  PRIMARY KEY (`id`),\r\n\t\t\t\t  KEY `userid` (`userid`),\r\n\t\t\t\t  KEY `status` (`status`)\r\n\t\t\t\t) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT=''");
		}
		else {
			echo 'movesay_money_log is found!<br>';
		}

		if (!M('menu')->where(array('url' => 'Money/Index'))->find()) {
			$falg = M()->execute("INSERT INTO `movesay_menu` (`title`, `pid`, `sort`, `url`,`group`,`ico_name`)\r\n\t\t\t\tVALUES ('理财管理', '10', '1', 'Money/Index','理财管理','time')");

			if ($falg) {
				echo '理财管理菜单添加成功<br>';
			}
			else {
				echo '理财管理菜单添加失败' . M()->getLastSql() . '<br>';
			}
		}
		else {
			echo '后台理财管理菜单已添加过<br>';
		}

		if (!M('menu')->where(array('url' => 'Money/Log'))->find()) {
			$falg = M()->execute("INSERT INTO `movesay_menu` (`title`, `pid`, `hide`,\r\n `sort`, `url`,`group`,`ico_name`)\r\n\t\t\t\tVALUES ('理财日志', '10','1', '2', 'Money/Log','理财管理','time')");

			if ($falg) {
				echo '理财管理菜单添加成功<br>';
			}
			else {
				echo '理财管理菜单添加失败' . M()->getLastSql() . '<br>';
			}
		}
		else {
			echo '后台理财管理菜单已添加过<br>';
		}

		if (!M('menu')->where(array('url' => 'Money/Dlog'))->find()) {
			$falg = M()->execute("INSERT INTO `movesay_menu` (`title`,`pid`, `hide`,`sort`, `url`,`group`,`ico_name`)\r\n\t\t\t\tVALUES ('理财详细日志', '10', '1','3', 'Money/Dlog','理财管理','time')");

			if ($falg) {
				echo '理财管理菜单添加成功<br>';
			}
			else {
				echo '理财管理菜单添加失败' . M()->getLastSql() . '<br>';
			}
		}
		else {
			echo '后台理财管理菜单已添加过<br>';
		}
	}

	public function queue()
	{
		$br = (IS_CLI ? "\n" : '<br>');
		echo IS_CLI ? '' : '<pre>';
		echo 'start money queue:' . $br;
		$MoneyList = M('Money')->where(array('status' => 1))->select();

		foreach ($MoneyList as $money) {
			if ($money['endtime'] < $money['lasttime']) {
				echo 'end ok ' . $br;
				D('Money')->save(array('id' => $money['id'], 'status' => 0));
				D('MoneyLog')->save(array('money_id' => $money['id'], 'status' => 0));
				continue;
			}

			echo (($money['lasttime'] + $money['step']) - time()) . ' s' . $br;

			if (($money['lasttime'] + $money['step']) < time()) {
				echo 'start ' . $money['name'] . ':' . $br;
				$mo = M();
				$MoneyLogList = M('MoneyLog')->where(array('money_id' => $money['id'], 'status' => 1))->select();

				foreach ($MoneyLogList as $MoneyLog) {
					if ($MoneyLog['chktime'] == $money['lasttime']) {
						continue;
					}

					$mo->execute('set autocommit=0');
					$mo->execute('lock tables movesay_user_coin write,movesay_money_log  write,movesay_money_dlog  write');
					$rs = array();
					$fee = round(($money['fee'] * $MoneyLog['num']) / 100, 8);
					echo 'update ' . $MoneyLog['userid'] . ' coin ' . $br;
					$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $MoneyLog['userid']))->setInc($money['feecoin'], $fee);
					echo 'update ' . $MoneyLog['userid'] . ' log ' . $br;
					$MoneyLog['allfee'] = round($MoneyLog['allfee'] + $fee, 8);
					$MoneyLog['times'] = $MoneyLog['times'] + 1;
					$MoneyLog['chktime'] = $money['lasttime'];
					$rs[] = $mo->table('movesay_money_log')->save($MoneyLog);
					echo 'add ' . $MoneyLog['userid'] . ' dlog ' . $br;
					$rs[] = $mo->table('movesay_money_dlog')->add(array('userid' => $MoneyLog['userid'], 'money_id' => $money['id'], 'type' => 1, 'num' => $fee, 'addtime' => time(), 'content' => '本金:' . $money['coinname'] . ' :' . $MoneyLog['num'] . '个,获取理财利息' . $money['feecoin'] . ' ' . $fee . '个'));

					if (check_arr($rs)) {
						$mo->execute('commit');
						$mo->execute('unlock tables');
						echo 'commit ok ' . $br;
					}
					else {
						$mo->execute('rollback');
						echo 'rollback ' . $br;
					}
				}

				if (D('Money')->where(array('id' => $money['id']))->setField('lasttime', time())) {
					echo 'update money last time ok' . $br;
				}
				else {
					echo 'update money last time fail!!!!!!!!!!!!!!!!!!!!!! ' . $br;
				}
			}
		}
	}

	public function index()
	{
		if (IS_POST) {
			$input = I('post.');

			if (!check($input['num'], 'd')) {
				$this->error('理财数量格式错误！');
			}

			$money_min = (C('money_min') ? C('money_min') : 100);
			$money_max = (C('money_max') ? C('money_max') : 10000000);
			$money_bei = (C('money_bei') ? C('money_bei') : 100);

			if ($input['num'] < $money_min) {
				$this->error('理财数量超过系统最小限制！');
			}

			if ($money_max < $input['num']) {
				$this->error('理财数量超过系统最大限制！');
			}

			if (($input['num'] % $money_bei) != 0) {
				$this->error('每次理财数量必须是' . $money_bei . '的整倍数！');
			}

			if (!check($input['paypassword'], 'password')) {
				$this->error('交易密码格式错误！');
			}

			$user = M('User')->where(array('id' => userid()))->find();

			if (md5($input['paypassword']) != $user['paypassword']) {
				$this->error('交易密码错误！');
			}

			$money = M('Money')->where(array('id' => $input['id']))->find();
			debug($money, '$money');

			if (!$money) {
				$this->error('当前理财错误！');
			}

			if (!$money['status']) {
				$this->error('当前理财已经禁用！');
			}

			if (($money['num'] - $money['deal']) < $input['num']) {
				$this->error('系统剩余量不足！');
			}

			$userCoin = M('UserCoin')->where(array('id' => userid()))->find();

			if ($userCoin[$money['coinname']] < $input['num']) {
				debug($userCoin[$money['coinname']], 'yue');
				$this->error('可用余额不足');
			}

			$mo = M();
			$mo->execute('set autocommit=0');
			$mo->execute('lock tables movesay_user_coin write  , movesay_money_log  write ');
			$rs = array();
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => $user['id']))->setDec($money['coinname'], $input['num']);
			$rs[] = $mo->table('movesay_money_log')->add(array('userid' => $user['id'], 'money_id' => $money['id'], 'num' => $input['num'], 'addtime' => time(), 'status' => 1));

			if (check_arr($rs)) {
				$mo->execute('commit');
				$mo->execute('unlock tables');
				$this->success('购买成功！');
			}
			else {
				$mo->execute('rollback');
				$this->error(APP_DEBUG ? implode('|', $rs) : '购买失败!');
			}
		}
		else {
			$input = I('get.');
			$coin = C('Coin');
			$where = array();
			if (isset($input['coinname']) && isset($coin[$input['coinname']])) {
				$this->assign('coinname', $input['coinname']);
				$where['coinname'] = $input['coinname'];
			}

			$where['status'] = 1;
			if (isset($input['type']) && in_array($input['type'], array(1, 2))) {
				$this->assign('type', $input['type']);
				$where['type'] = $input['type'];
			}

			$count = M('Money')->where($where)->count();
			$Page = new \Think\Page($count, 10);
			$show = $Page->show();
			$list = M('Money')->where($where)->order('sort desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

			foreach ($list as $k => $v) {
				$list[$k]['times'] = M('MoneyLog')->where(array('coinname' => $v['coinname']))->count();
				$list[$k]['nums'] = M('MoneyLog')->where(array('coinname' => $v['coinname']))->sum('num');
				$list[$k]['bili'] = round($list[$k]['nums'] / $v['num'], 2) * 100;
				$list[$k]['shengyu'] = number_format($v['num'] - $v['deal']);
				$list[$k]['shen'] = (round($v['ci'] - $v['unlock'], 2) * $v['num']) / $v['ci'];
				$list[$k]['tian'] = $list[$k]['tian'] . ' ' . $this->danweitostr($list[$k]['danwei']);
			}

			$coin_type = array();
			$group = M()->query('select * from movesay_money group by coinname');

			foreach ($group as $type) {
				if (isset($coin[$type['coinname']]['title'])) {
					$coin_type[$type['coinname']] = $coin[$type['coinname']]['title'];
				}
			}

			$this->assign('coin_type', $coin_type);
			$this->assign('list', $list);
			$this->assign('page', $show);
			$this->display();
		}
	}

	public function info($id)
	{
		$id = intval($id);
		$ret = array();

		if (!$id) {
			$this->error('参数错误');
		}

		$Money = M('Money')->where(array('id' => $id))->find();
		$UserCoin = M('UserCoin')->where(array('userid' => userid()))->find();
		$ret['Money'] = array_merge($Money, array('yue' => $UserCoin[$Money['coinname']]));
		$this->success($ret);
	}

	public function beforeGet($id)
	{
		$id = intval($id);
		$MoneyLog = M('MoneyLog')->where(array('userid' => userid(), 'id' => $id, 'status' => 1))->find();

		if (!$MoneyLog) {
			$this->error('参数错误');
		}

		$Money = M('Money')->where(array('id' => $MoneyLog['money_id']))->find();

		if (!$Money) {
			$this->error('参数错误');
		}

		$num = $MoneyLog['num'];
		$fee = ($Money['outfee'] ? round(($MoneyLog['num'] * $Money['outfee']) / 100, 8) : 0);
		$mo = M();
		$mo->execute('set autocommit=0');
		$mo->execute('lock tables movesay_user_coin write  , movesay_money_log  write,movesay_money_dlog  write');
		$rs = array();

		if ($Money['coinname'] != $Money['feecoin']) {
			$user_coin = $mo->table('movesay_user_coin')->where(array('userid' => userid()))->find();

			if (!isset($user_coin[$Money['feecoin']])) {
				$this->error('利息币种不存在,请联系管理员');
			}

			if ($user_coin[$Money['feecoin']] < $fee) {
				$this->error('您的' . $Money['feecoin'] . '不够取现手续费(' . $fee . ')');
			}

			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => userid()))->setDec($Money['feecoin'], $fee);
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => userid()))->setInc($Money['coinname'], $num);
		}
		else {
			$rs[] = $mo->table('movesay_user_coin')->where(array('userid' => userid()))->setInc($Money['coinname'], round($num - $fee, 8));
		}

		$rs[] = $mo->table('movesay_money_log')->where($MoneyLog)->setField('status', 0);
		$rs[] = $mo->table('movesay_money_dlog')->add(array('userid' => userid(), 'money_id' => $Money['id'], 'type' => 2, 'num' => $fee, 'addtime' => time(), 'content' => '提前抽取' . $Money['title'] . ' 理财本金' . $Money['coinname'] . ' ' . $MoneyLog['num'] . '个,扣除利息' . $Money['feecoin'] . ': ' . $fee . '个'));

		if (check_arr($rs)) {
			$mo->execute('commit');
			$mo->execute('unlock tables');
			$this->success('操作成功！');
		}
		else {
			$mo->execute('rollback');
			$this->error(APP_DEBUG ? implode('|', $rs) : '操作失败!');
		}
	}

	public function log()
	{
		$input = I('get.');
		$where['userid'] = userid();
		$count = M('MoneyLog')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('MoneyLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['money'] = M('Money')->where(array('id' => $v['money_id']))->find();
			$list[$k]['money']['tian'] = $list[$k]['money']['tian'] . ' ' . $this->danweitostr($list[$k]['money']['danwei']);
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function Dlog()
	{
		$input = I('get.');
		$where['userid'] = userid();
		$count = M('MoneyDlog')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('MoneyDlog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['money'] = M('Money')->where(array('id' => $v['money_id']))->find();
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	private function danweitostr($danwei)
	{
		switch ($danwei) {
		case 'y':
			return '年';
			break;

		case 'm':
			return '月';
			break;

		case 'd':
			return '天';
			break;

		case 'h':
			return '小时';
			break;

		default:
		case 'i':
			return '分钟';
			break;
		}
	}
}

?>
