<?php

namespace Tphp\Apcu;
use Illuminate\Support\Facades\Blade;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
class TphpServiceProvider extends ServiceProvider
{
    protected $namespace = 'Tphp\Apcu';
    public function register()
    {
        \Tphp\Apcu\Routes::set($this->namespace);
    }

    public function map()
    {
        Blade::extend(function($value) {
            return preg_replace('/\@define(.+)/', '<?php ${1}; ?>', $value);
        });
    }
}
