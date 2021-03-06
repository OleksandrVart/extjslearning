Ext4.define('VetmanagerApp.modules.graphic_reports.store.RevenuesByCategory', {
    extend: 'Ext4.data.Store'
    , model: 'VetmanagerApp.modules.graphic_reports.model.RevenuesByCategory'
    , autoLoad: false
    , proxy: {
        type: 'ajax'
        , url : 'ajax_graphic.php'
        , extraParams: {cmd: 'get_revenues_by_category'}
        , reader: {
            type: 'json'
            , root: 'data'
            , totalProperty: 'total'
            , idProperty: 'id'
        }
    }
});