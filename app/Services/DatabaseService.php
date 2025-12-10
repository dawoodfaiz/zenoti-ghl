<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use PDOStatement;

class DatabaseService
{
    private static ?self $instance = null;
    private PDO $conn;
    public string $tablePrefix;

    private function __construct()
    {
        $dsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']};charset=UTF8";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ];

        try {
            $this->conn = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $options);
            $this->tablePrefix = $_ENV['DB_TABLE_PREFIX'];
        } catch (PDOException $e) {
            dd("MySQL Connection Failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->conn;
    }

    public function query(string $query, array $params = []): PDOStatement|false
    {
        try {
            $stmt = $this->conn->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            $stmt->execute();

            return $stmt;
        } catch (PDOException $e) {
            dd("Query Failed to Execute: " . $e->getMessage());
        }
    }

    public function save(array $data, string $table): string|false
    {
        $table = "{$this->tablePrefix}_{$table}";

        $fieldsArray = array_keys($data);
        $fields = implode(', ', $fieldsArray);

        $valuesArray = array_map([$this, 'placeholder'], $fieldsArray);
        $values = implode(', ', $valuesArray);

        $data = array_map([$this, 'nullable'], $data);

        $query = "INSERT INTO {$table} ({$fields}) VALUES ({$values})";

        self::getInstance()->query($query, $data);

        return self::getInstance()->getConnection()->lastInsertId();
    }

    public function update(array $data, string $table, array $whereParams): void
    {
        $table = "{$this->tablePrefix}_{$table}";

        $fieldsArray = array_keys($data);
        $fieldsArray = array_map([$this, 'updatePlaceholder'], $fieldsArray);
        $fields = implode(', ', $fieldsArray);

        $whereParamsArray = array_keys($whereParams);
        $whereParamsArray = array_map([$this, 'updatePlaceholder'], $whereParamsArray);
        $whereClause = implode(' AND ', $whereParamsArray);

        $data = array_map([$this, 'nullable'], $data);

        $query = "UPDATE {$table} SET {$fields} WHERE {$whereClause}";

        (self::getInstance())->query($query, array_merge($data, $whereParams));
    }

    private function placeholder(string $field): string
    {
        return ":$field";
    }

    private function updatePlaceholder(string $field): string
    {
        return "{$field} = :{$field}";
    }

    private function nullable(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }
}
