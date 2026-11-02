<?php

namespace Modules\Ai\Enums;

enum ExecutionStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Paused = 'paused';
    case Failed = 'failed';
}
