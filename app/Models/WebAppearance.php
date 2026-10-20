<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['logo_path'])]
final class WebAppearance extends Model
{
    protected $table = 'web_appearances';
}
