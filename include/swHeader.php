<?php
// ------------------------------------------------------------------------------
//
//		scenariowritercafeシステム共通
//				swHeader.php
//
//		Copyright (C) 2026 ICTCacao Released under the MIT License (see LICENSE).
//
// ------------------------------------------------------------------------------
print <<<END_OF_HTML

<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	<title>$HtmlTitle</title>
	<link rel="shortcut icon" href="./favicon.ico" type="image/vnd.microsoft.icon">

    <!-- Favicons
    ================================================== -->
    <link rel="shortcut icon" href="./img/icon/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="./img/icon/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="72x72" href="./img/icon/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="114x114" href="./img/icon/apple-touch-icon-114x114.png">
	
	<!-- Bootstrap -->
	<link href="css/scwDefault.css?v=20260921c" rel="stylesheet">
	<link href="css/scwUserStyle.css?v=20260918" rel="stylesheet">
	<!-- site style -->
	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!-- jQuery -->
	<script type="text/javascript" src="./js/scw.js?v=20260918"></script>
	<!-- bootstrap -->
	<!-- ScenarioWriterCafe -->
	<script type="text/javascript" src="./ajax/ajaxFunc.js?v=20260917"></script>
	
  </head>
  <body>

END_OF_HTML;


?>
