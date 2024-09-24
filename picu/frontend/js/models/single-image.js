var picu = picu || {};

picu.singleImage = Backbone.Model.extend({

	defaults: {
		number: '1',
		imageID: '',
		title: 'image title',
		description: 'image description',
		imagePath: 'images/placeholder.jpg',
		imagePath_small: 'images/placeholder.jpg',
		size: '(min-width: 499px) 340px, 468px',
		sizeLandscape: '100vw',
		orientation: 'landscape',
		selected: false,
		markers: [],
		lazyloaded: false,
	}

});