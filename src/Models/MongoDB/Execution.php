<?php

namespace Modules\Ai\Models\MongoDB;

use Modules\Ai\Concerns\HasExecutionAttributes;
use MongoDB\Laravel\Eloquent\Model;

class Execution extends Model
{
    use HasExecutionAttributes;
}
