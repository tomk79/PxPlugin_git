<?php

/**
 * PX Plugin "git"
 */
class pxplugin_git_helper_gitHelper{

	private $px;

	/**
	 * コンストラクタ
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct( $px ){
		$this->px = $px;
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
		$cmd = $this->path_git().' status';
		ob_start();
		passthru($cmd);
		$status = ob_get_clean();
		return $status;
	}

	/**
	 * ログを取得する
	 */
	public function get_log(){
		$cmd = $this->path_git().' log';
		ob_start();
		passthru($cmd);
		$log = ob_get_clean();

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

}

?>
