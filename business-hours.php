<?php
/*
* Plugin Name: business-hours
* Plugin URI:http://www.ohba-cr.co.jp/
* Description: 営業時間をショートコードで表示する
* Version: 1.0.0
* Author: Taiga Imoto
* Update URI:http://www.ohba-cr.co.jp/
*/
?>
<?php
//メニューの追加
add_action('admin_menu', function () {
	add_menu_page(
		'営業時間追加', // ページのタイトルタグ<title>に表示されるテキスト
		'営業時間追加',   // 左メニューとして表示されるテキスト
		'manage_options',       // 必要な権限 manage_options は通常 administrator のみに与えられた権限
		'business-hours',        // 左メニューのスラッグ名 →URLのパラメータに使われる /wp-admin/admin.php?page=toriaezu_menu
		'', // メニューページを表示する際に実行される関数(サブメニューがある時はこの値は空にする)
		'dashicons-clock',       // メニューのアイコンを指定 https://developer.wordpress.org/resource/dashicons/#awards
		0                             // メニューが表示される位置のインデックス(0が先頭) 5=投稿,10=メディア,20=固定ページ,25=コメント,60=テーマ,65=プラグイン,70=ユーザー,75=ツール,80=設定
	);

	add_submenu_page(
		'business-hours',    // 親メニューのスラッグ
		'使い方', // ページのタイトルタグ<title>に表示されるテキスト
		'使い方', // サブメニューとして表示されるテキスト
		'manage_options', // 必要な権限 manage_options は通常 administrator のみに与えられた権限
		'business-hours',  // サブメニューのスラッグ名。この名前を親メニューのスラッグと同じにすると親メニューを押したときにこのサブメニューを表示します。一般的にはこの形式を採用していることが多い。
		'howtobh', //（任意）このページのコンテンツを出力するために呼び出される関数
		0
	);

	add_submenu_page(
		'business-hours',    // 親メニューのスラッグ
		'営業時間追加', // ページのタイトルタグ<title>に表示されるテキスト
		'営業時間追加', // サブメニューとして表示されるテキスト
		'manage_options', // 必要な権限 manage_options は通常 administrator のみに与えられた権限
		'add-business-hours',  // サブメニューのスラッグ名。この名前を親メニューのスラッグと同じにすると親メニューを押したときにこのサブメニューを表示します。一般的にはこの形式を採用していることが多い。
		'hours_page_contents', //（任意）このページのコンテンツを出力するために呼び出される関数
		1
	);
});

//=================================================
// 表示・更新処理
//=================================================
function howtobh()
{
	$plugindir = plugin_dir_url(__FILE__);
	//---------------------------------
	// HTML表示
	//---------------------------------
	echo <<<EOF
<div class="wrap">
	<h2>使い方</h2>
	<h3>・営業時間の追加方法</h3>
	<p><b>「営業時間追加」</b>に基本の営業時間を入力する。<br>
	休日はチェックボックスにチェックを入れる。(時間は入力不要)</p>
	<img src="{$plugindir}img/1.png" style="border:1px solid lightgray; width:300px; height:auto;">
	<p><b>「営業時間の接頭辞」</b>に営業時間の前に表示する語句を入力する。</p>
	<img src="{$plugindir}img/2.png" style="border:1px solid lightgray; width:300px; height:auto;">
	<p>表示例<br>
	<span style="background-color:white; padding:8px 15px; display:inline-block;"><b>本日の営業時間</b> 10:00〜18:00</span></p>
	<p>休日に表示したい文章を<b>「休日表示文章」</b>に入力する。</p>
	<img src="{$plugindir}img/3.png" style="border:1px solid lightgray; width:300px; height:auto;">

	<h3 style="margin-top:50px;">・臨時営業時間の追加方法</h3>
	<p>臨時の休日や営業時間の登録は<b>「臨時営業時間」</b>の<b>「新規投稿を追加」</b>から行う。</p>
	<img src="{$plugindir}img/4.png" style="border:1px solid lightgray; width:300px; height:auto;">
	<ul>
		<li>①<b>「公開」</b>タブから公開日時を臨時の表示日に設定する。</li>
		<img src="{$plugindir}img/5.png" style="border:1px solid lightgray; width:300px; height:auto;">
		<li>②休日の場合はチェックボックスにチェックを入れる。</li>
		<li>③営業時間の変更の場合は<b>「時間：」</b>に臨時営業時間を入力する。</li>
		<img src="{$plugindir}img/6.png" style="border:1px solid lightgray; width:300px; height:auto;">
    </ul>
	<h3 style="margin-top:50px;">・表示方法</h3>
	<p>
	ショートコード<br>
	[business_hours]<br>
	を表示させたい記事に挿入。
	</p>
	<ul>
        <li>接頭辞id: message_bh_prefix</li>
        <li>営業時間id: bh_time</li>
        <li>休日用文章id: message_holiday</li>
    </ul>
	<h3 style="margin-top:50px;">・困った時は・不具合等</h3>
	<p><a>imo_watch@icloud.com</a> まで</p>
</div>
EOF;
}

