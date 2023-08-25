<?php
if(!defined('DIR_INIT'))exit();

if(isset($_POST['username']) && isset($_POST['password'])){
	if(!checkRefererHost())exit();
	$username = trim($_POST['username']);
	$password = trim($_POST['password']);

	if(empty($username) || empty($password)){
		echo_json(['code'=>-1, 'msg'=>'用户名或密码不能为空']);
	}
	if($username === $conf['admin_username'] && md5($password) === $conf['admin_password']){
		$conf['admin_session'] = getSid();
		$conf['admin_lastlogin'] = date('Y-m-d H:i:s');
		if($CACHE->set('config', $conf)){
			setcookie("admin_session", $conf['admin_session'], time() + 2592000);
			echo_json(['code'=>0]);
		}else{
			echo_json(['code'=>-1, 'msg'=>'登录失败，可能无文件写入权限']);
		}
	}else{
		echo_json(['code'=>-1, 'msg'=>'用户名或密码错误']);
	}
}

header('Content-Type: text/html; charset=UTF-8');

if($islogin) exit("<script language='javascript'>alert('您已登录！');window.location.href='./?c=admin';</script>");

include PAGE_ROOT.'header.php';
?>


	<div class="container" id="main">
		<div class="row mt-3">
			<div class="col-12 col-sm-11 col-md-9 col-lg-7 col-xl-5 center-block">
				<div class="card border-secondary shadow rounded text-center">
					<div class="card-header">后台管理登录</div>
					<div class="card-body bg-light">
						<form role="form" onsubmit="return submitlogin()">
							<div class="form-group">
								<div class="input-group">
									<div class="input-group-prepend">
										<div class="input-group-text"><span class="fa fa-user"></span></div>
									</div>
									<input type="text" class="form-control" name="username" placeholder="用户名">
								</div>
							</div>
							<div class="form-group">
								<div class="input-group">
									<div class="input-group-prepend">
										<div class="input-group-text"><span class="fa fa-lock"></span></div>
									</div>
									<input type="password" class="form-control" name="password" placeholder="密码">
								</div>
							</div>
							<div class="form-group">
								<input type="submit" value="立即登录" class="btn btn-primary btn-block"/>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		
	</div>

<?php include PAGE_ROOT.'footer.php';?>
<script src="./_dir/static/js/admin.js"></script>
</body>
</html>