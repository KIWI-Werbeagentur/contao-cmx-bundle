(function () {
    'use strict';

    // hide UI until loaded
    document.querySelectorAll('.cmx--ui[data-cmx-ui]').forEach((el) => {
        el.dataset.cmxDisplay = el.style.display;
        el.style.display = 'none'
    });

    document.addEventListener('DOMContentLoaded', () => {
        const toolbarWrapper = document.getElementsByTagName('cto-toolbar')[0];
        const toolbar = toolbarWrapper.shadowRoot.querySelector('.cto-toolbar');
        toolbar.addEventListener('cto-toolbar-loaded', (e) => {
            // prepare ui context as shadow DOM
            const cmxUi = document.createElement('cmx-ui');
            document.querySelector('body').appendChild(cmxUi);
            cmxUi.attachShadow({mode: 'open'});

            // add ui context to shadow DOM
            const uiEls = document.querySelectorAll('.cmx--ui[data-cmx-ui]');
            uiEls.forEach((el) => {
                cmxUi.shadowRoot.appendChild(el);
            });

            repositionCmxUi(uiEls);
            window.addEventListener('resize', () => repositionCmxUi(uiEls));

            // add styles to shadow DOM
            let cmxUiCss = '';
            for(const rule of document.head.querySelector('link[data-cmx-ui]').sheet.cssRules) {
                if (rule.selectorText.startsWith('.cmx--ui')) cmxUiCss += rule.cssText;
            }
            const sheet = new CSSStyleSheet();
            sheet.replaceSync(cmxUiCss);
            cmxUi.shadowRoot.adoptedStyleSheets = [sheet];

            // show UI after loading is finished
            uiEls.forEach((el) => {
                el.style.display = el.dataset.cmxDisplay;
                el.dataset.cmxDisplay = null;
            });
        });
    });

    function repositionCmxUi(uiEls) {
        uiEls.forEach((el) => {
            const a = document.querySelector(`.mod_article[data-cmx-ui="${el.dataset.cmxUi}"]`);
            const r = a.getBoundingClientRect();
            // position element relative to viewport boundaries, respecting scroll distance
            el.style.top = window.scrollY + r.top + 'px';
            el.style.left = window.scrollX + r.left + 'px';
            el.style.width = r.width + 'px';
            el.style.height = r.height + 'px';

            // do not shift badge out of view
            const b = el.querySelector('.cmx--ui__badge');
            const bTop = window.scrollY + b.getBoundingClientRect().top;
            if (bTop < 0) {
                b.classList.add('cmx--ui__badge--inside');
            }
        });
    }
})();
