<?php

class Cache
{
    const DataDir = SYSTEM_ROOT.'data/';
    const Expire = 0;

    private function getCacheKey($name){
        return self::DataDir.$name.'.php';
    }

    private function getRaw($name){
        $filename = $this->getCacheKey($name);

        if (!is_file($filename)) return;

        $content = @file_get_contents($filename);
        if (false === $content) return;

        $expire = (int) substr($content, 8, 12);
        if (0 != $expire && time() - $expire > filemtime($filename)) {
            //缓存过期删除缓存文件
            $this->unlink($filename);
            return;
        }

        $content = substr($content, 32);

        return is_string($content) ? $content : null;
    }

    public function has($name){
        return $this->getRaw($name) !== null;
    }

    public function get($name, $default = null){
        $raw = $this->getRaw($name);

        if (is_null($raw)){
            return $default;
        } elseif (is_numeric($raw)) {
            return $raw;
        } else {
            return unserialize($raw);
        }
    }

    public function set($name, $value, $expire = null){
        if (is_null($expire)) $expire = self::Expire;

        $filename = $this->getCacheKey($name);

        if (is_numeric($value)) {
            $data = (string) $value;
        } else {
            $data = serialize($value);
        }
        
        $data   = "<?php\n//" . sprintf('%012d', $expire) . "\n exit();?>\n" . $data;
        $result = file_put_contents($filename, $data);

        if ($result) {
            clearstatcache();
            return true;
        }

        return false;
    }

    public function delete($name){
        $filename = $this->getCacheKey($name);
        $this->unlink($filename);
        return true;
    }

    private function unlink($path)
    {
        try {
            return is_file($path) && unlink($path);
        } catch (\Exception $e) {
            return false;
        }
    }
}