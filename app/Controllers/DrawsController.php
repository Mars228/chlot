<?php
namespace App\Controllers;

use App\Models\GameModel;
use App\Models\DrawResultModel;
use App\Models\SettingModel;

class DrawsController extends BaseController
{
    public function index()
    {
        helper('format'); // pretty_numbers
        $games = new GameModel();
        $gameSlug = $this->request->getGet('game') ?: 'multi-multi';
        $month    = $this->request->getGet('month'); // YYYY-MM

        $game = $games->where('slug', $gameSlug)->first();
        if (! $game) {
            $game = $games->orderBy('id','ASC')->first();
            $gameSlug = $game['slug'] ?? 'multi-multi';
        }

        // Miesiąc
        $tz = new \DateTimeZone('Europe/Warsaw');
        $today = new \DateTime('now', $tz);
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$y,$m] = explode('-', $month);
            $start = new \DateTime(sprintf('%04d-%02d-01 00:00:00', $y, $m), $tz);
        } else {
            $start = new \DateTime($today->format('Y-m-01 00:00:00'), $tz);
        }
        $end = (clone $start)->modify('last day of this month')->setTime(23,59,59);

        $model = new DrawResultModel();
        $rows = $model->where('game_id', $game['id'])
            ->where('draw_date >=', $start->format('Y-m-d'))
            ->where('draw_date <=', $end->format('Y-m-d'))
            ->orderBy('draw_date','DESC')->orderBy('draw_time','DESC')
            ->findAll();

        // ZAKRESY (min/max numer i data) — dla całej gry
        $db = \Config\Database::connect();
        $range = $db->query("
            SELECT 
              MIN(draw_system_id) AS min_no,
              MAX(draw_system_id) AS max_no,
              MIN(CONCAT(draw_date,' ',draw_time)) AS min_dt,
              MAX(CONCAT(draw_date,' ',draw_time)) AS max_dt
            FROM draw_results
            WHERE game_id = ?
        ", [$game['id']])->getFirstRow('array') ?: [];

        $prev = (clone $start)->modify('-1 month')->format('Y-m');
        $next = (clone $start)->modify('+1 month')->format('Y-m');

        $content = view('draws/index', [
            'rows' => $rows,
            'game' => $game,
            'gameSlug' => $gameSlug,
            'games' => $games->orderBy('name','ASC')->findAll(),
            'month' => $start->format('Y-m'),
            'prev' => $prev,
            'next' => $next,
            'range' => $range,
        ]);

        return view('layouts/adminlte', ['title' => 'Losowania', 'content' => $content]);
    }

    public function importForm()
    {
        $games = (new GameModel())->orderBy('name','ASC')->findAll();
        $content = view('draws/import', compact('games'));
        return view('layouts/adminlte', ['title' => 'Import losowań (CSV)', 'content' => $content]);
    }

    public function import()
    {
        helper(['upload','format']);
        $gameId = (int) $this->request->getPost('game_id');
        $file   = $this->request->getFile('csv');

        if (! $gameId || ! $file) {
            return redirect()->back()->with('errors', ['Wybierz grę i plik CSV.']);
        }

        $webPath = handle_public_upload($file, 'csv'); // /uploads/csv/...
        if (! $webPath) {
            return redirect()->back()->with('errors', ['Nie udało się zapisać pliku.']);
        }
        $fullPath = rtrim(FCPATH, '/\\') . '/' . $webPath;
        if (! is_file($fullPath)) {
            return redirect()->back()->with('errors', ['Brak pliku po uploadzie.']);
        }

        [$inserted, $updated] = $this->importCsvFile($fullPath, $gameId);
        return redirect()->to('/losowania?game=' . $this->gameSlugById($gameId))
            ->with('success', "Zaimportowano: $inserted, zaktualizowano: $updated");
    }

    public function fetchForm()
    {
        $games = (new GameModel())->whereIn('slug', ['lotto','eurojackpot','multi-multi'])->orderBy('name','ASC')->findAll();
        // prefill z GET
        $prefillSlug = $this->request->getGet('game_slug');
        $prefillDt   = $this->request->getGet('draw_datetime');

        $content = view('draws/fetch', compact('games', 'prefillSlug', 'prefillDt'));
        return view('layouts/adminlte', ['title' => 'Pobierz z Lotto OpenAPI', 'content' => $content]);
    }

    /** AJAX: ostatni wynik (tester) -> JSON { ok, slug, drawSystemId, drawDateLocal } */
