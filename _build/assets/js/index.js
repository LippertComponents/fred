import emitter from './EE';
import Sidebar from './Sidebar/Sidebar';
import SidebarPlugin from './Components/SidebarPlugin';
import Launcher from './Launcher';
import drake from './Drake';
import Editor from './Editors/Editor';
import fredConfig from './Config';
import { div, section, a, iFrame } from './UI/Elements'
import Mousetrap from 'mousetrap';
import MousetrapGlobalBind from 'mousetrap/plugins/global-bind/mousetrap-global-bind.min'
import {loadElements, pluginTools, valueParser} from "./Utils";
import utilitySidebar from './Components/UtilitySidebar';
import { getPreview, saveContent, fetchContent, fetchLexicons } from './Actions/fred';
import ContentElement from "./Components/Sidebar/Elements/ContentElement";
import ToolbarPlugin from "./Components/Sidebar/Elements/Toolbar/ToolbarPlugin";

export default class Fred {
    constructor(config = {}) {
        fredConfig.jwt = config.jwt;
        delete config.jwt;

        if (typeof config.modifyPermissions === 'function') {
            config.modifyPermissions = config.modifyPermissions.bind(this);
            config.permission = config.modifyPermissions(config.permission);
        }

        fredConfig.permission = config.permission;
        delete config.permission;

        fredConfig.resource = config.resource;
        delete config.resource;

        fredConfig.config = config || {};
        fredConfig.fred = this;
        this.loading = null;
        this.wrapper = null;
        this.fingerprint = '';

        this.previewDocument = null;
        this.replaceScript = this.replaceScript.bind(this);
        this.scriptsToReplace = [];

        this.unsavedChanges = false;

        window.onbeforeunload = () => {
            if (this.unsavedChanges === true) {
                return fredConfig.lng('fred.fe.unsaved_data_warning');
            } else {
                return;
            }
        };

        const lexiconsLoaded = this.loadLexicons();

        document.addEventListener("DOMContentLoaded", () => {
            const scripts = document.body.querySelectorAll('script-fred');
            for (let script of scripts) {
                const newScript = document.createElement('script');

                for (let i = 0; i < script.attributes.length; i++) {
                    newScript.setAttribute(script.attributes[i].name, script.attributes[i].value);
                }

                if (script.dataset.fredScript) {
                    newScript.innerHTML = script.dataset.fredScript;
                }

                newScript.removeAttribute('data-fred-script');

                this.scriptsToReplace.push({old: script, 'new': newScript});
            }

            lexiconsLoaded.then(() => {
                this.init();
            });
        });
    }

    render() {
        this.wrapper = div(['fred']);

        document.body.appendChild(this.wrapper);
    }

    renderPreview() {
        const previewWrapper = div(['fred--content-preview']);
        previewWrapper.style.display = 'none';

        this.iframe = iFrame('#');
        this.iframe.style.width = '100%';
        this.iframe.style.height = '100%';

        this.controls = div(['fred--content-preview_controls']);

        this.devices = div(['fred--devices']);

        this.phoneP = a(`<span>${fredConfig.lng('fred.fe.preview.phone_portrait')}</span>`, fredConfig.lng('fred.fe.preview.phone_portrait'), '', ['fred--smartphone-portrait'], () => {
            this.iframe.style.width = '320px';
            this.iframe.style.height = '480px';
        });

        this.devices.appendChild(this.phoneP);

        this.phoneL = a(`<span>${fredConfig.lng('fred.fe.preview.phone_landscape')}</span>`, fredConfig.lng('fred.fe.preview.phone_landscape'), '', ['fred--smartphone-landscape'], () => {
            this.iframe.style.width = '480px';
            this.iframe.style.height = '320px';
        });

        this.devices.appendChild(this.phoneL);

        this.tabletP = a(`<span>${fredConfig.lng('fred.fe.preview.tablet_portrait')}</span>`, fredConfig.lng('fred.fe.preview.tablet_portrait'), '', ['fred--tablet-portrait'], () => {
            this.iframe.style.width = '768px';
            this.iframe.style.height = '1024px';
        });

        this.devices.appendChild(this.tabletP);

        this.tabletL = a(`<span>${fredConfig.lng('fred.fe.preview.tablet_landscape')}</span>`, fredConfig.lng('fred.fe.preview.tablet_landscape'), '', ['fred--tablet-landscape'], () => {
            this.iframe.style.width = '1024px';
            this.iframe.style.height = '768px';
        });

        this.devices.appendChild(this.tabletL);

        this.auto = a(`<span>${fredConfig.lng('fred.fe.preview.auto')}</span>`, fredConfig.lng('fred.fe.preview.auto'), '', ['fred--auto'], () => {
            this.iframe.style.width = '100%';
            this.iframe.style.height = '100%';
        });

        this.devices.appendChild(this.auto);

        this.controls.appendChild(this.devices);


        previewWrapper.append(this.controls);

        previewWrapper.appendChild(this.iframe);

        this.wrapper.insertBefore(previewWrapper, this.wrapper.firstChild);
    }

