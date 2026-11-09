<?php
if(!defined('DIR_INIT'))exit();

if(session_status() !== PHP_SESSION_ACTIVE) session_start();

function upload_check_filename($filename){
    global $x, $path;
    if ($filename === '' || $filename === '.' || $filename === '..' || strpos($filename, '..') !== false || preg_match('/[:\/\\\\?*<>|"\x00]/', $filename)) {
        exit(json_encode(['code'=>-1, 'msg'=>'文件名不合法']));
    }
    $filename = $x->encoding($filename, false);
    if ($x->is_hide(($path === '.' ? '' : $path.'/').$filename)) {
        exit(json_encode(['code'=>-1, 'msg'=>'该文件名为系统保护文件，禁止上传']));
    }
    return $filename;
}
function upload_check_token(){
    if(empty($_POST['token']) || empty($_SESSION['csrf_token']) || !is_string($_POST['token']) || !hash_equals($_SESSION['csrf_token'], $_POST['token'])) exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
}

if(!empty($_FILES) && !empty($_POST['type']) && $_POST['type'] == 'fileupload'){
    header('Content-Type: application/json; charset=UTF-8');
    upload_check_token();
    if(!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK){
        exit(json_encode(['code'=>-1, 'msg'=>'上传失败，错误码：'.(isset($_FILES['file']['error']) ? $_FILES['file']['error'] : '-')]));
    }
    $chunkIndex = isset($_POST['dzchunkindex']) ? intval($_POST['dzchunkindex']) : 0;
    $chunkTotal = isset($_POST['dztotalchunkcount']) ? intval($_POST['dztotalchunkcount']) : 0;
    $filename = basename(str_replace('\\', '/', $_FILES['file']['name']));
    $filename = upload_check_filename($filename);
    $tmp_name = $_FILES['file']['tmp_name'];
    $fullPath = $path.'/'.$filename;
    if ($chunkTotal){
        $out = fopen($fullPath.'.part', $chunkIndex == 0 ? "wb" : "ab");
        if(!$out){
            exit(json_encode(['code'=>-1, 'msg'=>'分块上传失败，可能无文件写入权限']));
        }
        $in = fopen($tmp_name, "rb");
        if(!$in){
            fclose($out);
            exit(json_encode(['code'=>-1, 'msg'=>'分块上传失败，无法读取临时文件']));
        }
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);
        @unlink($tmp_name);

        if ($chunkIndex == $chunkTotal - 1) {
            file_exists($fullPath) && unlink($fullPath);
            if(!rename($fullPath.'.part', $fullPath)){
                exit(json_encode(['code'=>-1, 'msg'=>'保存文件失败，可能无文件写入权限']));
            }
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
}else if(!empty($_POST['type']) && $_POST['type'] == 'urlupload' && !empty($_POST['url']) && !empty($_POST['filename'])){
    header('Content-Type: application/json; charset=UTF-8');
    upload_check_token();
    $url = trim($_POST['url']);
    $show_filename = basename(str_replace('\\', '/', trim($_POST['filename'])));
    if(!filter_var($url, FILTER_VALIDATE_URL)) exit(json_encode(['code'=>-1, 'msg'=>'URL格式不正确']));
    $scheme = strtolower(parse_url($url, PHP_URL_SCHEME));
    if(!in_array($scheme, ['http','https'], true)) exit(json_encode(['code'=>-1, 'msg'=>'仅支持http/https远程地址']));
    $filename = upload_check_filename($show_filename);
    $fullPath = $path.'/'.$filename;
    try{
        curl_download($url, $fullPath.'.part');
        file_exists($fullPath) && unlink($fullPath);
        if(!rename($fullPath.'.part', $fullPath)) throw new Exception('保存文件失败，可能无文件写入权限');
        exit(json_encode(['code'=>0, 'filename'=>$show_filename]));
    }catch(Exception $e){
        exit(json_encode(['code'=>-1, 'msg'=>$e->getMessage()]));
    }
}
// 复用已有token，避免同时打开多个上传窗口时旧窗口token失效
if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf_token = $_SESSION['csrf_token'];
$show_path = $x->encoding($path, true);

header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>上传文件</title>
    <link rel="stylesheet" href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="<?php echo $cdnpublic?>twitter-bootstrap/4.6.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $cdnpublic?>dropzone/5.9.3/min/dropzone.min.css">
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
            目标文件夹: <?php echo h($show_path)?>/
        </p>
        <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="fileupload" role="tabpanel" aria-labelledby="fileupload-tab">
            <form action="?c=upload&amp;path=<?php echo urlencode($show_path);?>" class="dropzone card-tabs-container" id="fileUploader" enctype="multipart/form-data">
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
<script src="<?php echo $cdnpublic?>jquery/3.7.1/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>dropzone/5.9.3/min/dropzone.min.js"></script>
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
                window.parent.postMessage('reload', '*')

            }).on("error", function(file, response) {
                layer.msg(typeof response === 'object' && response ? (response.msg || response.error || '上传失败') : response, {icon: 2});
            });
        }
    }
    function upload_from_url(obj){
        var ii = layer.msg('正在上传，请稍候...', {icon: 16,shade: 0.3,time: 15000});
        $.ajax({
            type : "POST",
            url : "?c=upload&path=<?php echo urlencode($show_path);?>",
            data : $(obj).serialize(),
            dataType : 'json',
            success : function(data) {
                layer.close(ii);
                if(data.code == 0){
                    $("#upload-msg").empty().append($('<strong>').text(data.filename)).append(' 文件上传成功！');
                    $("#upload-alert").addClass("show");
                    window.parent.postMessage('reload', '*')
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