<?php

namespace App\Enums;

enum MenuItemType: string
{
    case Link = 'link';
    case Page = 'page';
    case Category = 'category';
    case Tag = 'tag';
}


