<?php

namespace App\Models;

/**
 * Induk semua model. Model hanya berisi query ke database.
 */
abstract class Model
{
    public function __construct(protected Database $db)
    {
    }
}
