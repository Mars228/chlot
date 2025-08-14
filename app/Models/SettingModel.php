<?php
namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['key','value'];

    public function get(string $key, $default = null)
    {
        $row = $this->where('key',$key)->first();
        return $row['value'] ?? $default;
    }

    public function setKV(string $key, $value): bool
    {
        // upsert po unikalnym 'key'
        return (bool) $this->db->query(
            'INSERT INTO settings (`key`,`value`) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)',
            [$key, $value]
        );
    }
}
