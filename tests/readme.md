# Tests for MiniCMS OSV

Individual tests are functions which name begins by `test_` (case insensitive).

They must be inside files which names ends by `test.php` (case insensitive), inside the `tests` folder.

Run all tests by running in a terminal :
```
php tests.php
```
Files may be in subfolder, they are sorted in natural order.

Run a single test by running in a terminal :
```
php tests.php nameOfTheFileTest.php test_name_of_the_function
```
Test is successful if it does not return anything.

Each individual test functions should setup some data, for instance in hte $_POST superglobal then call the `loadSite($queryString[, $loggedInUserId])` function.  
The first argument is just the query string.  
The optional second argument should be the id of the user you want to be considered logged in.
The function returns the content that would have been sent to the browser.  

Then you should call some of the `assert*` functions. See the `tests/asserts.php` file for all assert functions.  
A failed assert stops all the tests and print information in the terminal.

When running all the tests, the database is completely recreated at the beginning.  
Each individual test is run in its own process, so that they do not pollute each other's states, except for the database (which is not recreated for each individual tests).  
You can setup the database connection information in the `tests/config.json` file (copy then rename the `tests\config.sample.json` file if it doesn't exist).
