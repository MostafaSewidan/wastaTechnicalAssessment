/*!
 * Documenter 2.0
 * http://rxa.li/documenter
 *
 * Copyright 2011, Xaver Birsak
 * http://revaxarts.com
 *
 */

$(document).ready(function(){
    $("#documenter_nav a").click(function(e) {// bind anchor here
        var target = $(this).attr('href');
       $('html, body').animate({
          scrollTop: ($(target).offset().top)
       }, 1200);
       e.preventDefault();// to prevent default working of anchor;
    });
});

