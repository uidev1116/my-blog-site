<?php

namespace Acms\Services\StaticExport;

class Destination
{
    /**
     * @var string
     */
    protected $destinationDocumentRoot;

    /**
     * @var string
     */
    protected $destinationOffsetDir;

    /**
     * @var string
     */
    protected $destinationDiffDir;

    /**
     * @var string
     */
    protected $destinationDomain;

    /**
     * @var string
     */
    protected $blogCode;

    /**
     * @return string
     */
    public function getDestinationDomain()
    {
        return $this->destinationDomain;
    }

    /**
     * @param string $destinationDomain
     */
    public function setDestinationDomain($destinationDomain)
    {
        $this->destinationDomain = $destinationDomain;
    }

    /**
     * @return string
     */
    public function getDestinationPath()
    {
        $path = $this->getDestinationDiffDir();
        if (empty($path)) {
            return $this->getDestinationDocumentRoot() . $this->getDestinationOffsetDir();
        }
        return $path;
    }

    /**
     * @return string
     */
    public function getDestinationDocumentRoot()
    {
        return $this->destinationDocumentRoot;
    }

    /**
     * @param string $destinationDocumentRoot
     */
    public function setDestinationDocumentRoot($destinationDocumentRoot)
    {
        $this->destinationDocumentRoot = rtrim($destinationDocumentRoot, '/') . '/';
    }

    /**
     * @return string
     */
    public function getDestinationOffsetDir()
    {
        return $this->destinationOffsetDir;
    }

    /**
     * @param string $destinationOffsetDir
     */
    public function setDestinationOffsetDir($destinationOffsetDir)
    {
        $this->destinationOffsetDir = trim($destinationOffsetDir, '/') . '/';
        if ( $this->destinationOffsetDir === '/' ) {
            $this->destinationOffsetDir = '';
        }
    }

    /**
     * @return string
     */
    public function getDestinationDiffDir()
    {
        return $this->destinationDiffDir;
    }

    /**
     * @param string $destinationDiffDir
     */
    public function setDestinationDiffDir($destinationDiffDir)
    {
        $this->destinationDiffDir = rtrim($destinationDiffDir, '/') . '/';
        if ($this->destinationDiffDir === '/') {
            $this->destinationDiffDir = '';
        }
    }

    /**
     * @return string
     */
    public function getBlogCode()
    {
        return $this->blogCode;
    }

    /**
     * @param string $code
     */
    public function setBlogCode($code)
    {
        $this->blogCode = trim($code, '/') . '/';
        if ( $this->blogCode === '/' ) {
            $this->blogCode = '';
        }
    }
}