<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleView extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'ip_address',
        'user_agent',
        'referer',
        'viewed_at',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime'
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
