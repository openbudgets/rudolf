<?php
/**
 * Created by PhpStorm.
 * User: larjohns
 * Date: 29/04/2016
 * Time: 12:39:48
 */

namespace App\Model\Globals;


use App\Model\Dimension;
use App\Model\Sparql\SubPattern;
use App\Model\Sparql\TriplePattern;
use App\Model\SparqlModel;
use Asparagus\QueryBuilder;

class GlobalMembersResult extends SparqlModel
{

    /**
     * PREFIX skos: <http://testSkos/>

    select distinct ?elem  where {
    ?elem ?p ?o.
    filter not exists {
    ?elem (skos:similar|^skos:similar)* ?elem_
    filter( str(?elem_) < str(?elem) )
    }
    }
     */

    public $page;
    public $page_size;
    public $order;
    public $status;
    public $data;

    public function __construct($dimension, $page, $page_size, $orders)
    {

        parent::__construct();




        $this->page = intval($page);
        $this->page_size = intval($page_size);
        $this->order = $orders;
        $this->load($dimension, $page, $page_size, $orders);

        $this->status = "ok";
    }


    private function load($attributeShortName, $page, $page_size, $order)
    {

        $queryBuilder = new QueryBuilder(config("sparql.prefixes"));
        $subQueryBuilders = [];
        $model = (new BabbageGlobalModelResult())->model;
        $this->fields = [];
        //($model->dimensions[$dimensionShortName]);
        $dimensionShortName = explode(".", $attributeShortName)[0];
        foreach ($model->dimensions[$dimensionShortName]->attributes as $att) {
            $this->fields[] = $att->ref;
        }
        // return $facts;
        $dimensions = $model->dimensions;

        /** @var GlobalDimension $actualDimension */
        $actualDimension = $model->dimensions[explode('.', $dimensionShortName)[0]];
        $selectedPatterns = [];
        /** @var Dimension $innerDimension */
        foreach ($actualDimension->getInnerDimensions() as $innerDimension) {

            $myPatterns = $this->globalDimensionToPatterns([$innerDimension], [$actualDimension->label_ref, $actualDimension->key_ref]);
            $selectedPatterns[$innerDimension->ref] = $myPatterns;

            $selectedDimensions = [];
            $bindings = [];
            $attributes = [];


            $selectedDimensions[$innerDimension->getUri()] = $innerDimension;
            $bindingName = "binding_" . substr(md5($innerDimension->ref), 0, 5);
            $valueAttributeLabel = "uri";

            $attributes[$innerDimension->getUri()][$valueAttributeLabel] = $bindingName;
            $bindings[$innerDimension->getUri()] = "?$bindingName";


            $sliceSubGraph = new SubPattern([
                new TriplePattern("?slice", "a", "qb:Slice"),
                new TriplePattern("?slice", "qb:observation", "?observation"),

            ], true);

            $dataSetSubGraph = new SubPattern([
                new TriplePattern("?dataset", "a", "qb:DataSet"),
                new TriplePattern("?observation", "qb:dataSet", "?dataset"),

                ], true);

            $patterns = [];
            $needsSliceSubGraph = false;
            $needsDataSetSubGraph = false;
            foreach ($selectedDimensions as $dimensionName => $dimension) {
                $attribute = $dimension->getUri();
                $attachment = $dimension->getAttachment();
                if (isset($attachment) && $attachment == "qb:Slice") {
                    $needsSliceSubGraph = true;
                    $sliceSubGraph->add(new TriplePattern("?slice", $attribute, $bindings[$attribute], false));
                }
                elseif(isset($attachment) && $attachment == "qb:DataSet"){
                    $needsDataSetSubGraph = true;
                    $dataSetSubGraph->add(new TriplePattern("?dataset", $attribute, $bindings[$attribute], false));
                }
                else {
                    $patterns [] = new TriplePattern("?observation", $attribute, $bindings[$attribute], false);
                }

                if ($dimension instanceof Dimension) {

//dd($dimension->attributes[$dimension->key_attribute]->getUri());


                    if ($dimension->orig_dimension != $dimension->label_attribute) {
                        $attributes[$attribute][$dimension->attributes[$dimension->label_attribute]->getUri()] = $attributes[$attribute]["uri"] . "_" . substr(md5($dimension->label_attribute), 0, 5);

                        $bindings[] = $bindings[$attribute] . "_" . substr(md5($dimension->label_attribute), 0, 5);
                    }



                    //var_dump($dimension->attributes);
                    if (isset($attachment) && $attachment == "qb:Slice") {
                        $sliceSubGraph->add(new TriplePattern($bindings[$attribute], $dimension->attributes[$dimension->key_attribute]->getUri(), $bindings[$attribute] . "_" . substr(md5($dimension->key_attribute), 0, 5), false));
                        $sliceSubGraph->add(new TriplePattern($bindings[$attribute], $dimension->attributes[$dimension->label_attribute]->getUri(), $bindings[$attribute] . "_" . substr(md5($dimension->label_attribute), 0, 5), false));
                        if ($dimension->ref != $dimension->key_attribute) {
                            $attributes[$attribute][$dimension->attributes[$dimension->key_attribute]->getUri()] = $attributes[$attribute]["uri"] . "_" . substr(md5($dimension->key_attribute), 0, 5);
                            $bindings[] = $bindings[$attribute] . "_" . substr(md5($dimension->key_attribute), 0, 5);

                        }
                    }
                    elseif(isset($attachment) && $attachment == "qb:DataSet"){
                        if ($dimension->orig_dimension != $dimension->key_attribute)
                            $dataSetSubGraph->add(new TriplePattern($bindings[$attribute], $dimension->attributes[$dimension->key_attribute]->getUri(), $bindings[$attribute] . "_" . substr(md5($dimension->key_attribute), 0, 5), false));
                        if ($dimension->orig_dimension != $dimension->label_attribute)
                            $dataSetSubGraph->add(new TriplePattern($bindings[$attribute], $dimension->attributes[$dimension->label_attribute]->getUri(), $bindings[$attribute] . "_" . substr(md5($dimension->label_attribute), 0, 5), false));
                        if ($dimension->orig_dimension != $dimension->key_attribute) {
                            $attributes[$attribute][$dimension->attributes[$dimension->key_attribute]->getUri()] = $attributes[$attribute]["uri"] . "_" . substr(md5($dimension->key_attribute), 0, 5);
                            $bindings[] = $bindings[$attribute] . "_" . substr(md5($dimension->key_attribute), 0, 5);

                        }
                    }
                    else {
                        if ($dimension->orig_dimension != $dimension->label_attribute)
                            $patterns [] = new TriplePattern($bindings[$attribute], $dimension->attributes[$dimension->label_attribute]->getUri(), $bindings[$attribute] . "_" . substr(md5($dimension->label_attribute), 0, 5), false);


                    }


                }
            }

            if ($needsSliceSubGraph) {
                $patterns[] = $sliceSubGraph;

            }
            if ($needsDataSetSubGraph) {
                $patterns[] = $dataSetSubGraph;

            }
            $dataset = $innerDimension->getDataSet();
            //$dsd = $model->getDsd();
            $patterns[] = new TriplePattern('?observation', 'a', 'qb:Observation');
            $patterns[] = new TriplePattern('?observation', 'qb:dataSet', "<$dataset>");
            $subQueryBuilder = $this->build($bindings, $patterns, $queryBuilder);

            $subQueryBuilders[] = $subQueryBuilder;
            //echo $subQueryBuilder->format();die;

        }
        /** @var QueryBuilder $subQueryBuilder */

        $innerQuery = $queryBuilder->newSubquery();
        $innerQuery->union(array_map(function (QueryBuilder $subQueryBuilder) use($innerQuery) {
            return $innerQuery->newSubgraph()->subquery($subQueryBuilder);
        }, $subQueryBuilders));
        $innerQuery->selectDistinct(["?key", "?value"]);
        $innerQuery->filterNotExists($innerQuery->newSubgraph()->where("?key", "(skos:similar|^skos:similar)*", "?elem_")->filter("str(?elem_) < str(?key )"));
        $queryBuilder->subquery($innerQuery);
        $queryBuilder->select(["?key", "(GROUP_CONCAT(?value, '/') AS ?value)"]);
        $queryBuilder->limit($page_size);
        $queryBuilder->offset($page * $page_size);
        //echo $queryBuilder->format();die;

        $result = $this->sparql->query(
            $queryBuilder->getSPARQL()
        );

        //return $result;
      // dd($result);
        // dd($selectedPatterns);
        // dd($selectedPatterns);
        $results = [];
        foreach ($result as $row){
            if(!isset($row->key))continue;
            $results[$row->key->dumpValue('text')]=$row->value->getValue();
        }


        $this->data = $results;

    }

