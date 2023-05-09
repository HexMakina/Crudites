[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HexMakina/Crudites/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/HexMakina/Crudites/?branch=main)
<img src="https://img.shields.io/badge/PHP-7.4-brightgreen" alt="PHP 7.04 Required" />
[![License](http://poser.pugx.org/hexmakina/crudites/license)](https://packagist.org/packages/hexmakina/crudites)
[![Latest Stable Version](http://poser.pugx.org/hexmakina/crudites/v)](https://packagist.org/packages/hexmakina/crudites)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HexMakina/Crudites/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/HexMakina/Crudites/?branch=main)

# Crudités ?

_"Crudités, it's a cup of carrots sticks"_

**C**odd's **R**elations, **U**nicity, **D**efinitions, **I**ntrospection, **T**ransaction, **E**xecution & **S**ets

**C**reate - **R**etrieve - **U**pdate - **D**elete

Crudites is a PHP PDO wrapper

# Exception
`CruditeException` extends `\Exception` and is thrown by all Crudites components


# Source
The Source object stores and validates a DSN (Data Source Name)

## Instantiation
It is created using a DSN string, the constructor will extract and validates all the required information

`mysql:host=localhost;dbname=testdb`  
`mysql:host=localhost;port=3307;dbname=testdb`  

## Exceptions
If the database's name or a proper driver cannot be found, a `CruditesException` is thrown with the message
- `_DSN_NO_DBNAME_`
- `_DSN_NO_DRIVER_`
- `_DSN_UNAVAILABLE_DRIVER_`

## Properties
The object stores
1. The DSN string (constructor parameter)
2. The database name (extracted from the DSN string)

The object validates the driver's name (extracted from DSN string) by calling `\PDO::getAvailableDrivers()`

## Methods
When instantiated, the object provides two methods:
1. `DSN()`, returns the DSN string (string)
2. `name()`, returns the database name (string)


# Connection
The Connection object relies on PDO to connect to a database.

## Instantiation

It is created using
1. a DSN string
2. a username (optional)
3. a password (optional)
4. a list of driver options (optional)

## Exceptions
The DSN string is validated using the Source object, and may throw `CruditesException`
A PDO object is created and may throw a `PDOException`

### Default Options
```
\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // the one option you cannot change
\PDO::ATTR_CASE => \PDO::CASE_NATURAL,
\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
```

**The PDO::ATTR_ERRMODE is the only option that cannot be overridden**


## Properties
The object stores:
1. a Source object;
2. a database name (string)
3. a PDO object

## Methods
When instantiated, the object provides the following methods:

### Database related
+ useDatabase(string name)
+ driverName()
+ databaseName()

### Query execution
+ prepare(string statement)
+ query()
+ alter()
+ lastInsertId(string name)
+ errorInfo()
+ errorCode()

### Transactions
+ transact()
+ commit()
+ rollback()


# Database
The object represent a SQL database, handles connection and introspection.

Introspection is a two step process:
1. The `INFORMATION_SCHEMA` is queried and FK and UNIQUE constraints are stored in the object properties upon instantiation
2. The table introspection, called inspection, is executed on demand using `inspect(table_name)` and the results are stored in the table cache property. Inspection uses introspection to create a complete representation of a table: fields, data type, defaults & constraints

## Instantiation
It is created using a `Connection` object. The connection is stored and the database instrospection is executed.

## Properties
+ `Connection` object
+ Table cache (array)
+ List of foreign keys, indexed by table (array)
+ List of unique constraints, indexed by table (array)

## Methods
When instantiated, the object provides the following methods:
+ name(), returns the database name
+ connection(), returns the `Connection` object
+ inspect(string table_name), return a Table object
