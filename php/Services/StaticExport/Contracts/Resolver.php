<?php

namespace Acms\Services\StaticExport\Contracts;

abstract class Resolver
{
    /**
     * @param string $html
     * @param string $document_root
     * @param string $offset_dir
     * @param string $domain
     * @param string $blog_code
     *
     * @return string
     */
    abstract public function resolve($html, $document_root, $offset_dir, $domain, $blog_code);
}