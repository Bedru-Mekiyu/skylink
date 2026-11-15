<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An old article URL that now answers with a 301 to the article's current slug. */
class NewsSlugRedirect extends Model
{
    protected $fillable = ['news_id', 'old_slug'];

    public function news()
    {
        return $this->belongsTo(News::class);
    }
}
