<?php
/**
 * class.psAzureAD.php
 *  
 */

  class psAzureADClass extends PMPlugin {
    function __construct() {
      set_include_path(
        PATH_PLUGINS . 'psAzureAD' . PATH_SEPARATOR .
        get_include_path()
      );
    }

    function setup()
    {
    }

    function getFieldsForPageSetup()
    {
    }

    function updateFieldsForPageSetup()
    {
    }

  }
?>