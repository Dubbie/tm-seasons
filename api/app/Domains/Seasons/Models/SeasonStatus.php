<?php

namespace App\Domains\Seasons\Models;

enum SeasonStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Ended = 'ended';
    case Finalized = 'finalized';
}
