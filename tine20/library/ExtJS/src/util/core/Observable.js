/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */

const { toArray, each, isObject, isString, applyIf, isFunction, isBoolean } = require("Ext/core/core/Ext");
const DelayedTask = require("Ext/util/core/DelayedTask");

/**
 * @class Ext.util.Observable
 * Base class that provides a common interface for publishing events. Subclasses are expected to
 * to have a property "events" with all the events defined, and, optionally, a property "listeners"
 * with configured listeners defined.<br>
 * For example:
 * <pre><code>
Employee = Ext.extend(Ext.util.Observable, {
    constructor: function(config){
        this.name = config.name;
        this.addEvents({
            "fired" : true,
            "quit" : true
        });

        // Copy configured listeners into *this* object so that the base class&#39;s
        // constructor will add them.
        this.listeners = config.listeners;

        // Call our superclass constructor to complete construction process.
        Employee.superclass.constructor.call(config)
    }
});
</code></pre>
 * This could then be used like this:<pre><code>
var newEmployee = new Employee({
    name: employeeName,
    listeners: {
        quit: function() {
            // By default, "this" will be the object that fired the event.
            alert(this.name + " has quit!");
        }
    }
});
</code></pre>
 */
const Observable = function(){
    /**
     * @cfg {Object} listeners (optional) <p>A config object containing one or more event handlers to be added to this
     * object during initialization.  This should be a valid listeners config object as specified in the
     * {@link #addListener} example for attaching multiple handlers at once.</p>
     * <br><p><b><u>DOM events from ExtJs {@link Ext.Component Components}</u></b></p>
     * <br><p>While <i>some</i> ExtJs Component classes export selected DOM events (e.g. "click", "mouseover" etc), this
     * is usually only done when extra value can be added. For example the {@link Ext.DataView DataView}'s
     * <b><code>{@link Ext.DataView#click click}</code></b> event passing the node clicked on. To access DOM
     * events directly from a Component's HTMLElement, listeners must be added to the <i>{@link Ext.Component#getEl Element}</i> after the Component
     * has been rendered. A plugin can simplify this step:<pre><code>
// Plugin is configured with a listeners config object.
// The Component is appended to the argument list of all handler functions.
Ext.DomObserver = Ext.extend(Object, {
    constructor: function(config) {
        this.listeners = config.listeners ? config.listeners : config;
    },

    // Component passes itself into plugin&#39;s init method
    init: function(c) {
        var p, l = this.listeners;
        for (p in l) {
            if (Ext.isFunction(l[p])) {
                l[p] = this.createHandler(l[p], c);
            } else {
                l[p].fn = this.createHandler(l[p].fn, c);
            }
        }

        // Add the listeners to the Element immediately following the render call
        c.render = c.render.{@link Function#createSequence createSequence}(function() {
            var e = c.getEl();
            if (e) {
                e.on(l);
            }
        });
    },

    createHandler: function(fn, c) {
        return function(e) {
            fn.call(this, e, c);
        };
    }
});

var combo = new Ext.form.ComboBox({

    // Collapse combo when its element is clicked on
    plugins: [ new Ext.DomObserver({
        click: function(evt, comp) {
            comp.collapse();
        }
    })],
    store: myStore,
    typeAhead: true,
    mode: 'local',
    triggerAction: 'all'
});
     * </code></pre></p>
     */
    var me = this, e = me.events;
    if(me.listeners){
        me.on(me.listeners);
        delete me.listeners;
    }
    me.events = e || {};
};

