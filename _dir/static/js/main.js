var pageurl = window.location.protocol + '//' + window.location.host + window.location.pathname;
var page_reload = false;

function copy(src){
    var url = pageurl + src.slice(2);
    new clipBoard(document.getElementById('list'), {
        beforeCopy: function() {
            
        },
        copy: function() {
            return url;
        },
        afterCopy: function() {
			layer.msg('链接已复制！', {icon:1, time:1000});
        }
    });
}

function qrcode(src){
    var url = pageurl + src.slice(2);
    var content = '<center style="margin:15px 10px 5px 10px;" id="qrcode"></center>';
	layer.open({
		type: 1,
        area: '220px',
        title: false,
	  	content: content,
        closeBtn: 2,
        shadeClose: true,
        success: function(layero, index){
            $('#qrcode').qrcode({
                text: url,
                width: 200,
                height: 200,
                foreground: "#000000",
                background: "#ffffff",
                typeNumber: -1
            });
        }
	});
}

function filehash(path){
    var ii = layer.load();
    $.post("./?c=hash", {path:path}, function(data){
        layer.close(ii);
		if(data.code == 0){
			layer.open({
  				title: data.name,
                shadeClose: true,
  				area: ['400px', 'auto'],
			  	content: '<b>md5: </b>' + data.md5 + '<br /><b>sha1: </b>' + data.sha1
			});
		}else{
			layer.alert(data.msg, {icon:7});
		}
	}, 'json');
}

function view_image(src){
    var resourcesUrl = pageurl + src.slice(2);

    var img = new Image();
    img.onload = function () {//避免图片还未加载完成无法获取到图片的大小。
        //避免图片太大，导致弹出展示超出了网页显示访问，所以图片大于浏览器时下窗口可视区域时，进行等比例缩小。
        var max_height = $(window).height() - 100;
        var max_width = $(window).width();

        //rate1，rate2，rate3 三个比例中取最小的。
        var rate1 = max_height / img.height;
        var rate2 = max_width / img.width;
        var rate3 = 1;
        var rate = Math.min(rate1, rate2, rate3);
        //等比例缩放
        var imgHeight = img.height * rate; //获取图片高度
        var imgWidth = img.width * rate; //获取图片宽度

        var imgHtml = "<img src='" + resourcesUrl + "' width='" + imgWidth + "px' height='" + imgHeight + "px'/>";
        //弹出层
        layer.open({
            type:1,
            shade: 0.6,
            anim: 1,
            title: false,
            area: ['auto', 'auto'],
            shadeClose: true,
            content: imgHtml
        });
    }
    img.src = resourcesUrl;
}

function view_audio(src){
    if(audio_list == null || audio_list.length == 0){
        layer.msg('当前列表没有音频文件', {icon:2, time:1000});
        return;
    }
    $.each(audio_list, function(key, item){
        item.artist = 'artist';
        item.cover = './_dir/static/images/music.png';
    });
    var index = audio_list.findIndex(item => item.url == src);
    if(index == -1){
        layer.msg('音频文件不存在', {icon:2, time:1000});
        return;
    }
    layer.closeAll();
    if(!aplayer){
        aplayer = new APlayer({
            container: document.getElementById('aplayer'),
            fixed: true,
            loop: 'none',
            audio: audio_list
        });
    }
    aplayer.setMode('normal');
    aplayer.list.switch(index);
    aplayer.play();
	/*var apiurl = './?c=audio&path=' + encodeURIComponent(path);
	layer.open({
        type: 2,
        shade: 0.6,
		title:false,
	  	area: [$(window).width() > 768 ? '60%' : '95%', '88px'],
        shadeClose: true,
	  	content: apiurl
	});*/
}

function view_video(name, path){
    if(aplayer && aplayer.audio && !aplayer.audio.paused){
        aplayer.pause();
    }
	var apiurl = './?c=video&path=' + encodeURIComponent(path);
	layer.open({
        type: 2,
        shade: 0.6,
		title: '视频播放器 - ' + name,
	  	area: [$(window).width() > 768 ? '68%' : '95%', $(window).width() > 768 ? '78%' : '280px'],
        shadeClose: true,
	  	content: apiurl
	});
}

function view_markdown(name, path){
	var apiurl = './?c=markdown&path=' + encodeURIComponent(path);
	layer.open({
		title:'MarkDown查看器 - ' + name,
	  	type: 2, 
	  	area: ['100%', '100%'],
	  	content: apiurl
	});
}

function view_text(name, path){
	var apiurl = './?c=text&path=' + encodeURIComponent(path);
	layer.open({
		title:'文本查看器 - ' + name,
	  	type: 2, 
	  	area: ['100%', '100%'],
	  	content: apiurl
	});
}

