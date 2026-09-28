<?php

/*
|--------------------------------------------------------------------------
| Password hashing
|--------------------------------------------------------------------------
|
| Argon2id is the default: memory-hard, so GPU cracking is expensive. Hosts
| whose PHP build lacks Argon2 fall back to bcrypt automatically.
|
| verify=false lets the Argon2id hasher still check older bcrypt hashes
| (password_verify understands both); Laravel then rehashes them to
| Argon2id the next time that user signs in (rehash_on_login).
|
*/

$argonAvailable = defined('PASSWORD_ARGON2ID');
$driver = env('HASH_DRIVER', 'argon2id');

return [

    'driver' => $driver === 'argon2id' && ! $argonAvailable ? 'bcrypt' : $driver,

    'bcrypt' => [
        'rounds' => (int) env('BCRYPT_ROUNDS', 12),
        'verify' => false,
        'limit' => null,
    ],

    'argon' => [
        'memory' => (int) env('ARGON_MEMORY', 65536),
        'threads' => (int) env('ARGON_THREADS', 1),
        'time' => (int) env('ARGON_TIME', 4),
        'verify' => false,
    ],

    'rehash_on_login' => true,

];
