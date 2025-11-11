<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    protected $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        $config->set('HTML.Allowed', 'p,br,strong,em,u,h2,h3,h4,h5,h6,ul,ol,li,a[href|title|target],img[src|alt|width|height],blockquote,pre,code,table,thead,tbody,tr,th,td,hr,span[class],div[class]');

        $config->set('HTML.AllowedAttributes', 'a.href,a.title,a.target,img.src,img.alt,img.width,img.height,span.class,div.class');

        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);

        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        $config->set('AutoFormat.AutoParagraph', false);

        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', false);

        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(?string $html): ?string
    {
        if (empty($html)) {
            return $html;
        }

        return $this->purifier->purify($html);
    }

    public function sanitizeMultiple(array $fields): array
    {
        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                $fields[$key] = $this->sanitize($value);
            }
        }

        return $fields;
    }
}
