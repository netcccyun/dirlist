<?php

class FileMgr
{
    private $path;
    private $dirList;

    // 允许前端调用的操作
    const ACTIONS = ['create', 'rename', 'delete', 'addclip', 'paste', 'query_secret', 'set_secret', 'compress', 'uncompress'];

    public function __construct($path)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $this->path = $path;
        $this->dirList = new DirList();
    }

    //创建文件/文件夹
    public function create()
    {
        $type = isset($_POST['type']) ? trim($_POST['type']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        if ($name === '') throw new Exception('创建名称不能为空');
        if (!$this->check_filename($name)) throw new Exception('名称不能包含系统禁止的特殊符号');

        $filepath = $this->child_path($name, true);
        if (file_exists($filepath)) throw new Exception('同名文件或文件夹已存在');
        if ($type == 'file') {
            if (!touch($filepath)) throw new Exception('文件创建失败');
        } else {
            if (!mkdir($filepath)) throw new Exception('文件夹创建失败');
        }
        return true;
    }

    //重命名
    public function rename()
    {
        $oldname = isset($_POST['oldname']) ? trim($_POST['oldname']) : '';
        $newname = isset($_POST['newname']) ? trim($_POST['newname']) : '';
        if ($oldname === '') throw new Exception('原名称不能为空');
        if ($newname === '') throw new Exception('名称不能为空');
        if (!$this->check_filename($oldname)) throw new Exception('原名称不能包含系统禁止的特殊符号');
        if (!$this->check_filename($newname)) throw new Exception('名称不能包含系统禁止的特殊符号');
        if ($oldname === $newname) return true;

        $oldpath = $this->child_path($oldname, true);
        $newpath = $this->child_path($newname, true);
        if (!file_exists($oldpath)) throw new Exception('原文件不存在');
        // Windows下仅修改大小写时目标“已存在”，允许继续
        if (file_exists($newpath) && strtolower($oldname) !== strtolower($newname)) throw new Exception('目标名称已存在');
        if (!rename($oldpath, $newpath)) throw new Exception('重命名失败');
        return true;
    }

    //批量删除文件/文件夹
    public function delete()
    {
        $files = isset($_POST['files']) ? $_POST['files'] : null;
        if (!is_array($files) || count($files) == 0) throw new Exception('请选择要删除的文件');

        $count = 0;
        foreach ($files as $file) {
            $filepath = $this->child_path($file, true);
            if (is_link($filepath)) {
                if (@unlink($filepath) || @rmdir($filepath)) $count++;
            } elseif (is_dir($filepath)) {
                $this->rm_dir($filepath, $count);
            } elseif (is_file($filepath)) {
                if (unlink($filepath)) $count++;
            }
        }
        return $count;
    }

    //添加到剪贴板
    public function addclip()
    {
        $files = isset($_POST['files']) ? $_POST['files'] : null;
        $op = isset($_POST['op']) ? $_POST['op'] : '';
        if ($op !== 'cut' && $op !== 'copy') throw new Exception('操作类型错误');
        $opname = $op == 'cut' ? '剪切' : '复制';
        if (!is_array($files) || count($files) == 0) throw new Exception('请选择要' . $opname . '的文件');
        foreach ($files as $file) {
            $this->child_path($file, true);
        }
        $_SESSION['filemgr_clip'] = ['dir' => $this->path, 'op' => $op, 'files' => $files];
        return true;
    }

    //粘贴
    public function paste()
    {
        $clip = isset($_SESSION['filemgr_clip']) ? $_SESSION['filemgr_clip'] : null;
        if (!$clip || empty($clip['op']) || empty($clip['files']) || !is_array($clip['files']) || count($clip['files']) == 0) throw new Exception('没有进行复制或剪切');
        if ($clip['dir'] == $this->path) throw new Exception('粘贴目录不能为当前目录');

        $op = $clip['op'];
        $tasks = [];
        $dstreal = realpath($this->path);
        foreach ($clip['files'] as $file) {
            if ($file == '') continue;
            if (!$this->check_filename($file)) continue;

            $oldpath = $clip['dir'] . '/' . $this->encoding($file);
            $newpath = $this->child_path($file, true);
            if (!file_exists($oldpath)) continue;
            // 不能把文件夹复制/移动到它自己的子目录里，否则会无限递归
            $oldreal = realpath($oldpath);
            if ($oldreal !== false && $dstreal !== false && is_dir($oldreal)) {
                $oldnorm = strtolower(str_replace('\\', '/', $oldreal)) . '/';
                $dstnorm = strtolower(str_replace('\\', '/', $dstreal)) . '/';
                if (strpos($dstnorm, $oldnorm) === 0) throw new Exception('不能将文件夹粘贴到其自身或子目录中');
            }
            $tasks[] = [$oldpath, $newpath];
        }

        unset($_SESSION['filemgr_clip']);
        $count = 0;
        foreach ($tasks as list($oldpath, $newpath)) {
            switch ($op) {
                case 'cut':
                    if (rename($oldpath, $newpath)) $count++;
                    break;
                case 'copy':
                    if (is_dir($oldpath)) {
                        @mkdir($newpath);
                        $this->copy_dir($oldpath, $newpath, $count);
                    } else {
                        if (copy($oldpath, $newpath)) $count++;
                    }
                    break;
            }
        }
        return ['op' => $op, 'count' => $count];
    }

    //查询文件夹访问密码
    public function query_secret()
    {
        $file = $this->path . '/.passwd';
        if (file_exists($file)) {
            $passwd = file_get_contents($file);
            if ($passwd) {
                return ['issecret' => true];
            }
        }
        return ['issecret' => false];
    }

    //设置文件夹访问密码
    public function set_secret()
    {
        $file = $this->path . '/.passwd';
        $issecret = isset($_POST['issecret']) ? intval($_POST['issecret']) : 0;
        if ($issecret == 1) {
            $passwd = isset($_POST['passwd']) ? trim($_POST['passwd']) : '';
            if ($passwd === '') {
                if (is_file($file) && filesize($file) > 0) return true;
                throw new Exception('密码不能为空');
            }
            $passwd = password_hash($passwd, PASSWORD_DEFAULT);
            if (!file_put_contents($file, $passwd)) throw new Exception('设置访问密码失败');
        } else {
            is_file($file) && unlink($file);
        }
        return true;
    }

    //创建压缩包文件
    public function compress()
    {
        if (!class_exists('ZipArchive')) throw new Exception('php未开启ZipArchive，不支持该功能');
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        if ($name === '') throw new Exception('压缩包文件名不能为空');
        if (!$this->check_filename($name)) throw new Exception('名称不能包含系统禁止的特殊符号');
        if ($this->get_file_ext($name) !== 'zip') $name .= '.zip';
        $files = isset($_POST['files']) ? $_POST['files'] : null;
        if (!is_array($files) || count($files) == 0) throw new Exception('请选择要压缩的文件');

        $zipfilepath = $this->child_path($name, true);
        if (file_exists($zipfilepath)) throw new Exception('压缩包文件名已存在');

        $zip = new ZipArchive();
        if ($zip->open($zipfilepath, ZipArchive::CREATE) !== true) throw new Exception('压缩包文件创建失败');
        foreach ($files as $file) {
            $filepath = $this->child_path($file, true);
            if (!file_exists($filepath)) continue;
            if (is_dir($filepath)) {
                $this->zip_dir($filepath, $zip, strlen($this->path . '/'));
            } else {
                $zip->addFile($filepath, basename($filepath));
            }
        }
        $zip->close();
        return true;
    }

    //解压缩
    public function uncompress()
    {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $targetdir = isset($_POST['targetdir']) ? trim($_POST['targetdir']) : '';
        if ($name === '') throw new Exception('压缩包文件名不能为空');
        if ($targetdir === '') throw new Exception('解压目录不能为空');
        if (!$this->check_filename($name)) throw new Exception('压缩包文件名不合法');

        $zipfilepath = $this->child_path($name);
        if (!is_file($zipfilepath)) throw new Exception('压缩包文件不存在');

        $extractPath = $this->normalize_target_dir($targetdir);
        $zipbasename = basename(str_replace('\\', '/', $zipfilepath));

        $ext = $this->get_file_ext($zipfilepath);
        if (!in_array($ext, ['zip', 'gz', 'bz2', 'tar', 'tgz'])) throw new Exception('不支持的压缩包格式');
        if ($ext == 'zip' && !class_exists('ZipArchive')) throw new Exception('php未开启ZipArchive，不支持解压该文件');
        if (($ext == 'gz' || $ext == 'tgz') && !function_exists('gzopen')) throw new Exception('php未开启zlib，不支持解压该文件');
        if ($ext == 'bz2' && !function_exists('bzopen')) throw new Exception('php未开启bz2扩展，不支持解压该文件');
        if (($ext == 'tar' || $ext == 'tgz') && !class_exists('PharData')) throw new Exception('php未开启phar，不支持解压该文件');

        if (!is_dir($extractPath)) {
            if (!@mkdir($extractPath, 0777, true) && !is_dir($extractPath)) throw new Exception('解压目录创建失败');
        }
        $this->assert_target_dir($extractPath);

        if ($ext == 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($zipfilepath) !== true) throw new Exception('压缩包文件打开失败');
            $this->assert_archive_entries($zip, $extractPath);
            if (!$zip->extractTo($extractPath)) {
                $zip->close();
                throw new Exception('解压失败，可能无文件写入权限');
            }
            $zip->close();
        } elseif ($ext == 'gz') {
            $targetfile = $extractPath . '/' . substr($zipbasename, 0, -3);
            $this->assert_target_file($targetfile, $extractPath);
            $fp = gzopen($zipfilepath, 'rb');
            if (!$fp) throw new Exception('压缩包文件打开失败');
            $out = fopen($targetfile, 'wb');
            if (!$out) {
                gzclose($fp);
                throw new Exception('解压目录打开失败');
            }
            while (!gzeof($fp)) {
                $buff = gzread($fp, 65536);
                if ($buff === false) break;
                fwrite($out, $buff);
            }
            fclose($out);
            gzclose($fp);
        } elseif ($ext == 'bz2') {
            $targetfile = $extractPath . '/' . substr($zipbasename, 0, -4);
            $this->assert_target_file($targetfile, $extractPath);
            $fp = bzopen($zipfilepath, 'r');
            if (!$fp) throw new Exception('压缩包文件打开失败');
            $out = fopen($targetfile, 'wb');
            if (!$out) {
                bzclose($fp);
                throw new Exception('解压目录打开失败');
            }
            while (!feof($fp)) {
                $buff = bzread($fp, 65536);
                if ($buff === false) break;
                fwrite($out, $buff);
            }
            fclose($out);
            bzclose($fp);
        } elseif ($ext == 'tar') {
            $phar = new PharData($zipfilepath);
            $this->assert_phar_entries($phar, $extractPath);
            $phar->extractTo($extractPath, null, true);
        } elseif ($ext == 'tgz') {
            $fp = gzopen($zipfilepath, 'rb');
            if (!$fp) throw new Exception('压缩包文件打开失败');
            // 用临时文件名，避免覆盖目录中已有的同名 .tar 文件
            $targetfile = $extractPath . '/.untgz_' . md5(uniqid('', true)) . '.tar';
            $out = fopen($targetfile, 'wb');
            if (!$out) {
                gzclose($fp);
                throw new Exception('解压目录打开失败');
            }
            while (!gzeof($fp)) {
                $buff = gzread($fp, 65536);
                if ($buff === false) break;
                fwrite($out, $buff);
            }
            fclose($out);
            gzclose($fp);
            try {
                if (filesize($targetfile) == 0) throw new Exception('解压失败，文件损坏或格式不支持');
                $phar = new PharData($targetfile);
                $this->assert_phar_entries($phar, $extractPath);
                $phar->extractTo($extractPath, null, true);
                unset($phar);
            } finally {
                @unlink($targetfile);
            }
        } else {
            throw new Exception('不支持的压缩包格式');
        }

        return true;
    }

    // 规范化解压目录，始终落在站点根目录内
    private function normalize_target_dir($targetdir)
    {
        $targetdir = str_replace('\\', '/', $targetdir);
        $targetdir = preg_replace('#/+#', '/', $targetdir);
        if (strpos($targetdir, '..') !== false || strpos($targetdir, "\0") !== false) {
            throw new Exception('无效的解压目录');
        }
        $targetdir = trim($targetdir, '/');
        if ($targetdir === '' || $targetdir === '.') {
            return '.';
        }
        if (strpos($targetdir, ':') !== false || $this->dirList->is_hide($targetdir)) {
            throw new Exception('无效的解压目录');
        }
        return $this->encoding($targetdir);
    }

    // 获取相对站点根目录的真实路径，不在站点内返回false
    private function relative_path($path)
    {
        $root = realpath('.');
        $real = realpath($path);
        if ($root === false || $real === false) return false;
        $rootNorm = strtolower(str_replace('\\', '/', $root));
        $realNorm = strtolower(str_replace('\\', '/', $real));
        if ($realNorm === $rootNorm) return '';
        if (strpos($realNorm, $rootNorm . '/') !== 0) return false;
        return substr(str_replace('\\', '/', $real), strlen($root) + 1);
    }

    private function assert_target_dir($extractPath)
    {
        $rel = $this->relative_path($extractPath);
        if ($rel === false || ($rel !== '' && $this->dirList->is_hide($rel))) {
            throw new Exception('无效的解压目录');
        }
    }

    private function assert_target_file($targetfile, $extractPath)
    {
        $name = basename(str_replace('\\', '/', $targetfile));
        if (!$this->check_filename($name)) throw new Exception('无效的解压路径');
        $rel = $this->relative_path($extractPath);
        if ($rel === false || $this->dirList->is_hide(($rel === '' ? '' : $rel . '/') . $name)) {
            throw new Exception('解压后的文件名受保护，禁止覆盖');
        }
    }

    private function assert_archive_entry($entry, $rel)
    {
        $entry = str_replace('\\', '/', $entry);
        if (strpos($entry, '..') !== false || strpos($entry, "\0") !== false || strpos($entry, ':') !== false || (strlen($entry) > 0 && $entry[0] === '/')) {
            throw new Exception('压缩包包含非法路径');
        }
        $entry = rtrim($entry, '/');
        if ($entry !== '' && $this->dirList->is_hide(($rel === '' ? '' : $rel . '/') . $entry)) {
            throw new Exception('压缩包包含受保护的文件：' . $entry);
        }
    }

    private function assert_archive_entries($zip, $extractPath)
    {
        $rel = $this->relative_path($extractPath);
        if ($rel === false) {
            $zip->close();
            throw new Exception('无效的解压目录');
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false) continue;
            try {
                $this->assert_archive_entry($entry, $rel);
            } catch (Exception $e) {
                $zip->close();
                throw $e;
            }
        }
    }

    private function assert_phar_entries($phar, $extractPath)
    {
        $rel = $this->relative_path($extractPath);
        if ($rel === false) throw new Exception('无效的解压目录');
        $prefix = 'phar://' . str_replace('\\', '/', $phar->getPath());
        foreach (new RecursiveIteratorIterator($phar, RecursiveIteratorIterator::SELF_FIRST) as $file) {
            $entry = str_replace('\\', '/', $file->getPathname());
            if (stripos($entry, $prefix) === 0) $entry = substr($entry, strlen($prefix));
            $this->assert_archive_entry(ltrim($entry, '/'), $rel);
        }
    }

    //检查文件名
    private function check_filename($name)
    {
        if ($name === '' || $name === '.' || $name === '..') return false;
        if (strpos($name, "\0") !== false) return false;
        $forbidden = [':', '/', '\\', '?', '<', '>', '|', '*', '"'];
        foreach ($forbidden as $a) {
            if (strpos($name, $a) !== false) return false;
        }
        if (strpos($name, '..') !== false) return false;
        return true;
    }

    // $this->path 已是文件系统编码，这里只转换文件名部分
    private function child_path($name, $protect = false)
    {
        $name = trim((string)$name);
        if (!$this->check_filename($name)) throw new Exception('名称不能包含系统禁止的特殊符号');
        $name = $this->encoding($name);
        if ($protect && $this->dirList->is_hide(($this->path === '.' ? '' : $this->path . '/') . $name)) {
            throw new Exception('该文件为系统保护文件，禁止操作');
        }
        return $this->path . '/' . $name;
    }

    // 复制文件夹
    private function copy_dir($src, $dst, &$count)
    {
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
            } else {
                ++$count;
                copy($src_file, $dst . '/' . $file);
            }
        }

        closedir($rd);
        ++$count;
        return true;
    }

    // 删除文件夹
    private function rm_dir($dir, &$count)
    {
        $rd = opendir($dir);
        if (!$rd) {
            return false;
        }

        while (($file = readdir($rd)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $file = $dir . '/' . $file;

            if (is_link($file)) {
                ++$count;
                @unlink($file) || @rmdir($file);
            } elseif (is_dir($file)) {
                $this->rm_dir($file, $count);
            } else {
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
    private function zip_dir($folder, &$zipFile, $exclusiveLength)
    {
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
                $this->zip_dir($filePath, $zipFile, $exclusiveLength);
            } else {
                $zipFile->addFile($filePath, $localPath);
            }
        }
        closedir($handle);
    }


    // 解决中文文件名编码问题
    private function encoding($str, $type = false)
    {
        global $conf;
        if ($conf['name_encode'] == 'gbk' && preg_match("/[\x7f-\xff]/", $str)) {
            if ($type) {
                return mb_convert_encoding($str, 'UTF-8', 'GBK');
            } else {
                return mb_convert_encoding($str, 'GBK', 'UTF-8');
            }
        }
        return $str;
    }

    // 获取文件后缀名
    private function get_file_ext($filepath)
    {
        $suffix = explode(".", $filepath);
        $suffix = end($suffix);
        $suffix = strtolower($suffix);
        return $suffix;
    }
}
