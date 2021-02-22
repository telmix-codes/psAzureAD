<?php

G::loadClass("pmFunctions");
require_once(PATH_CORE.'classes/model/Groupwf.php');
require_once(PATH_CORE.'classes/model/Users.php');

/**
 * aadPmEscapeString
 * Allows escape the data for the DB.
 *
 * @param $stringToBeEscaped (String) //String to be escaped to mysql
 *
 * @return string //Escaped string
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadPmEscapeString($stringToBeEscaped)
{
    try {
        $con = Propel::getConnection('workflow');
        switch ($con->getDSN()["phptype"]) {
        case "mysql":
            $escapedData = mysql_real_escape_string($stringToBeEscaped);
            break;
        case "mysqli":
            $con = Propel::getConnection('workflow');
            $escapedData = mysqli_real_escape_string($con->getResource(), $stringToBeEscaped);
            break;
        default:
            $escapedData = mysql_real_escape_string($stringToBeEscaped);
        }
        return $escapedData;
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadPropelExecuteQuery
 * Execute queries with propel connection.
 *
 * @param $sqlStatement (String) //SQL code statement
 *
 * @return array
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadPropelExecuteQuery($sqlStatement = '')
{
    try {
        $connection = Propel::getConnection('workflow');
        $oPropel = $connection->createStatement();
        $resultQuery = $oPropel->executeQuery($sqlStatement, ResultSet::FETCHMODE_ASSOC);
        $response = true;
        if (gettype($resultQuery->getResource()) == 'object') {
            $response = [];
            while ($resultQuery->next()) {
                array_push($response, $resultQuery->getRow());
            }
        }
        return $response;
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadGetConfiguration
 * Get all the configuration related tih an output document.
 *
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadGetConfiguration()
{
    try {
        $sqlSOutputs = "SELECT PAADC_ID,
                               TENANT_ID,
                               CLIENT_ID,
                               CLIENT_SECRET,
                               APP_ACCESS_TOKEN
                        FROM PS_AZUREAD_CONFIGURATIONS";
        $resSOutputs = aadPropelExecuteQuery($sqlSOutputs);
        $resSOutputs = !empty($resSOutputs) ? $resSOutputs[0] : $resSOutputs;
        $response = [
            "success" => true,
            "data" => $resSOutputs
        ];
        return json_encode($response);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadSaveConfiguration
 * Save the configuration.
 *
 * @param $configId (String) //Configuration ID
 * @param $tenantId (String) //Tenant ID
 * @param $clienId (String) //Client ID of Application
 * @param $clientSecret (String) //Client Secret ID
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadSaveConfiguration($configId, $tenantId, $clienId, $clientSecret)
{
    try {
        $appAccessToken = '';
        //URL
        $url = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';
        //Body Request
        $bodyRequest = 'grant_type=client_credentials';
        $bodyRequest .= '&client_id=' . $clienId;
        $bodyRequest .= '&client_secret=' . $clientSecret;
        $bodyRequest .= '&scope=https://graph.microsoft.com/.default';
        //Call API
        $res = aadCallApi('POST', $url, $bodyRequest);
        //Evaluate Response
        if (!isset($res->error)) {
            //Update App Access Token
            $appAccessToken = $res->access_token;
        } else {
            $response = [
                "success" => false,
                "data" => 'Is not possible connect with Azure AD.<br/> Please verify your credentials.'
            ];
            return json_encode($response);
        }
        $sqlSOutputs = "SELECT PAADC_ID
                        FROM PS_AZUREAD_CONFIGURATIONS
                        WHERE PAADC_ID = '" . $configId . "'";
        $resSOutputs = aadPropelExecuteQuery($sqlSOutputs);
        if (empty($resSOutputs)) {
            //New configuration
            $sqlIConfiguration = "INSERT INTO PS_AZUREAD_CONFIGURATIONS (
                                                TENANT_ID,
                                                CLIENT_ID,
                                                CLIENT_SECRET,
                                                APP_ACCESS_TOKEN
                                            ) VALUES (
                                                '" . aadPmEscapeString($tenantId) . "',
                                                '" . aadPmEscapeString($clienId) . "',
                                                '" . aadPmEscapeString($clientSecret) . "',
                                                '" . aadPmEscapeString($appAccessToken) . "'
                                            )";
            aadPropelExecuteQuery($sqlIConfiguration);
        } else {
            //Update configuration
            $sqlUConfiguration = "UPDATE PS_AZUREAD_CONFIGURATIONS SET
                                        TENANT_ID = '" . aadPmEscapeString($tenantId) . "',
                                        CLIENT_ID = '" . aadPmEscapeString($clienId) . "',
                                        CLIENT_SECRET = '" . aadPmEscapeString($clientSecret) . "',
                                        APP_ACCESS_TOKEN = '" . aadPmEscapeString($appAccessToken) . "'
                                  WHERE PAADC_ID = '" . $configId . "'";
            aadPropelExecuteQuery($sqlUConfiguration);
        }
        $response = [
            "success" => true,
            "data" => 'The configuration has been successfully saved.'
        ];
        return json_encode($response);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadGetAppAccessToken
 * Get App Access Token of PS_AZUREAD_CONFIGURATIONS Table.
 *
 * @param $AADCUid (String) //Azure AD Config UID
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadGetAppAccessToken($AADCUid)
{
    try {
        $sqlSOutputs = "SELECT APP_ACCESS_TOKEN
                        FROM PS_AZUREAD_CONFIGURATIONS
                        WHERE PAADC_ID = '" . $AADCUid . "'";
        $resSOutputs = aadPropelExecuteQuery($sqlSOutputs);
        $resSOutputs = !empty($resSOutputs) ? $resSOutputs[0] : $resSOutputs;
        $response = [
            "success" => true,
            "data" => $resSOutputs
        ];
        return json_encode($response);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadSetAppAccessToken
 * Set New App Access Token in PS_AZUREAD_CONFIGURATIONS.
 *
 * @param $AADCUid (String) //Azure AD Config UID
 * @param $appAccessToken (String) //Token to Save
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadSetAppAccessToken($AADCUid, $appAccessToken)
{
    try {
        //Save App Access Token
        $sqlUToken = "UPDATE PS_AZUREAD_CONFIGURATIONS SET
                                APP_ACCESS_TOKEN = '" . $appAccessToken . "'
                        WHERE PAADC_ID = '" . $AADCUid . "'";
        $resSOutputs = aadPropelExecuteQuery($sqlUToken);
        $resUToken = !empty($resUToken) ? $resUToken[0] : $resUToken;
        $response = [
            "success" => true,
            "data" => $resUToken
        ];
        return json_encode($response);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadGenerateAppAccessToken
 * Get new App Access Token from Azure.
 *
 * @param $AADCUid (String) //Azure AD Config UID
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadGenerateAppAccessToken($AADCUid)
{
    try {
        $sqlSOutputs = "SELECT PAADC_ID,
                            TENANT_ID,
                            CLIENT_ID,
                            CLIENT_SECRET
                        FROM PS_AZUREAD_CONFIGURATIONS
                        WHERE PAADC_ID = '" . $AADCUid . "'";
        $resSOutputs = aadPropelExecuteQuery($sqlSOutputs);
        $resSOutputs = !empty($resSOutputs) ? $resSOutputs[0] : $resSOutputs;

        if ($resSOutputs != '') {
            //URL
            $url = 'https://login.microsoftonline.com/' . $resSOutputs['TENANT_ID'] . '/oauth2/v2.0/token';
            //Body Request
            $bodyRequest = 'grant_type=client_credentials';
            $bodyRequest .= '&client_id=' . $resSOutputs['CLIENT_ID'];
            $bodyRequest .= '&client_secret=' . $resSOutputs['CLIENT_SECRET'];
            $bodyRequest .= '&scope=https://graph.microsoft.com/.default';
            //Call API
            $res = aadCallApi('POST', $url, $bodyRequest);
            //Evaluate Response
            if (!isset($res->error)) {
                //Update App Access Token
                $sqlUConfiguration = "UPDATE PS_AZUREAD_CONFIGURATIONS SET
                APP_ACCESS_TOKEN = '" . $res->access_token . "'
                WHERE PAADC_ID = '" . $AADCUid . "'";
                aadPropelExecuteQuery($sqlUConfiguration);
                $response = [
                    "success" => true,
                    "data" => $res
                ];
            } else {
                $response = [
                    "success" => false,
                    "data" => $res
                ];
            }
        } else {
            $response = [
                "success" => false,
                "data" => 'No results'
            ];
        }

        return json_encode($response);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadUpdateStatusPSAzureADSynchronized
 * Update Status in PS_AZUREAD_CONFIGURATIONS
 * @param $pmUID (String) //Process Maker UID
 * @param $type (String) //Type of Relation USER / GROUP
 * @param $status (String) //New Status ACTIVE / INACTIVE
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadUpdateStatusPSAzureADSynchronized($pmUID, $type, $status)
{
    try {
        $sqlUTable = "UPDATE PS_AZUREAD_SYNCHRONIZED SET
                                        STATUS = '" . $status . "'
                                    WHERE PM_ID = '" . $pmUID . "' AND AAD_TYPE = '" . $type . "'";
        $resUpdate = aadPropelExecuteQuery($sqlUTable);
        $statusChange = !empty($resUpdate) ? true : false;
        return $statusChange;
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadInsertTableAzureADSynchronized
 * Insert in Table Relational PS_AZUREAD_SYNCHRONIZED
 *
 * @param $pmUID (String) //Process Maker UID
 * @param $azADID (String) //Azure Active Directory ID
 * @param $type (String) //Type of register (USER/GROUP)
 * @return array
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadInsertTableAzureADSynchronized($pmUID, $azADID, $type)
{
    $sqlIAzureADSynchronized = "INSERT INTO PS_AZUREAD_SYNCHRONIZED (
                                    PM_ID,
                                    AAD_ID,
                                    AAD_TYPE,
                                    STATUS
                                ) VALUES (
                                    '" . $pmUID . "',
                                    '" . $azADID . "',
                                    '" . $type . "',
                                    'ACTIVE'
                                )";
    $result = aadPropelExecuteQuery($sqlIAzureADSynchronized);
    return $result;
}

/**
 * aadCallApi
 * Connect with the Azure API.
 *
 * @param $method (String) //POST-GET-PUT-DELETE
 * @param $url (String) //URL Server
 * @param $data (String) //Body of request
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadCallApi($method, $url, $data, $headers = false)
{
    try {
        $curl = curl_init();
        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            default:
                if ($data) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
        }
        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $url);
        if (!$headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded',
                'SdkVersion: postman-graph/v1.0'
            ));
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded',
                'SdkVersion: postman-graph/v1.0',
                $headers
            ));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // EXECUTE:
        $result = curl_exec($curl);
        if (!$result) {
            die("Connection Failure");
        }
        curl_close($curl);
        return json_decode($result);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), true);
    }
}

/**
 * aadGetAzureADSynchronized
 * Get all Azure Active Directory Synchronized
 * @param $type (String) //Type od Request (USER or GROUP)
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadGetAzureADSynchronized($type)
{
    try {
        $sqlSOutputs = "SELECT PAADS_ID,
                                PM_ID,
                                AAD_ID,
                                AAD_TYPE,
                                STATUS
                        FROM PS_AZUREAD_SYNCHRONIZED
                        WHERE AAD_TYPE = '" . $type . "'
                        AND STATUS='ACTIVE'";
        $resSOutputs = aadPropelExecuteQuery($sqlSOutputs);
        $resSOutputs = !empty($resSOutputs) ? $resSOutputs : $resSOutputs;
        if ($type == 'GROUP') {
            $resultFilter = array();
            foreach ($resSOutputs as $key => $value) {
                if (aadVerifyGroup($value['PM_ID'])) {
                    //The group does exist
                    $resultFilter[] = $value;
                } else {
                    //The group no longer exists
                    aadUpdateStatusPSAzureADSynchronized($value['PM_ID'], $value['AAD_TYPE'], 'INACTIVE');
                }
            }
        } else {
            $resultFilter = $resSOutputs;
        }
        $response = [
            "success" => true,
            "data" => $resultFilter
        ];
        return json_encode($response, true);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadGetAzureUsers
 * Get List of Azure Users
 *
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadGetAzureUsers()
{
    try {
        //Get Config ID
        $confg = json_decode(aadGetConfiguration());
        //Generate New App Access Token
        $newToken = json_decode(aadGenerateAppAccessToken($confg->data->PAADC_ID));
        $appAccessToken = $newToken->data->access_token;
        //URL
        $url = 'https://graph.microsoft.com/v1.0/users';
        //Body Request
        $bodyRequest = false;
        //Header
        $headers = 'Authorization: Bearer '.$appAccessToken;
        //Call API
        $res = aadCallApi('GET', $url, $bodyRequest, $headers);

        if (!isset($res->error)) {
            //Get Synchronized Azure Active Directory Users in Process Maker
            $userSync = json_decode(aadGetAzureADSynchronized('USER'));

            $data = $res->value;
            foreach ($data as $k => $val) {
                $val->key = $k;
                $val->sync = false;
                $val->check = false;
                $val->checkable = true;
                if (empty($val->mail)) {
                    $val->checkable = false;
                }
                if (count($userSync->data) > 0) {
                    foreach ($userSync->data as $key => $value) {
                        if ($value->AAD_ID == $val->id) {
                            $val->sync = $value->PM_ID;
                            $val->check = true;
                            $val->checkable = false;
                        }
                    }
                }
            }
            if ($userSync->success) {
                $response = [
                    "success" => true,
                    "data" => $res->value
                ];
                return json_encode($response);
            } else {
                $response = [
                    "success" => false,
                    "data" => 'Error connecting to PS_AZUREAD_SYNCHRONIZED'
                ];
                return json_encode($response);
            }
        } else {
            //Error Token Expired
            if ($res->error->code == 'InvalidAuthenticationToken') {
                //Get Config ID
                $confg = json_decode(aadGetConfiguration());
                //Generate New App Access Token
                $newToken = json_decode(aadGenerateAppAccessToken($confg->data->PAADC_ID));
                if ($newToken->success) {
                    //Recursive Get List of Azure Users
                    $response = json_decode(aadGetAzureUsers());
                } else {
                    $response = [
                        "success" => false,
                        "data" => $newToken->error
                    ];
                    return json_encode($response);
                }
            } else {
                $response = [
                    "success" => false,
                    "data" => $res->error
                ];
                return json_encode($response);
            }
        }
        return json_encode($response);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadSyncUsers
 * Sync / Update Azure Users to Process Maker
 *
 * @param $data (array) //Data of User(s)
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadSyncUsers($data)
{
    try {
        $u = new Users();
        $syncDetail = [];
        foreach ($data as $user) {
            $user = (object) $user;
            //New values
            $user->password = 'G59_RwTXCxd&Q-Es';
            $user->dueDate = date('Y-m-d', strtotime('+4 years'));
            $user->pmUID = $user->sync;
            //Variable of Status
            $success = true;
            $errorDetail = '';
            //Decide Sync or Update
            if ($user->sync == 'false' || $user->sync == false) {
                //Create User in Process Maker
                $var = PMFCreateUser(
                    $user->mail, // string username: The username of the new user.
                    $user->password, // string password: The password of the new user, which can be up to 32 characters long.
                    $user->givenName, // string firstname: The first name of the user, which can be up to 50 characters long.
                    $user->surname, // string lastname: The last name of the user, which can be up to 50 characters long.
                    $user->mail, // string email: The email of the new user, which can be up to 100 characters long.
                    'PROCESSMAKER_OPERATOR', // string role: The role of the new user can be the roleCode such as 'PROCESSMAKER_ADMIN', 'PROCESSMAKER_OPERATOR', 'PROCESSMAKER_MANAGER' or the roleUid that is a unique UID of 32 characters long.
                    $user->dueDate, // string dueDate: Optional. The date in 'YYYY-MM-DD' format when the user's account will become inactive. If not included, then set to December 31 of the next year by default.
                    'ACTIVE' // string status: Optional. The status of the user, which can be 'ACTIVE' (default), 'INACTIVE', or 'VACATION'. If set to 'INACTIVE' or 'VACATION', the user can't be designated to work on tasks in cases.
                );
                if ($var) {
                    $aUser = $u->loadByUsernameInArray($user->mail);
                    //Get Process Maker UID
                    $user->pmUID = $aUser['USR_UID'];
                    $aUser = $u->load($user->pmUID);
                    //New date of Update
                    $dateUpdate = new DateTime($aUser['USR_UPDATE_DATE']);
                    $dateUpdate->modify("+1 second");
                    $dateUpdate = $dateUpdate->format("Y-m-d H:i:s");
                    //We insert new fields
                    $aUser['USR_CELLULAR'] = $user->mobilePhone;
                    $aUser['USR_POSITION'] = $user->jobTitle;
                    $aUser['USR_UPDATE_DATE'] = $dateUpdate;
                    $var = $u->update($aUser);
                }
            } else {
                //Update User in Process Maker
                $updateUser = $u->load($user->pmUID);
                $updateUser['USR_USERNAME'] = $user->mail;
                $updateUser['USR_FIRSTNAME'] = $user->givenName;
                $updateUser['USR_LASTNAME'] = $user->surname;
                $updateUser['USR_EMAIL'] = $user->mail;
                $updateUser['USR_DUE_DATE'] = $user->dueDate;
                $updateUser['USR_STATUS'] = 'ACTIVE';
                $updateUser['USR_CELLULAR'] = $user->mobilePhone;
                $updateUser['USR_POSITION'] = $user->jobTitle;
                $updateUser['USR_UPDATE_DATE'] = date("Y-m-d H:i:s");
                $var = $u->update($updateUser);
            }
            if ($var == 1) {
                if ($user->sync == 'false' || $user->sync == false) {
                    //New User
                    //Insert in Table Relational PS_AZUREAD_SYNCHRONIZED
                    $insertRelation = aadInsertTableAzureADSynchronized($user->pmUID, $user->id, 'USER');
                    if ($insertRelation != 1) {
                        $success = false;
                        $errorDetail = 'An error occurred when inserting in PS_AZUREAD_SYNCHRONIZED.';
                    }
                }
                $syncDetail[] = $user->pmUID;
            } else {
                $success = false;
                if ($user->sync == 'false' || $user->sync == false) {
                    //Error when Creating New User
                    $errorDetail = 'An error occurred while creating the user '.$user->displayName;
                } else {
                    //Error when Updating User in Process Maker
                    $errorDetail = 'An error occurred while updating the user '.$user->displayName;
                }
            }

            if (!$success) {
                $response = [
                    "success" => $success,
                    "data" => $errorDetail
                ];
                return json_encode($response);
            }
        }
        //Message Success
        $messageSuccess = '';
        if (count($data) == 1) {
            if ($user->sync == 'false'  || $user->sync == false) {
                //New User
                $messageSuccess = 'The user has been synced.';
            } else {
                //Update User
                $messageSuccess = 'The user has been updated';
            }
        } else {
            $messageSuccess = 'The users have been synced.';
        }
        $response = [
            "success" => true,
            "data" => $messageSuccess,
            "syncDetail" => $syncDetail
        ];
        return json_encode($response);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadGetAzureGroups
 * Get List of Azure Groups
 *
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadGetAzureGroups()
{
    try {
        //Get Config ID
        $confg = json_decode(aadGetConfiguration());
        //Generate New App Access Token
        $newToken = json_decode(aadGenerateAppAccessToken($confg->data->PAADC_ID));
        $appAccessToken = $newToken->data->access_token;
        //URL
        $url = 'https://graph.microsoft.com/v1.0/groups';
        //Body Request
        $bodyRequest = false;
        //Header
        $headers = 'Authorization: Bearer '.$appAccessToken;
        //Call API
        $res = aadCallApi('GET', $url, $bodyRequest, $headers);

        if (!isset($res->error)) {
            //Get Synchronized Azure Active Directory Groups in Process Maker
            $groupsSync = json_decode(aadGetAzureADSynchronized('GROUP'));

            $data = $res->value;
            foreach ($data as $k => $val) {
                $val->key = $k;
                $val->sync = false;
                $val->check = false;
                $val->checkable = true;

                if (count($groupsSync->data) > 0) {
                    foreach ($groupsSync->data as $key => $value) {
                        if ($value->AAD_ID == $val->id) {
                            $val->sync = $value->PM_ID; //Process Maker Group UID
                            $val->check = true;
                            $val->checkable = false;
                        }
                    }
                }
            }
            if ($groupsSync->success) {
                $response = [
                    "success" => true,
                    "data" => $res->value
                ];
                return json_encode($response);
            } else {
                $response = [
                    "success" => false,
                    "data" => 'Error connecting to PS_AZUREAD_SYNCHRONIZED'
                ];
                return json_encode($response);
            }
        } else {
            //Error Token Expired
            if ($res->error->code == 'InvalidAuthenticationToken') {
                //Get Config ID
                $confg = json_decode(aadGetConfiguration());
                //Generate New App Access Token
                $newToken = json_decode(aadGenerateAppAccessToken($confg->data->PAADC_ID));
                if ($newToken->success) {
                    //Recursive Get List of Azure Groups
                    $response = json_decode(aadGetAzureGroups());
                } else {
                    $response = [
                        "success" => false,
                        "data" => $newToken->error
                    ];
                    return json_encode($response);
                }
            } else {
                $response = [
                    "success" => false,
                    "data" => $res->error
                ];
                return json_encode($response);
            }
        }
        return json_encode($response);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadSyncGroups
 * Sync / Update Azure Groups to Process Maker
 *
 * @param $data (array) //Data of User(s)
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadSyncGroups($data)
{
    try {
        $nGroup = new Groupwf();
        //Get Config APP_ACCESS_TOKEN
        $confg = json_decode(aadGetConfiguration());
        foreach ($data as $group) {
            $group = (object) $group;
            $groupPMUID = $group->sync;
            //Variable of Status
            $success = true;
            $errorDetail = '';
            //Decide Insert or Update
            if ($group->sync == 'false' || $group->sync == false) {
                //Create Group in Process Maker
                $dataSend['GRP_TITLE'] = $group->displayName;
                $dataSend['GRP_STATUS'] = 'ACTIVE';
                //New Group UID
                $groupPMUID = $nGroup->create($dataSend);
                //Insert in Table Relational PS_AZUREAD_SYNCHRONIZED
                $insertRelation = aadInsertTableAzureADSynchronized($groupPMUID, $group->id, 'GROUP');
                if ($insertRelation == 0) {
                    $success = false;
                    $errorDetail = 'An error occurred when inserting in PS_AZUREAD_SYNCHRONIZED.';
                }
            } else {
                //Check if the Group needs to be updated
                if (PMFGetGroupName($groupPMUID, 'en') != $group->displayName) {
                    //Update Group in Process Maker
                    $dataSend['GRP_UID'] = $groupPMUID;
                    $dataSend['GRP_TITLE'] = $group->displayName;
                    $dataSend['GRP_STATUS'] = 'ACTIVE';
                    $statusUpdate = $nGroup->update($dataSend);
                    if ($statusUpdate == 0) {
                        $success = false;
                        $errorDetail = 'An error occurred while updating the Group.';
                    }
                }
            }

            if ($success) {
                //Obtain Members Azure of the Group
                $membersGroup = json_decode(aadGetAzureGroupMembers($confg->data->APP_ACCESS_TOKEN, $group->id));
                //Sync Members of the Group
                $synchronizedUsers = json_decode(aadSyncUsers($membersGroup->data));
                //Get current Users PM of the Group
                $actualUsers = PMFGetGroupUsers($groupPMUID);

                if ($synchronizedUsers->success) {
                    //Browse user list
                    foreach ($synchronizedUsers->syncDetail as $idUser) {
                        $newAssignment = true;
                        //Check if the user is already assigned to the group
                        foreach ($actualUsers as $us) {
                            if ($us['USR_UID'] == $idUser) {
                                $newAssignment = false;
                            }
                        }
                        if ($newAssignment) {
                            //Assignment to group
                            $varAs = PMFAssignUserToGroup($idUser, $groupPMUID);
                            if ($varAs == 0) {
                                $success = false;
                                $errorDetail = 'User [' . $idUser . '] was not assigned';
                            }
                        }
                    }
                } else {
                    $success = false;
                    $errorDetail = $synchronizedUsers->$errorDetail;
                }
            }
            if (!$success) {
                $response = [
                    "success" => $success,
                    "data" => $errorDetail
                ];
                return json_encode($response);
            }
        }
        //Message Success
        $messageSuccess = '';
        if (count($data) == 1) {
            if ($group->sync == 'false' || $group->sync == false) {
                //New group
                $messageSuccess = 'The group has been synced.';
            } else {
                //Update Group
                $messageSuccess = 'The group has been updated';
            }
        } else {
            $messageSuccess = 'The groups have been synced.';
        }
        $response = [
            "success" => $success,
            "data" => $messageSuccess
        ];
        return json_encode($response);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadGetAzureGroupMembers
 * Get List of Azure Group Members
 *
 * @param $appAccessToken (String) //Application Access Token
 * @param $groupId (String) //Azure Group ID
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadGetAzureGroupMembers($appAccessToken, $groupId)
{
    try {
        //URL
        $url = 'https://graph.microsoft.com/v1.0/groups/' . $groupId . '/members';
        //Body Request
        $bodyRequest = false;
        //Header
        $headers = 'Authorization: Bearer '.$appAccessToken;
        //Call API
        $res = aadCallApi('GET', $url, $bodyRequest, $headers);

        if (!isset($res->error)) {
            //Get Synchronized Azure Active Directory Users in Process Maker
            $usersSync = json_decode(aadGetAzureADSynchronized('USER'));

            $data = $res->value;
            foreach ($data as $k => $val) {
                $val->key = $k;
                $val->sync = false;
                if (count($usersSync->data) > 0) {
                    foreach ($usersSync->data as $key => $value) {
                        if ($value->AAD_ID == $val->id) {
                            $val->sync = $value->PM_ID; //Process Maker Group UID
                        }
                    }
                }
            }
            $response = [
                "success" => true,
                "data" => $res->value
            ];
        } else {
            //Error Token Expired
            if ($res->error->code == 'InvalidAuthenticationToken') {
                //Get Config ID
                $confg = json_decode(aadGetConfiguration());
                //Generate New App Access Token
                $newToken = json_decode(aadGenerateAppAccessToken($confg->data->PAADC_ID));
                if ($newToken->success) {
                    //Recursive Get List of Azure Groups
                    $response = json_decode(aadGetAzureGroupMembers($newToken->data->access_token, $groupId));
                } else {
                    $response = [
                        "success" => false,
                        "data" => $newToken->error
                    ];
                    return json_encode($response);
                }
            } else {
                $response = [
                    "success" => false,
                    "data" => $res->error
                ];
                return json_encode($response);
            }
        }
        return json_encode($response);
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

/**
 * aadVerifyGroup
 * Verify if the Group exist
 * @param $groupID (String) //Process Maker GroupUID
 * @return json object
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function aadVerifyGroup($groupID)
{
    try {
        $sqlSelect = "SELECT GRP_UID
                        FROM GROUPWF
                        WHERE GRP_UID = '" . $groupID . "'";
        $resVerify = aadPropelExecuteQuery($sqlSelect);
        $existGroup = !empty($resVerify) ? true : false;
        return $existGroup;
    } catch (Exception $e) {
        throw new Exception($e->getMessage(), 1);
    }
}

?>