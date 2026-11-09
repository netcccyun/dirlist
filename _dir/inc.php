<?php
error_reporting(0);
define("DIR_INIT", true);
define("SYSTEM_ROOT", dirname(__FILE__).'/');
define("ROOT", dirname(SYSTEM_ROOT).'/');
define("PAGE_ROOT", SYSTEM_ROOT.'page/');
define("VERSION", '1.6');

date_default_timezone_set("PRC");

require SYSTEM_ROOT.'functions.php';
require SYSTEM_ROOT.'Cache.class.php';
require SYSTEM_ROOT.'DirList.class.php';
require SYSTEM_ROOT.'FileMgr.class.php';

$CACHE = new Cache();
$conf = $CACHE->get('config');
if(!$conf && $CACHE->exists('config')){
	usleep(200000);
	$conf = $CACHE->get('config');
	if(!$conf) sysmsg('配置文件读取失败，请检查 _dir/data/config.php 是否损坏');
}
if(!$conf){
	if(!$CACHE->set('config', ['admin_username'=>'admin','admin_password'=>md5('123456'),'title'=>'彩虹目录列表', 'keywords'=>'彩虹目录列表,Directory Lister目录列表,目录索引','description'=>'彩虹目录列表程序','announce'=>'','footer'=>'', 'name_encode'=>'utf8', 'file_hash'=>'1', 'cache_indexes'=>'0', 'footer_bar'=>'1', 'readme_md'=>'1', 'auth'=>'0', 'webdav'=>'0', 'nav'=>'音乐搜索*http://music.hi.cn/|图片压缩*https://tinypng.com/|今日热榜*https://tophub.today/'])){
		sysmsg('配置项初始化失败，可能无文件写入权限');
	}
	$conf = $CACHE->get('config');
}

$conf_defaults = [
	'title' => '',
	'keywords' => '',
	'description' => '',
	'announce' => '',
	'footer' => '',
	'nav' => '',
	'name_encode' => 'utf8',
	'file_hash' => '0',
	'cache_indexes' => '0',
	'footer_bar' => '1',
	'readme_md' => '1',
	'tinyview' => '0',
	'download_ext' => '',
	'editor' => '0',
	'page_size' => '0',
	'cdnpublic' => '0',
	'webdav' => '0',
	'admin_username' => 'admin',
	'admin_password' => '',
	'admin_session' => '',
];
$conf = array_merge($conf_defaults, is_array($conf) ? $conf : []);

$scriptpath=str_replace('\\','/',$_SERVER['SCRIPT_NAME']);
$sitepath = substr($scriptpath, 0, strrpos($scriptpath, '/'));
$siteurl = (is_https() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].
$sitepath.'/';

if($conf['cdnpublic']==1){
	$cdnpublic = 'https://cdnjs.webstatic.cn/ajax/libs/';
	$cdnpublic2 = $cdnpublic;
}elseif($conf['cdnpublic']==2){
	$cdnpublic = 'https://s4.zstatic.net/ajax/libs/';
	$cdnpublic2 = $cdnpublic;
}elseif($conf['cdnpublic']==3){
	$cdnpublic = 'https://cdnjs.snrat.com/ajax/libs/';
	$cdnpublic2 = $cdnpublic;
}else{
	$cdnpublic = './_dir/static/libs/';
	$cdnpublic2 = 'https://cdnjs.webstatic.cn/ajax/libs/';
}

$islogin = 0;
if(!empty($_COOKIE["admin_session"]) && !empty($conf['admin_session']) && $conf['admin_session']===$_COOKIE["admin_session"]) {
	$islogin=1;
}
