<?php
    
    namespace App\Infrastructure\Action;
    
    use App\Domain\AbstractModel;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use App\Infrastructure\Environment as E;
    
    class Paginator implements \JsonSerializable
    {
        
        const MAX_LIMIT = 200;
        
        /**
         * @var Request
         */
        protected $request;
        
        /**
         * @var AbstractModel
         */
        protected $model;
        
        /**
         * @var int
         */
        protected $modelCount;
        
        /**
         * @var int
         */
        protected $limit = 100;
        
        /**
         * @var int
         */
        protected $offset;
        
        /**
         * Paginator constructor.
         * @param Request $request
         * @param AbstractModel $model
         */
        public function __construct(Request $request, AbstractModel $model)
        {
            $this->request = $request;
            $this->model = $model;
            $this->limit = E::getValue("REQUEST_PAGE_SIZE", $this->limit);
        }
        
        /**
         * @return int
         */
        public function getModelCount(): int
        {
            if (is_null($this->modelCount)) {
                $this->modelCount = $this->model->getCount();
            }
            
            return $this->modelCount;
        }
        
        /**
         * @return int
         */
        public function getLimit(): int
        {
            $p = $this->request->getQueryParams();
            $limit = isset($p['limit']) ? (int) $p['limit'] : $this->limit;
            if ($limit > $this::MAX_LIMIT) {
                $limit = $this::MAX_LIMIT;
            }
            return $limit;
        }
        
        /**
         * @return int
         */
        public function getOffset(): int
        {
            $p = $this->request->getQueryParams();
            return isset($p['offset']) ? (int) $p['offset'] : 0;
        }
        
        /**
         * @return array
         */
        public function getLimitOffset(): array
        {
            return [
                $this->getLimit(), $this->getOffset()
            ];
        }
        
        /**
         * @return int
         */
        public function getCurrentPage(): int
        {
            
            $limit = $this->getLimit();
            $offset = $this->getOffset();
            return ($offset > 0) ? (int) (floor($offset / $limit)) : 0;
        }
        
        /**
         * @return int
         */
        public function getTotalPages(): int
        {
            $pages = 0;
            if ($count = $this->getModelCount()) {
                $pages = ceil($count / $this->getLimit());
            }
            
            return $pages;
        }
        
        /**
         * @return int|null
         */
        public function getNext()
        {
            list($l, $o) = $this->getLimitOffset();
            $count = $this->getModelCount();
            $next = null;
            if (($o + $this->getLimit()) < $count) {
                $next = $o + $this->getLimit();
            }
            
            return $next;
        }
        
        /**
         * @return int|null
         */
        public function getPrev()
        {
            list($l, $o) = $this->getLimitOffset();
            $prev = null;
            if (($o - $this->getLimit()) > 0) {
                $prev = $o - $this->getLimit();
            }
            return $prev;
        }
        
        /**
         * @param string $type
         * @return string
         */
        public function getLink(string $type): string
        {
            $link = "";
            $method = "get" . ucwords($type);
            if (method_exists($this, $method) && in_array($type, ["next", "prev"])) {
                $offset = $this->$method();
                if (!is_null($offset)) {
                    $url = $this->request->getUri()->getPath();
                    $params = ["limit" => $this->getLimit(), "offset" => $offset];
                    $link = sprintf(
                        "%s?%s",
                        $url,
                        http_build_query($this->updateParams($params), '', '&')
                    );
                }
            }
            return $link;
        }
        
        /**
         * @param array $replacements
         * @return array
         */
        public function updateParams(array $replacements): array
        {
            
            $params = $this->request->getQueryParams();
            foreach ($replacements as $k => $replacement) {
                $params[$k] = $replacement;
            }
            return $params;
        }
        
        /**
         * @return array
         */
        public function getLinks()
        {
            return [
                "next" => $this->getLink("next"),
                "prev" => $this->getLink("prev"),
                "page" => $this->getCurrentPage(),
                "pages" => $this->getTotalPages(),
                "total" => $this->getModelCount()
            ];
        }
        
        /**
         * @return false|float|int|mixed|\Services_JSON_Error|string|void
         */
        public function jsonSerialize()
        {
            return $this->getLinks();
        }
    }
    