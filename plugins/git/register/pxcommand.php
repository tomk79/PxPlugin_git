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
		$src .= '<p>activility: '.($gitHelper->is_enabled_git_command()?'true':'false').'</p>'."\n";

		$src .= '<h2>git log</h2>';
		$gitlog = $gitHelper->get_log();
		$src .= '<p>'.count($gitlog).' commits.</p>'."\n";
		if(count($gitlog)){
			$src .= '<dl>'."\n";
			foreach( $gitlog as $gitlog_row ){
				$src .= '<dt class="large">'.t::h($gitlog_row['subject']).'</dt>'."\n";
				$src .= '	<dd class="small">commit: '.t::h($gitlog_row['commit']).'</dd>'."\n";
				$src .= '	<dd class="small">author: '.t::h($gitlog_row['author']).'</dd>'."\n";
				$src .= '	<dd class="small">date: '.t::h($gitlog_row['date']).'</dd>'."\n";
				$src .= '	<dd>'.t::text2html($gitlog_row['description']).'</dd>'."\n";
			}
			$src .= '</dl>'."\n";
		}

		$src .= '<h2>git status</h2>';
		$src .= t::text2html($gitHelper->get_status());

		print $this->html_template($src);
		exit;
	}

}

?>