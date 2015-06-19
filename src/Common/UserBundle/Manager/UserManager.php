<?php

namespace Common\UserBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface as Templating;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Common\UserBundle\Mailer\UserMailer;
use Common\UserBundle\Entity\User;
use Common\UserBundle\Exception\UserException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserManager {

    /**
     * @var Doctrine
     */
    protected $doctrine;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Templating
     */
    protected $templating;

    /**
     * @var EncoderFactory
     */
    protected $encoderFactory;

    /**
     * @var UserMailer
     */
    protected $userMailer;


    function __construct(Doctrine $doctrine, Router $router, Templating $templating, EncoderFactory $encoderFactory, UserMailer $userMailer) {
        $this->doctrine = $doctrine;
        $this->router = $router;
        $this->templating = $templating;
        $this->encoderFactory = $encoderFactory;
        $this->userMailer = $userMailer;
    }

    protected function generateActionToken()
    {
        return substr(md5(uniqid(null, true)), 0, 20);
    }

    function sendResetPasswordLink($userEmail)
    {
        $user = $this->doctrine->getRepository('CommonUserBundle:User')
                            ->findOneByEmail($userEmail);

        if(null === $user) {
            throw new UserException('Nie znaleziono takiego użytkownika');
        }

        $user->setActionToken($this->generateActionToken());

        $em = $this->doctrine->getManager();
        $em->persist($user);
        $em->flush();

        $urlParams = array(
            'actionToken' => $user->getActionToken()
        );

        $resetUrl = $this->router->generate('user_resetPassword',
            $urlParams,
            UrlGeneratorInterface::ABSOLUTE_URL);

        $emailBody = $this->templating(render('CommonUserBundle:Email:passwordResetLink.html.twig', array(
            'resetUrl' => $resetUrl
        )));

        $this->userMailer->send($user, 'Link resetujący hasło', $emailBody);

        return true;

    }


    public function resetPassword($actionToken)
    {
        $user = $this->doctrine->getRepository('CommonUserBundle:User')
                            ->findOneByActionToken($actionToken);

        if(null === $user) {
            throw new UserException('Podano błędne parametry akcji');
        }

        $plainPassword = $this->getRandomPassword();
        $encoder = $this->encoderFactory->getEncoder($user);
        $encodedPassword = $encoder->encodePassword($plainPassword, $user->getSalt());

        $user->setPassword($encodedPassword);
        $user->setActionToken(null);

        $em = $this->doctrine->getManager();
        $em->persist($user);
        $em->flush();

        $emailBody = $this->templating->render('CommonUserBundle:Email:newPassword.html.twig', array(
            'plainPassword' => $plainPassword
        ));

        $this->userMailer->send($user, 'Nowe hasło do konta', $emailBody);

        return true;


    }

    protected function getRandomPassword($length = 8){
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    public function registerUser(User $user)
    {
        if(null !== $user->getId()) {
            throw new UserException('Użytkownik jest już zarejestrowany');
        }

        $encoder = $this->encoderFactory->getEncoder($user);
        $encodedPassword = $encoder->encodePassword($user->getPlainPassword(), $user->getSalt());

        $user->setPassword($encodedPassword);
        $user->setActionToken($this->generateActionToken());
        $user->setEnabled(false);

        $em = $this->doctrine->getManager();
        $em->persist($user);
        $em->flush();

        $urlParams = array(
            'actionToken'   =>  $user->getActionToken(),
        );
        $activationUrl = $this->router->generate('user_activateAccount', $urlParams, UrlGeneratorInterface::ABSOLUTE_URL);

        $emailBody = $this->templating->render('CommonUserBundle:Email:accountActivation.html.twig', array(
            'activationUrl' =>  $activationUrl,
        ));

        $this->userMailer->send($user, 'Aktywacja konta', $emailBody);

        return true;
    }

    public function activateAccount($actionToken)
    {
        $user = $this->doctrine->getRepository('CommonUserBundle:User')
            ->findOneByActionToken($actionToken);

        if(null === $user) {
            throw new UserException('Podano błędny parametr akcji');
        }

        $user->setEnabled(true);
        $user->setActionToken(null);

        $em = $this->doctrine->getManager();
        $em->persist($user);
        $em->flush();

        return true;
    }

    public function changePassword(User $User){

        if(null == $User->getPlainPassword()){
            throw new UserException('Nie ustawiono nowego hasła!');
        }

        $encoder = $this->encoderFactory->getEncoder($User);
        $encoderPassword = $encoder->encodePassword($User->getPlainPassword(), $User->getSalt());
        $User->setPassword($encoderPassword);

        $em = $this->doctrine->getManager();
        $em->persist($User);
        $em->flush();

        return true;
    }
}
