# PHP BaseBuy.Auto API SDK

## Requirements

PHP 5.3 or above


## Links

1. BaseBuy.Auto API page: [Ru](http://api.basebuy.ru/api/auto/v1/),
2. [sample app](https://github.com/basebuy/basebuy-auto-sdk-php/blob/master/sample.php)

## Getting started

### Installation

1. Clone repo to your project.
2. Paste following code
    ```
    require_once '/path/to/cloned/repo/BasebuyAutoApi.php';
    ```

Using BaseBuy.Auto API requires following steps

1. Obtain API_KEY (token) by buying database at http://basebuy.ru.
Note: `API_KEY` is constant that you get for access to BaseBuy.Auto API.

2. Than you can use BaseBuy.Auto API.

    ```
    $basebuyAutoApi = new BasebuyAutoApi(
        new CurlGetConnector( API_KEY )
    );

    // check last information update date
    $basebuyAutoApi->typeGetDateUpdate();

    // get database file
    $downloadedFilePath = $basebuyAutoApi->typeGetAll();

    // catch error codes
    } catch( Exception $e ){
        if ( $e->getCode() == 401 ) ...
    }
    ```



## Side notes

1. Library throws exceptions in case of

    ```
    * response status isn't equal 2**
    * I/O error(see [requests](https://github.com/rmccue/Requests))
    ```


## Running tests

1. Clone this repo.
2. Install composer
3. Run `composer install`
4. Create `sample.php` with `API_KEY` constant.
6. Run it
# basebuy-auto
