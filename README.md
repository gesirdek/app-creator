# App-Creator

App-Creator is a collection of Laravel Components which aim is 
to create Laravel 5.6 and Vue2 application from database.

## How does it work?

This package expects that you are using Laravel 5.6.
You will need to import the `app-creator` package via composer:

```shell
composer require gesirdek/app-creator
```

### Configuration

Add this to your package.json and run `npm install`

```json
{
  "dependencies": {
    "axios": "^0.18",
    "bootstrap": "^4.0.0",
    "bootstrap-vue": "^2.0.0-rc.6",
    "font-awesome": "^4.7.0",
    "jquery": "^3.3.1",
    "jquery-mask-plugin": "^1.14.15",
    "js-cookie": "^2.2.0",
    "material-design-icons": "^3.0.1",
    "material-icons": "^0.1.0",
    "npm": "^5.8.0",
    "popper.js": "^1.12",
    "sweetalert2": "^7.18.0",
    "vee-validate": "^2.0.5",
    "vue": "^2.5.13",
    "vue-i18n": "^7.5.0",
    "vue-meta": "^1.4.2",
    "vue-router": "^3.0.1",
    "vuetify": "^1.0.5",
    "vuex": "^3.0.1",
    "vuex-i18n": "^1.10.5",
    "vuex-router-sync": "^5.0.0",
    "waypoints": "^3.1.1"
  },
  "devDependencies": {
    "babel-eslint": "^8.2.1",
    "babel-plugin-syntax-dynamic-import": "^6.18.0",
    "browser-sync": "^2.23.6",
    "browser-sync-webpack-plugin": "^2.0.1",
    "cross-env": "^5.1.0",
    "eslint": "^4.15.0",
    "eslint-plugin-vue-libs": "^2.1.0",
    "laravel-mix": "^2.0",
    "lodash": "^4.17.4",
    "vue-template-compiler": "^2.5.13",
    "webpack-bundle-analyzer": "^2.9.2",
    "popper.js": "^1.12"
  }
}
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
Route::get('admin/{name?}', function () {
    return view('admin');
});
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

For the time being, this package supports Postgre database. MySQL model and schema is ready but never tested.
