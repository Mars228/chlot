<?php
namespace App\Models;

use CodeIgniter\Model;

class PrizeTierModel extends Model
{
    protected $table = 'prize_tiers';
    protected $primaryKey = 'id';
    protected $allowedFields = ['game_variant_id','matched_a','matched_b','payout_type','value','description'];
    protected $useTimestamps = true;

    protected $validationRules = [
        'payout_type' => 'required|in_list[fixed,coefficient]',
        'value' => 'required|decimal'
    ];
}