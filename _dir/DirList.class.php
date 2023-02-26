<?php

class DirList
{
    private $config = [
        'hide_dot_files' => true,
        // 隐藏文件
        'hidden_files' => [
            'index.php',
            '.htaccess',
            '*/.htaccess',
            '_dir',
            '_dir/*',
            'robots.txt',
        ],
    ];
    
    // 获取文件后缀名
    public function get_file_ext($filepath){
        $suffix = explode(".",$filepath);
        $suffix = end($suffix);
        $suffix = strtolower($suffix);
        return $suffix;
    }
    
    // 获取文件预览类型
    public function get_view_type($type){
        $type_image = ['png','jpg','jpeg','gif','bmp','webp','ico','svg','svgz','tif','tiff','heic','exif'];
        $type_audio = ['mp3','wav','ogg','m4a','flac','aac'];
        $type_video = ['mp4','webm','flv','f4v','mov','3gp','3gpp','avi','wmv','mkv','ts','dat','asf','mts','m2ts','m3u8', 'm4v'];
        $type_office = ['doc','docx','xps','rtf','wps','xls','xlsx','ppt','pptx','pdf'];
        $type_markdown = ['md'];
        $type_text = ['txt','text','log','yaml','yml','conf','config','ini','c','cpp','cxx','rc','php','py','cs','h','htm','html','css','less','sass','scss','js','hdml','dtd','wml','xml','xsl','vbs','vb','rtx','xsd','dpr','sql','java','go','jsp','asp','aspx','asa','asax','pl','bat','cmd','rb','reg','sh','json','lua','r','mm','mak','swift','tpl'];
        if(in_array($type, $type_image)){
            return 'image';
        }elseif(in_array($type, $type_audio)){
            return 'audio';
        }elseif(in_array($type, $type_video)){
            return 'video';
        }elseif(in_array($type, $type_office)){
            return 'office';
        }elseif(in_array($type, $type_markdown)){
            return 'markdown';
        }elseif(in_array($type, $type_text)){
            return 'text';
        }else{
            return '';
        }
    }
    
    // 获取文件小图标
    public function get_file_icon($type){
        $type_image = ['png','jpg','jpeg','gif','bmp','webp','ico','svg','svgz','tif','tiff','heic','psd','exif','pcx','tga','fpx','cdr','pcd','eps','ai','wmf','raw','ufo','jpc','jp2','jpx','xbm','wbmp','avif'];
        $type_audio = ['mp3','wav','wma','ogg','m4a','flac','ape','aac','ra','cda','midi','mid','aif','au','voc'];
        $type_video = ['mp4','webm','flv','f4v','mov','3gp','3gpp','avi','mpg','mpeg','wmv','mkv','ts','dat','asf','rm','rmvb','ram','divx','vob','qt','fli','flc','mod','m2t','swf','mts','m2ts','mpe','div','lavf','m3u8','m4v','ogm','ogv'];
        $type_text = ['txt','text','log','md','yaml','yml','conf','config','ini'];
        $type_code = ['c','cpp','cxx','rc','php','py','cs','h','htm','html','css','less','sass','scss','js','hdml','dtd','wml','xml','xsl','vbs','vb','rtx','xsd','dpr','sql','java','go','jsp','asp','aspx','asa','asax','pl','bat','cmd','rb','reg','sh','json','lua','r','mm','mak','swift','tpl'];
        $type_archive = ['zip','7z','rar','tgz','gz','xz','tar','jar','iso','z','zipx','cab','bz2','arj','lz','lzh'];
        $type_word = ['doc','docx','xps','rtf','wps','odt'];
        $type_excel = ['xls','xlsx','ods'];
        $type_pdf = ['pdf'];
        $type_powerpoint = ['ppt','pptx'];
        $type_android = ['apk'];
        $type_apple = ['ipa','dmg'];
        $type_windows = ['exe','appx','msi'];
        $type_linux = ['deb','rpm'];
        if(in_array($type, $type_image)){
            return 'fa-file-image-o';
        }elseif(in_array($type, $type_audio)){
            return 'fa-file-audio-o';
        }elseif(in_array($type, $type_video)){
            return 'fa-file-video-o';
        }elseif(in_array($type, $type_text)){
            return 'fa-file-text-o';
        }elseif(in_array($type, $type_code)){
            return 'fa-file-code-o';
        }elseif(in_array($type, $type_archive)){
            return 'fa-file-archive-o';
        }elseif(in_array($type, $type_word)){
            return 'fa-file-word-o';
        }elseif(in_array($type, $type_excel)){
            return 'fa-file-excel-o';
        }elseif(in_array($type, $type_pdf)){
            return 'fa-file-pdf-o';
        }elseif(in_array($type, $type_powerpoint)){
            return 'fa-file-powerpoint-o';
        }elseif(in_array($type, $type_android)){
            return 'fa-android';
        }elseif(in_array($type, $type_apple)){
            return 'fa-apple';
        }elseif(in_array($type, $type_windows)){
            return 'fa-windows';
        }elseif(in_array($type, $type_linux)){
            return 'fa-linux';
        }else{
            return 'fa-file-o';
        }
    }

