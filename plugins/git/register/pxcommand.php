<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Plugin "git"
 */
class pxplugin_git_register_pxcommand extends px_bases_pxcommand{

	private $local_sitemap = array();// ページ名等を定義する

	/**
	 * コンストラクタ
	 * @param $command = PXコマンド配列
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->px = $px;

		$this->local_sitemap = array();
		$this->local_sitemap[ ':'                                           ] = array( 'title'=>'git'                                );
		$this->local_sitemap[ ':repos'                                      ] = array( 'title'=>'リポジトリ一覧'                     );
		$this->local_sitemap[ ':repo_select'                                ] = array( 'title'=>'リポジトリ選択'                     );

		switch( $command[2] ){
			case 'repos':
				$fin = $this->page_repos(); break;
			case 'repo_select':
				$fin = $this->page_repo_select(); break;
			default:
				$fin = $this->page_home(); break;
		}
		print $this->html_template($fin);
		exit;
	}


	/**
	 * コンテンツ内へのリンク先を調整する。
	 */
	private function href( $linkto = null ){
		if(is_null($linkto)){
			return '?PX='.implode('.',$this->pxcommand_name);
		}
		if($linkto == ':'){
			return '?PX=plugins.git';
		}
		$rtn = preg_replace('/^\:/','?PX=plugins.git.',$linkto);

		$rtn = $this->px->theme()->href( $rtn );
		return $rtn;
	}

	/**
	 * コンテンツ内へのリンクを生成する。
	 */
	private function mk_link( $linkto , $options = array() ){
		if( !strlen($options['label']) ){
			if( $this->local_sitemap[$linkto] ){
				$options['label'] = $this->local_sitemap[$linkto]['title'];
			}
		}
		$rtn = $this->href($linkto);

		$rtn = $this->px->theme()->mk_link( $rtn , $options );
		return $rtn;
	}

	// ----------------------------------------------------------------------------

	/**
	 * ホームページを表示する。
	 */
	private function page_home(){
		$command = $this->get_command();
		$obj = $this->px->get_plugin_object('git');
		$obj_repo = $obj->factory_repos();
		$cur_repo = $obj_repo->get_selected_repo_info();
		$gitHelper = $obj->factory_gitHelper( $cur_repo );

		$src = '';
		$src .= '<p>path: '.t::h($cur_repo['path']).'</p>'."\n";

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
		$src .= '<p>'.$this->mk_link(':repos').'</p>'."\n";
		$src .= '<p>git enabled: '.($gitHelper->is_enabled_git_command()?'true':'false').'</p>'."\n";

		return $src;
	}

	/**
	 * リポジトリリストページ
	 */
	private function page_repos(){
		$obj = $this->px->get_plugin_object('git');
		$dao_repos = $obj->factory_repos();

		$fin = '';

		$fin .= '<form action="'.t::h($this->href(':repo_select')).'" method="post" class="inline">'."\n";
		$fin .= '<ul>'."\n";
		$fin .= '<li>path: <input type="text" name="path" value="" /></li>'."\n";
		$fin .= '<li>name: <input type="text" name="name" value="" /></li>'."\n";
		$fin .= '</ul>'."\n";
		$fin .= '<input type="submit" value="選択" />'."\n";
		$fin .= '<input type="hidden" name="mode" value="execute" />'."\n";
		$fin .= '</form>'."\n";
		$fin .= ''."\n";

		return $fin;
	}

	// ----------------------------------------------------------------------------

	/**
	 * リポジトリ登録
	 */
	private function page_repo_select(){
		$error = $this->page_repo_select_check();
		if( $this->px->req()->get_param('mode') == 'thanks' ){
			return	$this->page_repo_select_thanks();
		}elseif( $this->px->req()->get_param('mode') == 'execute' && !count( $error ) ){
			return	$this->page_repo_select_execute();
		}elseif( !strlen( $this->px->req()->get_param('mode') ) ){
			$error = array();
		}
		return	$this->page_repo_select_input( $error );
	}
	/**
	 * リポジトリ登録：入力
	 */
	private function page_repo_select_input( $error ){
		$RTN = ''."\n";

		$RTN .= '<p>'."\n";
		$RTN .= '	プロジェクトの情報を入力して、「確認する」ボタンをクリックしてください。<span class="must">必須</span>印の項目は必ず入力してください。<br />'."\n";
		$RTN .= '</p>'."\n";
		if( is_array( $error ) && count( $error ) ){
			$RTN .= '<p class="error">'."\n";
			$RTN .= '	入力エラーを検出しました。画面の指示に従って修正してください。<br />'."\n";
			$RTN .= '</p>'."\n";
		}
		$RTN .= '<form action="'.t::h( $this->href() ).'" method="post">'."\n";
		$RTN .= '<table style="width:100%;" class="form_elements">'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>パス <span class="must">必須</span></div></th>'."\n";
		$RTN .= '		<td style="width:70%;">'."\n";
		$RTN .= '			<div><input type="text" name="path" value="'.t::h( $this->px->req()->get_param('path') ).'" style="width:80%;" /></div>'."\n";
		if( strlen( $error['path'] ) ){
			$RTN .= '			<div class="error">'.$error['path'].'</div>'."\n";
		}
		$RTN .= '		</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '	<tr>'."\n";
		$RTN .= '		<th style="width:30%;"><div>名前</div></th>'."\n";
		$RTN .= '		<td style="width:70%;">'."\n";
		$RTN .= '			<div><input type="text" name="name" value="'.t::h( $this->px->req()->get_param('name') ).'" style="width:80%;" /></div>'."\n";
		if( strlen( $error['name'] ) ){
			$RTN .= '			<div class="error">'.$error['name'].'</div>'."\n";
		}
		$RTN .= '		</td>'."\n";
		$RTN .= '	</tr>'."\n";
		$RTN .= '</table>'."\n";
		$RTN .= '	<div class="center"><input type="submit" value="選択する" /></div>'."\n";
		$RTN .= '	<input type="hidden" name="mode" value="execute" />'."\n";
		$RTN .= '</form>'."\n";
		return	$RTN;
	}
	/**
	 * リポジトリ登録：チェック
	 */
	private function page_repo_select_check(){
		$RTN = array();

		if( !strlen($this->px->req()->get_param('path')) ){
			$RTN['path'] = 'パスは必ず入力してください。';
		}
		if( !strlen($this->px->req()->get_param('name')) ){
		}

		return	$RTN;
	}
	/**
	 * リポジトリ登録：実行
	 */
	private function page_repo_select_execute(){
		$obj = $this->px->get_plugin_object('git');
		$dao_repos = $obj->factory_repos();
		$dao_repos->select( $this->px->req()->get_param('path') );
		return $this->px->redirect( $this->href().'&mode=thanks' );
	}
	/**
	 * リポジトリ登録：完了
	 */
	private function page_repo_select_thanks(){
		// $RTN = ''."\n";
		// $RTN .= '<p>プロジェクト編集処理を完了しました。</p>'."\n";
		// $backTo = ':';
		// $RTN .= '<form action="'.htmlspecialchars( $this->href( $backTo ) ).'" method="post">'."\n";
		// $RTN .= '	<p><input type="submit" value="戻る" /></p>'."\n";
		// $RTN .= '</form>'."\n";
		// return	$RTN;
		return $this->px->redirect( $this->href(':') );
	}




}

?>