<?php
namespace App\Models;

use CodeIgniter\Model;

class GamePickGroupModel extends Model
{
    protected $table = 'game_pick_groups';
    protected $primaryKey = 'id';
    protected $allowedFields = ['game_id','code','range_min','range_max'];
    protected $useTimestamps = true;

    protected $validationRules = [
        'code' => 'required|in_list[A,B]',
        'range_min' => 'required|integer',
        'range_max' => 'required|integer|greater_than_equal_to[range_min]'
    ];
}