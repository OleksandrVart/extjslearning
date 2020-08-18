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

var result = new Ext.Panel({
    xtype:'panel',
    width: 500,
    style: {
        textAlign: 'center',
    },
    padding: 20,
    margin: 50,
    items: []
});

var tbar = new Ext.Toolbar({
    items: [
        {
            xtype: 'button',
            text: "Кнопка",
            disabled: true,
            id: 'choiceBtn',
            handler: function () {
                var sm = grid.getSelectionModel();
                var text = sm.getSelected().get('name');
                result.removeAll();
                result.add({
                    xtype: 'box',
                    html: text
                });
                result.doLayout();
            }
        },
    ],
});

var grid = new Ext.grid.GridPanel({
    store: ds,
    sm: csm,
    tbar: tbar,
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
    listeners: {
        rowclick: function(gr, rowIndex, e){
            if (gr.getSelectionModel().hasSelection()) {
                Ext.getCmp('choiceBtn').enable();
            } else {
                Ext.getCmp('choiceBtn').disable();
            }
        }
    },

});

panel6 = new Ext.Panel({
    title: 'Задание 6',
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
                'Сделать топ тулбар в гриде. В него добавить кнопку (изначально задизейбленная).',
                'При выделении строки в гриде (кнопка активируется)',
                'При нажатии на кнопку под гридом пишется текстом то что выбрано в гриде'
            ].join('<br/>')
        }, {
            xtype: 'panel',
            flex: 1,
            padding: 10,
            items: [
                grid,
                result
            ]
        }
    ]
});