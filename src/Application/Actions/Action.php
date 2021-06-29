<?php
    declare(strict_types=1);
    
    namespace App\Application\Actions;
    
    //PHP Standard
    use App\Application\Actions\ActionPayload;
    use JsonSerializable;

//Local Framework
    use Slim\Exception\HttpBadRequestException;
    use Slim\Exception\HttpNotFoundException;

//3rd Party Vendor
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Psr\Log\LoggerInterface;

//Local Repo
    use App\Domain\DomainException\DomainRecordNotFoundException;
    use App\Domain\Oauth2;
    use App\Infrastructure\Action\Data\Type as TypeParser;
    use App\Infrastructure\Action\Paginator;
    
    
    abstract class Action
    {
        /**
         * @var LoggerInterface
         */
        protected $logger;
        
        /**
         * @var Request
         */
        protected $request;
        
        /**
         * @var Response
         */
        protected $response;
        
        /**
         * @var array
         */
        protected $args;
        
        /**
         * @TODO Sort out namespace issue for repository interface
         */
        protected $repository;
        
        /**
         * Default content type for action data
         * @var string
         */
        protected $defaultContentType = "";
        
        /**
         * @var array
         */
        protected $data = [];
        
        /**
         * Action constructor.
         * @param LoggerInterface $logger
         */
        public function __construct(LoggerInterface $logger)
        {
            $this->logger = $logger;
        }

        /**
         * @param Request $request
         * @param Response $response
         * @return Response
         */
        public function list(Request $request, Response $response): Response
        {
            $pager = new Paginator($request, $this->repository->getModel());
            
            $params = $request->getQueryParams();
            //@ToDo: prob be better to filter out valid url params
            // rather than model throw an exception
            unset($params["limit"]);
            unset($params["offset"]);
            $data = $this->repository->findAll($params, $pager->getLimit(), $pager->getOffset());

            return $this->respond(
                $response,
                (new ActionPayload(200, $data))->setPager($pager)
            );
        }
        
        /**
         * @param Response $response
         * @param ActionPayload $payload
         * @return Response
         */
        protected function respond(Response $response, ActionPayload $payload): Response
        {
            $json = json_encode($payload, JSON_PRETTY_PRINT);
            $response->getBody()->write($json);
            
            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus($payload->getStatusCode());
        }
        
        /**
         * @param Request $request
         * @param Response $response
         * @param $args
         * @return Response
         */
        public function getById(Request $request, Response $response, $args): Response
        {
            $model = $this->repository->getModel();
            return $this->respond(
                $response,
                new ActionPayload(200, $model->load($args["id"]))
            );
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param $args
         * @return Response
         */
        public function getModelById(Request $request, Response $response, $args) {
            $parts = array_filter(explode("/", $request->getUri()->getPath()));
            if(count($parts) >= 2) {
                $id = (int) array_pop($parts);
                try {
                    $model = $this->repository->getModel(array_pop($parts));
                    if (isset($args["id"]) && $id === (int) $args["id"]) {
                        $model->load($id);
                        if(!$model->isLoaded()) {
                            return $this->notFound($response);
                        }
    
                        return $this->respond(
                            $response,
                            new ActionPayload(200, $model->load($args["id"]))
                        );
                    }
                }
                catch(\Exception $e) {
                    //No need to do anything here, model name wrong, just return a bad request.
                }
            }
            return $this->badRequest($response);
        }
    
        /**
         * @param Request $request
         * @param Response $response
         * @param string $type
         * @return Response
         */
        public function listByModel(Response $response, string $type): Response
        {
            $model = $this->repository->getModel($type);
            if ($model) {
                $this->badRequest($response);
            }
            
            return $this->respond(
                $response,
                new ActionPayload(200, $model->findAll())
            );
        }

        /**
         * @param Request $request
         * @param Response $response
         * @return Response
         */
        public function create(Request $request, Response $response): Response
        {
            $data = $this->getData();
            if ($data) {
                $id = $this->repository->create($data);
            } else {
                $this->badRequest($response);
            }
            
            return $this->respond(
                $response,
                new ActionPayload(200, array('id' => $id))
            );
        }
        
        /**
         * @param null $k
         * @param null $default
         * @return array|false|float|int|mixed|\Services_JSON_Error|string|void|null
         */
        public function getData($k = null, $default = null)
        {
            if (!$this->data) {
                $contents = file_get_contents('php://input');
                
                if ($contents) {
                    $type = $this->getRequestContentType();
                    $this->data = TypeParser::parse($type, $contents);
                }
            }
            
            if ($k) {
                return $this->data[$k] ?? $default;
            }
            
            return $this->data;
        }
        
        /**
         * Allow an Action to have an enforced data content post type
         * @return bool|mixed
         */
        public function getRequestContentType()
        {
            if ($this->defaultContentType) {
                return $this->defaultContentType;
            }
            return $_SERVER["CONTENT_TYPE"];
        }
        
        /**
         * @param Response $response
         * @return Response
         */
        public function badRequest(Response $response): Response
        {
            return $response->withStatus(400);
        }
        
        /**
         * @param Request $request
         * @param Response $response
         * @param $args
         * @return Response
         */
        public function updateById(Request $request, Response $response, $args): Response
        {
            $model = $this->repository->getModel()->load($args["id"]);

            if (!$model->isLoaded()) {
                return $this->notFound($response);
            }

            $model->store($this->getData());
            return $this->noContent($response);
        }
        
        /**
         * @param Response $response
         * @return Response
         */
        public function notFound(Response $response): Response
        {
            return $response->withStatus(404);
        }
        
        /**
         * @param Response $response
         * @return Response
         */
        public function noContent(Response $response): Response
        {
            return $response->withStatus(203);
        }
        
        /**
         * @param Request $request
         * @param Response $response
         * @param $args
         * @return Response
         */
        public function deleteById(Request $request, Response $response, $args): Response
        {
            $this->repository->getModel()->deleteById((int) $args["id"]);
            return $this->noContent($response);
        }

        /**
         * @param JsonSerializable $obj
         * @param int $status
         * @return Response
         */
        public function jsonResponse(JsonSerializable $obj, $status = 200): Response
        {
            $json = json_encode($obj, JSON_PRETTY_PRINT);
            $this->response->getBody()->write($json);
            
            return $this->response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($status);
        }
        
        /**
         * @param Response $response
         * @return Response
         */
        public function noAuth(Response $response): Response
        {
            return $response->withStatus(401);
        }
        
        /**
         * @return array|object
         * @throws HttpBadRequestException
         */
        protected function getFormData()
        {
            $input = json_decode(file_get_contents('php://input'));
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new HttpBadRequestException($this->request, 'Malformed JSON input.');
            }
            
            return $input;
        }
        
        /**
         * @param string $name
         * @return mixed
         * @throws HttpBadRequestException
         */
        protected function resolveArg(string $name)
        {
            if (!isset($this->args[$name])) {
                throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
            }
            
            return $this->args[$name];
        }
        
        /**
         * @param array|object|null $data
         * @param int $statusCode
         * @return Response
         */
        protected function respondWithData($data = null, int $statusCode = 200): Response
        {
            $payload = new ActionPayload($statusCode, $data);
            return $this->respond($this->createPayload($statusCode, $data));
        }
        
        /**
         * @param int $status
         * @param $data
         * @return \App\Application\Actions\ActionPayload
         */
        public function createPayload(int $status, $data): ActionPayload
        {
            return new ActionPayload($status, $data);
        }
    }
