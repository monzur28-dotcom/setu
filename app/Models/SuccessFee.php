<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class SuccessFee extends Model
{
    protected $guarded = ['id'];

    /**
     * Two-person confirmation. An operator whose income depends partly on
     * success fees must not be the sole recorder of success. Spec 18.5.
     */
    public function confirm(User $confirmer): void
    {
        if ($confirmer->id === $this->recorded_by) {
            throw new RuntimeException(
                'A success fee must be confirmed by someone other than the person who recorded it.'
            );
        }

        $this->update(['confirmed_by' => $confirmer->id, 'status' => 'INVOICED']);
    }
}
