/**
 * This is how we bootstrap JS code in our modules
 */
(function(Icinga) {

  Icinga.Module = function(icinga, name, prototyp) {

    // The Icinga instance
    this.icinga = icinga;

    // Event handlers registered by this module
    this.handlers = [];

    this.registeredHandlers = {};

    // The module name
    this.name = name;

    // The JS prototype for this module
    this.prototyp = prototyp;

    // Once initialized, this will be an instance of the modules prototype
    this.object = {};

    // Initialize this module
    this.initialize();
  };

  Icinga.Module.prototype = {

    initialize: function()
    {
      try {
        // The constructor of the modules prototype must be prepared to get an
        // instance of Icinga.Module
        this.object = new this.prototyp(this);
        this.applyRegisteredEventHandlers();
      } catch(e) {
        this.icinga.logger.error('Failed to load module ', this.name, ': ', e);
        return false;
      }

      // That's all, the module is ready
      this.icinga.logger.debug('Module ' + this.name + ' has been initialized');
      return true;
    },

    /**
     * Globally register this modules event handlers
     */
    registerEventHandlers: function(handlers)
    {
      this.registeredHandlers = handlers;
      return this;
    },

    applyRegisteredEventHandlers: function()
    {
      var self = this;
      $.each(this.registeredHandlers, function(filter, events) {
        $.each(events, function (event, handler) {
          // TODO: if (event[1] === 'each') {
          // $(event[0], $(el)).each(event[2]);
          self.bindEventHandler(
            event,
            '.module-' + self.name + ' ' + filter,
            handler
          );
        });
      });
      self = null;
      return this;
    },

    /**
     * Effectively bind the given event handler
     */
    bindEventHandler: function(event, filter, handler)
    {
      var self = this;
      this.icinga.logger.debug('Bound ' + filter + ' .' + event + '()');
      this.handlers.push([event, filter, handler]);
      $(document).on(event, filter, handler.bind(self.object));
    },

    /**
     * Unbind all event handlers bound by this module
     */
    unbindEventHandlers: function()
    {
        $.each(this.handlers, function(idx, handler) {
            $(document).off(handler[0], handler[1], handler[2]);
        });
    },

    /**
     * Allow to destroy and clean up this module
     */
    destroy: function()
    {
      this.unbindEventHandlers();
      if (typeof this.object.destroy === 'function') {
        this.object.destroy();
      }
      this.object = null;
      this.icinga = null;
      this.prototyp = null;
    }

  };

}(Icinga));