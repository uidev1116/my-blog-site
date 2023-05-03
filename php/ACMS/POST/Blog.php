<?php

class ACMS_POST_Blog extends ACMS_POST
{
    protected $workflowData = false;

    /**
     * ToDo: deprecated method 2.7.0
     */
    function isCodeExists($domain, $code, $bid=null, $aid=null)
    {
        return Blog::isCodeExists($domain, $code, $bid, $aid);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function isValidStatus($val, $update=false)
    {
        return Blog::isValidStatus($val, $update);
    }

    /**
     * ToDo: deprecated method 2.7.0
     */
    function isDomain($domain, $isAlias=false, $update=false)
    {
        return Blog::isDomain($domain, $this->Get->get('aid'), $isAlias, $update);
    }
}
