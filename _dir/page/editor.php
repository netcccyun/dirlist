<?php
if(!defined('DIR_INIT'))exit();

$name = basename($path);
$ext = $x->get_file_ext($path);
if(isset($_POST['file_content']) && isset($_POST['character'])){
    $file_content = stripslashes($_POST['file_content']);
    $character = $_POST['character'];
    if ($character != 'UTF-8')
    {
        $file_content = mb_convert_encoding($file_content, $character, 'UTF-8');
    }
    file_put_contents($path, $file_content);
    echo_json(['code'=>0]);
}

if(filesize($path) > 1024 * 1024 * 10) sysmsg('文件超过10M无法在线编辑');
$file_content = file_get_contents($path);
$character = mb_detect_encoding($file_content, array('UTF-8',"EUC-CN",'BIG-5',"CP936"), true);
if(!$character) sysmsg('该文件因编码问题不支持在线编辑');
if($character != 'UTF-8')
    $file_content = mb_convert_encoding($file_content, 'UTF-8', $character);
$file_content = htmlspecialchars($file_content);

header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>文本编辑器</title>
    <link rel="stylesheet" href="./_dir/static/css/editor.css"/>
</head>
<body>
<form id="file_content_form" method="POST">
<input type="hidden" name="path" id="path" value="<?php echo $path;?>" />
<input type="hidden" name="character" id="character" value="<?php echo $character;?>" />
<pre id="file_content" name="file_content"><?php echo $file_content;?></pre>
<div class="title" style="padding: 6px 17px;">
    <input type="button" value="查找" onclick="edit_tool('find')" class="input_button tip" title="Ctrl+F"> &nbsp; 
    <input type="button" value="替换" onclick="edit_tool('replace')" class="input_button tip" title="<?php echo stripos($_SERVER['HTTP_USER_AGENT'], 'macintosh') ? 'Ctrl+Option+F':'Ctrl+H';?>"> &nbsp; 
    <input type="button" value="转到行" onclick="edit_tool('gotoline')" class="input_button tip" title="Ctrl+L"> &nbsp; 
	<input type="submit" value="保存" name="save" style="float: right;"  class="input_button input_primary"/>
</div>
</form>
<script src="https://s4.zstatic.net/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script src="https://s4.zstatic.net/ajax/libs/layer/3.1.1/layer.js"></script>
<script src="https://s4.zstatic.net/ajax/libs/ace/1.28.0/ace.js"></script>
<script src="https://s4.zstatic.net/ajax/libs/ace/1.28.0/ext-language_tools.js"></script>
<script src="https://s4.zstatic.net/ajax/libs/ace/1.28.0/ext-modelist.js"></script>
<script type="text/javascript">
ace.require("ace/ext/language_tools");
editor = ace.edit("file_content");
var modelist = ace.require("ace/ext/modelist")
var mode = modelist.getModeForPath($("#path").val()).mode
editor.session.setMode(mode);
editor.setTheme("ace/theme/chrome");
editor.setOptions({
    enableSnippets: true,
    enableLiveAutocompletion: true,
    showPrintMargin: false,
});
editor.commands.addCommand({
    name: "SaveFile",
    bindKey: { win: "Ctrl-S", mac: "Command-S" },
    exec: function(editor) { $('#file_content_form').submit(); },
    scrollIntoView: "cursor",
    multiSelectAction: "forEachLine"
});
editor.focus();
editor.gotoLine(1, 0);
$(document).ready(function(){
    var file_content_height = $(window).height()-195;
    $('#file_content').height(file_content_height > 500 ? file_content_height : 500);
    if(editor)
        editor.resize();
    $("#file_content_form").on("submit", function(){
        var file_content = editor.getSession().getValue();
        var character = $("#character").val();
        var path = $("#path").val();
        var ii = layer.load(2, {shade:[0.1,'#fff']});
        $.ajax({
            type : 'POST',
            url : './?c=editor&path=' + encodeURIComponent(path),
            data : {file_content:file_content, character:character},
            dataType : 'json',
            success : function(data) {
                layer.close(ii);
                if(data.code == 0){
                    layer.msg('保存成功！', {icon: 1,time: 900});
                }else{
                    layer.msg(data.msg, {icon: 2})
                }
            },
            error:function(){
                layer.close(ii);
                layer.msg('服务器错误');
            }
        });
        return false;
    })
})

var edit_tool = function(type)
{
	if(type == 'find' || type == 'replace')
	{
		if(!editor.searchBox || !editor.searchBox.active || editor.action_name != type)
			editor.execCommand(type);
		else
			editor.searchBox.hide();
		editor.action_name = type;
	}
	else
		editor.execCommand(type);
}
</script>
</body>
</html>