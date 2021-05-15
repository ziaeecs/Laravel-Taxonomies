<?php namespace Lecturize\Taxonomies;

use Illuminate\Support\ServiceProvider;

class TaxonomiesServiceProvider extends ServiceProvider
{
    protected $migrations = [
        'CreateTaxonomiesTable' => 'create_taxonomies_table'
    ];

    /**
     * @inheritdoc
     */
    public function boot()
    {
        $this->handleConfig();
        $this->handleMigrations();
    }

    /**
     * @inheritdoc
     */
    public function register()
    {
        //
    }

    /**
     * @inheritdoc
     */
    public function provides()
    {
        return [];
    }

    /**
     * Publish and merge the config file.
     *
     * @return void
     */
    private function handleConfig()
    {
        $configPath = __DIR__.'/../config/config.php';

        $this->publishes([$configPath => config_path('taxonomy.php')]);

        $this->mergeConfigFrom($configPath, 'taxonomy');
    }

    /**
     * Publish migrations.
     *
     * @return void
     */
    private function handleMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/');

        foreach ($this->migrations as $class => $file) {
            if (!class_exists($class)) {
                $timestamp = date('Y_m_d_His', time());

                $this->publishes([
                    __DIR__.'/../database/migrations/'.$file.'.php' =>
                        database_path('migrations/'.$timestamp.'_'.$file.'.php')
                ], 'migrations');
            }
        }
    }
}