public function testLatest()
{
    $slug = trim((string)$this->request->getGet('game_slug'));
    if ($slug === '') {
        return $this->response->setJSON(['ok' => false, 'msg' => 'Brak parametru game_slug.']);
    }

    // znajdź grę po slug
    $game = (new \App\Models\GameModel())
        ->where('slug', $slug)
        ->first();

    if (!$game) {
        return $this->response->setJSON(['ok' => false, 'msg' => 'Nieznana gra: '.$slug]);
    }

    // ostatnie zapisane losowanie w DB dla tej gry
    $dr = (new \App\Models\DrawResultModel())
        ->where('game_id', (int)$game['id'])
        ->orderBy('draw_system_id', 'DESC')
        ->first();

    if (!$dr) {
        return $this->response->setJSON(['ok' => false, 'msg' => 'Brak zapisanych losowań dla gry '.$game['name']]);
    }

    // zbuduj lokalny „YYYY-MM-DDTHH:mm” z pól draw_date + draw_time (zakładamy, że to czas PL)
    $date = (string)($dr['draw_date'] ?? '');
    $time = substr((string)($dr['draw_time'] ?? '00:00:00'), 0, 5); // HH:mm
    $drawDateLocal = $date && $time ? ($date.'T'.$time) : ($date ?: '');

    return $this->response->setJSON([
        'ok'            => true,
        'game'          => $game['name'],
        'slug'          => $slug,
        'drawSystemId'  => (int)$dr['draw_system_id'],
        'drawDateLocal' => $drawDateLocal,   // np. "2025-05-29T20:00"
    ]);
}





    public function fetchOne()
    {
        $games = new GameModel();
        $slug = $this->request->getPost('game_slug');
        $dtLocal = $this->request->getPost('draw_datetime'); // HTML datetime-local (PL)

        $game = $games->where('slug', $slug)->first();
        if (! $game) {
            return redirect()->back()->with('errors', ['Nieprawidłowa gra.']);
        }
        if (! $dtLocal) {
            return redirect()->back()->with('errors', ['Podaj datę i godzinę.']);
        }
        $gameId = (int)$game['id'];

        // Sekret API
        $secret = (new SettingModel())->get('lotto_api_secret', null);
        if (! $secret) {
            return redirect()->back()->with('errors', ['Brak klucza API w ustawieniach.']);
        }

        // PL -> UTC Z (oraz trzymajmy ORYGINALNY PL do zapisu, żeby MM miało 14:00 jak w UI)
        $tzPl = new \DateTimeZone('Europe/Warsaw');
        $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $dtLocal, $tzPl);
        if (! $dt) {
            return redirect()->back()->with('errors', ['Błędny format daty.']);
        }
        $isoUtc = (clone $dt)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i\Z');

        $map = ['lotto'=>'Lotto','eurojackpot'=>'EuroJackpot','multi-multi'=>'MultiMulti'];
        $gameType = $map[$slug] ?? 'Lotto';

        $client = \Config\Services::curlrequest();
        $url = 'https://developers.lotto.pl/api/open/v1/lotteries/draw-results/by-date-per-game';

        $doRequest = function(array $query) use ($client, $url, $secret) {
            return $client->get($url, [
                'headers' => ['accept'=>'application/json','secret'=>$secret,'Secret'=>$secret],
                'query' => $query,
                'http_errors' => false,
                'timeout' => 10,
            ]);
        };

        // 1) z hour/minute
        $q1 = [
            'gameType'=>$gameType,
            'drawDate'=>$isoUtc,
            'hour'=>(int)$dt->format('H'),
            'minute'=>(int)$dt->format('i'),
            'sort'=>'drawSystemId','order'=>'ASC','index'=>1,'size'=>10
        ];
        $resp = $doRequest($q1);
        $status = $resp->getStatusCode();
        $body   = (string)$resp->getBody();
        $json   = json_decode($body, true);
        $items  = $this->pickItemsFromApiResponse($json);

        // 2) fallback bez hour/minute
        if ($status===200 && empty($items)) {
            $q2 = $q1; unset($q2['hour'],$q2['minute']);
            $resp = $doRequest($q2);
            $status = $resp->getStatusCode();
            $body   = (string)$resp->getBody();
            $json   = json_decode($body, true);
            $items  = $this->pickItemsFromApiResponse($json);
        }

        if ($status !== 200) {
            return redirect()->back()->with('errors', ["Błąd API ($status): $body"]);
        }
        if (empty($items)) {
            return redirect()->back()->with('errors', ['Brak wyników dla podanej daty. Użyj „Sprawdź ostatnie” i wstaw proponowaną datę.']);
        }

        $inserted = 0; $updated = 0; $skipped = 0;
        foreach ($items as $it) {
            if (!is_array($it)) { $skipped++; continue; }
            $data = $this->parseApiItemToDraw($it, $gameId);

// === MAPOWANIE NUMERU: zewnętrzny -> lokalny ===
helper('drawno');
if (isset($data['draw_system_id'])) {
    $external = (int)$data['draw_system_id'];
    $data['external_draw_no'] = $external;
    $data['draw_system_id']   = normalize_draw_no($game, $external); // lokalny = external + offset
}

            if (!$data) { $skipped++; continue; }

            // >>>> KLUCZOWA ZMIANA GODZINY: zapisuj DATĘ/CZAS z formularza PL, nie z API
            $data['draw_date'] = $dt->format('Y-m-d');
            $data['draw_time'] = $dt->format('H:i:00');

            [$ok, $isUpdate] = $this->upsertDraw($data);
            if ($ok) { $isUpdate ? $updated++ : $inserted++; }
            else { $skipped++; }
        }

        return redirect()->to('/losowania?game=' . $slug . '&month=' . $dt->format('Y-m'))
            ->with('success', "Zapisano z API – dodane: {$inserted}, zaktualizowane: {$updated}" . ($skipped ? ", pominięte: {$skipped}" : ''));
    }

    // ===== Pomocnicze =====

    private function importCsvFile(string $path, int $gameId): array
    {
        $fh = fopen($path, 'r');
        if (! $fh) { return [0,0]; }

        $inserted = 0; $updated = 0;
        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            if (count($row) < 4) continue;

            $drawId  = preg_replace('/^\xEF\xBB\xBF/', '', trim($row[0]));
            $dateStr = trim($row[1]); // YYYY-MM-DD
            $timeStr = trim($row[2]); // HH:MM[:SS]
            $a       = trim($row[3]);
            $b       = isset($row[4]) ? trim($row[4]) : null;

            if (preg_match('/^\d{2}:\d{2}$/', $timeStr)) $timeStr .= ':00';

            $data = [
                'game_id'        => $gameId,
                'draw_system_id' => (int) $drawId,
                'draw_date'      => $dateStr,
                'draw_time'      => $timeStr,
                'numbers_a'      => $this->normalizeCsvNumbers($a),
                'numbers_b'      => $b ? $this->normalizeCsvNumbers($b) : null,
                'source'         => 'csv',
                'raw_json'       => null,
            ];

            [$ok, $isUpdate] = $this->upsertDraw($data);
            if ($ok) { $isUpdate ? $updated++ : $inserted++; }
        }
        fclose($fh);
        return [$inserted, $updated];
    }

    private function upsertDraw(array $data): array
{
    $model = new DrawResultModel();

    // 1) jeśli mamy oficjalny numer – próbuj po (game_id, draw_system_id)
    if (!empty($data['draw_system_id'])) {
        $existing = $model->where('game_id', $data['game_id'])
                          ->where('draw_system_id', (int)$data['draw_system_id'])
                          ->first();

        if ($existing) {
            $data['id'] = $existing['id'];
            $ok = $model->save($data);
            return [$ok, true];
        }

        // 1a) brak po numerze — spróbuj dopasować „tymczasowy” rekord po (gra,data,czas)
        $tmp = $model->where('game_id', $data['game_id'])
                     ->where('draw_date', $data['draw_date'])
                     ->where('draw_time', $data['draw_time'])
                     ->first();
        if ($tmp) {
            // uzupełnij numer, zaktualizuj liczby
            $data['id'] = $tmp['id'];
            $ok = $model->save($data);
            return [$ok, true];
        }

        // 1b) w ogóle nie ma – wstaw od zera
        $ok = (bool) $model->insert($data);
        return [$ok, false];
    }

    // 2) brak draw_system_id: pracujemy na kluczu (gra,data,czas)
    $existing = $model->where('game_id', $data['game_id'])
                      ->where('draw_date', $data['draw_date'])
                      ->where('draw_time', $data['draw_time'])
                      ->first();

    if ($existing) {
        $data['id'] = $existing['id'];
        $ok = $model->save($data);
        return [$ok, true];
    }

    $ok = (bool) $model->insert($data);
    return [$ok, false];
}

    private function gameSlugById(int $id): string
    {
        $g = (new GameModel())->select('slug')->find($id);
        return $g['slug'] ?? 'multi-multi';
    }

    private function normalizeCsvNumbers(string $s): string
    {
        $s = trim($s);
        $s = str_replace([';', '|', "\t"], ',', $s);
        $s = preg_replace('/\s+/', ',', $s);
        $s = preg_replace('/,+/', ',', $s);
        $s = trim($s, ',');
        return $s;
    }

    private function pickItemsFromApiResponse($payload): array
    {
        if (!is_array($payload) || empty($payload)) return [];
        if (isset($payload['content']) && is_array($payload['content'])) return $payload['content'];
        $isList = array_keys($payload) === range(0, count($payload)-1);
        if ($isList) return $payload;
        foreach (['items','data','results'] as $k) {
            if (isset($payload[$k]) && is_array($payload[$k])) return $payload[$k];
        }
        if (isset($payload['drawSystemId']) || isset($payload['resultsJson']) || isset($payload['results'])) return [$payload];
        return [];
    }

    private function parseApiItemToDraw(array $it, int $gameId): ?array
    {
        $drawSystemId = $it['drawSystemId'] ?? null;
        $drawDateUtc  = $it['drawDate'] ?? null;
        if ((!$drawSystemId || !$drawDateUtc) && isset($it['results'][0])) {
            $r = $it['results'][0];
            $drawSystemId = $drawSystemId ?? ($r['drawSystemId'] ?? null);
            $drawDateUtc  = $drawDateUtc  ?? ($r['drawDate'] ?? null);
        }
        if (!$drawSystemId || !$drawDateUtc) return null;

        // Ustal typ/slug gry (żeby rozróżnić reguły A/B)
$gameRow  = (new \App\Models\GameModel())->find($gameId);
$gameSlug = $gameRow['slug'] ?? '';

// Jeżeli prosimy o Lotto, a przychodzi LottoPlus (na wszelki wypadek) – ignoruj
$itType = strtolower($it['gameType'] ?? '');
if ($gameSlug === 'lotto' && str_contains($itType, 'plus')) {
    return null;
}


// Weź surowe wyniki – wybierz element z results[] dopasowany do gry (nie zawsze [0] jest właściwy)
$r = null;
if (!empty($it['results']) && is_array($it['results'])) {
    foreach ($it['results'] as $ri) {
        $riType = strtolower($ri['gameType'] ?? '');
        if ($gameSlug === 'lotto'       && $riType === 'lotto')       { $r = $ri; break; }
        if ($gameSlug === 'eurojackpot' && $riType === 'eurojackpot') { $r = $ri; break; }
        if ($gameSlug === 'multi-multi' && ($riType === 'multimulti' || $riType === 'multi-multi')) { $r = $ri; break; }
    }
}
// fallback: jeśli nie znaleziono dopasowania, użyj pierwszego (rzadko potrzebne)
if ($r === null) $r = $it['results'][0] ?? null;

$rawA = $r['resultsJson']    ?? $it['resultsJson']    ?? [];
$rawB = $r['specialResults'] ?? $it['specialResults'] ?? [];


// Ujednolicenie i zrzucenie wiodących zer
$norm = static function($arr) {
    if (!is_array($arr)) return null;
    $arr = array_map('intval', $arr);      // „01” → 1
    if (empty($arr)) return null;
    return implode(',', $arr);
};

$numbersA = null;
$numbersB = null;

// Logika per gra:
switch ($gameSlug) {
    case 'lotto':
        // Tylko podstawowe kule (resultsJson). „Plus” ignorujemy.
        $numbersA = $norm($rawA);
        $numbersB = null;
        break;

    case 'eurojackpot':
        // A = 5 z 50 (resultsJson), B = 2 z 12 (specialResults)
        $numbersA = $norm($rawA);
        $numbersB = $norm($rawB);
        break;

    case 'multi-multi':
        // A = 20 z 80, B nie dotyczy
        $numbersA = $norm($rawA);
        $numbersB = null;
        break;

    default:
        // Domyślnie: tylko A z resultsJson
        $numbersA = $norm($rawA);
        $numbersB = null;
        break;
}

        try { new \DateTime($drawDateUtc, new \DateTimeZone('UTC')); } catch (\Throwable $e) { return null; }

        return [
            'game_id'        => $gameId,
            'draw_system_id' => (int)$drawSystemId,
            // draw_date/time ustawiamy wyżej z $dt (PL)
            'numbers_a'      => $numbersA,
            'numbers_b'      => $numbersB,
            'source'         => 'api',
            'raw_json'       => json_encode($it, JSON_UNESCAPED_UNICODE),
        ];
    }
    
