<?php
namespace App\Models;

use CodeIgniter\Model;

class GameVariantModel extends Model
{
    protected $table = 'game_variants';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'game_id','name','picks_a_min','picks_a_max','picks_b_min','picks_b_max','price','is_default'
    ];
    protected $useTimestamps = true;

    protected $validationRules = [
        'name' => 'required|min_length[1]|max_length[100]',
        'picks_a_min' => 'permit_empty|integer',
        'picks_a_max' => 'permit_empty|integer',
        'picks_b_min' => 'permit_empty|integer',
        'picks_b_max' => 'permit_empty|integer',
        'price' => 'permit_empty|decimal'
    ];
}