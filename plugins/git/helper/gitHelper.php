<?php

/**
 * PX Plugin "git"
 */
class pxplugin_git_helper_gitHelper{

	private $px;
	private $path_rep;

	/**
	 * コンストラクタ
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct( $px, $path_rep ){
		$this->px = $px;
		$this->path_rep = $path_rep;
	}

	/**
	 * gitコマンドのパス
	 */
	public function path_git(){
		return $this->px->get_conf('commands.git');
	}

	/**
	 * gitコマンドが利用可能か調べる
	 */
	public function is_enabled_git_command(){
		if(!strlen($this->path_git())){
			return false;
		}
		if(!is_executable($this->path_git())){
			return false;
		}
		return true;
	}

	/**
	 * ステータスを取得する
	 */
	public function get_status(){
		$status = $this->cmd_git('status');
		return $status;
	}

	/**
	 * タグの一覧を取得する
	 */
	public function get_tag(){
		$rtn = $this->cmd_git('tag');
		return $rtn;
	}

	/**
	 * ブランチの一覧を取得する
	 */
	public function get_branch(){
		$rtn = $this->cmd_git('branch');
		return $rtn;
	}

	/**
	 * ログを取得する
	 */
	public function get_log(){
		$log = $this->cmd_git('log');

		$rtn = array();
		$current_revision = array();
		$header_flg = false;
		foreach( preg_split('/\r\n|\r|\n/', $log) as $row ){
			if( $header_flg === true && !strlen($row) ){
				$header_flg = false;
				continue;
			}elseif( $header_flg === true ){
				if( preg_match('/^Author\: (.+)$/s', $row, $matches) ){
					$current_revision['author'] = trim($matches[1]);
				}elseif( preg_match('/^Date\: (.+)$/s', $row, $matches) ){
					$current_revision['date'] = trim($matches[1]);
				}
				continue;
			}
			if( $header_flg === false && preg_match('/^commit ([a-zA-Z0-9]+)$/s', $row, $matches) ){
				if( strlen($current_revision['commit']) ){
					array_push($rtn, $current_revision);
				}
				$current_revision = array(
					'commit'=>$matches[1],
					'author'=>'',
					'date'=>'',
					'subject'=>'',
					'description'=>'',
				);
				$header_flg = true;
			}elseif( $header_flg === false){
				if( preg_match('/^    (.+)$/s', $row, $matches) ){
					if(!strlen($current_revision['subject'])){
						$current_revision['subject'] .= trim($matches[1]);
					}else{
						$current_revision['description'] .= $matches[1]."\n";
					}
				}
				// $current_revision['description'] .= $row."\n";
			}

		}
		if( $current_revision !== array() ){
			array_push($rtn, $current_revision);
		}

		return $rtn;
	}

	/**
	 * コマンドを実行する
	 */
	private function cmd_git( $cmd ){
		$path_current_dir = realpath('.');
		if(strlen($this->path_rep) && is_dir($this->path_rep.'/.git/')){
			@chdir($this->path_rep);
		}

		$cmd = $this->path_git().' '.$cmd;
		ob_start();
		passthru($cmd);
		$std_out = ob_get_clean();

		@chdir($path_current_dir);
		return $std_out;
	}//cmd_git()

}

?>
