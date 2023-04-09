<?php
//error_reporting(1);
date_default_timezone_set('Asia/Tokyo');	

$nozokima_url = "http://kagan.php.xdomain.jp/nozokima/nozokima.php";
$oauth_url = "https://slack.com/api/oauth.access";
$param = [
        "client_id" => file(__DIR__ . "/nozokima_data/client_id")[0],
        "client_secret" => file(__DIR__ . "/nozokima_data/client_secret")[0]
];

//認証ページから来たとき
if(isset($_GET["code"]) && !isset($_COOKIE["nozokima_response"])){
	$param["code"] = $_GET["code"];

	$oauth_curl = curl_init($oauth_url . "?" . http_build_query($param));
	curl_setopt_array($oauth_curl, [CURLOPT_RETURNTRANSFER => true]);
	$json = curl_exec($oauth_curl);
	setcookie("nozokima_response", $json);
	curl_close($oauth_curl);
	$result = json_decode($json);
	header("Location: " . $nozokima_url);
	exit;
}

echo <<< EOM
<!DOCTYPE html>
<html lang="ja">
<head>
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-TNJRPLD');</script>
	<!-- End Google Tag Manager -->
	<title>覗き魔</title>
	<link rel="stylesheet" type="text/css" href="design.css">
	<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
</head>
<body>
	<div id="container">
		<h1>覗きツール</h1>
EOM;