    previewContent() {
        if (!this.previewDocument) {
            this.renderPreview();
            this.iframe.src = fredConfig.resource.emptyUrl;

            return getPreview().then(text => {
                const parser = new DOMParser();
                this.previewDocument = parser.parseFromString(text, 'text/html');
                return this.getPreviewContent();
            });
        } else {
            return this.getPreviewContent();
        }
    }

    getPreviewContent() {
        const promises = [];

        for (let i = 0; i < this.dropzones.length; i++) {
            promises.push(this.getCleanDropZoneContent(this.dropzones[i], true, false, true).then(content => {
                const dz = this.previewDocument.querySelector('[data-fred-dropzone="' + this.dropzones[i].dataset.fredDropzone + '"]');
                if (dz) {
                    dz.innerHTML = content;
                }
            }));
        }

        let base = this.previewDocument.querySelector('base');
        if (base) {
            base.setAttribute('target', '_blank');
        } else {
            base = document.createElement('base');
            base.setAttribute('target', '_blank');
            const head = this.previewDocument.querySelector('head');
            head.appendChild(base);
        }

        return Promise.all(promises).then(() => {
            this.iframe.contentWindow.document.open();
            this.iframe.contentWindow.document.write(this.previewDocument.documentElement.innerHTML);
            this.iframe.contentWindow.document.close();

            return new Promise(resolve => {
                this.iframe.onload = resolve;
            }).then(() => {
                return this.iframe;
            });
        });
    }

    renderComponents() {
        fredConfig.launcher = new Launcher((fredConfig.config.launcherPosition || 'bottom_left'));
        fredConfig.sidebar = new Sidebar(this.wrapper);
        utilitySidebar.render();
    }

    getDataFromDropZone(dropZone) {
        const data = [];

        for (let child of dropZone.children) {
            data.push(child.fredEl.getContent());
        }

        return data;
    }

    getCleanDropZoneContent(dropZone, parseModx = false, handleLinks = true, isPreview = false) {
        let cleanedContent = '';

        const promises = [];
        for (let child of dropZone.children) {
            promises.push(child.fredEl.cleanRender(parseModx, handleLinks, isPreview));
        }

        return Promise.all(promises).then(values => {
            values.forEach(el => {
                cleanedContent += el.innerHTML;
            });

            return cleanedContent;
        });
    }

