<?php

return [
    'anchor' => ['comment-%{id}%', 'form-comment'],
    'path' => '-comment',
    'thread' => 1, // Set to `0` or `false` to disable comment thread (TODO: multi-level comment thread)
    'page' => [
        'state' => 'page', // Default file extension for new comment (`draft` to save/moderate the comment and `page` to publish the comment immediately)
        'type' => 'HTML',
        'status' => 2
    ],
    'max' => [
        'author' => 100,
        'email' => 100,
        'link' => 100,
        'content' => 1700
    ],
    'min' => [
        'author' => 1,
        'email' => 1,
        'link' => 0,
        'content' => 1
    ],
    'query_x' => ['<script ', '<iframe '], // Block by word(s)
    'user_ip_x' => [], // Block by IP address(es)
    'user_agent_x' => [] // Block by user agent word(s)
];