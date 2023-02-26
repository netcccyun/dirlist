<?php
if(!defined('DIR_INIT'))exit();

$view_type = $x->get_view_type($ext);
if($view_type!='text') sysmsg('不支持的文件格式');

if(filesize($path) > 1024 * 1024 * 10) sysmsg('文件超过10M无法查看');

$content = file_get_contents($path);
if($content===false) sysmsg('文件读取失败');

$coding = mb_detect_encoding($content,"UTF-8,GBK,GB2312");
if($coding != 'UTF-8'){
    $content = mb_convert_encoding($content, 'UTF-8', $coding);
}

$content = htmlspecialchars($content);

header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8" />
    <title>文本查看器</title>
    <link rel="stylesheet" href="//cdn.staticfile.org/highlight.js/11.6.0/styles/github.min.css">
    <style type="text/css" media="screen">
        code{
            font-family:  Arial,sans-serif;
        }
        #viewhtml{
            word-break: break-all;
            white-space: break-spaces;
        }
    </style>
</head>
<body>
<pre><code id="viewhtml"><?php echo $content; ?>
</code></pre>
<script src="//cdn.staticfile.org/highlight.js/11.6.0/highlight.min.js"></script>
<script>hljs.highlightAll();</script>
</body>
</html>