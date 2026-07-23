(function (){
    'use strict';

    if(!window.CLP_FE) return;

    const _childrenIcon = `
<svg style="flex-shrink:0" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
  <rect width="7" height="7" x="3" y="3" rx="1"/>
  <rect width="7" height="7" x="3" y="14" rx="1"/>
  <path d="M14 4h7m-7 5h7m-7 6h7m-7 5h7"/>
</svg>
    `;
    const _pageIcon = `
<svg style="flex-shrink:0" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
  <path d="M15 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V7Z"/>
</svg>
    `;
    CLP_FE.set('cmx:edit-children', (ctx, post, ui) => {
        if (ctx.table !== 'tl_article') return;
        return ui.button({icon:_childrenIcon,class:'cmx-badge-article-edit',title:'Inhalte anzeigen',postOptions:{type:'cmx:edit-children',table:ctx.table,id:ctx.id,parentTable:ctx.parentTable||''}});
    });
    CLP_FE.set('cmx:page-children', (ctx, post, ui) => {
        if (ctx.table !== 'tl_article') return;
        return ui.button({icon:_pageIcon,class:'cmx-badge-page-children',title:'Seite anzeigen',postOptions:{type:'cmx:page-children',parentTable:ctx.parentTable||''}});
    });
})();