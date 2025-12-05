(function ($) {
    'use strict';

    // Initialize the class on DOM ready
    $(document).ready(() => {
        $("input[name='invadr[oxuser__oxcompany]']").companySearch({
            onlyCountries: window.onlyCountries,
            companySearchType: window.companySearchType,
            defaultCountry: window.defaultCountry,
            form: 'invadr',
            prefix: 'oxuser',
        });
    });

})(jQuery);
