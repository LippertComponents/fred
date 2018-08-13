<?php
/*
 * This file is part of the Fred package.
 *
 * Copyright (c) MODX, LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(dirname(__FILE__)) . '/index.class.php';

/**
 * @package fred
 * @subpackage controllers
 */
class FredHomeManagerController extends FredBaseManagerController
{
    public function process(array $scriptProperties = array())
    {
//        $this->migrate();
    }

    public function getPageTitle()
    {
        return $this->modx->lexicon('fred.menu.fred');
    }

    public function loadCustomCssJs()
    {
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/theme.window.js');
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/themes.grid.js');
        
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/blueprint_category.window.js');
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/blueprint_categories.grid.js');
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/blueprint.window.js');
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/blueprints.grid.js');
        
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/element_rte_config.window.js');
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/element_rte_configs.grid.js');
        
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/element_option_set.window.js');
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/element_option_sets.grid.js');
        
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/element_category.window.js');
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/element_categories.grid.js');
        
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/element.window.js');
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/widgets/elements.grid.js');
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/panel.js');
        $this->addJavascript($this->fred->getOption('jsUrl') . 'home/page.js');
        
        $this->addJavascript($this->fred->getOption('jsUrl') . 'utils/griddraganddrop.js');
        $this->addLastJavascript($this->fred->getOption('jsUrl') . 'utils/combos.js');

        $this->addHtml('
        <script type="text/javascript">
            Ext.onReady(function() {
                MODx.load({ xtype: "fred-page-home"});
            });
        </script>
        ');
    }

    public function getTemplateFile()
    {
        return $this->fred->getOption('templatesPath') . 'home.tpl';
    }

    private function migrate()
    {
        $modx = $this->modx;

        $modx->removeCollection('FredTheme', []);
        
        $theme = $modx->newObject('FredTheme');
        $theme->set('name', 'Default');
        $theme->set('description', 'Fred\'s Default Theme');
        $theme->save();
        
        $modx->updateCollection('FredElementCategory', ['theme' => $theme->id]);
        $modx->updateCollection('FredBlueprintCategory', ['theme' => $theme->id]);
        $modx->updateCollection('FredElementRTEConfig', ['theme' => $theme->id]);
        $modx->updateCollection('FredElementOptionSet', ['theme' => $theme->id]);
    }
}