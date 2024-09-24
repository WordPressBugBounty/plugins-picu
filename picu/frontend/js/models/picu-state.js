var picu = picu || {};

picu.appState = Backbone.Model.extend({

	defaults: {
		version: 1,
		nonce: false,
		postid: false,
		poststatus: false,
		title: false,
		date: false,
		description: false,
		ajaxurl: false,
		ident: false
	}

});