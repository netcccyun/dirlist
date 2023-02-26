<?php

require './_dir/inc.php';

$c = isset($_GET['c'])?trim($_GET['c']):'home';

$x = new DirList();

switch($c){
    case 'hash':
        if(!checkRefererHost())exit('{"code":403}');
        $path = isset($_POST['path'])?trim($_POST['path']):'';
        try{
            $r = $x->get_file_hash($path);
        }catch(Exception $e){
            $r = ['code'=>-1, 'msg'=>$e->getMessage()];
        }
        echo_json($r);
        break;
    case 'video':
    case 'audio':
    case 'markdown':
    case 'text':
        if(!checkRefererHost())exit('Access Denied');
        $path = isset($_GET['path'])?trim($_GET['path']):'';
        try{
            $path = './'.$x->set_dir_path($path, true);
        }catch(Exception $e){
            $errmsg = $e->getMessage();
            sysmsg($errmsg);
        }
        $name = basename($path);
        $ext = $x->get_file_ext($path);
        $url = implode('/', array_map('rawurlencode', explode('/', $path)));
        include PAGE_ROOT.$c.'.php';
        break;
    case 'search':
        $s = isset($_GET['s'])?trim($_GET['s']):'';
        if($s == '') exit("<script language='javascript'>window.location.href='./';</script>");
        try{
            $list = $x->search_files($s);
            $r = ['list'=>$list];
        }catch(Exception $e){
            $errmsg = $e->getMessage();
        }
        $s = htmlspecialchars($s);
        include PAGE_ROOT.'home.php';
        break;
    case 'home':
        $dir = isset($_GET['dir'])?trim($_GET['dir']):'';
        try{
            $r = $x->list_dir($dir);
        }catch(Exception $e){
            $errmsg = $e->getMessage();
            $r = $x->list_dir();
        }
        include PAGE_ROOT.'home.php';
        break;
    case 'admin':
        include PAGE_ROOT.'admin.php';
        break;
    case 'login':
        include PAGE_ROOT.'login.php';
        break;
    default:
        break;
}

