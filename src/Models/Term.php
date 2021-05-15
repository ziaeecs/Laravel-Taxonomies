<?php namespace Lecturize\Taxonomies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * Class Term
 * @package Lecturize\Taxonomies\Models
 */
class Term extends Model
{
    use HasTranslations, HasTranslatableSlug, SoftDeletes;

    protected $translatable = [
        'name',
        'slug',
    ];

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * @inheritdoc
     */
    protected $dates = ['deleted_at'];

    /**
     * @inheritdoc
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('lecturize.taxonomies.table_terms', 'terms');
    }

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function taxable()
    {
        return $this->morphMany(Taxable::class, 'taxable');
    }

    /**
     * Get the taxonomies this term belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taxonomies()
    {
        return $this->hasMany(Taxonomy::class);
    }

    /**
     * Get route parameters.
     *
     * @param  string  $taxonomy
     * @return mixed
     */
    public function getRouteParameters($taxonomy, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        $taxonomy = Taxonomy::taxonomy($taxonomy)
            ->term($this->name, $locale)
            ->with('parent')
            ->first();

        $parameters = $this->getParentSlugs($taxonomy);

        array_push($parameters, $taxonomy->taxonomy);

        return array_reverse($parameters);
    }

    /**
     * Get slugs of parent terms.
     *
     * @param  Taxonomy  $taxonomy
     * @param  array  $parameters
     * @return array
     */
    function getParentSlugs(Taxonomy $taxonomy, $parameters = [])
    {
        array_push($parameters, $taxonomy->term->slug);

        if (($parents = $taxonomy->parent()) && ($parent = $parents->first())) {
            return $this->getParentSlugs($parent, $parameters);
        }

        return $parameters;
    }
}
