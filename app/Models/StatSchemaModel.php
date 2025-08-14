<?php
namespace App\Models;

use CodeIgniter\Model;

class StatSchemaModel extends Model
{
    protected $table         = 'stat_schemas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'game_id','scheme','name','params_json','status',
        'first_met_from_draw','current_from_draw','processed_since_first'
    ];
}
