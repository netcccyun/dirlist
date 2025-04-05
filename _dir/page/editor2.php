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
<textarea id="content" style="display:none"><?php echo $file_content;?></textarea>
<div id="file_content"></div>
<div class="title" style="padding: 6px 17px;">
    <input type="button" value="查找" onclick="edit_tool('find')" class="input_button tip" title="Ctrl+F"> &nbsp; 
    <input type="button" value="替换" onclick="edit_tool('replace')" class="input_button tip" title="<?php echo stripos($_SERVER['HTTP_USER_AGENT'], 'macintosh') ? 'Ctrl+Option+F':'Ctrl+H';?>"> &nbsp; 
    <input type="button" value="转到行" onclick="edit_tool('gotoline')" class="input_button tip" title="Ctrl+L"> &nbsp; 
    <input type="button" value="格式化" onclick="edit_tool('format')" class="input_button tip" title="Ctrl+Shift+F"> &nbsp;
    <input type="button" value="切换主题" onclick="edit_tool('theme')" class="input_button tip" title="切换主题"> &nbsp;
	<input type="submit" value="保存" name="save" style="float: right;"  class="input_button input_primary"/>
</div>
</form>
<script src="<?php echo $cdnpublic?>jquery/3.6.1/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>monaco-editor/0.52.2/min/vs/loader.min.js"></script>
<script type="text/javascript">
var editor;
var currentTheme = 'vs-light';
require.config({ paths: { 'vs': '<?php echo $cdnpublic?>monaco-editor/0.52.2/min/vs' }, 'vs/nls': {availableLanguages: {'*': 'zh-cn'}}});
require(['vs/editor/editor.main'], function () {

    function getLanguageFromFilename(filename) {
        const registered = monaco.languages.getLanguages();
        const ext = filename.split('.').pop().toLowerCase();

        for (const lang of registered) {
            if (lang.extensions && lang.extensions.includes('.' + ext)) {
                return lang.id;
            }
        }
        if (ext == "tpl") return "html";
        return 'plaintext';
    }

    // 初始化编辑器
    const initialLang = getLanguageFromFilename($("#path").val());
    editor = monaco.editor.create(document.getElementById('file_content'), {
        value: $("#content").val(),
        language: initialLang,
        theme: currentTheme,
        smoothScrolling: true,
        cursorBlinking: "smooth",
        cursorSmoothCaretAnimation: true,
        wordWrap: true,
        autoIndent: true,
        automaticLayout: true,
        enableSnippets: true,
        enableLiveAutocompletion: true,
    });

    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, function () {
        $("#file_content_form").submit();
    })
});
$(document).ready(function(){
    var file_content_height = $(window).height()-165;
    $('#file_content').height(file_content_height > 500 ? file_content_height : 500);
    if(editor)
        editor.layout();
    $("#file_content_form").on("submit", function(){
        var file_content = editor.getValue();
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
	if(type == 'find')
	{
        editor.getAction('actions.find').run();
	}
    else if(type == 'replace')
    {
        editor.getAction('editor.action.startFindReplaceAction').run();
    }
    else if(type == 'gotoline')
    {
        editor.focus();
        editor.getAction('editor.action.gotoLine').run();
    }
    else if(type == 'format')
    {
        editor.getAction('editor.action.formatDocument').run();
    }
    else if(type == 'theme')
    {
        currentTheme = currentTheme === 'vs-light' ? 'vs-dark' : 'vs-light';
        monaco.editor.setTheme(currentTheme);
    }
}
</script>
</body>
</html>