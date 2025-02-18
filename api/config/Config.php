<?php
class Config {
    private static $config = null;

    public static function load() {
        if (self::$config !== null) {
            return self::$config;
        }

        // Load .env file
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }

        self::$config = [
            'database' => [
                'host' => getenv('DB_HOST'),
                'name' => getenv('DB_NAME'),
                'user' => getenv('DB_USER'),
                'pass' => getenv('DB_PASS')
            ],
            'jwt' => [
                'secret' => getenv('JWT_SECRET'),
                'expiry' => getenv('JWT_EXPIRY')
            ],
            'app' => [
                'env' => getenv('APP_ENV'),
                'url' => getenv('APP_URL'),
                'api_url' => getenv('API_URL')
            ],
            'smtp' => [
                'host' => getenv('SMTP_HOST'),
                'port' => getenv('SMTP_PORT'),
                'user' => getenv('SMTP_USER'),
                'pass' => getenv('SMTP_PASS')
            ]
        ];

        return self::$config;
    }

    public static function get($key, $default = null) {
        $config = self::load();
        return array_key_exists($key, $config) ? $config[$key] : $default;
    }
} 