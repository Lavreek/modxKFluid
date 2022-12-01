$('.card-question').on('click', function(event) {
    if (event.currentTarget.className.includes("active"))
        $(this).removeClass("active");
    else
        $(this).toggleClass('active');
});

$(document).ready(function () {
    if ($.cookie('width') == null) {
        $.cookie('width', $(window).width());
        window.location.reload();
    }
    if ($(window).width() != $.cookie('width')) {
        $.cookie('width', $(window).width());
        window.location.reload();
    }
})

$('.btn-collapse').on('click', function () {
    if ($(this).hasClass('img-collapsed')) {

        anime({
            targets: '.btn-collapse',
            translateY: 0,
            easing: 'easeInOutExpo',
            duration: 200
        });

        anime({
            targets: '.collapse-links',
            translateX: 0,
            easing: 'easeInOutExpo',
            duration: 200
        });

        $('.btn-collapse-img').attr('src', '/assets/resources/images/header-burger.svg');
        $(this).removeClass('img-collapsed')
    } else {
        $(this).addClass('img-collapsed');

        anime({
            targets: '.btn-collapse',
            translateY: 125,
            easing: 'easeInOutExpo',
            duration: 200
        });

        anime({
            targets: '.collapse-links',
            translateX: 50,
            easing: 'easeInOutExpo',
            duration: 200
        });

        $('.btn-collapse-img').attr('src', '/assets/resources/images/header-collapse.svg');

    }
});

$("#nav ul li a[href^='#']").on('click', function(e) {
    e.preventDefault();

    var hash = this.hash;

    $('html, body').animate(
        {
            scrollTop: $(hash).offset().top
        }, 500, function()
            {
                window.location.hash = hash;
            }
    );
});

window.addEventListener('scroll', function() {
    let scrollToTop = document.querySelector('.bi-arrow-up-circle-fill');

    if (pageYOffset > 800 && scrollToTop.style.opacity != .8)
        scrollToTop.style.opacity = .8;
    if (pageYOffset < 800 && scrollToTop.style.opacity != 0)
        scrollToTop.style.opacity = 0;
});