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
	}//__construct()

	/**
	 * リポジトリを選択する
	 */
	public function select($path, $name = null){
		$target = null;
		foreach( $this->repo_list as $num=>$row ){
			if( $row['path'] == $path ){
				$target = $row;
				if( strlen($name) ){
					$target['name'] = $name;
				}
				unset($this->repo_list[$num]);
				break;
			}
		}

		if( !is_array($target) ){
			$target = array();
			$target['path'] = $path;
			$target['name'] = $name;
		}
		array_unshift($this->repo_list, $target);
		$this->save();

		return $this->get_selected_repo_info();
	}//select()

	/**
	 * 選択したリポジトリ情報を取得する
	 */
	public function get_selected_repo_info(){
		return $this->repo_list[0];
	}//get_selected_repo_info()

	/**
	 * リポジトリ情報の一覧を取得する
	 */
	public function get_repo_list(){
		return $this->repo_list;
	}//get_repo_list()

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
			array_push( $this->repo_list, $tmp_ary );
		}
		// asort($this->repo_list);
		return true;
	}

	/**
	 * 現在のリポジトリ一覧を保存する
	 */
	public function save(){
		$src = $this->px->dbh()->mk_csv_utf8($this->repo_list);
		$result = $this->px->dbh()->file_overwrite( $this->obj->get_path_ramdata().'repo_list.csv', $src );
		return $result;
	}

}

?>
