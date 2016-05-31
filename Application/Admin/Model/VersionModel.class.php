<?php
//dezend by http://www.yunlu99.com/ QQ:270656184
namespace Admin\Model;

class VersionModel extends \Think\Model
{
	protected $versionPath = './Database/version.ini';

	public function checkUpdate()
	{
		$result = S('admin_update');

		if ($result === false) {
			if ($this->getNextVersion() == '') {
				$result = 0;
			}
			else {
				$result = 1;
			}

			S('admin_update', $result, 600);
		}

		return $result;
	}

	public function cleanCheckUpdateCache()
	{
		S('admin_update', null);
	}

	public function getCurrentVersion()
	{
		$version = file_get_contents($this->versionPath);
		$this->refreshVersions();
		$version = $this->where(array('name' => $version))->find();
		return $version;
	}

	public function setCurrentVersion($name)
	{
		return file_put_contents($this->versionPath, $name);
	}

	public function refreshVersions()
	{
		$content = file_get_contents('http://auth.movesay.com/Appstore/Update');
		$versions = json_decode($content, true);

		foreach ($versions as $key => $v) {
			$version = $this->where(array('name' => $v['name']))->find();

			if (!$version) {
				$this->add(array('title' => $v['title'], 'create_time' => $v['addtime'], 'log' => $v['log'], 'url' => $v['url'], 'number' => $v['id'], 'name' => $v['name']));
			}
			else {
				$this->save(array('title' => $v['title'], 'create_time' => $v['addtime'], 'log' => $v['log'], 'url' => $v['url'], 'number' => $v['id'], 'name' => $v['name']));
			}
		}

		$this->where(array(
	'name' => array('not in', getSubByKey($versions, 'name'))
	))->delete();
	}

	public function getNextVersion()
	{
		$versions = $this->order('number asc')->select();
		$currentVersion = $this->getCurrentVersion();

		foreach ($versions as $v) {
			if (version_compare($v['name'], $currentVersion['name']) == 1) {
				return $v;
			}
		}

		return '';
	}
}

?>
