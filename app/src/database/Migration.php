<?php

namespace sunframework\database;

use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use PDO;
use sunframework\system\SunLogger;

// NOTE: in development
class Migration {
    private $pdo;
    private $migrationDir;
    private $logger;
    private $tableName;
    private const LOCK_FILE = 'db.lock';
    private const TABLE_NAME = '__migration';

    /**
     * Migration constructor.
     * @param PDO $pdo
     * @param string $migrationDir
     * @param string $tableName
     * @throws Exception
     */
    public function __construct(PDO $pdo, string $migrationDir, string $tableName = self::TABLE_NAME) {
        $this->logger = new SunLogger('migration');
        $this->tableName = $tableName;
        $this->pdo = $pdo;
        $this->migrationDir = $migrationDir;

        if (!file_exists($this->migrationDir)) {
            throw new InvalidArgumentException("Directory '$migrationDir' doesn't exists.");
        }

        $this->logger->info("initializing the database.");
        if (!$this->tableExists()) {
            $this->init();
        }
    }

    public function migrate() {
        if (file_exists(self::LOCK_FILE)) {
            return;
        } else {
            file_put_contents("lock.txt", "locked");
        }

        try {
            $this->logger->info("starting the migration.");

            $this->logger->info("fetching migration scripts.");
            $fi = new FilesystemIterator(__DIR__, FilesystemIterator::SKIP_DOTS);
            $localFiles = iterator_to_array($fi);
            // TODO: need to split the file with version number and name + real name.
            usort($localFiles, function() {
                return 0; // TODO: make a compare function
            });

            $stm = $this->pdo->prepare("SELECT * FROM TABLE $this->tableName");
            $stm->execute();
            $dbFiles = $stm->fetchAll();

            foreach ($dbFiles as $dbFile) {
                if (isset($localFiles[$dbFile])) {
                    unset($localFiles[$dbFile]); // TODO: not working for sure... debug.
                }
            }

            foreach ($localFiles as $file) {
                $this->logger->info("updating to script '$file'.");
                $sql = file_get_contents($file);
                $this->pdo->exec($sql);
            }

            $this->logger->info("migration completed.");
        }
        catch (Exception $ex) {
            $this->logger->error("migration stopped.", ['exception' => $ex]);
        }
    }

    private function tableExists() {
        // Note: throw an exception if cannot connect.
        return $this->pdo->query("SELECT 1 FROM $this->tableName LIMIT 1;") !== false;
    }

    private function init() {
        $this->pdo->query("CREATE DATABASE IF NOT EXISTS $this->tableName " .
            "(filename VARCHAR(255), version VARCHAR(255), name VARCHAR(255), created DATE, PRIMARY KEY (filename))");
        $this->logger->info("migration table created.");
    }
}