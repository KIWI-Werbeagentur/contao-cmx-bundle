(function () {
    'use strict';

    if(!window.CLP_BE) return;

    const t = Date.now();
    const wait = setInterval(() => {
        if (window.CLP_BE.isInitialised) {
            clearInterval(wait);
            registerHandlers();
        } else if (Date.now() - t > 5000) {
            // timeout after 5 seconds
            console.error('Could not register custom live-preview BE handlers: Initialization timed out.');
            clearInterval(wait);
        }
    }, 50);

    const registerHandlers = () => {
        // Change core edit action for tl_article to target article edit view (not list)
        CLP_BE.on('clp:edit', (d) => {
            const {table, id, parentTable} = d;
            if (!table || !id) return;
            let params;
            if (table === 'tl_article') {
                params = new URLSearchParams({do: 'article', table: 'tl_article', act: 'edit', id: String(id)});
            } else return;
            const url = window.location.pathname + '?' + params.toString();
            if (window.Turbo) {
                Turbo.visit(url);
            } else {
                window.location.href = url;
            }
        }, 'cmx:edit');
        // Handle new action for article list view
        CLP_BE.on('cmx:edit-children', (d) => {
            const {table, id, parentTable} = d;
            if (!table || !id) return;
            let params;
            if (table === 'tl_article') {
                params = new URLSearchParams({do: 'article', table: 'tl_content', id: String(id)});
            } else return;
            const url = window.location.pathname + '?' + params.toString();
            if (window.Turbo) {
                Turbo.visit(url);
            } else {
                window.location.href = url;
            }
        });
        CLP_BE.on('cmx:page-children', (d,e) => {
            const {table, id, parentTable} = d;
            if (!table || !id) return;
            let params;
            if (table === 'tl_article') {
                params = new URLSearchParams({do: 'article', pn: String(pageId)});
            } else return;
            const url = window.location.pathname + '?' + params.toString();
            if (window.Turbo) {
                Turbo.visit(url);
            } else {
                window.location.href = url;
            }
        });
    }
})();