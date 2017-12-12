<?php

namespace Drupal\spid\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\spid\SamlServiceInterface;
use Exception;
use OneLogin_Saml2_Utils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for spid module routes.
 */
class SamlController extends ControllerBase {

  /**
   * The spid SAML service.
   *
   * @var \Drupal\spid\SamlServiceInterface
   */
  protected $saml;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor for Drupal\spid\Controller\SamlController.
   *
   * @param \Drupal\spid\SamlServiceInterface $saml
   *   The spid SAML service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(SamlServiceInterface $saml, RequestStack $request_stack) {
    $this->saml = $saml;
    $this->requestStack = $request_stack;
  }

  /**
   * Factory method for dependency injection container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('spid.saml'),
      $container->get('request_stack')
    );
  }

  /**
   * Initiates a SAML2 authentication flow.
   *
   * This should redirect to the Login service on the IDP and then to our ACS.
   */
  public function login($idp) {
    try {
      $relayState = $this->createRelayState($idp);
      $this->saml->login($idp, $relayState);
      // We don't return here unless something is fundamentally wrong inside the
      // SAML Toolkit sources.
      throw new Exception('Not redirected to SAML IDP');
    } catch (Exception $e) {
      $this->handleException($e, 'initiating SAML login');
    }
    return new RedirectResponse(Url::fromRoute('<front>', [], ['absolute' => TRUE])
      ->toString());
  }

  /**
   * Initiate a SAML2 logout flow.
   *
   * This should redirect to the SLS service on the IDP and then to our SLS.
   */
  public function logout() {
    try {
      $relayState = $this->createRelayState($this->currentUser()->id());
      $this->saml->logout($relayState);
      // We don't return here unless something is fundamentally wrong inside the
      // SAML Toolkit sources.
      throw new Exception('Not redirected to SAML IDP');
    } catch (Exception $e) {
      $this->handleException($e, 'initiating SAML logout');
    }
    return new RedirectResponse(Url::fromRoute('<front>', [], ['absolute' => TRUE])
      ->toString());
  }

  /**
   * Displays service provider metadata XML for iDP autoconfiguration.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function metadata() {
    try {
      $metadata = $this->saml->getMetadata();
    } catch (Exception $e) {
      $this->handleException($e, 'processing SAML SP metadata');
      return new RedirectResponse(Url::fromRoute('<front>', [], ['absolute' => TRUE])
        ->toString());
    }

    $response = new Response($metadata, 200);
    $response->headers->set('Content-Type', 'text/xml');
    return $response;
  }

  /**
   * Attribute Consumer Service.
   *
   * This is usually the second step in the authentication flow; the Login
   * service on the IDP should redirect (or: execute a POST request to) here.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function acs() {
    try {
      list($idp,) = $this->parseRelayState();
      $this->saml->acs($idp);
      $url = $this->getRedirectUrlAfterProcessing(TRUE);
    } catch (Exception $e) {
      $this->handleException($e, 'processing SAML authentication response');
      $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
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
   */
  public function sls() {
    try {
      list($uid,) = $this->parseRelayState();
      if ($uid) {
        $this->saml->sls($uid);
        $url = $this->getRedirectUrlAfterProcessing();
      }
    } catch (Exception $e) {
      $this->handleException($e, 'processing SAML single-logout response');
      $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    }

    return new RedirectResponse($url);
  }

  /**
   * Constructs a full URL from the 'destination' parameter.
   *
   * @return string|null
   *   The full absolute URL (i.e. leading back to ourselves), or NULL if no
   *   destination parameter was given. This value is tuned to what login() /
   *   logout() expect for an input argument.
   *
   * @throws \RuntimeException
   *   If the destination is disallowed.
   */
  protected function getUrlFromDestination() {
    $destination_url = NULL;
    $destination = $this->requestStack->getCurrentRequest()->query->get('destination');
    if ($destination) {
      if (UrlHelper::isExternal($destination)) {
        // Prevent authenticating and then redirecting somewhere else.
        throw new \RuntimeException("Destination URL query parameter must not be external: $destination");
      }
      // The destination parameter is relative by convention but fromUserInput()
      // requires it to start with '/'. (Note '#' and '?' don't make sense here
      // because that would be expanded to the current URL, which is saml/*.)
      if (strpos($destination, '/') !== 0) {
        $destination = "/$destination";
      }
      $destination_url = Url::fromUserInput($destination)
        ->setAbsolute()
        ->toString();
    }

    return $destination_url;
  }

  /**
   * Returns a URL to redirect to.
   *
   * This should be called only after successfully processing an ACS/logout
   * response.
   *
   * @param bool $logged_in
   *   (optional) TRUE if an ACS request was just processed.
   *
   * @return string|null
   *   The URL to redirect to.
   */
  protected function getRedirectUrlAfterProcessing($logged_in = FALSE) {
    $url = '';
    list(, $redirectTo) = $this->parseRelayState();
    if (isset($redirectTo) && $redirectTo != "") {
      // We should be able to trust the RelayState parameter at this point
      // because the response from the IDP was verified. Only validate general
      // syntax.
      if (!UrlHelper::isValid($redirectTo, TRUE)) {
        $this->getLogger('spid')
          ->error('Invalid RelayState parameter found in request: @relaystate', ['@relaystate' => $redirectTo]);
      }
      // The SAML toolkit set a default RelayState to itself (saml/log(in|out))
      // when starting the process; ignore this.
      elseif (strpos($redirectTo, OneLogin_Saml2_Utils::getSelfURLhost() . '/saml/') !== 0) {
        $url = $redirectTo;
      }
    }

    if (!$url) {
      // If no url was specified, we have a hardcoded route to redirect to.
      $route = $logged_in ? 'user.page' : '<front>';
      $url = Url::fromRoute($route, [], ['absolute' => TRUE])->toString();
    }

    return $url;
  }

  /**
   * Displays error message and logs full exception.
   *
   * @param $exception
   *   The exception thrown.
   * @param string $while
   *   A description of when the error was encountered.
   */
  protected function handleException($exception, $while = '') {
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
    drupal_set_message("Error $while; details have been logged.", 'error');
  }

  /**
   * @param $data
   *   The data to pass as relay state.
   *
   * @return string
   */
  protected function createRelayState($data) {
    return base64_encode(implode('+', [
      $data,
      $this->getUrlFromDestination(),
    ]));
  }

  /**
   * @return array
   */
  protected function parseRelayState() {
    return explode('+', base64_decode($this->requestStack->getCurrentRequest()
      ->get('RelayState')));
  }
}
