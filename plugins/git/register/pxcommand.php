<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Plugin "git"
 */
class pxplugin_git_register_pxcommand extends px_bases_pxcommand{

	/**
	 * コンストラクタ
	 * @param $command = PXコマンド配列
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->px = $px;

		$this->homepage();
	}

	/**
	 * ホームページを表示する。
	 */
	private function homepage(){
		$command = $this->get_command();
		$obj = $this->px->get_plugin_object('git');
		$gitHelper = $obj->factory_gitHelper();

		$src = '';
		$src .= '<h2>git log</h2>';
		$gitlog = $gitHelper->get_log();
		$src .= '<p>'.count($gitlog).' commits.</p>'."\n";
		if(count($gitlog)){
			$src .= '<dl style="max-height:16em; overflow:auto; padding-right:1em;">'."\n";
			foreach( $gitlog as $gitlog_row ){
				$src .= '<dt class="large" style="font-weight:bold; margin:1.5em 0 0.5em 0;">'.t::h($gitlog_row['subject']).'</dt>'."\n";
				$src .= '	<dd class="small">commit: '.t::h($gitlog_row['commit']).'</dd>'."\n";
				$src .= '	<dd class="small">author: '.t::h($gitlog_row['author']).'</dd>'."\n";
				$src .= '	<dd class="small">date: '.t::h($gitlog_row['date']).'</dd>'."\n";
				if( strlen(trim($gitlog_row['description'])) ){
					$src .= '	<dd><div style="padding:0.5em 1em; background-color:#f9f9f9; border:1px solid #aaaaaa;">'.t::text2html($gitlog_row['description']).'</div></dd>'."\n";
				}
			}
			$src .= '</dl>'."\n";
		}

		$src .= '<h2>git status</h2>';
		$src .= t::text2html($gitHelper->get_status());

		$src .= '<hr />'."\n";
		$src .= '<p>git enabled: '.($gitHelper->is_enabled_git_command()?'true':'false').'</p>'."\n";

		print $this->html_template($src);
		exit;
	}

}

?>