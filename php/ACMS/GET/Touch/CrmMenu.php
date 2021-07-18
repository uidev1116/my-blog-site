<?php

class ACMS_GET_Touch_CrmMenu extends ACMS_GET
{
var $crm_admin_path = array(
'crm_index',
'crm_customer_index',
'crm_customer_edit',
'crm_crmmail_index',
'crm_crmmail_search',
'crm_crmmail_edit',
'crm_crmmail_draft',
'crm_crmmail_trash',
'crm_crmmail_mail',
'crm_crmmail_drdetail',
'crm_crmmail_tmdetail',
'crm_crmmail_trdetail',
'crm_crmmail_garble',
'crm_crmmail_bunch',
'crm_ctag_index',
'crm_ctag_edit',
'crm_form_index',
'crm_form_edit',
'crm_form_log',
'crm_config_index',
'crm_crm_index',
'crm_crm_edit',
'crm_config_mail',
'crm_config_search',
'crm_config_auth',
'crm_config_auth_index',
'crm_config_auth_edit',
'crm_config_graph_index',
'crm_config_graph_edit',
'crm_config_receive_index',
'crm_config_receive_edit',
);

function get()
{
return in_array(ADMIN,$this->crm_admin_path)?$this->tpl:false;
}
}