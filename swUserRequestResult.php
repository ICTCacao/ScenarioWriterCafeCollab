<?php
//	------------------------------------------------------------------------------
//
//	Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
//			ScenarioWriterCafe登録申請リザルト
//
//			SwUserRequestResult.php
//	------------------------------------------------------------------------------
// ------------------------------------------------------------------------------
    //共通関数をｲﾝｸﾙｰﾄﾞ
    include_once("./include/swFunc.php");
    // ------------------------------------------------------------------------------
    //フォームのデータを読み込む
    fncGetPostItems();
    
	//MAIN PROC
	fncMainProc();

exit;
// ------------------------------------------------------------------------------
//
//      フォームのデータを読み込む
//          fncGetPostItems()
// ------------------------------------------------------------------------------
function fncGetPostItems(){
    //global変数
    global  $fdtUserMailad;
    global  $fdtUserName;

    //global変数の取得
    $fdtUserMailad = swFunc_GetPostData('fdtUserMailad');
    $fdtUserName = swFunc_GetPostData('fdtUserName');

}

//--------------------------------------------------------------------------------
// MAIN PROCEDUR
//	fncMainProc($mySqlConnObj)
//--------------------------------------------------------------------------------
function fncMainProc(){
    global  $fdtUserMailad;
    global  $fdtUserName;

	//ｻﾆﾀｲｽﾞ
	$fdtUserName = swFunc_SanitizeStrings($fdtUserName);
	$fdtUserMailad = swFunc_SanitizeStrings($fdtUserMailad);
	
 	$retHtml = <<<END_OF_HTML
 	
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>ScenarioWriterCafe</title>
	<link rel="shortcut icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
		<!-- Bootstrap Core CSS -->
    <link rel="stylesheet" type="text/css" href="./css/scwDefault.css?v=20260921c">
    <link rel="stylesheet" type="text/css" href="./css/scwUserStyle.css?v=20260918">
		
		<!-- Custom CSS -->
		
		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->

  </head>
  <body>
	<header>
		<div class="container">
			<div class="result-text">
					<img src="./img/logo.png">
					<hr>
					<div class="col-sm-10 offset-sm-1" >
						<div class="jumbotron">
							$fdtUserName 様<br />
							<br />
							ScenarioWriterCafeへのメールアドレス登録申請を受け付けました。<br />
							<br />
							　ScenarioWriterCafeへの<font color="red">ログインURL</font>と<font color="red">仮のパスワード</font>を<br />
							$fdtUserMailad に送信しましたのでメールに記載のURLからご利用ください。<br />
							<br />
							なお，仮のパスワードでログインいただいた後に<font color="red">必ずパスワードの変更</font>をしていただきますようお願いいたします。<br />
							また，メールが届かない場合は，メールアドレスをご確認いただき再度送信してください。<br />
			        	</div><!-- jumbotron -->
			        </div><!-- col-sm-10 -->
					<div class="col-sm-4 offset-sm-3">
						<button type="button" class="btn btn-success btn-lg w-100"
									onClick="window.close();">CLOSE
						</button>
					</div>
        	</div><!-- intro-text -->
        </div><!-- container -->
    </header>

    <!-- jQuery -->
    <script src="./js/scw.js?v=20260918"></script>

    <!-- Bootstrap Core JavaScript -->

  </body>
</html>

END_OF_HTML;

	print $retHtml;
}

?>