<?php
namespace App\Controllers;

use App\Models\StrategyModel;
use App\Models\GameModel;
use App\Models\StatSchemaModel;

class StrategiesController extends BaseController
{
    public function index()
{
    $stype   = $this->request->getGet('stype') ?: 'SIMPLE';
    $gameId  = (int)($this->request->getGet('game_id') ?? 0);
    $schemaId= (int)($this->request->getGet('schema_id') ?? 0);
    $perPage = max(10, (int)($this->request->getGet('per_page') ?? 50));
    $page    = max(1,  (int)($this->request->getGet('page') ?? 1));

    $m = new \App\Models\StrategyModel();

    // budujemy query z joinem do stat_results (żeby mieć okno/zakres)
    $builder = $m->builder()
        ->select('strategies.*, stat_results.window_draws, stat_results.window_draws_a, stat_results.window_draws_b, stat_results.met_at_draw_system_id, stat_results.hot_a, stat_results.hot_b')
        ->join('stat_results', 'stat_results.id = strategies.stat_result_id', 'left')
        ->where('strategies.stype', $stype);

    if ($gameId)   $builder->where('strategies.game_id', $gameId);
    if ($schemaId) $builder->where('strategies.schema_id', $schemaId);

    $builder->orderBy('strategies.id','DESC');

    // Paginacja
    $total = (clone $builder)->countAllResults(false);
    $offset = ($page - 1) * $perPage;
    $list = $builder->get($perPage, $offset)->getResultArray();

    // selecty do filtrów
    $games = [];
    foreach ((new \App\Models\GameModel())->findAll() as $g) $games[$g['id']] = $g;

    $schemas = [];
    $sQuery = (new \App\Models\StatSchemaModel())->orderBy('id','ASC');
    if ($gameId) $sQuery->where('game_id', $gameId);
    foreach ($sQuery->findAll() as $s) $schemas[$s['id']] = $s;


// Jeśli schema_id nie należy do wybranej gry – wyczyść
if ($schemaId && !isset($schemas[$schemaId])) {
    $schemaId = 0;
}

// (opcjonalnie) ustaw domyślnie pierwszy schemat tej gry
if (!$schemaId && !empty($schemas)) {
    $first = array_key_first($schemas);
    if ($first) $schemaId = (int)$first;
}

    // pager prościutki
    $pager = [
        'page'    => $page,
        'perPage' => $perPage,
        'total'   => $total,
        'pages'   => (int)ceil($total / $perPage),
        'query'   => http_build_query(['stype'=>$stype,'game_id'=>$gameId,'schema_id'=>$schemaId,'per_page'=>$perPage]),
    ];

    $content = view('strategies/index', compact('list','games','schemas','total','pager','stype','gameId','schemaId'));
    return view('layouts/adminlte', ['title'=>'Strategie','content'=>$content]);
}


public function schemasByGame()
{
    $gid = (int)($this->request->getGet('game_id') ?? 0);
    $m = new \App\Models\StatSchemaModel();
    $rows = $m->where('game_id', $gid)->orderBy('id','DESC')->findAll(200);

    $out = array_map(static function(array $r){
        return [
            'id'     => (int)$r['id'],
            'scheme' => $r['scheme'],
            'name'   => $r['name'] ?? '',
            'x_a'    => $r['x_a'], 'y_a'=>$r['y_a'],
            'x_b'    => $r['x_b'], 'y_b'=>$r['y_b'],
            'k_a'    => $r['k_a'], 'k_b'=>$r['k_b'],
        ];
    }, $rows);

    return $this->response->setJSON($out);
}



}
