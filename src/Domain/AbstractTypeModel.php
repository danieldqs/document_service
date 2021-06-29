<?php
    
    
    namespace App\Domain;

    /**
     * Generic Abstraction for type tables that have only a label field
     * Include methods to cache results as type tables are queried often to check for
     * Model types
     * Class AbstractTypeModel
     * @package App\Domain
     */
    
    class AbstractTypeModel extends AbstractModel
    {
        /**
         * @var array
         */
        protected $columns = [
            'label'
        ];
    
        /**
         * @var array
         */
        protected static $cache = [];
    
        /**
         * @param string $type
         * @param int $id
         * @return mixed
         * @throws \Exception
         */
        public function getLabel(int $id) {
        
            $cache = $this->getCache();
            if(isset($cache[$id])) {
                return $cache[$id]["label"];
            }
        }
    

        /**
         * Clear Cache after a save so new labels are not missed
         * @ToDo Could make this more intelligent to save a select to the db after save
         */
        public function afterSave()
        {
            self::$cache = [];
        }
    
        /**
         * @param $type
         * @throws \Exception
         */
        public function getCache() {
            $cls = get_called_class();
            if(!isset(self::$cache[$cls])) {
                $labels = $this->findAll();
                $data = [];
                
                foreach($labels as $label) {
                    $data[$label["id"]] = $label;
                }
                self::$cache[$cls] = $data;
            }
            return self::$cache[$cls];
        }
    
        /**
         * @param string $type
         * @param string $label
         * @return false|int|string
         */
        public function getLabelId(string $label) {
            $cache = $this->getCache();
            foreach($cache as $id => $data) {
                if(strcasecmp($label, $data["label"]) === 0) {
                    return $id;
                }
            }
            return false;
        }
    }