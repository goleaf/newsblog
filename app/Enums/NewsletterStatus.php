<?php

namespace App\Enums;

enum NewsletterStatus: string
{
    case Pending = 'pending';
    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';
}


