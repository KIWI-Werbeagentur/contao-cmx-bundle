(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const toolbarWrapper = document.getElementsByTagName('cto-toolbar')[0];
        const toolbar = toolbarWrapper.shadowRoot.querySelector('.cto-toolbar');
        toolbar.addEventListener('cto-toolbar-loaded', (e) => {
            // prepare ui context as shadow DOM
            const cmxUi = document.createElement('cmx-ui');
            document.querySelector('body').appendChild(cmxUi);
            cmxUi.attachShadow({mode: 'open'});

            // add ui context
            const uiEls = document.querySelectorAll('.cmx--ui[data-cmx-ui]');
            uiEls.forEach((el) => {
                cmxUi.shadowRoot.appendChild(el);
            });

            repositionCmxUi(e);
            window.addEventListener('resize', repositionCmxUi);

            // add .cmx--ui styles to shadow DOM
            let cmxUiCss = '';
            for(const rule of document.head.querySelector('link[data-cmx-ui]').sheet.cssRules) {
                if (rule.selectorText.startsWith('.cmx--ui')) cmxUiCss += rule.cssText;
            }
            const sheet = new CSSStyleSheet();
            sheet.replaceSync(cmxUiCss);
            cmxUi.shadowRoot.adoptedStyleSheets = [sheet];
        });
    });

    function repositionCmxUi(e) {
        const cmxUi = document.querySelector('cmx-ui');
        const uiEls = cmxUi.shadowRoot.querySelectorAll('.cmx--ui[data-cmx-ui]');
        uiEls.forEach((el) => {
            const a = document.querySelector(`.mod_article[data-cmx-ui="${el.dataset.cmxUi}"]`);
            const r = a.getBoundingClientRect();
            el.style.top = window.scrollY + r.top + 'px';
            el.style.left = window.scrollX + r.left + 'px';
            el.style.width = r.width + 'px';
            el.style.height = r.height + 'px';
        });
    }
})();