    /**
     * @param Dimension[] $dimensions
     * @param $fields
     * @return array
     */
    protected function globalDimensionToPatterns(array $dimensions, $fields)
    {
        $selectedDimensions = [];

        foreach ($fields as $field) {
            $fieldNames = explode(".", $field);

            /** @var Dimension $attribute */
            foreach ($dimensions as $name => $attribute) {
                //var_dump($fieldNames);
                if ($fieldNames[0] == $attribute->orig_dimension) {
                    if (!isset($selectedDimensions[$attribute->getUri()])) {
                        $selectedDimensions[$attribute->getUri()] = [];
                    }
                    $currentAttribute = $attribute;
                    for ($i = 1; $i < count($fieldNames); $i++) {
                        foreach ($currentAttribute->attributes as $innerAttributeName => $innerAttribute) {
                            if ($fieldNames[$i] == $fieldNames[$i - 1]) continue;

                            if ($fieldNames[$i] == $innerAttributeName) {

                                $selectedDimensions[$attribute->getUri()][$innerAttribute->getUri()] = [];
                                break;

                            }
                        }
                    }

                }

            }

        }

        return ($selectedDimensions);
    }

    private function build(array $bindings, array $filters, QueryBuilder $queryBuilder)
    {
        $myQueryBuilder = $queryBuilder->newSubquery();
        foreach ($filters as $filter) {
            if ($filter instanceof TriplePattern || ($filter instanceof SubPattern && !$filter->isOptional)) {
                if ($filter->isOptional) {
                    $myQueryBuilder->optional($filter->subject, self::expand($filter->predicate), $filter->object);
                } else {
                    $myQueryBuilder->where($filter->subject, self::expand($filter->predicate), $filter->object);
                }
            } elseif ($filter instanceof SubPattern) {
                $subGraph = $myQueryBuilder->newSubgraph();

                foreach ($filter->patterns as $pattern) {

                    if ($pattern->isOptional) {
                        $subGraph->optional($pattern->subject, self::expand($pattern->predicate), $pattern->object);
                    } else {
                        $subGraph->where($pattern->subject, self::expand($pattern->predicate), $pattern->object);
                    }
                }

                $myQueryBuilder->optional($subGraph);


            }
        }


//dd(array_map(function($key, $value){ return $value . " AS ". $key;}, ["key", "value"], $bindings) );
if(count($bindings)==1){
    $bindings[] = "STR(".reset($bindings).")";
}
$bindings  = array_slice($bindings, 0,2);
    // dd($bindings);
        $myQueryBuilder
            ->selectDistinct(array_map(function($key, $value){ return "(".$value . " AS ". $key.")";}, ["?key", "?value"], $bindings))->limit($this->page_size)->offset($this->page*$this->page_size);


        return $myQueryBuilder ;

    }



}