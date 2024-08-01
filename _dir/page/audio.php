<?php
if(!defined('DIR_INIT'))exit();
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>音频播放器</title>
    <link rel="stylesheet" href="https://s4.zstatic.net/ajax/libs/aplayer/1.10.1/APlayer.min.css"/>
</head>
<body>
<div id="aplayer"></div>
<?php if($ext == 'm3u8'){?><script src="https://s4.zstatic.net/ajax/libs/hls.js/1.2.4/hls.min.js"></script><?php }?>
<script src = "https://s4.zstatic.net/ajax/libs/aplayer/1.10.1/APlayer.min.js"></script>
<script type="text/javascript">
    var ap = new APlayer({
        container: document.getElementById('aplayer'),
        loop: 'none',
        audio: [{
            name: '<?php echo $name; ?>',
            url: '<?php echo $url; ?>',
            artist: 'none',
            cover: './_dir/static/images/music.png'
        }]
    });
</script>
</body>
</html>