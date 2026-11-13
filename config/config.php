<?php
// Override with environment variables; never commit real credentials.
return [
    'db' => [
        'dsn'  => 'mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=' . (getenv('DB_NAME') ?: 'nested_forum') . ';charset=utf8mb4',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    'max_indent_depth' => 8,    // visual indentation cap (labels stay accurate)
    'max_write_depth'  => 50,   // reject replies nested deeper than this
    'max_traversal'    => 5000, // max comments rendered per discussion
    'max_body'         => 2000,
];
