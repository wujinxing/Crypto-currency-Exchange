<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Home\Controller;

class IndexController extends HomeController
{
	public function check($id = '', $returnurl = '')
	{
		$this->assign('id', $id);
		$this->assign('returnurl', $returnurl);
		$this->display();
	}

	public function checkdo($id = '')
	{
		if (msCheckAuthDo($id)) {
			echo 1;
		}
		else {
			echo 0;
		}
	}

	public function up()
	{
		$config = M('Config')->getDbFields();
		$aa = 0;
		$trade_moshi = 0;
		$trade_hangqing = 0;

		foreach ($config as $k => $v) {
			if ($v == 'index_html') {
				$aa = 1;
			}

			if ($v == 'trade_hangqing') {
				$trade_hangqing = 1;
			}

			if ($v == 'trade_moshi') {
				$trade_moshi = 1;
			}
		}

		if (!$aa) {
			echo M()->execute('ALTER TABLE `movesay_config` ADD COLUMN `index_html` VARCHAR(50) NULL AFTER `status`;');
		}

		if (!$trade_hangqing) {
			echo M()->execute('ALTER TABLE `movesay_config` ADD COLUMN `trade_hangqing` VARCHAR(50) NULL AFTER `status`;');
		}

		if (!$trade_moshi) {
			echo M()->execute('ALTER TABLE `movesay_config` ADD COLUMN `trade_moshi` VARCHAR(50) NULL AFTER `status`;');
		}

		if (!M('menu')->where(array('url' => 'Index/coin'))->find()) {
			$falg = M()->execute('INSERT INTO `movesay_menu` (`title`, `pid`, `sort`, `url`,`group`,`ico_name`) VALUES (\'币种统计\', \'1\', \'1\', \'Index/coin\',\'首页\',\'time\')');

			if ($falg) {
				echo '后台队列菜单添加成功<br>';
			}
			else {
				echo '后台队列菜单添加失败' . M()->getLastSql() . '<br>';
			}
		}
		else {
			echo '后台队列菜单已添加过<br>';
		}

		if (!M('menu')->where(array('url' => 'Index/market'))->find()) {
			$falg = M()->execute('INSERT INTO `movesay_menu` (`title`, `pid`, `sort`, `url`,`group`,`ico_name`) VALUES (\'市场统计\', \'1\', \'1\', \'Index/market\',\'首页\',\'time\')');

			if ($falg) {
				echo '后台队列菜单添加成功<br>';
			}
			else {
				echo '后台队列菜单添加失败' . M()->getLastSql() . '<br>';
			}
		}
		else {
			echo '后台队列菜单已添加过<br>';
		}

		if (!M('menu')->where(array('url' => 'Tools/queue'))->find()) {
			$falg = M()->execute('INSERT INTO `movesay_menu` (`title`, `pid`, `sort`, `url`,`group`,`ico_name`) VALUES (\'队列状态\', \'9\', \'5\', \'Tools/queue\',\'其他\',\'time\')');

			if ($falg) {
				echo '后台队列菜单添加成功<br>';
			}
			else {
				echo '后台队列菜单添加失败' . M()->getLastSql() . '<br>';
			}
		}
		else {
			echo '后台队列菜单已添加过<br>';
		}

		if (!M('menu')->where(array('url' => 'Tools/qianbao'))->find()) {
			$falg = M()->execute('INSERT INTO `movesay_menu` (`title`, `pid`, `sort`, `url`,`group`,`ico_name`) VALUES (\'钱包检查\', \'9\', \'5\', \'Tools/qianbao\',\'其他\',\'time\')');

			if ($falg) {
				echo '后台钱包检查菜单添加成功<br>';
			}
			else {
				echo '后台钱包检查菜单添加失败' . M()->getLastSql() . '<br>';
			}
		}
		else {
			echo '后台钱包检查已添加过<br>';
		}

		$tables = M()->query('show tables');
		$tableMap = array();

		foreach ($tables as $table) {
			$tableMap[reset($table)] = 1;
		}

		if (!isset($tableMap['movesay_coin_json'])) {
			M()->execute("\r\n\r\n\t\t\tCREATE TABLE `movesay_coin_json` (\r\n\t`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\r\n\t`name` VARCHAR(100) NOT NULL COLLATE 'utf8_unicode_ci',\r\n\t`data` VARCHAR(500) NOT NULL COLLATE 'utf8_unicode_ci',\r\n\t`type` VARCHAR(100) NOT NULL COLLATE 'utf8_unicode_ci',\r\n\t`sort` INT(11) UNSIGNED NOT NULL,\r\n\t`addtime` INT(11) UNSIGNED NOT NULL,\r\n\t`endtime` INT(11) UNSIGNED NOT NULL,\r\n\t`status` INT(4) NOT NULL,\r\n\tPRIMARY KEY (`id`),\r\n\tINDEX `status` (`status`)\r\n)\r\nCOLLATE='utf8_unicode_ci'\r\nENGINE=MyISAM\r\nAUTO_INCREMENT=3\r\n;\r\n\r\n\r\n");
		}

		if (!isset($tableMap['movesay_myzc_fee'])) {
			M()->execute("\r\n\r\nCREATE TABLE `movesay_myzc_fee` (\r\n\t`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\r\n\t`userid` INT(11) UNSIGNED NOT NULL,\r\n\t`username` VARCHAR(200) NOT NULL COLLATE 'utf8_unicode_ci',\r\n\t`coinname` VARCHAR(200) NOT NULL COLLATE 'utf8_unicode_ci',\r\n\t`txid` VARCHAR(200) NOT NULL COLLATE 'utf8_unicode_ci',\r\n\t`type` VARCHAR(200) NOT NULL COLLATE 'utf8_unicode_ci',\r\n\t`fee` DECIMAL(20,8) NOT NULL,\r\n\t`num` DECIMAL(20,8) UNSIGNED NOT NULL,\r\n\t`mum` DECIMAL(20,8) UNSIGNED NOT NULL,\r\n\t`sort` INT(11) UNSIGNED NOT NULL,\r\n\t`addtime` INT(11) UNSIGNED NOT NULL,\r\n\t`endtime` INT(11) UNSIGNED NOT NULL,\r\n\t`status` INT(4) NOT NULL,\r\n\tPRIMARY KEY (`id`),\r\n\tINDEX `status` (`status`)\r\n)\r\nCOLLATE='utf8_unicode_ci'\r\nENGINE=InnoDB\r\n;\r\n\r\n\r\n");
		}

		if (!isset($tableMap['movesay_market_json'])) {
			M()->execute("\r\n\r\nCREATE TABLE `movesay_market_json` (\r\n\t`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,\r\n\t`name` VARCHAR(100) NOT NULL COLLATE 'utf8_unicode_ci',\r\n\t`data` VARCHAR(500) NOT NULL COLLATE 'utf8_unicode_ci',\r\n\t`type` VARCHAR(100) NOT NULL COLLATE 'utf8_unicode_ci',\r\n\t`sort` INT(11) UNSIGNED NOT NULL,\r\n\t`addtime` INT(11) UNSIGNED NOT NULL,\r\n\t`endtime` INT(11) UNSIGNED NOT NULL,\r\n\t`status` INT(4) NOT NULL,\r\n\tPRIMARY KEY (`id`),\r\n\tINDEX `status` (`status`)\r\n)\r\nCOLLATE='utf8_unicode_ci'\r\nENGINE=MyISAM\r\nAUTO_INCREMENT=4\r\n;\r\n\r\n\r\n");
		}
	}

