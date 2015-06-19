<?php

namespace Common\UserBundle\Controller;

use Common\UserBundle\Exception\UserException;
use Common\UserBundle\Form\Type\RememberPasswordType;
use Common\UserBundle\Form\Type\RegisterUserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Common\UserBundle\Form\Type\LoginType;
use Symfony\Component\Form\FormError;
use Common\UserBundle\Entity\User;

class LoginController extends Controller
{
    /**
     * @Route(
     *      "/login",
     *      name="blog_login"
     * )
     * @Template()
     */
    public function loginAction(Request $request)
    {
        $session = $this->get('session');

        // Login Form
        if($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $loginError = $request->attributes->get(SecurityContextInterface::AUTHENTICATION_ERROR);
        } else {
            $loginError = $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);
        }

        if(isset($loginError)) {
            $this->get('session')->getFlashBag()->add('error', $loginError->getMessage());
        }

        $loginForm = $this->createForm(new LoginType(), array(
           'username'   =>    $session->get(SecurityContextInterface::LAST_USERNAME),
        ));

        // Remember Password Form
        $rememberPasswordForm = $this->createForm(new RememberPasswordType());

        if($request->isMethod('POST')) {
            $rememberPasswordForm->handleRequest($request);

            if($rememberPasswordForm->isValid()) {

                try{
                    $userEmail = $rememberPasswordForm->get('email')->getData();

                    $userManager = $this->get('user_manager');
                    $userManager->sendResetPasswordLink($userEmail);

                    $this->get('session')->getFlashBag()->add('success', 'Instrukcje resetowania zostały wyłane na adres email');
                    return $this->redirect($this->generateUrl('blog_login'));

                } catch (UserException $exc) {
                    $error = new FormError($exc->getMessage());
                    $rememberPasswordForm->get('email')->addError($error);
                }

            }
        }

        // Register User Form
        $user = new User();
        $registerUserForm = $this->createForm(new RegisterUserType(), $user);

        if($request->isMethod('POST')){
            $registerUserForm->handleRequest($request);

            if($registerUserForm->isValid()){

                try{

                    $userManager = $this->get('user_manager');
                    $userManager->registerUser($user);

                    $this->get('session')->getFlashBag()->add('success', 'Konto zostało utworzone. Na Twoją skrzynkę pocztową została wysłana wiadomość aktywacyjna.');

                    return $this->redirect($this->generateUrl('blog_login'));

                } catch (UserException $ex) {
                    $this->get('session')->getFlashBag()->add('error', $ex->getMessage());
                }

            }
        }


        return array(
            'loginForm' => $loginForm->createView(),
            'rememberPasswordForm' => $rememberPasswordForm->createView(),
            'registerUserForm' => $registerUserForm->createView()
        );
    }

    /**
     * @Route(
     *      "/account-activation/{actionToken}",
     *      name="user_activateAccount"
     * )
     */
    public function activateAccountAction($actionToken)
    {
        try {
            $userManager = $this->get('user_manager');
            $userManager->activateAccount($actionToken);

            $this->get('session')->getFlashBag()->add('success', 'Twoje konto zostało aktywowane');
        } catch (UserException $ex) {
            $this->get('session')->getFlashBag()->add('error', $ex->getMessage());
        }

        return $this->redirect($this->generateUrl('blog_login'));
    }

    /**
     * @Route(
     *      "/reset-password/{actionToken}",
     *      name="user_resetPassword"
     * )
     */
    public function resetAction($actionToken)
    {
        try {
            $userManager = $this->get('user_manager');
            $userManager->resetPassword($actionToken);

            $this->get('session')->getFlashBag()->add('success', 'Na Twój adres email zostało wysłane nowe hasło');
        } catch (Exception $ex) {
            $this->get('session')->getFlashBag()->add('error', $ex->getMessage());
        }

        return $this->redirect($this->generateUrl('blog_login'));
    }
}
