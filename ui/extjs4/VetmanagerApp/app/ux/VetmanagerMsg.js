Ext4.ux.VetmanagerMsg = function(){
    var msgCt;

    function createBox(t, s){
       return '<div class="msg"><h3>' + t + '</h3><p>' + s + '</p></div>';
    }
    return {
        msg : function(title, format){
            if(!msgCt){
                msgCt = Ext4.DomHelper.insertFirst(document.body, {id:'msg-div'}, true);
            }
            var s = Ext4.String.format.apply(String, Array.prototype.slice.call(arguments, 1));
            var m = Ext4.DomHelper.append(msgCt, createBox(title, s), true);
            m.hide();
            m.slideIn('t').ghost("t", { delay: 1000, remove: true});
        }
    };
}();