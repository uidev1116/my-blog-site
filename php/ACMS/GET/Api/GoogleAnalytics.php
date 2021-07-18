<?php

require_once LIB_DIR."vendor/autoload.php";

class ACMS_GET_Api_GoogleAnalytics extends ACMS_GET_Api
{
    private $analytics  = null;
    private $profile    = null;
    private $startDate  = '7daysAgo';
    private $endDate    = 'today';

    const GA_DATE_PREG  = '/[0-9]{4}-[0-9]{2}-[0-9]{2}|today|yesterday|[0-9]+(daysAgo)/';

    function getService($email, $key)
    {
        // Creates and returns the Analytics service object.

        // Create and configure a new client object.
        $config = new Google_Config();
        $config->setClassConfig('Google_Cache_File', array(
            'directory' => SCRIPT_DIR.ARCHIVES_DIR,
        ));
        $Client = new Google_Client($config);
        $Client->setApplicationName("ACMS");
        $analytics = new Google_Service_Analytics($Client);

        // Read the generated client_secrets.p12 key.
        $key  = Storage::get($key);
        $Cred = new Google_Auth_AssertionCredentials(
            $email,
            array(Google_Service_Analytics::ANALYTICS_READONLY),
            $key
        );
        $Client->setAssertionCredentials($Cred);
        if ( $Client->getAuth()->isAccessTokenExpired() ) {
            $Client->getAuth()->refreshTokenWithAssertion($Cred);
        }

        $this->analytics = $analytics;
        $this->getFirstProfileId();
    }

    function getFirstprofileId() 
    {
        // Get the user's first view (profile) ID.

        // Get the list of accounts for the authorized user.
        $accounts = $this->analytics->management_accounts->listManagementAccounts();

        if ( count($accounts->getItems()) > 0 ) {
            $items = $accounts->getItems();
            $firstAccountId = $items[0]->getId();

            // Get the list of properties for the authorized user.
            $properties = $this->analytics->management_webproperties
                ->listManagementWebproperties($firstAccountId);

            if ( count($properties->getItems()) > 0 ) {
                  $items = $properties->getItems();
                  $firstPropertyId = $items[0]->getId();

                  // Get the list of views (profiles) for the authorized user.
                  $profiles = $this->analytics->management_profiles
                        ->listManagementProfiles($firstAccountId, $firstPropertyId);

                if ( count($profiles->getItems()) > 0 ) {
                    $items = $profiles->getItems();

                    // Return the first view (profile) ID.
                    $this->profileId = $items[0]->getId();

                } else {
                    throw new Exception('No views (profiles) found for this user.');
                }
            } else {
              throw new Exception('No properties found for this user.');
            }
        } else {
            throw new Exception('No accounts found for this user.');
        }
    }

    function setStartDate($date)
    {
        if ( preg_match(self::GA_DATE_PREG, $date) ) {
            $this->startDate = $date;
        }
    }

    function setEndDate($date)
    {
        if ( preg_match(self::GA_DATE_PREG, $date) ) {
            $this->endDate = $date;
        }
    }

    function getResults($metrics='ga:sessions', $options=array())
    {
        return $this->analytics->data_ga->get(
            'ga:'.$this->profileId,
            $this->startDate,
            $this->endDate,
            $metrics,
            $options
        );
    }
}
