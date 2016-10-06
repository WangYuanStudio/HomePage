<?php

return [
    "v1" => [
        "/api/list"                         => [
            "GET" => "Tribune@test"
        ],
        "/tribune/{department}/list/{page}" => [
            "GET"  => "Tribune@index"
        ],
        "/tribune/publish"                  => [
            "POST" => "Tribune@publish"
        ]
    ],

    "v2" => [

    ]
];
