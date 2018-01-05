# Tests for MiniCMS OSV

Individual tests are functions which name begins by `test_`.

They must be inside files which names ends by `test.php`, inside the `tests` folder.

Run all tests by running in a terminal :
```
tests.php
```
Files may be in subfolder, they are sorted in natural order.

Run all tests of a particular file by running in a terminal :
```
tests.php nameOfTheFileTest.php
```

Run a single test by running in a terminal :
```
tests.php nameOfTheFileTest.php test_name_of_the_function
```

Each individual test functions should setup some data, for instance in hte $_POST superglobal then call the `loadSite($queryString[, $loggedInUserId])` function.  
The first argument is just the query string.  
The optional second argument should be the id of the user you want to be considered logged in.
The function returns the content that would have been sent to the browser.  

Then you should call some of the `assert*` functions. See the `tests/asserts.php` file for all assert functions.  
A failed assert stops all the tests and print information in the terminal.

Each individual test is run in its own process, so that they do not pollute each other's states.

By default the test database if completely recreated from scratch for each test.  
You can prevent this by adding the `--keep-db` option to the command line.  
Note however that even with this option, the database is still recreated at the beginning when running all the tests (calling `tests.php` without argument).

