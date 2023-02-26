<?php
if(!defined('DIR_INIT'))exit();
header('Content-Type: text/html; charset=UTF-8');

include PAGE_ROOT.'header.php';
?>
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

		<div class="row mt-2"><div class="col-12">
			<table class="table table-hover dirlist" id="list">
				<thead>
					<tr>
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
								<a href="javascript:;" class="btn btn-sm btn-outline-secondary" title="复制链接" onclick="copy('<?php echo $item['src']; ?>')"><i class="fa fa-copy fa-fw"></i></a>
								<a href="<?php echo $item['src']; ?>" class="btn btn-sm btn-outline-primary" title="点击下载"><i class="fa fa-download fa-fw"></i></a>
								<?php if($item['view_type'] == 'image'){ ?><a class="btn btn-sm btn-outline-info" title="点此查看" href="javascript:;" onclick="view_image('<?php echo $item['src']; ?>')"><i class="fa fa-eye fa-fw"></i></a>
								<?php }elseif($item['view_type'] == 'audio'){ ?><a class="btn btn-sm btn-outline-info" title="点此播放" href="javascript:;" onclick="view_audio('<?php echo $item['path']; ?>')"><i class="fa fa-play-circle fa-fw"></i></a>
								<?php }elseif($item['view_type'] == 'video'){ ?><a class="btn btn-sm btn-outline-info" title="点此播放" href="javascript:;" onclick="view_video('<?php echo $item['name']; ?>','<?php echo $item['path']; ?>')"><i class="fa fa-play-circle fa-fw"></i></a>
								<?php }elseif($item['view_type'] == 'office'){ ?><a class="btn btn-sm btn-outline-info" title="点此查看" href="javascript:;" onclick="view_office('<?php echo $item['name']; ?>','<?php echo $item['src']; ?>')"><i class="fa fa-eye fa-fw"></i></a>
								<?php }elseif($item['view_type'] == 'markdown'){ ?><a class="btn btn-sm btn-outline-info" title="点此查看" href="javascript:;" onclick="view_markdown('<?php echo $item['name']; ?>','<?php echo $item['path']; ?>')"><i class="fa fa-eye fa-fw"></i></a>
								<?php }elseif($item['view_type'] == 'text'){ ?><a class="btn btn-sm btn-outline-info" title="点此查看" href="javascript:;" onclick="view_text('<?php echo $item['name']; ?>','<?php echo $item['path']; ?>')"><i class="fa fa-eye fa-fw"></i></a>
								<?php } ?>
							<?php } ?>
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
?>
	</div>
<?php include PAGE_ROOT.'footer.php';?>
<script src="./_dir/static/js/main.js"></script>
</body>
</html>