//メニューを開いた時に実行
function hours_page_contents()
{
	//---------------------------------
	// ユーザーが必要な権限を持つか確認
	//---------------------------------
	if (!current_user_can('manage_options')) {
		wp_die(__('この設定ページのアクセス権限がありません'));
	}

	//---------------------------------
	// 初期化
	//---------------------------------

	$days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
	$options = [];
	foreach ($days as $day) {
		$options[$day] = get_option($day, '');
		$options["check_$day"] = get_option("check_$day", '');
	}
	$options['bh_prefix'] = get_option('bh_prefix', '');
	$options['holiday'] = get_option('holiday', '');

	$message_html = "";

	//---------------------------------
	// 更新されたときの処理
	//---------------------------------
	// 更新されたときの処理
	if (isset($_POST['submit'])) {
		foreach ($days as $day) {
			if (isset($_POST[$day])) {
				update_option($day, $_POST[$day]);
			}
			$checkbox = "check_$day";
			if (isset($_POST[$checkbox])) {
				update_option($checkbox, $_POST[$checkbox]);
			} else {
				delete_option($checkbox);
			}
		}

		if (isset($_POST['bh_prefix'])) {
			update_option('bh_prefix', $_POST['bh_prefix']);
		}
		if (isset($_POST['holiday'])) {
			update_option('holiday', $_POST['holiday']);
		}

		// 更新後の再取得
		foreach ($days as $day) {
			$options[$day] = get_option($day, '');
			$options["check_$day"] = get_option("check_$day", '');
		}
		$options['bh_prefix'] = get_option('bh_prefix', '');
		$options['holiday'] = get_option('holiday', '');

		// 画面にメッセージを表示
		$message_html = '<div class="notice notice-success is-dismissible"><p>営業時間を保存しました。</p></div>';
	}

	//---------------------------------
	// HTML表示
	//---------------------------------
	echo <<<EOF
{$message_html}

<div class="wrap">
    
    <h2>営業時間を入力してください。</h2>
    <p>※休日はチェックボックスにチェックを入れてください。(時間は入力不要)</p>
    
    <form name="form1" method="post" action="">
EOF;

	foreach (['sun' => '日曜日', 'mon' => '月曜日', 'tue' => '火曜日', 'wed' => '水曜日', 'thu' => '木曜日', 'fri' => '金曜日', 'sat' => '土曜日'] as $key => $day) {
		echo <<<EOF
    <label for="{$key}">{$day}：</label>
    <input id="{$key}" type="text" name="{$key}" value="{$options[$key]}" placeholder="例)10:00~18:00">
    <input id="check_{$key}" type="checkbox" name="check_{$key}" value="checked" {$options["check_$key"]} ><br><br>
EOF;
	}

	echo <<<EOF
    <label for="bh_prefix">営業時間の接頭辞：</label>
    <input id="bh_prefix" type="text" name="bh_prefix" value="{$options['bh_prefix']}" placeholder="例)本日の営業時間"><br>
    <p style="background-color:white; padding:8px 15px; display:inline-block;">表示例： <b>本日の営業時間</b> 10:00〜18:00</p><br>
    <label for="holiday">休日表示文章：</label>
    <input id="holiday" type="text" name="holiday" value="{$options['holiday']}" placeholder="例)本日はお休みです。">
    
    <p class="submit">
        <input type="submit" name="submit" class="button-primary" value="時間を保存" />
    </p>
    </form>
    
</div>
EOF;
}

//臨時営業時間
add_action('init', 'create_rinji_post_type');
function create_rinji_post_type()
{
	//投稿時に使用できる投稿用のパーツを指定
	$supports = array();
	register_post_type(
		'rinji', // 投稿タイプ名の定義
		[
			'labels' => [
				'name' => '臨時営業時間', // 管理画面上で表示する投稿タイプ名
			],
			'public'        => true,  // カスタム投稿タイプの表示(trueにする)
			'exclude_from_search' => true,  //trueで検索結果から外す
			'has_archive'   => false, // カスタム投稿一覧(true:表示/false:非表示)
			'menu_position' => 1,     // 管理画面上での表示位置
			'show_in_rest'  => false,  // true:「Gutenberg」/ false:「ClassicEditor」
			'supports' => $supports
		]
	);
}

