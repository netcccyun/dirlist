<?php

class FileMgr
{
    private $path;

    public function __construct($path)
    {
        session_start();
        $this->path = $path;
    }

    //创建文件/文件夹
    public function create(){
        $type = trim($_POST['type']);
        $name = trim($_POST['name']);
        if(empty($name)) throw new Exception('创建名称不能为空');
        if(!$this->check_filename($name)) throw new Exception('名称不能包含系统禁止的特殊符号');

        $filepath = $this->encoding($this->path.'/'.$name);
        if($type == 'file') { 
            if(!touch($filepath)) throw new Exception('文件创建失败');
        }else{
            if(!mkdir($filepath)) throw new Exception('文件夹创建失败');
        }
        return true;
    }

    //重命名
    public function rename(){
        $oldname = trim($_POST['oldname']);
        $newname = trim($_POST['newname']);
        if(empty($oldname)) throw new Exception('原名称不能为空');
        if(empty($newname)) throw new Exception('名称不能为空');
        if(!$this->check_filename($newname)) throw new Exception('名称不能包含系统禁止的特殊符号');

        $oldpath = $this->encoding($this->path.'/'.$oldname);
        $newpath = $this->encoding($this->path.'/'.$newname);
        if(!rename($oldpath, $newpath)) throw new Exception('重命名失败');
        return true;
    }

    //批量删除文件/文件夹
    public function delete(){
        $files = $_POST['files'];
        if(!$files || count($files)==0) throw new Exception('请选择要删除的文件');

        $count = 0;
        foreach($files as $file){
            $filepath = $this->encoding($this->path.'/'.$file);
            if (is_dir($filepath)) {
                $this->rm_dir($filepath, $count);
            }else{
                if(unlink($filepath)) $count++;
            }
        }
        return $count;
    }

    //添加到剪贴板
    public function addclip(){
        $files = $_POST['files'];
        $op = $_POST['op'];
        $opname = $op == 'cut' ? '剪切' : '复制';
        if(!$files || count($files)==0) throw new Exception('请选择要'.$opname.'的文件');
        $_SESSION['filemgr_clip'] = ['dir'=>$this->path, 'op'=>$op, 'files'=>$files];
        return true;
    }

    //粘贴
    public function paste(){
        $clip = $_SESSION['filemgr_clip'];
        if(!$clip || !$clip['op'] || count($clip['files']) == 0) throw new Exception('没有进行复制或剪切');
        if($clip['dir'] == $this->path) throw new Exception('粘贴目录不能为当前目录');

        unset($_SESSION['filemgr_clip']);
        $op = $clip['op'];
        $count = 0;
        foreach($clip['files'] as $file){
            if ($file == '') continue;

            $oldpath = $this->encoding($clip['dir'].'/'.$file);
            $newpath = $this->encoding($this->path.'/'.$file);
            
            switch($op){
            case 'cut':
                if (rename($oldpath, $newpath)) $count++;
                break;
            case 'copy':
                if (is_dir($oldpath)) {
					@mkdir($newpath);
					$this->copy_dir($oldpath, $newpath, $count);
				}
				else {
					if (copy($oldpath, $newpath)) $count++;
				}
                break;
            }
        }
        return ['op'=>$op, 'count'=>$count];
    }

    //查询文件夹访问密码
    public function query_secret(){
        $file = $this->encoding($this->path.'/.passwd');
        if(file_exists($file)){
            $passwd = file_get_contents($file);
            if($passwd){
                return ['issecret'=>true];
            }
        }
        return ['issecret'=>false];
    }

    //设置文件夹访问密码
    public function set_secret(){
        $file = $this->encoding($this->path.'/.passwd');
        $issecret = intval($_POST['issecret']);
        if($issecret == 1){
            $passwd = trim($_POST['passwd']);
            if(empty($passwd)) throw new Exception('密码不能为空');
            $passwd = password_hash($passwd, PASSWORD_DEFAULT);
            if(!file_put_contents($file, $passwd)) throw new Exception('设置访问密码失败');
        }else{
            is_file($file) && unlink($file);
        }
        return true;
    }

