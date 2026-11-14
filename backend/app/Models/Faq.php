<?php

namespace App\Models;

use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['question', 'answer', 'sort_order', 'is_active'])]
class Faq extends OrderedContent
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;
}