Observable.prototype = {
    // private
    filterOptRe : /^(?:scope|delay|buffer|single)$/,

    /**
     * <p>Fires the specified event with the passed parameters (minus the event name).</p>
     * <p>An event may be set to bubble up an Observable parent hierarchy (See {@link Ext.Component#getBubbleTarget})
     * by calling {@link #enableBubble}.</p>
     * @param {String} eventName The name of the event to fire.
     * @param {Object...} args Variable number of parameters are passed to handlers.
     * @return {Boolean} returns false if any of the handlers return false otherwise it returns true.
     */
    fireEvent : function(){
        var a = toArray(arguments),
            ename = a[0].toLowerCase(),
            me = this,
            ret = true,
            ce = me.events[ename],
            q,
            c;
        if (me.eventsSuspended === true) {
            if (q = me.eventQueue) {
                q.push(a);
            }
        }
        else if(isObject(ce) && ce.bubble){
            if(ce.fire.apply(ce, a.slice(1)) === false) {
                return false;
            }
            c = me.getBubbleTarget && me.getBubbleTarget();
            if(c && c.enableBubble) {
                if(!c.events[ename] || !isObject(c.events[ename]) || !c.events[ename].bubble) {
                    c.enableBubble(ename);
                }
                return c.fireEvent.apply(c, a);
            }
        }
        else {
            if (isObject(ce)) {
                a.shift();
                ret = ce.fire.apply(ce, a);
            }
        }
        return ret;
    },

    fireAsyncEvent: async function() {
        const ce = this.events[arguments[0]] || {};
        ce.isAsync = true;
        const r = this.fireEvent.apply(this, arguments);
        ce.isAsync = false;
        return !r || r === true ? Promise.resolve() : (isFunction(r.then) ? r : Promise.reject());
    },
    
    /**
     * Appends an event handler to this object.
     * @param {String}   eventName The name of the event to listen for.
     * @param {Function} handler The method the event invokes.
     * @param {Object}   scope (optional) The scope (<code><b>this</b></code> reference) in which the handler function is executed.
     * <b>If omitted, defaults to the object which fired the event.</b>
     * @param {Object}   options (optional) An object containing handler configuration.
     * properties. This may contain any of the following properties:<ul>
     * <li><b>scope</b> : Object<div class="sub-desc">The scope (<code><b>this</b></code> reference) in which the handler function is executed.
     * <b>If omitted, defaults to the object which fired the event.</b></div></li>
     * <li><b>delay</b> : Number<div class="sub-desc">The number of milliseconds to delay the invocation of the handler after the event fires.</div></li>
     * <li><b>single</b> : Boolean<div class="sub-desc">True to add a handler to handle just the next firing of the event, and then remove itself.</div></li>
     * <li><b>buffer</b> : Number<div class="sub-desc">Causes the handler to be scheduled to run in an {@link Ext.util.DelayedTask} delayed
     * by the specified number of milliseconds. If the event fires again within that time, the original
     * handler is <em>not</em> invoked, but the new handler is scheduled in its place.</div></li>
     * <li><b>target</b> : Observable<div class="sub-desc">Only call the handler if the event was fired on the target Observable, <i>not</i>
     * if the event was bubbled up from a child Observable.</div></li>
     * </ul><br>
     * <p>
     * <b>Combining Options</b><br>
     * Using the options argument, it is possible to combine different types of listeners:<br>
     * <br>
     * A delayed, one-time listener.
     * <pre><code>
myDataView.on('click', this.onClick, this, {
single: true,
delay: 100
});</code></pre>
     * <p>
     * <b>Attaching multiple handlers in 1 call</b><br>
     * The method also allows for a single argument to be passed which is a config object containing properties
     * which specify multiple handlers.
     * <p>
     * <pre><code>
myGridPanel.on({
'click' : {
    fn: this.onClick,
    scope: this,
    delay: 100
},
'mouseover' : {
    fn: this.onMouseOver,
    scope: this
},
'mouseout' : {
    fn: this.onMouseOut,
    scope: this
}
});</code></pre>
 * <p>
 * Or a shorthand syntax:<br>
 * <pre><code>
myGridPanel.on({
'click' : this.onClick,
'mouseover' : this.onMouseOver,
'mouseout' : this.onMouseOut,
 scope: this
});</code></pre>
     */
    addListener : function(eventName, fn, scope, o){
        var me = this,
            e,
            oe,
            isF,
        ce;
        if (isObject(eventName)) {
            o = eventName;
            for (e in o){
                oe = o[e];
                if (!me.filterOptRe.test(e)) {
                    me.addListener(e, oe.fn || oe, oe.scope || o.scope, oe.fn ? oe : o);
                }
            }
        } else {
            eventName = eventName.toLowerCase();
            ce = me.events[eventName] || true;
            if (isBoolean(ce)) {
                me.events[eventName] = ce = new Event(me, eventName);
            }
            ce.addListener(fn, scope, isObject(o) ? o : {});
        }
    },

    /**
     * Removes an event handler.
     * @param {String}   eventName The type of event the handler was associated with.
     * @param {Function} handler   The handler to remove. <b>This must be a reference to the function passed into the {@link #addListener} call.</b>
     * @param {Object}   scope     (optional) The scope originally specified for the handler.
     */
    removeListener : function(eventName, fn, scope){
        var ce = this.events[eventName.toLowerCase()];
        if (isObject(ce)) {
            ce.removeListener(fn, scope);
        }
    },

    /**
     * Removes all listeners for this object
     */
    purgeListeners : function(){
        var events = this.events,
            evt,
            key;
        for(key in events){
            evt = events[key];
            if(isObject(evt)){
                evt.clearListeners();
            }
        }
    },

    /**
     * Adds the specified events to the list of events which this Observable may fire.
     * @param {Object|String} o Either an object with event names as properties with a value of <code>true</code>
     * or the first event name string if multiple event names are being passed as separate parameters.
     * @param {string} Optional. Event name if multiple event names are being passed as separate parameters.
     * Usage:<pre><code>
this.addEvents('storeloaded', 'storecleared');
</code></pre>
     */
    addEvents : function(o){
        var me = this;
        me.events = me.events || {};
        if (isString(o)) {
            var a = arguments,
                i = a.length;
            while(i--) {
                me.events[a[i]] = me.events[a[i]] || true;
            }
        } else {
            applyIf(me.events, o);
        }
    },

    /**
     * Checks to see if this object has any listeners for a specified event
     * @param {String} eventName The name of the event to check for
     * @return {Boolean} True if the event is being listened for, else false
     */
    hasListener : function(eventName){
        var e = this.events[eventName];
        return isObject(e) && e.listeners.length > 0;
    },

    /**
     * Suspend the firing of all events. (see {@link #resumeEvents})
     * @param {Boolean} queueSuspended Pass as true to queue up suspended events to be fired
     * after the {@link #resumeEvents} call instead of discarding all suspended events;
     */
    suspendEvents : function(queueSuspended){
        this.eventsSuspended = true;
        if(queueSuspended && !this.eventQueue){
            this.eventQueue = [];
        }
    },

    /**
     * Resume firing events. (see {@link #suspendEvents})
     * If events were suspended using the <tt><b>queueSuspended</b></tt> parameter, then all
     * events fired during event suspension will be sent to any listeners now.
     */
    resumeEvents : function(){
        var me = this,
            queued = me.eventQueue || [];
        me.eventsSuspended = false;
        delete me.eventQueue;
        each(queued, function(e) {
            me.fireEvent.apply(me, e);
        });
    }
};

