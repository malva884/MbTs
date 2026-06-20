<?php
return [
    'host' => env('SSH_HOST', '10.141.1.22'),
    'username' => env('SSH_USERNAME', 'dev'),
    'private_key_path' => env('SSH_PRIVATE_KEY_PATH', '/path/to/privatekey'),
];
