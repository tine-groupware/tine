   
Ext.ns('Ext.ux');

Ext.ux.MessageBox = function(){
    var msgCt;

    function createBox(t, s){
        return ['<div class="x-ux-messagebox-msg">',
                '<div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>',
                '<div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc"><h3>', t, '</h3>', s, '</div></div></div>',
                '<div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>',
                '</div>'].join('');
    }
    return {
        msg : function(title, text='', timeOut=3){

            if(!msgCt){
                msgCt = Ext.DomHelper.insertFirst(document.body, {id:'x-ux-messagebox-msg-div'}, true);
            }
            msgCt.alignTo(document, 't-t');
            var s = String.format.apply(String, [text]);
            var m = Ext.DomHelper.append(msgCt, {html:createBox(title, s)}, true);
            m.slideIn('t').pause(timeOut).ghost("t", {remove:true});
            return new Promise(resolve => {
                window.setTimeout(resolve, timeOut + 1000);
            });
        }
    };
}();
