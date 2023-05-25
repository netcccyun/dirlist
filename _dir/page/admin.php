<?php
if(!defined('DIR_INIT'))exit();

if(isset($_GET['do'])){
	if(!checkRefererHost())exit();
	if($_GET['do'] == 'set'){
		foreach($_POST as $key=>$value){
			$conf[$key] = $value;
		}
		if($CACHE->set('config', $conf)){
			echo_json(['code'=>0, 'msg'=>'设置保存成功！']);
		}else{
			echo_json(['code'=>-1, 'msg'=>'保存失败，可能无文件写入权限']);
		}
	}
	elseif($_GET['do'] == 'account'){
		$username=trim($_POST['username']);
		$oldpwd=trim($_POST['oldpwd']);
		$newpwd=trim($_POST['newpwd']);
		$newpwd2=trim($_POST['newpwd2']);
		if(empty($username))echo_json(['code'=>-1, 'msg'=>'用户名不能为空！']);
		$conf['admin_username'] = $username;
		$msg = '修改成功！';
		if(!empty($newpwd) && !empty($newpwd2)){
			if(md5($oldpwd)!=$conf['admin_password'])echo_json(['code'=>-1, 'msg'=>'旧密码不正确！']);
			if($newpwd!=$newpwd2)echo_json(['code'=>-1, 'msg'=>'两次输入的密码不一致！']);
			$conf['admin_password'] = md5($newpwd);
			$conf['admin_session'] = null;
			$msg = '修改成功！请重新登录';
		}
		if($CACHE->set('config', $conf)){
			echo_json(['code'=>0, 'msg'=>$msg]);
		}else{
			echo_json(['code'=>-1, 'msg'=>'修改失败，可能无文件写入权限']);
		}
	}
	elseif($_GET['do'] == 'clearindexes'){
		$CACHE->delete('indexes');
		echo_json(['code'=>0]);
	}
	elseif($_GET['do'] == 'logout'){
		setcookie("admin_session", "", time() - 2592000);
		header('Content-Type: text/html; charset=UTF-8');
		exit("<script language='javascript'>alert('退出登录成功！');window.location.href='./';</script>");
	}
}

header('Content-Type: text/html; charset=UTF-8');
include PAGE_ROOT.'header.php';
?>


	<div class="container" id="main">
		<div class="row mt-3">
			<div class="col-md-12 col-lg-10 col-xl-8 center-block">
				<ul class="nav nav-tabs border-0" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<a class="nav-link active" id="set-tab" data-toggle="tab" href="#set" role="tab" aria-controls="set" aria-selected="true">网站设置</a>
				</li>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="account-tab" data-toggle="tab" href="#account" role="tab" aria-controls="account" aria-selected="false">账号设置</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="./?c=admin&do=logout" onclick="return confirm('是否确定退出登录？')">退出登录</a>
				</li>
				</ul>
			<div class="card">
			<div class="card-body shadow-sm">
				<div class="tab-content" id="myTabContent">
				<div class="tab-pane fade show active" id="set" role="tabpanel" aria-labelledby="set-tab">
  <form onsubmit="return saveSetting(this)" method="post" role="form">
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">网站标题</label>
	  <div class="col-sm-9"><input type="text" name="title" value="<?php echo $conf['title']; ?>" class="form-control" required/></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">关键字</label>
	  <div class="col-sm-9"><input type="text" name="keywords" value="<?php echo $conf['keywords']; ?>" class="form-control"/></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">网站描述</label>
	  <div class="col-sm-9"><input type="text" name="description" value="<?php echo $conf['description']; ?>" class="form-control"/></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">首页公告</label>
	  <div class="col-sm-9"><textarea class="form-control" name="announce" rows="3" placeholder="不填写则不显示首页公告"><?php echo htmlspecialchars($conf['announce'])?></textarea></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">底部代码</label>
	  <div class="col-sm-9"><textarea class="form-control" name="footer" rows="3" placeholder="可填写备案号、统计代码等"><?php echo htmlspecialchars($conf['footer'])?></textarea></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">顶部导航链接</label>
	  <div class="col-sm-9"><textarea class="form-control" name="nav" rows="3" placeholder=""><?php echo $conf['nav']?></textarea><font color="green">填写格式：链接文字*链接地址|链接文字*链接地址</font></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">中文文件名编码</label>
	  <div class="col-sm-9"><select class="form-control" name="name_encode" default="<?php echo $conf['name_encode']?>"><option value="utf8">UTF-8</option><option value="gbk">GBK</option></select><font color="green">当出现中文文件名乱码的情况下可以修改此项</font></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">开启文件hash显示</label>
	  <div class="col-sm-9"><select class="form-control" name="file_hash" default="<?php echo $conf['file_hash']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">开启README.md显示</label>
	  <div class="col-sm-9"><select class="form-control" name="readme_md" default="<?php echo $conf['readme_md']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">文件索引缓存</label>
	  <div class="col-sm-9"><select class="form-control" name="cache_indexes" default="<?php echo $conf['cache_indexes']?>"><option value="0">关闭</option><option value="1">缓存1小时</option><option value="2">缓存6小时</option><option value="3">缓存24小时</option></select><font color="green">文件和目录数量多的情况下建议开启，可提升搜索速度。开启后如文件有变动需手动清除缓存</font></div>
	</div>
	<div class="form-group row">
	  <div class="offset-sm-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/>
	  <?php if($conf['cache_indexes']>0){?>
<hr/><button type="button" class="btn btn-warning btn-block" onclick="clearIndexes()"><i class="fa fa-trash"></i> 清除文件索引缓存</button>
<?php }?>
	 </div>
	</div>
  </form>
				</div>
				<div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
  <form onsubmit="return setAccount(this)" method="post" role="form">
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">用户名</label>
	  <div class="col-sm-9"><input type="text" name="username" value="<?php echo $conf['admin_username']; ?>" class="form-control" required/></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">旧密码</label>
	  <div class="col-sm-9"><input type="password" name="oldpwd" value="" class="form-control" placeholder="请输入当前的管理员密码"/></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">新密码</label>
	  <div class="col-sm-9"><input type="password" name="newpwd" value="" class="form-control" placeholder="不修改请留空"/></div>
	</div>
	<div class="form-group row">
	  <label class="col-sm-3 col-form-label">重输密码</label>
	  <div class="col-sm-9"><input type="password" name="newpwd2" value="" class="form-control" placeholder="不修改请留空"/></div>
	</div>
	<div class="form-group row">
	  <div class="offset-sm-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/>
	 </div>
	</div>
  </form>
				</div>
				</div>
			</div>
			</div>
			</div>
		</div>

	</div>

<?php include PAGE_ROOT.'footer.php';?>
<script src="./_dir/static/js/admin.js"></script>
</body>
</html>