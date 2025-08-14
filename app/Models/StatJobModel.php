<?php
namespace App\Models;
use CodeIgniter\Model;

class StatJobModel extends Model
{
    protected $table = 'stat_jobs';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'game_id','scheme','from_draw_system_id','from_draw_date','from_draw_time',
        'params_json','progress','total','status','message','result_id'
    ];
}
