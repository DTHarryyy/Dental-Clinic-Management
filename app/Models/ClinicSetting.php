<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicSetting extends Model
{
    protected $fillable = ['clinic_name', 'phone', 'email', 'address', 'tax_id', 'website'];

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