	public function index()
	{
		$indexAdver = (APP_DEBUG ? null : S('index_indexAdver'));

		if (!$indexAdver) {
			$indexAdver = M('Adver')->where(array('status' => 1))->order('id asc')->select();
			S('index_indexAdver', $indexAdver);
		}

		$this->assign('indexAdver', $indexAdver);
		$indexArticleType = (APP_DEBUG ? null : S('index_indexArticleType'));

		if (!$indexArticleType) {
			$indexArticleType = M('ArticleType')->where(array('status' => 1, 'index' => 1))->order('sort asc ,id desc')->limit(3)->select();
			S('index_indexArticleType', $indexArticleType);
		}

		$this->assign('indexArticleType', $indexArticleType);
		$indexArticle = (APP_DEBUG ? null : S('index_indexArticle'));

		if (!$indexArticle) {
			foreach ($indexArticleType as $k => $v) {
				$indexArticle[$k] = M('Article')->where(array('type' => $v['name'], 'status' => 1))->order('id desc')->limit(6)->select();
			}

			S('index_indexArticle', $indexArticle);
		}

		$this->assign('indexArticle', $indexArticle);
		$indexLink = (APP_DEBUG ? null : S('index_indexLink'));

		if (!$indexLink) {
			$indexLink = M('Link')->where(array('status' => 1))->order('sort asc ,id desc')->select();
		}

		$this->assign('indexLink', $indexLink);

		if (C('index_html')) {
			$this->display(C('index_html'));
		}
		else {
			$this->display();
		}
	}
}

?>