    // 文件大小格式化
    private function size_format($size)
    {
        if ($size<1024) {
            $size.=' B';
        } else {
            $size/=1024;
            if ($size<1024) {
                $size=round($size, 2).' KB';
            } else {
                $size/=1024;
                if ($size<1024) {
                    $size=round($size, 2).' MB';
                } else {
                    $size/=1024;
                    if ($size<1024) {
                        $size=round($size, 2).' GB';
                    }
                }
            }
        }
        return $size;
    }

    // 检测路径
    public function set_dir_path($dir, $is_file = false){
        $dir = str_replace("\\","/",$dir);
    
        while (strpos($dir, '//')) {
            $dir = str_replace('//', '/', $dir);
        }
    
        if(substr($dir, -1, 1) == '/') {
            $dir = substr($dir, 0, -1);
        }

        if(substr($dir, 0, 1) == '/') {
            $dir = substr($dir, 1);
        }

        if (empty($dir) || $dir == '.') {
            return '.';
        }

        if (strpos($dir, '<') !== false || strpos($dir, '>') !== false
        || strpos($dir, '..') !== false || strpos($dir, '/./') !== false) {
            throw new Exception('检测到无效的路径字符串');
        }

        if ($this->is_hide($dir)) {
            throw new Exception('拒绝访问');
        }

        $dir = $this->encoding($dir, false);
    
        if ($is_file){
            if (!file_exists($dir) || !is_file($dir)){
                throw new Exception('文件路径不存在');
            }
        }else{
            if (!file_exists($dir) || !is_dir($dir)){
                throw new Exception('文件路径不存在');
            }
        }
    
        return $dir;
    }

    // 扫描所有文件
    private function scan_files($dir = '.'){
        $list = [];
        $files = scandir($dir);
        foreach($files as $file){
            if($file == '.' || $file == '..') continue;

            $relativePath = $dir . '/' . $file;
            if (substr($relativePath, 0, 2) == './') {
                $relativePath = substr($relativePath, 2);
            }

            if($this->is_hide($relativePath)) continue;

            $name = $this->encoding($file, true);
            $relativePathEncode = $this->encoding($relativePath, true);

            $ctime = filemtime($relativePath);
            $ctime = date("Y-m-d H:i",$ctime);

            if(is_dir($relativePath)){
                $list = array_merge($list, $this->scan_files($relativePath));
            }else{
                $list[] = [
                    'name' => $name,
                    'path' => $relativePathEncode,
                ];
            }
        }
        return $list;
    }

    // 获取所有文件
    public function get_all_files(){
        global $conf,$CACHE;
        switch($conf['cache_indexes']){
            case '1': $cache_time = 3600;break;
            case '2': $cache_time = 3600 * 6;break;
            case '3': $cache_time = 3600 * 24;break;
            default: $cache_time = 0;break;
        }
        if($cache_time > 0){
            $all_files = $CACHE->get('indexes');
            if($all_files && count($all_files) > 0){
                return $all_files;
            }
        }
        $all_files = $this->scan_files();
        if($cache_time > 0){
            $CACHE->set('indexes', $all_files, $cache_time);
        }
        return $all_files;
    }

