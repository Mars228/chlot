<?php
namespace App\Controllers;

use App\Models\GameVariantModel;

/**
 * Pod-zasób: Warianty gry (np. „10 z 80”, „6 z 49”, „5 z 50 + 2 z 12”).
 */
class GameVariantsController extends BaseController
{
    public function store(int $gameId)
    {
        $model = new GameVariantModel();
        $data = [
            'game_id'     => $gameId,
            'name'        => $this->request->getPost('name'),
            'picks_a_min' => $this->request->getPost('picks_a_min') ?: null,
            'picks_a_max' => $this->request->getPost('picks_a_max') ?: null,
            'picks_b_min' => $this->request->getPost('picks_b_min') ?: null,
            'picks_b_max' => $this->request->getPost('picks_b_max') ?: null,
            'price'       => $this->request->getPost('price') ?: null,
            'is_default'  => (int) (bool) $this->request->getPost('is_default'),
        ];

        if (! $model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        return redirect()->to('/gry/'.$gameId)->with('success', 'Dodano wariant.');
    }

    public function delete(int $gameId, int $variantId)
    {
        $model = new GameVariantModel();
        $model->delete($variantId);
        return redirect()->to('/gry/'.$gameId)->with('success', 'Usunięto wariant.');
    }
}