var picu = picu || {};

picu.GalleryCollection = Backbone.Collection.extend({

    model: picu.singleImage,
    url: '#',

    initialize: function() {

    },

    // Helper function to count selected images
    countSelected: function() {
        return this.where({selected: true}).length;
    }

});