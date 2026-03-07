<?php

declare(strict_types=1);

namespace System\Cli\Commands;

use PDO;
use PDOException;
use System\Cli\Command;
use System\Foundation\Application;

class MigrateCommand extends Command
{
    public function name(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Run SQL migrations from database/update or database/drop';
    }

    public function aliases(): array
    {
        return ['Migrate', 'migrate:update', 'migrate:drop'];
    }

    public function handle(array $args, Application $app): int
    {
        $action = $this->resolveAction($args);

        if ($action === null) {
            fwrite(STDOUT, "Usage: php aiti migrate [update|drop]\n");
            return 1;
        }

        $directory = $app->basePath('database/' . $action);
        if (!is_dir($directory)) {
            fwrite(STDOUT, 'Migration directory not found: ' . $directory . PHP_EOL);
            return 1;
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . '*.sql');
        if ($files === false || $files === []) {
            fwrite(STDOUT, 'No SQL files found in ' . $directory . PHP_EOL);
            return 0;
        }

        sort($files);

        try {
            $pdo = $this->connect();
            $pdo->beginTransaction();

            foreach ($files as $file) {
                $sql = file_get_contents($file);
                if ($sql === false) {
                    throw new \RuntimeException('Unable to read migration file: ' . $file);
                }

                fwrite(STDOUT, 'Running ' . basename($file) . PHP_EOL);
                $pdo->exec($sql);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            fwrite(STDOUT, 'Migration failed: ' . $exception->getMessage() . PHP_EOL);
            return 1;
        }

        fwrite(STDOUT, 'Migration ' . $action . ' completed.' . PHP_EOL);
        return 0;
    }

    /**
     * @param array<int, string> $args
     */
    private function resolveAction(array $args): ?string
    {
        $first = $args[0] ?? null;
        if (in_array($first, ['update', 'drop'], true)) {
            return $first;
        }

        $invokedCommand = $_SERVER['argv'][1] ?? null;
        if ($invokedCommand === 'migrate:update') {
            return 'update';
        }

        if ($invokedCommand === 'migrate:drop') {
            return 'drop';
        }

        return null;
    }

    private function connect(): PDO
    {
        $dsn = trim((string) ($_ENV['DB_DSN'] ?? ''));
        $username = (string) ($_ENV['DB_USERNAME'] ?? '');
        $password = (string) ($_ENV['DB_PASSWORD'] ?? '');

        if ($dsn === '') {
            throw new PDOException('DB_DSN is not configured.');
        }

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
