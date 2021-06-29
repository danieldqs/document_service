<?php
    declare(strict_types=1);
    
    namespace App\Domain;
    
    use Exception;
    
    class DomainException extends Exception
    {
        /**
         * Attempt to match erro messages to produce a api firendly version for the frontend
         * @var \string[][]
         */
        protected $templates = [
            [
                "regex" => "/.*1062 Duplicate entry.*for key \'[A-Za-z0-9_.]+\.(?<field>[A-Za-z0-9_.\s]+)\'/",
                "message" => "{field} already exists"
            ],
            [
                "regex" => "/a foreign key constraint fails.*FOREIGN KEY \(\`(?<field>[a-z_]+)\`\)/",
                "message" => "Incorrect value for {field}"
            ]
        ];

    //SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`account_service`.`membership`, CONSTRAINT `subscriptionFk` FOREIGN KEY (`subscription_id`) REFERENCES `subscription` (`id`) ON DELETE CASCADE ON UPDATE CASCADE)",
        
        /**
         * @return string|string[]
         */
        public function getFriendly()
        {
            $message = $this->getMessage();
            foreach ($this->templates as $template) {
                if (preg_match($template["regex"], $message, $match)) {
                    $message = $template["message"];
                    foreach ($match as $k => $v) {
                        if (is_string($k)) {
                            $message = str_replace(sprintf("{%s}", $k), $v, $message);
                        }
                    }
                    break;
                }
            }
            return $message;
        }
    }