function view_office(name, src){
	var url = pageurl + src.slice(2);
	var apiurl = 'https://view.officeapps.live.com/op/view.aspx?src=' + encodeURIComponent(url);
	layer.open({
		title: name,
	  	type: 2, 
	  	area: ['100%', '100%'],
	  	content: apiurl
	});
}

function submitpasswd(){
    var dir = $("#dir").val();
    var passwd = $("input[name='passwd']").val();
    var ii = layer.load();
    $.ajax({
        type : 'POST',
        url : './?dir=' + encodeURIComponent(dir),
        data : {passwd:passwd},
        dataType : 'json',
        success : function(data) {
            layer.close(ii);
            if(data.code == 0){
                window.location.reload();
            }else{
                layer.msg(data.msg, {icon:2, time:1000});
            }
        }
    });
    return false;
}

function tinyview(obj){
    var info = $(obj).parent().parent().find('template').html();
    var html = '<div class="card" style="height: 100%;"><div class="card-body text-center">' + info + '</div></div>';
    layer.open({
        type: 1,
        shade: 0.6,
        title:false,
        area: ['340px', '180px'],
        shadeClose: true,
        content: html
    });
}

function change_checkboxes(e, t) { for (var n = e.length - 1; n >= 0; n--) e[n].checked = "boolean" == typeof t ? t : !e[n].checked }
function get_checkboxes() { for (var e = document.getElementsByName("file[]"), t = [], n = e.length - 1; n >= 0; n--) (e[n].type = "checkbox") && t.push(e[n]); return t }
function checkbox_toggle(obj) { change_checkboxes(get_checkboxes(), obj.checked) }
function get_checked_values() {
    var chk_value = new Array();
    $("input[name='file[]']:checked").each(function(){
        chk_value.push($(this).val());
    })
    return chk_value;
}

function admin_upload(){
    var dir = $("#dir").val();
    if($(window).width() > 768){
        layer.open({
            type:2,
            title: false,
            area: ["720px",";max-height:100%;min-height:490px"],
            content: './?c=upload&path=' + encodeURIComponent(dir),
            end: function(){
                if(page_reload){
                    window.location.reload()
                }
            }
        });
    }else{
        layer.open({
            type:2,
            title: '上传',
            shadeClose: true,
            skin: 'layui-layer-molv',
            area: ["100%",";max-height:100%;min-height:490px"],
            content: './?c=upload&path=' + encodeURIComponent(dir),
            end: function(){
                if(page_reload){
                    window.location.reload()
                }
            }
        });
    }
}

function admin_create(){
    var dir = $("#dir").val();
    layer.open({
        area: ['360px'],
        title: '创建文件/文件夹',
        content: '<div><label for="newfile">文件类型 </label><br/><div class="custom-control custom-radio custom-control-inline"><input type="radio" id="customRadioInline1" name="newfile" value="file" class="custom-control-input"><label class="custom-control-label" for="customRadioInline1">文件</label></div><div class="custom-control custom-radio custom-control-inline"><input type="radio" id="customRadioInline2" name="newfile" value="folder" class="custom-control-input" checked=""><label class="custom-control-label" for="customRadioInline2">文件夹</label></div></div><div class="mt-3"><label for="newfilename">创建名称 </label><br/><input type="text" name="newfilename" id="newfilename" value="" class="form-control" placeholder="文件/文件夹名称" autocomplete="off"></div>',
        btn: ['创建', '取消'],
        yes: function(){
            var newfile = $("input[name='newfile']:checked").val();
            var newfilename = $("input[name='newfilename']").val();
            if(newfilename == ''){
                $("input[name='newfilename']").focus();
                return;
            }
            var ii = layer.load();
            $.ajax({
                type : 'POST',
                url : './?c=filemgr',
                data : {do:'create', path:dir, type:newfile, name:newfilename},
                dataType : 'json',
                success : function(data) {
                    layer.close(ii);
                    if(data.code == 0){
                        window.location.reload()
                    }else{
                        layer.alert(data.msg, {icon:2});
                    }
                }
            });
        }
    });
}

