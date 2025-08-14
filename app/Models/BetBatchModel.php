<?php
namespace App\Models;

use CodeIgniter\Model;

class BetBatchModel extends Model
{
    protected $table         = 'bet_batches';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'game_id','stype','schema_id',
        'strategy_id_from','strategy_id_to','last_n',
        'per_strategy','include_random_baseline',
        'status','total_strategies','total_tickets',
        'created_at','started_at','finished_at',
    ];
}
