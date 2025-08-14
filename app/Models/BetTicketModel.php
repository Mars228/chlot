<?php
namespace App\Models;

use CodeIgniter\Model;

class BetTicketModel extends Model
{
    protected $table         = 'bet_tickets';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'batch_id','game_id','strategy_id',
        'k_a','k_b',
        'hot_count_a','cold_count_a','hot_count_b','cold_count_b',
        'is_baseline','numbers_a','numbers_b',
        'next_draw_system_id','created_at',
    ];
}