    save() {
        if(!fredConfig.permission.save_document){
            return;
        }

        emitter.emit('fred-loading', fredConfig.lng('fred.fe.saving_page'));
        const body = {};
        const data = {};

        const promises = [];

        for (let i = 0; i < this.dropzones.length; i++) {
            data[this.dropzones[i].dataset.fredDropzone] = this.getDataFromDropZone(this.dropzones[i]);

            const targets = this.dropzones[i].querySelectorAll('[data-fred-target]:not([data-fred-target=""])');
            for (let target of targets) {
                if (fredConfig.pageSettings.hasOwnProperty(target.dataset.fredTarget)) {
                    fredConfig.pageSettings[target.dataset.fredTarget] = ContentElement.getElValue(target);
                    continue;
                }

                if ((target.dataset.fredTarget.indexOf('tv_') === 0) && (target.dataset.fredTarget.substr(3) !== '')) {
                    fredConfig.pageSettings.tvs[target.dataset.fredTarget.substr(3)] = ContentElement.getElValue(target);
                    continue;
                }

                body[target.dataset.fredTarget] = ContentElement.getElValue(target);
            }
            promises.push(this.getCleanDropZoneContent(this.dropzones[i]).then(content => {
                body[this.dropzones[i].dataset.fredDropzone] = content;
            }))
        }

        body.id = fredConfig.resource.id;
        body.data = data;
        body.plugins = fredConfig.pluginsData;
        body.pageSettings = JSON.parse(JSON.stringify(fredConfig.pageSettings));
        body.fingerprint = this.fingerprint;

        if (body.pageSettings.tvs) {
            for (let tvName in body.pageSettings.tvs) {
                if (body.pageSettings.tvs.hasOwnProperty(tvName)) {
                    body.pageSettings.tvs[tvName] = valueParser(body.pageSettings.tvs[tvName], true);
                }
            }
        }

        Promise.all(promises).then(() => {
            saveContent(body)
            .then(json => {
                this.unsavedChanges = false;

                if (json.url) {
                    location.href = json.url;
                }

                if (json.fingerprint) {
                    this.fingerprint = json.fingerprint;
                }

                fredConfig.pageSettings.publishedon = json.publishedon;

                emitter.emit('fred-loading-hide');
                emitter.emit('fred-after-save');
            })
            .catch(err => {
                if (err.response) {
                    console.error(err.response.message);
                    alert(err.response.message);
                }

                emitter.emit('fred-loading-hide');
            });
        });
    }

    loadContent() {
        emitter.emit('fred-loading', fredConfig.lng('fred.fe.preparing_content'));

        const dropZones = document.querySelectorAll('[data-fred-dropzone]:not([data-fred-dropzone=""]');
        for (let dz of dropZones) {
            const minHeight = dz.dataset.fredMinHeight;
            if (minHeight) {
                dz.style.minHeight = minHeight;
            }

            const minWidth = dz.dataset.fredMinWidth;
            if (minWidth) {
                dz.style.minWidth = minWidth;
            }
        }

        return fetchContent().then(json => {
            if (json.data.pageSettings.tagger && Array.isArray(json.data.pageSettings.tagger)) json.data.pageSettings.tagger = {};

            this.fingerprint = json.data.fingerprint || '';
            fredConfig.pageSettings = json.data.pageSettings || {};
            fredConfig.tagger = json.data.tagger || [];
            fredConfig.tvs = json.data.tvs || [];
            fredConfig.pluginsData = json.data.plugins || {};

            this.renderComponents();

            return loadElements(json.data).then(() => {
                drake.reloadContainers();

                if (document.querySelectorAll('.fred--block-invalid').length > 0) {
                    fredConfig.invalidElements = true;

                    this.invalidElementsWarning = div(['fred--alert-invalid'], 'fred.fe.invalid_elements_warning');
                    this.wrapper.appendChild(this.invalidElementsWarning);
                }

                emitter.emit('fred-loading-hide');
            });
        });
    }

