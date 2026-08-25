var script = document.createElement('script');
script.src = payment_terms.terms_js;
script.type = 'module';
script.onload = async function () {
    for (const item of payment_terms.data) {
        const terms = await createTerms(item.env, item.payment);
        terms.mount('.container_terms_' + item.payment_key);
    }
};
document.head.appendChild(script);
