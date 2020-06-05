panel1 = new Ext.Panel({
    title: 'Задание 1',
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
                '<p>Динамическое создание кнопок.</p>',
                'Добавить кнопку, при нажатии на которую, внизу будет появлятся такая же кнопка.',
                'При этом нажатая кнопка деактивируется через 3 секунды после нажатия'
            ].join('<br/>')
        }, {
            xtype: 'panel',
            flex: 1,
            padding: 10,
            html: 'Тут решение',
            items: [
                new Ext.Button({
                    text: 'Кнопка',
                    style: {
                        margin: '10px',
                    },
                    handler: function() {
                        var me = this,
                            panel = this.findParentByType('panel');
                        panel.add(this.cloneConfig());
                        panel.doLayout();

                        setTimeout(function() {
                            me.disable();
                        }, 3000);
                    }
                })
            ]
        }
    ]
});