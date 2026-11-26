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

    // navigate the back end like the core handlers: the sidebar holds the back end entry point, the current
    // path may be a sub-route (e.g. /contao/template-studio) that 404s with these parameters
    const visit = (params) => {
        const beUrl = document.getElementById('clp-right')?.dataset.clpBackendUrl || '/contao';
        const url = beUrl + '?' + params.toString();
        if (window.Turbo) {
            Turbo.visit(url);
        } else {
            window.location.href = url;
        }
    };

    const registerHandlers = () => {
        // Change core edit action for tl_article to target article edit view (not list). Replaces the core
        // handler (same id) and passes all other tables on to it, a parallel handler would navigate twice.
        const coreEdit = CLP_BE._h['clp:edit']?.get('clp:edit');
        CLP_BE.on('clp:edit', (d, e) => {
            const {table, id} = d;
            if (table !== 'tl_article') {
                coreEdit?.(d, e);
                return;
            }
            if (!id) return;
            const params = new URLSearchParams({do: 'article', table: 'tl_article', act: 'edit', id: String(id)});
            visit(params);
        });
        // Handle new action for article list view
        CLP_BE.on('cmx:edit-children', (d) => {
            const {table, id, parentTable} = d;
            if (!table || !id) return;
            let params;
            if (table === 'tl_article') {
                params = new URLSearchParams({do: 'article', table: 'tl_content', id: String(id)});
            } else return;
            visit(params);
        });
        CLP_BE.on('cmx:page-children', (d,e) => {
            const {table, pageId} = d;
            if (!table || !pageId) return;
            const rt   = window.Contao?.request_token || window.Contao?.requestToken || '';
            let params;
            if (table === 'tl_article') {
                params = new URLSearchParams({do: 'article', pn: String(pageId), rt});
            } else return;
            visit(params);
        });
    }
})();