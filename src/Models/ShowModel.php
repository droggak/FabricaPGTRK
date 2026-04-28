<?php
// src/Models/ShowModel.php

namespace App\Models;

use App\Core\DB;

class ShowModel
{
    private DB $db;

    public const COLORS = ['accent','purple','green','amber','red','teal'];

    public function __construct()
    {
        $this->db = DB::getInstance();
    }

    public function getAll(): array
    {
        return $this->db->rows('
            SELECT sh.*, COUNT(s.id) AS story_count
            FROM shows sh
            LEFT JOIN stories s ON s.show_id = sh.id
            GROUP BY sh.id
            ORDER BY sh.name
        ');
    }

    public function getById(int $id): ?array
    {
        return $this->db->row('SELECT * FROM shows WHERE id = ?', [$id]);
    }

    public function create(array $data, int $userId): int
    {
        return $this->db->insert('shows', [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'color'       => in_array($data['color'], self::COLORS, true) ? $data['color'] : 'accent',
            'air_time'    => $data['air_time'] ?? null,
            'created_by'  => $userId,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $this->db->updateById('shows', $id, [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'color'       => in_array($data['color'], self::COLORS, true) ? $data['color'] : 'accent',
            'air_time'    => $data['air_time'] ?? null,
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->query('UPDATE stories SET show_id = NULL WHERE show_id = ?', [$id]);
        $this->db->deleteById('shows', $id);
    }
}
