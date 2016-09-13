/*jshint -W054 */

(function($) {

//Workaround for indexOf in IE 7&8
if (!Array.prototype.indexOf){
	Array.prototype.indexOf = function(elt /*, from*/){
		var len = this.length;

		var from = Number(arguments[1]) || 0;
		from = (from < 0) ? Math.ceil(from) : Math.floor(from);

		if ( from < 0 )
			from += len;

		for (; from < len; from++ ){
			if ( from in this && this[from] === elt )
				return from;
		}
		return -1;
	};
}

function EOPosterBoard ($el, options) {
    this.$el = $el;
		this.template = this._createTemplateFunction( options.template );
		this.page = 0;
		this.url = options.url;
		this.i18n = {
			loading: options.loading,
			load_more: options.load_more
		};
		this.query = options.query;
		this.reversed = options.reversed;
		if ( this.reversed ){
			this.$el.addClass( 'eo-event-board-reversed' );
		}
}

EOPosterBoard.prototype.init = function() {

	$container = this.$el.find('.eo-event-board-items');
	var width = $container.width();

	this.setMoreText( this.i18n.loading );

	$container.masonry({
		isFitWidth: true,
		itemSelector : '.eo-eb-event-box',
		isAnimatedFromBottom: true,
		isAnimated: true,
		singleMode: true,
		layoutPriorities: {
			shelfOrder: 0
		}
	});

	this.fetchEvents();

};

EOPosterBoard.prototype.fetchEvents = function() {
	this.page++;
	var self = this;
	var $container = this.$el.find('.eo-event-board-items');

	$.ajax({
		url: this.url,
		dataType: 'json',
		data:{
			action: 'eventorganiser-posterboard',
			page: this.page,
			query: this.query
		}
	}).done(function ( events ) {

		self.addEvents( events );

		//If there are less than query.posts_per_page events, then we won't need this...
		if( events.length < self.query.posts_per_page ){
			self.$el.find('.eo-event-board-more').hide();
		}

		self.setMoreText( self.i18n.load_more );

		var activeFilters = self.getActiveFilters();
		if( activeFilters.length > 0 ){
			if ( self.reversed ) {
				$hide = $container.find( '.'+activeFilters.join(', .') );
			} else {
				$hide = self.$el.find( '.eo-eb-event-box' ).not( '.'+activeFilters.join('.') );
			}
			$hide.css({'visibility': 'hidden', 'display': 'none'})
				.removeClass("eo-eb-event-box masonry-brick masonry-brick")
				.addClass('eo-eb-event-box-hidden');
		}
	});
};

EOPosterBoard.prototype.addEvents = function( events ) {
	$container = this.$el.find('.eo-event-board-items');
	var html = '';
	for( var i=0; i< events.length; i++ ){
		var event = events[i];
		html += this.template( event );
	}
	$container.append( $(html) ).masonry( 'appended', $(html), true );
	$container.imagesLoaded( function() {
		$container.masonry( 'reload' );
	});
};

EOPosterBoard.prototype.hook = function() {
    this.$el.find('.eo-event-board-more').click( $.proxy( this._onClickMore, this ) );
		this.$el.find('.eo-eb-filter').click( $.proxy( this._onToggleFilter, this ) );
};

EOPosterBoard.prototype.setMoreText = function( text ) {
	this.$el.find('.eo-event-board-more').text( text );
};

EOPosterBoard.prototype.getActiveFilters = function() {
	var activeFilters = this.$el.find('.eo-event-board-filters').data('filters').split(',');
	if( activeFilters.length == 1 && activeFilters[0] === "" ) {
		activeFilters = [];
	}
	return activeFilters;
};

/**
 * An active filter means - oddly - removing events of that type
 */
EOPosterBoard.prototype.toggleFilter = function( filter ) {

	var activeFilters = this.getActiveFilters();

	var index = activeFilters.indexOf( filter );
	var isActive = ( index > -1 );

	if( ! isActive ) {
		//Add filter
		activeFilters.push( filter );

		if ( this.reversed ) {
			//Apply filter by hiding all elements of that class
			$hide = this.$el.find( '.'+filter );
		} else {
			$hide = this.$el.find( '.eo-eb-event-box' ).not( '.'+activeFilters.join('.') );
		}

		$hide.css({'visibility': 'hidden', 'display': 'none'})
			.removeClass("eo-eb-event-box masonry-brick masonry-brick")
			.addClass('eo-eb-event-box-hidden');

	} else {

		//Remove filter
		activeFilters.splice(index, 1);
		$.grep(activeFilters,function(n){ return(n); });

		// Filter is currently active. By deactivating the filter we now show
		// events of that type, unless it is hidden by another applied filter.

		if( activeFilters.length > 0 ){

			if ( this.reversed ) {
				//Apply filter by hiding all elements of that class
				$show = this.$el.find( '.'+filter).not( '.'+activeFilters.join(', .') );
			} else {
				$show = this.$el.find( '.eo-eb-event-box-hidden.'+activeFilters.join('.') );
			}

			$show.css({'visibility': 'visible', 'display': 'block'})
				.addClass("eo-eb-event-box masonry-brick masonry-brick")
				.removeClass('eo-eb-event-box-hidden');

		}else{
			//If this filter was the last active one. Just show all the events
			this.$el.find( '.eo-eb-event-box-hidden' )
				.css({'visibility': 'visible', 'display': 'block'})
				.addClass("eo-eb-event-box masonry-brick masonry-brick")
				.removeClass('eo-eb-event-box-hidden');
		}
	}

	//Update dom data
	this.$el.find('.eo-event-board-filters').data('filters', activeFilters.join(','));

	//Toggle the class of the filter
	var filterClass = filter.replace( 'eo-eb-', 'eo-eb-filter-' );
	this.$el.find( '.eo-event-board-filters .'+filterClass ).toggleClass( 'eo-eb-filter-on', ! isActive );

	this.$el.find('.eo-event-board-items').masonry('reloadItems').masonry('layout');
};

EOPosterBoard.prototype._onClickMore = function() {
	this.fetchEvents();
};

EOPosterBoard.prototype._onToggleFilter = function( ev ) {
	ev.preventDefault();

	var $filter = $(ev.target);
	var type = $filter.data('filter-type');
	var value = $filter.data(type);
	var filter = 'eo-eb-'+type + '-' + value;
	this.toggleFilter( filter );
};

EOPosterBoard.prototype._createTemplateFunction = function( text ) {

	var escaper = /\\|'|\r|\n|\t|\u2028|\u2029/g;

	var settings = {
		evaluate    : /<%([\s\S]+?)%>/g,
		interpolate : /<%=([\s\S]+?)%>/g,
		escape      : /<%-([\s\S]+?)%>/g
	};
	var escapes = {
		"'":      "'",
		'\\':     '\\',
		'\r':     'r',
		'\n':     'n',
		'\t':     't',
		'\u2028': 'u2028',
		'\u2029': 'u2029'
	};

	var render;

	//Combine delimiters into one regular expression via alternation.
	var matcher = new RegExp([
    (settings.escape ).source,
    (settings.interpolate ).source,
    (settings.evaluate ).source
    ].join('|') + '|$', 'g');

	//Compile the template source, escaping string literals appropriately.
	var index = 0;
	var source = "__p+='";
	text.replace(matcher, function(match, escape, interpolate, evaluate, offset) {
		source += text.slice(index, offset).replace(escaper, function(match) { return '\\' + escapes[match]; });

		if (escape) {
			source += "'+\n((__t=(" + escape + "))==null?'':_.escape(__t))+\n'";
		}
		if (interpolate) {
			source += "'+\n((__t=(" + interpolate + "))==null?'':__t)+\n'";
		}
		if (evaluate) {
			source += "';\n" + evaluate + "\n__p+='";
		}
		index = offset + match.length;
		return match;
	});

	source += "';\n";

	//If a variable is not specified, place data values in local scope.
	if (!settings.variable) source = 'with(obj||{}){\n' + source + '}\n';

	source = "var __t,__p='',__j=Array.prototype.join," +
		"print=function(){__p+=__j.call(arguments,'');};\n" +
		source + "return __p;\n";

	try {
		render = new Function(settings.variable || 'obj', '_', source);
	} catch (e) {
		e.source = source;
		throw e;
	}

	var template = function( data ) {
		return render.call( this, data );
	};

	return template;
};

$(document).ready(function () {
	$('.eo-event-board').each(function() {
		var id = $(this).data('board');
		var options = window['eo_posterboard_' + id];
		var posterboard = new EOPosterBoard( $(this), {
			reversed: options.reversed,
			template: options.template,
			query: options.query,
			loading: options.loading,
			load_more: options.load_more,
			url: options.url
		});
		posterboard.hook();
		posterboard.init();
	} );
});

})( jQuery );
