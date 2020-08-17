var csm = new Ext.grid.CheckboxSelectionModel();

var ds = new Ext.data.JsonStore({
    url: 'ajax_request.php',
    baseParams: {
        cmd: 'task_5'
    },
    autoLoad: true,
    fields: [
        'name',
        'sex',
        'status'
    ],
});

var grid = new Ext.grid.GridPanel({
    store: ds,
    sm: csm,
    colModel: new Ext.grid.ColumnModel({
        defaults: {
            width: 120,
            sortable: true
        },
        columns: [
            csm,
            {
                id: 'name',
                header: 'Имя',
                width: 200,
                sortable: true,
                dataIndex: 'name'
            }, {
                header: 'Пол',
                dataIndex: 'sex'
            }, {
                header: 'Статус',
                dataIndex: 'status',
                renderer: function(v) {
                    var text = (v) ? 'Онлайн' : 'Офлайн';
                    var color = (v) ? 'green' : 'red';
                    return '<span style="color: ' + color + '">' + text + '</span>'
                }
            },
        ]
    }),
    width: 500,
    height: 300,
    frame: true,
    title: 'Сотрудники',
});

panel5 = new Ext.Panel({
    title: 'Задание 5',
    listeners: {
        scope: this,
        activate: function (a) {
            console.log('activate');
            a.doLayout();
        }
    },
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    items: [
        {
            xtype: 'panel',
            autoHeight: true,
            padding: 10,
            style: {
                fontWeight: 'bold',
                fontSize: '14px',
                color: 'green'
            },
            html: [
                '<p>Работа с гридом</p>',
                'Сделать грид с 3-мя колонками, который грузит тестовые данные с сервера',
                'В гриде должна быть возможность выбрать много строк, через checkboxselectionmodel'
            ].join('<br/>')
        }, {
            xtype: 'panel',
            flex: 1,
            padding: 10,
            items: [
                grid
            ]
        }
    ]
});