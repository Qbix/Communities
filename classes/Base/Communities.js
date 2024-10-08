/**
 * Autogenerated base class for the Communities model.
 * 
 * Don't change this file, since it can be overwritten.
 * Instead, change the Communities.js file.
 *
 * @module Communities
 */
var Q = require('Q');
var Db = Q.require('Db');

/**
 * Base class for the Communities model
 * @namespace Base
 * @class Communities
 * @static
 */
function Base () {
	return this;
}
 
module.exports = Base;

/**
 * The list of model classes
 * @property tableClasses
 * @type array
 */
Base.tableClasses = [
	
];

/**
 * This method calls Db.connect() using information stored in the configuration.
 * If this has already been called, then the same db object is returned.
 * @method db
 * @return {Db} The database connection
 */
Base.db = function () {
	return Db.connect('Communities');
};

/**
 * The connection name for the class
 * @method connectionName
 * @return {string} The name of the connection
 */
Base.connectionName = function() {
	return 'Communities';
};
