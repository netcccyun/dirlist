<?php
if(!defined('DIR_INIT'))exit();
header('Content-Type: text/html; charset=UTF-8');

include PAGE_ROOT.'header.php';
?>
<style>
	.btn-2 { padding:4px 10px;font-size:small; }
	.footer-action li { margin-bottom:0.5rem; }
	ul.footer-action { margin-bottom:0.18rem; }
	.list-inline-item:not(:last-child) {
		margin-right: 0;
	}
</style>
	<div class="container" id="main">
		<div class="row mt-3">
			<div class="col-12">
<?php if($errmsg){?>
<div class="card border-warning mb-3">
  <div class="card-header bg-warning">提示信息</div>
  <div class="card-body bg-light">
    <h5 class="card-title"><?php echo $errmsg?></h5>
	<a href="./" class="btn btn-primary">返回首页</a>
  </div>
</div>
</div></div></div>
<?php exit;}

if($conf['announce']){?>
	<div class="card-body" id="msg">
		<i class="fa fa-volume-up mr-2"></i><?php echo $conf['announce']?>
	</div>
<?php
}

if($c=='search'){?>
				<p>
					<b><?php echo $s?></b> 的搜索结果 (<?php echo count($r['list']);?>)
				</p>
<?php } else { ?>
<input type="hidden" id="dir" value="<?php echo $r['dir']?>">
				<p>
					当前位置：<?php
foreach($r['navi'] as $item){
echo '<a href="'.$item['src'].'">'.$item['name'].'</a> / ';
}
?>
				</p>
<?php } ?>
			</div>
		</div>
