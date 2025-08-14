<?php
namespace App\Models;
use CodeIgniter\Model;

class StatResultModel extends Model
{
    protected $table = 'stat_results';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
    'schema_id',
    'game_id','scheme','from_draw_system_id','met_at_draw_system_id',
    'window_draws','window_draws_a','window_draws_b',
    'criteria_a','criteria_b',
    'numbers_in_a','numbers_in_b',
    'counts_a','counts_b',
    'hot_a','cold_a','hot_b','cold_b',
    'finished_at'
];
}
