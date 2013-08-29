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
		$this->local_sitemap[ ':'                 ] = array( 'title'=>'git'               );
		$this->local_sitemap[ ':rev'              ] = array( 'title'=>'リビジョン'        );
		$this->local_sitemap[ ':repos'            ] = array( 'title'=>'リポジトリ一覧'    );
		$this->local_sitemap[ ':repo_select'      ] = array( 'title'=>'リポジトリ選択'    );

		switch( $command[2] ){
			case 'rev':
				$fin = $this->page_revision(); break;
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

		$src .= '<h2>git status</h2>';
		$src .= t::text2html($gitHelper->get_status());

		$src .= '<h2>git log</h2>';
		$gitlog = $gitHelper->get_log();
		$src .= '<p>'.count($gitlog).' commits.</p>'."\n";
		if(count($gitlog)){
			$src .= '<dl style="max-height:16em; overflow:auto; padding-right:1em;">'."\n";
			foreach( $gitlog as $gitlog_row ){
				$src .= '<dt class="large" style="font-weight:bold; margin:1.5em 0 0.5em 0;">'.t::h($gitlog_row['subject']).'</dt>'."\n";
				$src .= '	<dd class="small">commit: <a href="'.t::h($this->href(':rev.'.$gitlog_row['commit'])).'">'.t::h($gitlog_row['commit']).'</a></dd>'."\n";
				$src .= '	<dd class="small">author: '.t::h($gitlog_row['author']).'</dd>'."\n";
				$src .= '	<dd class="small">date: '.t::h($gitlog_row['date']).'</dd>'."\n";
				if( strlen(trim($gitlog_row['description'])) ){
					$src .= '	<dd><div style="padding:0.5em 1em; background-color:#f9f9f9; border:1px solid #aaaaaa;">'.t::text2html($gitlog_row['description']).'</div></dd>'."\n";
				}
			}
			$src .= '</dl>'."\n";
		}

		$src .= '<h2>git tag</h2>';
		$src .= t::text2html($gitHelper->get_tag());

		$src .= '<h2>git branch</h2>';
		$src .= t::text2html($gitHelper->get_branch());

		$src .= '<hr />'."\n";
		$src .= '<p>'.$this->mk_link(':repos').'</p>'."\n";
		$src .= '<p>git enabled: '.($gitHelper->is_enabled_git_command()?'true':'false').'</p>'."\n";

		return $src;
	}

	/**
	 * リビジョン情報を表示する。
	 */
	private function page_revision(){
		$command = $this->get_command();
		$obj = $this->px->get_plugin_object('git');
		$obj_repo = $obj->factory_repos();
		$cur_repo = $obj_repo->get_selected_repo_info();
		$gitHelper = $obj->factory_gitHelper( $cur_repo );

		$revision = $command[3];

		$src = '';
		$src .= '<p>revision: '.t::h($revision).'</p>'."\n";

		$revision_info = $gitHelper->get_revision_info($revision);
		$src .= '<ul>';
		$src .= '<li>commit: '.t::h($revision_info['commit']).'</li>';
		$src .= '<li>author: '.t::h($revision_info['author']).'</li>';
		$src .= '<li>date: '.t::h($revision_info['date']).'</li>';
		$src .= '<li>subject: '.t::h($revision_info['subject']).'</li>';
		$src .= '<li>description: '.t::text2html($revision_info['description']).'</li>';
		$src .= '<li>diff: '.t::text2html($revision_info['diff']).'</li>';
		$src .= '</ul>';

		return $src;
	}

	/**
	 * リポジトリリストページ
	 */
	private function page_repos(){
		$this->set_title('リポジトリリスト');
		$obj = $this->px->get_plugin_object('git');
		$dao_repos = $obj->factory_repos();

		$css = '';
		$css .= '<style type="text/css">'."\n";
		$css .= '.contents .cont_repoform{'."\n";
		$css .= '}'."\n";
		$css .= '.contents .cont_repoform dl,'."\n";
		$css .= '.contents .cont_repoform dl dt,'."\n";
		$css .= '.contents .cont_repoform dl dd{'."\n";
		$css .= '	display:inline-block;'."\n";
		$css .= '	list-style-type:none;'."\n";
		$css .= '	padding:0;'."\n";
		$css .= '	margin:0;'."\n";
		$css .= '}'."\n";
		$css .= '.contents .cont_repoform dl dt{'."\n";
		$css .= '	padding:0 1em 0 0;'."\n";
		$css .= '}'."\n";
		$css .= '.contents .cont_repoform dl dd{'."\n";
		$css .= '	padding:0 1em 0 0;'."\n";
		$css .= '}'."\n";
		$css .= '</style>'."\n";

		$fin = '';
		$fin .= $css;
		$fin .= '<h2>ローカルリポジトリを設定</h2>'."\n";
		$fin .= '<div class="unit cont_repoform">'."\n";
		$fin .= '<form action="'.t::h($this->href(':repo_select')).'" method="post" class="inline">'."\n";
		$fin .= '<dl>'."\n";
		$fin .= '<dt>path:</dt><dd><input type="text" name="path" value="" style="width:300px; " /></dd>'."\n";
		$fin .= '<dt>name:</dt><dd><input type="text" name="name" value="" style="width:120px; " /></dd>'."\n";
		$fin .= '</dl>'."\n";
		$fin .= '<input type="submit" value="設定する" />'."\n";
		$fin .= '<input type="hidden" name="mode" value="execute" />'."\n";
		$fin .= '</form>'."\n";
		$fin .= '</div><!-- / .cont_repoform -->'."\n";
		$fin .= ''."\n";

		$repos = $dao_repos->get_repo_list();
		if( count($repos) ){
			$fin .= '<h2>履歴から選ぶ</h2>'."\n";
			$fin .= '<table class="def" style="width:100%;">'."\n";
			$fin .= '	<thead>'."\n";
			$fin .= '		<tr>'."\n";
			$fin .= '			<th>パス</th>'."\n";
			$fin .= '			<th>名前</th>'."\n";
			$fin .= '			<th></th>'."\n";
			$fin .= '		</tr>'."\n";
			$fin .= '	</thead>'."\n";
			$fin .= '	<tbody>'."\n";
			foreach( $repos as $row ){
				$fin .= '		<tr>'."\n";
				$fin .= '			<th><a href="'.t::h( $this->href(':repo_select').'&path='.urlencode($row['path']).'&name='.urlencode($row['name']).'&mode=execute').'">'.t::h($row['path']).'</a></th>'."\n";
				$fin .= '			<td>'.t::h($row['name']).'</td>'."\n";
				$fin .= '			<td>{$string}</td>'."\n";
				$fin .= '		</tr>'."\n";
			}
			$fin .= '	</tbody>'."\n";
			$fin .= '</table><!-- /table.def -->'."\n";
			$fin .= ''."\n";

		}


		return $fin;
	}

	// ----------------------------------------------------------------------------

	/**
	 * リポジトリ登録
	 */
	private function page_repo_select(){
		$this->set_title('リポジトリ選択');
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
		$RTN .= '	<p class="center"><input type="submit" value="設定する" /></p>'."\n";
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