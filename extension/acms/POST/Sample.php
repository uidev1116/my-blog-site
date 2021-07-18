<?php

namespace Acms\Custom\POST;

use ACMS_POST;

/**
 * テンプレート上では、標準のPOSTモジュールと同様に、
 * '<input type="submit" name="ACMS_POST_Sample" value="送信" />' で呼び出されます。
 */
class Sample extends ACMS_POST
{
    function post()
    {
        return $this->Post;
    }
}
