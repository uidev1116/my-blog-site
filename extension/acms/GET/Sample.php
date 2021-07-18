<?php

namespace Acms\Custom\GET;

use ACMS_GET;
use Template;
use ACMS_Corrector;

/**
 * テンプレート上では、標準のGETモジュールと同様に、
 * '<!-- BEGIN_MODULE Sample --><!--END_MODULE Sample -->' で呼び出されます。
 */
class Sample extends ACMS_GET
{
    /**
      例
      <!-- BEGIN_MODULE Sample -->
      <!-- BEGIN message -->
      ブロックの表示
      <!-- END message -->

      <!-- BEGIN message2 -->
      <p>{msg}</p>
      <!-- END message2 -->

      <p>件数: {count}件</p>

      <ul>
        <!-- BEGIN data:loop -->
        <li>{id}: {name}</li>
        <!-- END data:loop -->
      </ul>
      <!-- END_MODULE Sample -->
     */
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $data = array(
            array(
                'id' => 'aaa',
                'name' => '山田太郎',
            ),
            array(
                'id' => 'bbb',
                'name' => '鈴木次郎',
            ),
            array(
                'id' => 'ccc',
                'name' => '佐藤三郎',
            ),
        );

        $obj = array(
            'data' => $data,
            'count' => count($data),
            'message' => (object)[],
            'message2' => array(
                'msg' => 'ブロック内の変数',
            )
        );

        return $Tpl->render($obj);
    }
}