    //创建压缩包文件
    public function compress(){
        if(!class_exists('ZipArchive')) throw new Exception('当前php未开启ZipArchive，不支持该功能');
        $name = trim($_POST['name']);
        if(empty($name)) throw new Exception('压缩包文件名不能为空');
        if(!$this->check_filename($name)) throw new Exception('名称不能包含系统禁止的特殊符号');
        $files = $_POST['files'];
        if(!$files || count($files)==0) throw new Exception('请选择要压缩的文件');

        $zipfilepath = $this->encoding($this->path.'/'.$name);
        if(file_exists($zipfilepath)) throw new Exception('压缩包文件名已存在');

        $zip = new ZipArchive();
        if($zip->open($zipfilepath, ZipArchive::CREATE)===false) throw new Exception('压缩包文件创建失败');
        foreach($files as $file){
            $filepath = $this->encoding($this->path.'/'.$file);
            if (is_dir($filepath)) {
                $this->zip_dir($filepath, $zip, strlen($this->path.'/'));
            }else{
                $zip->addFile($filepath, basename($filepath));
            }
        }
        $zip->close();
        return true;
    }

    //解压缩
    public function uncompress(){
        if(!class_exists('ZipArchive')) throw new Exception('当前php未开启ZipArchive，不支持该功能');
        $name = trim($_POST['name']);
        $targetdir = trim($_POST['targetdir']);
        if(empty($name)) throw new Exception('压缩包文件名不能为空');
        if(empty($targetdir)) throw new Exception('解压目录不能为空');

        $zipfilepath = $this->encoding($this->path.'/'.$name);
        if(!is_file($zipfilepath)) throw new Exception('压缩包文件不存在');

        if(!is_dir($targetdir)) @mkdir($targetdir, 0777, true);

        $zip = new ZipArchive();
        if($zip->open($zipfilepath)===false) throw new Exception('压缩包文件打开失败');
        $zip->extractTo('.'.$targetdir);
        $zip->close();
        return true;
    }

    //检查文件名
    private function check_filename($name){
        $forbidden = [':','/','\\','?','<','>','|','*','"'];
        foreach($forbidden as $a){
            if(strpos($name, $a)!==false) return false;
        }
        return true;
    }

    // 复制文件夹
    private function copy_dir($src, $dst, &$count){
        $rd = opendir($src);
        if (!$rd) {
            return false;
        }

        while (($file = readdir($rd)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $src_file = $src . '/' . $file;

            if (is_dir($src_file)) {
                @mkdir($dst . '/' . $file);
                $this->copy_dir($src_file, $dst . '/' . $file, $count);
            }
            else {
                ++$count;
                copy($src_file, $dst . '/' . $file);
            }
        }

        closedir($rd);
        ++$count;
        return true;
    }

    // 删除文件夹
    private function rm_dir($dir, &$count){
        $rd = opendir($dir);
        if (!$rd) {
            return false;
        }

        while (($file = readdir($rd)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $file = $dir . '/' . $file;

            if (is_dir($file)) {
                $this->rm_dir($file, $count);
            }
            else {
                ++$count;
                unlink($file);
            }
        }

        closedir($rd);
        ++$count;
        rmdir($dir);
        return true;
    }

    // 压缩文件夹
    private function zip_dir($folder, &$zipFile, $exclusiveLength) {
        $handle = opendir($folder);
        if (!$handle) {
            return false;
        }
        while (($file = readdir($handle)) !== false) {
            if ($file == '.' || $file == '..' || $file == '.passwd') {
                continue;
            }
            $filePath = $folder . '/' . $file;

            // 在添加到zip之前从文件路径中删除前缀
            $localPath = substr($filePath, $exclusiveLength);
            if (is_dir($filePath)) {
                $zipFile->addEmptyDir($localPath);
                self::zip_dir($filePath, $zipFile, $exclusiveLength);
            } else {
                $zipFile->addFile($filePath, $localPath);
            }
        }
        closedir($handle);
    }


    // 解决中文文件名编码问题
    private function encoding($str, $type = false){
        global $conf;
        if($conf['name_encode'] == 'gbk' && preg_match("/[\x7f-\xff]/", $str)){
            if($type){
                return mb_convert_encoding($str, 'UTF-8', 'GBK');
            }else{
                return mb_convert_encoding($str, 'GBK', 'UTF-8');
            }
        }
        return $str;
    }
}