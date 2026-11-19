<?php

/*
 * Container healthcheck: GET /up on the local server with the Host header of
 * APP_URL, because trustHosts() rejects any other host outside "local".
 */

$host = parse_url((string) getenv('APP_URL'), PHP_URL_HOST) ?: 'localhost';
$port = ltrim((string) getenv('SERVER_NAME'), ':') ?: '8080';

$context = stream_context_create([
    'http' => [
        'header' => "Host: {$host}\r\n",
        'timeout' => 4,
        'ignore_errors' => true,
    ],
]);

$body = @file_get_contents("http://127.0.0.1:{$port}/up", false, $context);
$status = isset($http_response_header[0]) ? (int) explode(' ', $http_response_header[0])[1] : 0;

exit($body !== false && $status === 200 ? 0 : 1);
