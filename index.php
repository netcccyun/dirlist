<?php

require './_dir/inc.php';

$c = isset($_GET['c'])?trim($_GET['c']):'home';

$x = new DirList();

switch($c){
    case 'hash':
        if(!checkRefererHost())exit('{"code":403}');
        if($conf['file_hash'] != '1')echo_json(['code'=>-1,'msg'=>'未开启该功能']);
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
        if(!empty($r['passwd']) && isset($_POST['passwd'])){
            if($errmsg) echo_json(['code'=>-1, 'msg'=>$errmsg]);
            if(password_verify($_POST['passwd'], $r['passwd'])){
                setcookie('dir_passwd', md5($r['passwd']));
                echo_json(['code'=>0]);
            }else{
                echo_json(['code'=>-1, 'msg'=>'目录访问密码错误']);
            }
        }
        include PAGE_ROOT.'home.php';
        break;
    case 'admin':
        if(!$islogin) exit("<script language='javascript'>window.location.href='./?c=login';</script>");
        include PAGE_ROOT.'admin.php';
        break;
    case 'upload':
        if(!$islogin) exit("<script language='javascript'>window.location.href='./?c=login';</script>");
        $path = isset($_GET['path'])?trim($_GET['path']):'';
        try{
            $path = $x->set_dir_path($path);
        }catch(Exception $e){
            sysmsg($e->getMessage());
        }
        include PAGE_ROOT.'upload.php';
        break;
    case 'filemgr':
        if(!$islogin) exit("<script language='javascript'>window.location.href='./?c=login';</script>");
        $path = isset($_POST['path'])?trim($_POST['path']):'';
        $do = isset($_POST['do'])?trim($_POST['do']):echo_json(['code'=>-1, 'msg'=>'param error']);
        try{
            $path = $x->set_dir_path($path);
        }catch(Exception $e){
            echo_json(['code'=>-1, 'msg'=>$e->getMessage()]);
        }
        $mgr = new FileMgr($path);
        if(!method_exists($mgr, $do))echo_json(['code'=>-1, 'msg'=>'action error']);
        try{
            $result = $mgr->$do();
            echo_json(['code'=>0, 'data'=>$result]);
        }catch(Exception $e){
            echo_json(['code'=>-1, 'msg'=>$e->getMessage()]);
        }
        break;
    case 'editor':
        if(!$islogin) exit("<script language='javascript'>window.location.href='./?c=login';</script>");
        $path = isset($_GET['path'])?trim($_GET['path']):'';
        try{
            $path = $x->set_dir_path($path, true);
        }catch(Exception $e){
            $errmsg = $e->getMessage();
            sysmsg($errmsg);
        }
        if($conf['editor'] == '1'){
            include PAGE_ROOT.'editor2.php';
        }else{
            include PAGE_ROOT.'editor.php';
        }
        break;
    case 'login':
        include PAGE_ROOT.'login.php';
        break;
    default:
        break;
}

