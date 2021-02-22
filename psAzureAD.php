<?php

G::LoadClass("plugin");

if (!defined('PATH_PLUGIN_PS_AZUREAD')) {
    define('PATH_PLUGIN_PS_AZUREAD', PATH_CORE . 'plugins' . PATH_SEP . 'psAzureAD' . PATH_SEP);
}

class psAzureADPlugin extends PMPlugin
{
    public function psAzureADPlugin($sNamespace, $sFilename = null)
    {
        $res = parent::PMPlugin($sNamespace, $sFilename);
        $this->sFriendlyName = "AzureAD Plugin";
        $this->sDescription  = "Synchronization plugin with Azure Active Directory";
        $this->sPluginFolder = "psAzureAD";
        $this->sSetupPage    = "setup";
        $this->iVersion      = "1.01";
        $this->aWorkspaces   = null;
        return $res;
    }

    public function setup()
    {
        $this->registerMenu("setup", "azureADMenu.php");
        $this->registerPmFunction();
    }

    public function install()
    {
    }

    public function enable()
    {
        $sqlFile = PATH_PLUGIN_PS_AZUREAD . 'data' . PATH_SEP . 'db_azuread.sql';
        $handle = @fopen($sqlFile, "r"); // Open file form read.
        $line = '';
        if ($handle) {
            while (!feof($handle)) { // Loop til end of file.
                $buffer = fgets($handle, 4096); // Read a line.
                if ($buffer[0] != "#" && strlen(trim($buffer)) > 0) { // Check for valid lines
                    $line .= $buffer;
                    $buffer = trim($buffer);
                    if ($buffer[strlen($buffer) - 1] == ';') {
                        $con = Propel::getConnection('workflow');
                        $stmt = $con->createStatement();
                        $stmt->executeQuery($line, ResultSet::FETCHMODE_NUM);
                        $line = '';
                    }
                }
            }
            fclose($handle); // Close the file.
        }
        //Create permission in ProcessMaker
        $RBAC = RBAC::getSingleton();
        $RBAC->initRBAC();
        $RBAC->createPermision('PS_AZUREAD');
    }

    public function disable()
    {
    }
}

$oPluginRegistry = PMPluginRegistry::getSingleton();
$oPluginRegistry->registerPlugin("psAzureAD", __FILE__);