/**
 * Appends an event handler to this object (shorthand for {@link #addListener}.)
 * @param {String}   eventName     The type of event to listen for
 * @param {Function} handler       The method the event invokes
 * @param {Object}   scope         (optional) The scope (<code><b>this</b></code> reference) in which the handler function is executed.
 * <b>If omitted, defaults to the object which fired the event.</b>
 * @param {Object}   options       (optional) An object containing handler configuration.
 * @method
 */
Observable.prototype.on = Observable.prototype.addListener;
/**
 * Removes an event handler (shorthand for {@link #removeListener}.)
 * @param {String}   eventName     The type of event the handler was associated with.
 * @param {Function} handler       The handler to remove. <b>This must be a reference to the function passed into the {@link #addListener} call.</b>
 * @param {Object}   scope         (optional) The scope originally specified for the handler.
 * @method
 */
Observable.prototype.un = Observable.prototype.removeListener;

/**
 * Removes <b>all</b> added captures from the Observable.
 * @param {Observable} o The Observable to release
 * @static
 */
Observable.releaseCapture = function(o){
    o.fireEvent = Observable.prototype.fireEvent;
};

function createTargeted(h, o, scope){
    return function(){
        if(o.target == arguments[0]){
            h.apply(scope, toArray(arguments));
        }
    };
};

