<?php

namespace Modules\Ai\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Ai\Concerns\HasExecutionAttributes;

class Execution extends Model
{
    use HasExecutionAttributes;
}
