<?php
namespace App\Controllers;

use App\Models\PrizeTierModel;
use App\Models\GameVariantModel;

/**
 * Pod-zasób: Progi wypłat per wariant.
 * payout_type: fixed (kwota PLN) / coefficient (udział/procent dla EJ itp.).
 */
class PrizeTiersController extends BaseController
{
    public function store(int $variantId)
    {
        $variant = (new GameVariantModel())->find($variantId);
        if (! $variant) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Wariant nie istnieje');
        }

        $model = new PrizeTierModel();
        $data = [
            'game_variant_id' => $variantId,
            'matched_a'       => $this->request->getPost('matched_a') ?: null,
            'matched_b'       => $this->request->getPost('matched_b') ?: null,
            'payout_type'     => $this->request->getPost('payout_type'),
            'value'           => $this->request->getPost('value'),
            'description'     => $this->request->getPost('description'),
        ];

        if (! $model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        return redirect()->to('/gry/'.$variant['game_id'])->with('success', 'Dodano próg wypłat.');
    }

    public function delete(int $variantId, int $tierId)
    {
        $variant = (new GameVariantModel())->find($variantId);
        $model   = new PrizeTierModel();
        $model->delete($tierId);
        return redirect()->to('/gry/'.$variant['game_id'])->with('success', 'Usunięto próg wypłat.');
    }
}