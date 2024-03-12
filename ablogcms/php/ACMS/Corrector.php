<?php

use Acms\Services\Common\CorrectorFactory;

class ACMS_Corrector
{
    /**
     * @var Acms\Services\Common\CorrectorFactory
     */
    protected $factory;

    /**
     * ACMS_Corrector constructor.
     */
    public function __construct()
    {
        $this->factory = CorrectorFactory::singleton();
    }

    public function __call($method, $argument)
    {
        return $this->factory->call($method, '', $argument, true);
    }

    /**
     * @param string $txt
     * @param string $opt
     * @param string $name
     * @return string
     */
    public function correct($txt, $opt, $name)
    {
        if (
            !( 0
            or 'selected' == $name
            or 'checked' == $name
            or 'disabled' == $name
            or 'class' == $name
            or 'attr' == $name
            or is_int(strpos($name, ':selected'))
            or is_int(strpos($name, ':checked'))
            )
        ) {
            if (empty($opt)) {
                $opt = 'escape';
            } elseif (
                1
                && strpos($opt, 'escape') === false
                && strpos($opt, 'raw') === false
                && strpos($opt, 'resizeImg') === false
            ) {
                $opt = 'escape|' . $opt;
            }
            // [allow_dangerous_tag]校正オプションが指定されてなければ、危険なタグを削除する校正オプションを付与
            if (config('strip_dangerous_tag') === 'on' && strpos($opt, 'allow_dangerous_tag') === false) {
                $dangerousTags = configArray('dangerous_tags');
                if (empty($dangerousTags)) {
                    $dangerousTags = ['script', 'iframe'];
                }
                $opt = 'strip_select_tags(\'' . implode('\',\'', $dangerousTags) . '\')|' . $opt;
            }
        }
        if (!empty($opt)) {
            $opt    = '|' . $opt;
            while (preg_match('@\s*\|\s*([^(|\s]+)\s*(\()?\s*(.*)@', $opt, $match)) {
                $method = $match[1];
                $opt    = $match[3];
                $args   = array();
                if (!empty($match[2])) {
                    while (
                        preg_match('@' . '(?:'
                        . '([-\d.]+)' . '|'
                        . '"((?:[^"]|\\\")*)"' . '|'
                        . "'((?:[^']|\\\')*)'"
                        . ')' . '\s*(,|\))\s*(.*)@', $opt, $match)
                    ) {
                        $args[] = $match[1] | $match[2] | $match[3];
                        $opt    = $match[5];
                        if (')' == $match[4]) {
                            break;
                        }
                    }
                }
                if ('list' == $method) {
                    $method = 'acms_corrector_list';
                }
                $res = $this->factory->call($method, $txt, $args);

                if ($res !== false) {
                    $txt = $res;
                }
            }
        }
        return $txt;
    }
}
