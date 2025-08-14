<?php
namespace App\Models;

use CodeIgniter\Model;

class BetResultModel extends Model
{
    protected $table         = 'bet_results';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'ticket_id','batch_id','game_id','strategy_id','is_baseline',
        'next_draw_system_id','evaluation_draw_system_id',
        'hits_a','hits_b','k_a','k_b',
        'win_amount','win_factor','win_currency','prize_label','is_winner',
        'created_at',
    ];
}
