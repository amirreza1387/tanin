<?php

namespace App\Enums;

enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';

    public function label(): string
    {
        return __('enums.media_types.'.$this->value);
    }
}
