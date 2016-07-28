<?php
// 环境配置
return [
    // 异常页面的模板文件（正式部署时建议使用此设置）
    'exception_tmpl' => APP_PATH . 'view' . DS . 'Msg' . DS . 'exception.html',
    // 静态资源文件时间戳（正式部署时，在更新版本之后，可修改此设置以使浏览器强制更新资源文件）
    'static_file_timestamp' => '',
    // 网站统计脚本
    'stat_script' => '',
    // 登录页面提示信息
    'login_page_msg' => '',
    // 是否使用CDN传输部分静态资源文件（如jquery.js、bootstrap.js），默认值：true
    //'static_file_cdn' => false,
    // 是否关闭网站，默认值：false
    //'site_close' => true,
];