function admin_secret(){
    var dir = $("#dir").val();
    var files = get_checked_values()
    if(files.length > 0){
        layer.alert('只支持设置当前目录的密码，请勿选中任何文件或文件夹', {icon:7});
        return;
    }
    var ii = layer.load();
    $.ajax({
        type : 'POST',
        url : './?c=filemgr',
        data : {do:'query_secret', path:dir},
        dataType : 'json',
        success : function(data) {
            layer.close(ii);
            if(data.code == 0){
                layer.open({
                    area: ['360px','240px'],
                    title: '设置目录密码访问',
                    content: '<div><div class="custom-control custom-radio custom-control-inline"><input type="radio" id="customRadioInline1" name="issecret" value="0" class="custom-control-input"><label class="custom-control-label" for="customRadioInline1">公开访问</label></div><div class="custom-control custom-radio custom-control-inline"><input type="radio" id="customRadioInline2" name="issecret" value="1" class="custom-control-input"><label class="custom-control-label" for="customRadioInline2">密码访问</label></div></div><div class="mt-3" id="passwd_frame" style="display:none"><label for="passwd">目录访问密码 </label><br/><input type="text" name="passwd" id="passwd" value="" class="form-control" autocomplete="off"></div>',
                    btn: ['确定', '取消'],
                    success: function(){
                        var issecret = data.data.issecret;
                        if(issecret){
                            $("#customRadioInline2").prop('checked', true);
                            $("#passwd_frame").show();
                            $("#passwd").attr('placeholder','填写后可重置密码');
                        }else{
                            $("#customRadioInline1").prop('checked', true);
                        }
                        $("input[name='issecret']").click(function(){
                            var issecret = $("input[name='issecret']:checked").val();
                            if(issecret=='1'){
                                $("#passwd_frame").show();
                            }else{
                                $("#passwd_frame").hide();
                            }
                        })
                    },
                    yes: function(){
                        var issecret = $("input[name='issecret']:checked").val();
                        var passwd = $("input[name='passwd']").val();
                        if(issecret == '1' && passwd == ''){
                            $("input[name='passwd']").focus();
                            return;
                        }
                        var ii = layer.load();
                        $.ajax({
                            type : 'POST',
                            url : './?c=filemgr',
                            data : {do:'set_secret', path:dir, issecret:issecret, passwd:passwd},
                            dataType : 'json',
                            success : function(data) {
                                layer.close(ii);
                                if(data.code == 0){
                                    layer.alert('修改成功', {icon:1}, function(){window.location.reload()});
                                }else{
                                    layer.alert(data.msg, {icon:2});
                                }
                            }
                        });
                    }
                });
            }else{
                layer.alert(data.msg, {icon:2});
            }
        }
    });
}

function admin_rename(name){
    var dir = $("#dir").val();
    layer.open({
        area: ['360px'],
        title: '重命名',
        content: '<div><input type="text" name="newname" value="'+name+'" class="form-control" placeholder="新的名称" autocomplete="off"></div>',
        btn: ['确定', '取消'],
        yes: function(){
            var newname = $("input[name='newname']").val();
            if(newname == ''){
                $("input[name='newname']").focus();
                return;
            }
            var ii = layer.load();
            $.ajax({
                type : 'POST',
                url : './?c=filemgr',
                data : {do:'rename', path:dir, oldname:name, newname:newname},
                dataType : 'json',
                success : function(data) {
                    layer.close(ii);
                    if(data.code == 0){
                        window.location.reload()
                    }else{
                        layer.alert(data.msg, {icon:2});
                    }
                }
            });
        }
    });
}

function admin_delete(name, type){
    var dir = $("#dir").val();
    var typename = type == 'file'?'文件':'文件夹';
    var confirmobj = layer.confirm('确实要删除此'+typename+'吗？删除后无法恢复', {
        btn: ['确定','取消'], icon:0, title: '删除'+typename
    }, function(){
        var files = new Array();
        files.push(name);
        var ii = layer.load();
        $.ajax({
            type : 'POST',
            url : './?c=filemgr',
            data : {do:'delete', path:dir, files:files},
            dataType : 'json',
            success : function(data) {
                layer.close(ii);
                if(data.code == 0){
                    layer.alert('成功删除了'+data.data+'个文件或目录', {icon:1}, function(){window.location.reload()});
                }else{
                    layer.alert(data.msg, {icon: 2});
                }
            }
        });
    }, function(){
        layer.close(confirmobj);
    });
}

function admin_delete_batch(){
    var dir = $("#dir").val();
    var files = get_checked_values()
    if(files.length == 0){
        layer.msg('未选中任何文件或文件夹', {time:1000});
        return;
    }
    var confirmobj = layer.confirm('确实要删除所选的'+files.length+'个文件或目录吗？删除后无法恢复', {
        btn: ['确定','取消'], icon:0, title: '批量删除'
    }, function(){
        var ii = layer.load();
        $.ajax({
            type : 'POST',
            url : './?c=filemgr',
            data : {do:'delete', path:dir, files:files},
            dataType : 'json',
            success : function(data) {
                layer.close(ii);
                if(data.code == 0){
                    layer.alert('成功删除了'+data.data+'个文件或目录', {icon:1}, function(){window.location.reload()});
                }else{
                    layer.alert(data.msg, {icon: 2});
                }
            }
        });
    }, function(){
        layer.close(confirmobj);
    });
}

