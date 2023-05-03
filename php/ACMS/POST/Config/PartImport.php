<?php

class ACMS_POST_Config_PartImport extends ACMS_POST_Config_Import
{
    /**
     * @return \Field
     */
    function post()
    {
        @set_time_limit(0);

        if ( !$this->checkAuth() ) {
            return $this->Post;
        }

        $rid = $this->Post->get('rid', null);
        if ( !($setid = intval($this->Get->get('setid'))) ) { $setid = null; }
        if (empty($rid)) {$rid = null;}

        try {
            $yaml = Config::yamlLoad($_FILES['file']['tmp_name']);
            $config = new Field();
            foreach ($yaml as $key => $val) {
                $config->addField($key, $val);
            }
            $config = Config::fix($config);
            Config::saveConfig($config, BID, $rid, null, $setid);

            $this->Post->set('import', 'success');
        } catch ( \Exception $e ) {
            $this->addError($e->getMessage());
        }

        return $this->Post;
    }
}