//臨時営業時間カスタムフィールド追加
add_action('admin_menu', 'create_rinji_custom_fields');
function create_rinji_custom_fields()
{
	add_meta_box(
		'rinji_setting', //編集画面セクションID
		'臨時営業時間設定', //編集画面セクションのタイトル
		'insert_rinji_custom_fields', //編集画面セクションにHTML出力する関数
		'rinji', //投稿タイプ名
		'normal' //編集画面セクションが表示される部分
	);
}

function insert_rinji_custom_fields()
{
	global $post;
	$jikan = get_post_meta($post->ID, 'jikan', true);
	$yasumi_check = get_post_meta($post->ID, 'yasumi', true) == "checked" ? "checked" : "";
?>
	<form method="post" action="admin.php?page=site_settings">
		<label for="jikan">時間：</label>
		<input id="jikan" type="text" name="jikan" value="<?php echo $jikan ?>"><br><br>
		<label for="yasumi">休みならここにチェック</label>
		<input id="yasumi" type="checkbox" name="yasumi" value="checked" <?php echo $yasumi_check ?>><br><br>
		<!-- 隠しフィールドを追加 -->
		<input type="hidden" name="yasumi_present" value="1">
	</form>
<?php
}

// 更新時の処理
add_action('save_post', 'save_rinji_custom_fields');

function save_rinji_custom_fields($post_id)
{
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (wp_is_post_revision($post_id)) return;

	if (isset($_POST['jikan'])) {
		update_post_meta($post_id, 'jikan', $_POST['jikan']);
	}

	// 'yasumi_present' フィールドが存在する場合のみ、'yasumi' の処理を行う
	if (isset($_POST['yasumi_present'])) {
		if (isset($_POST['yasumi'])) {
			update_post_meta($post_id, 'yasumi', $_POST['yasumi']);
		} else {
			delete_post_meta($post_id, 'yasumi');
		}
	}
}

add_action('init', 'remove_rinji_editor_support');
function remove_rinji_editor_support()
{
	remove_post_type_support('rinji', 'editor'); //本文機能を削除
}

//rinjiをnoindex
function archive_noindex()
{

	if (is_post_type_archive('rinji') || is_singular('movie')) {
		echo '<meta name="robots" content="noindex , nofollow" />';
	}
}
add_action('wp_head', 'archive_noindex');

//タイトル入力欄のプレースホルダー文言変更
function change_default_title($title)
{
	$screen = get_current_screen();
	if ($screen->post_type == 'rinji') {
		$title = 'ここに変更する日付を入力(店舗側確認用)';
	}
	return $title;
}
add_filter('enter_title_here', 'change_default_title');
?>
<?php
//ショートコード

function business_hours()
{
	// タイムゾーンを日本に設定
	date_default_timezone_set('Asia/Tokyo');
	$todayweek = date('w');

	$days = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
	$day_option = $days[$todayweek];
	$youbi = get_option($day_option, '');
	$check_holiday = get_option('check_' . $day_option, '');

	$message_holiday = get_option('holiday', '');
	$message_bh_prefix = get_option('bh_prefix', '');

	$temporary_business_hour = check_temporary_business_hours();
	if ($temporary_business_hour) {
		return $temporary_business_hour;
	}

	if ($check_holiday == "checked") {
		return "<span id='message_holiday'>" . $message_holiday . "</span>";
	} else {
		return "<span id='message_bh_prefix'>" . $message_bh_prefix . "</span><span id='bh_time'>" . $youbi . "</span>";
	}
}

function check_temporary_business_hours()
{
	$today = getdate();
	$args = array(
		'post_type' => 'rinji',
		'posts_per_page' => 1,
		'date_query' => array(
			array(
				'year' => $today['year'],
				'month' => $today['mon'],
				'day' => $today['mday'],
			),
		),
	);

	$posts = get_posts($args);

	if (!empty($posts)) {
		foreach ($posts as $post) {
			setup_postdata($post);
			if (get_post_meta($post->ID, 'yasumi', true) == "checked") {
				wp_reset_postdata();
				return "<span id='message_holiday'>" . get_option('holiday', '') . "</span>";
			} else {
				$jikan = get_post_meta($post->ID, 'jikan', true);
				wp_reset_postdata();
				if (!empty($jikan)) {
					return "<span id='message_bh_prefix'>" . get_option('bh_prefix', '') . "</span><span id='bh_time'>" . $jikan . "</span>";
				}
			}
		}
	}
	return false; // 臨時営業時間がない場合は false を返す
}

add_shortcode('business_hours', 'business_hours');
