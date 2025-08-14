<?php
namespace App\Controllers;

use App\Models\GameModel;
use App\Models\GamePickGroupModel;
use App\Models\GameVariantModel;
use App\Models\PrizeTierModel;

/**
 * Kontroler GRY odpowiada za CRUD gier oraz prezentację pod-zasobów
 * (grupy zakresów, warianty i progi wypłat) na stronie szczegółów gry.
 * Zasada DRY: logika walidacji w Modelach, wspólne fragmenty UI w partialach.
 */
class GamesController extends BaseController
{
    /** Lista gier z paginacją */
    public function index()
    {
        $model = new GameModel();
        $games = $model->orderBy('name', 'ASC')->paginate(10);

        $content = view('games/index', [
            'games' => $games,
            'pager' => $model->pager,
        ]);

        return view('layouts/adminlte', [
            'title' => 'Gry',
            'content' => $content,
        ]);
    }

    /** Formularz tworzenia gry */
    public function create()
    {
        $content = view('games/create');
        return view('layouts/adminlte', ['title' => 'Nowa gra', 'content' => $content]);
    }

    /** Zapis nowej gry (POST) */
    public function store()
    {
        helper('upload');
        $model = new GameModel();

        $data = [
            'name' => $this->request->getPost('name'),
            'slug' => $this->request->getPost('slug'),
            'description' => $this->request->getPost('description'),
            'default_price' => $this->request->getPost('default_price') ?: null,
            'is_active' => (int) (bool) $this->request->getPost('is_active'),
        ];

        // Upload logo (opcjonalny)
        $logo = $this->request->getFile('logo');
        if ($logo && $logo->isValid()) {
            $path = handle_logo_upload($logo);
            if ($path) {
                $data['logo_path'] = $path;
            }
        }

        if (! $model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('/gry')->with('success', 'Gra została dodana.');
    }

    /** Szczegóły gry + zarządzanie grupami/wariantami/progami (na zakładkach) */
    public function show(int $id)
    {
        $games = new GameModel();
        $game  = $games->find($id);
        if (! $game) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Gra nie istnieje');
        }

        $groups  = (new GamePickGroupModel())->where('game_id', $id)->orderBy('code')->findAll();
        $variants= (new GameVariantModel())->where('game_id', $id)->orderBy('is_default','DESC')->orderBy('name','ASC')->findAll();

        // Dla wygody: progi wypłat pogrupowane per wariant
        $tiersByVariant = [];
        if ($variants) {
            $tierModel = new PrizeTierModel();
            foreach ($variants as $v) {
                $tiersByVariant[$v['id']] = $tierModel->where('game_variant_id', $v['id'])->orderBy('matched_a','DESC')->orderBy('matched_b','DESC')->findAll();
            }
        }

        $content = view('games/show', compact('game','groups','variants','tiersByVariant'));
        return view('layouts/adminlte', ['title' => 'Szczegóły gry', 'content' => $content]);
    }

    /** Formularz edycji gry */
    public function edit(int $id)
    {
        $model = new GameModel();
        $game  = $model->find($id);
        if (! $game) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Gra nie istnieje');
        }

        $content = view('games/edit', compact('game'));
        return view('layouts/adminlte', ['title' => 'Edytuj grę', 'content' => $content]);
    }

    /** Aktualizacja gry (POST) */
    public function update(int $id)
    {
        helper('upload');
        $model = new GameModel();
        $game  = $model->find($id);
        if (! $game) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Gra nie istnieje');
        }

        $data = [
            'id' => $id,
            'name' => $this->request->getPost('name'),
            'slug' => $this->request->getPost('slug'),
            'description' => $this->request->getPost('description'),
            'default_price' => $this->request->getPost('default_price') ?: null,
            'is_active' => (int) (bool) $this->request->getPost('is_active'),
        ];

        $logo = $this->request->getFile('logo');
        if ($logo && $logo->isValid()) {
            $path = handle_logo_upload($logo);
            if ($path) {
                $data['logo_path'] = $path;
            }
        }

        if (! $model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to('/gry/'.$id)->with('success', 'Zaktualizowano grę.');
    }

    /** Usunięcie gry (POST) */
    public function delete(int $id)
    {
        $model = new GameModel();
        $model->delete($id);
        return redirect()->to('/gry')->with('success', 'Gra została usunięta.');
    }
}