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
	 * PHPExcelHelper を生成する
	 */
	public function factory_gitHelper(){
		$tmp_class_name = $this->px->load_px_plugin_class('/'.$this->plugin_name.'/helper/gitHelper.php');
		if(!$tmp_class_name){
			$this->px->error()->error_log('FAILED to load "gitHelper.php".', __FILE__, __LINE__);
			return false;
		}
		$gitHelper = new $tmp_class_name($this->px);
		return $gitHelper;
	}

}

?>