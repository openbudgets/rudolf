<?php
/**
 * Created by PhpStorm.
 * User: larjohns
 * Date: 07/04/2016
 * Time: 11:12:43
 */

namespace App\Model;



class PackageResult extends SparqlModel
{



    public $author;

    public $countryCode = null;
    public $cityCode = "";

    public $__origin_url = "http://apps.openbudgets.eu/dumps";
    public $model = [];
    public $name;
    public $title;
    public $resources = [];

    public function __construct($name)
    {
        parent::__construct();

        $model = (new BabbageModelResult($name))->model;
        //dd($model);

        foreach ($model->dimensions as $dimension) {
            $newDimension = [];
            if($dimension->ref=="fiscalYear"||$dimension->ref=="fiscalPeriod"){
                $newDimension["dimensionType"] = "datetime";



            }elseif($dimension->ref=="organization"||$dimension->ref=="budgetaryUnit"){
                $newDimension["dimensionType"] = "location";

            }
            else{
                $newDimension["dimensionType"] = "classification";

            }
            $this->model["dimensions"][$dimension->ref] = $newDimension;
        }
        foreach ($model->measures as $measure) {
            $newMeasure = [];
            $newMeasure["currency"] = $measure->currency;
            $newMeasure["title"] = $measure->label;
            $this->model["measures"][$measure->ref] = $newMeasure;
        }
        $this->name = $name;
        $this->title = $model->getTitle();
        $this->countryCode = $model->getCountryCode();
        $this->cityCode = $model->getCityCode();
        $this->__origin_url = $model->getDistributionURL();
        $this->resources = [
            "url" => $this->__origin_url,
            "name" => $this->name,
        ];
        //TODO: $this->cityCode =
       // $this->origin_url =


        
        
        

    }

    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        if(!isset($this->countryCode)){
            return config("sparql.defaultCountryCode");
        }
        return $this->countryCode;
    }


}