<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, the Argon2id algorithm is
    | used. Supported: "bcrypt", "argon", "argon2id".
    |
    | Note: live authentication runs through Supabase, which hashes passwords
    | on its own servers. This driver only applies to the legacy Sanctum path
    | (App\Http\Controllers\AuthController) that the frontend no longer uses.
    | bcrypt hashes created earlier still verify fine alongside argon2id.
    |
    */

    'driver' => 'argon2id',

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Bcrypt algorithm. This will allow you
    | to control the amount of time it takes to hash the given password.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
        'limit' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Argon algorithm. These will allow you
    | to control the amount of time it takes to hash the given password.
    |
    | 'verify' is false so that pre-existing bcrypt hashes (e.g. the seeded
    | test user) still pass Hash::check during the migration to argon2id.
    | With verify=true the argon2id hasher throws on any non-argon2id hash.
    |
    */

    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
        'verify' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    |
    | Setting this option to true will tell Laravel to automatically rehash
    | the user's password during login if the configured work factor has
    | changed, ensuring that legacy bcrypt hashes migrate to argon2id.
    |
    */

    'rehash_on_login' => true,

];
