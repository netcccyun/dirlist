<?php
if(!defined('DIR_INIT'))exit();
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>视频播放器</title>
</head>
<body>
<div id="dplayer"></div>
<script src="//cdn.staticfile.org/jquery/3.6.1/jquery.min.js"></script>
<?php if($ext == 'm3u8'){?><script src="//cdn.staticfile.org/hls.js/1.2.4/hls.min.js"></script><?php }?>
<?php if($ext == 'flv'){?><script src="//cdn.staticfile.org/flv.js/1.6.2/flv.min.js"></script><?php }?>
<script src="//cdn.staticfile.org/dplayer/1.27.1/DPlayer.min.js"></script>
<script type="text/javascript">
    var dp = new DPlayer({
        container: document.getElementById('dplayer'),
        video: {
            url: '<?php echo $url; ?>'
        }
    });
    dp.on('loadedmetadata', function () {
        var maxheight = $(window).height() - 18;
        if($("#dplayer").height() > maxheight)
            $("#dplayer").height(maxheight);
    });
</script>
</body>
</html>