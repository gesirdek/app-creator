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

Add the service provider to your `config/app.php` file within the `providers` key:

```php
// ...
'providers' => [
    /*
     * Package Service Providers...
     */

    AppCreator\Coders\CodersServiceProvider::class,
],
// ...
```

Add this to your webpack.mix file

```js
mix.webpackConfig({
    resolve: {
        extensions: ['.js', '.json', '.vue'],
        alias: {
            '~': path.join(__dirname, './resources/assets/js')
        }
    },
    output: {
        chunkFilename: 'js/[name].[chunkhash].js',
        publicPath: mix.config.hmr ? '//localhost:8080' : '/'
    }
}).options({
    extractVueStyles: true
});
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
        $this->app->register(AppCreator\Coders\CodersServiceProvider::class);
    }
}
```

## Models

Add the `models.php` configuration file to your `config` directory.

```shell
php artisan vendor:publish --tag=gesirdek-models
php artisan config:clear
```

## Vue Scaffolding

Create necessary js files
```shell
php artisan vendor:publish --tag=gesirdek-vue-scaffolding
```

## Admin Panel

Add tih sto your routes.php or web.php

```php
Route::get('admin/{name?}', function () {
    return view('admin');
});
```

### Usage

Assuming you have already configured your database, you are now all set to go.

- Let's scaffold some of your models from your default connection.

```shell
php artisan code:models --connection=pgsql --schema=shop
```

### Database Creation

Tested only on Postgre.

To be able to create project from database, consider below for database design.

- Table names need to be plural form except pivot table names.
- Pivot table names must include both tables' name as singular form.
- To implement moduler design, add module name to table comment. If you need to add a comment to any table, add double semicolon (;;) before your comment.

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
