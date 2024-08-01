<?php
if(!defined('DIR_INIT'))exit();

$view_type = $x->get_view_type($ext);
if($view_type!='markdown') sysmsg('不支持的文件格式');

if(filesize($path) > 1024 * 1024 * 10) sysmsg('文件超过10M无法查看');

$content = file_get_contents($path);
if($content===false) sysmsg('文件读取失败');

require SYSTEM_ROOT.'Parsedown.class.php';
$Parsedown = new Parsedown();
$content = $Parsedown->text($content);
$content = str_replace('[x]','<input type="checkbox" checked>',$content);
$content = str_replace('[ ]','<input type="checkbox">',$content);

header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MDtoHTML</title>
    <link rel="stylesheet" href="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/4.6.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://s4.zstatic.net/ajax/libs/github-markdown-css/5.1.0/github-markdown.min.css">
<style>
    body {background-color: #eee!important;}
    .center-block {margin: 0 auto; float: none; padding: 0;}
    .markdown-body {
        box-sizing: border-box;
        margin: 18px auto;
        padding: 45px;
        box-shadow: 2px 2px 2px 2px #888888;
    }
    @media (max-width: 767px) {
        .markdown-body {
            padding: 15px;
            margin: 0 auto;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-lg-10 col-xl-8 center-block">
                <div class="markdown-body">
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>