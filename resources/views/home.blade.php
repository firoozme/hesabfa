<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{$setting->title}}</title>

        <!-- Styles / Scripts -->
        <!-- Vendor CSS -->
        <link rel="stylesheet" href="{{ asset('css/animate.min.css?v=2.0') }}">
        <!-- Template CSS -->
        <link rel="stylesheet" href="{{ asset('css/tailwind-built.css?v=2.0') }}">
    </head>
    <body class="bg-white text-body font-body" style="overflow: visible;">
        <div class="main">
            <!--Header-->
            <header class="mt-4 bg-transparent sticky-bar">
                <div class="container bg-transparent">
                    <nav class="flex items-center justify-between py-3 bg-transparent">
                        <a class="text-3xl font-semibold leading-none" href="index.html">
                            <img class="h-10 lazy" src="{{ asset('upload/'.$setting->logo) }}" alt="لوگو">
                        </a>
                        <ul class="hidden lg:flex lg:items-center lg:w-auto lg:space-x-12">
                            <li class="relative pt-4 pb-4 group has-child">
                                <a href="#" class="text-sm font-semibold text-blueGray-600 hover:text-blueGray-500">خانه</a>

                            <li class="pt-4 pb-4">
                                <a class="text-sm font-semibold text-blueGray-600 hover:text-blueGray-500" href="#">درباره ما</a>
                            </li>
                            <li class="pt-4 pb-4">
                                <a class="text-sm font-semibold text-blueGray-600 hover:text-blueGray-500" href="#">خدمات</a>
                            </li>


                            <li class="pt-4 pb-4"><a class="text-sm font-semibold text-blueGray-600 hover:text-blueGray-500" href="#">ارتباط با ما</a></li>
                        </ul>
                        <div class="hidden lg:block">
                            <a class="btn-primary hover-up-2" href="/company">ورود / ثبت نام</a>
                        </div>
                        <div class="lg:hidden">
                            <button class="flex items-center px-3 py-2 text-blue-500 border border-blue-200 rounded navbar-burger hover:text-blue-700 hover:border-blue-300">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <title>منو موبایل</title>
                                    <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"></path>
                                </svg>
                            </button>
                        </div>
                    </nav>
                </div>
            </header>

            <!--Mobile menu-->
            <div class="relative z-50 hidden transition duration-300 navbar-menu">
                <div class="fixed inset-0 opacity-25 navbar-backdrop bg-blueGray-800"></div>
                <nav class="fixed top-0 bottom-0 left-0 flex flex-col w-5/6 max-w-sm px-6 py-6 overflow-y-auto transition duration-300 bg-white border-r">
                    <div class="flex items-center mb-8">
                        <a class="mr-auto text-3xl font-semibold leading-none" href="#">
                            <img class="h-10 lazy" src="{{ asset('upload/'.$setting->logo) }}" alt="">
                        </a>
                        <button class="navbar-close">
                            <svg class="w-6 h-6 cursor-pointer text-blueGray-400 hover:text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div>
                        <ul class="mobile-menu">
                            <li class="mb-1 menu-item-has-children rounded-xl"><span class="menu-expand">+</span>
                                <a class="block p-4 text-sm text-blueGray-500 hover:bg-blue-50 hover:text-blue-500 rounded-xl" href="#">خانه</a>

                            </li>
                            <li class="mb-1 rounded-xl">
                                <a class="block p-4 text-sm text-blueGray-500 hover:bg-blue-50 hover:text-blue-500 rounded-xl" href="about.html">درباره ما</a>
                            </li>

                            <li class="mb-1 menu-item-has-children rounded-xl"><span class="menu-expand">+</span>

                            </li>

                            <li class="mb-1">
                                <a class="block p-4 text-sm text-blueGray-500 hover:bg-blue-50 hover:text-blue-500" href="contact.html">ارتباط با ما</a>
                            </li>
                        </ul>
                        <div class="pt-6 mt-4 border-t border-blueGray-100">
                            <a class="block px-4 py-3 mb-2 text-xs font-semibold leading-none text-center text-blue-500 border border-blue-200 rounded hover:text-blue-700 hover:border-blue-300" href="/company">ورود / ثبت نام</a>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <p class="my-4 text-xs text-blueGray-400">
                            <span>در تماس باشید</span>
                            <a class="text-blue-500 underline hover:text-blue-500" href="#">contact@monst.com</a>
                        </p>
                        <a class="inline-block px-1" href="#">
                            <img src="assets/imgs/icons/facebook-blue.svg" alt="">
                        </a>
                        <a class="inline-block px-1" href="#">
                            <img src="assets/imgs/icons/twitter-blue.svg" alt="">
                        </a>
                        <a class="inline-block px-1" href="#">
                            <img src="assets/imgs/icons/instagram-blue.svg" alt="">
                        </a>
                    </div>
                </nav>
            </div>
            <section class="hero-3">
                <div class="container">
                    <div class="flex flex-wrap items-center -mx-3">
                        <div class="w-full px-3 lg:w-2/5">
                            <div class="max-w-lg mx-auto mb-8 text-center lg:max-w-md lg:mx-0 lg:text-left">
                                <h2 class="mb-4 text-3xl font-bold lg:text-4xl font-heading wow animate__ animate__fadeIn animated" style="visibility: visible; animation-name: fadeIn;">
                                  {{$setting->titr1}}
                                </h2>
                                <p class="leading-relaxed text-blueGray-400 wow animate__ animate__fadeIn animated" style="visibility: visible; animation-name: fadeIn;">ما <strong class="text-blue-500">نمو تک</strong>، یک طراحی خلاقیم و <span class="typewrite d-inline text-brand" data-period="3000" data-type="[&quot;شرکت وب&quot;, &quot;سوشال مارکتینگ&quot; ]"><span class="wrap">سوشال </span></span></p>
                                <p class="mt-3 text-sm leading-relaxed text-blueGray-400 wow animate__ animate__fadeIn animated" style="visibility: visible; animation-name: fadeIn;"> به شما در حداکثر رساندن مدیریت عملیات با دیجیتالی شدن کمک می کنیم </p>
                            </div>
                            <div class="text-center lg:text-left">
                                <a class="block px-8 py-4 mb-4 text-xs font-semibold leading-none tracking-wide text-center text-white bg-blue-500 rounded hover-up-2 sm:inline-block sm:mb-0 sm:mr-3 hover:bg-blue-700 wow animate__ animate__fadeInUp animated" href="#key-features" style="visibility: visible; animation-name: fadeInUp;">تعرفه ها</a>
                                <a class="block px-8 py-4 text-xs font-semibold leading-none text-center bg-white border rounded sm:inline-block hover-up-2 text-blueGray-500 hover:text-blueGray-600 border-blueGray-200 hover:border-blueGray-300 wow animate__ animate__fadeInUp animated" data-wow-delay=".3s" href="#how-we-work" style="visibility: visible; animation-delay: 0.3s; animation-name: fadeInUp;">با چه کسانی همکاری میکنیم؟</a>
                            </div>
                        </div>
                        <div class="w-full px-3 mb-12 lg:w-3/5 lg:mb-0">
                            <div class="flex items-center justify-center lg:h-128">
                                <img class="lg:max-w-lg lazy" src="{{ asset('upload/'.$setting->image) }}" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="">
                <div class="container">
                    <div class="flex flex-wrap justify-between pb-8">
                        <div class="flex w-1/2 py-4 lg:w-auto wow animate__ animate__fadeInUp animated" data-wow-delay=".2s" style="visibility: visible; animation-delay: 0.2s; animation-name: fadeInUp;">
                            <div class="flex items-center justify-center w-12 h-12 text-blue-500 bg-blueGray-50 rounded-xl sm:h-24 sm:w-24">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-2 sm:py-2 sm:ml-6">
                                <span class="font-bold sm:text-2xl font-heading">+ </span><span class="font-bold sm:text-2xl font-heading count">150</span>
                                <p class="text-xs sm:text-base text-blueGray-400">همکاری سالانه</p>
                            </div>
                        </div>
                        <div class="flex w-1/2 py-4 lg:w-auto wow animate__ animate__fadeInUp animated" data-wow-delay=".4s" style="visibility: visible; animation-delay: 0.4s; animation-name: fadeInUp;">
                            <div class="flex items-center justify-center w-12 h-12 text-blue-500 bg-blueGray-50 rounded-xl sm:h-24 sm:w-24">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                </svg>
                            </div>
                            <div class="ml-2 sm:py-2 sm:ml-6">
                                <span class="font-bold sm:text-2xl font-heading">+ </span><span class="font-bold sm:text-2xl font-heading count">57</span><span class="font-bold sm:text-2xl font-heading"> k </span>
                                <p class="text-xs sm:text-base text-blueGray-400">پروژه های تکمیل شده</p>
                            </div>
                        </div>
                        <div class="flex w-1/2 py-4 lg:w-auto wow animate__ animate__fadeInUp animated" data-wow-delay=".6s" style="visibility: visible; animation-delay: 0.6s; animation-name: fadeInUp;">
                            <div class="flex items-center justify-center w-12 h-12 text-blue-500 bg-blueGray-50 rounded-xl sm:h-24 sm:w-24">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                            </div>
                            <div class="ml-2 sm:py-2 sm:ml-6">
                                <span class="font-bold sm:text-2xl font-heading">+ </span><span class="font-bold sm:text-2xl font-heading count">500</span>
                                <p class="text-xs sm:text-base text-blueGray-400">مشتریان خوشحال</p>
                            </div>
                        </div>
                        <div class="flex w-1/2 py-4 lg:w-auto wow animate__ animate__fadeInUp animated" data-wow-delay=".8s" style="visibility: visible; animation-delay: 0.8s; animation-name: fadeInUp;">
                            <div class="flex items-center justify-center w-12 h-12 text-blue-500 bg-blueGray-50 rounded-xl sm:h-24 sm:w-24">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            </div>
                            <div class="ml-2 sm:py-2 sm:ml-6">
                                <span class="font-bold sm:text-2xl font-heading">+ </span><span class="font-bold sm:text-2xl font-heading count">320</span>
                                <p class="text-xs sm:text-base text-blueGray-400">کار با تحقیق و بررسی</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <!--Import Vendor Js-->
        <script src="{{ asset('js/modernizr-3.6.0.min.js') }}"></script>
        <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
        <script src="{{ asset('js/counterup.js') }}"></script>
        <script src="{{ asset('js/smooth.js') }}"></script>
        <script src="{{ asset('js/textType.js') }}"></script>
        <script src="{{ asset('js/mobile-menu.js') }}"></script>
		<a id="scrollUp" href="#top" style="position: fixed; z-index: 2147483647; display: none;"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg></a>


<style type="text/css">.typewrite > .wrap { border-left: 0.05em solid rgba(147, 197, 253)}</style></body>
</html>
