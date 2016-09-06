<?php

return [
    /*
     *    日志记录方式
     * -----------------
     *  single || daily
     */
    'log'       => 'single',


    /*
     *     调试模式
     * ----------------
     *  true || false
     */
    'debug'     => true,


    /**
     * 数据库配置
     */
    'database'  => [
        'master' => [
            "drive"   => "mysql",
            "host"    => "mysql",
            "dbname"  => "wangyuan",
            "user"    => "root",
            "pwd"     => "Wyzxgzs#08",
            "charset" => "utf8"
        ],

        'slave' => [
            [
                "drive"   => "mysql",
                "host"    => "123.207.240.115",
                "dbname"  => "wangyuan",
                "user"    => "root",
                "pwd"     => "Wyzxgzs#08",
                "charset" => "utf8"
            ]
        ]
    ],


    /**
     * Smarty配置
     */
    'smarty'    => [
        "debugging"       => false,
        "caching"         => false,
        "cache_lifetime"  => 120,
        "left_delimiter"  => "<{",
        "right_delimiter" => "}>"
    ],


    /**
     * 类名别名
     */
    'aliases'   => [
        'Api'                     => Zereri\Lib\Replacement\Api::class,
        'Factory'                 => Zereri\Lib\Factory::class,
        'App\Models\Model'        => Zereri\Lib\Model::class,
        'App\Queues\InQueue'      => Zereri\Lib\InQueue::class,
        'App\Middles\MiddleWare'  => Zereri\Lib\MiddleWare::class,
        'App\Controllers\Smarty'  => Zereri\Lib\Replacement\Smarty::class,
        'App\Controllers\Session' => Zereri\Lib\Replacement\Session::class,
        'App\Controllers\Cache'   => Zereri\Lib\Replacement\Cache::class
    ],


    /**
     * 缓存配置
     */
    'cache'     => [
        "drive" => "redis",
        'time'  => 3600
    ],


    /**
     * Memcached服务器配置
     */
    'memcached' => [
        'server' => [
            ['127.0.0.1', 11211]
        ]
    ],


    /**
     * redis服务器配置
     */
    'redis'     => [
        'server' => ["redis", 6379, "Wyzxgzs#08_redis"]
    ],


    /**
     *      Session
     * --------------------
     *   file || memcached || redis
     */
    'session'   => [
        'drive' => 'file'
    ],


    'common_status' => [
        200 => '',
        300 => 'Invalid login status.',
        301 => 'Permission denied.',
        302 => 'Verify code was wrong.',
        303 => 'Operations are too frequent',
        304 => 'permission is existed',
        305 => 'role is existed',
        306 => 'no permission list',
        307 => 'no role list',
        308 => 'fail to delete',
        309 => 'fail to update',
        310 => 'permission is already assign'，
        311 => 'Did not find relevant content.'
        ]

];