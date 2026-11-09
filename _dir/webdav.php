<?php

require __DIR__ . '/inc.php';

/**
 * WebDAV服务端类
 * 实现WebDAV协议支持，允许客户端通过WebDAV协议访问文件系统
 */
class WebDAVServer
{
    private $basePath;
    private $authEnabled;
    private $username;
    private $password;
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
    
    public function __construct($basePath = '.', $authEnabled = false, $username = '', $password = '')
    {
        $this->basePath = realpath($basePath);
        $this->authEnabled = $authEnabled;
        $this->username = $username;
        $this->password = $password;
        
        if (!is_dir($this->basePath)) {
            throw new Exception('Base path does not exist: ' . $this->basePath);
        }
    }
    
    /**
     * 处理WebDAV请求
     */
    public function handleRequest()
    {
        // 检查认证
        if ($this->authEnabled && !$this->checkAuth()) {
            header('WWW-Authenticate: Basic realm="WebDAV Server"');
            header('HTTP/1.1 401 Unauthorized');
            echo '<!DOCTYPE html><html><head><title>401 Unauthorized</title></head><body><h1>401 Unauthorized</h1><p>请输入正确的用户名和密码</p></body></html>';
            exit;
        }
        
        $method = $_SERVER['REQUEST_METHOD'];
        try {
            $path = $this->getRequestPath();
        } catch (Exception $e) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Error: ' . $e->getMessage();
            exit;
        }
        