if(!isset($_GET["code"]) && !isset($_COOKIE["nozokima_response"])){
	echo 'ログインしてください<br><a href="https://slack.com/oauth/authorize?scope=search:read,users:read,channels:read&client_id=115530075751.280393885911"><img alt="Sign in with Slack" height="40" width="172" src="https://platform.slack-edge.com/img/sign_in_with_slack.png" srcset="https://platform.slack-edge.com/img/sign_in_with_slack.png 1x, https://platform.slack-edge.com/img/sign_in_with_slack@2x.png 2x" /></a><br>';
}else if(isset($_COOKIE["nozokima_response"])){
	//使い方
	echo "<button onClick='
	var elem = document.getElementById(\"howtouse\");
	if(elem.style.display==\"none\"){
		elem.style.display=\"block\";
	}else{
		elem.style.display=\"none\";
	}'>▼使い方を表示▼</button>";
	echo "<div id='howtouse' style='display:none; font-size:80%;'>";
	echo "<b>説明</b><br>Slackの検索結果をブラウザで表示しているだけのプログラムです。<br>初期値は「昨日からの全てのメッセージ」です。<br>";
	echo "ほぼ読み込んだまま吐き出しているので、太字や取り消し線などの装飾やリンクは再現できてないです。<br>";
	echo "見られるもの：全てのパブリックチャンネル、自分が入っているプライベートチャンネル、自分が関係するDM<br>";
	echo "見られないもの：アプリの出力、自分の入っていないプライベートチャンネル、人のDM<br>";
	echo "<b>オプションの使い方</b><br>";
	echo "「検索」：単語で検索できます。<br>";
	echo "「この日から」：初期値は昨日になってます。空欄で指定なし。<br>";
	echo "「この日まで」：空欄で指定なし。<br>";
	echo "「表示数」：１ページに表示する件数。1番下でページ移動できます。<br>";
	echo "入力したら「更新」してください。<br>";
	echo "<b>「Slackで開く」ボタン</b><br>";
	echo "パソコンならPC版Slackで、スマホならアプリで開きます。ソフトが入ってない場合は何も起こりません。<br>";
	echo "ちなみに、チャンネル名も同様にチャンネルへのリンクになっています。<br>";
	echo "</div><br>";

	//cookie読み込み
	$result = json_decode($_COOKIE["nozokima_response"]);

	echo "<button onClick='
	var elem = document.getElementById(\"channellist\");
	if(elem.style.display==\"none\"){
		elem.style.display=\"block\";
	}else{
		elem.style.display=\"none\";
	}'>▼チャンネル一覧を表示▼</button>";

	//channels.list
    $channels_url = "https://slack.com/api/channels.list?token={$result->access_token}";
	$channels_curl = curl_init($channels_url);
	curl_setopt_array($channels_curl, [CURLOPT_RETURNTRANSFER => true]);
	$channels_json = curl_exec($channels_curl);
	curl_close($channels_curl);
	$channels = json_decode($channels_json)->channels;

	if(is_null($channels)){
		echo "チャンネル情報取得失敗";
		echo $channels_json;
	}else{
		echo "<div id='channellist' style='display:none;'>";
		echo "[検索]=覗き魔で検索、[開く]=Slackアプリで開く<br>";
		foreach($channels as $channel){			
			//チャンネル名
			if(!($channel->is_archived)){
				echo "<div class='channel";
				echo "'>";
				echo "<span class='channel_name'>#" . $channel->name . "</span> ";
				//検索
				echo "<span class='link'><a href='" . $nozokima_url . "?word=in:{$channel->name}'>検索</a></span> ";
				//Slackで開く
				echo "<span class='link'><a href='slack://channel?team={$result->team_id}&id={$channel->id}'>開く</a></span>";
				echo "</div>";
			}
		}
		echo "</div>";
	}


	//文字表示
	//echo "{$result->user->name}さんようこそ！<br><br>";

	//単語検索
	echo "<form name='reload' method='GET' style='border:solid 1px #000; padding:10px; margin: 10px 0;'>";
	echo "検索 <input type='text' name='word' value='";
	if(!empty($_GET['word'])){
		echo $_GET['word'];
	}
	echo "''><br>";
	//after
	echo "この日から <input type='date' name='after' value=";
	if(empty($_GET['after']) && empty($_GET['word']) && empty($_GET['before']) && empty($_GET['count'])){
		echo date('Y-m-d', strtotime('-1 day'));
	}else if(!empty($_GET['after'])){
		echo $_GET['after'];
	}
	echo "><br>";
	//before
	echo "この日まで <input type='date' name='before' value=";
	if(!empty($_GET['before'])){
		echo $_GET['before'];
	}
	echo "><br>";
	//件数指定
	echo "表示数 <input type='number' name='count' value=";
	if(empty($_GET['count'])){
		echo "20";
	}else{
		echo $_GET['count'];
	}
	echo "><br>";
	//ページ
	echo "<input type='hidden' id='page' name='page' value='1'>";
	//更新ボタン
	echo "<input type='submit' value='更新'></form>";

	//users.list
    $users_url = "https://slack.com/api/users.list?token={$result->access_token}";
	$users_curl = curl_init($users_url);
	curl_setopt_array($users_curl, [CURLOPT_RETURNTRANSFER => true]);
	$users_json = curl_exec($users_curl);
	curl_close($users_curl);
	$users = json_decode($users_json)->members;

	//search.all
	$query_text = "";
	if(!empty($_GET['word'])){
		$query_text .= $_GET['word'];
	}
	if(!empty($_GET['after'])){
		$query_text .= " after:".date('Y-m-d', strtotime($_GET['after']." -1 day"));
	}
	if(!empty($_GET['before'])){
		$query_text .= " before:".date('Y-m-d', strtotime($_GET['before']."+1 day"));
	}
	$search_url = "https://slack.com/api/search.all";
	$search_query =[
		"token" => $result->access_token,
		"query" => (empty($query_text) ? "after:".date('Y-m-d', strtotime("-2 day")) : $query_text),
		"count" => (empty($_GET['count']) ? 20 : $_GET['count']),
		"page" => (empty($_GET['page']) ? 1 : $_GET['page']),
		"sort" => "timestamp",
		"pretty" => 1,
	];
	$search_curl = curl_init($search_url . "?" . http_build_query($search_query));
	curl_setopt_array($search_curl, [CURLOPT_RETURNTRANSFER => true]);
	$search_json = curl_exec($search_curl);

	//debug出力
	/*
	echo "<button onClick='
	var elem = document.getElementById(\"json\");
	if(elem.style.display==\"none\"){
		elem.style.display=\"block\";
	}else{
		elem.style.display=\"none\";
	}'>デバッグ用json表示</button>";
	*/
	echo "<div id='json' style='display:none'><pre>";
	echo str_replace(['<', '>', '  '], ['&lt;', '&gt;', ' '], json_encode(json_decode($search_json), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	echo "</pre></div>";

	curl_close($search_curl);
	
	$messages = json_decode($search_json)->messages->matches;
	if(is_null($messages)){
		echo "取得失敗";
		echo $search_json;
	}else{
		foreach($messages as $message){
			echo "<div class='message'>";
			
			//チャンネル名
			preg_match_all("/^(U[0-9A-Z]{8})$/", $message->channel->name, $res);
			foreach ($res[1] as $i => $val) {
				$key = array_search($val, array_column($users, 'id'));
				$real_name[$i] = $users[$key]->real_name;
			}
			$text = str_replace($res[1], $real_name, $message->channel->name);
			echo "<a class='channel_name' href='slack://channel?team={$message->team}&id={$message->channel->id}'>#" . $text . "</a><br>";
			//ユーザー名
			if(!empty($message->user)){
				$key = array_search($message->user, array_column($users, 'id'));
				if($key !== null){
					echo "<span class='user_icon'><img src='" . $users[$key]->profile->image_24 . "'></span> ";
					echo "<span class='user_name'>" . $users[$key]->real_name . "</span> ";
				}
			}else{
				echo "<span class='user_name'>アプリ<span> ";
			}

			//時刻
			echo "<span class='time'>" . date("n\月j\日 G:i", $message->ts) . "</span>　";
			echo "<br>";

			//メッセージ
			//＠の変換
			$text = (is_array($message->text) ? end($message->text) : $message->text);
			preg_match_all("/<@(U[0-9A-Z]{8})>/", $text, $res);
			foreach ($res[1] as $i => $val) {
				$key = array_search($val, array_column($users, 'id'));
				$real_name[$i] = $users[$key]->real_name;
			}
			$text = str_replace($res[1], $real_name, $text);
			echo "<span class='text'>" . str_replace(['<', '>', "\n"], ['&lt;', '&gt;', "<br>"], $text) . "</span>";

			//リンク
			echo "<span class='link'><a href='slack://channel?team={$message->team}&id={$message->channel->id}&ts={$message->ts}";
			if(($pos = strpos($message->permalink, "?thread_ts")) !== false){
				echo "&" . substr($message->permalink, $pos + 1);
			}
			echo "''>Slackで開く</a></span><br>";
			//echo "<span class='link'><a href='{$message->permalink}''>パーマリンク</a></span><br>";
			echo "</div>";
		}
		$pagination = json_decode($search_json)->messages->pagination;
		echo "<div style='text-align:center; padding:20px 0;'>";
		if(1 < $pagination->page){
			echo "<button onClick='
			document.getElementById(\"page\").value=";
			echo ($pagination->page - 1).";";
			echo "document.reload.submit();
			'>前のページ</button>　";
		}else{
			echo "<button style='visibility:hidden;'>前のページ</button>";
		}
		echo $pagination->page."ページ / ".$pagination->page_count."ページ";
		if($pagination->page < $pagination->page_count){
			echo "　<button onClick='
			document.getElementById(\"page\").value=";
			echo ($pagination->page + 1).";";
			echo "document.reload.submit();
			'>次のページ</button>";
		}
		echo "</div>";
	}
}else{
	echo "エラー<br><a href='" . $nozokima_url . "'>いったんもどる</a>";
}

echo <<< EOM
	</div>
</body>
</html>
EOM;

