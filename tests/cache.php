<?
require_once('../libs/MemcacheTags.php');
$cache = new MemcacheTags('localhost', 11211, false, 'remonts');
//var_dump($cache->delete_tag('t1'));
var_dump($cache->get('test', false));
var_dump($cache->get('test', true, 3));
var_dump($cache->get('test', true, 3));
//var_dump($cache->set('test', 'test', ['t1', 't2'], 10));
var_dump($cache->get('test'));
//var_dump($cache->delete_tag('remonts'));
?>