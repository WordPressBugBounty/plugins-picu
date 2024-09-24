var picu = picu || {};

picu.ApprovedView = Backbone.View.extend({

	tagName: 'div',
	className: 'picu-modal',
	id: 'picu-approved',

	template: _.template( jQuery( "#picu-approved" ).html() ),

	initialize: function( options ) {
		this.title = options.title;
	},

	render: function() {
		var approvedTemplate = this.template({title: this.title});
		this.$el.html( approvedTemplate );
		return this;
	}

});