var pageurl = window.location.protocol + '//' + window.location.host + window.location.pathname;

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

function view_audio(path){
	var apiurl = './?c=audio&path=' + encodeURIComponent(path);
	layer.open({
        type: 2,
        shade: 0.6,
		title:false,
	  	area: ['60%', '88px'],
        shadeClose: true,
	  	content: apiurl
	});
}

function view_video(name, path){
	var apiurl = './?c=video&path=' + encodeURIComponent(path);
	layer.open({
        type: 2,
        shade: 0.6,
		title: '视频播放器 - ' + name,
	  	area: ['68%', '78%'],
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
