(function (window, angular, undefined) {
	'use strict';

	var app = angular.module('netlogix.crud.service.uripointer', []);

	var getCache = function($cacheFactory) {
		return $cacheFactory.get('GenericUriPointer') || $cacheFactory('GenericUriPointer');
	};

	/**
	 * UriListPointer targets a single target object, like a user.
	 *
	 * It is meant to be an actual object, extending "{}".
	 */
	app.factory('nxcrudextbase.GenericUriPointer', ['$q', '$http', '$cacheFactory', function($q, $http, $cacheFactory) {

		var deferredCache = getCache($cacheFactory);

		/**
		 * The "resource" is the uri string to target the data.
		 * When available during construction, the target data can be passed directly.
		 *
		 * @param resource
		 * @param content
		 * @constructor
		 */
		var GenericUriPointer = function(resource, content, ResourceFactory) {

			this.$$resource = resource || 'about:blank';
			this.$$resourceFactory = angular.isFunction(ResourceFactory) ? ResourceFactory(this.$$resource) : undefined;

			if (content) {
				this.override(content);
			}
		};

		GenericUriPointer.prototype = [];

		GenericUriPointer.prototype.$$initialized = false;

		/**
		 * To use this object as target URL as well, the "toString"
		 * method returns the resource string.
		 *
		 * @returns {string}
		 */
		GenericUriPointer.prototype.toString = function() {
			return this.$$resource;
		};

		/**
		 * Overriding can be done at any time, from both, inside and outside.
		 * Usually the override method gets triggered by the initialize method
		 * right after the remote resource has been fetched, or just inside
		 * of the construct method when pre-fetched data has been passed.
		 *
		 * @param content
		 */
		GenericUriPointer.prototype.override = function(content) {
			throw 'The method "override" is not provided by the "GenericUriPointer".';
		};

		/**
		 * Initializing triggers $http to fetch the resource if necessary.
		 *
		 * @returns {Function|promise}
		 */
		GenericUriPointer.prototype.initialize = function() {
			var $this = this,
				resource = this.toString(),
				deferred = deferredCache.get(resource);

			if (!deferred) {
				deferred = $q.defer();
				deferredCache.put(resource, deferred);
			}
			if (!this.$$initialized) {
				$http.get(resource, {cache: true}).success(function(data) {
					$this.override(data);
				});
			}
			return deferred.promise;
		};

		/**
		 * Factory method.
		 *
		 * Use this to transform a resource string to an UriPointer object, because this
		 * plays nice with the HEATOSProvider which needs to clean up those attributes
		 * right befor sending data.
		 *
		 * @param object
		 * @param property
		 * @param ResourceFactory
		 */
		GenericUriPointer.convertProperty = function(object, property, ResourceFactory) {
			if (angular.isString(object[property])) {
				var containerProperty = property + '#content';
				object[property] = new this(object[property], object[containerProperty], ResourceFactory);
				object[containerProperty] = true;
			}
		};

		return GenericUriPointer;

	}]);

	/**
	 * The UriObjectPointer targets a list of things.
	 *
	 * It is meant to be an array, extending the "[]".
	 */
	app.factory('nxcrudextbase.UriListPointer', ['$q', '$http', '$cacheFactory', 'nxcrudextbase.GenericUriPointer', function($q, $http, $cacheFactory, GenericUriPointer) {

		var deferredCache = getCache($cacheFactory);

		/**
		 * The "resource" is the uri string to target the data.
		 * When available during construction, the target data can be passed directly.
		 *
		 * @param resource
		 * @param content
		 * @constructor
		 */
		var UriListPointer = function(resource, content, ResourceFactory) {

			this.$$resource = resource || 'about:blank';
			this.$$resourceFactory = angular.isFunction(ResourceFactory) ? ResourceFactory(this.$$resource) : undefined;

			if (content) {
				this.override(content);
			}
		};
		UriListPointer.prototype = new GenericUriPointer();

		/**
		 * Overriding can be done at any time, from both, inside and outside.
		 * Usually the override method gets triggered by the initialize method
		 * right after the remote resource has been fetched, or just inside
		 * of the construct method when pre-fetched data has been passed.
		 *
		 * @param content
		 */
		UriListPointer.prototype.override = function(content) {
			var $this = this,
				deferred = deferredCache.get(this.$$resource);
			if (!deferred) {
				deferred = $q.defer();
				deferredCache.put(this.$$resource, deferred);
			}

			while ($this.pop()) {};
			angular.forEach(content, function(value) {
				if (angular.isFunction($this.$$resourceFactory)) {
					value = new $this.$$resourceFactory(value);
				}
				$this.push(value);
			});
			this.$$initialized = true;

			deferred.resolve(this);
		};

		/**
		 * Factory method.
		 *
		 * Use this to transform a resource string to an UriPointer object, because this
		 * plays nice with the HEATOSProvider which needs to clean up those attributes
		 * right befor sending data.
		 *
		 * @param object
		 * @param property
		 */
		UriListPointer.convertProperty = GenericUriPointer.convertProperty;

		return UriListPointer;

	}]);

	/**
	 * UriListPointer targets a single target object, like a user.
	 *
	 * It is meant to be an actual object, extending "{}".
	 */
	app.factory('nxcrudextbase.UriObjectPointer', ['$q', '$http', '$cacheFactory', 'nxcrudextbase.GenericUriPointer', function($q, $http, $cacheFactory, GenericUriPointer) {

		var deferredCache = getCache($cacheFactory);

		/**
		 * The "resource" is the uri string to target the data.
		 * When available during construction, the target data can be passed directly.
		 *
		 * @param resource
		 * @param content
		 * @constructor
		 */
		var UriObjectPointer = function(resource, content, ResourceFactory) {

			this.$$resource = resource || 'about:blank';
			this.$$resourceFactory = angular.isFunction(ResourceFactory) ? ResourceFactory(this.$$resource) : undefined;

			if (content) {
				this.override(content);
			}
		};
		UriObjectPointer.prototype = new GenericUriPointer();

		/**
		 * Overriding can be done at any time, from both, inside and outside.
		 * Usually the override method gets triggered by the initialize method
		 * right after the remote resource has been fetched, or just inside
		 * of the construct method when pre-fetched data has been passed.
		 *
		 * @param content
		 */
		UriObjectPointer.prototype.override = function(content) {
			var deferred = deferredCache.get(this.$$resource);
			if (!deferred) {
				deferred = $q.defer();
				deferredCache.put(this.$$resource, deferred);
			}

			angular.extend(this, angular.fromJson(angular.toJson(content)));
			this.$$initialized = true;

			deferred.resolve(this, angular.isFunction(this.$$resourceFactory) ? new this.$$resourceFactory(this) : undefined);
		};

		/**
		 * Factory method.
		 *
		 * Use this to transform a resource string to an UriPointer object, because this
		 * plays nice with the HEATOSProvider which needs to clean up those attributes
		 * right befor sending data.
		 *
		 * @param object
		 * @param property
		 */
		UriObjectPointer.convertProperty = GenericUriPointer.convertProperty;

		return UriObjectPointer;

	}]);


}(window, angular));