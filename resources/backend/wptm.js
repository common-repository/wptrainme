jQuery(document).ready(function($) {
	$('.wptm-sidebar').stickyfloat({ duration: 0 });

	$('.wptm-category-name').next('.wptm-tutorials').hide().end().children('.wptm-category-name-link').click(function(event) {
		event.preventDefault();

		$(this).parent().next('.wptm-tutorials').slideToggle(600);
	});
});
