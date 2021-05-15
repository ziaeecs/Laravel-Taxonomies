<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class TaxonomiesTable
 */
class CreateTaxonomiesTable extends Migration
{
    /**
     * Table names.
     *
     * @var string $table_terms The terms table name.
     * @var string $table_taxonomies The taxonomies table name.
     * @var string $table_pivot The pivot table name.
     */
    protected $table_terms;
    protected $table_taxonomies;
    protected $table_pivot;

    /**
     * Create a new migration instance.
     */
    public function __construct()
    {
        $this->table_terms = config('lecturize.taxonomies.table_terms', 'terms');
        $this->table_taxonomies = config('lecturize.taxonomies.table_taxonomies', 'taxonomies');
        $this->table_pivot = config('lecturize.taxonomies.table_pivot', 'taxables');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table_terms, function (Blueprint $table) {
            $table->id();

            $table->json('name');
            $table->json('slug');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($this->table_taxonomies, function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('term_id')
                ->references('id')
                ->on($this->table_terms)
                ->onDelete('cascade');

            $table->string('taxonomy');
            $table->string('description')->nullable();

            $table->unsignedBigInteger('parent')->default(0);

            $table->unsignedSmallInteger('count')->default(0);

            $table->timestamps();
            $table->softDeletes();

             $table->unique(['term_id', 'taxonomy']);
        });

        Schema::create($this->table_pivot, function (Blueprint $table) {
            $table->unsignedBigInteger('taxonomy_id')
                ->references('id')
                ->on($this->table_taxonomies);

            $table->nullableMorphs('taxable');

            $table->unsignedSmallInteger('order')->default(0);

            $table->unique(['taxonomy_id', 'taxable_type', 'taxable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table_pivot);
        Schema::dropIfExists($this->table_taxonomies);
        Schema::dropIfExists($this->table_terms);
    }
}