    // 文件搜索
    public function search_files($s){
        if(empty($s)) return [];
        $list = [];
        $all_files = $this->get_all_files();
        foreach($all_files as $file){
            if(stripos($file['name'], $s)===false) continue;
            $relativePathEncode = $file['path'];
            $relativePath = $this->encoding($file['path'], false);
            $ctime = filemtime($relativePath);
            $ctime = date("Y-m-d H:i",$ctime);
            $src = './'.implode('/', array_map('rawurlencode', explode('/', $relativePath)));
            $ext = $this->get_file_ext($relativePath);
            $icon = $this->get_file_icon($ext);
            $view_type = $this->get_view_type($ext);
            $size = filesize($relativePath);
            $list[] = [
                'type' => 'file',
                'name' => $file['name'],
                'path' => $relativePathEncode,
                'src' => $src,
                'icon' => $icon,
                'mtime' => $ctime,
                'size' => $size,
                'size_format' => $this->size_format($size),
                'view_type' => $view_type,
            ];
            if(count($list) >= 100) break;
        }
        return $list;
    }
    
    // 目录列表
    public function list_dir($dir = '.'){
        $dir = $this->set_dir_path($dir);

        $navi = $this->get_navigation($dir);
        
        $newdir = [];
        $newfile = [];

        $readme_md = null;

        $files = scandir($dir);
        foreach($files as $file){
            if($file == '.' || $file == '..') continue;

            $relativePath = $dir . '/' . $file;
            if (substr($relativePath, 0, 2) == './') {
                $relativePath = substr($relativePath, 2);
            }

            if($this->is_hide($relativePath)) continue;

            $name = $this->encoding($file, true);
            $relativePathEncode = $this->encoding($relativePath, true);

            $ctime = filemtime($relativePath);
            $ctime = date("Y-m-d H:i",$ctime);

            if(is_dir($relativePath)){
                $src = './?dir='.rawurlencode('/'.$relativePathEncode);
                $newdir[] = [
                    'type' => 'dir',
                    'name' => $name,
                    'path' => $relativePathEncode,
                    'src' => $src,
                    'icon' => 'fa-folder-open',
                    'mtime' => $ctime,
                    'size' => false,
                    'size_format' => '-',
                    'view_type' => false,
                ];
            }else{
                $src = './'.implode('/', array_map('rawurlencode', explode('/', $relativePath)));
                $ext = $this->get_file_ext($relativePath);
                $icon = $this->get_file_icon($ext);
                $view_type = $this->get_view_type($ext);
                $size = filesize($relativePath);
                $newfile[] = [
                    'type' => 'file',
                    'name' => $name,
                    'path' => $relativePathEncode,
                    'src' => $src,
                    'icon' => $icon,
                    'mtime' => $ctime,
                    'size' => $size,
                    'size_format' => $this->size_format($size),
                    'view_type' => $view_type,
                ];
                if(($name == 'readme.md' || $name == 'README.md') && $size < 1024 * 1024 * 5) $readme_md = $relativePath;
            }
        }
        $listdir = array_merge($newdir, $newfile);

        $parent = null;
        if($dir != '.'){
            $pathArray = explode('/', '/'.$dir);
            unset($pathArray[count($pathArray)-1]);
            $parentPath = implode('/', $pathArray);
            if($parentPath == '') $parent = './';
            else $parent = './?dir='.rawurlencode($this->encoding($parentPath, true));
        }

        $result = ['dir' => $dir, 'list' => $listdir, 'navi' => $navi, 'parent' => $parent, 'readme_md' => $readme_md];
        return $result;
    }

    // 获取导航栏
    private function get_navigation($dir){
        $navi = [
            ['name'=>'首页', 'src'=>'./']
        ];
        $navi_arr = explode('/', $dir);
        $navi_src = '';
        foreach($navi_arr as $name){
            if($name == '.') continue;
            $name = $this->encoding($name, true);
            $navi_src .= '/' . rawurlencode($name);
            $navi[] = ['name'=>$name, 'src'=>'./?dir='.$navi_src];
        }
        return $navi;
    }

    // 文件是否隐藏
    private function is_hide($filePath){
        $hidden_files = $this->config['hidden_files'];
        if ($this->config['hide_dot_files']) {
            $hidden_files = array_merge(
                $hidden_files,
                array('.*', '*/.*')
            );
        }

        foreach ( $hidden_files as $hiddenPath) {
            if (fnmatch($hiddenPath, $filePath)) {
                return true;
            }
        }
        return false;
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

    // 文件hash
    public function get_file_hash($path){
        global $conf;
        if(!$conf['file_hash'])throw new Exception('未开启该功能');
        $path = $this->set_dir_path($path, true);
        $name = basename($path);
        $md5 = md5_file($path);
        $sha1 = sha1_file($path);
        return ['code'=>0, 'name'=>$name, 'md5'=>$md5, 'sha1'=>$sha1];
    }
}