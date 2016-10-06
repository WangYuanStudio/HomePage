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
        ]
    ]
];
