const initSwiperSliders = () => {

    if (typeof window.Swiper === 'undefined') {
        return;
    }

    // ==================================================
    // QUI Dashboard
    // ==================================================

    const quiDashboard =
        document.querySelector('#qui-dashboard');

    if (
        quiDashboard &&
        !quiDashboard.swiper
    ) {

        const options = {
            centeredSlides: false,
            loop: false,
            slidesPerView: 4,
            autoplay: false,
            spaceBetween: 32,

            breakpoints: {
                320: { slidesPerView: 1 },
                550: { slidesPerView: 1 },
                991: { slidesPerView: 1 },
                1400: { slidesPerView: 1 },
                1500: { slidesPerView: 1 },
                1920: { slidesPerView: 1 },
                2040: { slidesPerView: 1 },
                2440: { slidesPerView: 1 },
            },

            pagination: {
                el: '.swiper-pagination',
            },

            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },

            scrollbar: {
                el: '.swiper-scrollbar',
            },
        };

        let swiper =
            new window.Swiper(
                quiDashboard,
                options
            );

        document.addEventListener(
            'theme_scheme_direction',
            () => {

                swiper?.destroy(
                    true,
                    true
                );

                setTimeout(() => {

                    const element =
                        document.querySelector(
                            '#qui-dashboard'
                        );

                    if (
                        element &&
                        !element.swiper
                    ) {

                        swiper =
                            new window.Swiper(
                                element,
                                options
                            );
                    }

                }, 500);
            }
        );
    }

    // ==================================================
    // TOP Dashboard
    // ==================================================

    const topDashboard =
        document.querySelector('#top-dashboard');

    if (
        topDashboard &&
        !topDashboard.swiper
    ) {

        const options = {
            centeredSlides: false,
            loop: true,
            slidesPerView: 3,
            autoplay: false,
            spaceBetween: 24,

            breakpoints: {
                320: { slidesPerView: 1 },
                550: { slidesPerView: 1 },
                991: { slidesPerView: 2 },
                1400: { slidesPerView: 2 },
                1500: { slidesPerView: 3 },
                1920: { slidesPerView: 3 },
                2040: { slidesPerView: 3 },
                2440: { slidesPerView: 3 },
            },

            pagination: {
                el: '.swiper-pagination-dot',
                clickable: true,
            },

            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },

            scrollbar: {
                el: '.swiper-scrollbar',
            },
        };

        let swiper =
            new window.Swiper(
                topDashboard,
                options
            );

        document.addEventListener(
            'theme_scheme_direction',
            () => {

                swiper?.destroy(
                    true,
                    true
                );

                setTimeout(() => {

                    const element =
                        document.querySelector(
                            '#top-dashboard'
                        );

                    if (
                        element &&
                        !element.swiper
                    ) {

                        swiper =
                            new window.Swiper(
                                element,
                                options
                            );
                    }

                }, 500);
            }
        );
    }
};

export default initSwiperSliders;
