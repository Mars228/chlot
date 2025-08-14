<?php
namespace App\Controllers;

use App\Models\GamePickGroupModel;

/**
 * Pod-zasób: Grupy zakresów (A/B). Prosty store/delete, UI w GamesController::show.
 */
class GamePickGroupsController extends BaseController
{
    public function store(int $gameId)
    {
        $model = new GamePickGroupModel();
        $data = [
            'game_id'   => $gameId,
            'code'      => $this->request->getPost('code'), // 'A' lub 'B'
            'range_min' => (int) $this->request->getPost('range_min'),
            'range_max' => (int) $this->request->getPost('range_max'),
        ];
        if (! $model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        return redirect()->to('/gry/'.$gameId)->with('success', 'Dodano grupę zakresu.');
    }

    public function delete(int $gameId, int $groupId)
    {
        $model = new GamePickGroupModel();
        $model->delete($groupId);
        return redirect()->to('/gry/'.$gameId)->with('success', 'Usunięto grupę zakresu.');
    }
}