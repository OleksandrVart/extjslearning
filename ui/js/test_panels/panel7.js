panel7 = new Ext.Panel({
    title: 'Задание 7',
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
                '<p>Работа с лайаутами</p>',
                'Добавить бордер лаяут, все части должны схлопываться',
                'В левой части добавить панельку с лаяут акордеон (из 3х панелек)',
                'В правой части добавить панельку с лаяут колумн (из 2х панелек)'
            ].join('<br/>')
        }, {
            xtype: 'panel',
            flex: 1,
            padding: 10,
            layout: 'border',
            items: [
                {
                    xtype:'panel',
                    region: 'center',
                    style: {
                        textAlign: 'center',
                    },
                    padding: 20,
                    items: [
                        {
                            xtype: 'box',
                            html: 'Тут большая центральная часть'
                        }
                    ]
                },
                {
                    region: 'west',
                    id: 'left-panel',
                    title: 'Левая',
                    split: true,
                    width: 200,
                    minSize: 100,
                    maxSize: 300,
                    collapsible: true,
                    margins: '5 0 5 5',
                    layout: {
                        type: 'accordion',
                        animate: true
                    },
                    items: [{
                        title: 'Первая',
                        html: '<p>Очень важная первая вкладка</p>',
                        padding: 5,
                        border: false,
                    }, {
                        title: 'Вторая',
                        html: '<p>Так себе вторая</p>',
                        padding: 5,
                        border: false,
                    }, {
                        title: 'Третья',
                        html: '<p>Ну и что-то там в третьей</p>',
                        padding: 5,
                        border: false,
                    }]
                },
                {
                    region: 'east',
                    id: 'right-panel',
                    title: 'Правая',
                    split: true,
                    width: 300,
                    minSize: 100,
                    maxSize: 500,
                    collapsible: true,
                    margins: '5 5 5 0',
                    layout: 'column',
                    items: [{
                        cls: 'qwerty',
                        flex: 1,
                        padding: 5,
                        title: 'One',
                        columnWidth: 0.5,
                        html: '<p>Столбец 1</p>',
                        // border: false,
                    }, {
                        layout: 'fit',
                        title: 'Two',
                        padding: 5,
                        columnWidth: 0.5,
                        html: '<p>Столбец 2</p>',
                        // border: false,
                    }]
                },
            ]
        }
    ]
});