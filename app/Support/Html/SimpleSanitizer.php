<?php

namespace App\Support\Html;

use HTMLPurifier;
use HTMLPurifier_Config;

class SimpleSanitizer
{
    /**
     * Sanitize HTML content using HTMLPurifier with a safe profile.
     */
    public static function sanitize(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        // Allow a safe subset of HTML suitable for comments
        $config->set('HTML.Allowed', 'a[href|title|rel|target],b,strong,i,em,u,p,br,ul,ol,li,blockquote,code,pre');
        $config->set('URI.DisableJavaScript', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $config->set('HTML.TargetBlank', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.Linkify', false);
        $config->set('Cache.SerializerPath', storage_path('app/purifier'));

        $purifier = new HTMLPurifier($config);
        $clean = $purifier->purify($html);

        return trim($clean);
    }
}


