<?php namespace Lecturize\Taxonomies;

use Lecturize\Taxonomies\Models\Taxonomy;
use Lecturize\Taxonomies\Models\Term;

/**
 * Class TaxableUtils
 * @package Lecturize\Taxonomies
 */
class TaxableUtils
{
    /**
     * @param  string|array  $terms
     * @return array
     */
    public static function makeTermMultilingual($term)
    {
        if (is_array($term)) {
            return $term;
        }

        return [
            app()->getLocale() => $term,
        ];
    }

    /**
     * @param  array  $terms
     */
    public static function createTerm(array $term)
    {
        $found = Term::whereRaw('name = cast(? as json)', json_encode($term))->first();

        if ($found) {
            return $found;
        }

        $newTerm = new Term;
        $newTerm->name = $term;
        $newTerm->save();

        return $newTerm;
    }

    /**
     * @param  Term  $term
     * @param  string  $taxonomy
     * @param  integer  $parent
     * @param  integer  $order
     */
    public static function createTaxonomies(Term $term, $taxonomy, $parent = 0)
    {
        if ($tax = Taxonomy::where('taxonomy', $taxonomy)->where('term_id', $term->id)->where('parent',
            $parent)->first()) {
            return $tax;
        }

        $model = new Taxonomy;
        $model->taxonomy = $taxonomy;
        $model->term_id = $term->id;
        $model->parent = $parent;
        $model->save();

        return $model;
    }

    public static function isAssoc(array $arr)
    {
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
