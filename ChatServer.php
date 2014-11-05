<?php
// 消息文件
$chatFile = dirname(__FILE__).'/chat.txt';
// 保存聊天记录的文件夹
$chatLogs = dirname(__FILE__).'/ChatLogs';
// 设置系统时区 PHP5支持
if(function_exists('date_default_timezone_set')) { date_default_timezone_set('UTC'); }
// 开始运行服务器
$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
if ($action=='SendMessage') {
    $nickname = isset($_REQUEST['nickname'])?$_REQUEST['nickname']:null;
    $context  = isset($_REQUEST['context'])?$_REQUEST['context']:null;
    SendMessage($nickname,$context);
} else {
    ChatServer();
}

/**
 * 获取当前时间戳
 *
 * @return integer
 */
function now(){
    return time() + (8*3600);
}
/**
 * 返回当前 Unix 时间戳和微秒数
 *
 * @return float
 */
function microtime_float(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
/**
 * 读取文件
 *
 * @param string $p1    filename
 * @return string
 */
function read_file($p1){
    if (!is_file($p1)) { return ; }
    $fp   = fopen($p1,'rb');
    $size = filesize($p1);
    if ((int)$size==0) { return ; }
    $R = fread($fp,$size);
    fclose($fp);
    return $R;
}
/**
 * 将文本保存成文件
 *
 * @param string $p1    filename
 * @param string $p2    content
 * @param bool   $p3    写入模式 false:追加
 */
function save_file($p1,$p2='',$p3=true){
    $fp = fopen($p1,($p3?'wb':'ab'));
    flock($fp,LOCK_EX + LOCK_NB);
    do {
        $isDo = fwrite($fp,$p2);
        if (!$isDo) {
            usleep(0.01 * 1000000);
        }
    } while (!$isDo);
    flock($fp, LOCK_UN);
    fclose($fp);
}
/**
 * 转换特殊字符为HTML实体
 *
 * @param   string $p1
 * @return  string
 */
function h2c($p1){
    return empty($p1)?null:htmlspecialchars($p1);
}
/**
 * 批量创建目录
 *
 * @param string $p1    文件夹路径
 * @param int    $p2    权限
 * @return bool
 */
function mkdirs($p1, $p2 = 0777){
    if (!is_dir($p1)) {
        mkdirs(dirname($p1), $p2);
        return @mkdir($p1, $p2);
    }
    return true;
}
// 聊天服务器
function ChatServer(){
    global $chatFile;
    $beginTime = microtime_float(); $max = 25;
    $timeStamp = isset($_REQUEST['timestamp'])?$_REQUEST['timestamp']:0;
    while (true) {
        $lastTime = filemtime($chatFile); clearstatcache();
        if (($isModify = (int)$lastTime > (int)($timeStamp)) || floatval(microtime_float()-$beginTime) >= floatval($max)) {
            $result = array('timestamp' => $lastTime);
            $result = $isModify?array_merge($result,array('context' => json_decode(read_file($chatFile),true))):$result;
            echo json_encode($result);
            break;
        } else {
            usleep(0.05 * 1000000);
        }
    }
}
// 发送聊天消息
function SendMessage($nickname,$context){
    if ($context) {
        $result = array(
            'CODE' => 200,
            'TIME' => date('Y-m-d H:i:s',now()),
            'NAME' => $nickname,
            'TEXT' => $context,
        );
    } else {
        $result = array('CODE' => 413);
    }
    if ($result['CODE']==200) {
        global $chatFile,$chatLogs;
        $context = h2c($context);
        save_file($chatFile,json_encode($result));
        $sendTime = date('Y-m-d H:i:s',now());
        mkdirs($chatLogs); $context = "[{$nickname}] Say: {$context} {$sendTime}\r\n";
        save_file($chatLogs.'/'.date('Y-m-d',now()).'.log',$context,false);
    }
    echo json_encode($result);
}