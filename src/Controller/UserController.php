<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Class UserController
 *
 * @package App\Controller
 */
class UserController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * UserController constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param UserHelper             $userHelper
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UserHelper $userHelper
    )
    {
        $this->entityManager = $entityManager;
        $this->userHelper = $userHelper;
    }

    /**
     * If we have reached here then return a 200
     * Error responses are sent from within LoginAuthenticator
     * @return JsonResponse
     */
    public function authenticate(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @param Request                      $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     *
     * @return JsonResponse
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder): JsonResponse
    {
        /** @var User $user */
        $user = new User();

        //TODO: Extract to Form class
        $form = $this->createFormBuilder($user, ['csrf_protection' => false])
            ->add('email', EmailType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new NotNull(),
                    new Email()
                ]

            ])
            ->add('password', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new NotNull(),
                    new NotCompromisedPassword()
                ]
            ])
            ->getForm()
        ;

        $form->handleRequest($request);

        $form->submit(['email' => $request->get('email'), 'password' => $request->get('password')]);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $user->setPassword($passwordEncoder->encodePassword($user, null));
            $user = $this->userHelper->refreshUserAuthToken($user);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return new JsonResponse(['token' => $user->getAuthToken()], Response::HTTP_CREATED);
        }

        return new JsonResponse($this->getErrorsFromForm($form));
    }

    /**
     * @param FormInterface $form
     *
     * @return array
     */
    //TODO: extract to BaseApiController class
    private function getErrorsFromForm(FormInterface $form): array
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if (($childForm instanceof FormInterface) && $childErrors = $this->getErrorsFromForm($childForm)) {
                $errors[$childForm->getName()] = $childErrors;
            }
        }
        return $errors;
    }
}
