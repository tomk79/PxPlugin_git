<?php

/**
 * PX Plugin "git"
 */
class pxplugin_git_register_object{
	private $px;
	private $plugin_name = 'git';

	/**
	 * コンストラクタ
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct($px){
		$this->px = $px;
	}

	/**
	 * gitHelper を生成する
	 */
	public function factory_gitHelper( $repo_info ){
		$tmp_class_name = $this->px->load_px_plugin_class('/'.$this->plugin_name.'/helper/gitHelper.php');
		if(!$tmp_class_name){
			$this->px->error()->error_log('FAILED to load "gitHelper.php".', __FILE__, __LINE__);
			return false;
		}
		$gitHelper = new $tmp_class_name($this->px, $repo_info['path']);
		return $gitHelper;
	}

	/**
	 * repos を生成する
	 */
	public function factory_repos(){
		$tmp_class_name = $this->px->load_px_plugin_class('/'.$this->plugin_name.'/models/repos.php');
		if(!$tmp_class_name){
			$this->px->error()->error_log('FAILED to load "repos.php".', __FILE__, __LINE__);
			return false;
		}
		$gitHelper = new $tmp_class_name($this->px);
		return $gitHelper;
	}

	/**
	 * ramデータディレクトリのパスを得る
	 */
	public function get_path_ramdata(){
		$rtn = $this->px->get_conf('paths.px_dir').'_sys/ramdata/plugins/git/';
		$this->px->dbh()->mkdir_all($rtn);
		return $rtn;
	}

}

?>