function createBuffered(h, o, l, scope){
    l.task = new DelayedTask();
    return function(){
        l.task.delay(o.buffer, h, scope, toArray(arguments));
    };
};

function createSingle(h, e, fn, scope){
    return function(){
        e.removeListener(fn, scope);
        return h.apply(scope, arguments);
    };
};

function createDelayed(h, o, l, scope){
    return function(){
        var task = new DelayedTask();
        if(!l.tasks) {
            l.tasks = [];
        }
        l.tasks.push(task);
        task.delay(o.delay || 10, h, scope, toArray(arguments));
    };
};

const Event = function(obj, name){
    this.name = name;
    this.obj = obj;
    this.listeners = [];
    this.isAsync = false;
};

Event.prototype = {
    addListener : function(fn, scope, options){
        var me = this,
            l;
        scope = scope || me.obj;
        if(!me.isListening(fn, scope)){
            l = me.createListener(fn, scope, options);
            if(me.firing){ // if we are currently firing this event, don't disturb the listener loop
                me.listeners = me.listeners.slice(0);
            }
            me.listeners.push(l);
        }
    },

    createListener: function(fn, scope, o){
        o = o || {}, scope = scope || this.obj;
        var l = {
            fn: fn,
            scope: scope,
            options: o
        }, h = fn;
        if(o.target){
            h = createTargeted(h, o, scope);
        }
        if(o.delay){
            h = createDelayed(h, o, l, scope);
        }
        if(o.single){
            h = createSingle(h, this, fn, scope);
        }
        if(o.buffer){
            h = createBuffered(h, o, l, scope);
        }
        l.fireFn = h;
        return l;
    },

    findListener : function(fn, scope){
        var list = this.listeners,
            i = list.length,
            l,
            s;
        while(i--) {
            l = list[i];
            if(l) {
                s = l.scope;
                if(l.fn == fn && (s == scope || s == this.obj)){
                    return i;
                }
            }
        }
        return -1;
    },

    isListening : function(fn, scope){
        return this.findListener(fn, scope) != -1;
    },

    removeListener : function(fn, scope){
        var index,
            l,
            k,
            me = this,
            ret = false;
        if((index = me.findListener(fn, scope)) != -1){
            if (me.firing) {
                me.listeners = me.listeners.slice(0);
            }
            l = me.listeners[index];
            if(l.task) {
                l.task.cancel();
                delete l.task;
            }
            k = l.tasks && l.tasks.length;
            if(k) {
                while(k--) {
                    l.tasks[k].cancel();
                }
                delete l.tasks;
            }
            me.listeners.splice(index, 1);
            ret = true;
        }
        return ret;
    },

    // Iterate to stop any buffered/delayed events
    clearListeners : function(){
        var me = this,
            l = me.listeners,
            i = l.length;
        while(i--) {
            me.removeListener(l[i].fn, l[i].scope);
        }
    },

    fire : function(){
        if (this.isAsync) return this.fireAsync.apply(this, arguments);

        var me = this,
            args = toArray(arguments),
            listeners = me.listeners,
            len = listeners.length,
            i = 0,
            l;

        if(len > 0){
            me.firing = true;
            for (; i < len; i++) {
                l = listeners[i];
                if(l && l.fireFn.apply(l.scope || me.obj || window, args) === false) {
                    return (me.firing = false);
                }
            }
        }
        me.firing = false;
        return true;
    },

    fireAsync : async function() {
        const args = toArray(arguments);
        let result = Promise.resolve();

        if (this.listeners.length) {
            this.firing = true;

            for (let listener of this.listeners) {
                try {
                    if( await listener.fireFn.apply(listener.scope || this.obj || window, args) === false) {
                        throw null; // non async event listener returned false
                    }
                } catch (e) {
                    result = Promise.reject();
                    break;
                }
            }

            this.firing = false;
        }
        return result;
    }
};

module.exports = { Observable, Event }