<?php
if(!$islogin && !empty($r['passwd']) && (!isset($_COOKIE['dir_passwd']) || $_COOKIE['dir_passwd']!==md5($r['passwd']))){ ?>
		<div class="row mt-3">
			<div class="col-12 col-sm-11 col-md-9 col-lg-7 col-xl-5 center-block">
				<div class="card border-success shadow rounded">
					<div class="card-header text-center">当前目录已加密</div>
					<div class="card-body bg-light">
						<form role="form" onsubmit="return submitpasswd()">
							<div class="form-group">
								<div class="input-group">
									<input type="text" class="form-control" name="passwd" placeholder="请输入目录访问密码" autocomplete="off" required>
									<div class="input-group-append">
										<input type="submit" value="进入" class="btn btn-primary btn-block"/>
									</div>
								</div>
							</div>
							<?php if($r['parent']){?><div class="form-group">
								<a href="<?php echo $r['parent']?>"><<返回上级</a>
							</div><?php } ?>
						</form>
					</div>
				</div>
			</div>
		</div>
<?php }else{?>
		<div class="row mt-2"><div class="col-12">
			<?php if($islogin){?>
			<ul class="list-inline footer-action">
                <li class="list-inline-item"><a class="btn btn-sm btn-outline-success btn-2" onclick="admin_upload()"><i class="fa fa-cloud-upload"></i> 上传</a></li>
                <li class="list-inline-item"><a class="btn btn-sm btn-outline-info btn-2" onclick="admin_create()"><i class="fa fa-plus-square"></i> 新建</a></li>
				<li class="list-inline-item"><a class="btn btn-sm btn-outline-danger btn-2" onclick="admin_delete_batch()"><i class="fa fa-trash"></i> 删除</a></li>
				<li class="list-inline-item"><a class="btn btn-sm btn-outline-primary btn-2" onclick="admin_addclip_batch('copy')"><i class="fa fa-copy"></i> 复制</a></li>
				<li class="list-inline-item"><a class="btn btn-sm btn-outline-primary btn-2" onclick="admin_addclip_batch('cut')"><i class="fa fa-cut"></i> 剪切</a></li>
				<li class="list-inline-item"><a class="btn btn-sm btn-outline-primary btn-2" onclick="admin_paste()"><i class="fa fa-paste"></i> 粘贴</a></li>
				<li class="list-inline-item"><a class="btn btn-sm btn-outline-primary btn-2" onclick="admin_compress()"><i class="fa fa-file-zip-o"></i> 压缩</a></li>
				<li class="list-inline-item"><a class="btn btn-sm btn-outline-primary btn-2" onclick="admin_secret()"><i class="fa fa-lock"></i> 密码</a></li>
            </ul><?php }?>
			<table class="table table-hover dirlist" id="list">
				<thead>
					<tr>
					<?php if($islogin){?><th style="width:3%" class="custom-checkbox-header">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="js-select-all-items" onclick="checkbox_toggle(this)">
                            <label class="custom-control-label" for="js-select-all-items"></label>
                        </div>
					</td><?php }?>
					<th>文件名</th>
					<th class="d-none d-lg-table-cell"></th>
					<th class="d-none d-md-table-cell">修改时间</th>
					<th>大小</th>
					<th class="d-none d-md-table-cell">操作</th>
					</tr>
				</thead>
				<tbody>
<?php if($r['parent']){?>
					<tr>
						<?php if($islogin){?><td></td><?php }?>
						<td>
							<a class="fname" href="<?php echo $r['parent']?>"><i class="fa fa-level-up fa-fw"></i> ..</a>
						</td>
						<td class="d-none d-lg-table-cell">
						</td>
						<td class="d-none d-md-table-cell">-</td>
						<td>-</td>
						<td class="d-none d-md-table-cell">
						</td>
					</tr>
<?php }
foreach($r['list'] as $item) {
?>
					<tr>
						<?php if($islogin){?><td class="custom-checkbox-td">
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="<?php echo $item['name']; ?>" name="file[]" value="<?php echo $item['name']; ?>">
								<label class="custom-control-label" for="<?php echo $item['name']; ?>"></label>
							</div>
                        </td><?php }?>
						<td>
							<a class="fname" href="<?php echo $item['src']?>" title="<?php echo $c=='search'?'/'.$item['path']:$item['name']?>"><i class="fa <?php echo $item['icon']?> fa-fw"></i> <?php echo $c=='search'?'/'.$item['path']:$item['name']?></a>
						</td>
						<td class="d-none d-lg-table-cell fileinfo">
						<?php if($item['type'] == 'file'){ ?>
							<?php if($conf['file_hash'] == '1'){ ?><a href="javascript:;" title="查看文件hash" onclick="filehash('<?php echo $item['path']; ?>')"><i class="fa fa-info-circle" aria-hidden="true"></i></a><?php } ?>
							<a href="javascript:;" onclick="qrcode('<?php echo $item['src']; ?>')" title="显示二维码"><i class="fa fa-qrcode" aria-hidden="true"></i></a>
						<?php } ?>
						</td>
						<td class="d-none d-md-table-cell"><?php echo $item['mtime']; ?></td>
						<td><?php echo $item['size_format']; ?></td>
						<td class="d-none d-md-table-cell">
							<?php if($item['type'] == 'file'){ ?>
								<a href="javascript:;" class="btn btn-sm btn-outline-secondary" title="复制链接" onclick="copy('<?php echo $item['src']; ?>')"><i class="fa fa-link fa-fw"></i></a>
								<a href="<?php echo $item['src']; ?>" class="btn btn-sm btn-outline-primary" title="点击下载"><i class="fa fa-download fa-fw"></i></a>
								<?php if($item['view_type'] == 'image'){ ?><a class="btn btn-sm btn-outline-info" title="点此查看" href="javascript:;" onclick="view_image('<?php echo $item['src']; ?>')"><i class="fa fa-eye fa-fw"></i></a>
								<?php }elseif($item['view_type'] == 'audio'){ ?><a class="btn btn-sm btn-outline-info" title="点此播放" href="javascript:;" onclick="view_audio('<?php echo $item['path']; ?>')"><i class="fa fa-play-circle fa-fw"></i></a>
								<?php }elseif($item['view_type'] == 'video'){ ?><a class="btn btn-sm btn-outline-info" title="点此播放" href="javascript:;" onclick="view_video('<?php echo $item['name']; ?>','<?php echo $item['path']; ?>')"><i class="fa fa-play-circle fa-fw"></i></a>
								<?php }elseif($item['view_type'] == 'office'){ ?><a class="btn btn-sm btn-outline-info" title="点此查看" href="javascript:;" onclick="view_office('<?php echo $item['name']; ?>','<?php echo $item['src']; ?>')"><i class="fa fa-eye fa-fw"></i></a>
								<?php }elseif($item['view_type'] == 'markdown'){ ?><a class="btn btn-sm btn-outline-info" title="点此查看" href="javascript:;" onclick="view_markdown('<?php echo $item['name']; ?>','<?php echo $item['path']; ?>')"><i class="fa fa-eye fa-fw"></i></a>
								<?php }elseif($item['view_type'] == 'text'){ ?><a class="btn btn-sm btn-outline-info" title="点此查看" href="javascript:;" onclick="view_text('<?php echo $item['name']; ?>','<?php echo $item['path']; ?>')"><i class="fa fa-eye fa-fw"></i></a>
								<?php } ?>
							<?php } ?>
							<?php if($islogin){?><button type="button" class="btn btn-sm btn-outline-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="管理操作"></button>
							<div class="dropdown-menu">
								<a class="dropdown-item" href="javascript:;" onclick="admin_delete('<?php echo $item['name']; ?>','<?php echo $item['type']; ?>')"><i class="fa fa-trash fa-fw"></i> 删除</a>
								<a class="dropdown-item" href="javascript:;" onclick="admin_addclip('copy','<?php echo $item['name']; ?>')"><i class="fa fa-copy fa-fw"></i> 复制</a>
								<a class="dropdown-item" href="javascript:;" onclick="admin_addclip('cut','<?php echo $item['name']; ?>')"><i class="fa fa-cut fa-fw"></i> 剪切</a>
								<a class="dropdown-item" href="javascript:;" onclick="admin_rename('<?php echo $item['name']; ?>')"><i class="fa fa-pencil-square-o fa-fw"></i> 重命名</a>
								<?php if($item['ext'] == 'zip'){?><a class="dropdown-item" href="javascript:;" onclick="admin_uncompress('<?php echo $item['name']; ?>')"><i class="fa fa-file-zip-o fa-fw"></i> 解压缩</a><?php } ?>
							</div><?php } ?>
						</td>
					</tr>
<?php
}
?>
				</tbody>
			</table>
		</div></div>
<?php
if($conf['readme_md'] == 1 && $r['readme_md']){
	$content = file_get_contents($r['readme_md']);
	if($content){
		require SYSTEM_ROOT.'Parsedown.class.php';
		$Parsedown = new Parsedown();
		$content = $Parsedown->text($content);
		$content = str_replace('[x]','<input type="checkbox" checked>',$content);
		$content = str_replace('[ ]','<input type="checkbox">',$content);
?>
		<div class="card mt-1">
			<div class="card-header">
			README.md
			</div>
			<div class="card-body">
				<div class="markdown-body">
                    <?php echo $content; ?>
                </div>
			</div>
		</div>
<?php	}
}
}
?>
	</div>
<?php include PAGE_ROOT.'footer.php';?>
<script src="./_dir/static/js/main.js?v=<?php echo VERSION?>"></script>
</body>
</html>