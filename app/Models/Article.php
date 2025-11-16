<?php

namespace App\Models;

/**
 * Article is an alias of Post to match spec terminology.
 * It inherits all relationships, casts, scopes, and accessors.
 */
class Article extends Post
{
    /**
     * Author alias for the existing user() relationship.
     */
    public function author()
    {
        return $this->user();
    }
}
