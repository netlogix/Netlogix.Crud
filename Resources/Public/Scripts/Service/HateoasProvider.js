/*global angular:false *//* jshint maxstatements:false */
(function(window, angular, undefined) {
	'use strict';

	var module = angular.module('netlogix.crud.service.hateoassprovider', []);

	/**
	 * FIXME: Make a "global singleton"
	 *
	 * This feels unhandy, since the $httpProvider isn't a scope instance but a global
	 * singleton. So adjusting the httpProvider needs to be done only once. Currently
	 * every package requiring the hateoassprovider adds the interceptor again.
	 */

	module.config(['$httpProvider', function($httpProvider) {

		/*
		 * HATEOAS!
		 *
		 * We need to avoid fixed URI strings inside our RESTfull client, besides the entry
		 * point which needs to be known.
		 *
		 * This interceptor does make it possible to use mechanism which is comparable to the
		 * $resource syntax containing variables such as "http://localhost/rest/:identifier".
		 * The syntax difference is that we use "http://localhost/rest/{identifier}" instead,
		 * where "{identifier}" is based on every POST property.
		 *
		 * But that's not completely the goal here. We don't want to have templated URLs at
		 * all but those URLs that completely rely on payload properties. So schema and path
		 * are not intended to be used, although it does work.
		 *
		 * The $resource syntax "http://localhost/rest/:identifier" form templated URLs doesn't
		 * completely fit, since the PUT and DELETE requests as well as the "singleView" GET
		 * request might be required to be made against a completely different URL than the
		 * "listView" GET url.
		 * This is due to the fact that HATEOAS allows for completely non predictable URLs as
		 * references as well.
		 *
		 * The resource URI needs to be part of the server response code because the resource
		 * itself it actual payload, not part of the protocol and not part of the implementation.
		 *
		 * @see: http://en.wikipedia.org/wiki/HATEOAS
		 *   > A REST client enters a REST application through a simple fixed URL. All future
		 *   > actions the client may take are discovered within resource representations
		 *   > returned from the server.
		 */
		$httpProvider.interceptors.push(function() {
			return {
				request: function(config) {
					var propertyName;

					/**
					 * Piping stuff through toJson|fromJson decouples everything from
					 * referenced variables since a string as intermediate transport is used.
					 */
					angular.forEach(['data', 'params'], function(dataOrParams) {
						if (config[dataOrParams] !== undefined) {
							config[dataOrParams] = removeAnchorContentSuffixesRecursively(config[dataOrParams]);
							config[dataOrParams] = angular.fromJson(angular.toJson(config[dataOrParams]));
						}
					});

					angular.forEach(config.url.match(/\{[^}]*\}/g), function(pattern) {
						propertyName = pattern.replace(/{(.*)}/, '$1');

						angular.forEach(['data', 'params'], function(dataOrParams) {
							if (config[dataOrParams] !== undefined && config[dataOrParams][propertyName] !== undefined) {
								config.url = config.url.replace(pattern, config[dataOrParams][propertyName]);
								delete config[dataOrParams][propertyName];
								if (angular.equals({}, config[dataOrParams])) {
									delete config[dataOrParams];
								}
							}
						});
					});

					return config;
				}
			};
		});

	}]);

	/**
	 * The API representation means that some properties are resource URIs,
	 * such as "author".
	 * Additionally, sometimes the target value of this resource comes as
	 * an embedded value, which would be "author#content" in this case.
	 *
	 * The UriPointer mechanism transforms the "author" string to the actual
	 * target value, no matter if the target has been embedded with the
	 * "#content" suffix or loaded afterwards. In both cases the "author"
	 * property gets a UriPointer object, which needs to be reverse
	 * transferred to the URI string to make the server side happy.
	 *
	 * @param input
	 * @returns {*}
	 */
	function removeAnchorContentSuffixesRecursively(input) {

		if (angular.isFunction(input) || (!angular.isObject(input) && !angular.isArray(input))) {
			return input;
		}
		var remove = [], contentKey, resourceKey;

		angular.forEach(input, function(value, key) {
			if (key.match(/^([^#]+)#content$/g)) {
				remove.push(key);
			}
		});

		while (remove.length) {
			contentKey = remove.pop();
			if (angular.isFunction(input[contentKey].toString)) {
				resourceKey = contentKey.replace(/^([^#]+)#content$/g, '$1');
				input[resourceKey] = input[resourceKey].toString();
			}
			delete input[contentKey];
		}

		return input;
	}

}(window, angular));