function admin_addclip(op, name){
    var dir = $("#dir").val();
    var opname = op == 'cut'?'剪切':'复制';
    var files = new Array();
    files.push(name);
    var ii = layer.load();
    $.ajax({
        type : 'POST',
        url : './?c=filemgr',
        data : {do:'addclip', path:dir, op:op, files:files},
        dataType : 'json',
        success : function(data) {
            layer.close(ii);
            if(data.code == 0){
                layer.msg(opname+'成功，请点击粘贴按钮', {time:1000});
            }else{
                layer.alert(data.msg, {icon:2});
            }
        }
    });
}

function admin_addclip_batch(op){
    var dir = $("#dir").val();
    var opname = op == 'cut'?'剪切':'复制';
    var files = get_checked_values()
    if(files.length == 0){
        layer.msg('未选中任何文件或文件夹', {time:1000});
        return;
    }
    var ii = layer.load();
    $.ajax({
        type : 'POST',
        url : './?c=filemgr',
        data : {do:'addclip', path:dir, op:op, files:files},
        dataType : 'json',
        success : function(data) {
            layer.close(ii);
            if(data.code == 0){
                layer.msg(opname+'成功，请点击粘贴按钮', {time:1000});
            }else{
                layer.alert(data.msg, {icon:2});
            }
        }
    });
}

function admin_paste(){
    var dir = $("#dir").val();
    var ii = layer.load();
    $.ajax({
        type : 'POST',
        url : './?c=filemgr',
        data : {do:'paste', path:dir},
        dataType : 'json',
        success : function(data) {
            layer.close(ii);
            if(data.code == 0){
                var opname = data.data.op == 'cut'?'剪切':'复制';
                var count = data.data.count;
                layer.alert('成功'+opname+'了'+count+'个文件或目录', {icon:1}, function(){window.location.reload()});
            }else{
                layer.msg(data.msg, {icon:2, time:1500});
            }
        }
    });
}

function admin_compress(){
    var dir = $("#dir").val();
    var files = get_checked_values()
    if(files.length == 0){
        layer.msg('未选中任何文件或文件夹', {time:1000});
        return;
    }
    var name = 'archive.zip';
    if(files.length == 1) name = files[0] + '.zip';
    layer.open({
        area: ['360px'],
        title: '创建压缩包文件',
        content: '<div><label>压缩包文件名：</label><br/><input type="text" name="zipname" value="'+name+'" class="form-control" placeholder="" autocomplete="off"></div>',
        btn: ['确定', '取消'],
        yes: function(){
            var zipname = $("input[name='zipname']").val();
            if(zipname == ''){
                $("input[name='zipname']").focus();
                return;
            }
            var ii = layer.load();
            $.ajax({
                type : 'POST',
                url : './?c=filemgr',
                data : {do:'compress', path:dir, name:zipname, files:files},
                dataType : 'json',
                success : function(data) {
                    layer.close(ii);
                    if(data.code == 0){
                        layer.alert('压缩成功', {icon:1}, function(){window.location.reload()});
                    }else{
                        layer.alert(data.msg, {icon:2});
                    }
                }
            });
        }
    });
}

function admin_uncompress(name){
    var dir = $("#dir").val();
    if(dir == '.') dir = '/';
    else dir = '/'+dir;
    layer.open({
        area: ['360px'],
        title: '解压缩',
        content: '<div><label>解压到：</label><br/><input type="text" name="targetdir" value="'+dir+'" class="form-control" placeholder="" autocomplete="off"></div>',
        btn: ['确定', '取消'],
        yes: function(){
            var targetdir = $("input[name='targetdir']").val();
            if(targetdir == ''){
                $("input[name='targetdir']").focus();
                return;
            }
            var ii = layer.load();
            $.ajax({
                type : 'POST',
                url : './?c=filemgr',
                data : {do:'uncompress', path:dir, targetdir:targetdir, name:name},
                dataType : 'json',
                success : function(data) {
                    layer.close(ii);
                    if(data.code == 0){
                        layer.alert('解压缩成功', {icon:1}, function(){window.location.reload()});
                    }else{
                        layer.alert(data.msg, {icon:2});
                    }
                }
            });
        }
    });
}

function admin_edit(name, path){
	var apiurl = './?c=editor&path=' + encodeURIComponent(path);
	layer.open({
		title:'文本编辑器 - ' + name,
	  	type: 2, 
	  	area: ['100%', '100%'],
	  	content: apiurl
	});
}

window.addEventListener('message', function(e){
    if(e.data == 'reload'){
        page_reload = true
    }
});