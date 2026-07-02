<?php

function generatePayFastSignature(array $data, string $passphrase = null): string
{
    // Remove signature if it exists
    unset($data['signature']);

    // Step 1: Sort parameters alphabetically
    ksort($data);

    // Step 2: Build query string
    $pfOutput = [];
    foreach ($data as $key => $val) {
        if ($val !== '') {
            $pfOutput[] = $key . '=' . urlencode(trim($val));
        }
    }

    $pfString = implode('&', $pfOutput);

    // Step 3: Add passphrase
    if ($passphrase) {
        $pfString .= "&passphrase=" . urlencode(trim($passphrase));
    }

    // Step 4: Return MD5 signature
    return md5($pfString);
}