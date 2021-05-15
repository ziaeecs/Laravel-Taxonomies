<?php namespace Lecturize\Taxonomies\Traits;

use Lecturize\Taxonomies\Models\Taxable;
use Lecturize\Taxonomies\Models\Taxonomy;
use Lecturize\Taxonomies\Models\Term;
use Lecturize\Taxonomies\TaxableUtils;

/**
 * Class HasTaxonomies
 * @package Lecturize\Taxonomies\Traits
 */
trait HasTaxonomies
{
    /**
     * Return a collection of taxonomies related to the taxed model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function taxed()
    {
        return $this->morphMany(Taxable::class, 'taxable');
    }

    /**
     * Return a collection of taxonomies related to the taxed model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function taxonomies()
    {
        return $this->morphToMany(Taxonomy::class, 'taxable');
    }

    /**
     * Add one or more terms in a given taxonomy.
     *
     * @param  mixed  $term
     * @param  string  $taxonomy
     * @param  integer  $parent
     * @param  integer  $order
     */
    public function addTerm($term, $taxonomy, $parent = 0, $order = 0)
    {
        if (!is_array($term) || (is_array($term) && TaxableUtils::isAssoc($term))) {
            $this->addSignleTerm($term, $taxonomy, $parent, $order);
        } else {
            foreach ($term as $item) {
                $this->addSignleTerm($item, $taxonomy, $parent, $order);
            }
        }
    }

    private function addSignleTerm($term, $taxonomy, $parent = 0, $order = 0)
    {
        $term = TaxableUtils::makeTermMultilingual($term);

        $term = TaxableUtils::createTerm($term);

        $taxonomyModel = TaxableUtils::createTaxonomies($term, $taxonomy, $parent);

        if ($this->taxonomies()->where('taxonomy', $taxonomy)->where('term_id', $term->id)->first()) {
            return;
        }

        $this->taxonomies()->attach($taxonomyModel, [
            'order' => $order,
        ]);

        $taxonomyModel->count++;
        $taxonomyModel->save();
    }

    /**
     * Convenience method for attaching this models taxonomies to the given parent taxonomy.
     *
     * @param  integer  $taxonomy_id
     */
    public function setCategory($taxonomy_id, $order = 0)
    {
        $taxonomy = Taxonomy::find($taxonomy_id);

        if ($taxonomy && !$this->taxed()->where('taxonomy_id', $taxonomy_id)->first()) {
            $this->taxonomies()->attach($taxonomy_id, [
                'order' => $order,
            ]);

            $taxonomy->count++;
            $taxonomy->save();
        }
    }

    /**
     * Pluck taxonomies by given field.
     *
     * @param  string  $by
     * @return mixed
     */
    public function getTaxonomies($by = 'id')
    {
        return $this->taxonomies->pluck($by);
    }

    /**
     * Pluck terms for a given taxonomy by name.
     *
     * @param  string  $taxonomy
     * @return mixed
     */
    public function getTermNames($taxonomy = '')
    {
        if ($terms = $this->getTerms($taxonomy)) {
            return $terms->pluck('name->'.app()->getLocale());
        }

        return null;
    }

    /**
     * Get the terms related to a given taxonomy.
     *
     * @param  string  $taxonomy
     * @return mixed
     */
    public function getTerms($taxonomy = '')
    {
        if ($taxonomy) {
            $term_ids = $this->taxonomies->where('taxonomy', $taxonomy)->pluck('term_id');
        } else {
            $term_ids = $this->getTaxonomies('term_id');
        }

        return Term::whereIn('id', $term_ids)->get();
    }

    /**
     * Get a term model by the given name and optionally a taxonomy.
     *
     * @param  string  $term_name
     * @param  string  $taxonomy
     * @return mixed
     */
    public function getTerm($term_name, $taxonomy = '', $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        if ($taxonomy) {
            $term_ids = $this->taxonomies->where('taxonomy', $taxonomy)->pluck('term_id');
        } else {
            $term_ids = $this->getTaxonomies('term_id');
        }

        return Term::whereIn('id', $term_ids)->where('name->'.$locale, $term_name)->first();
    }

    /**
     * Check if this model has a given term.
     *
     * @param  string  $term_name
     * @param  string  $taxonomy
     * @return boolean
     */
    public function hasTerm($term_name, $taxonomy = '', $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        return (bool) $this->getTerm($term_name, $taxonomy, $locale);
    }

    /**
     * Disassociate the given term from this model.
     *
     * @param  string  $term_name
     * @param  string  $taxonomy
     * @return mixed
     */
    public function removeTerm($term_name, $taxonomy = '', $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        if (!$term = $this->getTerm($term_name, $taxonomy, $locale)) {
            return null;
        }

        if ($taxonomy) {
            $taxonomy = $this->taxonomies->where('taxonomy', $taxonomy)->where('term_id', $term->id)->first();
        } else {
            $taxonomy = $this->taxonomies->where('term_id', $term->id)->first();
        }

        return $this->taxed()->where('taxonomy_id', $taxonomy->id)->delete();
    }

    /**
     * Disassociate all terms from this model.
     *
     * @return mixed
     */
    public function removeAllTerms()
    {
        return $this->taxed()->delete();
    }

    /**
     * Scope by given terms.
     *
     * @param  object  $query
     * @param  array  $terms
     * @param  string  $taxonomy
     * @return mixed
     */
    public function scopeWithTerms($query, $terms, $taxonomy, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        foreach ($terms as $term) {
            $this->scopeWithTerm($query, $term, $taxonomy, $locale);
        }

        return $query;
    }

    /**
     * Scope by the given term.
     *
     * @param  object  $query
     * @param  string  $term_name
     * @param  string  $taxonomy
     * @return mixed
     */
    public function scopeWithTerm($query, $term_name, $taxonomy, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        $term_ids = Taxonomy::where('taxonomy', $taxonomy)->pluck('term_id');

        $term = Term::whereIn('id', $term_ids)->where('name->'.$locale, $term_name)->first();

        return $query->whereHas('taxonomies', function ($q) use ($term) {
            $q->where('term_id', $term->id);
        });
    }

    /**
     * Scope by given taxonomy.
     *
     * @param  object  $query
     * @param  string  $term_name
     * @param  string  $taxonomy
     * @return mixed
     */
    public function scopeWithTax($query, $term_name, $taxonomy, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        $term_ids = Taxonomy::where('taxonomy', $taxonomy)->pluck('term_id');

        $term = Term::whereIn('id', $term_ids)->where('name->'.$locale, $term_name)->first();

        $taxonomy = Taxonomy::where('term_id', $term->id)->first();

        return $query->whereHas('taxed', function ($q) use ($taxonomy) {
            $q->where('taxonomy_id', $taxonomy->id);
        });
    }

    /**
     * Scope by category id.
     *
     * @param  object  $query
     * @param  integer  $taxonomy_id
     * @return mixed
     */
    public function scopeHasCategory($query, $taxonomy_id)
    {
        return $query->whereHas('taxed', function ($q) use ($taxonomy_id) {
            $q->where('taxonomy_id', $taxonomy_id);
        });
    }

    /**
     * Scope by category ids.
     *
     * @param  object  $query
     * @param  array  $taxonomy_ids
     * @return mixed
     */
    public function scopeHasCategories($query, $taxonomy_ids)
    {
        return $query->whereHas('taxed', function ($q) use ($taxonomy_ids) {
            $q->whereIn('taxonomy_id', $taxonomy_ids);
        });
    }
}