        // 设置CORS头
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: OPTIONS, GET, HEAD, PUT, POST, DELETE, PROPFIND, MKCOL, COPY, MOVE, LOCK, UNLOCK');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Depth, If-Modified-Since, If-None-Match, Destination, Overwrite, Lock-Token, Timeout');
        header('Access-Control-Expose-Headers: ETag, Content-Length, Allow');
        
        // 处理OPTIONS请求 - 修复：添加必要的WebDAV头
        if ($method === 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            header('DAV: 1, 2, 3'); // 添加WebDAV级别支持
            header('MS-Author-Via: DAV'); // Windows客户端需要这个头
            header('Allow: OPTIONS, GET, HEAD, PUT, POST, DELETE, PROPFIND, MKCOL, COPY, MOVE, LOCK, UNLOCK');
            header('Content-Length: 0');
            exit;
        }
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet($path);
                    break;
                case 'HEAD':
                    $this->handleHead($path);
                    break;
                case 'PUT':
                    $this->handlePut($path);
                    break;
                case 'DELETE':
                    $this->handleDelete($path);
                    break;
                case 'PROPFIND':
                    $this->handlePropfind($path);
                    break;
                case 'MKCOL':
                    $this->handleMkcol($path);
                    break;
                case 'COPY':
                    $this->handleCopy($path);
                    break;
                case 'MOVE':
                    $this->handleMove($path);
                    break;
                case 'LOCK':
                    $this->handleLock($path);
                    break;
                case 'UNLOCK':
                    $this->handleUnlock($path);
                    break;
                default:
                    header('HTTP/1.1 405 Method Not Allowed');
                    header('Allow: OPTIONS, GET, HEAD, PUT, POST, DELETE, PROPFIND, MKCOL, COPY, MOVE, LOCK, UNLOCK');
                    break;
            }
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Error: ' . $e->getMessage();
        }
    }
    
    /**
     * 获取 WebDAV 挂载根路径，即当前脚本地址
     */
    private function getWebDavRoot()
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        return rtrim($script, '/');
    }

    /**
     * 从 /webdav.php 或 /webdav.php/foo 中提取资源路径
     */
    private function extractWebDavPath($uriPath)
    {
        $uriPath = str_replace('\\', '/', $uriPath ?: '');
        $scriptPath = $this->getWebDavRoot();

        if ($scriptPath !== '' && strpos($uriPath, $scriptPath) === 0) {
            return ltrim(substr($uriPath, strlen($scriptPath)), '/');
        }
        if (!empty($_SERVER['PATH_INFO'])) {
            return ltrim($_SERVER['PATH_INFO'], '/');
        }
        return '';
    }

    /**
     * 生成 PROPFIND 等响应中的 href
     */
    private function getHref($webPath, $isDir = false)
    {
        $href = $this->getWebDavRoot();
        if ($webPath !== '') {
            $segments = array_map('rawurlencode', explode('/', str_replace('\\', '/', $webPath)));
            $href .= '/' . implode('/', $segments);
        }
        if ($isDir && substr($href, -1) !== '/') {
            $href .= '/';
        }
        return $href;
    }

    /**
     * 获取请求路径
     */
    private function getRequestPath()
    {
        $path = $this->extractWebDavPath(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $path = rawurldecode($path);
        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');
        if (strpos($path, '..') !== false || strpos($path, "\0") !== false) {
            throw new Exception('Invalid path');
        }
        if ($path !== '' && $this->is_hide($path)) {
            throw new Exception('拒绝访问');
        }
        return $path;
    }
    
    /**
     * 获取文件系统路径
     */
    private function getFilesystemPath($path)
    {
        $path = str_replace('\\', '/', $path);
        if (strpos($path, '..') !== false || strpos($path, "\0") !== false) {
            throw new Exception('Invalid path');
        }
        $full = $this->basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $real = realpath($full);
        if ($real !== false) {
            if (!$this->isInsideBase($real)) {
                throw new Exception('Invalid path');
            }
            // 按真实路径再检查一次隐藏规则，防止大小写等写法绕过
            $rel = ltrim(substr(str_replace('\\', '/', $real), strlen(str_replace('\\', '/', $this->basePath))), '/');
            if ($rel !== '' && $this->is_hide($rel)) {
                throw new Exception('Access denied');
            }
            return $real;
        }
        $parent = realpath(dirname($full));
        if ($parent === false || !$this->isInsideBase($parent)) {
            throw new Exception('Invalid path');
        }
        return $full;
    }

    private function isInsideBase($realPath)
    {
        $base = strtolower(str_replace('\\', '/', $this->basePath));
        $real = strtolower(str_replace('\\', '/', $realPath));
        return $real === $base || strpos($real, $base . '/') === 0;
    }
    
    /**
     * 检查认证
     */
    private function checkAuth()
    {
        $username = null;
        $password = null;
        
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
        }
        elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Basic\s+(.*)$/i', $auth, $matches)) {
                list($username, $password) = array_pad(explode(':', base64_decode($matches[1]), 2), 2, '');
            }
        }
        elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if (preg_match('/Basic\s+(.*)$/i', $auth, $matches)) {
                list($username, $password) = array_pad(explode(':', base64_decode($matches[1]), 2), 2, '');
            }
        }
        if ($username !== null && $password !== null) {
            return $username === $this->username && md5($password) === $this->password;
        }
        
        return false;
    }
    
    /**
     * 处理GET请求
     */
    private function handleGet($path)
    {
        $filePath = $this->getFilesystemPath($path);
        
        if (!file_exists($filePath)) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        
        if (is_dir($filePath)) {
            // 如果是目录，返回目录列表
            $this->sendDirectoryListing($filePath, $path);
        } else {
            // 如果是文件，发送文件内容
            $this->sendFile($filePath);
        }
    }
    
    /**
     * 处理HEAD请求
     */
    private function handleHead($path)
    {
        $filePath = $this->getFilesystemPath($path);

        if (!file_exists($filePath)) {
            header('HTTP/1.1 404 Not Found');
            return;
        }

        if (is_dir($filePath)) {
            header('Content-Type: text/html; charset=utf-8');
            return;
        }
        $lastModified = filemtime($filePath);
        header('Content-Type: ' . $this->getMimeType($filePath));
        header('Content-Length: ' . filesize($filePath));
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('ETag: "' . md5($filePath . $lastModified) . '"');
    }

    /**
     * 处理PUT请求
     */
    private function handlePut($path)
    {
        if ($path === '') {
            header('HTTP/1.1 405 Method Not Allowed');
            return;
        }
        $filePath = $this->getFilesystemPath($path);
        if (is_dir($filePath)) {
            header('HTTP/1.1 405 Method Not Allowed');
            return;
        }
        $existed = file_exists($filePath);
        $dirPath = dirname($filePath);
        
        // 确保目录存在
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
        
        // 写入文件
        $input = fopen('php://input', 'r');
        $output = fopen($filePath, 'w');
        
        if ($input && $output) {
            stream_copy_to_stream($input, $output);
            fclose($input);
            fclose($output);
            
            header($existed ? 'HTTP/1.1 204 No Content' : 'HTTP/1.1 201 Created');
        } else {
            header('HTTP/1.1 500 Internal Server Error');
        }
    }
    
    /**
     * 处理DELETE请求
     */
    private function handleDelete($path)
    {
        // 禁止删除WebDAV根目录（即整个站点）
        if ($path === '') {
            header('HTTP/1.1 403 Forbidden');
            return;
        }
        $filePath = $this->getFilesystemPath($path);
        
        if (!file_exists($filePath)) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        
        if (is_dir($filePath)) {
            $this->deleteDirectory($filePath);
        } else {
            unlink($filePath);
        }
        
        header('HTTP/1.1 204 No Content');
    }
    
    /**
     * 处理PROPFIND请求 - 修复：添加必要的WebDAV头
     */
    private function handlePropfind($path)
    {
        $filePath = $this->getFilesystemPath($path);
        
        if (!file_exists($filePath)) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        
        $depth = isset($_SERVER['HTTP_DEPTH']) ? strtolower(trim($_SERVER['HTTP_DEPTH'])) : '1';
        
        // 添加WebDAV头
        header('HTTP/1.1 207 Multi-Status');
        header('DAV: 1, 2, 3');
        header('MS-Author-Via: DAV');
        header('Content-Type: text/xml; charset="utf-8"');
        echo '<?xml version="1.0" encoding="utf-8"?>';
        echo '<D:multistatus xmlns:D="DAV:">';
        
        $this->sendPropfindResponse($filePath, $path, $depth);
        
        echo '</D:multistatus>';
    }
    
    /**
     * 处理MKCOL请求（创建目录）
     */
    private function handleMkcol($path)
    {
        if ($path === '') {
            header('HTTP/1.1 405 Method Not Allowed');
            return;
        }
        $dirPath = $this->getFilesystemPath($path);
        
        if (file_exists($dirPath)) {
            header('HTTP/1.1 405 Method Not Allowed');
            return;
        }
        if (!is_dir(dirname($dirPath))) {
            header('HTTP/1.1 409 Conflict');
            return;
        }
        
        if (mkdir($dirPath, 0755)) {
            header('HTTP/1.1 201 Created');
        } else {
            header('HTTP/1.1 403 Forbidden');
        }
    }
    
    /**
     * 处理COPY请求
     */
    /**
     * 解析COPY/MOVE的目标路径，失败时已输出响应并返回false
     */
    private function prepareDestination($path, &$sourcePath, &$destPath, &$existed)
    {
        if ($path === '') {
            header('HTTP/1.1 403 Forbidden');
            return false;
        }
        $sourcePath = $this->getFilesystemPath($path);
        $destination = isset($_SERVER['HTTP_DESTINATION']) ? $_SERVER['HTTP_DESTINATION'] : '';
        
        if (!$destination) {
            header('HTTP/1.1 400 Bad Request');
            return false;
        }
        
        $destRel = $this->extractWebDavPath(parse_url($destination, PHP_URL_PATH));
        $destRel = trim(str_replace('\\', '/', rawurldecode($destRel)), '/');
        if ($destRel === '' || strpos($destRel, '..') !== false || strpos($destRel, "\0") !== false || $this->is_hide($destRel)) {
            header('HTTP/1.1 403 Forbidden');
            return false;
        }
        $destPath = $this->getFilesystemPath($destRel);
        
        if (!file_exists($sourcePath)) {
            header('HTTP/1.1 404 Not Found');
            return false;
        }

        $srcNorm = strtolower(str_replace('\\', '/', $sourcePath));
        $dstNorm = strtolower(str_replace('\\', '/', $destPath));
        if ($srcNorm === $dstNorm || (is_dir($sourcePath) && strpos($dstNorm, rtrim($srcNorm, '/') . '/') === 0)) {
            header('HTTP/1.1 403 Forbidden');
            return false;
        }
        
        $overwrite = !isset($_SERVER['HTTP_OVERWRITE']) || strtoupper($_SERVER['HTTP_OVERWRITE']) === 'T';
        $existed = file_exists($destPath);
        
        if ($existed && !$overwrite) {
            header('HTTP/1.1 412 Precondition Failed');
            return false;
        }
        if ($existed) {
            is_dir($destPath) ? $this->deleteDirectory($destPath) : unlink($destPath);
        }
        return true;
    }

    private function handleCopy($path)
    {
        if (!$this->prepareDestination($path, $sourcePath, $destPath, $existed)) return;
        
        if (is_dir($sourcePath)) {
            $this->copyDirectory($sourcePath, $destPath);
            $ok = is_dir($destPath);
        } else {
            $ok = copy($sourcePath, $destPath);
        }
        
        if (!$ok) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        header($existed ? 'HTTP/1.1 204 No Content' : 'HTTP/1.1 201 Created');
    }
    
    /**
     * 处理MOVE请求
     */
    private function handleMove($path)
    {
        if (!$this->prepareDestination($path, $sourcePath, $destPath, $existed)) return;
        
        if (!rename($sourcePath, $destPath)) {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        header($existed ? 'HTTP/1.1 204 No Content' : 'HTTP/1.1 201 Created');
    }
    
    /**
     * 处理LOCK请求
     */
    private function handleLock($path)
    {
        // 简化实现：返回成功但不实际锁定
        header('Content-Type: text/xml; charset="utf-8"');
        header('DAV: 1, 2, 3');
        header('Lock-Token: <opaquelocktoken:' . md5($path) . '>');
        echo '<?xml version="1.0" encoding="utf-8"?>';
        echo '<D:prop xmlns:D="DAV:">';
        echo '<D:lockdiscovery>';
        echo '<D:activelock>';
        echo '<D:locktype><D:write/></D:locktype>';
        echo '<D:lockscope><D:exclusive/></D:lockscope>';
        echo '<D:depth>Infinity</D:depth>';
        echo '<D:owner>WebDAV Server</D:owner>';
        echo '<D:timeout>Second-604800</D:timeout>';
        echo '<D:locktoken><D:href>opaquelocktoken:' . md5($path) . '</D:href></D:locktoken>';
        echo '</D:activelock>';
        echo '</D:lockdiscovery>';
        echo '</D:prop>';
    }
    
    /**
     * 处理UNLOCK请求
     */
    private function handleUnlock($path)
    {
        header('HTTP/1.1 204 No Content');
    }
    
    /**
     * 发送文件
     */
    private function sendFile($filePath)
    {
        $mimeType = $this->getMimeType($filePath);
        $fileSize = filesize($filePath);
        $lastModified = filemtime($filePath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('ETag: "' . md5($filePath . $lastModified) . '"');
        
        readfile($filePath);
    }
    
    /**
     * 发送目录列表
     */
    private function sendDirectoryListing($dirPath, $webPath)
    {
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html>';
        echo '<html>';
        echo '<head>';
        echo '<title>WebDAV Directory: ' . htmlspecialchars($webPath) . '</title>';
        echo '<style>';
        echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
        echo 'h1 { color: #333; }';
        echo 'ul { list-style-type: none; padding: 0; }';
        echo 'li { padding: 5px 0; }';
        echo 'a { text-decoration: none; color: #0066cc; }';
        echo 'a:hover { text-decoration: underline; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<h1>Directory: ' . htmlspecialchars($webPath) . '</h1>';
        echo '<ul>';
        
        if ($webPath !== '') {
            $parent = dirname($webPath);
            echo '<li><a href="' . htmlspecialchars($this->getHref($parent === '.' ? '' : $parent, true)) . '">../</a></li>';
        }
        
        $items = scandir($dirPath);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = $dirPath . DIRECTORY_SEPARATOR . $item;
            $itemWebPath = ($webPath === '' ? '' : $webPath . '/') . $item;
            $isDir = is_dir($itemPath);

            if ($this->is_hide($item) || $this->is_hide($itemWebPath)) continue;
            
            echo '<li>';
            echo '<a href="' . htmlspecialchars($this->getHref($itemWebPath, $isDir)) . '">';
            echo htmlspecialchars($item) . ($isDir ? '/' : '');
            echo '</a>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</body>';
        echo '</html>';
    }
    
    /**
     * 发送PROPFIND响应
     */
    private function sendPropfindResponse($filePath, $webPath, $depth)
    {
        $this->sendPropfindItem($filePath, $webPath);
        
        if ($depth !== '0' && is_dir($filePath)) {
            $items = scandir($filePath);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;

                $itemWebPath = ($webPath === '' ? '' : $webPath . '/') . $item;
                if ($this->is_hide($item) || $this->is_hide($itemWebPath)) continue;
                
                $itemPath = $filePath . DIRECTORY_SEPARATOR . $item;
                
                $this->sendPropfindItem($itemPath, $itemWebPath);
                
                if ($depth === 'infinity' && is_dir($itemPath)) {
                    $this->sendPropfindResponse($itemPath, $itemWebPath, 'infinity');
                }
            }
        }
    }
    
    /**
     * 发送PROPFIND项目
     */
    private function sendPropfindItem($filePath, $webPath)
    {
        $isDir = is_dir($filePath);
        $fileSize = $isDir ? 0 : filesize($filePath);
        $lastModified = filemtime($filePath);
        $creationDate = filectime($filePath);
        $displayName = $webPath === '' ? basename($this->getWebDavRoot()) : basename($webPath);
        
        echo '<D:response>';
        echo '<D:href>' . htmlspecialchars($this->getHref($webPath, $isDir)) . '</D:href>';
        echo '<D:propstat>';
        echo '<D:prop>';
        echo '<D:getlastmodified>' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT</D:getlastmodified>';
        echo '<D:creationdate>' . gmdate('Y-m-d\\TH:i:s\\Z', $creationDate) . '</D:creationdate>';
        echo '<D:displayname>' . htmlspecialchars($displayName) . '</D:displayname>';
        echo '<D:getcontentlength>' . $fileSize . '</D:getcontentlength>';
        echo '<D:getcontenttype>' . ($isDir ? 'httpd/unix-directory' : $this->getMimeType($filePath)) . '</D:getcontenttype>';
        echo '<D:getetag>"' . md5($filePath . $lastModified) . '"</D:getetag>';
        echo '<D:resourcetype>' . ($isDir ? '<D:collection/>' : '') . '</D:resourcetype>';
        echo '</D:prop>';
        echo '<D:status>HTTP/1.1 200 OK</D:status>';
        echo '</D:propstat>';
        echo '</D:response>';
    }
    
    /**
     * 获取MIME类型
     */
    private function getMimeType($filePath)
    {
        if (!function_exists('finfo_open')) {
            return 'application/octet-stream';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        return $mimeType ?: 'application/octet-stream';
    }
    
    /**
     * 删除目录
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
            } else {
                unlink($itemPath);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * 复制目录
     */
    private function copyDirectory($source, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
            $destPath = $destination . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }

    // 文件是否隐藏
    private function is_hide($filePath)
    {
        $hidden_files = $this->config['hidden_files'];
        if ($this->config['hide_dot_files']) {
            $hidden_files = array_merge(
                $hidden_files,
                array('.*', '*/.*')
            );
        }

        $filePath = strtolower(str_replace('\\', '/', $filePath));
        foreach ($hidden_files as $hiddenPath) {
            if (fnmatch($hiddenPath, $filePath)) {
                return true;
            }
        }
        return false;
    }
}

try {
    if (empty($conf['webdav']) || $conf['webdav'] != '1') {
        header('HTTP/1.1 403 Forbidden');
        echo 'WebDAV is disabled';
        exit;
    }
    $webdav = new WebDAVServer(ROOT, true, $conf['admin_username'], $conf['admin_password']);
    $webdav->handleRequest();
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'WebDAV Server Error: ' . $e->getMessage();
}
