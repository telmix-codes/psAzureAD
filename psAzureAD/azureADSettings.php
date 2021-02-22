<?php
global $G_TMP_MENU;
global $RBAC;

if ($RBAC->userCanAccess('PS_AZUREAD') == 1) {
    G::loadClass("pmFunctions");
    require_once PATH_PLUGIN_PS_AZUREAD . 'classes/class.PublishSmarty.php';
    require_once PATH_PLUGIN_PS_AZUREAD . 'classes/azureADFunctions.php';

    $config["skin"] = $_SESSION["currentSkin"];

    //Select all Configuration Variables
    $aConfigData = json_decode(aadGetConfiguration(), true);

    $publish = new PublishSmarty();
    $publish->addVarJs("config", $config);
    $publish->addVarJs("aConfigData", $aConfigData);
    $publish->render('azureADSettings');
} else {
    echo "<h2 style='text-align: center'>You do not have permissions for this page, please contact your administrator for further instrucctions.</h2>";
}
?>