    registerListeners() {
        emitter.on('fred-save', () => {
            this.save();
        });

        emitter.on('fred-wrapper-insert', el => {
            this.wrapper.appendChild(el);
        });

        emitter.on('fred-loading', text => {
            if (this.loading !== null) return;

            text = text || '';

            this.loading = section(['fred--modal-bg', 'fred--modal_loading']);
            this.loading.innerHTML = `<div class="fred--modal" aria-hidden="false"><div style="color:white;text-align:center;"><span class="fred--loading"></span> ${text}</div></div>`;

            this.wrapper.appendChild(this.loading);
        });

        emitter.on('fred-loading-hide', () => {
            if (this.loading !== null) {
                this.loading.remove();
                this.loading = null;
            }
        });

        emitter.on('fred-page-setting-change', (settingName, settingValue, parsedValue, sourceEl) => {
            this.dropzones.forEach(dz => {
                const targets = dz.querySelectorAll(`[data-fred-target="${settingName}"]`);
                for (let target of targets) {
                    if (target !== sourceEl) {
                        target.fredEl.setElValue(target, settingValue, '_value', '_raw', null, false, true);
                    }
                }
            });
        });

        emitter.on('fred-preview-on', () => {
            this.previewContent().then(iframe => {
                document.body.classList.add('fred--fixed');
                iframe.parentNode.style.opacity = null;
                iframe.parentNode.style.zIndex = null;
                iframe.parentNode.style.display = 'block';
            });
        });

        emitter.on('fred-preview-off', () => {
            document.body.classList.remove('fred--fixed');
            this.iframe.parentNode.style.opacity = null;
            this.iframe.parentNode.style.zIndex = null;
            this.iframe.parentNode.style.display = 'none';
        });

        emitter.on('fred-logout-user', () => {
            this.logoutUser();
        });

        emitter.on('fred-clear-invalid-elements-warning', () => {
            if (document.querySelectorAll('.fred--block-invalid').length === 0) {
                fredConfig.invalidElements = false;
                if (this.invalidElementsWarning) {
                    this.invalidElementsWarning.remove();
                }
            }
        });

        emitter.on('fred-content-changed', () => {
            this.unsavedChanges = true;
        })
    }

    registerKeyboardShortcuts() {
        Mousetrap.bindGlobal('mod+s', e => {
            if (e.preventDefault) {
                e.preventDefault();
            } else {
                e.returnValue = false;
            }

            emitter.emit('fred-save');
        });

        Mousetrap.bind('up up down down left right left right b a enter', () => {
            (function(){function c(){var e=document.createElement("link");e.setAttribute("type","text/css");e.setAttribute("rel","stylesheet");e.setAttribute("href",f);e.setAttribute("class",l);document.body.appendChild(e)}function h(){var e=document.getElementsByClassName(l);for(var t=0;t<e.length;t++){document.body.removeChild(e[t])}}function p(){var e=document.createElement("div");e.setAttribute("class",a);document.body.appendChild(e);setTimeout(function(){document.body.removeChild(e)},100)}function d(e){return{height:e.offsetHeight,width:e.offsetWidth}}function v(i){var s=d(i);return s.height>e&&s.height<n&&s.width>t&&s.width<r}function m(e){var t=e;var n=0;while(!!t){n+=t.offsetTop;t=t.offsetParent}return n}function g(){var e=document.documentElement;if(!!window.innerWidth){return window.innerHeight}else if(e&&!isNaN(e.clientHeight)){return e.clientHeight}return 0}function y(){if(window.pageYOffset){return window.pageYOffset}return Math.max(document.documentElement.scrollTop,document.body.scrollTop)}function E(e){var t=m(e);return t>=w&&t<=b+w}function S(){var e=document.createElement("audio");e.setAttribute("class",l);e.src=i;e.loop=false;e.addEventListener("canplay",function(){setTimeout(function(){x(k)},500);setTimeout(function(){N();p();for(var e=0;e<O.length;e++){T(O[e])}},15500)},true);e.addEventListener("ended",function(){N();h()},true);e.innerHTML=" <p>If you are reading this, it is because your browser does not support the audio element. We recommend that you get a new browser.</p> <p>";document.body.appendChild(e);e.play()}function x(e){e.className+=" "+s+" "+o}function T(e){e.className+=" "+s+" "+u[Math.floor(Math.random()*u.length)]}function N(){var e=document.getElementsByClassName(s);var t=new RegExp("\\b"+s+"\\b");for(var n=0;n<e.length;){e[n].className=e[n].className.replace(t,"")}}var e=30;var t=30;var n=350;var r=350;var i="//s3.amazonaws.com/moovweb-marketing/playground/harlem-shake.mp3";var s="mw-harlem_shake_me";var o="im_first";var u=["im_drunk","im_baked","im_trippin","im_blown"];var a="mw-strobe_light";var f="//s3.amazonaws.com/moovweb-marketing/playground/harlem-shake-style.css";var l="mw_added_css";var b=g();var w=y();var C=document.getElementsByTagName("*");var k=null;for(var L=0;L<C.length;L++){var A=C[L];if(v(A)){if(E(A)){k=A;break}}}if(A===null){console.warn("Could not find a node of the right size. Please try a different page.");return}c();S();var O=[];for(var L=0;L<C.length;L++){var A=C[L];if(v(A)){O.push(A)}}})();
        });
    }

