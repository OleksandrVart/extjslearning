var store = new Ext.data.ArrayStore({
    fields: ['id', 'name'],
    data: [
        [0, 'name 0'],
        [1, 'name 1'],
        [2, 'name 2']
    ]
});

var comboLocal = new Ext.form.ComboBox({
    store: store,
    valueField: 'id',
    displayField: 'name',
    typeAhead: true,
    mode: 'local',
    forceSelection: true,
    triggerAction: 'all',
    emptyText:'Выберите что-то',
    selectOnFocus: true,
});

var comboRemote = new Ext.form.ComboBox({
    // store: loadStore,
    valueField: 'id',
    displayField: 'name',
    typeAhead: true,
    forceSelection: true,
    triggerAction: 'all',
    emptyText:'Выберите что-то еще?',
    selectOnFocus: true,
    store: new Ext.data.JsonStore({
        url: 'ajax_request.php',
        baseParams: {
            cmd: 'task_2'
        },
        autoLoad: false,
        fields: [
            'id',
            'name'
        ],
    })
});

panel2 = new Ext.Panel({
    title: 'Задание 2',
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
                '<p>Работа с комбобоксами</p>',
                'Сделать комбобокс (1) со store type=local,и поместить в нее несколько записей. Справа от комбо сделать кнопку, при ее нажатии в комбо будет добавлятся новая запись',
                'Сделать комбобокс (2) со store type=remote и справа от него кнопка (обновить)',
                'В комбобокс (2) грузятся данные с сервера (любые, тестовые) по нажатию кнопки обновить'
            ].join('<br/>')
        }, {
            xtype: 'panel',
            flex: 1,
            padding: 10,
            items: [
                {
                    xtype: 'panel',
                    name: 'comboBox-1',
                    flex: 1,
                    padding: 10,
                    border: false,
                    layout: {
                        type: 'hbox',
                        padding:'5',
                        align:'middle'
                    },
                    items: [
                        comboLocal,
                        new Ext.Button({
                            text: 'Добавить значение в список',
                            style: {
                                marginLeft: '10px',
                            },
                            handler: function() {
                                var count = store.getCount();
                                var rec = new Ext.data.Record({
                                    id: count,
                                    name: 'name ' + count
                                });
                                store.add(rec);
                            }
                        })
                    ]
                },
                {
                    xtype: 'panel',
                    name: 'comboBox-2',
                    flex: 1,
                    padding: 10,
                    border: false,
                    layout: {
                        type: 'hbox',
                        padding:'5',
                        align:'middle'
                    },
                    items: [
                        comboRemote,
                        new Ext.Button({
                            text: 'Обновить',
                            style: {
                                marginLeft: '10px',
                            },
                            handler: function() {
                                var loadStore = comboRemote.getStore();
                                loadStore.setBaseParam('add', 'new');
                                loadStore.load();
                            }
                        })
                    ]
                }

            ]
        }
    ]
});