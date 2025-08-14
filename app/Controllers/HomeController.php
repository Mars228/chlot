<?php
namespace App\Controllers;

use App\Controllers\BaseController;

class HomeController extends BaseController
{
    public function index()
    {
        helper('text');

        // 1) Gry (mapa id=>row)
        $games = [];
        foreach ((new \App\Models\GameModel())->orderBy('id','ASC')->findAll() as $g) {
            $games[(int)$g['id']] = $g;
        }

        // 2) Ostatnie losowania per gra
        $drM = new \App\Models\DrawResultModel();
        $latest = [];
        foreach ($games as $gid => $g) {
            $row = $drM->where('game_id', $gid)
                       ->orderBy('draw_system_id', 'DESC')
                       ->first();
            if ($row) $latest[$gid] = $row;
        }

        // 3) Podsumowania tygodniowe i miesięczne (na podstawie bet_results)
        $brM = new \App\Models\BetResultModel();

        // TYGODNIE: ostatnie 4 na grę
        $weeklyAll = $brM->db->query("
            SELECT
              br.game_id,
              YEARWEEK(dr.draw_date, 3) AS yrwk,
              MIN(dr.draw_date) AS date_from,
              MAX(dr.draw_date) AS date_to,
              SUM(COALESCE(br.win_amount,0)) AS win_sum,
              SUM(CASE WHEN br.is_winner=1 THEN 1 ELSE 0 END) AS winners,
              COUNT(*) AS tickets
            FROM bet_results br
            JOIN draw_results dr
              ON dr.game_id = br.game_id
             AND dr.draw_system_id = br.evaluation_draw_system_id
            GROUP BY br.game_id, YEARWEEK(dr.draw_date, 3)
            ORDER BY date_from DESC
            LIMIT 200
        ")->getResultArray();

        $weekly = []; // [game_id] => array of rows (max 4)
        foreach ($weeklyAll as $w) {
            $gid = (int)$w['game_id'];
            if (!isset($weekly[$gid])) $weekly[$gid] = [];
            if (count($weekly[$gid]) < 4) $weekly[$gid][] = $w;
        }

        // MIESIĄCE: ostatnie 3 na grę
        $monthlyAll = $brM->db->query("
            SELECT
              br.game_id,
              DATE_FORMAT(dr.draw_date, '%Y-%m') AS ym,
              MIN(dr.draw_date) AS date_from,
              MAX(dr.draw_date) AS date_to,
              SUM(COALESCE(br.win_amount,0)) AS win_sum,
              SUM(CASE WHEN br.is_winner=1 THEN 1 ELSE 0 END) AS winners,
              COUNT(*) AS tickets
            FROM bet_results br
            JOIN draw_results dr
              ON dr.game_id = br.game_id
             AND dr.draw_system_id = br.evaluation_draw_system_id
            GROUP BY br.game_id, ym
            ORDER BY ym DESC
            LIMIT 200
        ")->getResultArray();

        $monthly = []; // [game_id] => array of rows (max 3)
        foreach ($monthlyAll as $m) {
            $gid = (int)$m['game_id'];
            if (!isset($monthly[$gid])) $monthly[$gid] = [];
            if (count($monthly[$gid]) < 3) $monthly[$gid][] = $m;
        }

        // 4) Render
        $content = view('home/index', [
            'games'   => $games,
            'latest'  => $latest,
            'weekly'  => $weekly,
            'monthly' => $monthly,
        ]);

        return view('layouts/adminlte', [
            'title'   => 'Pulpit',
            'content' => $content,
        ]);
    }
}
