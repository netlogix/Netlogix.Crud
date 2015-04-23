(function (window, angular, undefined) {
	'use strict';

	var module = angular.module('netlogix.crud.service.xhrdebounce', []);
	var XhrDebounce;

	/**
	 * The $httpBackend is wrapped by the xhrDebounce.invoke method.
	 */
	module.config(['$provide', function ($provide) {

		$provide.decorator('$httpBackend', function($delegate) {

			return function(method, url, post, callback, headers, timeout, withCredentials, responseType) {
				return XhrDebounce.invoke($delegate, method, url, post, callback, headers, timeout, withCredentials, responseType);
			};

		});

	}]);

	/**
	 * This initial "run" is required. Otherwise the local-scope xhrDebounce object
	 * isn't available and the $httpBackend runs into trouble.
	 */
	module.run(['nxcrudextbase.XhrDebounce', function(xhrDebounce) {

		XhrDebounce = xhrDebounce;
		return;

	}]);

	/**
	 * The xhrDebounce object knows which URL/method combination need to be debounced
	 * and keeps track of the request stack.
	 */
	module.service('nxcrudextbase.XhrDebounce', ['$timeout', '$q', function($timeout, $q) {

		var timers = {};
		var deferred = $q.defer();
		deferred.resolve('XHR Debounce Reject');

		var xhrDebounce = {
			add: function(url, method, delay) {
				if (method === undefined) {
					xhrDebounce.add(url, 'GET', delay);
					xhrDebounce.add(url, 'POST', delay);
					xhrDebounce.add(url, 'PUT', delay);
					xhrDebounce.add(url, 'DELETE', delay);

				} else {
					var signature = method + url;
					if (timers[signature] == undefined) {
						timers[signature] = {
							stackSize: 0,
							delay: (delay == undefined) ? 500 : delay
						};
					}

				}
			},
			remove: function(url, method) {
				if (method === undefined) {
					xhrDebounce.remove(url, 'GET');
					xhrDebounce.remove(url, 'POST');
					xhrDebounce.remove(url, 'PUT');
					xhrDebounce.remove(url, 'DELETE');

				} else {
					var signature = url + method;
					if (timers[signature] != undefined) {
						delete timers[signature];
					}

				}

			},
			invoke: function($delegate, method, url, post, callback, headers, timeout, withCredentials, responseType) {
				var signature = method + url;

				if (timers[signature] === undefined) {
					$delegate(method, url, post, callback, headers, timeout, withCredentials, responseType);

				} else {
					timers[signature]['stackSize']++;
					$timeout(function() {
						timers[signature]['stackSize']--;
						if (timers[signature]['stackSize'] == 0) {
							$delegate(method, url, post, callback, headers, timeout, withCredentials, responseType);

						} else {
							$delegate(method, url, post, callback, headers, deferred.promise, withCredentials, responseType);

						}
					}, timers[signature]['delay']);

				}

			}
		};
		return xhrDebounce;

	}]);

}(window, angular));