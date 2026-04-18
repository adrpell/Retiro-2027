<?php
return [
    'version' => '20260415_001_seed_default_tasks',
    'description' => 'Garante tarefas padrão iniciais para o quadro de tarefas.',
    'up' => function (PDO $pdo, MigrationRunner $m): void {
        if (!$m->tableExists($pdo, 'task_definitions')) {
            return;
        }
        $count = (int)$pdo->query('SELECT COUNT(*) FROM task_definitions')->fetchColumn();
        if ($count > 0) {
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO task_definitions (name, slug, description, min_age, max_age, sex_rule, capacity_per_slot, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
        foreach ([
            ['Ajudar na cozinha', 'ajudar-na-cozinha', 'Apoio no preparo e organização da cozinha.', 14, null, 'any', 4, 10],
            ['Lavar panelas', 'lavar-panelas', 'Limpeza de panelas e utensílios após as refeições.', 16, null, 'any', 3, 20],
            ['Limpeza dos quartos', 'limpeza-dos-quartos', 'Organização e limpeza leve dos quartos.', 13, null, 'any', 4, 30],
        ] as $row) {
            $stmt->execute($row);
        }
    },
];
