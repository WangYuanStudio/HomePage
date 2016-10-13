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
        "/homework/setexcellentworks/{rid}"               => [
            "PATCH" => "Homework@setExcellentWorks"
        ],
        "/homework/cancelexcellentworks/{rid}"            => [
            "PATCH" => "Homework@cancelExcellentWorks"
        ],
        "/homework/deletework/{rid}"                      => [
            "DELETE" => "Homework@deleteWork"
        ],
        "/homework/addtask"                               => [
            "POST" => "Homework@addTask"
        ],
        "/homework/updatetask"                            => [
            "POST" => "Homework@updateTask"
        ],
        "/homework/deletetask/{tid}"                      => [
            "DELETE" => "Homework@deleteTask"
        ],
        "/homework/settaskoff/{tid}"                      => [
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
        ],
         //  //报名-实现报名      /测试完毕e
        "/sign/insertnews" =>[
            "POST"  => "Sign@Insertnews"
        ],
        // //报名系统-确认报名审核通过或不通过    测试完毕
        "/sign/upreview" =>[
            "PATCH" => "Sign@signupreview"
        ],
        //报名系统-获取报名列表     测试完毕
        "/sign/getsignlist/{page}/{department}/{privilege}" =>[
            "GET" => "Sign@CheckPower"
        ],
        //报名-一键通过审核(本页)     测试
        "/sign/allpass"  =>[
            "PATCH" => "Sign@Allpass"
        ],
        //报名-一键删除报名(全部未审核)
        "/sign/alldelete"  =>[
             "DELETE" => "Sign@Alldelete"
        ],
        //报名-审核淘汰      测试完毕
        "/sign/elimination/{uid}" =>[
            "DELETE"   =>"Sign@Elimination"
        ],
        //报名-搜索      测试完毕
        "/sign/content_search" => [
          "POST"  =>"Sign@Content_search"
        ],
        //报名-设置报名时间      测试完毕
        "/sign/addsigntime" =>[
          "POST"  =>"Sign@Addsigntime"
        ],
        //报名-获取所有报名时间    测试完毕
        "/sign/getsigntime/{page}" =>[
          "GET"   =>"Sign@Getsigntime"
        ],
        //删除报名时间
        "/sign/deletesigntime/{time_id}"  =>[
          "DELETE"=>"Sign@Deletesigntime"
        ],
        //获取最新报名时间
        "/sign/getnewsigntime"    =>[
          "GET"   =>"Sign@Getnewsigntime"
        ],
        //报名-修改报名时间     测试完毕
        "/sign/updatesigntime"   =>[
          "PATCH"  =>"Sign@Updatesigntime"
        ],
        //报名-列表通过/否决    测试完毕
        "/sign/listpass"   =>[
          "PATCH"  =>"Sign@Listpass"
        ], 
        //报名-发送短信(120s)    测试完毕
        "/sign/sendphone"  => [
          "POST"  =>"Sign@sendPhone"
        ],
        //判断短信     测试完毕
        "/sign/judge_me"  =>[
          "POST"  =>"Sign@Judge_me"
        ],
        //官网-实现注册     1
        "/enroll/register/{token}"  => [
          "GET"  =>"Enroll@Register"
        ],
        //官网-用户更换头像   bug
        "/enroll/uploadphoto"  =>[
           "POST"   =>"Enroll@UploadPhoto"
        ],
        //官网-发送邮件    1
        "/enroll/sendemail"    =>[
          "POST"  => "Enroll@sendEmail"
        ],
        //官网-限制账号      1
        "/enroll/limituser"   =>[
          "PATCH"  => "Enroll@Limituser"
        ],
        //官网-解除限制账号     1
        "/enroll/relieve"    =>[
          "PATCH"  =>"Enroll@Relieve"
        ],
        //官网-找回密码之发送邮箱    1
        "/enroll/searchpsw"  =>[
          "POST" =>"Enroll@Searchpsw"
        ],
        //官网-找回密码之修改密码    1
        "/enroll/supdatepsw" =>[
          "PATCH"  =>"Enroll@Supdatepsw"
        ],
        //官网-修改密码
        "/enroll/updatepsw"  =>[
          "PATCH"  =>"Enroll@Updatepsw"
        ],
        //官网-修改个人信息      1
        "/enroll/updateuser" =>[
          "PATCH"  =>"Enroll@Updateuser"
        ],
        //官网-修改密码之发送验证码
        "/enroll/sendverify"  =>[
          "POST" =>"Enroll@Sendverify"
        ]
    ]
];
