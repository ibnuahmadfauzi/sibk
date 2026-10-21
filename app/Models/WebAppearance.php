<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['logo_path', 'agency_logo_path', 'theme'])]
final class WebAppearance extends Model
{
    public const THEMES = ['default', 'pine', 'slate'];

    protected $table = 'web_appearances';
}
