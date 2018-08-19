$(function() {

	function revealOnScroll() {
		var scrolled = $(window).scrollTop();
		$(".jion>.mui-row>.mui-col-xs-10 img").each(function() {
			var current = $(this),
				w_height = $(window).outerHeight(),
				offsetTop = current.offset().top;
			if(scrolled + w_height > +0 + offsetTop) {
				current.addClass("animated rubberBand");
			} else {
				current.removeClass("animated bounceInDown");
			}
		});
		$(".join_Recruit img,.jion_Offering img").each(function() {
			var current = $(this),
				w_height = $(window).outerHeight(),
				offsetTop = current.offset().top;
			if(scrolled + w_height > +0 + offsetTop) {
				current.addClass("animated bounceInDown");
			} else {
				current.removeClass("animated bounceInDown");
			}
		});
		$(".jion_title img").each(function() {
			var current = $(this),
				w_height = $(window).outerHeight(),
				offsetTop = current.offset().top;
			if(scrolled + w_height > +0 + offsetTop) {
				current.addClass("animated rubberBand");
			} else {
				current.removeClass("animated rubberBand");
			}
		});
		
		$(".jion .mui-col-xs-12 img").each(function() {
			var current = $(this),
				w_height = $(window).outerHeight(),
				offsetTop = current.offset().top;
			if(scrolled + w_height > +0 + offsetTop) {
				current.addClass("animated rubberBand");
			} else {
				current.removeClass("animated rubberBand");
			}
		});
	}
	$(window).on("scroll", revealOnScroll);

})