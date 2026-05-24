<?php

declare(strict_types=1);

class ApiTestClient
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
    }

    public function request(array $options): array
    {
        $file = (string) ($options['file'] ?? '');
        $method = strtoupper((string) ($options['method'] ?? 'GET'));
        $get = (array) ($options['get'] ?? []);
        $post = (array) ($options['post'] ?? []);
        $json = $options['json'] ?? null;
        $session = (array) ($options['session'] ?? []);

        $this->seedSession($session);

        $oldGet = $_GET ?? [];
        $oldPost = $_POST ?? [];
        $oldServer = $_SERVER ?? [];
        $oldInput = $GLOBALS['YOPY_RAW_INPUT'] ?? null;
        $hadInput = array_key_exists('YOPY_RAW_INPUT', $GLOBALS);

        $_GET = $get;
        $_POST = $post;
        $_SERVER['REQUEST_METHOD'] = $method;

        if ($json !== null) {
            $GLOBALS['YOPY_RAW_INPUT'] = json_encode($json);
        } elseif ($hadInput) {
            unset($GLOBALS['YOPY_RAW_INPUT']);
        }

        http_response_code(200);

        ob_start();
        try {
            $target = $this->resolvePath($file);
            include $target;
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() !== '__YOPY_TEST_TERMINATE__') {
                throw $exception;
            }
        }
        $output = (string) ob_get_clean();

        $code = http_response_code();

        $_GET = $oldGet;
        $_POST = $oldPost;
        $_SERVER = $oldServer;

        if ($hadInput) {
            $GLOBALS['YOPY_RAW_INPUT'] = $oldInput;
        } else {
            unset($GLOBALS['YOPY_RAW_INPUT']);
        }

        return [
            'code' => $code,
            'json' => json_decode($output, true),
            'raw' => $output,
        ];
    }

    private function resolvePath(string $file): string
    {
        if ($file === '') {
            throw new InvalidArgumentException('API file is required.');
        }

        if (str_contains($file, DIRECTORY_SEPARATOR) || str_contains($file, '/')) {
            return $file;
        }

        return $this->rootPath . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . $file;
    }

    private function seedSession(array $sessionData): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }

        session_start();
        $_SESSION = $sessionData;
        session_write_close();
    }
}
