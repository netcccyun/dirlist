var items = $("select[default]");
for (i = 0; i < items.length; i++) {
	$(items[i]).val($(items[i]).attr("default")||0);
}

function submitlogin(){
    var username = $("input[name='username']").val();
	var password = $("input[name='password']").val();
    if(username=='' || password==''){layer.alert('用户名或密码不能为空！');return false;}
    var ii = layer.load(2, {shade:[0.1,'#fff']});
      $.ajax({
      type : 'POST',
      url : './?c=login',
      data: {username:username, password:password},
      dataType : 'json',
      success : function(data) {
        layer.close(ii);
        if(data.code == 0){
          layer.msg('登录成功，正在跳转', {icon: 1,shade: 0.01,time: 15000});
          window.location.href='./?c=admin';
        }else{
          layer.alert(data.msg, {icon: 2});
        }
      },
      error:function(){
        layer.close(ii);
        layer.msg('服务器错误');
      }
    });
    return false;
}

function saveSetting(obj){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : './?c=admin&do=set',
		data : $(obj).serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {
					icon: 1,
					closeBtn: false
				}, function(){
				  window.location.reload()
				});
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(){
            layer.close(ii);
			layer.msg('服务器错误');
		}
	});
	return false;
}

function setAccount(obj){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : './?c=admin&do=account',
		data : $(obj).serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {
					icon: 1,
					closeBtn: false
				}, function(){
				  window.location.reload()
				});
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(){
            layer.close(ii);
			layer.msg('服务器错误');
		}
	});
	return false;
}

function clearIndexes(){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : './?c=admin&do=clearindexes',
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
                layer.msg('清空文件索引成功！', {icon:1});
            }else{
                layer.msg('清空文件索引失败', {icon: 2});
            }
		},
		error:function(){
            layer.close(ii);
			layer.msg('服务器错误');
		}
	});
	return false;
}
