<?php
if(!defined('DIR_INIT'))exit();

session_start();

if(!empty($_FILES) && $_POST['type'] == 'fileupload'){
    header('Content-Type: application/json; charset=UTF-8');
    if(!$_POST['token'] || $_POST['token']!=$_SESSION['csrf_token'])exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
    $chunkIndex = intval($_POST['dzchunkindex']);
    $chunkTotal = intval($_POST['dztotalchunkcount']);
    $filename = $_FILES['file']['name'];
    $tmp_name = $_FILES['file']['tmp_name'];
    $fullPath = $path.'/'.$filename;
    if ($chunkTotal){
        $out = fopen($fullPath.'.part', $chunkIndex == 0 ? "wb" : "ab");
        if(!$out){
            exit(json_encode(['code'=>-1, 'msg'=>'分块上传失败，可能无文件写入权限']));
        }
        $in = fopen($tmp_name, "rb");
        if(!$in){
            exit(json_encode(['code'=>-1, 'msg'=>'分块上传失败，无法读取临时文件']));
        }
        while ($buff = fread($in, 4096)) { fwrite($out, $buff); }
        fclose($in);
        fclose($out);
        unlink($tmp_name);

        if ($chunkIndex == $chunkTotal - 1) {
            file_exists($fullPath) && unlink($fullPath);
            rename($fullPath.'.part', $fullPath);
            exit(json_encode(['code'=>0]));
        }else{
            exit(json_encode(['code'=>0, 'chunkindex'=>$chunkIndex]));
        }
    }else{
        file_exists($fullPath) && unlink($fullPath);
        if(move_uploaded_file($tmp_name, $fullPath)){
            exit(json_encode(['code'=>0]));
        }else{
            exit(json_encode(['code'=>-1, 'msg'=>$filename.' 上传失败，可能无文件写入权限']));
        }
    }
}else if($_POST['type'] == 'urlupload' && !empty($_POST['url']) && !empty($_POST['filename'])){
    header('Content-Type: application/json; charset=UTF-8');
    $url = trim($_POST['url']);
    $filename = trim($_POST['filename']);
    if(!filter_var($url, FILTER_VALIDATE_URL)) exit(json_encode(['code'=>-1, 'msg'=>'URL格式不正确']));
    if(!$_POST['token'] || $_POST['token']!=$_SESSION['csrf_token'])exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
    $fullPath = $path.'/'.$filename;
    try{
        curl_download($url, $fullPath.'.part');
        file_exists($fullPath) && unlink($fullPath);
        rename($fullPath.'.part', $fullPath);
        exit(json_encode(['code'=>0, 'filename'=>$filename]));
    }catch(Exception $e){
        exit(json_encode(['code'=>-1, 'msg'=>$e->getMessage()]));
    }
}
$csrf_token = md5(mt_rand(0,999).time());
$_SESSION['csrf_token'] = $csrf_token;

header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>上传文件</title>
    <link rel="stylesheet" href="//cdn.staticfile.org/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="//cdn.staticfile.org/twitter-bootstrap/4.6.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="//cdn.staticfile.org/dropzone/5.9.3/min/dropzone.min.css">
