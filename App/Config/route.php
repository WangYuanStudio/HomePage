<?php

return [
    "v1" => [
        "/api/list" => [
            "GET" => "Api@index"
        ],


        "/tribune/posts/{department}/{page}" => [
            "GET" => "Tribune@index"
        ],
        "/tribune/response"                  => [
            "POST" => "Tribune@response"
        ],
        "/tribune/publish"                   => [
            "POST" => "Tribune@publish"
        ],
        "/tribune/post/{pid}/{page}"         => [
            "GET" => "Tribune@postInfo"
        ],


        "/verifyImg"  => [
            "GET" => "Common@verifyImg"
        ],
        "/verifyText" => [
            "GET" => "Common@verifyType"
        ],
        "/verify"     => [
            "GET" => "Common@verify"
        ],


        "/homework/getexcellentworksfromuid/{uid}/{page}" => [
            "GET" => "Homework@getExcellentWorksFromUid"
        ],
        "/homework/getexcellentworksfromtid/{tid}/{page}" => [
            "GET" => "Homework@getExcellentWorksFromTid"
        ],
        "/homework/uploadwork"                            => [
            "POST" => "Homework@uploadWork"
        ],
        "/homework/getalltasks/{department}/{page}"       => [
            "GET" => "Homework@getAllTasks"
        ],
        "/homework/getunzipworkdir/{rid}"                 => [
            "GET" => "Homework@getUnzipWorkDir"
        ],
        "/homework/getunzipworkfile/{path}"               => [
            "GET" => "Homework@getUnzipWorkFile"
        ],
        "/homework/getworksfromtid/{tid}/{page}"          => [
            "GET" => "Homework@getWorksFromTid"
        ],
        "/homework/getallworks/{type}/{page}"             => [
            "GET" => "Homework@getAllWorks"
        ],
        "/homework/correctwork"                           => [
            "PATCH" => "Homework@correctWork"
        ],
        "/homework/setexcellentworks"                     => [
            "PATCH" => "Homework@setExcellentWorks"
        ],
        "/homework/cancelexcellentworks"                  => [
            "PATCH" => "Homework@cancelExcellentWorks"
        ],
        "/homework/deletework"                            => [
            "DELETE" => "Homework@deleteWork"
        ],
        "/homework/addtask"                               => [
            "POST" => "Homework@addTask"
        ],
        "/homework/updatetask"                            => [
            "PATCH" => "Homework@updateTask"
        ],
        "/homework/deletetask"                            => [
            "DELETE" => "Homework@deleteTask"
        ],
        "/homework/settaskoff"                            => [
            "PATCH" => "Homework@setTaskOff"
        ],
        "/homework/exportdata/{type}"                     => [
            "GET" => "Homework@exportData"
        ],
        "/homework/searchtask/{title}/{page}"             => [
            "GET" => "Homework@searchTask"
        ],

        "/login/checklogin/{mail}/{password}/{log}" => [
            "GET" => "Login@CheckLogin"
        ],
        "/login/Getuserinfo"                        => [
            "GET" => "Login@GetUserinfo"
        ],
        "/login/logout/{token}"                     => [
            "GET" => "Login@logout"
        ],
            
        "/index/ShowMember/{department}/{num}/{page}"            => [
            "GET" => "Index@ShowMember"
        ],
        "/index/getmessage/{auto}/{num}/{page}"                  => [
            "GET" => "Index@GetMessage"
        ],
        "/index/send_message"                                    => [
            "POST" => "Index@Send_message"
        ],
        "/index/del_mes/{id}"                                    => [
            "DELETE" => "Index@del_mes"
        ],
        "/index/publish_article"                                 => [
            "POST" => "Index@publish_article"
        ],
        "/index/del_article"                                     => [
            "POST" => "Index@del_article"
        ],
        "/index/get_article/{num}/{page}"                        => [
            "GET" => "Index@get_article"
        ],
        "/index/check_mes/{id}"                                  => [
            "DELETE" => "Index@check_mes"
        ],
        "/index/edt_article"                                     => [
            "PUT" => "Index@edt_article"
        ],
        "/index/edt_member"                                      => [
            "PUT" => "Index@edt_member"
        ],
        "/index/del_member/{id}"                                 => [
            "DELETE" => "Index@del_member"
        ],
        "/index/edt_message"                                     => [
            "PUT" => "Index@edt_message"
        ],
        "/index/add_member"                                      => [
            "POST" => "Index@add_member"
        ],
        "/index/del_allmes"                                      => [
            "PUT" => "Index@del_allmes"
        ],
        "/index/search_page/{key}/{title}/{num}"                 => [
            "GET" => "Index@search_page"
        ],
        "/index/total_message/{title}/{num}/{auto}/{department}" => [
            "GET" => "Index@total_message"
        ],
        "/index/search_data/{title}/{key}/{page}/{num}/{auto}"   => [
            "GET" => "Index@search_data"
        ]
    ]
];
