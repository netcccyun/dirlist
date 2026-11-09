<?php
if(!defined('DIR_INIT'))exit();
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>音频播放器</title>
    <link rel="stylesheet" href="<?php echo $cdnpublic?>aplayer/1.10.1/APlayer.min.css"/>
</head>
<body>
<div id="aplayer"></div>
<?php if($ext == 'm3u8'){?><script src="<?php echo $cdnpublic?>hls.js/1.6.14/hls.min.js"></script><?php }?>
<script src="<?php echo $cdnpublic?>aplayer/1.10.1/APlayer.min.js"></script>
<script type="text/javascript">
    var ap = new APlayer({
        container: document.getElementById('aplayer'),
        loop: 'none',
        audio: [{
            name: <?php echo json_encode($name, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG); ?>,
            url: <?php echo json_encode($url, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?>,
            artist: 'none',
            cover: './_dir/static/images/music.png'
        }]
    });
</script>
</body>
</html>