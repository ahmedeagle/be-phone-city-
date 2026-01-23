<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Offerable extends Pivot
{
    protected $table = 'offerables';

    public $incrementing = true;

    public function offerable()
    {
        return $this->morphTo();
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
