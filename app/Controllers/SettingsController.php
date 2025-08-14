<?php
namespace App\Controllers;

use App\Models\SettingModel;
use CodeIgniter\I18n\Time;

class SettingsController extends BaseController
{
    public function index()
    {
        $settings = new SettingModel();
        $secret = $settings->get('lotto_api_secret', '');

        // Prefill (opcjonalne wartości z sesji po teście)
        $last = session('api_last_result'); // ['gameType','drawSystemId','drawDateLocal']
        $content = view('settings/index', compact('secret', 'last'));
        return view('layouts/adminlte', ['title' => 'Ustawienia', 'content' => $content]);
    }

    public function save()
    {
        $secret = trim((string) $this->request->getPost('lotto_api_secret'));
        if ($secret === '') {
            return redirect()->back()->with('errors', ['Podaj klucz API.']);
        }
        $ok = (new SettingModel())->setKV('lotto_api_secret', $secret);
        return redirect()->to('/ustawienia')->with('success', $ok ? 'Zapisano klucz API.' : 'Nie udało się zapisać klucza.');
    }

    /** Prosty test – pobiera ostatnie wyniki dla wybranej gry i pokazuje skrót odpowiedzi. */
    public function testApi()
    {
        $settings = new SettingModel();
        $secret = $settings->get('lotto_api_secret');
        if (! $secret) {
            return redirect()->back()->with('errors', ['Brak klucza API. Zapisz klucz i spróbuj ponownie.']);
        }
        $slug = $this->request->getPost('game_slug') ?: 'lotto';
        $map = [
            'lotto' => 'Lotto',
            'eurojackpot' => 'EuroJackpot',
            'multi-multi' => 'MultiMulti',
        ];
        $gameType = $map[$slug] ?? 'Lotto';

        $client = \Config\Services::curlrequest();
        $url = 'https://developers.lotto.pl/api/open/v1/lotteries/draw-results/last-results-per-game';
        $resp = $client->get($url, [
            'headers' => [
                'accept' => 'application/json',
                'secret' => $secret,
                'Secret' => $secret, // na wszelki wypadek
            ],
            'query' => [ 'gameType' => $gameType ],
            'http_errors' => false,
            'timeout' => 10,
        ]);

        $status = $resp->getStatusCode();
        $body = (string) $resp->getBody();
        $json = json_decode($body, true);

        if ($status !== 200) {
            return redirect()->back()->with('errors', ["Błąd API ($status): $body"]);
        }
        if (! is_array($json) || empty($json)) {
            return redirect()->back()->with('errors', ['Połączenie OK, ale API zwróciło pustą listę. Spróbuj inną grę.']);
        }

        // Weź pierwszy wpis i pokaż skrót + zaproponuj prefill do /losowania/pobierz
        $it = $json[0];
        $drawUtc = $it['drawDate'] ?? null;
        $dt = $drawUtc ? new \DateTime($drawUtc, new \DateTimeZone('UTC')) : null;
        if ($dt) { $dt->setTimezone(new \DateTimeZone('Europe/Warsaw')); }
        $drawLocal = $dt ? $dt->format('Y-m-d\TH:i') : '';

        session()->set('api_last_result', [
            'gameType' => $gameType,
            'slug' => $slug,
            'drawSystemId' => $it['drawSystemId'] ?? null,
            'drawDateLocal' => $drawLocal,
        ]);

        return redirect()->to('/ustawienia')->with('success', 'Połączenie OK. Pobrano ostatni wynik dla gry: ' . $gameType);
    }
}