    registerEditor(name, initFn) {
        if (typeof initFn !== 'function') {
            console.log('initFn has to be a functions');
            return false;
        }

        return fredConfig.registerEditor(name, initFn(this, Editor, pluginTools()));
    }

    registerRTE(name, initFn) {
        if (typeof initFn !== 'function') {
            console.log('initFn has to be a functions');
            return false;
        }

        return fredConfig.registerRTE(name, initFn(this, pluginTools()));
    }

    registerSidebarPlugin(name, initFn) {
        if (typeof initFn !== 'function') {
            console.log('initFn has to be a functions');
            return false;
        }

        return fredConfig.registerSidebarPlugin(name, initFn(this, SidebarPlugin, pluginTools()));
    }

    registerToolbarPlugin(name, initFn) {
        if (typeof initFn !== 'function') {
            console.log('initFn has to be a functions');
            return false;
        }

        return fredConfig.registerToolbarPlugin(name, initFn(this, ToolbarPlugin, pluginTools()));
    }

    loadLexicons() {
        let topics = '';
        if (fredConfig.config.lexicons && Array.isArray(fredConfig.config.lexicons)) {
            topics = '&topics=' + fredConfig.config.lexicons.join(',');
        }
        return fetchLexicons(topics).then(json => {
            fredConfig.lang = json.data;
            return true;
        });
    }

    init() {
        this.registerListeners();
        this.registerKeyboardShortcuts();

        this.dropzones = document.querySelectorAll('[data-fred-dropzone]:not([data-fred-dropzone=""])');
        let registeredDropzones = [];

        for (let zoneIndex = 0; zoneIndex < this.dropzones.length; zoneIndex++) {
            if (registeredDropzones.indexOf(this.dropzones[zoneIndex].dataset.fredDropzone) !== -1) {
                console.error('There are several dropzones with same name: ' + this.dropzones[zoneIndex].dataset.fredDropzone + '. The name of each dropzone has to be unique.');
                return false;
            }

            registeredDropzones.push(this.dropzones[zoneIndex].dataset.fredDropzone);
        }

        if (typeof fredConfig.config.beforeRender === 'function') {
            fredConfig.config.beforeRender = fredConfig.config.beforeRender.bind(this);
            fredConfig.config.beforeRender();
        }

        this.render();
        drake.initDrake();

        this.loadContent().then(() => {
            if (this.scriptsToReplace[0]) {
                this.replaceScript(0);
            }
        });

    }

    replaceScript(index) {
        const next = index + 1;

        if (this.scriptsToReplace[index].new.src) {
            this.scriptsToReplace[index].new.addEventListener('load', () => {
                if (this.scriptsToReplace[next]) {
                    this.replaceScript(next);
                }
            });

            this.scriptsToReplace[index].old.parentElement.replaceChild(this.scriptsToReplace[index].new, this.scriptsToReplace[index].old);
            return;
        }

        this.scriptsToReplace[index].old.parentElement.replaceChild(this.scriptsToReplace[index].new, this.scriptsToReplace[index].old);

        if (this.scriptsToReplace[next]) {
            this.replaceScript(next);
        }
    }

    getContent() {
        const data = {};

        for (let i = 0; i < this.dropzones.length; i++) {
            data[this.dropzones[i].dataset.fredDropzone] = this.getDataFromDropZone(this.dropzones[i]);
        }

        return data;
    }

    logoutUser() {
        document.location.href = fredConfig.config.managerUrl + '?a=security/logout';
    }
}
