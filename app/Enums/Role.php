<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case REPORTER = 'reporter';
    case USER = 'user';

    public function label(): string
    {
        return __('enums.roles.'.$this->value);
    }
}
