<?php

function generatePayFastSignature(array $data, string $passphrase = null): string
{
    unset($data['signature']);

    ksort($data);

    $queryString = http_build_query($data, '', '&', PHP_QUERY_RFC3986);

    if ($passphrase) {
        $queryString .= '&passphrase=' . urlencode($passphrase);
    }

    return md5($queryString);
}