</head>
<body>
<style>
.card{border: unset;}
form.dropzone { min-height:280px;border:2px dashed #007bff;line-height:6rem; }
.lds-facebook { display:none;position:relative;width:64px;height:64px }
.lds-facebook div,.lds-facebook.show-me { display:inline-block }
.lds-facebook div { position:absolute;left:6px;width:13px;background:#007bff;animation:lds-facebook 1.2s cubic-bezier(0,.5,.5,1) infinite }
.lds-facebook div:nth-child(1) { left:6px;animation-delay:-.24s }
.lds-facebook div:nth-child(2) { left:26px;animation-delay:-.12s }
.lds-facebook div:nth-child(3) { left:45px;animation-delay:0s }
@keyframes lds-facebook { 0% { top:6px;height:51px }
100%,50% { top:19px;height:26px }
}
</style>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" data-toggle="tab" href="#fileupload" role="tab"><i class="fa fa-arrow-circle-o-up"></i> 本地上传</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-toggle="tab" href="#urlupload" role="tab"><i class="fa fa-link"></i> 远程上传</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <p class="card-text">
            目标文件夹: <?php echo $path?>/
        </p>
        <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="fileupload" role="tabpanel" aria-labelledby="fileupload-tab">
            <form action="?c=upload&path=<?php echo urlencode($path);?>" class="dropzone card-tabs-container" id="fileUploader" enctype="multipart/form-data">
                <input type="hidden" name="type" value="fileupload">
                <input type="hidden" name="token" value="<?php echo $csrf_token?>">
                <div class="fallback">
                    <input name="file" type="file" multiple/>
                </div>
            </form>
        </div>
        <div class="tab-pane fade" id="urlupload" role="tabpanel" aria-labelledby="urlupload-tab">
            <div class="upload-url-wrapper card-tabs-container" id="urlUploader">
                <form id="js-form-url-upload" class="form" onsubmit="return upload_from_url(this);" method="POST">
                    <input type="hidden" name="type" value="urlupload">
                    <input type="hidden" name="token" value="<?php echo $csrf_token?>">
                    <div class="form-group">
                        <input type="text" placeholder="远程文件的URL" name="url" id="url" required class="form-control" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" placeholder="保存的文件名" name="filename" required class="form-control" autocomplete="off">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">确定</button>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-success alert-dismissible fade" role="alert" id="upload-alert">
                        <span id="upload-msg"></span>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
</div>
<script src="//cdn.staticfile.org/jquery/3.6.1/jquery.min.js"></script>
<script src="//cdn.staticfile.org/twitter-bootstrap/4.6.1/js/bootstrap.min.js"></script>
<script src="//cdn.staticfile.org/layer/3.1.1/layer.js"></script>
<script src="//cdn.staticfile.org/dropzone/5.9.3/min/dropzone.min.js"></script>
<script>
    Dropzone.options.fileUploader = {
        chunking: true,
        chunkSize: 8 * 1024 * 1024,
        forceChunking: true,
        retryChunks: true,
        retryChunksLimit: 3,
        parallelUploads: 1,
        parallelChunkUploads: false,
        timeout: 120000,
        maxFilesize: 10 * 1024,
        init: function () {
            this.on("sending", function (file, xhr, formData) {
                xhr.ontimeout = (function() {
                    layer.msg('Error: Server Timeout', {icon: 2});
                });
            }).on("success", function (res) {
                let _response = JSON.parse(res.xhr.response);

                if(_response.code != 0) {
                    layer.msg(_response.msg, {icon: 2});
                }
                window.parent.postMessage('reload')

            }).on("error", function(file, response) {
                layer.msg(response, {icon: 2});
            });
        }
    }
    function upload_from_url(obj){
        var ii = layer.msg('正在上传，请稍候...', {icon: 16,shade: 0.3,time: 15000});
        $.ajax({
            type : "POST",
            url : "?c=upload&path=<?php echo urlencode($path);?>",
            data : $(obj).serialize(),
            dataType : 'json',
            success : function(data) {
                layer.close(ii);
                if(data.code == 0){
                    $("#upload-msg").html('<storng>'+data.filename+'</strong> 文件上传成功！');
                    $("#upload-alert").addClass("show");
                    window.parent.postMessage('reload')
                }else{
                    layer.alert(data.msg, {icon: 2})
                }
            },
            error: function(){
                layer.close(ii);
                layer.msg('服务器错误');
            }
        })
        return false;
    }
    $(document).ready(function(){
        $("#url").change(function(){
            var url = $(this).val();
            if(url == '') return;
            var name = url.slice(url.lastIndexOf('/')+1);
            name = name.split('#')[0].split('?')[0]
            if(name!=''){
                $("input[name='filename']").val(name)
            }
        })
    })
</script>
</body>
</html>