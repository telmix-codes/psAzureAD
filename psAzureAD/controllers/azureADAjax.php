<?php
/**
 * Azure Active Directory Ajax Processes.
 */

G::LoadClass('pmFunctions');
require_once PATH_PLUGIN_PS_AZUREAD . 'classes/azureADFunctions.php';

$option = $_REQUEST['option'];
switch ($option) {
    //Get configuration
    case 'getConfiguration':
        $response = aadGetConfiguration();
        echo $response;
        break;
    //Save the configuration
    case 'saveConfiguration':
        $configId = $_REQUEST['configId'];
        $tenantId = $_REQUEST['tenantId'];
        $clientId = $_REQUEST['clientId'];
        $clientSecret = $_REQUEST['clientSecret'];
        $response = aadSaveConfiguration($configId, $tenantId, $clientId, $clientSecret);
        echo $response;
        break;
    //Generate new App Access Token
    case 'generateAppAccessToken':
        $configId = $_REQUEST['configId'];
        $response = aadGenerateAppAccessToken($configId);
        echo $response;
        break;
    //Get App Access Token
    case 'getAppAccessToken':
        $configId = $_REQUEST['configId'];
        $response = aadGetAppAccessToken($configId);
        echo $response;
        break;
    //Get Azure Users
    case 'getAzureUsers':
        $response = aadGetAzureUsers();
        echo $response;
        break;
    //Sync Users
    case 'syncUsers':
        $data = $_REQUEST['data'];
        $response = aadSyncUsers($data);
        echo $response;
        break;
    //Get Azure Groups
    case 'getAzureGroups':
        $response = aadGetAzureGroups();
        echo $response;
        break;
    //Sync Groups
    case 'syncGroups':
        $data = $_REQUEST['data'];
        $response = aadSyncGroups($data);
        echo $response;
        break;
}

?>