<?php
return [
    'version' => '20260415_000_runtime_schema_replacement',
    'description' => 'Substitui schema runtime por migrations versionadas e garante colunas/tabelas legadas.',
    'up' => function (PDO $pdo, MigrationRunner $m): void {
        $statements = [
            "CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY, admin_id INT NOT NULL, email VARCHAR(190) NOT NULL, token_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_password_resets_email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS backup_history (id INT AUTO_INCREMENT PRIMARY KEY, file_name VARCHAR(190) NOT NULL, file_path VARCHAR(255) NOT NULL, file_size BIGINT NOT NULL DEFAULT 0, backup_type VARCHAR(40) NOT NULL DEFAULT 'manual', status VARCHAR(30) NOT NULL DEFAULT 'gerado', created_by_admin_id INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS report_history (id INT AUTO_INCREMENT PRIMARY KEY, report_type VARCHAR(40) NOT NULL DEFAULT 'executive_html', sort_by VARCHAR(40) NOT NULL DEFAULT 'access_code', sort_dir VARCHAR(4) NOT NULL DEFAULT 'asc', output_format VARCHAR(20) NOT NULL DEFAULT 'html', file_path VARCHAR(255) NULL, recipient_email VARCHAR(190) NULL, status VARCHAR(30) NOT NULL DEFAULT 'gerado', created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS task_definitions (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, slug VARCHAR(190) NULL, description TEXT NULL, min_age INT NULL, max_age INT NULL, sex_rule VARCHAR(10) NOT NULL DEFAULT 'any', capacity_per_slot INT NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS task_slots (id INT AUTO_INCREMENT PRIMARY KEY, task_id INT NOT NULL, slot_date DATE NOT NULL, shift_key VARCHAR(40) NOT NULL, shift_label VARCHAR(120) NOT NULL, shift_order INT NOT NULL DEFAULT 0, capacity_override INT NULL, notes TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_task_slot (task_id, slot_date, shift_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS task_assignments (id INT AUTO_INCREMENT PRIMARY KEY, task_slot_id INT NOT NULL, participant_id INT NOT NULL, assignment_mode VARCHAR(20) NOT NULL DEFAULT 'manual', assigned_by_admin_id INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_task_assignment (task_slot_id, participant_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS food_meals (id INT AUTO_INCREMENT PRIMARY KEY, retreat_day TINYINT NOT NULL, meal_type VARCHAR(30) NOT NULL, title VARCHAR(190) NOT NULL, meal_date DATE NULL, meal_time VARCHAR(20) NULL, estimated_people INT NOT NULL DEFAULT 0, responsible_name VARCHAR(190) NULL, status VARCHAR(30) NOT NULL DEFAULT 'planejado', notes TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_food_meals_day_type (retreat_day, meal_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS food_menu_items (id INT AUTO_INCREMENT PRIMARY KEY, meal_id INT NOT NULL, item_name VARCHAR(190) NOT NULL, quantity_estimate DECIMAL(10,2) NULL, unit VARCHAR(30) NULL, notes TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS food_pantry_items (id INT AUTO_INCREMENT PRIMARY KEY, item_name VARCHAR(190) NOT NULL, category VARCHAR(80) NULL, unit VARCHAR(30) NULL, quantity_current DECIMAL(10,2) NOT NULL DEFAULT 0, minimum_stock DECIMAL(10,2) NOT NULL DEFAULT 0, expiration_date DATE NULL, storage_place VARCHAR(120) NULL, notes TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS food_purchase_items (id INT AUTO_INCREMENT PRIMARY KEY, pantry_item_id INT NULL, item_name VARCHAR(190) NOT NULL, category VARCHAR(80) NULL, quantity_needed DECIMAL(10,2) NOT NULL DEFAULT 0, unit VARCHAR(30) NULL, priority_level VARCHAR(20) NOT NULL DEFAULT 'media', estimated_cost DECIMAL(10,2) NOT NULL DEFAULT 0, status VARCHAR(30) NOT NULL DEFAULT 'pendente', notes TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS food_menu_item_ingredients (id INT AUTO_INCREMENT PRIMARY KEY, menu_item_id INT NOT NULL, pantry_item_id INT NOT NULL, quantity_base DECIMAL(10,3) NOT NULL DEFAULT 0, unit VARCHAR(30) NULL, consumption_mode VARCHAR(20) NOT NULL DEFAULT 'fixed', notes TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS food_categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NULL, sort_order INT NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_food_categories_name (name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS food_stock_movements (id INT AUTO_INCREMENT PRIMARY KEY, pantry_item_id INT NOT NULL, meal_id INT NULL, movement_type VARCHAR(20) NOT NULL DEFAULT 'saida', quantity DECIMAL(10,3) NOT NULL DEFAULT 0, unit VARCHAR(30) NULL, notes TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
        foreach ($statements as $sql) {
            $pdo->exec($sql);
        }

        $columnStatements = [
            ['payments', 'receipt_file', "ALTER TABLE payments ADD COLUMN receipt_file VARCHAR(255) NULL AFTER payment_date"],
            ['participants', 'dietary_notes', "ALTER TABLE participants ADD COLUMN dietary_notes TEXT NULL AFTER presence_status"],
            ['food_pantry_items', 'category_id', "ALTER TABLE food_pantry_items ADD COLUMN category_id INT NULL AFTER category"],
            ['food_purchase_items', 'category_id', "ALTER TABLE food_purchase_items ADD COLUMN category_id INT NULL AFTER category"],
            ['food_meals', 'stock_applied_at', "ALTER TABLE food_meals ADD COLUMN stock_applied_at DATETIME NULL AFTER notes"],
            ['food_meals', 'executed_people', "ALTER TABLE food_meals ADD COLUMN executed_people INT NULL AFTER estimated_people"],
            ['food_meals', 'stock_reversed_at', "ALTER TABLE food_meals ADD COLUMN stock_reversed_at DATETIME NULL AFTER stock_applied_at"],
            ['food_menu_item_ingredients', 'actual_quantity_used', "ALTER TABLE food_menu_item_ingredients ADD COLUMN actual_quantity_used DECIMAL(10,3) NULL AFTER quantity_base"],
        ];
        foreach ($columnStatements as [$table, $column, $sql]) {
            if ($m->tableExists($pdo, $table) && !$m->columnExists($pdo, $table, $column)) {
                $pdo->exec($sql);
            }
        }

        if ($m->tableExists($pdo, 'task_definitions')) {
            if (!$m->indexExists($pdo, 'task_definitions', 'idx_task_definitions_active')) {
                $pdo->exec('ALTER TABLE task_definitions ADD INDEX idx_task_definitions_active (is_active)');
            }
            if (!$m->indexExists($pdo, 'task_definitions', 'idx_task_definitions_order')) {
                $pdo->exec('ALTER TABLE task_definitions ADD INDEX idx_task_definitions_order (sort_order, name)');
            }
        }
        if ($m->tableExists($pdo, 'task_slots')) {
            if (!$m->indexExists($pdo, 'task_slots', 'idx_task_slots_lookup')) {
                $pdo->exec('ALTER TABLE task_slots ADD INDEX idx_task_slots_lookup (slot_date, shift_key, shift_order)');
            }
            if (!$m->indexExists($pdo, 'task_slots', 'idx_task_slots_task')) {
                $pdo->exec('ALTER TABLE task_slots ADD INDEX idx_task_slots_task (task_id)');
            }
        }
        if ($m->tableExists($pdo, 'task_assignments')
            && $m->columnExists($pdo, 'task_assignments', 'task_slot_id')
            && !$m->indexExists($pdo, 'task_assignments', 'idx_task_assignments_slot')) {
                $pdo->exec('ALTER TABLE task_assignments ADD INDEX idx_task_assignments_slot (task_slot_id)');
            }
            if ($m->columnExists($pdo, 'task_assignments', 'participant_id') && !$m->indexExists($pdo, 'task_assignments', 'idx_task_assignments_participant')) {
                $pdo->exec('ALTER TABLE task_assignments ADD INDEX idx_task_assignments_participant (participant_id)');
            }
        }
    },
];
