# App-Creator

App-Creator is a collection of Laravel Components which aim is 
to create Laravel 5.6 and Vue2 application from database.

## How does it work?

This package expects that you are using Laravel 5.6+ and already have a fresh Laravel installation with a database that has tables with Laravel naming convention.
Then
You will need to import the `app-creator` package via composer:

```shell
composer require gesirdek/app-creator
```

### Configuration

Run

```bash
npm i
npm i -S font-awesome js-cookie sweetalert2 vee-validate material-design-icons material-icons vue-i18n vue-router vue-timeago vuetify vuex vuex-router-sync
npm i -D babel-plugin-syntax-dynamic-import
```  

Add the service provider to your `config/app.php` file within the `providers` key:

```php
// ...
'providers' => [
    /*
     * Package Service Providers...
     */

    Gesirdek\Coders\CodersServiceProvider::class,
],
// ...
```
 
### Configuration for local environment only

If you wish to enable generators only for your local environment, you should install it via composer using the --dev option like this:

```shell
composer require gesirdek/app-creator --dev
```

Then you'll need to register the provider in `app/Providers/AppServiceProvider.php` file.

```php
public function register()
{
    if ($this->app->environment() == 'local') {
        $this->app->register(Gesirdek\Coders\CodersServiceProvider::class);
    }
}
```

## Models

Add the `models.php` configuration file to your `config` directory.

```shell
php artisan vendor:publish --tag=gesirdek-models --force
php artisan config:clear
```

## Admin Panel

Add this to your routes.php or web.php depending on your laravel verison.

```php
Route::get('admin/{name?}', function ($name='') {
    return view('admin');
})->where('name','.*');
```

### Database Creation

To be able to create project from database, consider below for database design.

- Table names need to be plural form except pivot table names.
- Pivot table names must include both tables' name as singular form.
- To implement modular design, add module name to table comment. If you need to add a comment to any table, add double semicolon (;;) before your comment.

To be able to use modular design, add nWidart package

```shell
composer require nwidart/laravel-modules
```

After that add modules directory (`"Modules\\": "Modules/"`) to composer.json

```json
{
"autoload": {
        "classmap": [
            "database/seeds",
            "database/factories"
        ],
        "psr-4": {
            "App\\": "app/",
            "Modules\\": "Modules/"
        }
    },
}
```

After that run composer dumpautoload
```shell
composer dumpautoload
```

### Usage

Assuming you have already configured your database, you are now all set to go.

- Let's scaffold some of your models from your default (mysql) connection.

```shell
php artisan code:models
```

If you are using POSTGRESQL
```shell
php artisan code:models --connection=pgsql
```

Then run
```shell
npm run production
```

Thats it! Your admin panel with DB CRUD's is ready under /admin !

### Customizing Model Scaffolding

To change the scaffolding behaviour you can make `config/models.php` configuration file
fit your database needs. 

### Tips

#### 1. Keeping model changes

You may want to generate your models as often as you change your database. In order
not to lose you own model changes, you should set `base_files` to `true` in your `config/models.php`.

When you enable this feature your models will inherit their base configurations from
base models. You should avoid adding code to your base models, since you
will lose all changes when they are generated again.

> Note: You will end up with to models for the same table and you may think it is a horrible idea 
to have two classes for the same thing. However, it is up to you
to decide whether this approach gives value to your project :-)

#### Support

For the time being, this package supports Postgre and MYSQL database.
