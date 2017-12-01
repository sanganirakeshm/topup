$(function() {
  //$('a[href*=#]:not([href=#])').click(function() {
  $('footer .footerMenu a').click(function() {
    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
      if (target.length) {
        $('html,body').animate({
          scrollTop: target.offset().top
        }, 1000);
        return false;
      }
    }
  });
});


$(function() {
    $(window).scroll(function() {
        if($(this).scrollTop() != 0) {
            $('#back-to-top').fadeIn();    
        } else {
            $('#back-to-top').fadeOut();
        }
    });
 
    $('#back-to-top').click(function() {
        $('body,html').animate({scrollTop:0},1500);
    });    
});

$(function() {
    $('.box-body').on('click', '.selectLink', function() {
        
        $('.box-body').find('.selectDropdown').not($(this).next()).slideUp();
        $(this).find('span').removeClass('fa-angle-up').addClass('fa-angle-down');

        $(this).next('.selectDropdown').slideToggle(function() {

	   $(this).prev('.selectLink').toggleClass('opened');
           $('.opened').find('span').removeClass('fa-angle-down').addClass('fa-angle-up');
        });
    });			
});

 $("body").click(function (e) {
    if(!$(e.target).is('.selectLink')) {
        $('.opened').find('span').removeClass('fa-angle-down').addClass('fa-angle-up');
        $('.box-body').find('.selectDropdown').slideUp();
    }	    
  });

$(function () {
$( ".dataTable" ).wrap( "<div class='responsive_table'></div>" );
$( ".datatable" ).wrap( "<div class='responsive_table'></div>" );
});
