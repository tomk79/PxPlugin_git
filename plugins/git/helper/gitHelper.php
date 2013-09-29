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
	public function get_tags(){
		$rtn = $this->cmd_git('tag');
		$rtn = trim($rtn);
		if(!strlen($rtn)){ return array(); }
		$rtn = preg_split( '/(?:\r\n|\r|\n)+/', $rtn );
		return $rtn;
	}

	/**
	 * タグのリビジョン番号を取得する
	 */
	public function get_rev_parse($tag_name){
		$rtn = $this->cmd_git('rev-parse '.$tag_name);
		return trim($rtn);
	}

	/**
	 * リビジョンの情報を得る
	 */
	public function get_commit_info($rev){
		$log = $this->cmd_git('log -n 1 -p '.$rev);

		$rtn = array();
		$current_revision = array();
		$status = 0;
		foreach( preg_split('/\r\n|\r|\n/', $log) as $row ){
			if( $status === 1 && !strlen($row) ){
				$status = 0;
				continue;
			}elseif( $status === 1 ){
				if( preg_match('/^Author\: (.+)$/s', $row, $matches) ){
					$current_revision['author'] = trim($matches[1]);
				}elseif( preg_match('/^Date\: (.+)$/s', $row, $matches) ){
					$current_revision['date'] = trim($matches[1]);
				}
				continue;
			}
			if( $status === 2 ){
				$current_revision['diff'] .= $row."\n";
				continue;
			}
			if( $status === 0 && preg_match('/^commit ([a-zA-Z0-9]+)$/s', $row, $matches) ){
				if( strlen($current_revision['commit']) ){
					array_push($rtn, $current_revision);
				}
				$current_revision = array(
					'commit'=>$matches[1],
					'author'=>'',
					'date'=>'',
					'subject'=>'',
					'description'=>'',
					'diff'=>'',
				);
				$status = 1;
			}elseif( $status === 0){
				if( preg_match('/^    (.+)$/s', $row, $matches) ){
					if(!strlen($current_revision['subject'])){
						$current_revision['subject'] .= trim($matches[1]);
					}else{
						$current_revision['description'] .= $matches[1]."\n";
					}
				}elseif(!strlen($row)){
					$status = 2;
					continue;
				}
			}

		}
		if( $current_revision !== array() ){
			array_push($rtn, $current_revision);
		}
		return $rtn[0];
	}

	/**
	 * ブランチの一覧を取得する
	 */
	public function get_branches(){
		$rtn = $this->cmd_git('branch');
		$rtn = preg_split( '/(?:\r\n|\r|\n)+/', trim($rtn) );
		foreach( $rtn as $key=>$val ){
			$rtn[$key] = trim( preg_replace('/^\*\s+/', '', $val) );
		}
		return $rtn;
	}

	/**
	 * 現在のブランチ名を取得する
	 */
	public function get_current_branch(){
		$rtn = $this->cmd_git('branch');
		$rtn = preg_split( '/(?:\r\n|\r|\n)+/', trim($rtn) );
		foreach( $rtn as $key=>$val ){
			if( preg_match('/^\*\s+/', $val) ){
				return preg_replace('/^\*\s+/', '', $val);
			}
		}
		return null;
	}

	/**
	 * チェックアウトする
	 */
	public function checkout( $branch ){
		if( !strlen($branch) ){
			return false;
		}
		$rtn = $this->cmd_git( 'checkout "'.t::escape_doublequote($branch).'"' );

		if( $this->get_current_branch() != $branch ){
			return false;
		}
		return true;
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
