define(['jquery'], function ($) {
    'use strict';
    $(document).ready(function () {
        const url = new URL(window.location.href);
        const partner = url.searchParams.get("partner");

        if (partner) {
            console.log("Partner found in URL:", partner);
            document.cookie = `partner=${partner}; max-age=86400; path=/`;
        } else {
            console.log("No partner in URL");
        }
    });
});
