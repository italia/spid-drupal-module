<?php

namespace Drupal\spid\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\spid\SpidServiceInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for spid module routes.
 */
class SpidController extends ControllerBase {

  /**
   * The Spid service.
   *
   * @var \Drupal\spid\SpidServiceInterface
   */
  protected $spid;

  /**
   * The Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor for Drupal\spid\Controller\SpidController.
   *
   * @param \Drupal\spid\SpidServiceInterface $spid
   *   The Spid service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The Request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger service.
   */
  public function __construct(SpidServiceInterface $spid, RequestStack $request_stack, MessengerInterface $messenger) {
    $this->spid = $spid;
    $this->requestStack = $request_stack;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('spid'),
      $container->get('request_stack'),
      $container->get('messenger')
    );
  }

  /**
   * Initiates a SPID authentication flow.
   *
   * This should redirect to the Login service on the IDP and then to our ACS.
   *
   * @param string $idp
   *   The IDP to login to.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A RedirectResponse to the URL to go in case of failure.
   */
  public function login($idp) {
    $level = $this->config('spid.settings')->get('spid_level');

    try {
      $this->spid->login($idp, $level, "/user");
      throw new Exception('Not redirected to SPID IDP');
    }
    catch (Exception $e) {
      $this->handleException($e, 'initiating SPID login');
    }

    // TODO: the redirect URL should be configurable.
    return new RedirectResponse(Url::fromRoute('<front>', [], ['absolute' => TRUE])
      ->toString());
  }

  /**
   * Assertion Consumer Service.
   *
   * This is usually the second step in the authentication flow; the Login
   * service on the IDP should redirect (or: execute a POST request to) here.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP Request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A RedirectResponse to the URL to go after login.
   */
  public function acs(Request $request) {
    // TODO: the redirect URL should be configurable.
    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    try {
      $this->spid->acs();
      $url = $request->get('RelayState');
    }
    catch (Exception $e) {
      $this->handleException($e, 'processing SPID authentication response');
    }

    return new RedirectResponse($url);
  }

  /**
   * Initiate a SPID logout flow.
   *
   * This should redirect to the SLS service on the IDP and then to our SLS.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A RedirectResponse to the URL to go after logout.
   */
  public function logout() {
    // TODO: the redirect URL should be configurable.
    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    try {
      $this->spid->logout($url);
      throw new Exception('Not redirected to SPID IDP');
    }
    catch (Exception $e) {
      $this->handleException($e, 'initiating SPID logout');
    }

    return new RedirectResponse($url);
  }

  /**
   * Single Logout Service.
   *
   * This is usually the second step in the logout flow; the SLS service on the
   * IDP should redirect here.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A RedirectResponse to the URL to go after logout.
   */
  public function slo() {
    $this->spid->slo();

    // TODO: the redirect URL should be configurable.
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  /**
   * Displays service provider metadata XML for IDP autoconfiguration.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The SP metadata.
   */
  public function metadata() {
    $metadata = $this->spid->getMetadata();

    $response = new Response($metadata, 200);
    $response->headers->set('Content-Type', 'text/xml');
    return $response;
  }

  /**
   * Displays error message and logs full exception.
   *
   * @param \Exception $exception
   *   The exception thrown.
   * @param string $while
   *   A description of when the error was encountered.
   */
  protected function handleException(\Exception $exception, $while = '') {
    if ($while) {
      $while = " $while";
    }
    // We use the same format for logging as Drupal's ExceptionLoggingSubscriber
    // except we also specify where the error was encountered. (The options are
    // limited, so we make this part of the message, not a context parameter.)
    $error = Error::decodeException($exception);
    unset($error['severity_level']);
    $this->getLogger('spid')
      ->critical("%type encountered while $while: @message in %function (line %line of %file).", $error);
    // Don't expose the error to prevent information leakage; the user probably
    // can't do much with it anyway. But hint that more details are available.
    $this->messenger->addError("Error $while; details have been logged.");
  }

}
