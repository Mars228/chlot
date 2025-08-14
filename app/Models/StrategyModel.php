<?php
namespace App\Models;

use CodeIgniter\Model;

class StrategyModel extends Model
{
    protected $DBGroup      = 'default';           // ← DODAJ (na wszelki wypadek)
    protected $table        = 'strategies';
    protected $primaryKey   = 'id';
    protected $returnType   = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'game_id','schema_id','stat_result_id','from_draw_system_id','next_draw_system_id', 'stype',
        'hot_count_a','cold_count_a','hot_even_a','hot_odd_a','cold_even_a','cold_odd_a',
        'hits_hot_a','hits_cold_a',
        'hot_count_b','cold_count_b','hot_even_b','hot_odd_b','cold_even_b','cold_odd_b',
        'hits_hot_b','hits_cold_b','created_at',
        'recommend_a_json','recommend_b_json'     // ← UPEWNIJ SIĘ, ŻE TO JEST
    ];
}
