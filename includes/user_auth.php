<?php

require_once __DIR__ . '/auth.php';

function require_user(): void
{
    require_login();
}
