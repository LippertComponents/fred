<?php
/*
 * This file is part of the Fred package.
 *
 * Copyright (c) MODX, LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$corePath = $modx->getOption('fred.core_path', null, $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/fred/');
/** @var Fred $fred */
$fred = $modx->getService(
    'fred',
    'Fred',
    $corePath . 'model/fred/',
    array(
        'core_path' => $corePath
    )
);

switch ($modx->event->name) {
    case 'OnDocFormPrerender':
        if(!empty($resource) && !empty($fred->getTheme($resource->template))) {
            //Disable ContentBlocks
            $isContentBlocks = $resource->getProperty('_isContentBlocks', 'contentblocks', null);
            if($isContentBlocks !== false){
                $resource->setProperty('_isContentBlocks', false, 'contentblocks');
                $resource->save();
            }

            $data = $resource->getProperty('data', 'fred');
            $fingerprint = !empty($data['fingerprint']) ? $data['fingerprint'] : '';

            //Load Open in Fred button
            $modx->lexicon->load('fred:default');
            $modx->controller->addLexiconTopic('fred:default');

            $preview_url = 'panel.config.preview_url';
            if ($modx->getOption('fred.use_custom_editor_url')) {
                $preview_url = "'".rtrim($modx->makeUrl($modx->getOption('site_start')), '/')."/fred-editor-".$resource->get('id')."'";
            }

            $modx->controller->addHtml("
        <script>
            Ext.ComponentMgr.onAvailable('modx-resource-content', function(right) {
                right.on('beforerender', function() {
                    var content = Ext.getCmp('ta'),
                    contentvalue = content.getValue(),
                    panel = Ext.getCmp('modx-page-update-resource'); 
                    
                    content.destroy();

                    right.insert(0,{
                        xtype: 'button' 
                        ,fieldLabel: _('fred.open_in_fred')
                        ,hideLabel: true
                        ,cls: 'primary-button'
                        ,style: {padding: '10px 15px'}
                        ,html: _('fred.open_in_fred')
                        ,handler: function(){
                            window.open({$preview_url})
                        }
                    });
                     
                    right.insert(1,{
                        xtype: 'textarea' 
                        ,hideLabel: true
                        ,anchor: '100%'
                        ,grow: true
                        ,style: {marginTop:'15px'}
                        ,disabled: true
                        ,value: contentvalue
                    });
                });
                
                right.on('afterrender', function() {
                    var panel = Ext.getCmp('modx-panel-resource');
                    
                    panel.on('success', function(){
                        location.reload();
                    });
                    
                    var fingerprint = document.createElement('input');
                    fingerprint.setAttribute('type', 'hidden');
                    fingerprint.setAttribute('name', 'fingerprint');
                    fingerprint.setAttribute('value', '" . $fingerprint . "');
                    panel.form.el.dom.appendChild(fingerprint);
                });
            });
            
        </script>");
        }
        break;
    case 'OnWebPagePrerender':
        $theme = $fred->getTheme($modx->resource->template);
        if (!empty($theme)) {

            if (!$modx->user) return;
            if (!($modx->user->hasSessionContext('mgr') || $modx->user->hasSessionContext($modx->resource->context_key))) return;
            if (!$modx->hasPermission('fred')) return;

            $modx->lexicon->load('fred:fe');

            $fredMode = 1;

            if (isset($_SESSION['fred'])) {
                $fredMode = intval($_SESSION['fred']);
            }

            if (isset($_GET['fred'])) {
                $fredMode = intval($_GET['fred']);
            }

            if ($fredMode === 4) {
                $fredMode = 0;
                $_SESSION['fred'] = 0;
            }

            if ($fredMode === 1) {
                $_SESSION['fred'] = 1;
            }

            $get = $_GET;
            unset($get[$modx->getOption('request_param_alias', [], 'q')]);

            if ($fredMode === 0) {
                $button = "<a href=\"" . str_replace('&amp;', '&', $modx->makeUrl($modx->resource->id, '', array_merge($get, ['fred' => 1]) , 'abs')) . "\" title=\"" . $modx->lexicon('fred.fe.turn_on_fred') . "\" role=\"button\" style=\"display: block; text-decoration: none; background-color: #4D4D4D; background-image: url(&quot;data:image/svg+xml, %3Csvg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='-14.584 -8.583 48 48'%3E%3ClinearGradient id='a' gradientUnits='userSpaceOnUse' x1='2164.318' y1='-2519.043' x2='2047.448' y2='-2442.941' gradientTransform='matrix(.1471 0 0 -.1471 -290.574 -365.794)'%3E%3Cstop offset='0' stop-color='%2380c3e6'/%3E%3Cstop offset='1' stop-color='%233380c2'/%3E%3C/linearGradient%3E%3Cpath fill='url(%23a)' d='M22.035 11.468l9.602-15.406H8.488L5.236 1.17z'/%3E%3Cpath opacity='.15' d='M5.236 1.17l1.702-2.633 15.097 12.931z'/%3E%3ClinearGradient id='b' gradientUnits='userSpaceOnUse' x1='2184.769' y1='-2694.977' x2='2097.394' y2='-2637.275' gradientTransform='matrix(.1471 0 0 -.1471 -290.574 -365.794)'%3E%3Cstop offset='0' stop-color='%23f38649'/%3E%3Cstop offset='.185' stop-color='%23f28147'/%3E%3Cstop offset='.409' stop-color='%23ef7242'/%3E%3Cstop offset='.654' stop-color='%23ea5a3a'/%3E%3Cstop offset='.911' stop-color='%23e4382e'/%3E%3Cstop offset='1' stop-color='%23e12a29'/%3E%3C/linearGradient%3E%3Cpath fill='url(%23b)' d='M27.068 39.416V16.268l-4.957-3.176L11.583 29.74z'/%3E%3Cpath opacity='.15' d='M11.583 29.74l2.632 1.625 7.896-18.273z'/%3E%3ClinearGradient id='c' gradientUnits='userSpaceOnUse' x1='1896.918' y1='-2571.592' x2='2064.08' y2='-2467.137' gradientTransform='matrix(.1471 0 0 -.1471 -290.574 -365.794)'%3E%3Cstop offset='0' stop-color='%2342ab4a'/%3E%3Cstop offset='1' stop-color='%23add155'/%3E%3C/linearGradient%3E%3Cpath fill='url(%23c)' d='M-10.25-8.583v23.148l5.419 3.175 26.866-6.272z'/%3E%3ClinearGradient id='d' gradientUnits='userSpaceOnUse' x1='1933.576' y1='-2758.292' x2='2069.413' y2='-2540.907' gradientTransform='matrix(.1471 0 0 -.1471 -290.574 -365.794)'%3E%3Cstop offset='0' stop-color='%2342ab4a'/%3E%3Cstop offset='1' stop-color='%23add155'/%3E%3C/linearGradient%3E%3Cpath fill='url(%23d)' d='M-4.985 19.52l-9.599 15.093H8.565l13.546-21.521z'/%3E%3C/svg%3E&quot;); height: 48px; width: 48px; border-radius: 50%; outline: 0; border: none; background-repeat: no-repeat; background-size: 60%; background-position: center; box-shadow: rgba(18,55,100,0.34) 0 4px 8px; margin: 0; padding: 0; text-indent: -9999px; cursor: pointer; position: fixed; z-index: 10010; margin: 32px; bottom: 0; left: 0; opacity: 0.8; transition: .3s; box-sizing: border-box;\" onmouseover=\"this.style.transform='scale(1.05)';\" onmouseout=\"this.style.transform='initial'\";>Fred</a>";

                $modx->resource->_output = preg_replace('/(<\/body>(?:<\/body>)?)/i', "{$button}\r\n$1", $modx->resource->_output);
                return;
            }

            if ($fredMode === 3) {
                $modx->resource->_output = '';
                return;
            }

            $html = Wa72\HtmlPageDom\HtmlPageCrawler::create($modx->resource->_output);
            $dzs = $html->filter('[data-fred-dropzone]');

            $dzs->each(function(Wa72\HtmlPageDom\HtmlPageCrawler $node, $i)  {
                $node->setInnerHtml('');
            });

            $modx->resource->_output = $html->saveHTML();

            if ($fredMode === 2) {
                return;
            }

            $htmlTag = $html->filter('html');
            $fredActiveClass = $modx->getOption('fred.active_class');

            if (!empty($fredActiveClass)) {
                $htmlTag->addClass($fredActiveClass);
            }

            $scripts = $html->filter('script');
            $scripts->each(function(Wa72\HtmlPageDom\HtmlPageCrawler $node, $i)  {
                $newNode = Wa72\HtmlPageDom\HtmlPageCrawler::create('<script-fred></script-fred>');

                $attrs = $node->getNode(0)->attributes;

                foreach ($attrs as $attr) {
                    $newNode->attr($attr->nodeName, $attr->nodeValue);
                }

                $newNode->setAttribute('data-fred-script', $node->getInnerHtml());

                $node->replaceWith($newNode);

            });

            $modx->resource->_output = $html->saveHTML();

            $beforeRenderResults = $modx->invokeEvent('FredBeforeRender');
            $includes = '';
            $beforeRender = '';
            $modifyPermissions = '';
            $lexicons = [];
            foreach ($beforeRenderResults as $result) {

                if ($result['includes']) {
                    $includes .= $result['includes'];
                }

                if ($result['beforeRender']) {
                    $beforeRender .= $result['beforeRender'];
                }

                if ($result['modifyPermissions']) {
                    $modifyPermissions .= $result['modifyPermissions'];
                }

                if ($result['lexicons'] && is_array($result['lexicons'])) {
                    $lexicons = array_merge($lexicons, $result['lexicons']);
                }
            }

            /** @var FredElementRTEConfig[] $rteConfigs */
            $rteConfigs = $modx->getIterator('FredElementRTEConfig');
            $rteConfigString = [];

            foreach ($rteConfigs as $rteConfig) {
                if (empty($rteConfig->get('data'))) continue;

                $rteConfigString[] = $rteConfig->name . ':' . $rteConfig->data;
            }

            $rteConfigString = implode(',', $rteConfigString);

            $payload = [
                'iss' => $modx->user->id,
                'resource' => $modx->resource->id,
                'context' => $modx->resource->context_key,
                'queryParams' => $_GET,
                'postParams' => $_POST,
                'cookie' => $_COOKIE,
                'requestParams' => $_REQUEST
            ];

            $jwt = \Firebase\JWT\JWT::encode($payload, $fred->getSecret());

            $versionHash = substr(md5(Fred::VERSION), 0, 6);

            $fredContent = '
        <script type="text/javascript" src="' . $fred->getOption('webAssetsUrl') . 'fred.min.js?v=' . $versionHash . '"></script>
        <link rel="stylesheet" href="' . $fred->getOption('webAssetsUrl') . 'fred.css?v=' . $versionHash . '" type="text/css" />
        ' . $includes . '
        <script>
            var fred = new Fred({
                theme: ' . $theme->id . ',
                themeDir: "' . $theme->getThemeFolderUri() . '",
                themeNamespace: "'. $theme->get('namespace') .'",
                assetsUrl: "' . $fred->getOption('webAssetsUrl') . '",
                managerUrl: "' . MODX_MANAGER_URL . '",
                fredOffUrl: "' . str_replace('&amp;', '&', $modx->makeUrl($modx->resource->id, '', array_merge($get, ['fred' => 4]) , 'abs')) . '",
                contextKey: "' . $modx->resource->context_key. '",
                launcherPosition: "' . $fred->getOption('launcher_position') . '",
                iconEditor: "' . $fred->getOption('icon_editor') . '",
                imageEditor: "' . $fred->getOption('image_editor') . '",
                rte: "' . $fred->getOption('rte') . '",
                rteConfig: {' . $rteConfigString . '},
                jwt: "' . $jwt . '",
                resource: {
                    "id": ' . $modx->resource->id . ',
                    "parent": ' . $modx->resource->parent . ',
                    "previewUrl": "' . str_replace('&amp;', '&', $modx->makeUrl($modx->resource->id, '', array_merge($get, ['fred' => 2]) , 'abs')) . '",
                    "emptyUrl": "' . str_replace('&amp;', '&', $modx->makeUrl($modx->resource->id, '', array_merge($get, ['fred' => 3]) , 'abs')) . '",
                    "save": ' . (int)$modx->resource->checkPolicy('save') . ',
                    "delete": ' . (int)$modx->resource->checkPolicy('delete') . ',
                    "undelete": ' . (int)$modx->resource->checkPolicy('undelete') . ',
                    "publish": ' . (int)$modx->resource->checkPolicy('publish') . ',
                    "unpublish": ' . (int)$modx->resource->checkPolicy('unpublish') . ',
                },
                permission: {
                    "fred_settings": ' . (int)$modx->hasPermission('fred_settings') . ',
                    "fred_settings_advanced": ' . (int)$modx->hasPermission('fred_settings_advanced') . ',
                    "fred_settings_tags": ' . (int)$modx->hasPermission('fred_settings_tags') . ',
                    "fred_settings_tvs": ' . (int)$modx->hasPermission('fred_settings_tvs') . ',
                    "fred_elements": ' . (int)$modx->hasPermission('fred_elements') . ',
                    "fred_element_cache_refresh": ' . (int)$modx->hasPermission('fred_element_cache_refresh') . ',
                    "fred_blueprints": ' . (int)$modx->hasPermission('fred_blueprints') . ',
                    "fred_element_screenshot": ' . (int)$modx->hasPermission('fred_element_screenshot') . ',
                    "fred_element_move": ' . (int)$modx->hasPermission('fred_element_move') . ',
                    "fred_element_front_end_delete": ' . (int)$modx->hasPermission('fred_element_front_end_delete') . ',
                    "fred_blueprint_categories_save": ' . (int)$modx->hasPermission('fred_blueprint_categories_save') . ',
                    "fred_blueprint_categories_create_public": ' . (int)$modx->hasPermission('fred_blueprint_categories_create_public') . ',
                    "fred_blueprints_save": ' . (int)$modx->hasPermission('fred_blueprints_save') . ',
                    "fred_blueprints_create_public": ' . (int)$modx->hasPermission('fred_blueprints_create_public') . ',
                
                    "save_document": ' . (int)$modx->hasPermission('save_document') . ',
                    "delete_document" : ' . (int)$modx->hasPermission('delete_document') . ',
                    "undelete_document" : ' . (int)$modx->hasPermission('undelete_document') . ',
                    "publish_document" : ' . (int)$modx->hasPermission('publish_document') . ',
                    "unpublish_document" : ' . (int)$modx->hasPermission('unpublish_document') . ',
                    "new_document" : ' . (int)$modx->hasPermission('new_document') . ',
                    "resource_duplicate" : ' . (int)$modx->hasPermission('resource_duplicate') . ',
                    "new_document_in_root" : ' . (int)$modx->hasPermission('new_document_in_root') . '
                },
                lexicons: ' . json_encode($lexicons) . ',
                beforeRender: function() {
                    ' . $beforeRender . '
                },
                modifyPermissions: function(permissions) {
                    ' . $modifyPermissions . '
                    
                    return permissions;
                }
            });
        </script>';

            $modx->resource->cacheable = 0;
            $modx->resource->_output = preg_replace('/(<\/head>(?:<\/head>)?)/i', "{$fredContent}\r\n$1", $modx->resource->_output);
        }
        break;
    case 'OnBeforeDocFormSave':
        if ($mode !== 'upd') return;

        if (empty($fred->getTheme($resource->template))) return;

        $data = $resource->getProperty('data', 'fred');
        if (!empty($data['fingerprint'])) {
            if (empty($resource->fingerprint)) {
                $modx->event->_output = 'No fingerprint was provided.';
                return;
            }

            if ($data['fingerprint'] !== $resource->fingerprint) {
                $modx->event->_output = 'Your page is outdated, please reload the page.';
                return;
            }
        }

        $beforeSave = $modx->invokeEvent('FredOnBeforeFredResourceSave', [
            'id' => $resource->get('id'),
            'resource' => &$resource
        ]);

        if (is_array($beforeSave)) {
            $preventSave = false;

            foreach ($beforeSave as $msg) {
                if (!empty($msg)) {
                    $preventSave .= $msg . " ";
                }
            }
        } else {
            $preventSave = $beforeSave;
        }

        if ($preventSave !== false) {
            $modx->event->_output = $preventSave;
            return;
        }

        break;
    case 'OnDocFormSave':
        if ($mode !== 'upd') return;

        if (empty($fred->getTheme($resource->template))) return;

        $renderResource = new \Fred\RenderResource($resource, $modx);
        $renderResource->render();

        $modx->invokeEvent('FredOnFredResourceSave', array(
            'id' => $resource->get('id'),
            'resource' => &$resource
        ));

        break;

    case 'OnPageNotFound':
        /** This is only fired when a user clicks on the Mgr button Open in Fred and the system setting fred.use_custom_editor_url is set to Yes/1 */
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $baseUrl = $modx->getOption('base_url', null, MODX_BASE_URL);
        if(!empty($baseUrl) && $baseUrl != '/' && $baseUrl != ' ') {
            $path = str_replace($baseUrl,'', $path);
        }
        $path = ltrim($path,'/');

        if (!empty($path) && $modx->getOption('fred.use_custom_editor_url')) {
            // Fred URLs look like: fred-editor-123
            $parts = explode('-', $path);
            if (count($parts) == 3 && $parts[0] == 'fred' && $parts[1] == 'editor') {
                $modx->sendForward($parts[2]);
            }
        }

        break;
    case 'OnTemplateRemove':
        /** @var modTemplate $template */
        $templateId = $template->id;
        if (!empty($templateId)) {
            /** @var FredThemedTemplate $themedTemplate */
            $themedTemplate = $modx->getObject('FredThemedTemplate', ['template' => $templateId]);
            if ($themedTemplate) {
                $themedTemplate->remove();
            }
        }

        break;
    case 'OnTVInputRenderList':
        $modx->event->output($corePath . 'elements/tvs/input/');
        break;
    case 'OnTVInputPropertiesList':
        $modx->event->output($corePath . 'elements/tvs/input/options/');
        break;
    case 'OnManagerPageBeforeRender':
        $modx->controller->addLexiconTopic('fred:default');
        break;
    case 'OnManagerLogin':
    case 'OnWebLogin':
        if (!$modx->user) return;
        if (!($modx->user->hasSessionContext('mgr') || $modx->user->hasSessionContext($modx->resource->context_key))) return;
        if (!$modx->hasPermission('fred')) return;

        $fredMode = $modx->getOption('fred.default_enabled', $scriptProperties, 1);

        if (isset($_SESSION['fred'])) {
            $fredMode = intval($_SESSION['fred']);
        }

        $_SESSION['fred'] = $fredMode;

        break;
}

return;
