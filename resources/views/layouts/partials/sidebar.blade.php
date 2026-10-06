<!-- loader Start -->
    <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body">
                <img src="{{ asset('assets/images/loader.webp') }}" alt="loader" class="image-loader img-fluid" />
            </div>
        </div>
    </div>
<!-- loader END -->
<aside class="sidebar sidebar-base sidebar-white sidebar-default navs-rounded-all" id="first-tour"
    data-toggle="main-sidebar" data-sidebar="responsive">
    <div class="sidebar-header d-flex align-items-center justify-content-start">
        <a href="{{ route('dashboard') }}" class="navbar-brand" wire:navigate>
            <!--Logo start-->
            <div class="logo-main">
                <div class="logo-normal">
                    <img src="{{ asset('images/logo.png') }}" alt="logo" class="icon-30" height="32" width="32" />
                </div>
                <div class="logo-mini">
                    <img src="{{ asset('images/logo.png') }}" alt="logo" class="icon-30" height="32" width="32" />
                </div>
            </div>
            <!--logo End-->
            <h4 class="logo-title">COCOO</h4>
        </a>
        <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
            <i class="icon">
                <svg class="icon-10" width="10" height="10" viewBox="0 0 8 8" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M7.29853 8C7.11974 8 6.94002 7.93083 6.80335 7.79248L3.53927 4.50446C3.40728 4.37085 3.33333 4.18987 3.33333 4.00036C3.33333 3.81179 3.40728 3.63081 3.53927 3.4972L6.80335 0.207279C7.07762 -0.069408 7.52132 -0.069408 7.79558 0.209174C8.06892 0.487756 8.06798 0.937847 7.79371 1.21453L5.02949 4.00036L7.79371 6.78618C8.06798 7.06286 8.06892 7.51201 7.79558 7.79059C7.65892 7.93083 7.47826 8 7.29853 8Z"
                        fill="white" />
                    <path
                        d="M3.96552 8C3.78673 8 3.60701 7.93083 3.47034 7.79248L0.206261 4.50446C0.0742745 4.37085 0.000325203 4.18987 0.000325203 4.00036C0.000325203 3.81179 0.0742745 3.63081 0.206261 3.4972L3.47034 0.207279C3.74461 -0.069408 4.18831 -0.069408 4.46258 0.209174C4.73591 0.487756 4.73497 0.937847 4.4607 1.21453L1.69649 4.00036L4.4607 6.78618C4.73497 7.06286 4.73591 7.51201 4.46258 7.79059C4.32591 7.93083 4.14525 8 3.96552 8Z"
                        fill="white" />
                </svg>
            </i>
        </div>
    </div>
    <div class="sidebar-body pt-0 data-scrollbar">
        <div class="sidebar-list">
            <!-- Sidebar Menu Start -->
            <ul class="navbar-nav iq-main-menu" id="sidebar-menu">
                <li class="nav-item static-item">
                    <a class="nav-link static-item disabled text-start" href="{{ route('dashboard') }}" tabindex="-1" wire:navigate>
                        <span class="default-icon">Home</span>
                        <span class="mini-icon" data-bs-toggle="tooltip" title="Home"
                            data-bs-placement="right">-</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="{{ route('dashboard') }}" wire:navigate>
                        <i class="icon" data-bs-toggle="tooltip" title="Dashboard" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4"
                                    d="M16.0756 2H19.4616C20.8639 2 22.0001 3.14585 22.0001 4.55996V7.97452C22.0001 9.38864 20.8639 10.5345 19.4616 10.5345H16.0756C14.6734 10.5345 13.5371 9.38864 13.5371 7.97452V4.55996C13.5371 3.14585 14.6734 2 16.0756 2Z"
                                    fill="currentColor"></path>
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M4.53852 2H7.92449C9.32676 2 10.463 3.14585 10.463 4.55996V7.97452C10.463 9.38864 9.32676 10.5345 7.92449 10.5345H4.53852C3.13626 10.5345 2 9.38864 2 7.97452V4.55996C2 3.14585 3.13626 2 4.53852 2ZM4.53852 13.4655H7.92449C9.32676 13.4655 10.463 14.6114 10.463 16.0255V19.44C10.463 20.8532 9.32676 22 7.92449 22H4.53852C3.13626 22 2 20.8532 2 19.44V16.0255C2 14.6114 3.13626 13.4655 4.53852 13.4655ZM19.4615 13.4655H16.0755C14.6732 13.4655 13.537 14.6114 13.537 16.0255V19.44C13.537 20.8532 14.6732 22 16.0755 22H19.4615C20.8637 22 22 20.8532 22 19.44V16.0255C22 14.6114 20.8637 13.4655 19.4615 13.4655Z"
                                    fill="currentColor"></path>
                            </svg>
                        </i>
                        <span class="item-name">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="./analytics-dashboard.html">
                        <i class="icon" data-bs-toggle="tooltip" title="Analytics" data-bs-placement="right">
                            <svg width="20" height="20" class="icon-20" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M10.1615 3.15833C10.1615 4.639 11.3636 5.83931 12.8464 5.83931C13.0097 5.83851 13.1726 5.82283 13.333 5.79248V11.1076C13.333 13.3437 12.014 14.6666 9.77463 14.6666H4.89725C2.65201 14.6666 1.33301 13.3437 1.33301 11.1076V6.23736C1.33301 4.00126 2.65201 2.66663 4.89725 2.66663H10.2084C10.1769 2.82863 10.1612 2.9933 10.1615 3.15833ZM8.76632 9.93102L10.6716 7.47248V7.46077C10.8347 7.2416 10.793 6.93255 10.5778 6.76419C10.4736 6.68375 10.3411 6.64893 10.2108 6.66768C10.0804 6.68644 9.96324 6.75716 9.88601 6.8637L8.27976 8.93004L6.45074 7.49004C6.34632 7.40869 6.21348 7.37277 6.08221 7.39039C5.95095 7.40802 5.83233 7.47769 5.75313 7.5837L3.78342 10.1242C3.71419 10.2105 3.67691 10.318 3.6779 10.4286C3.66647 10.6521 3.80759 10.855 4.02126 10.9225C4.23493 10.9899 4.46726 10.9048 4.58655 10.7154L6.23384 8.58467L8.06286 10.0188C8.16689 10.1027 8.3006 10.1407 8.43327 10.1242C8.56594 10.1076 8.68617 10.0379 8.76632 9.93102Z"
                                    fill="currentColor" />
                                <ellipse opacity="0.4" cx="12.9997" cy="2.99998" rx="1.66667" ry="1.66667"
                                    fill="currentColor" />
                            </svg>
                        </i>
                        <span class="item-name">Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="./crypto-dashboard.html">
                        <i class="icon" data-bs-toggle="tooltip" title="Crypto" data-bs-placement="right">
                            <svg class="icon-20" width="20" height="20" viewBox="0 0 14 14" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4"
                                    d="M10.1167 0.333496H3.88856C1.61893 0.333496 0.333008 1.61942 0.333008 3.88905V10.1113C0.333008 12.3809 1.61893 13.6668 3.88856 13.6668H10.1167C12.3863 13.6668 13.6663 12.3809 13.6663 10.1113V3.88905C13.6663 1.61942 12.3863 0.333496 10.1167 0.333496Z"
                                    fill="currentColor" />
                                <path
                                    d="M3.91244 5.24609C3.61022 5.24609 3.36133 5.49498 3.36133 5.80313V10.3839C3.36133 10.6861 3.61022 10.935 3.91244 10.935C4.22059 10.935 4.46948 10.6861 4.46948 10.3839V5.80313C4.46948 5.49498 4.22059 5.24609 3.91244 5.24609Z"
                                    fill="currentColor" />
                                <path
                                    d="M7.02279 3.05957C6.72057 3.05957 6.47168 3.30846 6.47168 3.61661V10.384C6.47168 10.6862 6.72057 10.9351 7.02279 10.9351C7.33094 10.9351 7.57983 10.6862 7.57983 10.384V3.61661C7.57983 3.30846 7.33094 3.05957 7.02279 3.05957Z"
                                    fill="currentColor" />
                                <path
                                    d="M10.0932 7.66406C9.78502 7.66406 9.53613 7.91295 9.53613 8.2211V10.3841C9.53613 10.6863 9.78502 10.9352 10.0872 10.9352C10.3954 10.9352 10.6443 10.6863 10.6443 10.3841V8.2211C10.6443 7.91295 10.3954 7.66406 10.0932 7.66406Z"
                                    fill="currentColor" />
                            </svg>
                        </i>
                        <span class="item-name">Crypto</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#horizontal-menu" role="button"
                        aria-expanded="false" aria-controls="horizontal-menu">
                        <i class="icon" data-bs-toggle="tooltip" title="Menu Style" data-bs-placement="right">
                            <svg width="20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg" height="20" class="icon-20">
                                <path opacity="0.4"
                                    d="M22 11.9998C22 17.5238 17.523 21.9998 12 21.9998C6.477 21.9998 2 17.5238 2 11.9998C2 6.47776 6.477 1.99976 12 1.99976C17.523 1.99976 22 6.47776 22 11.9998Z"
                                    fill="currentColor"></path>
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M7.52075 10.8035C6.85975 10.8035 6.32275 11.3405 6.32275 11.9995C6.32275 12.6595 6.85975 13.1975 7.52075 13.1975C8.18175 13.1975 8.71875 12.6595 8.71875 11.9995C8.71875 11.3405 8.18175 10.8035 7.52075 10.8035ZM11.9999 10.8035C11.3389 10.8035 10.8019 11.3405 10.8019 11.9995C10.8019 12.6595 11.3389 13.1975 11.9999 13.1975C12.6609 13.1975 13.1979 12.6595 13.1979 11.9995C13.1979 11.3405 12.6609 10.8035 11.9999 10.8035ZM15.2813 11.9995C15.2813 11.3405 15.8183 10.8035 16.4793 10.8035C17.1403 10.8035 17.6773 11.3405 17.6773 11.9995C17.6773 12.6595 17.1403 13.1975 16.4793 13.1975C15.8183 13.1975 15.2813 12.6595 15.2813 11.9995Z"
                                    fill="currentColor"></path>
                            </svg>
                        </i>
                        <span class="item-name">Menu Style</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="horizontal-menu" data-bs-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="./index-horizontal.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Horizontal"
                                    data-bs-placement="right">
                                    H
                                </i>
                                <span class="item-name"> Horizontal </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./index-dual-compact.html">
                                <i class="icon svg-icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Dual Compact"
                                    data-bs-placement="right">
                                    D
                                </i>
                                <span class="item-name">Dual Compact</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./index-boxed.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Boxed Horizontal"
                                    data-bs-placement="right">
                                    B
                                </i>
                                <span class="item-name">Boxed Horizontal</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <hr class="hr-horizontal" />
                </li>
                <li class="nav-item static-item">
                    <a class="nav-link static-item disabled" href="#" tabindex="-1">
                        <span class="default-icon">Pages</span>
                        <span class="mini-icon">-</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-special" role="button"
                        aria-expanded="false" aria-controls="sidebar-special">
                        <i class="icon" data-bs-toggle="tooltip" title="Spacial Pages" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 14 13" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4"
                                    d="M6.85044 12.5583C5.32841 11.6182 3.91355 10.5097 2.63172 9.25306C1.7268 8.35584 1.0357 7.26031 0.611238 6.05018C-0.147183 3.69015 0.735667 0.992986 3.20054 0.192264C4.50154 -0.216313 5.9165 0.034503 7.00449 0.866556C8.09289 0.0354335 9.50738 -0.215301 10.8085 0.192264C13.2733 0.992986 14.1621 3.69015 13.4037 6.05018C12.9827 7.25917 12.2957 8.3546 11.395 9.25306C10.1121 10.5083 8.69734 11.6167 7.17632 12.5583L7.01042 12.6667L6.85044 12.5583Z"
                                    fill="currentColor" />
                                <path
                                    d="M7.00694 12.6666L6.85033 12.5583C5.32643 11.6183 3.90959 10.5098 2.62569 9.25304C1.71648 8.35707 1.02126 7.26144 0.593354 6.05016C-0.159142 3.69013 0.723708 0.992966 3.18858 0.192245C4.48958 -0.216332 5.9232 0.0346753 7.00694 0.873695V12.6666Z"
                                    fill="currentColor" />
                                <path
                                    d="M11.153 4.66615C11.0191 4.65753 10.8943 4.5906 10.8081 4.48104C10.7218 4.37149 10.6815 4.22893 10.6966 4.08677C10.7111 3.61854 10.4447 3.19234 10.0338 3.02651C9.7733 2.95539 9.61553 2.67324 9.6807 2.39499C9.74255 2.12121 9.99891 1.95098 10.2566 2.0126C10.2891 2.018 10.3204 2.02979 10.3489 2.04737C11.1727 2.36439 11.7061 3.2176 11.6637 4.1505C11.6623 4.2919 11.6072 4.42665 11.5109 4.52387C11.4146 4.62109 11.2854 4.67244 11.153 4.66615Z"
                                    fill="currentColor" />
                            </svg>
                        </i>
                        <span class="item-name">Special Pages</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="sidebar-special" data-bs-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="./special-pages/billing.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Login"
                                    data-bs-placement="right">B</i>
                                <span class="item-name">Billing </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./special-pages/calendar.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Calendar"
                                    data-bs-placement="right">C</i>
                                <span class="item-name">Calendar</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./special-pages/kanban.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="kanban"
                                    data-bs-placement="right">K</i>
                                <span class="item-name">kanban</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./special-pages/pricing.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Pricing"
                                    data-bs-placement="right">P</i>
                                <span class="item-name">Pricing</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./special-pages/timeline.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Timeline"
                                    data-bs-placement="right">T</i>
                                <span class="item-name">Timeline</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-auth" role="button"
                        aria-expanded="false" aria-controls="sidebar-auth">
                        <i class="icon" data-bs-toggle="tooltip" title="Auth Skins" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4"
                                    d="M12.0865 22C11.9627 22 11.8388 21.9716 11.7271 21.9137L8.12599 20.0496C7.10415 19.5201 6.30481 18.9259 5.68063 18.2336C4.31449 16.7195 3.5544 14.776 3.54232 12.7599L3.50004 6.12426C3.495 5.35842 3.98931 4.67103 4.72826 4.41215L11.3405 2.10679C11.7331 1.96656 12.1711 1.9646 12.5707 2.09992L19.2081 4.32684C19.9511 4.57493 20.4535 5.25742 20.4575 6.02228L20.4998 12.6628C20.5129 14.676 19.779 16.6274 18.434 18.1581C17.8168 18.8602 17.0245 19.4632 16.0128 20.0025L12.4439 21.9088C12.3331 21.9686 12.2103 21.999 12.0865 22Z"
                                    fill="currentColor"></path>
                                <path
                                    d="M11.3194 14.3209C11.1261 14.3219 10.9328 14.2523 10.7838 14.1091L8.86695 12.2656C8.57097 11.9793 8.56795 11.5145 8.86091 11.2262C9.15387 10.9369 9.63207 10.934 9.92906 11.2193L11.3083 12.5451L14.6758 9.22479C14.9698 8.93552 15.448 8.93258 15.744 9.21793C16.041 9.50426 16.044 9.97004 15.751 10.2574L11.8519 14.1022C11.7049 14.2474 11.5127 14.3199 11.3194 14.3209Z"
                                    fill="currentColor"></path>
                            </svg>
                        </i>
                        <span class="item-name">Auth Skins</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="sidebar-auth" data-bs-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="./auth/sign-in.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Login"
                                    data-bs-placement="right">L</i>
                                <span class="item-name">Login</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./auth/sign-up.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Register"
                                    data-bs-placement="right">R</i>
                                <span class="item-name">Register</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./auth/confirm-mail.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Confirm Mail"
                                    data-bs-placement="right">CM</i>
                                <span class="item-name">Confirm Mail</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./auth/lock-screen.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Lock Screen"
                                    data-bs-placement="right">LS</i>
                                <span class="item-name">Lock Screen</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./auth/recoverpw.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Recover Password"
                                    data-bs-placement="right">
                                    RP
                                </i>
                                <span class="item-name">Recover Password</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./auth/two-factor.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Two Factor"
                                    data-bs-placement="right">
                                    TF
                                </i>
                                <span class="item-name">Two Factor</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./auth/account-deactivate.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Account Deactivate"
                                    data-bs-placement="right">
                                    AD
                                </i>
                                <span class="item-name">Account Deactivate</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-user" role="button"
                        aria-expanded="false" aria-controls="sidebar-user">
                        <i class="icon" data-bs-toggle="tooltip" title="Users" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11.9488 14.54C8.49884 14.54 5.58789 15.1038 5.58789 17.2795C5.58789 19.4562 8.51765 20.0001 11.9488 20.0001C15.3988 20.0001 18.3098 19.4364 18.3098 17.2606C18.3098 15.084 15.38 14.54 11.9488 14.54Z"
                                    fill="currentColor"></path>
                                <path opacity="0.4"
                                    d="M11.949 12.467C14.2851 12.467 16.1583 10.5831 16.1583 8.23351C16.1583 5.88306 14.2851 4 11.949 4C9.61293 4 7.73975 5.88306 7.73975 8.23351C7.73975 10.5831 9.61293 12.467 11.949 12.467Z"
                                    fill="currentColor"></path>
                                <path opacity="0.4"
                                    d="M21.0881 9.21923C21.6925 6.84176 19.9205 4.70654 17.664 4.70654C17.4187 4.70654 17.1841 4.73356 16.9549 4.77949C16.9244 4.78669 16.8904 4.802 16.8725 4.82902C16.8519 4.86324 16.8671 4.90917 16.8895 4.93889C17.5673 5.89528 17.9568 7.0597 17.9568 8.30967C17.9568 9.50741 17.5996 10.6241 16.9728 11.5508C16.9083 11.6462 16.9656 11.775 17.0793 11.7948C17.2369 11.8227 17.3981 11.8371 17.5629 11.8416C19.2059 11.8849 20.6807 10.8213 21.0881 9.21923Z"
                                    fill="currentColor"></path>
                                <path
                                    d="M22.8094 14.817C22.5086 14.1722 21.7824 13.73 20.6783 13.513C20.1572 13.3851 18.747 13.205 17.4352 13.2293C17.4155 13.232 17.4048 13.2455 17.403 13.2545C17.4003 13.2671 17.4057 13.2887 17.4316 13.3022C18.0378 13.6039 20.3811 14.916 20.0865 17.6834C20.074 17.8032 20.1698 17.9068 20.2888 17.8888C20.8655 17.8059 22.3492 17.4853 22.8094 16.4866C23.0637 15.9589 23.0637 15.3456 22.8094 14.817Z"
                                    fill="currentColor"></path>
                                <path opacity="0.4"
                                    d="M7.04459 4.77973C6.81626 4.7329 6.58077 4.70679 6.33543 4.70679C4.07901 4.70679 2.30701 6.84201 2.9123 9.21947C3.31882 10.8216 4.79355 11.8851 6.43661 11.8419C6.60136 11.8374 6.76343 11.8221 6.92013 11.7951C7.03384 11.7753 7.09115 11.6465 7.02668 11.551C6.3999 10.6234 6.04263 9.50765 6.04263 8.30991C6.04263 7.05904 6.43303 5.89462 7.11085 4.93913C7.13234 4.90941 7.14845 4.86348 7.12696 4.82926C7.10906 4.80135 7.07593 4.78694 7.04459 4.77973Z"
                                    fill="currentColor"></path>
                                <path
                                    d="M3.32156 13.5127C2.21752 13.7297 1.49225 14.1719 1.19139 14.8167C0.936203 15.3453 0.936203 15.9586 1.19139 16.4872C1.65163 17.4851 3.13531 17.8066 3.71195 17.8885C3.83104 17.9065 3.92595 17.8038 3.91342 17.6832C3.61883 14.9167 5.9621 13.6046 6.56918 13.3029C6.59425 13.2885 6.59962 13.2677 6.59694 13.2542C6.59515 13.2452 6.5853 13.2317 6.5656 13.2299C5.25294 13.2047 3.84358 13.3848 3.32156 13.5127Z"
                                    fill="currentColor"></path>
                            </svg>
                        </i>
                        <span class="item-name">Configuración Usuarios</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="sidebar-user" data-bs-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('profile.edit') }}" wire:navigate>
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="User Profile"
                                    data-bs-placement="right">
                                    P
                                </i>
                                <span class="item-name">Perfil</span>
                            </a>
                        </li>
                        @can('viewAny', \App\Models\Role::class)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('roles.index') }}" wire:navigate>
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Roles y Permisos"
                                    data-bs-placement="right">
                                    R
                                </i>
                                <span class="item-name">Roles y Permisos</span>
                            </a>
                        </li>
                        @endcan
                        @can('viewAny', \App\Models\User::class)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('users.index') }}" wire:navigate>
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="User List"
                                    data-bs-placement="right">
                                    U
                                </i>
                                <span class="item-name">Usuarios</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#utilities-error" role="button"
                        aria-expanded="false" aria-controls="utilities-error">
                        <i class="icon" data-bs-toggle="tooltip" title="Utilities" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4"
                                    d="M11.9912 18.6215L5.49945 21.864C5.00921 22.1302 4.39768 21.9525 4.12348 21.4643C4.0434 21.3108 4.00106 21.1402 4 20.9668V13.7087C4 14.4283 4.40573 14.8725 5.47299 15.37L11.9912 18.6215Z"
                                    fill="currentColor"></path>
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M8.89526 2H15.0695C17.7773 2 19.9735 3.06605 20 5.79337V20.9668C19.9989 21.1374 19.9565 21.3051 19.8765 21.4554C19.7479 21.7007 19.5259 21.8827 19.2615 21.9598C18.997 22.0368 18.7128 22.0023 18.4741 21.8641L11.9912 18.6215L5.47299 15.3701C4.40573 14.8726 4 14.4284 4 13.7088V5.79337C4 3.06605 6.19625 2 8.89526 2ZM8.22492 9.62227H15.7486C16.1822 9.62227 16.5336 9.26828 16.5336 8.83162C16.5336 8.39495 16.1822 8.04096 15.7486 8.04096H8.22492C7.79137 8.04096 7.43991 8.39495 7.43991 8.83162C7.43991 9.26828 7.79137 9.62227 8.22492 9.62227Z"
                                    fill="currentColor"></path>
                            </svg>
                        </i>
                        <span class="item-name">Utilities</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="utilities-error" data-bs-parent="#sidebar-menu">
                        @can('viewAny', \App\Models\ServicePoint::class)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('service-points.index') }}" wire:navigate>
                                <i class="icon" data-bs-toggle="tooltip" title="Error 404"
                                    data-bs-placement="right">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg"><g><circle cx="12" cy="12" r="8" fill="currentColor"></circle></g>
                                    </svg>
                                </i>
                                <span class="item-name">Centro de Costos</span>
                            </a>
                        </li>
                        @endcan
                        <li class="nav-item">
                            <a class="nav-link" href="./errors/error500.html">
                                <i class="icon" data-bs-toggle="tooltip" title="Error 500"
                                    data-bs-placement="right">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <span class="item-name">Error 500</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./errors/maintenance.html">
                                <i class="icon" data-bs-toggle="tooltip" title="Maintenance"
                                    data-bs-placement="right">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <span class="item-name">Maintenance</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-plugins" role="button"
                        aria-expanded="false" aria-controls="sidebar-user">
                        <i class="icon" data-bs-toggle="tooltip" title="Plugins" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4"
                                    d="M12.0865 22C11.9627 22 11.8388 21.9716 11.7271 21.9137L8.12599 20.0496C7.10415 19.5201 6.30481 18.9259 5.68063 18.2336C4.31449 16.7195 3.5544 14.776 3.54232 12.7599L3.50004 6.12426C3.495 5.35842 3.98931 4.67103 4.72826 4.41215L11.3405 2.10679C11.7331 1.96656 12.1711 1.9646 12.5707 2.09992L19.2081 4.32684C19.9511 4.57493 20.4535 5.25742 20.4575 6.02228L20.4998 12.6628C20.5129 14.676 19.779 16.6274 18.434 18.1581C17.8168 18.8602 17.0245 19.4632 16.0128 20.0025L12.4439 21.9088C12.3331 21.9686 12.2103 21.999 12.0865 22Z"
                                    fill="currentColor"></path>
                                <path
                                    d="M11.3194 14.3209C11.1261 14.3219 10.9328 14.2523 10.7838 14.1091L8.86695 12.2656C8.57097 11.9793 8.56795 11.5145 8.86091 11.2262C9.15387 10.9369 9.63207 10.934 9.92906 11.2193L11.3083 12.5451L14.6758 9.22479C14.9698 8.93552 15.448 8.93258 15.744 9.21793C16.041 9.50426 16.044 9.97004 15.751 10.2574L11.8519 14.1022C11.7049 14.2474 11.5127 14.3199 11.3194 14.3209Z"
                                    fill="currentColor"></path>
                            </svg>
                        </i>
                        <span class="item-name">Plugins</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="sidebar-plugins" data-bs-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/apexcharts.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Apexcharts"
                                    data-bs-placement="right">
                                    A
                                </i>
                                <span class="item-name">Apexcharts</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/button-hover.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Button Hover"
                                    data-bs-placement="right">
                                    BH
                                </i>
                                <span class="item-name">Button Hover </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/choise-js.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Choice JS"
                                    data-bs-placement="right">
                                    C
                                </i>
                                <span class="item-name">Choice JS</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/flatpickr.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Flatpickr"
                                    data-bs-placement="right">
                                    F
                                </i>
                                <span class="item-name">Flatpickr </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/fslightbox.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="FSlightbox"
                                    data-bs-placement="right">
                                    F
                                </i>
                                <span class="item-name">FSlightbox </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/gallery-hover.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Gallery Hover"
                                    data-bs-placement="right">
                                    G
                                </i>
                                <span class="item-name">Gallery Hover </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/image-croper.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Image Croper"
                                    data-bs-placement="right">
                                    I
                                </i>
                                <span class="item-name">Image Croper </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/loader.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Loader"
                                    data-bs-placement="right">
                                    L
                                </i>
                                <span class="item-name">Loader </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/rating.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Rating"
                                    data-bs-placement="right">
                                    R
                                </i>
                                <span class="item-name">Rating</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/select2.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Select2"
                                    data-bs-placement="right">
                                    S
                                </i>
                                <span class="item-name">Select2 </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/sweet-alert.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Sweet Alert"
                                    data-bs-placement="right">
                                    S
                                </i>
                                <span class="item-name">Sweet Alert</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/quill-editor.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="quill-editor"
                                    data-bs-placement="right">
                                    T
                                </i>
                                <span class="item-name">Quill</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./plugins/uppy.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Uppy"
                                    data-bs-placement="right">
                                    U
                                </i>
                                <span class="item-name">Uppy</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./blank-page.html">
                        <i class="icon" data-bs-toggle="tooltip" title="Blank Page" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 24 24" fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4"
                                    d="M16.191 2H7.81C4.77 2 3 3.78 3 6.83V17.16C3 20.26 4.77 22 7.81 22H16.191C19.28 22 21 20.26 21 17.16V6.83C21 3.78 19.28 2 16.191 2"
                                    fill="currentColor" />
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M8.07999 6.64999V6.65999C7.64899 6.65999 7.29999 7.00999 7.29999 7.43999C7.29999 7.86999 7.64899 8.21999 8.07999 8.21999H11.069C11.5 8.21999 11.85 7.86999 11.85 7.42899C11.85 6.99999 11.5 6.64999 11.069 6.64999H8.07999ZM15.92 12.74H8.07999C7.64899 12.74 7.29999 12.39 7.29999 11.96C7.29999 11.53 7.64899 11.179 8.07999 11.179H15.92C16.35 11.179 16.7 11.53 16.7 11.96C16.7 12.39 16.35 12.74 15.92 12.74ZM15.92 17.31H8.07999C7.77999 17.35 7.48999 17.2 7.32999 16.95C7.16999 16.69 7.16999 16.36 7.32999 16.11C7.48999 15.85 7.77999 15.71 8.07999 15.74H15.92C16.319 15.78 16.62 16.12 16.62 16.53C16.62 16.929 16.319 17.27 15.92 17.31Z"
                                    fill="currentColor" />
                            </svg>
                        </i>
                        <span class="item-name">Blank Page</span>
                    </a>
                </li>
                <li>
                    <hr class="hr-horizontal" />
                </li>
                <li class="nav-item static-item">
                    <a class="nav-link static-item disabled" href="#" tabindex="-1">
                        <span class="default-icon">Elements</span>
                        <span class="mini-icon" data-bs-toggle="tooltip" title="Elements"
                            data-bs-placement="right">-</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#ui" role="button"
                        aria-expanded="false" aria-controls="utilities-error">
                        <i class="icon" data-bs-toggle="tooltip" title="UI Elements" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4"
                                    d="M13.7505 9.70303V7.68318C13.354 7.68318 13.0251 7.36377 13.0251 6.97859V4.57356C13.0251 4.2532 12.764 4.00049 12.4352 4.00049H5.7911C3.70213 4.00049 2 5.653 2 7.68318V10.1155C2 10.3043 2.07737 10.4828 2.21277 10.6143C2.34816 10.7449 2.53191 10.8201 2.72534 10.8201C3.46035 10.8201 4.02128 11.3274 4.02128 11.9944C4.02128 12.6905 3.45068 13.2448 2.73501 13.2533C2.33849 13.2533 2 13.5257 2 13.9203V16.3262C2 18.3555 3.70213 19.9995 5.78143 19.9995H12.4352C12.764 19.9995 13.0251 19.745 13.0251 19.4265V17.3963C13.0251 17.0027 13.354 16.6917 13.7505 16.6917V14.8701C13.354 14.8701 13.0251 14.5497 13.0251 14.1655V10.4076C13.0251 10.0224 13.354 9.70303 13.7505 9.70303Z"
                                    fill="currentColor"></path>
                                <path
                                    d="M19.9787 11.9948C19.9787 12.69 20.559 13.2443 21.265 13.2537C21.6615 13.2537 22 13.5262 22 13.9113V16.3258C22 18.3559 20.3075 20 18.2186 20H15.0658C14.7466 20 14.4758 19.7454 14.4758 19.426V17.3967C14.4758 17.0022 14.1567 16.6921 13.7505 16.6921V14.8705C14.1567 14.8705 14.4758 14.5502 14.4758 14.1659V10.4081C14.4758 10.022 14.1567 9.70348 13.7505 9.70348V7.6827C14.1567 7.6827 14.4758 7.36328 14.4758 6.9781V4.57401C14.4758 4.25366 14.7466 4 15.0658 4H18.2186C20.3075 4 22 5.64406 22 7.6733V10.0407C22 10.2286 21.9226 10.4081 21.7872 10.5387C21.6518 10.6702 21.4681 10.7453 21.2747 10.7453C20.559 10.7453 19.9787 11.31 19.9787 11.9948Z"
                                    fill="currentColor"></path>
                            </svg>
                        </i>
                        <span class="item-name">UI Elements</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="ui" data-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-avatars.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> A</i>
                                <span class="item-name">Avatars</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-alerts.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> A </i>
                                <span class="item-name">Alerts</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-badges.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> B </i>
                                <span class="item-name">Badge</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-breadcrumb.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> B </i>
                                <span class="item-name">Breadcrumb</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-buttons.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> B </i>
                                <span class="item-name">Buttons</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-buttons-group.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> B </i>
                                <span class="item-name">Buttons Group</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-offcanvas.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> O </i>
                                <span class="item-name">Offcanvas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-color.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> C</i>
                                <span class="item-name">Colors</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-cards.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> C </i>
                                <span class="item-name">Cards</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-carousel.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> C </i>
                                <span class="item-name">Carousel</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-grid.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> G </i>
                                <span class="item-name">Grid</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-images.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> I </i>
                                <span class="item-name">Images</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-list-group.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> L </i>
                                <span class="item-name">List Group</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-modal.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> M </i>
                                <span class="item-name">Modal</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-notifications.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> N </i>
                                <span class="item-name">Notifications</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-pagination.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> P </i>
                                <span class="item-name">Pagination</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-popovers.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> P </i>
                                <span class="item-name">Popovers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-typography.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> T </i>
                                <span class="item-name">Typography</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-tabs.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> T </i>
                                <span class="item-name">Tabs</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-tooltips.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> T </i>
                                <span class="item-name">Tooltips</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./ui-elements/ui-embed-video.html">
                                <i class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon"> V </i>
                                <span class="item-name">Video</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-widget" role="button"
                        aria-expanded="false" aria-controls="sidebar-widget">
                        <i class="icon" data-bs-toggle="tooltip" title="Widgets" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4"
                                    d="M21.25 13.4764C20.429 13.4764 19.761 12.8145 19.761 12.001C19.761 11.1865 20.429 10.5246 21.25 10.5246C21.449 10.5246 21.64 10.4463 21.78 10.3076C21.921 10.1679 22 9.97864 22 9.78146L21.999 7.10415C21.999 4.84102 20.14 3 17.856 3H6.144C3.86 3 2.001 4.84102 2.001 7.10415L2 9.86766C2 10.0648 2.079 10.2541 2.22 10.3938C2.36 10.5325 2.551 10.6108 2.75 10.6108C3.599 10.6108 4.239 11.2083 4.239 12.001C4.239 12.8145 3.571 13.4764 2.75 13.4764C2.336 13.4764 2 13.8093 2 14.2195V16.8949C2 19.158 3.858 21 6.143 21H17.857C20.142 21 22 19.158 22 16.8949V14.2195C22 13.8093 21.664 13.4764 21.25 13.4764Z"
                                    fill="currentColor"></path>
                                <path
                                    d="M15.4303 11.5887L14.2513 12.7367L14.5303 14.3597C14.5783 14.6407 14.4653 14.9177 14.2343 15.0837C14.0053 15.2517 13.7063 15.2727 13.4543 15.1387L11.9993 14.3737L10.5413 15.1397C10.4333 15.1967 10.3153 15.2267 10.1983 15.2267C10.0453 15.2267 9.89434 15.1787 9.76434 15.0847C9.53434 14.9177 9.42134 14.6407 9.46934 14.3597L9.74734 12.7367L8.56834 11.5887C8.36434 11.3907 8.29334 11.0997 8.38134 10.8287C8.47034 10.5587 8.70034 10.3667 8.98134 10.3267L10.6073 10.0897L11.3363 8.61268C11.4633 8.35868 11.7173 8.20068 11.9993 8.20068H12.0013C12.2843 8.20168 12.5383 8.35968 12.6633 8.61368L13.3923 10.0897L15.0213 10.3277C15.2993 10.3667 15.5293 10.5587 15.6173 10.8287C15.7063 11.0997 15.6353 11.3907 15.4303 11.5887Z"
                                    fill="currentColor"></path>
                            </svg>
                        </i>
                        <span class="item-name">Widgets</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="sidebar-widget" data-bs-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="./widget/widgetbasic.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Widget Basic"
                                    data-bs-placement="right">
                                    WB
                                </i>
                                <span class="item-name">Widget Basic</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./widget/widgetchart.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Widget Chart"
                                    data-bs-placement="right">
                                    WC
                                </i>
                                <span class="item-name">Widget Chart</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./widget/widgetcard.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Widget Card"
                                    data-bs-placement="right">
                                    WC
                                </i>
                                <span class="item-name">Widget Card</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-maps" role="button"
                        aria-expanded="false" aria-controls="sidebar-maps">
                        <i class="icon" data-bs-toggle="tooltip" title="Maps" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M8.53162 2.93677C10.7165 1.66727 13.402 1.68946 15.5664 2.99489C17.7095 4.32691 19.012 6.70418 18.9998 9.26144C18.95 11.8019 17.5533 14.19 15.8075 16.0361C14.7998 17.1064 13.6726 18.0528 12.4488 18.856C12.3228 18.9289 12.1848 18.9777 12.0415 19C11.9036 18.9941 11.7693 18.9534 11.6508 18.8814C9.78243 17.6746 8.14334 16.134 6.81233 14.334C5.69859 12.8314 5.06584 11.016 5 9.13442C4.99856 6.57225 6.34677 4.20627 8.53162 2.93677ZM9.79416 10.1948C10.1617 11.1008 11.0292 11.6918 11.9916 11.6918C12.6221 11.6964 13.2282 11.4438 13.6748 10.9905C14.1214 10.5371 14.3715 9.92064 14.3692 9.27838C14.3726 8.29804 13.7955 7.41231 12.9073 7.03477C12.0191 6.65723 10.995 6.86235 10.3133 7.55435C9.63159 8.24635 9.42664 9.28872 9.79416 10.1948Z"
                                    fill="currentColor"></path>
                                <ellipse opacity="0.4" cx="12" cy="21" rx="5"
                                    ry="1" fill="currentColor"></ellipse>
                            </svg>
                        </i>
                        <span class="item-name">Maps</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="sidebar-maps" data-bs-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="./maps/google.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Google"
                                    data-bs-placement="right">
                                    G
                                </i>
                                <span class="item-name">Google</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./maps/vector.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Vector"
                                    data-bs-placement="right">
                                    V
                                </i>
                                <span class="item-name">Vector</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-form" role="button"
                        aria-expanded="false" aria-controls="sidebar-form">
                        <i class="icon" data-bs-toggle="tooltip" title="Form" data-bs-placement="right">
                            <svg width="20" class="icon-20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.4"
                                    d="M16.191 2H7.81C4.77 2 3 3.78 3 6.83V17.16C3 20.26 4.77 22 7.81 22H16.191C19.28 22 21 20.26 21 17.16V6.83C21 3.78 19.28 2 16.191 2Z"
                                    fill="currentColor"></path>
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M8.07996 6.6499V6.6599C7.64896 6.6599 7.29996 7.0099 7.29996 7.4399C7.29996 7.8699 7.64896 8.2199 8.07996 8.2199H11.069C11.5 8.2199 11.85 7.8699 11.85 7.4289C11.85 6.9999 11.5 6.6499 11.069 6.6499H8.07996ZM15.92 12.7399H8.07996C7.64896 12.7399 7.29996 12.3899 7.29996 11.9599C7.29996 11.5299 7.64896 11.1789 8.07996 11.1789H15.92C16.35 11.1789 16.7 11.5299 16.7 11.9599C16.7 12.3899 16.35 12.7399 15.92 12.7399ZM15.92 17.3099H8.07996C7.77996 17.3499 7.48996 17.1999 7.32996 16.9499C7.16996 16.6899 7.16996 16.3599 7.32996 16.1099C7.48996 15.8499 7.77996 15.7099 8.07996 15.7399H15.92C16.319 15.7799 16.62 16.1199 16.62 16.5299C16.62 16.9289 16.319 17.2699 15.92 17.3099Z"
                                    fill="currentColor"></path>
                            </svg>
                        </i>
                        <span class="item-name">Form</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="sidebar-form" data-bs-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="./form/form-element.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Elements"
                                    data-bs-placement="right">
                                    E
                                </i>
                                <span class="item-name">Elements</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./form/form-wizard.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Wizard"
                                    data-bs-placement="right">
                                    W
                                </i>
                                <span class="item-name">Wizard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./form/form-validation.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Validation"
                                    data-bs-placement="right">
                                    V
                                </i>
                                <span class="item-name">Validation</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-table" role="button"
                        aria-expanded="false" aria-controls="sidebar-table">
                        <i class="icon" data-bs-toggle="tooltip" title="Table" data-bs-placement="right">
                            <svg class="icon-20" xmlns="http://www.w3.org/2000/svg" width="20"
                                viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M2 5C2 4.44772 2.44772 4 3 4H8.66667H21C21.5523 4 22 4.44772 22 5V8H15.3333H8.66667H2V5Z"
                                    fill="currentColor" stroke="currentColor" />
                                <path
                                    d="M6 8H2V11M6 8V20M6 8H14M6 20H3C2.44772 20 2 19.5523 2 19V11M6 20H14M14 8H22V11M14 8V20M14 20H21C21.5523 20 22 19.5523 22 19V11M2 11H22M2 14H22M2 17H22M10 8V20M18 8V20"
                                    stroke="currentColor" />
                            </svg>
                        </i>
                        <span class="item-name">Table</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="sidebar-table" data-bs-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="./table/bootstrap-table.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Bootstrap Table"
                                    data-bs-placement="right">
                                    BT
                                </i>
                                <span class="item-name">Bootstrap Table</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./table/table-data.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Datatable"
                                    data-bs-placement="right">
                                    D
                                </i>
                                <span class="item-name">Datatable</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./table/border-table.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Bordered Table"
                                    data-bs-placement="right">
                                    BT
                                </i>
                                <span class="item-name">Bordered Table</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./table/fancy-table.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Fancy Table"
                                    data-bs-placement="right">
                                    F
                                </i>
                                <span class="item-name">Fancy Table</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item mb-4">
                    <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-icons" role="button"
                        aria-expanded="false" aria-controls="sidebar-icons">
                        <i class="icon" data-bs-toggle="tooltip" title="Icons" data-bs-placement="right">
                            <svg class="icon-20" xmlns="http://www.w3.org/2000/svg" width="20"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M8 10.5378C8 9.43327 8.89543 8.53784 10 8.53784H11.3333C12.4379 8.53784 13.3333 9.43327 13.3333 10.5378V19.8285C13.3333 20.9331 14.2288 21.8285 15.3333 21.8285H16C16 21.8285 12.7624 23.323 10.6667 22.9361C10.1372 22.8384 9.52234 22.5913 9.01654 22.3553C8.37357 22.0553 8 21.3927 8 20.6832V10.5378Z"
                                    fill="currentColor" />
                                <rect opacity="0.4" x="8" y="1" width="5" height="5" rx="2.5"
                                    fill="currentColor" />
                            </svg>
                        </i>
                        <span class="item-name">Icons</span>
                        <i class="right-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </i>
                    </a>
                    <ul class="sub-nav collapse" id="sidebar-icons" data-bs-parent="#sidebar-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="./icons/solid.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Solid"
                                    data-bs-placement="right">
                                    S
                                </i>
                                <span class="item-name">Solid</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./icons/outline.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Outlined"
                                    data-bs-placement="right">
                                    O
                                </i>
                                <span class="item-name">Outlined</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./icons/dual-tone.html">
                                <i class="icon">
                                    <svg class="icon-10" width="10" viewBox="0 0 24 24" fill="currentColor"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor">
                                            </circle>
                                        </g>
                                    </svg>
                                </i>
                                <i class="sidenav-mini-icon" data-bs-toggle="tooltip" title="Dual Tone"
                                    data-bs-placement="right">
                                    D
                                </i>
                                <span class="item-name">Dual Tone</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <!-- Sidebar Menu End -->
        </div>
    </div>
    <div class="sidebar-footer"></div>
</aside>
