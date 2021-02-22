<?php

class PublishSmarty
{
    public $smarty;
    public $varsJs = [];

    public function __construct()
    {
        $this->smarty = new Smarty();
        $this->smarty->compile_dir = PATH_SMARTY_C;
        $this->smarty->cache_dir = PATH_SMARTY_CACHE;
        $this->smarty->config_dir = PATH_THIRDPARTY . 'smarty/configs';

        $this->smarty->caching = false;
        $this->smarty->force_compile = true;
    }

    public function addVarJs($nameVar, $valueVar)
    {
        $this->varsJs[$nameVar] = $valueVar;
    }

    private function printVarsJs()
    {
        if (is_array($this->varsJs) && !empty($this->varsJs)) {
            $varJs = '';
            foreach ($this->varsJs as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $varJs .= 'var ' . $key . ' = ' . json_encode($value) . '; ';
                } else {
                    $varJs .= 'var ' . $key . ' = "' . $value . '"; ';
                }
            }

            if ($varJs != '') {
                print '<script type="text/javascript">' . $varJs . '</script>' . "\n";
            }
        }
    }

    public function render($page)
    {
        $this->printVarsJs();

        $tplPage = $page . '.html';
        $jsPage = $page . '.js';

        if (file_exists(PATH_PLUGIN_PS_AZUREAD . 'templates' . PATH_SEP . $tplPage)) {
            $this->smarty->display(PATH_PLUGIN_PS_AZUREAD . 'templates' . PATH_SEP . $tplPage);
        } else {
            die("The template file no exist : " . $tplPage);
        }

        if (file_exists(PATH_PLUGIN_PS_AZUREAD . 'public_html' . PATH_SEP . 'js' . PATH_SEP . $jsPage)) {
            $random = rand(1, 10000000);
            print "\n\n" . '<script type="text/javascript" src="/plugin/psAzureAD/js/' . $jsPage . '?random=' . $random . '"></script>';
        }
    }
}
?>