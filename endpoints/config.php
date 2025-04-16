<?php

declare(strict_types=1);

$config = [
    'curl' => [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => 'utf-8',
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_CONNECTTIMEOUT => CONNECTION_TIMEOUT,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
        CURLOPT_HEADER         => false,
        CURLOPT_BUFFERSIZE     => 0,
        CURLOPT_TCP_FASTOPEN   => true,
        CURLOPT_TCP_KEEPALIVE  => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HEADEROPT      => CURLHEADER_SEPARATE,
    ],

];
