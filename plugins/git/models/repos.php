<?php

/**
 * PX Plugin "git"
 */
class pxplugin_git_models_repos{

	private $px;
	private $obj;

	private $repo_list = array();

	/**
	 * コンストラクタ
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct( $px ){
		$this->px = $px;
		$this->obj = $this->px->get_plugin_object('git');
		$this->load();

		// $this->obj->get_path_ramdata();
		// test::var_dump($this->repo_list);
	}

	/**
	 * リポジトリを選択する
	 */
	public function select($path){
		if( is_null($this->repo_list[$path]) ){
			return false;
		}
		$this->px->req()->set_session('plugins.git.selected_repository', $path);
		return $this->repo_list[$path];
	}

	/**
	 * 選択したリポジトリ情報を取得する
	 */
	public function get_selected_repo_info(){
		$path = $this->px->req()->get_session('plugins.git.selected_repository');
		return $this->repo_list[$path];
	}

	/**
	 * リポジトリ一覧を読み込む
	 */
	public function load(){
		$this->repo_list = array();
		$csv = $this->px->dbh()->read_csv_utf8( $this->obj->get_path_ramdata().'repo_list.csv' );
		foreach( $csv as $csv_row ){
			$tmp_ary = array();
			$tmp_ary['path'] = $csv_row[0];
			$tmp_ary['name'] = $csv_row[1];
			$this->repo_list[$tmp_ary['path']] = $tmp_ary;
		}
		// asort($this->repo_list);
		return true;
	}

	/**
	 * 現在のリポジトリ一覧を保存する
	 */
	public function save(){
		$src = $this->px->dbh()->mk_csv_utf8($this->repo_list);
		$result = $this->dbh()->file_overwrite( $this->obj->get_path_ramdata().'repo_list.csv', $src );
		return $result;
	}

}

?>
