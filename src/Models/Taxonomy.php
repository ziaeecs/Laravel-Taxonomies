<?php namespace Lecturize\Taxonomies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Taxonomy
 * @package Lecturize\Taxonomies\Models
 */
class Taxonomy extends Model
{
    use SoftDeletes;

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'term_id',
        'taxonomy',
        'description',
        'parent',
        'count',
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

        $this->table = config('lecturize.taxonomies.table_taxonomies', 'taxonomies');
    }

    /**
     * Get the term this taxonomy belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the parent taxonomy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Taxonomy::class, 'parent');
    }

    /**
     * Get the children taxonomies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Taxonomy::class, 'parent');
    }

    /**
     * An example for a related posts model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function posts()
    {
        return $this->morphedByMany('App\Models\Posts\Post', 'taxable', 'taxables');
    }

    /**
     * Scope taxonomies.
     *
     * @param  object  $query
     * @param  string  $taxonomy
     * @return mixed
     */
    public function scopeTaxonomy($query, $taxonomy)
    {
        return $query->where('taxonomy', $taxonomy);
    }

    /**
     * Scope terms.
     *
     * @param  object  $query
     * @param  string  $term
     * @param  string  $taxonomy
     * @param  null|string  $locale
     * @return mixed
     */
    public function scopeTerm($query, $term, $taxonomy = null, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        if ($taxonomy) {
            $query->taxonomy($taxonomy);
        }

        return $query->whereHas('term', function ($q) use ($term, $taxonomy, $locale) {
            $q->where('name->', $locale, $term);
        });
    }

    /**
     * A simple search scope.
     *
     * @param  object  $query
     * @param  string  $searchTerm
     * @param  string  $taxonomy
     * @param  null|string  $locale
     * @return mixed
     */
    public function scopeSearch($query, $searchTerm, $taxonomy = null, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        if ($taxonomy) {
            $query->taxonomy($taxonomy);
        }

        return $query->whereHas('term', function ($q) use ($searchTerm, $taxonomy, $locale) {
            $q->where('name->'.$locale, 'like', '%'.$searchTerm.'%');
        });
    }
}
