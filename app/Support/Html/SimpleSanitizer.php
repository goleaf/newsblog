<?php

namespace App\Support\Html;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\File;

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
        // Only allow safe URI schemes to prevent javascript: payloads
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $config->set('HTML.TargetBlank', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.Linkify', false);
        $cachePath = storage_path('framework/cache/purifier');
        File::ensureDirectoryExists($cachePath);
        $config->set('Cache.SerializerPath', $cachePath);

        $purifier = new HTMLPurifier($config);
        $clean = $purifier->purify($html);

        return trim($clean);
    }
}
