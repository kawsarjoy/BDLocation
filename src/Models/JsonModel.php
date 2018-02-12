<?php

namespace BDLocation\Models;

class JsonModel
{
    protected $schema;

    protected $dataPath;

    protected $collection;

    protected $contents;

    public function __construct($schema, $dataPath = __DIR__ . '/../../data')
    {
        $this->schema = $schema;

        $this->dataPath = $dataPath;

        $this->contents = $this->getFileContent("{$this->dataPath}/{$this->schema}.json");
    }

    public function all()
    {
        $locations = $this->contents['data'];
        if(!$this->isDivisionSchema()){
            $locations = $this->buildFlatArray($locations);
        }
        return $this->buildCollection($locations);
    }

    /**
     * @param $name ['division', 'district', 'sub_district', 'name', 'short_name', 'bengali_name']
     * @param $operator ['=', 'like']
     * @param $value
     * @return array
     */
    public function getWhere($name, $operator, $value = null)
    {
        if(is_null($value)){
            $value = $operator;
            $operator = '=';
        }

        if($this->contents['parent'] == $name){
            $locations = $this->contents['data'][$value] ?? [];
        }else{
            $locations = $this->contents['data'];
            if(!$this->isDivisionSchema()){
                $locations = $this->buildFlatArray($locations);
            }
            $locations = array_filter($locations, function($location) use($name, $operator, $value){
                if(isset($location[$name])){
                    if($operator == '='){
                        return strtolower($location['name']) == strtolower($value);
                    }else if(strtolower($operator) == 'like'){
                        return strpos(strtolower($location['name']), strtolower($value)) !== false;
                    }
                }
                return false;
            });
        }

        return $this->buildCollection($locations);
    }

    private function getFileContent($filePath)
    {
        $content = json_decode(file_get_contents($filePath), true);
        return $content;
    }

    private function buildFlatArray($locations){
        $locationArrays = array_values($locations);
        $flatLocationArray = [];
        foreach ($locationArrays as $locationArray){
            $flatLocationArray = array_merge($flatLocationArray, $locationArray);
        }
        return $flatLocationArray;
    }

    private function buildCollection($locations)
    {
        $collection = [];
        foreach ($locations as $location) {
            $collection[] = $this->buildLocation($location);
        }
        return $collection;
    }

    /**
     * @param $location
     * @return Location
     */
    private function buildLocation($location)
    {
        return new Location(
            $location['name'],
            $location['short_name'],
            $location['bengali_name'],
            $location['website'],
            $location['longitude'],
            $location['latitude']
        );
    }

    private function isDivisionSchema(){
        return $this->schema == 'divisions';
    }
}