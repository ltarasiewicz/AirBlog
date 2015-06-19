<?php

namespace Common\UserBundle\Entity;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass="Common\UserBundle\Repository\UserRepository")
 * @ORM\Table(name="users")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"username"})
 * @UniqueEntity(fields={"email"})
 */
class User implements AdvancedUserInterface, \Serializable
{

    const DEFAULT_AVATAR = 'default-avatar.jpg';
    const UPLOAD_DIR = 'uploads/avatars/';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20, unique=true)
     *
     * @Assert\NotBlank(
     *      groups = {"Registration", "ChangeDetails"}
     * )
     *
     * @Assert\Length(
     *      min=5,
     *      max=20,
     *      groups = {"Registration", "ChangeDetails"}
     * )
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=120, unique=true)
     *
     * @Assert\NotBlank(
     *      groups = {"Registration"}
     * )
     * @Assert\Email(
     *      groups = {"Registration"}
     * )
     * @Assert\Length(
     *      max=120,
 *          groups = {"Registration"}
     * )
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @Assert\NotBlank(
     *      groups = {"Registration", "ChangePassword"}
     * )
     * @Assert\Length(
     *      min=8,
     *      groups = {"Registration", "ChangePassword"}
     * )
     */
    private $plainPassword;

    /**
     * @ORM\Column(name="account_non_expired", type="boolean")
     */
    private $accountNonExpired = true;

    /**
     * @ORM\Column(name="account_non_locked", type="boolean")
     */
    private $accountNonLocked = true;

    /**
     * @ORM\Column(name="credentials_non_expired", type="boolean")
     */
    private $credentialsNonExpired = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = false;

    /**
     * @ORM\Column(type="array")
     */
    private $roles;

    /**
     * @ORM\Column(name="action_token", type="string", length=20, nullable=true)
     */
    private $actionToken;

    /**
     * @ORM\Column(name="register_date", type="datetime")
     */
    private $registerDate;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $avatar;

    /**
     * @var UploadedFile
     * @Assert\Image(
     *      minWidth=50,
     *      maxWidth=150,
     *      minHeight=50,
     *      maxHeight=150,
     *      maxSize="1M",
     *      groups={"ChangeDetails"}
     * )
     */
    private $avatarFile;

    private $avatarTemp;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updateDate;

    public function getAvatarFile()
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(UploadedFile $avatarFile)
    {
        $this->avatarFile = $avatarFile;
        $this->updateDate = new \DateTime();
        return $this;
    }



    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        if(empty($this->roles)) {
            return array('ROLE_USER');
        }
        return $this->roles;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired()
    {
        return $this->accountNonExpired;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked()
    {
        return $this->accountNonLocked;
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired()
    {
        return $this->credentialsNonExpired;
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    public function __construct()
    {
        $this->registerDate = new \DateTime();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set accountNonExpired
     *
     * @param boolean $accountNonExpired
     * @return User
     */
    public function setAccountNonExpired($accountNonExpired)
    {
        $this->accountNonExpired = $accountNonExpired;

        return $this;
    }

    /**
     * Get accountNonExpired
     *
     * @return boolean 
     */
    public function getAccountNonExpired()
    {
        return $this->accountNonExpired;
    }

    /**
     * Set accountNonLocked
     *
     * @param boolean $accountNonLocked
     * @return User
     */
    public function setAccountNonLocked($accountNonLocked)
    {
        $this->accountNonLocked = $accountNonLocked;

        return $this;
    }

    /**
     * Get accountNonLocked
     *
     * @return boolean 
     */
    public function getAccountNonLocked()
    {
        return $this->accountNonLocked;
    }

    /**
     * Set credentialsNonExpired
     *
     * @param boolean $credentialsNonExpired
     * @return User
     */
    public function setCredentialsNonExpired($credentialsNonExpired)
    {
        $this->credentialsNonExpired = $credentialsNonExpired;

        return $this;
    }

    /**
     * Get credentialsNonExpired
     *
     * @return boolean 
     */
    public function getCredentialsNonExpired()
    {
        return $this->credentialsNonExpired;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     * @return User
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean 
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set roles
     *
     * @param array $roles
     * @return User
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Set actionToken
     *
     * @param string $actionToken
     * @return User
     */
    public function setActionToken($actionToken)
    {
        $this->actionToken = $actionToken;

        return $this;
    }

    /**
     * Get actionToken
     *
     * @return string 
     */
    public function getActionToken()
    {
        return $this->actionToken;
    }

    /**
     * Set registerDate
     *
     * @param \DateTime $registerDate
     * @return User
     */
    public function setRegisterDate($registerDate)
    {
        $this->registerDate = $registerDate;

        return $this;
    }

    /**
     * Get registerDate
     *
     * @return \DateTime 
     */
    public function getRegisterDate()
    {
        return $this->registerDate;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Set avatar
     *
     * @param string $avatar
     * @return User
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get avatar
     *
     * @return string 
     */
    public function getAvatar()
    {
        if(null === $this->avatar) {
            return User::UPLOAD_DIR . User::DEFAULT_AVATAR;
        }

        return User::UPLOAD_DIR . $this->avatar;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
        ));
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
        ) = unserialize($serialized);
    }


    public function setPlainPassword($plainPassword) {
        $this->plainPassword = $plainPassword;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preSave() {
        if(null !== $this->getAvatarFile()){

            if(null !== $this->avatar){
                $this->avatarTemp = $this->avatar;
            }
            $avatarName = sha1(uniqid(null, true));
            $this->avatar = $avatarName.'.'.$this->getAvatarFile()->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist
     * @ORM\PostUpdate
     */
    public function postSave(){
        if(null !== $this->getAvatarFile()){

            $this->getAvatarFile()->move($this->getUploadRootDir(), $this->avatar);
            unset($this->avatarFile);

            if(null !== $this->avatarTemp){
                unlink($this->getUploadRootDir().$this->avatarTemp);
                unset($this->avatarTemp);
            }
        }
    }

    protected function getUploadRootDir() {
        return __DIR__.'/../../../../web/'.User::UPLOAD_DIR;
    }

    public function setAccountExpired($accountNonExpired)
    {
        $this->accountNonExpired = !$accountNonExpired;

        return $this;
    }

    public function getAccountExpired()
    {
        return !$this->accountNonExpired;
    }

    public function setAccountLocked($accountNonLocked)
    {
        $this->accountNonLocked = !$accountNonLocked;

        return $this;
    }

    public function getAccountLocked()
    {
        return !$this->accountNonLocked;
    }

    public function setCredentialsExpired($credentialsNonExpired)
    {
        $this->credentialsNonExpired = !$credentialsNonExpired;

        return $this;
    }

    public function getCredentialsExpired()
    {
        return !$this->credentialsNonExpired;
    }

}
