<?php
if(!defined('DIR_INIT'))exit();
?><!DOCTYPE html>
<html lang="zh-cn">
<head>
	<meta charset="utf-8" />
	<meta name="renderer" content="webkit">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<title><?php echo $conf['title']?></title>
	<meta name="keywords" content="Beauty Directory,Directory Lister目录列表,目录索引" />
	<meta name="description" content="Beauty Directory目录列表程序" />
	<link rel="stylesheet" href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="<?php echo $cdnpublic?>twitter-bootstrap/4.6.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="<?php echo $cdnpublic?>github-markdown-css/5.1.0/github-markdown.min.css">
    <link rel='stylesheet' href='./_dir/static/css/style.css?v=1003'>
  <!--[if lt IE 9]>
    <script src="<?php echo $cdnpublic?>html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="<?php echo $cdnpublic?>respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body>

<nav class="navbar sticky-top navbar-expand-lg navbar-light bg-white border-bottom" id="navbar">
	<div class="container big-nav">
		<a class="navbar-brand" href="./">
		<img src="./_dir/static/images/logo.png" width="180" height="40" class="d-inline-block align-top mr-2" alt="">
		</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarSupportedContent">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item <?php echo $c=='home'?'active':'';?>">
				<a class="nav-link" href="./">首页</a>
				</li>
<?php
$navs = explode('|', $conf['nav']);
foreach($navs as $nav){
	$nav_arr = explode('*', $nav);
	echo '<li class="nav-item"><a class="nav-link" href="'.$nav_arr[1].'" target="_blank">'.$nav_arr[0].'</a></li>';
}
?>
				<li class="nav-item <?php echo $c=='admin'?'active':'';?>">
					<a class="nav-link" href="./?c=admin">后台管理</a>
				</li>
			</ul>

			<form class="form-inline my-2 my-lg-0 d-none d-lg-flex" action="./" method="GET">
				<input type="hidden" name="c" value="search">
				<input name="s" class="form-control mr-sm-2" type="search" placeholder="请输入搜索关键字" aria-label="Search" value="">
				<button class="btn btn-outline-primary my-2 my-sm-0" type="submit">	<i class="fa fa-search" aria-hidden="true"></i> 搜索</button>
			</form>
												
		</div>
	</div>
</nav>