<?php
namespace App\Models;

use CodeIgniter\Model;

class GameModel extends Model
{
    protected $table      = 'games';
    protected $primaryKey = 'id';
    protected $allowedFields = [
    'name','slug','description','default_price','logo_path',
    'range_a_min','range_a_max','picks_a_min','picks_a_max',
    'range_b_min','range_b_max','picks_b_min','picks_b_max',
    'payout_schema_json','draw_no_transform_json','is_active'
];
    protected $useTimestamps = true;

    protected $validationRules = [
        'id' => 'permit_empty|is_natural_no_zero',
        'name' => 'required|min_length[2]|max_length[100]',
        'slug' => 'required|alpha_dash|min_length[2]|max_length[60]|is_unique[games.slug,id,{id}]',
        'default_price' => 'permit_empty|decimal'
    ];
}