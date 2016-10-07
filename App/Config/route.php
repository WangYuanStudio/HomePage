<?php

return [
    "v1" => [
        "/api/list"                         => [
            "GET" => "Api@index"
        ],
        "/tribune/posts/{department}/{page}" => [
            "GET"  => "Tribune@index"
        ],
        "/tribune/publish"                  => [
            "POST" => "Tribune@publish"
        ],
        "/homework/getexcellentworksfromuid/{uid}/{page}" => [
            "GET" => "Homework@getExcellentWorksFromUid"
        ],
        "/homework/getexcellentworksfromtid/{tid}/{page}" => [
            "GET" => "Homework@getExcellentWorksFromTid"
        ],
        "/homework/uploadwork" => [
            "POST" => "Homework@uploadWork"
        ],
        "/homework/getalltasks/{department}/{page}" => [
            "GET" => "Homework@getAllTasks"
        ],
        "/homework/getunzipworkdir/{rid}" => [
            "GET" => "Homework@getUnzipWorkDir"
        ],
        "/homework/getunzipworkfile/{path}" => [
            "GET" => "Homework@getUnzipWorkFile"
        ],
        "/homework/getworksfromtid/{tid}/{page}" => [
            "GET" => "Homework@getWorksFromTid"
        ],
        "/homework/getallworks/{type}/{page}" => [
            "GET" => "Homework@getAllWorks"
        ],
        "/homework/correctwork" => [
            "PATCH" => "Homework@correctWork"
        ],
        "/homework/setexcellentworks" => [
            "PATCH" => "Homework@setExcellentWorks"
        ],
        "/homework/cancelexcellentworks" => [
            "PATCH" => "Homework@cancelExcellentWorks"
        ],
        "/homework/deletework" => [
            "DELETE" => "Homework@deleteWork"
        ],
        "/homework/addtask" => [
            "POST" => "Homework@addTask"
        ],
        "/homework/updatetask" => [
            "PATCH" => "Homework@updateTask"
        ],
        "/homework/deletetask" => [
            "DELETE" => "Homework@deleteTask"
        ],
        "/homework/settaskoff" => [
            "PATCH" => "Homework@setTaskOff"
        ],
        "/homework/exportdata/{type}" => [
            "GET" => "Homework@exportData"
        ],
        "/homework/searchtask/{title}/{page}" => [
            "GET" => "Homework@searchTask"
        ]
    ]
];
