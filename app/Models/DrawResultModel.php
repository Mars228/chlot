<?php
namespace App\Models;

use CodeIgniter\Model;

class DrawResultModel extends Model
{
    protected $table = 'draw_results';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'game_id','draw_system_id','draw_date','draw_time','numbers_a','numbers_b','source','raw_json'
    ];

    protected $validationRules = [
        'game_id'        => 'required|is_natural_no_zero',
        'draw_system_id' => 'required|integer',
        'draw_date'      => 'required|valid_date',
        'draw_time'      => 'required',
        'numbers_a'      => 'required',
    ];
}