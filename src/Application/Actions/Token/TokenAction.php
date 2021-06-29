<?php

declare(strict_types=1);

namespace App\Application\Actions\Token;

use App\Application\Actions\Action;
use App\Application\Actions\ActionPayload;
use App\Domain\User\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Class UserAction
 * @package App\Application\Actions\User
 */
class TokenAction extends Action
{
  /**
   * Default content type for account action data is json
   * @var string
   */
  protected $defaultContentType = "application/json";

  /**
   * AccountAction constructor.
   * @param LoggerInterface $logger
   */
  public function __construct (LoggerInterface $logger)
  {
    parent::__construct($logger);
    $this->repository = new UserRepository();
  }

  /**
   * @param Request $request
   * @param Response $response
   * @param $args
   * @return Response|null
   * @throws \Exception
   */
  public function disable (Request $request, Response $response, $args): Response
  {
    $token = $args['token'] ?? null;
    $verify = $this->repository->verify($token);
    if ( $verify ) {
      $this->repository->setTokenInactive($token);
      return $response;
    }

    return $this->notFound($response);
  }

}