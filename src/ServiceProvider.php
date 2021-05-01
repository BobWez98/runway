<?php

namespace DoubleThreeDigital\Runway;

use DoubleThreeDigital\Runway\Support\ModelFinder;
use DoubleThreeDigital\Runway\Tags\RunwayTag;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        Fieldtypes\BelongsToFieldtype::class,
    ];

    protected $routes = [
        'cp' => __DIR__ . '/../routes/cp.php',
    ];

    protected $tags = [
        RunwayTag::class,
    ];

    public function boot()
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'runway');
        $this->mergeConfigFrom(__DIR__.'/../config/runway.php', 'runway');

        $this->publishes([
            __DIR__.'/../config/runway.php' => config_path('runway.php'),
        ], 'runway-config');

        Statamic::booted(function () {
            Runway::discoverResources();

            // TODO: remove old stuff
            ModelFinder::bootModels();

            Nav::extend(function ($nav) {
                foreach (Runway::allResources() as $resource) {
                    if ($resource->hidden()) {
                        continue;
                    }

                    $nav->content($resource->name())
                        ->icon($resource->cpIcon())
                        ->route('runway.index', ['model' => $resource->handle()]);
                }
            });

            foreach (Runway::allResources() as $resource) {
                Permission::register("View {$resource->plural()}", function ($permission) use ($resource) {
                    $permission->children([
                        Permission::make("Edit {$resource->plural()}")->children([
                            Permission::make("Create new {$resource->singular()}"),
                            Permission::make("Delete {$resource->singular()}"),
                        ]),
                    ]);
                })->group('Runway');
            }
        });
    }
}
