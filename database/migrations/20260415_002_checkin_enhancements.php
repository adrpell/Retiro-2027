<?php
return [
    'version' => '20260415_002_checkin_enhancements',
    'description' => 'Expande o módulo de check-in com auditoria e metadados.',
    'up' => function (PDO $pdo, MigrationRunner $m): void {
        if ($m->tableExists($pdo, 'participants')) {
            if (!$m->columnExists($pdo, 'participants', 'checkin_status')) {
                $pdo->exec("ALTER TABLE participants ADD COLUMN checkin_status VARCHAR(20) NOT NULL DEFAULT 'nao'");
            }
            if (!$m->columnExists($pdo, 'participants', 'checked_in_at')) {
                $pdo->exec("ALTER TABLE participants ADD COLUMN checked_in_at DATETIME NULL AFTER checkin_status");
            }
            if (!$m->columnExists($pdo, 'participants', 'checked_in_by_admin_id')) {
                $pdo->exec("ALTER TABLE participants ADD COLUMN checked_in_by_admin_id INT NULL AFTER checked_in_at");
            }
        }

        if (!$m->tableExists($pdo, 'checkin_history')) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS checkin_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                participant_id INT NOT NULL,
                group_id INT NOT NULL,
                previous_status VARCHAR(20) NOT NULL DEFAULT 'nao',
                new_status VARCHAR(20) NOT NULL DEFAULT 'nao',
                changed_by_admin_id INT NULL,
                change_source VARCHAR(40) NOT NULL DEFAULT 'manual',
                change_context VARCHAR(60) NULL,
                notes VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_checkin_history_participant (participant_id),
                INDEX idx_checkin_history_group (group_id),
                INDEX idx_checkin_history_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if ($m->tableExists($pdo, 'participants') && $m->columnExists($pdo, 'participants', 'checkin_status') && $m->columnExists($pdo, 'participants', 'checked_in_at')) {
            $pdo->exec("UPDATE participants SET checked_in_at = NULL, checked_in_by_admin_id = NULL WHERE COALESCE(checkin_status, 'nao') <> 'sim'");
        }
    },
];
