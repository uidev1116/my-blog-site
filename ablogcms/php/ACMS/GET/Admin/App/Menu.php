<?php

class ACMS_GET_Admin_App_Menu extends ACMS_GET_Admin_App_Index
{
    /**
     * @var bool
     */
    protected $exists = false;

    /**
     * @return string
     */
    function get()
    {
        if (!sessionWithAdministration()) {
            return '';
        }

        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $apps = array_merge($this->getAppList(), $this->getLegacyAppList());
        $this->build($Tpl, $apps);
        if (!$this->exists) {
            return '';
        }

        return $Tpl->get();
    }

    /**
     * @inheritdoc
     */
    protected function build($Tpl, $lists)
    {
        $DB = DB::singleton(dsn());

        foreach ($lists as $app) {
            $className = get_class($app);

            $SQL    = SQL::newSelect('app');
            $SQL->addWhereOpr('app_name', $className);

            // DBになければインストール前として扱う
            $status = 'init';

            if (!!($all = $DB->query($SQL->get(dsn()), 'all'))) {
                $existsOnThisBlog = false;
                foreach ($all as $row) {
                    if (intval($row['app_blog_id']) === BID) {
                        $existsOnThisBlog = $row;
                    }
                }
                if ($existsOnThisBlog) {
                    $status = $existsOnThisBlog['app_status'];
                } else {
                    $status = 'off';
                }
            }
            if ($status !== 'on') {
                continue;
            }
            if (!$app->menu) {
                continue;
            }

            $vars = array(
                'name'      => $app->name,
                'url'       => acmsLink(array('admin' => 'app_' . $app->menu, 'bid' => BID)),
                'className' => $className,
            );
            $reg = '/^app_' . $app->menu . '/';
            if (preg_match($reg, ADMIN)) {
                $vars['stay'] = ' class="stay"';
            }
            $Tpl->add('app:loop', $vars);

            $this->exists = true;
        }
    }
}
