

/*jQuery.noConflict();*/



$(document).ready(function() {
	$('.bannerSlider').slick({
		dots: false,
		infinite: true,
		speed: 500,
		slidesToShow: 1,
		slidesToScroll: 1,
		prevArrow: '.sliderControl.prev',
   		nextArrow: '.sliderControl.next'
	});
	
});

$(document).ready(function(){
  $('.single-item').slick({
    dots: true,
    infinite: true,
    speed: 300,
    slidesToShow: 1,
	autoplay:true,
    slidesToScroll: 1
  });
});

$(document).ready(function () {
	
        var viralhtml = $('.accountRight.responsiveShopingCart').html();
	 if ($(window).width() < 768) {
             $('.accountRight.desktopviewShoppingCart').html('');
             $('.accountRight.desktopviewShoppingCart').addClass('hide');
               $('.accountRight.responsiveShopingCart').html(viralhtml);
             
	}else{
            $('.accountRight.desktopviewShoppingCart').html(viralhtml);
            $('.accountRight.desktopviewShoppingCart').removeClass('hide');
             $('.accountRight.responsiveShopingCart').html('');
        }
});


$(document).ready(function() {

	$('.mobileMenu').click(function(){
        $(".mainNav nav ul").slideToggle('fast');
    });	
});	

/*$(window).scroll(function() {
 if ($(this).scrollTop() > 200){  
    $('.accountRight').addClass("stickycart");
  }
  else{
    $('.accountRight').removeClass("stickycart");
  }
});*/







