<?php

namespace Acms\Services\Process;

use AsyncPHP\Doorman\Task\ProcessCallbackTask;

class SingletonTask extends ProcessCallbackTask
{
    public function stopsSiblings()
    {
        return true;
    }
}