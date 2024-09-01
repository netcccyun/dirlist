<?php
error_reporting(0);
define("DIR_INIT", true);
define("SYSTEM_ROOT", dirname(__FILE__).'/');
define("ROOT", dirname(SYSTEM_ROOT).'/');
define("PAGE_ROOT", SYSTEM_ROOT.'page/');
define("VERSION", '1.5');

date_default_timezone_set("PRC");

require SYSTEM_ROOT.'functions.php';
require SYSTEM_ROOT.'Cache.class.php';
require SYSTEM_ROOT.'DirList.class.php';
require SYSTEM_ROOT.'FileMgr.class.php';

$CACHE = new Cache();
$conf = $CACHE->get('config');
if(!$conf){
	if(!$CACHE->set('config', ['admin_username'=>'admin','admin_password'=>md5('123456'),'title'=>'彩虹目录列表', 'keywords'=>'彩虹目录列表,Directory Lister目录列表,目录索引','description'=>'彩虹目录列表程序','announce'=>'','footer'=>'', 'name_encode'=>'utf8', 'file_hash'=>'1', 'cache_indexes'=>'0', 'footer_bar'=>'1', 'readme_md'=>'1', 'auth'=>'0', 'nav'=>'音乐搜索*http://music.hi.cn/|图片压缩*https://tinypng.com/|今日热榜*https://tophub.today/'])){
		sysmsg('配置项初始化失败，可能无文件写入权限');
	}
	$conf = $CACHE->get('config');
}

$scriptpath=str_replace('\\','/',$_SERVER['SCRIPT_NAME']);
$sitepath = substr($scriptpath, 0, strrpos($scriptpath, '/'));
$siteurl = (is_https() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$sitepath.'/';

$cdnpublic = 'https://s4.zstatic.net/ajax/libs/';

if(isset($_COOKIE["admin_session"]))
{
	if($conf['admin_session']===$_COOKIE["admin_session"]) {
		$islogin=1;
	}
}
