<?php
    
    
    namespace App\Domain;
    
    use \App\Domain\AbstractTypeModel as ATM;

    /**
     * This model is used for any models that required a type column and has additional methods for that purpose
     * Class AbstractTypeModel
     * @package App\Domain
     */
    
    class AbstractTypedModel extends AbstractModel
    {
        
        /*
         * @var AbstractTypeModel
         */
        protected $typeModel;
    
        /**
         * @ToDo could be made more flexible, by concating the name of the model with Type and
         * checking if a class exists and is of type AbstractTypeModel
         * @return AbstractTypeModel|mixed
         * @throws \Exception
         */
        public function getTypeModel() {
            
            if(class_exists($this->typeModel)) {
                if(is_subclass_of($this->typeModel, ATM::class)){
                       $tm = $this->typeModel;
                       return new $tm();
                }
                else {
                    throw new \Exception("Type Model must be instance of " . ATM::class);
                }
            }
            else {
                throw new \Exception("Type Model must be a classname");
            }
        }
    
        /**
         * Override parent populate method to automatically convert a type index into a type_id
         * @param array $data
         * @return array
         * @throws \Exception
         */
        public function populate(array $data): array
        {
            if(isset($data["type"])) {
                $type = $data["type"];
                $id = $this->getTypeModel()->getLabelId($type);
                if(!$id) {
                    throw new \Exception("Invalid Type $type");
                }
                
                $data["type_id"] = $id;
                unset($data["type"]);
            }
            
            return parent::populate($data);
        }
    }