public function syncMmIds()
{
    $games = new GameModel();
    $mm = $games->where('slug','multi-multi')->first();
    if (!$mm) {
        return redirect()->back()->with('errors',['Brak gry Multi Multi w bazie.']);
    }

    $secret = (new \App\Models\SettingModel())->get('lotto_api_secret', null);
    if (!$secret) return redirect()->back()->with('errors',['Brak klucza API w USTAWIENIACH.']);

    $limitDays = max(1, (int)($this->request->getPost('days') ?? 14)); // domyślnie 14 dni wstecz
    $since = (new \DateTime("-{$limitDays} days", new \DateTimeZone('Europe/Warsaw')))->format('Y-m-d');

    $model = new DrawResultModel();
    $rows = $model->where('game_id', $mm['id'])
                  ->where('draw_system_id', null)
                  ->where('draw_date >=', $since)
                  ->orderBy('draw_date','DESC')->orderBy('draw_time','DESC')
                  ->findAll(500); // twardy limit na jedno kliknięcie

    if (!$rows) {
        return redirect()->back()->with('success','Brak rekordów do uzupełnienia (ostatnie '.$limitDays.' dni).');
    }

    $client = \Config\Services::curlrequest();
    $url = 'https://developers.lotto.pl/api/open/v1/lotteries/draw-results/by-date-per-game';
    $updated = 0; $skipped = 0;

    foreach ($rows as $r) {
        // buduj datę lokalną -> UTC
        $tzPl = new \DateTimeZone('Europe/Warsaw');
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $r['draw_date'].' '.$r['draw_time'], $tzPl);
        if (!$dt) { $skipped++; continue; }
        $isoUtc = (clone $dt)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i\Z');

        $q = [
            'gameType' => 'MultiMulti',
            'drawDate' => $isoUtc,
            'hour'     => (int)$dt->format('H'),
            'minute'   => (int)$dt->format('i'),
            'sort'     => 'drawSystemId','order'=>'ASC','index'=>1,'size'=>10
        ];

        $resp = $client->get($url, [
            'headers' => ['accept'=>'application/json','secret'=>$secret,'Secret'=>$secret],
            'query'   => $q, 'http_errors'=>false, 'timeout'=>10
        ]);
        $status = $resp->getStatusCode();
        if ($status !== 200) { $skipped++; continue; }

        $json = json_decode((string)$resp->getBody(), true);
        $items = $this->pickItemsFromApiResponse($json);
        if (!$items) { $skipped++; continue; }

        // bierz pierwszy item i spróbuj wyciągnąć drawSystemId
        $first = is_array($items[0] ?? null) ? $items[0] : null;
        if (!$first) { $skipped++; continue; }

        $data = $this->parseApiItemToDraw($first, (int)$mm['id']);
        if (empty($data) || empty($data['draw_system_id'])) { $skipped++; continue; }

        // UAKTUALNIJ ISTNIEJĄCY rekord po (gra,data,czas)
        $existing = $model->where('game_id', $mm['id'])
                          ->where('draw_date', $r['draw_date'])
                          ->where('draw_time', $r['draw_time'])
                          ->first();
        if ($existing) {
            $save = [
                'id'              => $existing['id'],
                'draw_system_id'  => (int)$data['draw_system_id'],
                'raw_json'        => $data['raw_json'] ?? $existing['raw_json'],
                'source'          => 'api'
            ];
            if ($model->save($save)) $updated++; else $skipped++;
        } else {
            // awaryjnie: wstaw jako nowy (nie powinno się zdarzać)
            $data['draw_date'] = $r['draw_date'];
            $data['draw_time'] = $r['draw_time'];
            $data['game_id']   = (int)$mm['id'];
            if ($model->insert($data)) $updated++; else $skipped++;
        }
    }

    return redirect()->back()->with('success', "Synchronizacja MM: zaktualizowano: {$updated}, pominięto: {$skipped}");
}

    
}
