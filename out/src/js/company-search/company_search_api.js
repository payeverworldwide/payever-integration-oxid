/**
 * @typedef callbackOptions
 * @type {Object}
 * @property {function} showLoadingIndicator
 * @property {function} hideLoadingIndicator
 * @property {function} success
 * phpcs:ignoreFile
 */

if ("undefined" === typeof PayeverApiClient) {
    var PayeverApiClient = class {
        constructor() {
            /**
             * API Endpoint to search for a address
             * @type {string}
             * @private
             */
            this.companyFindEndpoint = '/index.php';
        }

        /**
         * This method can be used to find suggestions for the given value or get suggestions of a cluster
         *
         * @param term{string}
         * @param country{string}
         * @param callbackOptions{callbackOptions}
         * @return {XMLHttpRequest}
         */
        companyFind(term, country, callbackOptions) {
            const data = new URLSearchParams({
                cl: 'payevercompanysearch',
                fnc: 'search',
                term,
                country,
            });

            return this._call(this.companyFindEndpoint + '?' + data.toString(), callbackOptions);
        }

        /**
         * This method can be used to find suggestions for the given value or get suggestions of a cluster
         *
         * @param value{string}
         * @param type{string}
         * @param country{string}
         * @param callbackOptions{callbackOptions}
         * @return {XMLHttpRequest}
         */
        companyRetrieve(value, type, country = '', callbackOptions) {
            const data = new URLSearchParams({
                cl: 'payevercompanysearch',
                fnc: 'retrieve',
                value,
                type,
                country
            });

            return this._call(this.companyFindEndpoint + '?' + data.toString(), callbackOptions);
        }

        /**
         * @param item
         * @param callbackOptions
         */
        addressRetrieve(item, callbackOptions) {
            return this._handleCallResponse(item, callbackOptions);
        }

        _encode(string) {
            string = string.replace('/', '-')
            return encodeURIComponent(string);
        }

        /**
         * Call the StoreApi Endpoint
         *
         * @param endpointUrl{string}
         * @param callbackOptions{callbackOptions}
         * @return {XMLHttpRequest}
         * @private
         */
        _call(endpointUrl, callbackOptions) {
            if (this.abortController) {
                this.abortController.abort('New search started');
            }

            this.abortController = new AbortController();

            const init = {
                headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                signal: this.abortController.signal
            };

            return fetch(endpointUrl, init)
                .then((res) => res.json())
                .then((res) => this._handleCallResponse(res, callbackOptions));
        }

        /**
         * Handle the response from the validation api
         *
         * @param response
         * @param callbackOptions{callbackOptions}
         * @private
         * @return void
         */
        _handleCallResponse(response, callbackOptions) {
            try {
                if (!response) {
                    return;
                }

                // Do nothing if there is no response. This can happen, when the request is cancled by _abortValidation()
                if (!response) {
                    throw new Error('Missing response.');
                }

                // Hide loading indicator
                if (callbackOptions.hideLoadingIndicator) {
                    callbackOptions.hideLoadingIndicator();
                }

                // API (Payever Service) respond with error
                if (response.error) {
                    throw new Error('API respond with error. Look in your log files (var/log) for more information.');
                }

                // The XHR request returned a error
                if (response.errors && response.errors.length > 0) {
                    const statusCode = parseInt(response.errors[0].status, 10);

                    if (statusCode === 404) {
                        // If api return a 404 status code the value does not match a mail address
                        return;
                    }
                    if (statusCode !== 200) {
                        // If api return an unknown status code (e.g. 500) we will set the field to valid
                        throw new Error('API respond with error. Look in your log files (var/log) for more information.');
                    }
                }

                // Process correct api response
                if (callbackOptions.success) {
                    callbackOptions.success(response);
                }
            } catch (e) {
                /* eslint-disable no-console */
                console.log('Error in PayeverApiClient::_handleCallResponse', e);
                /* eslint-enable no-console */
            }
        }
    }
}

